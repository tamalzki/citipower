@extends('layouts.app')
@section('title', $supplier->name . ' — Ledger')

@section('content')
<style>
/* ── KPI row ── */
.sl-kpi-row {
    display: flex;
    gap: 0;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 6px rgba(15,23,42,.06);
    margin-bottom: 16px;
}
.sl-kpi-cell {
    flex: 1;
    padding: 14px 22px;
    border-right: 1px solid #f1f5f9;
    min-width: 0;
}
.sl-kpi-cell:last-child { border-right: none; }
.sl-kpi-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: #94a3b8;
    margin-bottom: 4px;
}
.sl-kpi-value {
    font-size: 24px;
    font-weight: 800;
    line-height: 1.15;
    white-space: nowrap;
}

/* ── Ledger card ── */
.sl-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 6px rgba(15,23,42,.06);
}
.sl-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    gap: 10px;
    flex-wrap: wrap;
}
.sl-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.sl-table thead th {
    background: #1e293b;
    color: #fff;
    padding: 10px 13px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    white-space: nowrap;
    text-align: left;
}
.sl-table thead th.r { text-align: right; }
.sl-table tbody td {
    padding: 10px 13px;
    border-bottom: 1px solid #f8fafc;
    vertical-align: middle;
}
.sl-table tbody tr:hover { background: #f9fafb; }
.sl-table tfoot td {
    background: #0f172a;
    color: #fff;
    font-weight: 700;
    padding: 10px 13px;
}
.sl-table tfoot td.r { text-align: right; }

.dr-badge { font-weight: 700; color: #1d4ed8; font-size: 13px; }
.po-pill {
    display: inline-block;
    margin-top: 3px;
    font-size: 10px;
    font-weight: 700;
    color: #7c3aed;
    background: #f3e8ff;
    border-radius: 20px;
    padding: 1px 8px;
    text-decoration: none;
}
.po-pill:hover { background: #ede9fe; }
.debit  { font-weight: 700; color: #dc2626; }
.credit { font-weight: 700; color: #16a34a; }
.bal-owe  { font-weight: 700; color: #dc2626; }
.bal-ok   { font-weight: 700; color: #16a34a; }
.muted    { color: #cbd5e1; }
.via-po-tag { font-size: 10px; color: #94a3b8; font-style: italic; }
</style>

{{-- ── Supplier header ── --}}
<div style="display:flex; align-items:flex-start; justify-content:space-between;
            flex-wrap:wrap; gap:12px; margin-bottom:16px;">
    <div>
        <div style="display:flex; align-items:center; gap:10px;">
            <a href="{{ route('supplier-ledger.index') }}"
               style="font-size:13px; color:#64748b; text-decoration:none; font-weight:600;">
                ← All Suppliers
            </a>
            <span style="color:#cbd5e1;">/</span>
            <h2 style="margin:0; font-size:20px; font-weight:800; color:#0f172a;">
                {{ $supplier->name }}
            </h2>
            @if($balance > 0)
                <span style="background:#fee2e2; color:#dc2626; font-size:11px; font-weight:700;
                             border-radius:20px; padding:2px 10px;">Unpaid</span>
            @else
                <span style="background:#dcfce7; color:#16a34a; font-size:11px; font-weight:700;
                             border-radius:20px; padding:2px 10px;">✓ Settled</span>
            @endif
        </div>
        @if($supplier->contact_person || $supplier->phone)
        <p style="margin:4px 0 0; font-size:12px; color:#64748b;">
            {{ implode(' · ', array_filter([$supplier->contact_person, $supplier->phone])) }}
        </p>
        @endif
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <button onclick="document.getElementById('delivery-modal').style.display='flex'"
                class="btn btn-secondary">+ Add Delivery (DR)</button>
        <button onclick="document.getElementById('payment-modal').style.display='flex'"
                class="btn btn-primary">+ Record Payment</button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:12px;">{{ session('success') }}</div>
@endif

{{-- ── KPI Row ── --}}
<div class="sl-kpi-row">
    <div class="sl-kpi-cell">
        <div class="sl-kpi-label">Total Delivered</div>
        <div class="sl-kpi-value" style="color:#dc2626;">₱{{ number_format($totalDelivered, 2) }}</div>
    </div>
    <div class="sl-kpi-cell">
        <div class="sl-kpi-label">Total Paid</div>
        <div class="sl-kpi-value" style="color:#16a34a;">₱{{ number_format($totalPaid, 2) }}</div>
    </div>
    <div class="sl-kpi-cell" style="background:{{ $balance > 0 ? '#fff7f7' : '#f0fdf4' }};">
        <div class="sl-kpi-label">Outstanding Balance</div>
        <div class="sl-kpi-value {{ $balance > 0 ? 'bal-owe' : 'bal-ok' }}">
            ₱{{ number_format($balance, 2) }}
        </div>
    </div>
</div>

{{-- ── Ledger Table ── --}}
<div class="sl-card">

    {{-- Toolbar: search --}}
    <div class="sl-toolbar">
        <span style="font-size:13px; font-weight:700; color:#0f172a;">
            Transaction Ledger
            @if($entries->count())
                <span style="font-size:12px; font-weight:500; color:#94a3b8; margin-left:4px;">
                    ({{ $entries->count() }} records)
                </span>
            @endif
        </span>
        <form method="GET" style="display:flex; gap:8px; align-items:center;">
            <input type="text" name="search" class="form-control"
                   value="{{ $search }}"
                   placeholder="Search DR, date (2025-01), notes…"
                   style="width:280px;">
            <button type="submit" class="btn btn-primary">Search</button>
            @if($search)
                <a href="{{ route('supplier-ledger.show', $supplier) }}" class="btn btn-secondary">Clear</a>
            @endif
        </form>
    </div>

    <div style="overflow-x:auto;">
        <table class="sl-table">
            <thead>
                <tr>
                    <th style="width:105px;">Date</th>
                    <th>DR</th>
                    <th>Type</th>
                    <th>Due / Terms</th>
                    <th class="r">Amount</th>
                    <th class="r">Running Balance</th>
                    <th>Notes</th>
                    <th style="width:50px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                <tr style="{{ $entry['type'] === 'payment' ? 'background:#f7fef9;' : '' }}">

                    <td style="font-weight:600; font-size:12px; white-space:nowrap; color:#374151;">
                        {{ \Carbon\Carbon::parse($entry['date'])->format('m/d/Y') }}
                    </td>

                    <td>
                        <span class="dr-badge">{{ $entry['dr'] ?: '—' }}</span>
                    </td>

                    <td>
                        @if($entry['type'] === 'delivery')
                            @if(!empty($entry['from_po']))
                                <span class="badge badge-info">PO Delivery</span>
                            @else
                                <span class="badge badge-info">Delivery</span>
                            @endif
                        @else
                            @if(!empty($entry['from_po']))
                                <span class="badge badge-success">PO Payment</span>
                            @else
                                <span class="badge badge-success">Payment</span>
                            @endif
                        @endif
                    </td>

                    <td style="font-size:11px; color:#64748b; white-space:nowrap;">
                        @if(!empty($entry['from_po']) && !empty($entry['due_date']))
                            <div><strong style="color:#92400e;">Due:</strong> {{ \Carbon\Carbon::parse($entry['due_date'])->format('M d, Y') }}</div>
                            @if(!empty($entry['terms_count']))
                                <div>
                                    {{ (int) ($entry['remaining_terms'] ?? 0) }}/{{ (int) $entry['terms_count'] }} term(s)
                                    @if(!empty($entry['terms_days'])) · {{ (int) $entry['terms_days'] }} days @endif
                                </div>
                            @endif
                            @if(($entry['type'] === 'payment') && !empty($entry['suggested_term_amount']))
                                <div>Suggested: ₱{{ number_format((float) $entry['suggested_term_amount'], 2) }}</div>
                            @endif
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>

                    <td style="text-align:right;">
                        @if($entry['type'] === 'delivery')
                            <span class="debit">₱{{ number_format($entry['debit'], 2) }}</span>
                        @else
                            <span class="credit">-₱{{ number_format($entry['credit'], 2) }}</span>
                        @endif
                    </td>

                    <td style="text-align:right;">
                        <span class="{{ $entry['running_balance'] >= 0 ? 'bal-owe' : 'bal-ok' }}">
                            {{ $entry['running_balance'] < 0 ? '-₱' : '₱' }}{{ number_format(abs($entry['running_balance']), 2) }}
                        </span>
                    </td>

                    <td style="font-size:11px; color:#64748b; max-width:180px; word-break:break-word;">
                        {{ $entry['notes'] ?: '—' }}
                    </td>

                    <td style="text-align:center;">
                        @if($entry['type'] === 'delivery')
                            @if(empty($entry['from_po']))
                                <form action="{{ route('supplier-ledger.destroy-delivery', [$supplier, $entry['model']]) }}"
                                      method="POST" class="offline-ledger-delivery-delete-form"
                                      data-supplier-id="{{ $supplier->id }}" data-delivery-id="{{ $entry['model']->id }}"
                                      onsubmit="return confirm('Delete this delivery?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm">✕</button>
                                </form>
                            @else
                                <span class="via-po-tag">via PO</span>
                            @endif
                        @else
                            @if(empty($entry['from_po']))
                                <form action="{{ route('supplier-ledger.destroy-payment', [$supplier, $entry['model']]) }}"
                                      method="POST" class="offline-ledger-payment-delete-form"
                                      data-supplier-id="{{ $supplier->id }}" data-payment-id="{{ $entry['model']->id }}"
                                      onsubmit="return confirm('Delete this payment?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm">✕</button>
                                </form>
                            @else
                                <span class="via-po-tag">via PO</span>
                            @endif
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center; padding:50px 20px; color:#94a3b8;">
                        <div style="font-size:32px; margin-bottom:8px;">📋</div>
                        No transactions yet. Add a delivery or record a payment above.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($entries->count() > 0)
            <tfoot>
                <tr>
                    <td colspan="3" style="font-size:11px; letter-spacing:.5px; color:#94a3b8;">TOTAL</td>
                    <td class="r" style="color:#fca5a5;">₱{{ number_format($totalDelivered - $totalPaid, 2) }}</td>
                    <td class="r" style="font-size:15px; color:{{ $balance > 0 ? '#fca5a5' : '#86efac' }};">
                        ₱{{ number_format($balance, 2) }}
                    </td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- ── Add Delivery Modal ── --}}
<div id="delivery-modal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.5);
     z-index:1000; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#fff; border-radius:14px; width:100%; max-width:460px;
                box-shadow:0 20px 60px rgba(15,23,42,.22); overflow:hidden;">
        <div style="display:flex; justify-content:space-between; align-items:center;
                    padding:18px 22px 14px; border-bottom:1px solid #f1f5f9;">
            <div>
                <div style="font-size:15px; font-weight:700; color:#0f172a;">Add Delivery (DR)</div>
                <div style="font-size:12px; color:#64748b; margin-top:2px;">{{ $supplier->name }}</div>
            </div>
            <button onclick="document.getElementById('delivery-modal').style.display='none'"
                    style="background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;">✕</button>
        </div>
        <form action="{{ route('supplier-ledger.store-delivery', $supplier) }}" method="POST"
              id="ledger-delivery-form" style="padding:18px 22px;">
            @csrf
            <div class="form-group">
                <label>DR Number *</label>
                <input type="text" name="dr_number" class="form-control" placeholder="e.g. #97861" required>
            </div>
            <div class="form-group">
                <label>Delivery Date *</label>
                <input type="date" name="delivery_date" class="form-control" value="{{ now()->toDateString() }}" required>
            </div>
            <div class="form-group">
                <label>Amount (₱) *</label>
                <input type="number" name="amount" class="form-control" placeholder="0.00" step="0.01" min="0.01" required>
            </div>
            <div class="form-group">
                <label>Notes <span style="color:#94a3b8; font-weight:400;">(Optional)</span></label>
                <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Delivered to main branch"></textarea>
            </div>
            <div style="display:flex; gap:8px; margin-top:6px;">
                <button type="submit" class="btn btn-primary" style="flex:1;">Save Delivery</button>
                <button type="button" onclick="document.getElementById('delivery-modal').style.display='none'"
                        class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Record Payment Modal ── --}}
<div id="payment-modal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.5);
     z-index:1000; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#fff; border-radius:14px; width:100%; max-width:460px;
                box-shadow:0 20px 60px rgba(15,23,42,.22); overflow:hidden;">
        <div style="display:flex; justify-content:space-between; align-items:center;
                    padding:18px 22px 14px; border-bottom:1px solid #f1f5f9;">
            <div>
                <div style="font-size:15px; font-weight:700; color:#0f172a;">Record Payment</div>
                <div style="font-size:12px; color:#64748b; margin-top:2px;">
                    {{ $supplier->name }}
                    @if($balance > 0)
                        &nbsp;·&nbsp;
                        <span style="color:#dc2626; font-weight:600;">Outstanding: ₱{{ number_format($balance, 2) }}</span>
                    @endif
                </div>
            </div>
            <button onclick="document.getElementById('payment-modal').style.display='none'"
                    style="background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;">✕</button>
        </div>
        <form action="{{ route('supplier-ledger.store-payment', $supplier) }}" method="POST"
              id="ledger-payment-form" style="padding:18px 22px;">
            @csrf
            <div class="form-group">
                <label>Payment Date *</label>
                <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
            </div>
            <div class="form-group">
                <label>Amount (₱) *</label>
                <input type="number" name="amount" class="form-control" placeholder="0.00" step="0.01" min="0.01"
                       value="{{ $balance > 0 ? number_format($balance, 2, '.', '') : '' }}" required>
            </div>
            <div class="form-group">
                <label>Payment Method *</label>
                <select name="payment_method" class="form-control">
                    <option value="cash">Cash</option>
                    <option value="check">Check</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="e_wallet">E-Wallet</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Reference No <span style="color:#94a3b8; font-weight:400;">(Optional)</span></label>
                <input type="text" name="reference_no" class="form-control" placeholder="Check/transfer reference">
            </div>
            <div class="form-group">
                <label>Notes <span style="color:#94a3b8; font-weight:400;">(Optional)</span></label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>
            <div style="display:flex; gap:8px; margin-top:6px;">
                <button type="submit" class="btn btn-primary" style="flex:1;">Save Payment</button>
                <button type="button" onclick="document.getElementById('payment-modal').style.display='none'"
                        class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
['delivery-modal','payment-modal'].forEach(function(id) {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});
(function () {
    const df = document.getElementById('ledger-delivery-form');
    if (df && window.CitiOffline?.queueLedgerDeliveryCreate) {
        df.addEventListener('submit', async function (e) {
            if (navigator.onLine) return;
            e.preventDefault();
            try {
                const ref = await window.CitiOffline.queueLedgerDeliveryCreate({
                    supplier_id: {{ (int) $supplier->id }},
                    dr_number: df.querySelector('[name="dr_number"]')?.value || '',
                    delivery_date: df.querySelector('[name="delivery_date"]')?.value,
                    amount: parseFloat(df.querySelector('[name="amount"]')?.value || '0'),
                    notes: df.querySelector('[name="notes"]')?.value || '',
                });
                alert('Offline: Delivery queued. Ref: ' + ref.slice(0, 8));
                window.location.reload();
            } catch (err) { alert((err && err.message) || 'Queue failed.'); }
        });
    }
    const pf = document.getElementById('ledger-payment-form');
    if (pf && window.CitiOffline?.queueLedgerPaymentCreate) {
        pf.addEventListener('submit', async function (e) {
            if (navigator.onLine) return;
            e.preventDefault();
            try {
                const ref = await window.CitiOffline.queueLedgerPaymentCreate({
                    supplier_id: {{ (int) $supplier->id }},
                    payment_date: pf.querySelector('[name="payment_date"]')?.value,
                    amount: parseFloat(pf.querySelector('[name="amount"]')?.value || '0'),
                    payment_method: pf.querySelector('[name="payment_method"]')?.value,
                    reference_no: pf.querySelector('[name="reference_no"]')?.value || '',
                    notes: pf.querySelector('[name="notes"]')?.value || '',
                });
                alert('Offline: Payment queued. Ref: ' + ref.slice(0, 8));
                window.location.reload();
            } catch (err) { alert((err && err.message) || 'Queue failed.'); }
        });
    }
    document.querySelectorAll('.offline-ledger-delivery-delete-form').forEach(function (form) {
        form.addEventListener('submit', async function (e) {
            if (navigator.onLine || !window.CitiOffline?.queueLedgerDeliveryDelete) return;
            e.preventDefault();
            if (!confirm('Delete this delivery?')) return;
            try {
                const ref = await window.CitiOffline.queueLedgerDeliveryDelete({
                    supplier_id: parseInt(form.dataset.supplierId || '0', 10),
                    delivery_id: parseInt(form.dataset.deliveryId || '0', 10),
                });
                alert('Offline: Delete queued. Ref: ' + ref.slice(0, 8));
                form.closest('tr')?.remove();
            } catch (err) { alert((err && err.message) || 'Queue failed.'); }
        });
    });
    document.querySelectorAll('.offline-ledger-payment-delete-form').forEach(function (form) {
        form.addEventListener('submit', async function (e) {
            if (navigator.onLine || !window.CitiOffline?.queueLedgerPaymentDelete) return;
            e.preventDefault();
            if (!confirm('Delete this payment?')) return;
            try {
                const ref = await window.CitiOffline.queueLedgerPaymentDelete({
                    supplier_id: parseInt(form.dataset.supplierId || '0', 10),
                    payment_id: parseInt(form.dataset.paymentId || '0', 10),
                });
                alert('Offline: Delete queued. Ref: ' + ref.slice(0, 8));
                form.closest('tr')?.remove();
            } catch (err) { alert((err && err.message) || 'Queue failed.'); }
        });
    });
})();
</script>

@endsection
