@extends('layouts.app')

@section('title', 'Add Stock')

@section('content')
    <div class="page-header">
        <div>
            <h2>Add Stock</h2>
            <p>Record incoming stock from supplier</p>
        </div>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">← Back</a>
    </div>

    <div class="card" style="max-width: 500px;">
        <div class="card-title">📦 Stock In</div>
        <div class="card-body">

            {{-- Product Info Block --}}
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px;
                        padding:12px 14px; margin-bottom:16px;">
                <div style="font-size:10.5px; font-weight:600; color:#64748b;
                            text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">
                    Product
                </div>
                <div style="font-size:15px; font-weight:700; color:#0f172a;">
                    {{ $product->name }}
                </div>
                @if($product->sku)
                    <div style="margin-top:2px;">
                        <span class="badge badge-gray">{{ $product->sku }}</span>
                    </div>
                @endif
                <div style="margin-top:8px; display:flex; align-items:center; gap:8px;">
                    <span style="font-size:12px; color:#64748b;">Current Stock:</span>
                    <span style="font-size:14px; font-weight:700;
                                 color:{{ $product->isLowStock() ? '#dc2626' : '#16a34a' }}">
                        {{ $product->stock_quantity }}
                    </span>
                    @if($product->isLowStock())
                        <span class="badge badge-danger">⚠ Low Stock</span>
                    @endif
                </div>
            </div>

            <form action="{{ route('inventory.add-stock', $product) }}" method="POST" id="add-stock-form">
                @csrf

                <div class="form-group">
                    <label>Quantity to Add *</label>
                    <input type="number" name="quantity_added" id="quantity_added"
                           class="form-control" placeholder="e.g. 10"
                           min="1" required autofocus>
                    @error('quantity_added')
                        <small style="color:#dc2626; font-size:11px; margin-top:4px; display:block;">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Live New Stock Preview --}}
                <div id="stock-preview" style="background:#f0fdf4; border:1px solid #bbf7d0;
                    border-radius:6px; padding:10px 14px; margin-bottom:14px; display:none;">
                    <span style="font-size:11px; color:#64748b; font-weight:600;
                                 text-transform:uppercase; letter-spacing:0.5px;">New Stock Will Be</span>
                    <div id="new-stock-value" style="font-size:20px; font-weight:700;
                                                      color:#16a34a; margin-top:2px;"></div>
                </div>

                <div class="form-group">
                    <label>Note <span style="color:#94a3b8; font-weight:400;">(Optional)</span></label>
                    <input type="text" name="note" class="form-control"
                           placeholder="e.g. Delivery from supplier">
                </div>

                <div style="display:flex; gap:8px; margin-top:6px;">
                    <button type="submit" class="btn btn-success">+ Add Stock</button>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const currentStock = {{ $product->stock_quantity }};
        const qtyInput     = document.getElementById('quantity_added');
        const preview      = document.getElementById('stock-preview');
        const newStockVal  = document.getElementById('new-stock-value');

        qtyInput.addEventListener('input', function () {
            const qty = parseInt(this.value) || 0;
            if (qty > 0) {
                newStockVal.textContent = currentStock + qty;
                preview.style.display  = 'block';
            } else {
                preview.style.display  = 'none';
            }
        });

        (function () {
            const f = document.getElementById('add-stock-form');
            if (!f || !window.CitiOffline?.queueInventoryAddStock) return;
            f.addEventListener('submit', async function (e) {
                if (navigator.onLine) return;
                e.preventDefault();
                const qty = parseInt(f.querySelector('[name="quantity_added"]')?.value || '0', 10);
                if (qty < 1) { alert('Enter a valid quantity.'); return; }
                try {
                    const ref = await window.CitiOffline.queueInventoryAddStock({
                        product_id: {{ (int) $product->id }},
                        quantity_added: qty,
                        note: f.querySelector('[name="note"]')?.value || '',
                    });
                    alert('Offline: Add stock queued. Ref: ' + ref.slice(0, 8));
                    window.location.href = '{{ route('products.index') }}';
                } catch (err) { alert((err && err.message) || 'Queue failed.'); }
            });
        })();
    </script>
@endsection