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

    <div class="card" style="max-width: 620px;">
        <div class="card-title">Product Information</div>
        <div class="card-body">
            <form action="{{ route('products.store') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name') }}" placeholder="e.g. Extension Cord 3m" required autofocus>
                    @error('name') <small style="color:#dc2626; font-size:11px; margin-top:4px; display:block;">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label>SKU <span style="color:#94a3b8; font-weight:400;">(Optional)</span></label>
                    <input type="text" name="sku" class="form-control"
                           value="{{ old('sku') }}" placeholder="e.g. EC-3M-001">
                    @error('sku') <small style="color:#dc2626; font-size:11px; margin-top:4px; display:block;">{{ $message }}</small> @enderror
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div class="form-group">
                        <label>Purchase Price (₱) *</label>
                        <input type="number" name="purchase_price" id="purchase_price"
                               class="form-control" value="{{ old('purchase_price') }}"
                               placeholder="0.00" step="0.01" min="0" required>
                        @error('purchase_price') <small style="color:#dc2626; font-size:11px; margin-top:4px; display:block;">{{ $message }}</small> @enderror
                    </div>

                    <div class="form-group">
                        <label>Selling Price (₱) *</label>
                        <input type="number" name="selling_price" id="selling_price"
                               class="form-control" value="{{ old('selling_price') }}"
                               placeholder="0.00" step="0.01" min="0" required>
                        @error('selling_price') <small style="color:#dc2626; font-size:11px; margin-top:4px; display:block;">{{ $message }}</small> @enderror
                    </div>
                </div>

                {{-- Live Profit Preview --}}
                <div id="profit-preview" style="background:#f8fafc; border:1px solid #e2e8f0;
                    border-radius:6px; padding:10px 14px; margin-bottom:14px; display:none;">
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
                               value="{{ old('stock_quantity', 0) }}" min="0" required>
                        @error('stock_quantity') <small style="color:#dc2626; font-size:11px; margin-top:4px; display:block;">{{ $message }}</small> @enderror
                    </div>

                    <div class="form-group">
                        <label>Minimum Stock *</label>
                        <input type="number" name="minimum_stock" class="form-control"
                               value="{{ old('minimum_stock', 0) }}" min="0" required>
                        @error('minimum_stock') <small style="color:#dc2626; font-size:11px; margin-top:4px; display:block;">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div style="display:flex; gap:8px; margin-top:6px;">
                    <button type="submit" class="btn btn-primary">Save Product</button>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const purchaseInput = document.getElementById('purchase_price');
        const sellingInput  = document.getElementById('selling_price');
        const preview       = document.getElementById('profit-preview');
        const profitAmt     = document.getElementById('profit-amount');
        const profitMargin  = document.getElementById('profit-margin');

        function updateProfit() {
            const purchase = parseFloat(purchaseInput.value) || 0;
            const selling  = parseFloat(sellingInput.value)  || 0;
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
    </script>
@endsection