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
        $search = trim($request->get('search', ''));

        // Always load ALL entries for accurate running balance totals
        $allDeliveries = SupplierDelivery::where('supplier_id', $supplier->id)->get();
        $allPayments   = SupplierPayment::where('supplier_id', $supplier->id)->get();

        $totalDelivered = (float) $allDeliveries->sum('amount');
        $totalPaid      = (float) $allPayments->sum('amount');
        $balance        = max(0, $totalDelivered - $totalPaid);

        // Apply search filter for display only
        $deliveries = $allDeliveries
            ->when($search, fn($c) => $c->filter(fn($d) =>
                str_contains(strtolower($d->dr_number ?? ''), strtolower($search)) ||
                str_contains(strtolower($d->notes ?? ''), strtolower($search)) ||
                str_contains($d->delivery_date->format('Y-m-d'), $search) ||
                str_contains($d->delivery_date->format('M d, Y'), $search)
            ))
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

        $payments = $allPayments
            ->when($search, fn($c) => $c->filter(fn($p) =>
                str_contains(strtolower($p->reference_no ?? ''), strtolower($search)) ||
                str_contains(strtolower($p->notes ?? ''), strtolower($search)) ||
                str_contains($p->payment_date->format('Y-m-d'), $search) ||
                str_contains($p->payment_date->format('M d, Y'), $search)
            ))
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

        // Merge and sort by date, then rebuild running balance on filtered set
        $entries = $deliveries->merge($payments)->sortBy('date')->values();

        $runningBalance = 0;
        $entries = $entries->map(function ($entry) use (&$runningBalance) {
            $runningBalance += $entry['debit'] - $entry['credit'];
            $entry['running_balance'] = $runningBalance;
            return $entry;
        });

        return view('supplier-ledger.show', compact(
            'supplier', 'entries', 'totalDelivered', 'totalPaid', 'balance', 'search'
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
