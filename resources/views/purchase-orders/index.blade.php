@extends('layouts.app')

@section('title', 'Purchase Orders')

@section('content')

<style>
    /* ── Receive Modal ──────────────────────────────── */
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
    .modal-body {
        overflow-y: auto;
        flex: 1;
        padding: 20px 24px;
    }
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
    .receive-table th:last-child { text-align: center; }
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
    .modal-loading {
        text-align: center; padding: 40px 20px; color: #94a3b8; font-size: 13px;
    }
    .modal-error {
        text-align: center; padding: 30px 20px; color: #dc2626; font-size: 13px;
    }
    .fill-all-btn {
        font-size: 11px; font-weight: 600; color: #2563eb;
        background: none; border: none; cursor: pointer; padding: 0;
        text-decoration: underline; text-underline-offset: 2px;
    }
    .fill-all-btn:hover { color: #1d4ed8; }
</style>

<div class="page-header">
    <div>
        <h2>Purchase Orders</h2>
        <p>Create and receive supplier purchase orders to replenish stock</p>
    </div>
    <div style="display:flex; gap:8px;">
        <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">Suppliers</a>
        <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary">+ New PO</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:16px;">{{ session('error') }}</div>
@endif

<div class="card" style="margin-bottom:16px;">
    <div class="card-body">
        <form method="GET" action="{{ route('purchase-orders.index') }}"
              style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <input type="text" name="search" class="form-control"
                   value="{{ $search }}"
                   placeholder="Search supplier, DR #, notes, status (ordered/received), or ID…"
                   style="flex:1; min-width:220px; max-width:480px;">
            <button type="submit" class="btn btn-primary">Search</button>
            @if($search)
                <a href="{{ route('purchase-orders.index') }}" class="btn btn-secondary">Clear</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
            <tr>
                <th>Order Date</th>
                <th>Supplier</th>
                <th>DR Number</th>
                <th>Arrival Date</th>
                <th>Due</th>
                <th>Status</th>
                <th style="text-align:right;">Total</th>
                <th style="text-align:right;">Paid</th>
                <th style="text-align:right;">Balance</th>
                <th>Payment</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($purchaseOrders as $po)
                @php
                    $paidAmt    = $po->total_paid;
                    $balanceAmt = $po->payment_balance;
                    $payStatus  = $po->payment_status;
                @endphp
                <tr>
                    <td style="font-size:12px; white-space:nowrap;">{{ $po->order_date->format('M d, Y') }}</td>
                    <td>{{ $po->supplier?->name }}</td>
                    <td style="font-weight:600;">{{ $po->dr_number ?? '—' }}</td>
                    <td style="font-size:12px;">{{ $po->arrival_date?->format('M d, Y') ?? '—' }}</td>
                    <td style="font-size:12px; white-space:nowrap;">
                        {{ $po->expected_arrival_date?->format('M d, Y') ?? '—' }}
                        @if(($po->payment_terms_count ?? 0) > 0)
                            <div style="font-size:11px; color:#64748b;">{{ $po->payment_terms_count }} term(s)</div>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $po->status === 'received' ? 'badge-success' : 'badge-warning' }}">
                            {{ ucfirst($po->status) }}
                        </span>
                    </td>
                    <td style="font-weight:600; text-align:right;">₱{{ number_format($po->total_amount, 2) }}</td>
                    <td style="text-align:right; color:#16a34a; font-weight:600;">
                        {{ $paidAmt > 0 ? '₱'.number_format($paidAmt, 2) : '—' }}
                    </td>
                    <td style="text-align:right; font-weight:700; color:{{ $balanceAmt > 0 ? '#dc2626' : '#16a34a' }};">
                        ₱{{ number_format($balanceAmt, 2) }}
                    </td>
                    <td>
                        @if($payStatus === 'paid')
                            <span class="badge badge-success">✓ Paid</span>
                        @elseif($payStatus === 'partial')
                            <span class="badge badge-warning">Partial</span>
                        @else
                            <span class="badge badge-danger">Unpaid</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                            <a href="{{ route('purchase-orders.show', $po) }}" class="btn btn-secondary btn-sm">View</a>
                            @if($po->status !== 'received')
                                <button type="button" class="btn btn-success btn-sm receive-btn"
                                        data-url="{{ route('purchase-orders.items-json', $po) }}"
                                        data-action="{{ route('purchase-orders.receive', $po) }}">
                                    Receive
                                </button>
                            @endif
                            @if(auth()->user()->hasRole('owner') || auth()->user()->hasRole('inventory'))
                            <button type="button" class="btn btn-primary btn-sm pay-btn"
                                    data-supplier="{{ $po->supplier?->name }}"
                                    data-order-date="{{ $po->order_date->format('M d, Y') }}"
                                    data-total="{{ number_format($po->total_amount, 2, '.', '') }}"
                                    data-balance="{{ number_format($balanceAmt, 2, '.', '') }}"
                                    data-terms="{{ (int) ($po->payment_terms_count ?? 0) }}"
                                    data-remaining-terms="{{ (int) $po->remaining_terms }}"
                                    data-suggested-term="{{ number_format($po->suggested_term_amount, 2, '.', '') }}"
                                    data-due-date="{{ $po->expected_arrival_date?->format('M d, Y') ?? '' }}"
                                    data-term-days="{{ (int) ($po->payment_terms_days ?? 0) }}"
                                    data-action="{{ route('purchase-orders.record-payment', $po) }}">
                                💳 Pay
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="11" style="color:#94a3b8; text-align:center; padding:30px;">
                    {{ $search ? 'No purchase orders match your search.' : 'No purchase orders yet.' }}
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div>{{ $purchaseOrders->links() }}</div>


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

        <form method="POST" id="receive-form">
            @csrf

            {{-- DR / Arrival fields --}}
            <div style="padding:16px 20px 0; display:grid; grid-template-columns:1fr 1fr; gap:12px; border-bottom:1px solid #f1f5f9; padding-bottom:16px;">
                <div class="form-group" style="margin:0;">
                    <label style="font-size:12px; font-weight:600; color:#374151;">DR Number *</label>
                    <input type="text" name="dr_number" id="receive-dr-number" class="form-control"
                           placeholder="e.g. #97861" required style="margin-top:4px;">
                </div>
                <div class="form-group" style="margin:0;">
                    <label style="font-size:12px; font-weight:600; color:#374151;">Arrival Date</label>
                    <input type="date" name="arrival_date" id="receive-arrival-date" class="form-control"
                           value="{{ now()->toDateString() }}" style="margin-top:4px;">
                </div>
                <div class="form-group" style="margin:0; grid-column:1/-1;">
                    <label style="font-size:12px; font-weight:600; color:#374151;">Arrival Notes</label>
                    <input type="text" name="arrival_notes" class="form-control"
                           placeholder="e.g. Delivered to main branch warehouse" style="margin-top:4px;">
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

