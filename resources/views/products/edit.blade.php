@extends('layouts.app')

@section('title', 'Edit Product')

@section('content')
    <div class="page-header">
        <div>
            <h2>Edit Product</h2>
            <p>Update details for <strong>{{ $product->name }}</strong></p>
        </div>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('products.show', $product) }}" class="btn btn-secondary">View</a>
            <a href="{{ route('products.index') }}" class="btn btn-secondary">← Back</a>
        </div>
    </div>

    <form action="{{ route('products.update', $product) }}" method="POST" id="product-edit-form">
        @csrf @method('PUT')
        <div style="display:grid; grid-template-columns:1fr 380px; gap:20px; align-items:start;">

            {{-- Left --}}
            <div>
                <div class="card">
                    <div class="card-title">Basic Information</div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Product Name *</label>
                            <input type="text" name="name" class="form-control"
                                   value="{{ old('name', $product->name) }}" required autofocus>
                            @error('name')<small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small>@enderror
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
                            <div class="form-group">
                                <label>Brand</label>
                                <input type="text" name="brand" class="form-control" value="{{ old('brand', $product->brand) }}">
                            </div>
                            <div class="form-group">
                                <label>Category</label>
                                <input type="text" name="category" class="form-control" value="{{ old('category', $product->category) }}">
                            </div>
                            <div class="form-group">
                                <label>Model</label>
                                <input type="text" name="model" class="form-control" value="{{ old('model', $product->model) }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>SKU</label>
                            <input type="text" name="sku" class="form-control" value="{{ old('sku', $product->sku) }}">
                            @error('sku')<small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small>@enderror
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $product->description) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">Suppliers & Cost Prices</div>
                    <div class="card-body">
                        <div id="supplier-rows"></div>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="addSupplierRow()">+ Add Supplier</button>
                    </div>
                </div>
            </div>

            {{-- Right --}}
            <div>
                <div class="card">
                    <div class="card-title">Pricing & Stock</div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Purchase Price (₱) *</label>
                            <input type="number" name="purchase_price" id="purchase_price"
                                   class="form-control" value="{{ old('purchase_price', $product->purchase_price) }}"
                                   step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Selling Price (₱) *</label>
                            <input type="number" name="selling_price" id="selling_price"
                                   class="form-control" value="{{ old('selling_price', $product->selling_price) }}"
                                   step="0.01" min="0" required>
                        </div>

                        <div id="profit-preview" style="background:#f0fdf4; border:1px solid #bbf7d0;
                            border-radius:6px; padding:10px 14px; margin-bottom:14px;">
                            <div style="display:flex; gap:20px;">
                                <div>
                                    <div style="font-size:10px; color:#64748b; text-transform:uppercase; font-weight:600;">Profit/Unit</div>
                                    <div id="profit-amount" style="font-size:15px; font-weight:700;"></div>
                                </div>
                                <div>
                                    <div style="font-size:10px; color:#64748b; text-transform:uppercase; font-weight:600;">Margin</div>
                                    <div id="profit-margin" style="font-size:15px; font-weight:700;"></div>
                                </div>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                            <div class="form-group">
                                <label>Stock Qty *</label>
                                <input type="number" name="stock_quantity" class="form-control"
                                       value="{{ old('stock_quantity', $product->stock_quantity) }}" min="0" required>
                            </div>
                            <div class="form-group">
                                <label>Min Stock *</label>
                                <input type="number" name="minimum_stock" class="form-control"
                                       value="{{ old('minimum_stock', $product->minimum_stock) }}" min="0" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary" style="flex:1;">Update Product</button>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>

    <script>
    const suppliersData = @json($suppliers);
    const existingSuppliers = @json($product->suppliers->map(fn($s) => ['id' => $s->id, 'cost_price' => $s->pivot->cost_price]));

    function addSupplierRow(selectedId = '', costPrice = '') {
        const container = document.getElementById('supplier-rows');
        const options = suppliersData.map(s =>
            `<option value="${s.id}" ${s.id == selectedId ? 'selected' : ''}>${s.name}</option>`
        ).join('');

        const row = document.createElement('div');
        row.style.cssText = 'display:flex; gap:8px; align-items:center; margin-bottom:8px;';
        row.innerHTML = `
            <select name="supplier_ids[]" class="form-control" style="flex:1;">
                <option value="">Select Supplier</option>
                ${options}
            </select>
            <div style="position:relative; flex:0 0 130px;">
                <span style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#64748b; pointer-events:none;">₱</span>
                <input type="number" name="supplier_costs[]" class="form-control"
                       placeholder="Cost Price" step="0.01" min="0"
                       value="${costPrice}" style="padding-left:24px;">
            </div>
            <button type="button" onclick="this.closest('div[style]').remove()"
                    class="btn btn-danger btn-sm" style="flex-shrink:0;">✕</button>
        `;
        container.appendChild(row);
    }

    // Restore old supplier rows (validation error) or load existing
    @if(old('supplier_ids'))
        @foreach(old('supplier_ids', []) as $i => $sid)
            addSupplierRow('{{ $sid }}', '{{ old("supplier_costs." . $i, "") }}');
        @endforeach
    @else
        existingSuppliers.forEach(s => addSupplierRow(s.id, s.cost_price));
    @endif

    const purchaseInput = document.getElementById('purchase_price');
    const sellingInput  = document.getElementById('selling_price');
    const profitAmt     = document.getElementById('profit-amount');
    const profitMargin  = document.getElementById('profit-margin');

    function updateProfit() {
        const purchase = parseFloat(purchaseInput.value) || 0;
        const selling  = parseFloat(sellingInput.value) || 0;
        const profit   = selling - purchase;
        const margin   = selling > 0 ? ((profit / selling) * 100).toFixed(1) : '0.0';
        profitAmt.textContent    = '₱' + profit.toFixed(2);
        profitMargin.textContent = margin + '%';
        profitAmt.style.color    = profit >= 0 ? '#16a34a' : '#dc2626';
        profitMargin.style.color = profit >= 0 ? '#16a34a' : '#dc2626';
    }
    updateProfit();
    purchaseInput.addEventListener('input', updateProfit);
    sellingInput.addEventListener('input', updateProfit);

    (function () {
        const form = document.getElementById('product-edit-form');
        if (!form || !window.CitiOffline || typeof window.CitiOffline.queueProductUpdate !== 'function') return;
        form.addEventListener('submit', async function (e) {
            if (navigator.onLine) return;
            e.preventDefault();
            const container = document.getElementById('supplier-rows');
            const selects = container.querySelectorAll('select[name="supplier_ids[]"]');
            const costInputs = container.querySelectorAll('input[name="supplier_costs[]"]');
            const supplier_ids = [];
            const supplier_costs = [];
            selects.forEach(function (sel, i) {
                if (sel.value) {
                    supplier_ids.push(parseInt(sel.value, 10));
                    supplier_costs.push(parseFloat(costInputs[i] && costInputs[i].value ? costInputs[i].value : '0'));
                }
            });
            const payload = {
                product_id: {{ (int) $product->id }},
                name: form.querySelector('[name="name"]')?.value,
                sku: form.querySelector('[name="sku"]')?.value || '',
                brand: form.querySelector('[name="brand"]')?.value || '',
                category: form.querySelector('[name="category"]')?.value || '',
                model: form.querySelector('[name="model"]')?.value || '',
                description: form.querySelector('[name="description"]')?.value || '',
                purchase_price: parseFloat(form.querySelector('[name="purchase_price"]')?.value || '0'),
                selling_price: parseFloat(form.querySelector('[name="selling_price"]')?.value || '0'),
                stock_quantity: parseInt(form.querySelector('[name="stock_quantity"]')?.value || '0', 10),
                minimum_stock: parseInt(form.querySelector('[name="minimum_stock"]')?.value || '0', 10),
                supplier_ids: supplier_ids,
                supplier_costs: supplier_costs,
            };
            if (!payload.name) {
                alert('Product name is required.');
                return;
            }
            try {
                const id = await window.CitiOffline.queueProductUpdate(payload);
                alert('Offline: Product update queued for sync. Ref: ' + id.slice(0, 8));
                window.location.href = '{{ route('products.index') }}';
            } catch (err) {
                alert((err && err.message) || 'Failed to queue update offline.');
            }
        });
    })();
    </script>
@endsection
