@extends('layouts.app')

@section('title', 'Adjust Stock')

@section('content')
    <div class="page-header">
        <div>
            <h2>Adjust Stock</h2>
            <p>Correct stock based on physical count</p>
        </div>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">← Back</a>
    </div>

    <div class="card" style="max-width: 500px;">
        <div class="card-title">⚙️ Stock Adjustment</div>
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
                    <span style="font-size:12px; color:#64748b;">System Stock:</span>
                    <span style="font-size:14px; font-weight:700; color:#0f172a;">
                        {{ $product->stock_quantity }}
                    </span>
                </div>
            </div>

            <form action="{{ route('inventory.adjust-stock', $product) }}" method="POST" id="adjust-stock-form">
                @csrf

                <div class="form-group">
                    <label>Actual Stock Count *</label>
                    <input type="number" name="new_quantity" id="new_quantity"
                           class="form-control" placeholder="Enter actual physical count"
                           min="0" required autofocus>
                    @error('new_quantity')
                        <small style="color:#dc2626; font-size:11px; margin-top:4px; display:block;">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Live Difference Preview --}}
                <div id="adjust-preview" style="border-radius:6px; padding:10px 14px;
                    margin-bottom:14px; display:none;">
                    <span style="font-size:10.5px; font-weight:600; color:#64748b;
                                 text-transform:uppercase; letter-spacing:0.5px;">Difference</span>
                    <div id="diff-value" style="font-size:20px; font-weight:700; margin-top:2px;"></div>
                </div>

                <div class="form-group">
                    <label>Reason *</label>
                    <input type="text" name="reason" class="form-control"
                           placeholder="e.g. Physical count correction" required>
                    @error('reason')
                        <small style="color:#dc2626; font-size:11px; margin-top:4px; display:block;">{{ $message }}</small>
                    @enderror
                </div>

                <div style="display:flex; gap:8px; margin-top:6px;">
                    <button type="submit" class="btn btn-warning">Adjust Stock</button>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const systemStock = {{ $product->stock_quantity }};
        const newQtyInput = document.getElementById('new_quantity');
        const preview     = document.getElementById('adjust-preview');
        const diffVal     = document.getElementById('diff-value');

        newQtyInput.addEventListener('input', function () {
            const newQty = parseInt(this.value);
            if (!isNaN(newQty)) {
                const diff = newQty - systemStock;
                diffVal.textContent   = (diff >= 0 ? '+' : '') + diff + ' units';
                diffVal.style.color   = diff === 0 ? '#64748b' : diff > 0 ? '#16a34a' : '#dc2626';
                preview.style.background = diff === 0 ? '#f8fafc' : diff > 0 ? '#f0fdf4' : '#fef2f2';
                preview.style.border  = '1px solid ' + (diff === 0 ? '#e2e8f0' : diff > 0 ? '#bbf7d0' : '#fecaca');
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        });

        (function () {
            const f = document.getElementById('adjust-stock-form');
            if (!f || !window.CitiOffline?.queueInventoryAdjustStock) return;
            f.addEventListener('submit', async function (e) {
                if (navigator.onLine) return;
                e.preventDefault();
                const newQty = parseInt(f.querySelector('[name="new_quantity"]')?.value || '', 10);
                if (isNaN(newQty) || newQty < 0) { alert('Enter actual stock count.'); return; }
                try {
                    const ref = await window.CitiOffline.queueInventoryAdjustStock({
                        product_id: {{ (int) $product->id }},
                        new_quantity: newQty,
                        reason: f.querySelector('[name="reason"]')?.value || '',
                    });
                    alert('Offline: Stock adjust queued. Ref: ' + ref.slice(0, 8));
                    window.location.href = '{{ route('products.index') }}';
                } catch (err) { alert((err && err.message) || 'Queue failed.'); }
            });
        })();
    </script>
@endsection