<script>
(function () {
    const modal       = document.getElementById('receive-modal');
    const modalTitle  = document.getElementById('modal-title');
    const modalSub    = document.getElementById('modal-subtitle');
    const modalBody   = document.getElementById('modal-body');
    const modalLoading= document.getElementById('modal-loading');
    const receiveForm = document.getElementById('receive-form');
    const submitBtn   = document.getElementById('modal-submit-btn');
    const csrfToken   = document.querySelector('meta[name="csrf-token"]')?.content
                        || '{{ csrf_token() }}';

    function openModal() { modal.classList.add('open'); document.body.style.overflow = 'hidden'; }
    function closeModal() { modal.classList.remove('open'); document.body.style.overflow = ''; }

    document.getElementById('modal-close-btn').addEventListener('click', closeModal);
    document.getElementById('modal-cancel-btn').addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });

    /* ── Open + load ──────────────────────────────── */
    document.querySelectorAll('.receive-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const itemsUrl = this.dataset.url;
            const action   = this.dataset.action;

            // Reset modal state
            modalTitle.textContent   = 'Receive Items';
            modalSub.textContent     = 'Loading order details…';
            modalBody.innerHTML      = '<div class="modal-loading">Loading order details…</div>';
            submitBtn.disabled       = true;
            receiveForm.action       = action;
            openModal();

            try {
                const res  = await fetch(itemsUrl, {
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
                modalBody.innerHTML = `<div class="modal-error">Failed to load order details.<br><small>${err.message}</small></div>`;
            }
        });
    });

    /* ── Render items table ───────────────────────── */
    function renderItems(items) {
        let html = `
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <p style="font-size:12.5px; color:#64748b; margin:0;">
                    Set the quantity received for each product. Set to <strong>0</strong> to skip an item.
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
            html += `
                <tr>
                    <td>
                        <div class="product-name">${esc(item.product_name)}</div>
                        ${item.sku ? `<div class="product-sku">SKU: ${esc(item.sku)}</div>` : ''}
                    </td>
                    <td style="text-align:right; font-size:13px; color:#475569;">
                        ₱${parseFloat(item.purchase_price).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                    </td>
                    <td style="text-align:center; color:#64748b; font-weight:600;">${item.ordered_qty}</td>
                    <td style="text-align:center;">
                        <input type="number" class="receive-qty-input"
                               name="quantities[${item.id}]"
                               value="${item.ordered_qty}"
                               min="0" max="${item.ordered_qty}"
                               required>
                    </td>
                </tr>`;
        });

        html += `</tbody></table>`;
        modalBody.innerHTML = html;

        document.getElementById('fill-all-btn').addEventListener('click', function () {
            modalBody.querySelectorAll('.receive-qty-input').forEach(inp => {
                inp.value = inp.max;
            });
        });
    }

    /* ── Submit via fetch (keeps page, shows flash) ─ */
    receiveForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        submitBtn.disabled   = true;
        submitBtn.textContent = 'Processing…';

        const formData = new FormData(this);

        try {
            const res = await fetch(this.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            });

            // Laravel returns redirect; follow it
            if (res.redirected) {
                window.location.href = res.url;
                return;
            }
            // Fallback: reload
            window.location.reload();

        } catch (err) {
            submitBtn.disabled   = false;
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
</script>

{{-- ══ Record Payment Modal ══ --}}
<div id="pay-modal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.5);
     z-index:1100; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#fff; border-radius:14px; width:100%; max-width:460px;
                box-shadow:0 20px 60px rgba(15,23,42,.22); overflow:hidden;">
        <div style="padding:20px 24px 16px; border-bottom:1px solid #e2e8f0;
                    display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 style="margin:0; font-size:16px; font-weight:700;">💳 Record Payment</h3>
                <p id="pay-modal-sub" style="margin:3px 0 0; font-size:12px; color:#64748b;"></p>
            </div>
            <button onclick="document.getElementById('pay-modal').style.display='none'"
                    style="background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;">✕</button>
        </div>
        <form id="pay-form" method="POST" style="padding:20px 24px;">
            @csrf
            <div id="pay-highlight" style="margin-bottom:12px; border-radius:10px; padding:10px 12px; background:#fff7ed; border:1px solid #fed7aa;">
                <div style="display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap;">
                    <div style="font-size:11px; color:#9a3412; font-weight:700; text-transform:uppercase;">Balance Due</div>
                    <div id="pay-highlight-balance" style="font-size:22px; line-height:1; font-weight:900; color:#dc2626;">₱0.00</div>
                </div>
                <div id="pay-highlight-meta" style="margin-top:4px; font-size:12px; color:#7c2d12;"></div>
            </div>
            <div class="form-group">
                <label>Payment Date *</label>
                <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
            </div>
            <div class="form-group">
                <label>Amount (₱) *</label>
                <input type="number" id="pay-amount" name="amount" class="form-control"
                       step="0.01" min="0.01" required>
                <small id="pay-amount-helper" style="display:block; margin-top:6px; color:#64748b; font-size:12px;"></small>
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
document.querySelectorAll('.pay-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const total = parseFloat(this.dataset.total || '0');
        const balance = parseFloat(this.dataset.balance || '0');
        const paid = Math.max(0, total - balance);
        document.getElementById('pay-modal-sub').textContent =
            (this.dataset.supplier || '—') + ' · ' + (this.dataset.orderDate || '') +
            ' · Total ₱' + this.dataset.total +
            ' · Balance ₱' + this.dataset.balance;
        const terms = parseInt(this.dataset.terms || '0', 10);
        const remainingTerms = parseInt(this.dataset.remainingTerms || '0', 10);
        const suggestedTerm = parseFloat(this.dataset.suggestedTerm || '0');
        const due = this.dataset.dueDate || '';
        const termDays = parseInt(this.dataset.termDays || '0', 10);
        document.getElementById('pay-highlight-balance').textContent = '₱' + balance.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
        document.getElementById('pay-highlight-meta').textContent =
            'Paid: ₱' + paid.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) +
            '  ·  Total: ₱' + total.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
        if (balance > 0 && remainingTerms > 0) {
            document.getElementById('pay-amount-helper').textContent =
                'Suggested per term: ₱' + suggestedTerm.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) +
                ' (' + remainingTerms + ' of ' + terms + ' term(s) remaining)' +
                (due ? ' · Due: ' + due : '') +
                (termDays > 0 ? ' · Window: ' + termDays + ' days' : '');
            document.getElementById('pay-amount').value = this.dataset.suggestedTerm;
        } else {
            document.getElementById('pay-amount-helper').textContent =
                balance > 0 ? ('Suggested payment: ₱' + balance.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})) : 'This order is fully paid.';
            document.getElementById('pay-amount').value = balance > 0 ? this.dataset.balance : '';
        }
        document.getElementById('pay-form').action  = this.dataset.action;
        document.getElementById('pay-modal').style.display = 'flex';
    });
});

document.getElementById('pay-amount').addEventListener('input', function () {
    const balanceText = document.getElementById('pay-highlight-balance').textContent.replace(/[₱,]/g, '');
    const balance = parseFloat(balanceText || '0');
    const current = parseFloat(this.value || '0');
    const helper = document.getElementById('pay-amount-helper');
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
</script>

@endsection
