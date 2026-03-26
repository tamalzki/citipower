@extends('layouts.app')

@section('title', 'New Purchase Order')

@section('content')

<style>
    /* ─── Product Panel ─────────────────────────────────── */
    .product-panel {
        display: none;
        flex-direction: column;
        background: #fff;
        border: 1.5px solid #2563eb;
        border-radius: 10px;
        box-shadow: 0 8px 28px rgba(37,99,235,0.13);
        overflow: hidden;
        position: absolute;
        z-index: 200;
        width: 100%;
        top: calc(100% + 6px);
        left: 0;
        max-height: 380px;
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
        letter-spacing: 0.6px;
        flex-shrink: 0;
    }
    .product-panel-body {
        overflow-y: auto;
        flex: 1;
    }
    .product-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 9px 14px;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        transition: background .1s;
    }
    .product-item:last-child { border-bottom: none; }
    .product-item:hover { background: #f8fafc; }
    .product-item-name { font-weight: 600; font-size: 13px; color: #0f172a; }
    .product-item-meta { font-size: 11px; color: #64748b; margin-top: 2px; }
    .product-item-price { font-weight: 700; font-size: 13px; color: #0f172a; text-align: right; white-space: nowrap; }
    .product-panel-loader {
        text-align: center;
        padding: 12px;
        font-size: 12px;
        color: #94a3b8;
    }

    /* ─── PO lines table compact inputs ─── */
    .po-table input.form-control { padding: 5px 8px; font-size: 12.5px; }
    .po-table td { vertical-align: middle; }

    /* ─── Summary sidebar ─── */
    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12.5px;
        padding: 7px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .summary-row:last-child { border-bottom: none; }
    .summary-row .label { color: #64748b; }

    /* ─── Step badges ─── */
    .step-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #2563eb;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        flex-shrink: 0;
    }
</style>

<div class="page-header">
    <div>
        <h2>New Purchase Order</h2>
        <p>Fill in order details, pick products, and review the total before saving</p>
    </div>
    <a href="{{ route('purchase-orders.index') }}" class="btn btn-secondary">← Back</a>
</div>

<form method="POST" action="{{ route('purchase-orders.store') }}" id="po-form">
@csrf

{{-- ══ STEP 1: Order Info ══ --}}
<div class="card">
    <div class="card-title">
        <span class="step-badge">1</span> Order Information
    </div>
    <div class="card-body">
        <div style="display:grid; grid-template-columns:1.4fr 1fr 1fr; gap:14px;">
            <div class="form-group" style="margin-bottom:0;">
                <label>Supplier *</label>
                <select name="supplier_id" class="form-control" required>
                    <option value="">— Select supplier —</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
                @if($suppliers->isEmpty())
                    <small style="color:#d97706; margin-top:4px; display:block;">
                        No suppliers yet. <a href="{{ route('suppliers.create') }}">Add one</a> first.
                    </small>
                @endif
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label>Order Date *</label>
                <input type="date" name="order_date" id="order_date" class="form-control"
                       value="{{ now()->toDateString() }}" required>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label>Expected Arrival</label>
                <input type="date" name="expected_arrival_date" class="form-control"
                       value="{{ old('expected_arrival_date') }}">
            </div>
        </div>
        <div class="form-group" style="margin-top:14px; margin-bottom:0;">
            <label>Note / Reference</label>
            <input type="text" name="note" class="form-control"
                   placeholder="e.g. Urgent restock for extension cords" maxlength="255">
        </div>
    </div>
</div>

{{-- ══ STEP 2 + Summary ══ --}}
<div style="display:grid; grid-template-columns:1fr 320px; gap:16px; align-items:start;">

    {{-- Left ──── Product picker + lines ──── --}}
    <div>
        {{-- Product search/picker --}}
        <div class="card" style="overflow:visible;">
            <div class="card-title">
                <span class="step-badge">2</span> Add Products
            </div>
            <div class="card-body" style="overflow:visible;">
                <div style="position:relative;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                         fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round"
                         style="position:absolute; left:10px; top:50%; transform:translateY(-50%); pointer-events:none;">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" id="product-search" class="form-control"
                           placeholder="Type to search products…"
                           autocomplete="off"
                           style="padding-left:34px; font-size:13.5px; padding-top:9px; padding-bottom:9px;">

                    <div id="product-panel" class="product-panel">
                        <div class="product-panel-header">Products — click to add</div>
                        <div class="product-panel-body" id="panel-body">
                            <div class="product-panel-loader" id="panel-loader">Loading…</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Lines table --}}
        <div class="card">
            <div class="card-title">
                <span class="step-badge">3</span> PO Line Items
                <span id="line-badge" class="badge badge-gray" style="margin-left:6px;">0 items</span>
            </div>
            <div class="table-wrapper">
                <table class="po-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style="width:96px;">Qty</th>
                            <th style="width:130px;">Unit price (₱)</th>
                            <th style="width:120px; text-align:right;">Subtotal</th>
                            <th style="width:44px;"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body">
                        <tr id="empty-row">
                            <td colspan="5">
                                <div style="text-align:center; padding:32px 20px; color:#94a3b8;">
                                    <div style="font-size:26px; margin-bottom:8px;">📦</div>
                                    Search for a product above to add items to this order.
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot id="items-foot" style="display:none;">
                        <tr style="background:#f8fafc;">
                            <td colspan="3" style="text-align:right; font-size:12px; font-weight:700;
                                color:#0f172a; text-transform:uppercase; letter-spacing:.4px; padding-right:14px;">
                                Order Total
                            </td>
                            <td style="font-weight:700; font-size:14px; color:#0f172a; text-align:right;"
                                id="foot-grand">₱0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Right ──── Sticky summary --}}
    <div style="position:sticky; top:68px;">
        <div class="card">
            <div class="card-title">Order Summary</div>
            <div class="card-body">
                <div class="summary-row">
                    <span class="label">Supplier</span>
                    <span id="sum-supplier" style="font-weight:600; max-width:150px; text-align:right; color:#0f172a;">—</span>
                </div>
                <div class="summary-row">
                    <span class="label">Order date</span>
                    <span id="sum-date" style="font-weight:600; color:#0f172a;">{{ now()->format('M d, Y') }}</span>
                </div>
                <div class="summary-row">
                    <span class="label">Expected</span>
                    <span id="sum-expected" style="font-weight:600; color:#64748b;">—</span>
                </div>
                <div class="summary-row">
                    <span class="label">Line items</span>
                    <span id="sum-lines" style="font-weight:600; color:#0f172a;">0</span>
                </div>
                <div class="summary-row">
                    <span class="label">Total qty</span>
                    <span id="sum-qty" style="font-weight:600; color:#0f172a;">0</span>
                </div>
                <div style="border-top:2px solid #0f172a; margin-top:12px; padding-top:14px;
                            display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:11px; font-weight:700; text-transform:uppercase;
                                 letter-spacing:.5px; color:#0f172a;">Total</span>
                    <span id="grand-total" style="font-size:24px; font-weight:700; color:#0f172a;">₱0.00</span>
                </div>

                <button type="submit" class="btn btn-primary" id="submit-po"
                        style="width:100%; justify-content:center; padding:10px; margin-top:14px;" disabled>
                    Save Purchase Order
                </button>
                <a href="{{ route('purchase-orders.index') }}" class="btn btn-secondary"
                   style="width:100%; justify-content:center; padding:10px; margin-top:6px;">
                    Cancel
                </a>
            </div>
        </div>

        <div class="card" style="border-color:#fde68a; background:#fffbeb;">
            <div class="card-body" style="padding:12px 16px;">
                <p style="font-size:12px; color:#92400e; line-height:1.6; margin:0;">
                    <strong>Tip:</strong> Unit prices are pre-filled from the product master.
                    Adjust them if the supplier charges a different price for this order.
                    Stock is only updated when you mark the PO as <em>Received</em>.
                </p>
            </div>
        </div>
    </div>

