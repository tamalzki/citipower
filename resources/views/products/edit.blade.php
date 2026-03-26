@extends('layouts.app')

@section('title', 'Edit Product')

@section('content')
    <div class="page-header">
        <div>
            <h2>Edit Product</h2>
            <p>Update the details for <strong>{{ $product->name }}</strong></p>
        </div>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">← Back</a>
    </div>

    <div class="card" style="max-width: 620px;">
        <div class="card-title">Product Information</div>
        <div class="card-body">
            <form action="{{ route('products.update', $product) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name', $product->name) }}" required autofocus>
                    @error('name') <small style="color:#dc2626; font-size:11px; margin-top:4px; display:block;">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label>SKU <span style="color:#94a3b8; font-weight:400;">(Optional)</span></label>
                    <input type="text" name="sku" class="form-control"
                           value="{{ old('sku', $product->sku) }}" placeholder="e.g. EC-3M-001">
                    @error('sku') <small style="color:#dc2626; font-size:11px; margin-top:4px; display:block;">{{ $message }}</small> @enderror
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div class="form-group">
                        <label>Purchase Price (₱) *</label>
                        <input type="number" name="purchase_price" id="purchase_price"
                               class="form-control"
                               value="{{ old('purchase_price', $product->purchase_price) }}"
                               step="0.01" min="0" required>
                        @error('purchase_price') <small style="color:#dc2626; font-size:11px; margin-top:4px; display:block;">{{ $message }}</small> @enderror
                    </div>

                    <div class="form-group">
                        <label>Selling Price (₱) *</label>
                        <input type="number" name="selling_price" id="selling_price"
                               class="form-control"
                               value="{{ old('selling_price', $product->selling_price) }}"
                               step="0.01" min="0" required>
                        @error('selling_price') <small style="color:#dc2626; font-size:11px; margin-top:4px; display:block;">{{ $message }}</small> @enderror
                    </div>
                </div>

                {{-- Live Profit Preview --}}
                <div id="profit-preview" style="background:#f8fafc; border:1px solid #e2e8f0;
                    border-radius:6px; padding:10px 14px; margin-bottom:14px;">
                    <div style="display:flex; gap:20px; align-items:center;">
                        <div>
                            <div style="font-size:10.5px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Profit / Unit</div>
                            <div id="profit-amount" style="font-size:15px; font-weight:700; margin-top:2px;"></div>
                        </div>
                        <div>
                            <div style="font-size:10.5px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Margin</div>
                            <div id="profit-margin" style="font-size:15px; font-weight:700; margin-top:2px;"></div>
                        </div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div class="form-group">
                        <label>Stock Quantity *</label>
                        <input type="number" name="stock_quantity" class="form-control"
                               value="{{ old('stock_quantity', $product->stock_quantity) }}"
                               min="0" required>
                        @error('stock_quantity') <small style="color:#dc2626; font-size:11px; margin-top:4px; display:block;">{{ $message }}</small> @enderror
                    </div>

                    <div class="form-group">
                        <label>Minimum Stock *</label>
                        <input type="number" name="minimum_stock" class="form-control"
                               value="{{ old('minimum_stock', $product->minimum_stock) }}"
                               min="0" required>
                        @error('minimum_stock') <small style="color:#dc2626; font-size:11px; margin-top:4px; display:block;">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div style="display:flex; gap:8px; margin-top:6px;">
                    <button type="submit" class="btn btn-primary">Update Product</button>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const purchaseInput = document.getElementById('purchase_price');
        const sellingInput  = document.getElementById('selling_price');
        const profitAmt     = document.getElementById('profit-amount');
        const profitMargin  = document.getElementById('profit-margin');

        function updateProfit() {
            const purchase = parseFloat(purchaseInput.value) || 0;
            const selling  = parseFloat(sellingInput.value)  || 0;
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
    </script>
@endsection