@extends('layouts.app')

@section('title', 'New Sale')

@section('content')

<style>
    /* ── Product search panel (same pattern as PO) ── */
    .product-panel {
        display: none;
        flex-direction: column;
        background: #fff;
        border: 1.5px solid #2563eb;
        border-radius: 10px;
        box-shadow: 0 8px 28px rgba(37,99,235,.13);
        overflow: hidden;
        position: absolute;
        z-index: 200;
        width: 100%;
        top: calc(100% + 6px);
        left: 0;
        max-height: 360px;
    }
    .product-panel.open { display: flex; }
    .product-panel-header {
        padding: 8px 14px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .6px;
        flex-shrink: 0;
    }
    .product-panel-body {
        overflow-y: auto;
        flex: 1;
    }
    .product-panel-empty {
        padding: 28px 16px;
        text-align: center;
        font-size: 13px;
        color: #94a3b8;
    }
    .sale-product-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        transition: background .1s;
    }
    .sale-product-item:last-child { border-bottom: none; }
    .sale-product-item:hover { background: #f8fafc; }
    .sale-product-item.in-cart { background: #f0fdf4; }
    .sale-product-item.in-cart:hover { background: #dcfce7; }
    .sale-product-item.no-stock { opacity: .55; cursor: not-allowed; }
    .spi-name { font-weight: 600; font-size: 13px; color: #0f172a; }
    .spi-meta { font-size: 11px; color: #64748b; margin-top: 2px; display:flex; align-items:center; gap:6px; }
    .spi-price { font-weight: 700; font-size: 13.5px; color: #0f172a; text-align: right; white-space: nowrap; }
    .spi-action { flex-shrink: 0; }

    /* ── Submit button emphasis ── */
    #submit-btn {
        background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        border: none;
        color: #fff;
        font-size: 15px;
        font-weight: 700;
        letter-spacing: .3px;
        padding: 13px 20px;
        border-radius: 10px;
        width: 100%;
        justify-content: center;
        box-shadow: 0 4px 14px rgba(22,163,74,.35);
        transition: opacity .15s, transform .1s, box-shadow .15s;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    #submit-btn:not(:disabled):hover {
        box-shadow: 0 6px 20px rgba(22,163,74,.45);
        transform: translateY(-1px);
    }
    #submit-btn:not(:disabled):active { transform: translateY(0); }
    #submit-btn:disabled {
        background: #e2e8f0;
        color: #94a3b8;
        box-shadow: none;
        cursor: not-allowed;
    }
</style>

<div class="page-header">
    <div>
        <h2>New Sale</h2>
        <p>Select products and enter quantities</p>
    </div>
    <div style="display:flex; gap:8px; align-items:center;">
        <a href="{{ route('products.create') }}" class="btn btn-secondary">+ Add New Product</a>
        <a href="{{ route('sales.index') }}" class="btn btn-secondary">← Back</a>
    </div>
</div>

<form action="{{ route('sales.store') }}" method="POST" id="sale-form">
    @csrf

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div style="display:grid; grid-template-columns:1fr 380px; gap:16px; align-items:start;">

        {{-- ══ LEFT ══ --}}
        <div style="display:flex; flex-direction:column; gap:16px;">

            {{-- Product Search Panel --}}
            <div class="card" style="overflow:visible;">
                <div class="card-title">Search Product</div>
                <div class="card-body" style="overflow:visible;">
                    <div style="position:relative;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                             fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round"
                             style="position:absolute; left:11px; top:50%; transform:translateY(-50%); pointer-events:none;">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <input type="text" id="product-search" class="form-control"
                               placeholder="Type product name or SKU…"
                               autocomplete="off"
                               style="padding-left:34px; font-size:13.5px; padding-top:9px; padding-bottom:9px;">

                        {{-- Dropdown panel --}}
                        <div id="product-panel" class="product-panel">
                            <div class="product-panel-header">Products — click to add to sale</div>
                            <div class="product-panel-body" id="panel-body"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Items Table --}}
            <div class="card">
                <div class="card-title">
                    Sale Items
                    <span id="item-count" class="badge badge-info" style="margin-left:6px;">0 items</span>
                </div>
                <div>
                    <table id="items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th style="width:120px; text-align:right;">Price</th>
                                <th style="width:72px; text-align:center;">Stock</th>
                                <th style="width:90px; text-align:center;">Qty</th>
                                <th style="width:120px; text-align:right;">Subtotal</th>
                                <th style="width:40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body">
                            <tr id="empty-row">
                                <td colspan="6">
                                    <div style="text-align:center; padding:32px 20px; color:#94a3b8; font-size:13px;">
                                        <div style="font-size:26px; margin-bottom:8px;">🛒</div>
                                        Search for a product above to add items to this sale.
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ══ RIGHT: Summary ══ --}}
        <div style="position:sticky; top:68px;">
            <div class="card">
                <div class="card-title">Order Summary</div>
                <div class="card-body">

                    <div id="summary-list" style="margin-bottom:14px; min-height:40px;">
                        <div style="color:#94a3b8; font-size:12px; text-align:center; padding:10px 0;">
                            No items yet
                        </div>
                    </div>

                    <div style="border-top:1px solid #f1f5f9; padding-top:10px;">

                        <div style="display:flex; justify-content:space-between; font-size:12.5px; padding:4px 0;">
                            <span style="color:#64748b;">Subtotal</span>
                            <span id="subtotal-display" style="font-weight:600;">₱0.00</span>
                        </div>

                        {{-- Discount --}}
                        <div style="padding:10px 0; border-top:1px dashed #e2e8f0; margin-top:6px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                <span style="font-size:12px; color:#64748b; font-weight:600;">DISCOUNT</span>
                                <button type="button" id="toggle-discount" onclick="toggleDiscount()"
                                        class="btn btn-secondary btn-sm">+ Add Discount</button>
                            </div>

                            <div id="discount-section" style="display:none;">
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px;">
                                    <button type="button" id="btn-percent" onclick="setDiscountType('percent')"
                                            class="btn btn-secondary btn-sm" style="justify-content:center;">
                                        % Percentage
                                    </button>
                                    <button type="button" id="btn-fixed" onclick="setDiscountType('fixed')"
                                            class="btn btn-secondary btn-sm" style="justify-content:center;">
                                        ₱ Fixed Amount
                                    </button>
                                </div>

                                <div id="discount-input-wrap" style="display:none; flex-direction:column; gap:6px;">
                                    <div style="display:flex; align-items:center; gap:6px;">
                                        <span id="discount-prefix"
                                              style="font-size:13px; font-weight:700; color:#64748b; min-width:16px;"></span>
                                        <input type="number" id="discount-value" class="form-control"
                                               placeholder="0" min="0" step="0.01">
                                        <button type="button" onclick="clearDiscount()" class="btn btn-danger btn-sm">✕</button>
                                    </div>
                                    <div id="discount-display" style="font-size:12px; color:#dc2626; font-weight:600;"></div>
                                </div>
                            </div>

                            <input type="hidden" name="discount_type"   id="discount_type"          value="">
                            <input type="hidden" name="discount_value"  id="discount_value_hidden"  value="0">
                            <input type="hidden" name="discount_amount" id="discount_amount_hidden" value="0">
                        </div>

                        {{-- Grand Total --}}
                        <div style="border-top:2px solid #0f172a; padding-top:10px; margin-top:4px;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="font-size:12px; font-weight:700; color:#0f172a;
                                             text-transform:uppercase; letter-spacing:.5px;">Total</span>
                                <span id="grand-total" style="font-size:24px; font-weight:700; color:#0f172a;">₱0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:14px;">
                        <label>Note <span style="color:#94a3b8; font-weight:400;">(Optional)</span></label>
                        <input type="text" name="note" class="form-control" placeholder="e.g. Walk-in customer">
                    </div>

                    <div style="border-top:1px solid #f1f5f9; margin-top:14px; padding-top:12px;">
                        <div style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase;
                                    letter-spacing:.5px; margin-bottom:8px;">
                            Initial Payment (Optional)
                        </div>
                        <div class="form-group">
                            <label>Amount</label>
                            <input type="number" name="initial_payment_amount" class="form-control"
                                   min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>Method</label>
                            <select name="initial_payment_method" class="form-control">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="e_wallet">E-Wallet</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Reference No <span style="color:#94a3b8; font-weight:400;">(Optional)</span></label>
                            <input type="text" name="initial_payment_reference_no" class="form-control"
                                   placeholder="Txn reference">
                        </div>
                    </div>

                    {{-- Submit button --}}
                    <div style="margin-top:16px;">
                        <button type="button" id="submit-btn" onclick="openConfirmModal()" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2.5"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Review &amp; Submit Sale
                        </button>
                        <div id="submit-hint" style="text-align:center; font-size:11px; color:#94a3b8; margin-top:7px;">
                            Add at least one product to continue
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>

