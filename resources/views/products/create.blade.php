@extends('layouts.app')

@section('title', 'Add Product')

@section('content')
    <div class="page-header">
        <div>
            <h2>Add New Product</h2>
            <p>Fill in the details below to add a new product</p>
        </div>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">← Back</a>
    </div>

    <form action="{{ route('products.store') }}" method="POST">
        @csrf
        <div style="display:grid; grid-template-columns:1fr 380px; gap:20px; align-items:start;">

            {{-- Left column --}}
            <div>
                <div class="card">
                    <div class="card-title">Basic Information</div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Product Name *</label>
                            <input type="text" name="name" class="form-control"
                                   value="{{ old('name') }}" placeholder="e.g. Extension Cord 3m" required autofocus>
                            @error('name')<small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small>@enderror
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
                            <div class="form-group">
                                <label>Brand</label>
                                <input type="text" name="brand" class="form-control" value="{{ old('brand') }}" placeholder="e.g. Panasonic">
                            </div>
                            <div class="form-group">
                                <label>Category</label>
                                <input type="text" name="category" class="form-control" value="{{ old('category') }}" placeholder="e.g. Cables">
                            </div>
                            <div class="form-group">
                                <label>Model</label>
                                <input type="text" name="model" class="form-control" value="{{ old('model') }}" placeholder="e.g. EC-3M">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>SKU <span style="color:#94a3b8; font-weight:400;">(Optional)</span></label>
                            <input type="text" name="sku" class="form-control" value="{{ old('sku') }}" placeholder="e.g. EC-3M-001">
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Optional product description...">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">Suppliers & Cost Prices</div>
                    <div class="card-body">
                        <p style="font-size:13px; color:#64748b; margin-bottom:12px;">
                            Link suppliers and their individual cost price for this product.
                        </p>
                        <div id="supplier-rows"></div>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="addSupplierRow()">+ Add Supplier</button>
                    </div>
                </div>
            </div>

            {{-- Right column --}}
            <div>
                <div class="card">
                    <div class="card-title">Pricing & Stock</div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Purchase Price (₱) *</label>
                            <input type="number" name="purchase_price" id="purchase_price"
                                   class="form-control" value="{{ old('purchase_price') }}"
                                   placeholder="0.00" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Selling Price (₱) *</label>
                            <input type="number" name="selling_price" id="selling_price"
                                   class="form-control" value="{{ old('selling_price') }}"
                                   placeholder="0.00" step="0.01" min="0" required>
                        </div>

                        <div id="profit-preview" style="background:#f0fdf4; border:1px solid #bbf7d0;
                            border-radius:6px; padding:10px 14px; margin-bottom:14px; display:none;">
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
                                       value="{{ old('stock_quantity', 0) }}" min="0" required>
                            </div>
                            <div class="form-group">
                                <label>Min Stock *</label>
                                <input type="number" name="minimum_stock" class="form-control"
                                       value="{{ old('minimum_stock', 0) }}" min="0" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary" style="flex:1;">Save Product</button>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </div>

        </div>
    </form>

    <script>
    const suppliersData = @json($suppliers);

    function addSupplierRow(selectedId = '', costPrice = '') {
        const container = document.getElementById('supplier-rows');
        const idx = container.children.length;
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

    // Profit preview
    const purchaseInput = document.getElementById('purchase_price');
    const sellingInput  = document.getElementById('selling_price');
    const preview       = document.getElementById('profit-preview');
    const profitAmt     = document.getElementById('profit-amount');
    const profitMargin  = document.getElementById('profit-margin');

    function updateProfit() {
        const purchase = parseFloat(purchaseInput.value) || 0;
        const selling  = parseFloat(sellingInput.value) || 0;
        if (selling > 0) {
            const profit = selling - purchase;
            const margin = ((profit / selling) * 100).toFixed(1);
            profitAmt.textContent    = '₱' + profit.toFixed(2);
            profitMargin.textContent = margin + '%';
            profitAmt.style.color    = profit >= 0 ? '#16a34a' : '#dc2626';
            profitMargin.style.color = profit >= 0 ? '#16a34a' : '#dc2626';
            preview.style.display    = 'block';
        } else {
            preview.style.display = 'none';
        }
    }
    purchaseInput.addEventListener('input', updateProfit);
    sellingInput.addEventListener('input', updateProfit);

    // Restore old supplier rows on validation error
    @if(old('supplier_ids'))
        @foreach(old('supplier_ids', []) as $i => $sid)
            addSupplierRow('{{ $sid }}', '{{ old("supplier_costs." . $i, "") }}');
        @endforeach
    @endif
    </script>
@endsection
