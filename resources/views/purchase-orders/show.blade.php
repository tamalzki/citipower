@extends('layouts.app')

@section('title', 'Purchase Order')

@section('content')

<style>
    .modal-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15,23,42,.45);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .modal-backdrop.open { display: flex; }
    .modal-box {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 20px 60px rgba(15,23,42,.22);
        width: 100%;
        max-width: 680px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: modalIn .18s ease;
    }
    @keyframes modalIn {
        from { opacity:0; transform:translateY(12px) scale(.98); }
        to   { opacity:1; transform:translateY(0) scale(1); }
    }
    .modal-header {
        padding: 20px 24px 16px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-shrink: 0;
    }
    .modal-header h3 { font-size: 17px; font-weight: 700; color: #0f172a; margin: 0 0 3px; }
    .modal-header p  { font-size: 12.5px; color: #64748b; margin: 0; }
    .modal-close {
        background: none; border: none; cursor: pointer;
        color: #94a3b8; font-size: 20px; line-height: 1;
        padding: 2px 6px; border-radius: 6px;
        transition: background .1s, color .1s;
        flex-shrink: 0; margin-left: 12px;
    }
    .modal-close:hover { background: #f1f5f9; color: #0f172a; }
    .modal-body { overflow-y: auto; flex: 1; padding: 20px 24px; }
    .modal-footer {
        padding: 16px 24px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        flex-shrink: 0;
        background: #f8fafc;
    }
    .receive-table { width: 100%; border-collapse: collapse; }
    .receive-table th {
        font-size: 11px; font-weight: 700; color: #64748b;
        text-transform: uppercase; letter-spacing: .5px;
        padding: 0 10px 10px; text-align: left;
        border-bottom: 2px solid #e2e8f0;
    }
    .receive-table td {
        padding: 10px; border-bottom: 1px solid #f1f5f9;
        vertical-align: middle; font-size: 13.5px;
    }
    .receive-table tr:last-child td { border-bottom: none; }
    .receive-table .product-name { font-weight: 600; color: #0f172a; }
    .receive-table .product-sku  { font-size: 11px; color: #94a3b8; margin-top: 2px; }
    .receive-qty-input {
        width: 80px; text-align: center; padding: 5px 8px;
        border: 1.5px solid #d1d5db; border-radius: 7px;
        font-size: 14px; font-weight: 600; color: #0f172a;
        transition: border-color .15s;
    }
    .receive-qty-input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
    .fill-all-btn {
        font-size: 11px; font-weight: 600; color: #2563eb;
        background: none; border: none; cursor: pointer; padding: 0;
        text-decoration: underline; text-underline-offset: 2px;
    }
    .fill-all-btn:hover { color: #1d4ed8; }
</style>

<div class="page-header">
    <div>
        <h2>Purchase Order</h2>
        <p>
            {{ $purchaseOrder->supplier?->name }} · Ordered {{ $purchaseOrder->order_date->format('M d, Y') }}
            @if($purchaseOrder->expected_arrival_date)
                · Expected {{ $purchaseOrder->expected_arrival_date->format('M d, Y') }}
            @endif
        </p>
    </div>
    <div style="display:flex; gap:8px;">
        <a href="{{ route('purchase-orders.index') }}" class="btn btn-secondary">← Back</a>
        @php
            $hasPayments = $purchaseOrder->supplierPayments->isNotEmpty();
        @endphp
        @if($purchaseOrder->status === 'ordered' && !$hasPayments && (auth()->user()->hasRole('owner') || auth()->user()->hasRole('inventory')))
            <a href="{{ route('purchase-orders.edit', $purchaseOrder) }}" class="btn btn-primary">Edit</a>
        @endif
        @if($purchaseOrder->status === 'ordered' && !$hasPayments && (auth()->user()->hasRole('owner') || auth()->user()->hasRole('inventory')))
            <form method="POST" action="{{ route('purchase-orders.destroy', $purchaseOrder) }}"
                  class="offline-po-delete-form"
                  data-po-id="{{ $purchaseOrder->id }}"
                  onsubmit="return confirm('Delete this purchase order? This will remove the order and its line items. Since it has not yet been received or paid, inventory and supplier ledger will not be affected.');"
                  style="margin:0;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        @endif
        @if($purchaseOrder->status !== 'received')
            @php $purchaseOrder->loadMissing('items.product'); @endphp
            <script type="application/json" id="po-receive-embed">{!! json_encode($purchaseOrder->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_name' => $item->product?->name ?? 'Product',
                    'sku' => $item->product?->sku ?? '',
                    'ordered_qty' => (int) $item->quantity,
                    'purchase_price' => (float) $item->purchase_price,
                ];
            })->values()) !!}</script>
            <button type="button" class="btn btn-success receive-btn"
                    data-url="{{ route('purchase-orders.items-json', $purchaseOrder) }}"
                    data-action="{{ route('purchase-orders.receive', $purchaseOrder) }}">
                Receive Items
            </button>
        @endif
        @if(auth()->user()->hasRole('owner') || auth()->user()->hasRole('inventory'))
        <button type="button" class="btn btn-primary"
                onclick="document.getElementById('pay-modal').style.display='flex'">
            💳 Record Payment
        </button>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:16px;">{{ session('error') }}</div>
@endif

@php
    $totalPaid    = $purchaseOrder->total_paid;
    $balance      = $purchaseOrder->payment_balance;
    $payStatus    = $purchaseOrder->payment_status;
@endphp
<div style="display:flex; gap:0; background:#fff; border:1px solid #e2e8f0; border-radius:12px;
            overflow:hidden; box-shadow:0 1px 6px rgba(15,23,42,.06); margin-bottom:16px; flex-wrap:wrap;">
    <div style="flex:1; min-width:120px; padding:14px 18px; border-right:1px solid #f1f5f9;">
        <div style="font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#94a3b8; margin-bottom:4px;">PO Status</div>
        <span class="badge {{ $purchaseOrder->status === 'received' ? 'badge-success' : 'badge-warning' }}" style="font-size:13px; padding:4px 10px;">
            {{ ucfirst($purchaseOrder->status) }}
        </span>
    </div>
    <div style="flex:1; min-width:120px; padding:14px 18px; border-right:1px solid #f1f5f9;">
        <div style="font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#94a3b8; margin-bottom:4px;">Total Amount</div>
        <div style="font-size:20px; font-weight:800; color:#0f172a;">₱{{ number_format($purchaseOrder->total_amount, 2) }}</div>
    </div>
    <div style="flex:1; min-width:120px; padding:14px 18px; border-right:1px solid #f1f5f9;">
        <div style="font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#94a3b8; margin-bottom:4px;">Total Paid</div>
        <div style="font-size:20px; font-weight:800; color:#16a34a;">₱{{ number_format($totalPaid, 2) }}</div>
    </div>
    <div style="flex:1; min-width:120px; padding:14px 18px; border-right:1px solid #f1f5f9; background:{{ $balance > 0 ? '#fff7f7' : '#f0fdf4' }};">
        <div style="font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#94a3b8; margin-bottom:4px;">Balance</div>
        <div style="font-size:20px; font-weight:800; color:{{ $balance > 0 ? '#dc2626' : '#16a34a' }};">₱{{ number_format($balance, 2) }}</div>
    </div>
    <div style="flex:1; min-width:120px; padding:14px 18px;">
        <div style="font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#94a3b8; margin-bottom:6px;">Payment</div>
        @if($payStatus === 'paid')
            <span class="badge badge-success" style="font-size:13px; padding:4px 10px;">✓ Paid</span>
        @elseif($payStatus === 'partial')
            <span class="badge badge-warning" style="font-size:13px; padding:4px 10px;">Partial</span>
        @else
            <span class="badge badge-danger" style="font-size:13px; padding:4px 10px;">Unpaid</span>
        @endif
        @if(($purchaseOrder->payment_terms_count ?? 0) > 0)
            <div style="margin-top:6px; font-size:11px; color:#64748b;">
                {{ $purchaseOrder->remaining_terms }} / {{ $purchaseOrder->payment_terms_count }} term(s) left
            </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-title">Order items</div>
    <div class="table-wrapper">
        <table>
            <thead>
            <tr>
                <th>Product</th>
                <th>SKU</th>
                <th style="text-align:right;">Unit Price</th>
                <th style="text-align:center;">Qty</th>
                <th style="text-align:right;">Subtotal</th>
            </tr>
            </thead>
            <tbody>
            @foreach($purchaseOrder->items as $item)
                <tr>
                    <td style="font-weight:600;">{{ $item->product?->name }}</td>
                    <td style="color:#94a3b8; font-size:12px;">{{ $item->product?->sku ?: '—' }}</td>
                    <td style="text-align:right;">₱{{ number_format($item->purchase_price, 2) }}</td>
                    <td style="text-align:center;">{{ number_format($item->quantity) }}</td>
                    <td style="text-align:right; font-weight:700;">₱{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc; font-weight:700;">
                    <td colspan="4" style="text-align:right; padding-right:14px;">Overall Total</td>
                    <td style="text-align:right;">₱{{ number_format($purchaseOrder->total_amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@if($purchaseOrder->received_at)
<div class="card" style="margin-top:0;">
    <div class="card-title" style="color:#16a34a;">Receiving Details</div>
    <div class="card-body">
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px;">
            <div>
                <div style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase;">Received On</div>
                <div style="font-weight:600; margin-top:3px;">{{ $purchaseOrder->received_at->format('M d, Y h:i A') }}</div>
            </div>
            <div>
                <div style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase;">DR Number</div>
                <div style="font-weight:600; margin-top:3px;">{{ $purchaseOrder->dr_number ?? '—' }}</div>
            </div>
            <div>
                <div style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase;">Arrival Date</div>
                <div style="font-weight:600; margin-top:3px;">{{ $purchaseOrder->arrival_date?->format('M d, Y') ?? '—' }}</div>
            </div>
            @if($purchaseOrder->arrival_notes)
            <div style="grid-column:1/-1;">
                <div style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase;">Arrival Notes</div>
                <div style="margin-top:3px; color:#374151;">{{ $purchaseOrder->arrival_notes }}</div>
            </div>
            @endif
        </div>
    </div>
</div>
@endif


{{-- ══ Receive Items Modal ══ --}}
<div class="modal-backdrop" id="receive-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <h3 id="modal-title">Receive Items</h3>
                <p id="modal-subtitle">Loading…</p>
            </div>
            <button class="modal-close" id="modal-close-btn" title="Close">✕</button>
        </div>

        <form method="POST" id="receive-form" data-purchase-order-id="{{ $purchaseOrder->id }}">
            @csrf
            <div style="padding:16px 20px 0; display:grid; grid-template-columns:1fr 1fr; gap:12px; border-bottom:1px solid #f1f5f9; padding-bottom:16px;">
                <div class="form-group" style="margin:0;">
                    <label style="font-size:12px; font-weight:600; color:#374151;">DR Number *</label>
                    <input type="text" name="dr_number" class="form-control" placeholder="e.g. #97861" required style="margin-top:4px;">
                </div>
                <div class="form-group" style="margin:0;">
                    <label style="font-size:12px; font-weight:600; color:#374151;">Arrival Date</label>
                    <input type="date" name="arrival_date" class="form-control" value="{{ now()->toDateString() }}" style="margin-top:4px;">
                </div>
                <div class="form-group" style="margin:0; grid-column:1/-1;">
                    <label style="font-size:12px; font-weight:600; color:#374151;">Arrival Notes</label>
                    <input type="text" name="arrival_notes" class="form-control" placeholder="e.g. Delivered to main branch warehouse" style="margin-top:4px;">
                </div>
            </div>
            <div class="modal-body" id="modal-body">
                <div class="modal-loading" id="modal-loading">Loading order details…</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="modal-cancel-btn">Cancel</button>
                <button type="submit" class="btn btn-success" id="modal-submit-btn" disabled
                        style="min-width:150px;">
                    ✓ Confirm Receive
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ══ Payment History ══ --}}
<div class="card" style="margin-top:0;">
    <div class="card-title" style="display:flex; justify-content:space-between; align-items:center;">
        <span>💳 Payment History</span>
        @if(auth()->user()->hasRole('owner') || auth()->user()->hasRole('inventory'))
        <button type="button" class="btn btn-primary btn-sm"
                onclick="document.getElementById('pay-modal').style.display='flex'">
            + Record Payment
        </button>
        @endif
    </div>
    @if($purchaseOrder->supplierPayments->count())
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Notes</th>
                    <th style="text-align:right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseOrder->supplierPayments->sortBy('payment_date') as $pmt)
                <tr>
                    <td style="font-weight:600; white-space:nowrap;">{{ $pmt->payment_date->format('M d, Y') }}</td>
                    <td><span class="badge badge-info">{{ ucfirst(str_replace('_',' ',$pmt->payment_method)) }}</span></td>
                    <td style="color:#64748b; font-size:12px;">{{ $pmt->reference_no ?: '—' }}</td>
                    <td style="color:#64748b; font-size:12px;">{{ $pmt->notes ?: '—' }}</td>
                    <td style="text-align:right; font-weight:700; color:#16a34a;">₱{{ number_format($pmt->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc; font-weight:700;">
                    <td colspan="4" style="text-align:right; padding:10px 14px; color:#64748b;">Total Paid</td>
                    <td style="text-align:right; padding:10px 14px; color:#16a34a;">₱{{ number_format($totalPaid, 2) }}</td>
                </tr>
                @if($balance > 0)
                <tr style="background:#fff7f7; font-weight:700;">
                    <td colspan="4" style="text-align:right; padding:10px 14px; color:#dc2626;">Remaining Balance</td>
                    <td style="text-align:right; padding:10px 14px; color:#dc2626; font-size:15px;">₱{{ number_format($balance, 2) }}</td>
                </tr>
                @endif
            </tfoot>
        </table>
    </div>
    @else
    <div class="card-body" style="text-align:center; padding:30px; color:#94a3b8;">
        No payments recorded yet.
        @if(auth()->user()->hasRole('owner') || auth()->user()->hasRole('inventory'))
            <a href="#" onclick="document.getElementById('pay-modal').style.display='flex'; return false;"
               style="color:#2563eb; font-weight:600; text-decoration:none; margin-left:6px;">Record first payment →</a>
        @endif
    </div>
    @endif
</div>

{{-- ══ Record Payment Modal ══ --}}
<div id="pay-modal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.5);
     z-index:1100; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#fff; border-radius:14px; padding:0; width:100%; max-width:460px;
                box-shadow:0 20px 60px rgba(15,23,42,.22); overflow:hidden;">
        <div style="padding:20px 24px 16px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 style="margin:0; font-size:16px; font-weight:700;">💳 Record Payment</h3>
                <p style="margin:3px 0 0; font-size:12px; color:#64748b;">
                    {{ $purchaseOrder->supplier?->name }} · {{ $purchaseOrder->order_date->format('M d, Y') }}
                    · Total: ₱{{ number_format($purchaseOrder->total_amount, 2) }}
                </p>
            </div>
            <button onclick="document.getElementById('pay-modal').style.display='none'"
                    style="background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;padding:4px;">✕</button>
        </div>
        <form action="{{ route('purchase-orders.record-payment', $purchaseOrder) }}" method="POST"
              id="po-record-payment-form" style="padding:20px 24px;">
            @csrf
            <div style="margin-bottom:12px; border-radius:10px; padding:10px 12px; background:{{ $balance > 0 ? '#fff7ed' : '#f0fdf4' }}; border:1px solid {{ $balance > 0 ? '#fed7aa' : '#bbf7d0' }};">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
                    <div style="font-size:11px; color:{{ $balance > 0 ? '#9a3412' : '#166534' }}; font-weight:700; text-transform:uppercase;">Balance Due</div>
                    <div style="font-size:22px; line-height:1; font-weight:900; color:{{ $balance > 0 ? '#dc2626' : '#16a34a' }};">
                        ₱{{ number_format($balance, 2) }}
                    </div>
                </div>
                <div style="margin-top:4px; font-size:12px; color:#64748b;">
                    Paid: ₱{{ number_format($totalPaid, 2) }} · Total: ₱{{ number_format($purchaseOrder->total_amount, 2) }}
                    @if($purchaseOrder->expected_arrival_date)
                        · Due: {{ $purchaseOrder->expected_arrival_date->format('M d, Y') }}
                    @endif
                </div>
            </div>
            <div class="form-group">
                <label>Payment Date *</label>
                <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
            </div>
            <div class="form-group">
                <label>Amount (₱) *
                    @if($balance > 0)
                        <span style="color:#dc2626; font-weight:600; font-size:11px; margin-left:6px;">
                            Balance: ₱{{ number_format($balance, 2) }}
                        </span>
                    @elseif($payStatus === 'paid')
                        <span style="color:#16a34a; font-weight:600; font-size:11px; margin-left:6px;">Fully Paid</span>
                    @endif
                </label>
                <input type="number" id="po-pay-amount" name="amount" class="form-control" step="0.01" min="0.01"
                       value="{{ $balance > 0 ? number_format($balance, 2, '.', '') : '' }}" required>
                <small id="po-pay-helper" style="display:block; margin-top:6px; color:#64748b; font-size:12px;">
                    @if($balance > 0 && ($purchaseOrder->remaining_terms > 0))
                        Suggested per term: ₱{{ number_format($purchaseOrder->suggested_term_amount, 2) }}
                        ({{ $purchaseOrder->remaining_terms }} of {{ $purchaseOrder->payment_terms_count }} term(s) remaining)
                    @elseif($balance > 0)
                        Suggested payment: ₱{{ number_format($balance, 2) }}
                    @else
                        This order is fully paid.
                    @endif
                </small>
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
                <textarea name="notes" class="form-control" rows="2"
                          placeholder="e.g. Partial payment for PO"></textarea>
            </div>
            <div style="display:flex; gap:8px; margin-top:4px;">
                <button type="submit" class="btn btn-primary" style="flex:1;">Save Payment</button>
                <button type="button" onclick="document.getElementById('pay-modal').style.display='none'"
                        class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('pay-modal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
document.getElementById('po-pay-amount').addEventListener('input', function () {
    const balance = {{ (float) $balance }};
    const current = parseFloat(this.value || '0');
    const helper = document.getElementById('po-pay-helper');
    if (current > balance && balance > 0) {
        helper.textContent = 'Payment exceeds balance. This will create overpayment.';
        helper.style.color = '#dc2626';
    } else {
        helper.style.color = '#64748b';
        helper.textContent = balance > 0
            ? ('Remaining after this payment: ₱' + (balance - current).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}))
            : 'This order is fully paid.';
    }
});
(function () {
    const f = document.getElementById('po-record-payment-form');
    if (!f || !window.CitiOffline?.queuePoRecordPayment) return;
    f.addEventListener('submit', async function (e) {
        if (navigator.onLine) return;
        e.preventDefault();
        try {
            const ref = await window.CitiOffline.queuePoRecordPayment({
                purchase_order_id: {{ (int) $purchaseOrder->id }},
                payment_date: f.querySelector('[name="payment_date"]')?.value,
                amount: parseFloat(f.querySelector('[name="amount"]')?.value || '0'),
                payment_method: f.querySelector('[name="payment_method"]')?.value,
                reference_no: f.querySelector('[name="reference_no"]')?.value || '',
                notes: f.querySelector('[name="notes"]')?.value || '',
            });
            alert('Offline: PO payment queued. Ref: ' + ref.slice(0, 8));
            window.location.reload();
        } catch (err) { alert((err && err.message) || 'Queue failed.'); }
    });
})();
</script>

<script>
(function () {
    const modal       = document.getElementById('receive-modal');
    const modalTitle  = document.getElementById('modal-title');
    const modalSub    = document.getElementById('modal-subtitle');
    const modalBody   = document.getElementById('modal-body');
    const receiveForm = document.getElementById('receive-form');
    const submitBtn   = document.getElementById('modal-submit-btn');

    function openModal()  { modal.classList.add('open');    document.body.style.overflow = 'hidden'; }
    function closeModal() { modal.classList.remove('open'); document.body.style.overflow = ''; }

    document.getElementById('modal-close-btn').addEventListener('click', closeModal);
    document.getElementById('modal-cancel-btn').addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

    document.querySelectorAll('.receive-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            modalTitle.textContent = 'Receive Items';
            modalSub.textContent   = 'Loading order details…';
            modalBody.innerHTML    = '<div style="text-align:center;padding:40px 20px;color:#94a3b8;font-size:13px;">Loading order details…</div>';
            submitBtn.disabled     = true;
            receiveForm.action     = this.dataset.action;
            openModal();

            if (!navigator.onLine) {
                const embed = document.getElementById('po-receive-embed');
                if (!embed) {
                    modalBody.innerHTML = '<div style="text-align:center;padding:30px 20px;color:#dc2626;font-size:13px;">Cannot receive offline: reload this page while online once to cache line items.</div>';
                    return;
                }
                try {
                    const items = JSON.parse(embed.textContent || '[]');
                    modalTitle.textContent = 'Receive Items';
                    modalSub.textContent   = items.length + ' product(s) (offline)';
                    renderItems(items);
                    submitBtn.disabled = false;
                } catch (err) {
                    modalBody.innerHTML = `<div style="text-align:center;padding:30px 20px;color:#dc2626;font-size:13px;">Invalid offline data.<br><small>${err.message}</small></div>`;
                }
                return;
            }

            try {
                const res  = await fetch(this.dataset.url, {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                if (!res.ok) throw new Error('Server error ' + res.status);
                const data = await res.json();

                modalTitle.textContent = 'Receive Items';
                modalSub.textContent   = (data.supplier || '—') + ' · ordered ' + (data.order_date || '')
                                       + ' · ' + data.items.length + ' product(s)';
                renderItems(data.items);
                submitBtn.disabled = false;

            } catch (err) {
                modalBody.innerHTML = `<div style="text-align:center;padding:30px 20px;color:#dc2626;font-size:13px;">
                    Failed to load order details.<br><small>${err.message}</small></div>`;
            }
        });
    });

    function renderItems(items) {
        let html = `
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                <p style="font-size:12.5px; color:#64748b; margin:0;">
                    Adjust quantities if you're receiving partial items. Set to <strong>0</strong> to skip.
                </p>
                <button type="button" class="fill-all-btn" id="fill-all-btn">Fill all ordered qty</button>
            </div>
            <table class="receive-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th style="text-align:right;">Unit Price</th>
                        <th style="text-align:center;">Ordered</th>
                        <th style="text-align:center;">Receive Qty</th>
                    </tr>
                </thead>
                <tbody>`;

        items.forEach(item => {
            const price = parseFloat(item.purchase_price).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            html += `
                <tr>
                    <td>
                        <div class="product-name">${esc(item.product_name)}</div>
                        ${item.sku ? `<div class="product-sku">SKU: ${esc(item.sku)}</div>` : ''}
                    </td>
                    <td style="text-align:right; color:#475569;">₱${price}</td>
                    <td style="text-align:center; color:#64748b; font-weight:600;">${item.ordered_qty}</td>
                    <td style="text-align:center;">
                        <input type="number" class="receive-qty-input"
                               name="quantities[${item.id}]"
                               value="${item.ordered_qty}"
                               min="0" max="${item.ordered_qty}">
                    </td>
                </tr>`;
        });

        html += `</tbody></table>`;
        modalBody.innerHTML = html;

        document.getElementById('fill-all-btn').addEventListener('click', () => {
            modalBody.querySelectorAll('.receive-qty-input').forEach(inp => { inp.value = inp.max; });
        });
    }

    receiveForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        submitBtn.disabled    = true;
        submitBtn.textContent = 'Processing…';

        if (!navigator.onLine && window.CitiOffline?.queuePoReceive) {
            const poId = parseInt(receiveForm.dataset.purchaseOrderId || '0', 10);
            const quantities = {};
            receiveForm.querySelectorAll('.receive-qty-input').forEach(function (inp) {
                const m = inp.name.match(/quantities\[(\d+)\]/);
                if (m) quantities[m[1]] = parseInt(inp.value || '0', 10);
            });
            try {
                const ref = await window.CitiOffline.queuePoReceive({
                    purchase_order_id: poId,
                    dr_number: receiveForm.querySelector('[name="dr_number"]')?.value || '',
                    arrival_date: receiveForm.querySelector('[name="arrival_date"]')?.value || null,
                    arrival_notes: receiveForm.querySelector('[name="arrival_notes"]')?.value || '',
                    quantities: quantities,
                });
                alert('Offline: Receive queued. Ref: ' + ref.slice(0, 8));
                window.location.href = '{{ route('purchase-orders.index') }}';
            } catch (err) {
                submitBtn.disabled    = false;
                submitBtn.textContent = '✓ Confirm Receive';
                alert((err && err.message) || 'Queue failed.');
            }
            return;
        }

        try {
            const res = await fetch(this.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(this),
            });
            if (res.redirected) { window.location.href = res.url; return; }
            window.location.reload();
        } catch (err) {
            submitBtn.disabled    = false;
            submitBtn.textContent = '✓ Confirm Receive';
            alert('Something went wrong: ' + err.message);
        }
    });

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = String(s ?? '');
        return d.innerHTML;
    }
})();

document.querySelectorAll('.offline-po-delete-form').forEach(form => {
    form.addEventListener('submit', async function (e) {
        if (navigator.onLine || !window.CitiOffline || typeof window.CitiOffline.queuePurchaseOrderDelete !== 'function') {
            return;
        }
        e.preventDefault();
        const confirmed = window.confirm('Delete this purchase order? This will remove the order and its line items. Since it has not yet been received or paid, inventory and supplier ledger will not be affected.');
        if (!confirmed) return;
        const poId = parseInt(form.dataset.poId || '0', 10);
        if (!poId) {
            alert('Missing purchase order id.');
            return;
        }
        try {
            const localId = await window.CitiOffline.queuePurchaseOrderDelete({ purchase_order_id: poId });
            alert('Offline: Purchase order delete queued and will auto-sync when online. Ref: ' + localId.slice(0, 8));
            window.location.href = '{{ route('purchase-orders.index') }}';
        } catch (err) {
            alert((err && err.message) || 'Failed to queue purchase order delete offline.');
        }
    });
});
</script>

@endsection
