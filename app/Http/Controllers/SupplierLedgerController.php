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
        $search = trim($request->get('search', ''));

        $suppliers = Supplier::withSum('deliveries', 'amount')
            ->withSum('supplierPayments', 'amount')
            ->when($search, fn($q) => $q->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->get()
            ->map(function ($s) {
                $totalDelivered = (float) ($s->deliveries_sum_amount ?? 0);
                $totalPaid = (float) ($s->supplier_payments_sum_amount ?? 0);
                $s->total_delivered = $totalDelivered;
                $s->total_paid = $totalPaid;
                $s->balance = max(0, $totalDelivered - $totalPaid);
                return $s;
            });

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
        $allDeliveries->load('purchaseOrder');

        $deliveries = collect($allDeliveries)
            ->when($search, fn($c) => $c->filter(fn($d) =>
                str_contains(strtolower($d->dr_number ?? ''), strtolower($search)) ||
                str_contains(strtolower($d->notes ?? ''), strtolower($search)) ||
                str_contains($d->delivery_date->format('Y-m-d'), $search) ||
                str_contains($d->delivery_date->format('M d, Y'), $search)
            ))
            ->map(fn($d) => [
                'id'          => $d->id,
                'type'        => 'delivery',
                'date'        => $d->delivery_date,
                'dr'          => $d->dr_number,
                'debit'       => (float) $d->amount,
                'credit'      => 0,
                'notes'       => $d->notes,
                'po_number'   => $d->purchaseOrder?->po_number,
                'from_po'     => $d->purchase_order_id !== null,
                'due_date'    => $d->purchaseOrder?->expected_arrival_date,
                'terms_count' => $d->purchaseOrder?->payment_terms_count,
                'terms_days'  => $d->purchaseOrder?->payment_terms_days,
                'remaining_terms' => $d->purchaseOrder?->remaining_terms,
                'suggested_term_amount' => $d->purchaseOrder?->suggested_term_amount,
                'model'       => $d,
            ]);

        $allPayments->load('purchaseOrder');

        $payments = collect($allPayments)
            ->when($search, fn($c) => $c->filter(fn($p) =>
                str_contains(strtolower($p->reference_no ?? ''), strtolower($search)) ||
                str_contains(strtolower($p->notes ?? ''), strtolower($search)) ||
                str_contains($p->payment_date->format('Y-m-d'), $search) ||
                str_contains($p->payment_date->format('M d, Y'), $search) ||
                str_contains(strtolower($p->purchaseOrder?->expected_arrival_date?->format('M d, Y') ?? ''), strtolower($search))
            ))
            ->map(fn($p) => [
                'id'        => $p->id,
                'type'      => 'payment',
                'date'      => $p->payment_date,
                'dr'        => $p->reference_no ?? '—',
                'debit'     => 0,
                'credit'    => (float) $p->amount,
                'notes'     => $p->notes,
                'po_number' => $p->purchaseOrder?->po_number,
                'from_po'   => $p->purchase_order_id !== null,
                'due_date'    => $p->purchaseOrder?->expected_arrival_date,
                'terms_count' => $p->purchaseOrder?->payment_terms_count,
                'terms_days'  => $p->purchaseOrder?->payment_terms_days,
                'remaining_terms' => $p->purchaseOrder?->remaining_terms,
                'suggested_term_amount' => $p->purchaseOrder?->suggested_term_amount,
                'model'     => $p,
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
