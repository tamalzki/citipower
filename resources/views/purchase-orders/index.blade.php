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

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
            <tr>
                <th>PO #</th>
                <th>Order Date</th>
                <th>Expected</th>
                <th>Supplier</th>
                <th>DR Number</th>
                <th>Arrival Date</th>
                <th>Status</th>
                <th>Total</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($purchaseOrders as $po)
                <tr>
                    <td style="font-weight:600; color:#2563eb;">{{ $po->po_number }}</td>
                    <td>{{ $po->order_date->format('M d, Y') }}</td>
                    <td>{{ $po->expected_arrival_date?->format('M d, Y') ?? '—' }}</td>
                    <td>{{ $po->supplier?->name }}</td>
                    <td style="font-weight:600;">{{ $po->dr_number ?? '—' }}</td>
                    <td>{{ $po->arrival_date?->format('M d, Y') ?? '—' }}</td>
                    <td>
                        <span class="badge {{ $po->status === 'received' ? 'badge-success' : 'badge-warning' }}">
                            {{ ucfirst($po->status) }}
                        </span>
                    </td>
                    <td style="font-weight:600;">₱{{ number_format($po->total_amount, 2) }}</td>
                    <td>
                        <div style="display:flex; gap:6px;">
                            <a href="{{ route('purchase-orders.show', $po) }}" class="btn btn-secondary btn-sm">View</a>
                            @if($po->status !== 'received')
                                <button type="button" class="btn btn-success btn-sm receive-btn"
                                        data-url="{{ route('purchase-orders.items-json', $po) }}"
                                        data-action="{{ route('purchase-orders.receive', $po) }}">
                                    Receive Items
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" style="color:#94a3b8; text-align:center; padding:30px;">No purchase orders yet.</td></tr>
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
                    <label style="font-size:12px; font-weight:600; color:#374151;">DR Number</label>
                    <input type="text" name="dr_number" id="receive-dr-number" class="form-control"
                           placeholder="e.g. #97861" style="margin-top:4px;">
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

                modalTitle.textContent = 'Receive Items — ' + data.po_number;
                modalSub.textContent   = 'Supplier: ' + (data.supplier || '—')
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

@endsection
