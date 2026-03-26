<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierDelivery;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;

class SupplierLedgerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $suppliers = Supplier::withSum('deliveries', 'amount')
            ->withSum('supplierPayments', 'amount')
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get()
            ->map(function ($s) {
                $s->total_delivered = (float) ($s->deliveries_sum_amount ?? 0);
                $s->total_paid      = (float) ($s->supplier_payments_sum_amount ?? 0);
                $s->balance         = max(0, $s->total_delivered - $s->total_paid);
                return $s;
            })
            ->sortByDesc('balance');

        return view('supplier-ledger.index', compact('suppliers', 'search'));
    }

    public function show(Supplier $supplier, Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');

        $deliveries = SupplierDelivery::where('supplier_id', $supplier->id)
            ->when($dateFrom, fn($q) => $q->whereDate('delivery_date', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('delivery_date', '<=', $dateTo))
            ->get()
            ->map(fn($d) => [
                'id'     => $d->id,
                'type'   => 'delivery',
                'date'   => $d->delivery_date,
                'dr'     => $d->dr_number,
                'debit'  => (float) $d->amount,
                'credit' => 0,
                'notes'  => $d->notes,
                'model'  => $d,
            ]);

        $payments = SupplierPayment::where('supplier_id', $supplier->id)
            ->when($dateFrom, fn($q) => $q->whereDate('payment_date', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('payment_date', '<=', $dateTo))
            ->get()
            ->map(fn($p) => [
                'id'     => $p->id,
                'type'   => 'payment',
                'date'   => $p->payment_date,
                'dr'     => $p->reference_no ?? '—',
                'debit'  => 0,
                'credit' => (float) $p->amount,
                'notes'  => $p->notes,
                'model'  => $p,
            ]);

        $entries = $deliveries->merge($payments)->sortBy('date')->values();

        // Build running balance
        $runningBalance = 0;
        $entries = $entries->map(function ($entry) use (&$runningBalance) {
            $runningBalance += $entry['debit'] - $entry['credit'];
            $entry['running_balance'] = $runningBalance;
            return $entry;
        });

        $totalDelivered = $deliveries->sum('debit');
        $totalPaid      = $payments->sum('credit');
        $balance        = max(0, $totalDelivered - $totalPaid);

        return view('supplier-ledger.show', compact(
            'supplier', 'entries', 'totalDelivered', 'totalPaid', 'balance', 'dateFrom', 'dateTo'
        ));
    }

    public function storeDelivery(Request $request, Supplier $supplier)
    {
        $request->validate([
            'dr_number'     => 'required|string|max:100',
            'delivery_date' => 'required|date',
            'amount'        => 'required|numeric|min:0.01',
            'notes'         => 'nullable|string|max:500',
        ]);

        SupplierDelivery::create([
            'supplier_id'   => $supplier->id,
            'dr_number'     => $request->dr_number,
            'delivery_date' => $request->delivery_date,
            'amount'        => $request->amount,
            'notes'         => $request->notes,
        ]);

        return redirect()->route('supplier-ledger.show', $supplier)
            ->with('success', 'Delivery record added.');
    }

    public function storePayment(Request $request, Supplier $supplier)
    {
        $request->validate([
            'payment_date'   => 'required|date',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,check,bank_transfer,e_wallet,other',
            'reference_no'   => 'nullable|string|max:100',
            'notes'          => 'nullable|string|max:500',
        ]);

        SupplierPayment::create([
            'supplier_id'    => $supplier->id,
            'payment_date'   => $request->payment_date,
            'amount'         => $request->amount,
            'payment_method' => $request->payment_method,
            'reference_no'   => $request->reference_no,
            'notes'          => $request->notes,
        ]);

        return redirect()->route('supplier-ledger.show', $supplier)
            ->with('success', 'Payment recorded.');
    }

    public function destroyDelivery(Supplier $supplier, SupplierDelivery $delivery)
    {
        $delivery->delete();
        return redirect()->route('supplier-ledger.show', $supplier)
            ->with('success', 'Delivery record deleted.');
    }

    public function destroyPayment(Supplier $supplier, SupplierPayment $payment)
    {
        $payment->delete();
        return redirect()->route('supplier-ledger.show', $supplier)
            ->with('success', 'Payment record deleted.');
    }
}