</div>
</form>

<script>
(function () {
    /* ── Config ─────────────────────────────────────── */
    const API = '{{ route('purchase-orders.products-json') }}';
    let page = 1;
    let hasMore = true;
    let loading = false;
    let searchTerm = '';
    let debounceTimer = null;

    /* ── DOM refs ──────────────────────────────────── */
    const searchInput   = document.getElementById('product-search');
    const panel         = document.getElementById('product-panel');
    const panelBody     = document.getElementById('panel-body');
    const panelLoader   = document.getElementById('panel-loader');
    const itemsBody     = document.getElementById('items-body');
    const itemsFoot     = document.getElementById('items-foot');
    const footGrand     = document.getElementById('foot-grand');
    const grandTotal    = document.getElementById('grand-total');
    const lineBadge     = document.getElementById('line-badge');
    const submitBtn     = document.getElementById('submit-po');
    const sumSupplier   = document.getElementById('sum-supplier');
    const sumDate       = document.getElementById('sum-date');
    const sumExpected   = document.getElementById('sum-expected');
    const sumLines      = document.getElementById('sum-lines');
    const sumQty        = document.getElementById('sum-qty');
    const supplierSel   = document.querySelector('select[name="supplier_id"]');
    const orderDateInput    = document.querySelector('input[name="order_date"]');
    const expectedDateInput = document.querySelector('input[name="expected_arrival_date"]');

    let rowSeq = 0;

    /* ── Helpers ────────────────────────────────────── */
    function esc(s) {
        if (!s && s !== 0) return '';
        const d = document.createElement('div');
        d.textContent = String(s);
        return d.innerHTML;
    }

    function fmt(n) {
        return '₱' + parseFloat(n || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function fmtDate(val) {
        if (!val) return '—';
        const d = new Date(val + 'T00:00:00');
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    /* ── Summary sidebar sync ───────────────────────── */
    function syncSummary() {
        const opt = supplierSel.options[supplierSel.selectedIndex];
        sumSupplier.textContent = opt && opt.value ? opt.text : '—';
        sumDate.textContent = fmtDate(orderDateInput.value);
        sumExpected.textContent = fmtDate(expectedDateInput.value);
    }

    supplierSel.addEventListener('change', syncSummary);
    orderDateInput.addEventListener('change', syncSummary);
    expectedDateInput.addEventListener('change', syncSummary);
    syncSummary();

    /* ── Panel open/close ───────────────────────────── */
    let panelCloseTimer = null;

    searchInput.addEventListener('focus', function () {
        clearTimeout(panelCloseTimer);
        openPanel();
    });

    searchInput.addEventListener('blur', function () {
        // Delay close so clicks inside the panel register first
        panelCloseTimer = setTimeout(() => closePanel(), 200);
    });

    panel.addEventListener('mousedown', function (e) {
        // Prevent the input from losing focus when clicking inside the panel
        e.preventDefault();
    });

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const q = this.value.trim();
        if (q === searchTerm) return;
        debounceTimer = setTimeout(() => {
            searchTerm = q;
            resetPanel();
            loadPage();
        }, 220);
    });

    function openPanel() {
        panel.classList.add('open');
        if (panelBody.querySelectorAll('.product-item').length === 0 && !loading) {
            loadPage();
        }
    }

    function closePanel() {
        panel.classList.remove('open');
    }

    function resetPanel() {
        page = 1;
        hasMore = true;
        panelBody.querySelectorAll('.product-item').forEach(el => el.remove());
        panelLoader.style.display = 'block';
        panelLoader.textContent = 'Loading…';
        panel.classList.add('open');
    }

    /* ── Lazy load ──────────────────────────────────── */
    async function loadPage() {
        if (loading || !hasMore) return;
        loading = true;

        const params = new URLSearchParams({ page, search: searchTerm });
        try {
            const res = await fetch(`${API}?${params}`, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            });

            if (!res.ok) {
                throw new Error('Server responded with ' + res.status);
            }

            const json = await res.json();

            panelLoader.style.display = 'none';

            if (!json.data.length && page === 1) {
                panelLoader.style.display = 'block';
                panelLoader.textContent = searchTerm ? 'No products match your search.' : 'No products found.';
                loading = false;
                return;
            }

            json.data.forEach(p => renderProductItem(p));
            hasMore = json.has_more;
            page++;

            if (!hasMore) {
                panelLoader.style.display = 'block';
                panelLoader.textContent = 'All products loaded.';
            } else {
                panelLoader.style.display = 'block';
                panelLoader.textContent = 'Scroll for more…';
            }
        } catch (err) {
            panelLoader.style.display = 'block';
            panelLoader.textContent = 'Failed to load products. (' + err.message + ')';
        }
        loading = false;
    }

    function renderProductItem(p) {
        const div = document.createElement('div');
        div.className = 'product-item';
        div.dataset.productId = p.id;
        div.dataset.price = parseFloat(p.purchase_price) || 0;

        const price = parseFloat(p.purchase_price) || 0;
        const stock = parseInt(p.stock_quantity, 10) || 0;
        const sku   = p.sku || '—';

        const stockColor = stock <= 0 ? '#dc2626' : stock < 5 ? '#d97706' : '#16a34a';
        const stockLabel = stock <= 0 ? 'Out of stock' : `${stock} in stock`;

        div.innerHTML = `
            <div style="min-width:0; flex:1;">
                <div class="product-item-name">${esc(p.name)}</div>
                <div class="product-item-meta">
                    SKU: ${esc(sku)} &nbsp;·&nbsp;
                    <span style="color:${stockColor}; font-weight:600;">${stockLabel}</span>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:10px; flex-shrink:0;">
                <div class="product-item-price">${fmt(price)}</div>
                <button type="button" class="btn btn-primary btn-sm po-add-btn"
                        data-id="${p.id}" data-name="${esc(p.name)}" data-price="${price}">Add</button>
            </div>
        `;
        panelBody.insertBefore(div, panelLoader);
    }

    /* ── Infinite scroll via panelBody scroll event ── */
    panelBody.addEventListener('scroll', function () {
        if (!hasMore || loading) return;
        const nearBottom = panelBody.scrollHeight - panelBody.scrollTop - panelBody.clientHeight < 60;
        if (nearBottom) loadPage();
    });

    /* ── Delegate clicks inside panel (row or Add button) ── */
    panelBody.addEventListener('click', function (e) {
        // Clicking the Add button OR anywhere on the product row triggers add
        const item = e.target.closest('.product-item');
        if (!item) return;
        const btn = item.querySelector('.po-add-btn');
        if (!btn) return;
        window.poAddLine(
            parseInt(btn.dataset.id, 10),
            btn.dataset.name,
            parseFloat(btn.dataset.price) || 0
        );
    });

    /* ── Add line item ──────────────────────────────── */
    window.poAddLine = function (productId, productName, productPrice) {
        const product = {
            id: productId,
            name: productName,
            purchase_price: parseFloat(productPrice) || 0,
        };
        if (!product.name) return;

        // If this product already has a row, just increment its qty
        const existing = itemsBody.querySelector(`tr[data-product-id="${productId}"]`);
        if (existing) {
            const qtyInput = existing.querySelector('.po-qty');
            qtyInput.value = (parseInt(qtyInput.value, 10) || 0) + 1;
            const rowIdx = existing.id.replace('po-row-', '');
            updateLine(parseInt(rowIdx, 10));
            existing.style.transition = 'background .15s';
            existing.style.background = '#dbeafe';
            setTimeout(() => { existing.style.background = ''; }, 500);
            closePanel();
            searchInput.blur();
            return;
        }

        const er = document.getElementById('empty-row');
        if (er) er.remove();

        const idx = rowSeq++;
        const unit = product.purchase_price;
        const qty = 1;
        const sub = unit * qty;

        const tr = document.createElement('tr');
        tr.id = 'po-row-' + idx;
        tr.dataset.productId = productId;
        tr.innerHTML = `
            <td>
                <div style="font-weight:600; color:#0f172a;">${esc(product.name)}</div>
                <input type="hidden" name="items[${idx}][product_id]" value="${productId}">
            </td>
            <td>
                <input type="number" name="items[${idx}][quantity]"
                       class="form-control po-qty" min="1" value="${qty}" required
                       style="width:72px;">
            </td>
            <td>
                <input type="number" name="items[${idx}][purchase_price]"
                       class="form-control po-price" min="0.01" step="0.01" value="${unit.toFixed(2)}" required
                       style="width:110px;">
            </td>
            <td style="text-align:right;">
                <span class="po-subtotal" style="font-weight:700; color:#0f172a;">${fmt(sub)}</span>
            </td>
            <td style="text-align:center;">
                <button type="button" class="btn btn-danger btn-sm" onclick="window.poRemoveLine(${idx})" title="Remove">✕</button>
            </td>
        `;
        itemsBody.appendChild(tr);

        tr.querySelectorAll('.po-qty, .po-price').forEach(el => {
            el.addEventListener('input', () => updateLine(idx));
        });

        recalc();
        submitBtn.disabled = false;
        closePanel();
        searchInput.blur();
    };

    /* ── Remove line ────────────────────────────────── */
    window.poRemoveLine = function (idx) {
        const row = document.getElementById('po-row-' + idx);
        if (row) row.remove();

        if (!itemsBody.querySelector('tr[id^="po-row-"]')) {
            itemsBody.innerHTML = `
                <tr id="empty-row">
                    <td colspan="5">
                        <div style="text-align:center; padding:32px 20px; color:#94a3b8;">
                            <div style="font-size:26px; margin-bottom:8px;">📦</div>
                            Search for a product above to add items to this order.
                        </div>
                    </td>
                </tr>`;
            itemsFoot.style.display = 'none';
            submitBtn.disabled = true;
        }
        recalc();
    };

    /* ── Update subtotal on line change ─────────────── */
    function updateLine(idx) {
        const row = document.getElementById('po-row-' + idx);
        if (!row) return;
        let q = parseInt(row.querySelector('.po-qty').value, 10) || 1;
        if (q < 1) { q = 1; row.querySelector('.po-qty').value = 1; }
        const price = parseFloat(row.querySelector('.po-price').value) || 0;
        row.querySelector('.po-subtotal').textContent = fmt(q * price);
        recalc();
    }

    /* ── Recalculate totals ─────────────────────────── */
    function recalc() {
        let total = 0, lines = 0, totalQty = 0;

        document.querySelectorAll('tr[id^="po-row-"]').forEach(row => {
            const q     = parseInt(row.querySelector('.po-qty').value, 10) || 0;
            const price = parseFloat(row.querySelector('.po-price').value) || 0;
            total    += q * price;
            totalQty += q;
            lines++;
        });

        grandTotal.textContent  = fmt(total);
        footGrand.textContent   = fmt(total);
        lineBadge.textContent   = `${lines} item${lines !== 1 ? 's' : ''}`;
        sumLines.textContent    = lines;
        sumQty.textContent      = totalQty;

        itemsFoot.style.display = lines > 0 ? '' : 'none';
    }

})();
</script>

@endsection
