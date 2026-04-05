@extends('layouts.app')
@section('title', 'Record Stock Transfer')
@section('content')
    <style>
        .transfer-row {
            display:grid;
            grid-template-columns: 1fr 120px auto;
            gap:10px;
            align-items:end;
            margin-bottom:10px;
            padding:10px;
            border:1px solid #e2e8f0;
            border-radius:8px;
            background:#f8fafc;
        }
        .combo-error {
            display:block;
            margin-top:4px;
            font-size:11px;
            color:#dc2626;
            font-weight:600;
        }
        .combo-wrap { position: relative; }
        .combo-wrap .combo-input {
            padding-right: 34px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2364758b' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 14px 14px;
            cursor: pointer;
        }
        .combo-wrap.open .combo-input {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.08);
        }
        .combo-list {
            position: absolute;
            z-index: 40;
            left: 0;
            right: 0;
            margin-top: 4px;
            max-height: 220px;
            overflow: auto;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.15);
            display: none;
        }
        .combo-item {
            padding: 8px 10px;
            cursor: pointer;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
        }
        .combo-item:last-child { border-bottom: 0; }
        .combo-item:hover { background: #f8fafc; }
        .combo-empty {
            padding: 8px 10px;
            color: #64748b;
            font-size: 12px;
        }
    </style>
    <div class="page-header">
        <div><h2>Record Stock Transfer</h2><p>Transfer from Main Branch to Second Branch</p></div>
        <a href="{{ route('stock-transfers.index') }}" class="btn btn-secondary">← Back</a>
    </div>

    <div class="card" style="max-width:540px;">
        <div class="card-title">Transfer Details</div>
        <div class="card-body">
            <form action="{{ route('stock-transfers.store') }}" method="POST">
                @csrf

                <div style="display:grid; grid-template-columns:1fr auto 1fr; gap:12px; align-items:end; margin-bottom:14px;">
                    <div class="form-group" style="margin:0;">
                        <label>From Branch</label>
                        <input class="form-control" value="{{ $mainBranch->name }}" readonly>
                    </div>
                    <div style="padding-bottom:8px; color:#64748b; font-size:18px; font-weight:700;">→</div>
                    <div class="form-group" style="margin:0;">
                        <label>To Branch</label>
                        <input class="form-control" value="{{ $secondBranch->name }}" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <label style="margin:0;">Products to Transfer *</label>
                        <button type="button" class="btn btn-secondary btn-sm" id="add-transfer-row">+ Add Product</button>
                    </div>
                    <div id="transfer-items-wrap"></div>
                    @error('items')<small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small>@enderror
                    @error('items.*.product_id')<small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small>@enderror
                    @error('items.*.quantity')<small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small>@enderror
                </div>

                <div class="form-group">
                    <label>Note <span style="color:#94a3b8; font-weight:400;">(Optional)</span></label>
                    <textarea name="note" class="form-control" rows="2"
                              placeholder="e.g. Monthly restock from warehouse to main branch">{{ old('note') }}</textarea>
                </div>

                <div style="display:flex; gap:8px; margin-top:6px;">
                    <button type="submit" class="btn btn-primary">Record Transfer</button>
                    <a href="{{ route('stock-transfers.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    @php
        $productsForJs = $products->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'stock' => (int) $p->stock_quantity,
            ];
        })->values();
    @endphp

    <script>
        (function () {
            const products = @json($productsForJs);
            const oldItems = @json(old('items', []));
            const wrap = document.getElementById('transfer-items-wrap');
            const addBtn = document.getElementById('add-transfer-row');
            const byId = {};
            products.forEach(p => byId[String(p.id)] = p);

            function rowTemplate(index, selectedId = '', qty = 1) {
                const selected = selectedId ? byId[String(selectedId)] : null;
                const label = selected
                    ? `${selected.name}${selected.sku ? ' — ' + selected.sku : ''} (Stock: ${selected.stock})`
                    : '';

                return `
                    <div class="transfer-row" data-row="${index}">
                        <div>
                            <label style="font-size:11px; font-weight:600; color:#374151;">Product</label>
                            <div class="combo-wrap">
                                <input type="text" class="form-control combo-input"
                                       placeholder="Search/select product..."
                                       value="${escapeHtml(label)}" autocomplete="off" required>
                                <input type="hidden" name="items[${index}][product_id]"
                                       value="${selectedId ? String(selectedId) : ''}" class="combo-hidden-id">
                                <div class="combo-list"></div>
                            </div>
                            <small class="combo-error" style="display:none;"></small>
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:600; color:#374151;">Qty</label>
                            <input type="number" name="items[${index}][quantity]" class="form-control"
                                   min="1" value="${qty}" required>
                        </div>
                        <div>
                            <button type="button" class="btn btn-danger btn-sm remove-transfer-row">Remove</button>
                        </div>
                    </div>
                `;
            }

            function addRow(selectedId = '', qty = 1) {
                const idx = wrap.querySelectorAll('.transfer-row').length;
                wrap.insertAdjacentHTML('beforeend', rowTemplate(idx, selectedId, qty));
                bindRemoveButtons();
            }

            function bindRemoveButtons() {
                wrap.querySelectorAll('.remove-transfer-row').forEach(btn => {
                    btn.onclick = function () {
                        this.closest('.transfer-row').remove();
                        reindexRows();
                    };
                });
                bindProductControls();
            }

            function reindexRows() {
                wrap.querySelectorAll('.transfer-row').forEach((row, idx) => {
                    row.setAttribute('data-row', idx);
                    row.querySelectorAll('select, input').forEach(el => {
                        if (el.name.includes('[product_id]')) el.name = `items[${idx}][product_id]`;
                        if (el.name.includes('[quantity]')) el.name = `items[${idx}][quantity]`;
                    });
                });
            }

            function bindProductControls() {
                wrap.querySelectorAll('.transfer-row').forEach((row) => {
                    const input = row.querySelector('.combo-input');
                    const hidden = row.querySelector('.combo-hidden-id');
                    const list = row.querySelector('.combo-list');
                    const errorEl = row.querySelector('.combo-error');

                    function renderList(q = '') {
                        const query = q.trim().toLowerCase();
                        const filtered = products.filter(p =>
                            query === '' || `${p.name} ${p.sku ?? ''}`.toLowerCase().includes(query)
                        );
                        if (!filtered.length) {
                            list.innerHTML = '<div class="combo-empty">No matching products.</div>';
                        } else {
                            list.innerHTML = filtered.map(p => (
                                `<div class="combo-item" data-id="${p.id}">
                                    ${escapeHtml(p.name)}${p.sku ? ' — ' + escapeHtml(p.sku) : ''} (Stock: ${p.stock})
                                </div>`
                            )).join('');
                        }
                        list.style.display = 'block';
                        row.querySelector('.combo-wrap').classList.add('open');
                    }

                    function hideList() {
                        list.style.display = 'none';
                        row.querySelector('.combo-wrap').classList.remove('open');
                    }

                    function isDuplicate(id) {
                        return Array.from(wrap.querySelectorAll('.combo-hidden-id'))
                            .filter(el => el !== hidden)
                            .some(el => el.value === String(id));
                    }

                    input.onfocus = function () {
                        renderList(this.value);
                    };

                    input.oninput = function () {
                        hidden.value = '';
                        errorEl.style.display = 'none';
                        renderList(this.value);
                    };

                    input.onblur = function () {
                        setTimeout(() => {
                            if (!hidden.value && input.value.trim() !== '') {
                                errorEl.textContent = 'Select a valid product from the dropdown.';
                                errorEl.style.display = 'block';
                            }
                            hideList();
                        }, 150);
                    };

                    list.onmousedown = function (e) {
                        e.preventDefault();
                    };

                    list.onclick = function (e) {
                        const item = e.target.closest('.combo-item');
                        if (!item) return;
                        const id = item.getAttribute('data-id');
                        const p = byId[String(id)];
                        if (!p) return;
                        if (isDuplicate(id)) {
                            hidden.value = '';
                            input.value = '';
                            errorEl.textContent = 'This product is already selected in another row.';
                            errorEl.style.display = 'block';
                        } else {
                            hidden.value = String(id);
                            input.value = `${p.name}${p.sku ? ' — ' + p.sku : ''} (Stock: ${p.stock})`;
                            errorEl.style.display = 'none';
                        }
                        hideList();
                    };
                });
            }

            function escapeHtml(str) {
                const d = document.createElement('div');
                d.textContent = String(str ?? '');
                return d.innerHTML;
            }

            addBtn.addEventListener('click', () => addRow('', 1));

            if (Array.isArray(oldItems) && oldItems.length > 0) {
                oldItems.forEach(item => addRow(item.product_id ?? '', item.quantity ?? 1));
            } else {
                addRow('', 1);
            }

            // Validate combobox values before submit and support offline queue
            const form = document.querySelector('form[action="{{ route('stock-transfers.store') }}"]');
            form.addEventListener('submit', async function (e) {
                let hasError = false;
                const selectedIds = new Set();
                const items = [];

                wrap.querySelectorAll('.transfer-row').forEach(row => {
                    const input = row.querySelector('.combo-input');
                    const hidden = row.querySelector('.combo-hidden-id');
                    const qtyEl = row.querySelector('input[name*=\"[quantity]\"]');
                    const errorEl = row.querySelector('.combo-error');
                    const qty = parseInt(qtyEl?.value || '0', 10) || 0;

                    if (!hidden.value) {
                        errorEl.textContent = input.value.trim() ? 'Select a valid product from the dropdown.' : 'Select a product.';
                        errorEl.style.display = 'block';
                        hasError = true;
                        return;
                    }
                    if (selectedIds.has(hidden.value)) {
                        errorEl.textContent = 'This product is already selected in another row.';
                        errorEl.style.display = 'block';
                        hasError = true;
                        return;
                    }
                    if (qty <= 0) {
                        errorEl.textContent = 'Quantity must be at least 1.';
                        errorEl.style.display = 'block';
                        hasError = true;
                        return;
                    }
                    selectedIds.add(hidden.value);
                    errorEl.style.display = 'none';
                    items.push({
                        product_id: parseInt(hidden.value, 10),
                        quantity: qty,
                    });
                });

                if (hasError) {
                    e.preventDefault();
                    return;
                }

                if (!navigator.onLine && window.CitiOffline && typeof window.CitiOffline.queueStockTransfer === 'function') {
                    e.preventDefault();
                    if (!items.length) {
                        alert('Add at least one product to transfer.');
                        return;
                    }
                    const payload = {
                        note: form.querySelector('[name=\"note\"]')?.value || '',
                        items,
                    };
                    try {
                        const localId = await window.CitiOffline.queueStockTransfer(payload);
                        alert('Offline: Stock transfer(s) saved locally and will auto-sync when online. Ref: ' + localId.slice(0, 8));
                        window.location.href = '{{ route('stock-transfers.index') }}';
                    } catch (err) {
                        alert((err && err.message) || 'Failed to save stock transfer offline.');
                    }
                }
            });
        })();
    </script>
@endsection