{{-- ══ Confirmation Modal ══ --}}
<style>
    .sale-modal-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15,23,42,.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .sale-modal-backdrop.open { display: flex; }
    .sale-modal-box {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 20px 60px rgba(15,23,42,.24);
        width: 100%;
        max-width: 560px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: smIn .17s ease;
    }
    @keyframes smIn {
        from { opacity:0; transform:translateY(10px) scale(.98); }
        to   { opacity:1; transform:translateY(0) scale(1); }
    }
    .sale-modal-header {
        padding: 18px 22px 14px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }
    .sale-modal-header h3 { font-size: 16px; font-weight: 700; color: #0f172a; margin: 0; }
    .sale-modal-body { overflow-y: auto; flex: 1; padding: 18px 22px; }
    .sale-modal-footer {
        padding: 14px 22px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        background: #f8fafc;
        flex-shrink: 0;
    }
    .confirm-items-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .confirm-items-table th {
        font-size: 10.5px; font-weight: 700; color: #64748b;
        text-transform: uppercase; letter-spacing: .5px;
        padding: 0 8px 8px; border-bottom: 2px solid #e2e8f0; text-align: left;
    }
    .confirm-items-table th:last-child { text-align: right; }
    .confirm-items-table td {
        padding: 9px 8px; border-bottom: 1px solid #f1f5f9;
        font-size: 13px; vertical-align: middle;
    }
    .confirm-items-table tr:last-child td { border-bottom: none; }
    .confirm-totals { border-top: 1px solid #e2e8f0; padding-top: 12px; }
    .confirm-total-row {
        display: flex; justify-content: space-between;
        font-size: 13px; padding: 4px 0; color: #475569;
    }
    .confirm-total-row.grand {
        border-top: 2px solid #0f172a; margin-top: 8px; padding-top: 10px;
        font-size: 17px; font-weight: 700; color: #0f172a;
    }
    .confirm-total-row.discount-row { color: #dc2626; }
    #modal-confirm-btn {
        background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        border: none; color: #fff;
        font-size: 14px; font-weight: 700;
        padding: 10px 22px; border-radius: 8px;
        box-shadow: 0 4px 12px rgba(22,163,74,.3);
        cursor: pointer; display: flex; align-items: center; gap: 7px;
        transition: opacity .15s;
    }
    #modal-confirm-btn:hover { opacity: .9; }
</style>

<div class="sale-modal-backdrop" id="sale-confirm-modal">
    <div class="sale-modal-box">
        <div class="sale-modal-header">
            <h3>Confirm Sale</h3>
            <button type="button" onclick="closeConfirmModal()"
                    style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:20px;line-height:1;padding:2px 6px;border-radius:6px;"
                    title="Close">✕</button>
        </div>
        <div class="sale-modal-body">
            <table class="confirm-items-table" id="confirm-items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th style="text-align:center; width:60px;">Qty</th>
                        <th style="text-align:right; width:100px;">Price</th>
                        <th style="text-align:right; width:100px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody id="confirm-items-body"></tbody>
            </table>
            <div class="confirm-totals">
                <div class="confirm-total-row">
                    <span>Subtotal</span>
                    <span id="confirm-subtotal"></span>
                </div>
                <div class="confirm-total-row discount-row" id="confirm-discount-row" style="display:none;">
                    <span id="confirm-discount-label">Discount</span>
                    <span id="confirm-discount-amount"></span>
                </div>
                <div class="confirm-total-row grand">
                    <span>Total</span>
                    <span id="confirm-grand-total"></span>
                </div>
            </div>
            <div id="confirm-payment-info" style="display:none; margin-top:14px; padding:12px 14px;
                 background:#f0fdf4; border-radius:8px; border:1px solid #bbf7d0;">
                <div style="font-size:11px; font-weight:700; color:#15803d; text-transform:uppercase;
                            letter-spacing:.5px; margin-bottom:6px;">Initial Payment</div>
                <div id="confirm-payment-detail" style="font-size:13px; color:#166534;"></div>
            </div>
        </div>
        <div class="sale-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">← Edit</button>
            <button type="button" id="modal-confirm-btn" onclick="submitSaleForm()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Confirm &amp; Save Sale
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    const products   = @json($products);
    let addedItems   = {};
    let discountType = null;
    let panelCloseTimer = null;

    const searchInput = document.getElementById('product-search');
    const panel       = document.getElementById('product-panel');
    const panelBody   = document.getElementById('panel-body');

    /* ── Panel helpers ──────────────────────────────── */
    function openPanel() {
        renderPanel();
        panel.classList.add('open');
    }

    function closePanel() {
        panel.classList.remove('open');
    }

    function renderPanel(query) {
        const q = (query ?? searchInput.value).trim().toLowerCase();

        const filtered = q.length === 0
            ? products
            : products.filter(p =>
                p.name.toLowerCase().includes(q) ||
                (p.sku && p.sku.toLowerCase().includes(q))
              );

        if (filtered.length === 0) {
            panelBody.innerHTML = `<div class="product-panel-empty">
                No products match "<strong>${esc(q)}</strong>".
                <a href="{{ route('products.create') }}" style="margin-left:6px;">+ Add product</a>
            </div>`;
            return;
        }

        panelBody.innerHTML = '';
        filtered.forEach(p => {
            const inCart   = !!addedItems[p.id];
            const noStock  = p.stock_quantity <= 0;
            const lowStock = !noStock && p.stock_quantity <= p.minimum_stock;
            const stockColor = noStock ? '#dc2626' : lowStock ? '#d97706' : '#16a34a';
            const stockLabel = noStock
                ? `⚠ Out of stock (${p.stock_quantity})`
                : lowStock ? `Low: ${p.stock_quantity}` : `${p.stock_quantity} in stock`;

            const div = document.createElement('div');
            div.className = 'sale-product-item' + (inCart ? ' in-cart' : '');
            div.dataset.productId = p.id;

            div.innerHTML = `
                <div style="min-width:0; flex:1;">
                    <div class="spi-name">${esc(p.name)}</div>
                    <div class="spi-meta">
                        ${p.sku ? `<span class="badge badge-gray">${esc(p.sku)}</span>` : ''}
                        <span style="color:${stockColor}; font-weight:600;">${stockLabel}</span>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:10px; flex-shrink:0;">
                    <div class="spi-price">₱${parseFloat(p.selling_price).toFixed(2)}</div>
                    <div class="spi-action">
                        ${inCart
                            ? `<span class="badge badge-success" style="padding:4px 8px; font-size:11px;">✓ In cart</span>`
                            : `<button type="button" class="btn btn-primary btn-sm">+ Add</button>`
                        }
                    </div>
                </div>`;

            div.addEventListener('click', () => addProduct(p.id));
            panelBody.appendChild(div);
        });
    }

    /* ── Focus / blur ───────────────────────────────── */
    searchInput.addEventListener('focus', () => {
        clearTimeout(panelCloseTimer);
        openPanel();
    });

    searchInput.addEventListener('blur', () => {
        panelCloseTimer = setTimeout(closePanel, 200);
    });

    panel.addEventListener('mousedown', e => e.preventDefault());

    searchInput.addEventListener('input', function () {
        renderPanel(this.value);
        panel.classList.add('open');
    });

    /* ── Add product ────────────────────────────────── */
    function addProduct(productId) {
        const p = products.find(x => x.id === productId);
        if (!p) return;

        if (addedItems[productId]) {
            // Increment qty
            const qtyInput = document.getElementById('qty-' + productId);
            const next = Math.min((parseInt(qtyInput.value) || 1) + 1, p.stock_quantity);
            qtyInput.value = next;
            onQtyChange(productId);
            // Flash the row
            const row = document.getElementById('row-' + productId);
            if (row) {
                row.style.transition = 'background .15s';
                row.style.background = '#dbeafe';
                setTimeout(() => { row.style.background = ''; }, 500);
            }
        } else {
            const emptyRow = document.getElementById('empty-row');
            if (emptyRow) emptyRow.remove();

            const tbody = document.getElementById('items-body');
            const tr    = document.createElement('tr');
            tr.id       = 'row-' + productId;

            const noStock  = p.stock_quantity <= 0;
            const lowStock = !noStock && p.stock_quantity <= p.minimum_stock;
            const stockColor = noStock ? '#dc2626' : lowStock ? '#d97706' : '#16a34a';

            tr.innerHTML = `
                <td>
                    <div style="font-weight:600; font-size:13px; color:#0f172a;">${esc(p.name)}</div>
                    ${p.sku ? `<span class="badge badge-gray" style="margin-top:2px;">${esc(p.sku)}</span>` : ''}
                    <input type="hidden" name="items[${productId}][product_id]" value="${productId}">
                </td>
                <td style="font-weight:600; color:#0f172a; text-align:right; white-space:nowrap;">
                    ₱${parseFloat(p.selling_price).toFixed(2)}
                </td>
                <td style="text-align:center;">
                    <span style="font-size:12px; font-weight:600; color:${stockColor};">
                        ${p.stock_quantity}
                    </span>
                </td>
                <td style="text-align:center;">
                    <input type="number" name="items[${productId}][quantity]"
                           id="qty-${productId}" class="form-control"
                           value="1" min="1"
                           oninput="onQtyChange(${productId})"
                           style="width:68px; text-align:center;">
                </td>
                <td style="text-align:right;">
                    <span id="subtotal-${productId}" style="font-weight:700; color:#0f172a;">
                        ₱${parseFloat(p.selling_price).toFixed(2)}
                    </span>
                </td>
                <td style="text-align:center;">
                    <button type="button" onclick="removeProduct(${productId})"
                            class="btn btn-danger btn-sm">✕</button>
                </td>`;

            tbody.appendChild(tr);
            addedItems[productId] = { price: p.selling_price, qty: 1 };
        }

        closePanel();
        searchInput.blur();
        updateSummary();
        renderPanel();  // Refresh panel so in-cart badge updates
    }

    window.removeProduct = function (productId) {
        const row = document.getElementById('row-' + productId);
        if (row) row.remove();
        delete addedItems[productId];

        if (Object.keys(addedItems).length === 0) {
            document.getElementById('items-body').innerHTML = `
                <tr id="empty-row">
                    <td colspan="6">
                        <div style="text-align:center; padding:32px 20px; color:#94a3b8; font-size:13px;">
                            <div style="font-size:26px; margin-bottom:8px;">🛒</div>
                            Search for a product above to add items to this sale.
                        </div>
                    </td>
                </tr>`;
        }

        updateSummary();
        renderPanel();
    };

    window.onQtyChange = function (productId) {
        const p = products.find(x => x.id === productId);
        const qtyInput = document.getElementById('qty-' + productId);
        let qty = parseInt(qtyInput.value) || 1;
        if (qty < 1) { qty = 1; qtyInput.value = 1; }

        document.getElementById('subtotal-' + productId).textContent =
            '₱' + (p.selling_price * qty).toFixed(2);

        if (addedItems[productId]) addedItems[productId].qty = qty;
        updateSummary();
    };

    /* ── Wire discount value input ──────────────────── */
    document.getElementById('discount-value').addEventListener('input', updateSummary);

    /* ── Discount ───────────────────────────────────── */
    window.toggleDiscount = function () {
        const section = document.getElementById('discount-section');
        const btn     = document.getElementById('toggle-discount');
        const visible = section.style.display !== 'none';
        section.style.display = visible ? 'none' : 'block';
        btn.textContent = visible ? '+ Add Discount' : '− Hide Discount';
        if (visible) clearDiscount();
    };

    window.setDiscountType = function (type) {
        discountType = type;
        document.getElementById('discount_type').value = type;
        document.getElementById('discount-input-wrap').style.display = 'flex';
        document.getElementById('discount-prefix').textContent = type === 'percent' ? '%' : '₱';
        document.getElementById('btn-percent').className =
            'btn btn-sm ' + (type === 'percent' ? 'btn-primary' : 'btn-secondary');
        document.getElementById('btn-fixed').className =
            'btn btn-sm ' + (type === 'fixed'   ? 'btn-primary' : 'btn-secondary');
        document.getElementById('discount-value').value = '';
        document.getElementById('discount-value').focus();
        updateSummary();
    };

    window.clearDiscount = function () {
        discountType = null;
        document.getElementById('discount_type').value          = '';
        document.getElementById('discount_value_hidden').value  = '0';
        document.getElementById('discount_amount_hidden').value = '0';
        document.getElementById('discount-value').value         = '';
        document.getElementById('discount-input-wrap').style.display = 'none';
        document.getElementById('discount-display').textContent = '';
        document.getElementById('btn-percent').className = 'btn btn-secondary btn-sm';
        document.getElementById('btn-fixed').className   = 'btn btn-secondary btn-sm';
        updateSummary();
    };

    /* ── Summary ────────────────────────────────────── */
    function updateSummary() {
        let subtotal    = 0;
        let summaryHtml = '';
        const itemKeys  = Object.keys(addedItems);

        itemKeys.forEach(pid => {
            const p = products.find(x => x.id == pid);
            if (!p) return;
            const qty     = parseInt(document.getElementById('qty-' + pid)?.value || 1) || 1;
            const itemSub = p.selling_price * qty;
            subtotal += itemSub;

            summaryHtml += `
                <div style="display:flex; justify-content:space-between; font-size:12.5px;
                            padding:5px 0; border-bottom:1px solid #f1f5f9;">
                    <span style="color:#334155; flex:1; padding-right:8px;
                                 white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        ${esc(p.name)} <span style="color:#94a3b8;">× ${qty}</span>
                    </span>
                    <span style="font-weight:600; white-space:nowrap;">₱${itemSub.toFixed(2)}</span>
                </div>`;
        });

        document.getElementById('summary-list').innerHTML = summaryHtml ||
            '<div style="color:#94a3b8; font-size:12px; text-align:center; padding:10px 0;">No items yet</div>';
        document.getElementById('subtotal-display').textContent = '₱' + subtotal.toFixed(2);

        // Discount
        let discountAmount = 0;
        const discVal = parseFloat(document.getElementById('discount-value').value) || 0;
        if (discountType === 'percent' && discVal > 0) {
            const pct = Math.min(discVal, 100);
            discountAmount = subtotal * (pct / 100);
            document.getElementById('discount-display').textContent =
                `−₱${discountAmount.toFixed(2)} (${pct}% off)`;
        } else if (discountType === 'fixed' && discVal > 0) {
            discountAmount = Math.min(discVal, subtotal);
            document.getElementById('discount-display').textContent =
                `−₱${discountAmount.toFixed(2)}`;
        } else {
            const el = document.getElementById('discount-display');
            if (el) el.textContent = '';
        }

        document.getElementById('discount_value_hidden').value  = discVal;
        document.getElementById('discount_amount_hidden').value = discountAmount.toFixed(2);

        const total = Math.max(0, subtotal - discountAmount);
        document.getElementById('grand-total').textContent  = '₱' + total.toFixed(2);
        document.getElementById('item-count').textContent   = itemKeys.length + ' item(s)';

        const submitBtn  = document.getElementById('submit-btn');
        const submitHint = document.getElementById('submit-hint');
        submitBtn.disabled = itemKeys.length === 0;
        submitHint.textContent = itemKeys.length === 0
            ? 'Add at least one product to continue'
            : `Ready — ${itemKeys.length} product${itemKeys.length > 1 ? 's' : ''}, total ₱${total.toFixed(2)}`;
        submitHint.style.color = itemKeys.length === 0 ? '#94a3b8' : '#16a34a';
    }

    /* ── Confirmation modal ─────────────────────────── */
    window.openConfirmModal = function () {
        const itemKeys = Object.keys(addedItems);
        if (itemKeys.length === 0) return;

        // Build items rows
        let subtotal = 0;
        let rowsHtml = '';
        itemKeys.forEach(pid => {
            const p   = products.find(x => x.id == pid);
            if (!p) return;
            const qty = parseInt(document.getElementById('qty-' + pid)?.value || 1) || 1;
            const sub = p.selling_price * qty;
            subtotal += sub;
            rowsHtml += `<tr>
                <td style="font-weight:600;">${esc(p.name)}${p.sku ? `<br><span style="font-size:11px;color:#94a3b8;">${esc(p.sku)}</span>` : ''}</td>
                <td style="text-align:center;">${qty}</td>
                <td style="text-align:right;">₱${parseFloat(p.selling_price).toFixed(2)}</td>
                <td style="text-align:right; font-weight:700;">₱${sub.toFixed(2)}</td>
            </tr>`;
        });
        document.getElementById('confirm-items-body').innerHTML = rowsHtml;

        // Totals
        const discVal    = parseFloat(document.getElementById('discount-value').value) || 0;
        const discAmt    = parseFloat(document.getElementById('discount_amount_hidden').value) || 0;
        const total      = Math.max(0, subtotal - discAmt);

        document.getElementById('confirm-subtotal').textContent    = '₱' + subtotal.toFixed(2);
        document.getElementById('confirm-grand-total').textContent = '₱' + total.toFixed(2);

        const discRow = document.getElementById('confirm-discount-row');
        if (discAmt > 0) {
            const type  = document.getElementById('discount_type').value;
            const label = type === 'percent'
                ? `Discount (${discVal}%)`
                : 'Discount (Fixed)';
            document.getElementById('confirm-discount-label').textContent  = label;
            document.getElementById('confirm-discount-amount').textContent = '−₱' + discAmt.toFixed(2);
            discRow.style.display = 'flex';
        } else {
            discRow.style.display = 'none';
        }

        // Payment info
        const payAmt = parseFloat(document.querySelector('[name="initial_payment_amount"]')?.value) || 0;
        const payInfo = document.getElementById('confirm-payment-info');
        if (payAmt > 0) {
            const method = document.querySelector('[name="initial_payment_method"]')?.value || '';
            const ref    = document.querySelector('[name="initial_payment_reference_no"]')?.value || '';
            document.getElementById('confirm-payment-detail').innerHTML =
                `₱${payAmt.toFixed(2)} via <strong>${method.replace('_', ' ')}</strong>`
                + (ref ? ` · Ref: ${esc(ref)}` : '');
            payInfo.style.display = 'block';
        } else {
            payInfo.style.display = 'none';
        }

        document.getElementById('sale-confirm-modal').classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.closeConfirmModal = function () {
        document.getElementById('sale-confirm-modal').classList.remove('open');
        document.body.style.overflow = '';
    };

    window.submitSaleForm = function () {
        document.getElementById('modal-confirm-btn').textContent = 'Saving…';
        document.getElementById('modal-confirm-btn').disabled    = true;
        document.getElementById('sale-form').submit();
    };

    // Close on backdrop click
    document.getElementById('sale-confirm-modal').addEventListener('click', function (e) {
        if (e.target === this) window.closeConfirmModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') window.closeConfirmModal();
    });

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = String(s ?? '');
        return d.innerHTML;
    }
})();
</script>

@endsection
