@extends('layouts.app')

@section('title', 'Products')

@section('content')
    <div class="page-header">
        <div>
            <h2>Products</h2>
            <p>Manage your product inventory</p>
        </div>
        <a href="{{ route('products.create') }}" class="btn btn-primary">+ Add Product</a>
    </div>

    {{-- Search --}}
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('products.index') }}"
                  style="display:flex; gap:10px; align-items:center;">
                <input type="text" name="search" class="form-control"
                       placeholder="Search by product, supplier, or supplier cost..."
                       value="{{ $search }}" style="flex:1; max-width:420px;">
                <button type="submit" class="btn btn-primary">Search</button>
                @if($search)
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">Clear</a>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Name / Model</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th>SKU</th>
                        <th>Suppliers & Cost</th>
                        <th>Purchase Price</th>
                        <th>Selling Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td>
                            <a href="{{ route('products.show', $product) }}"
                               style="font-weight:600; color:#2563eb; text-decoration:none;">
                                {{ $product->name }}
                            </a>
                        </td>
                        <td>{{ $product->brand ?: '—' }}</td>
                        <td>{{ $product->category ?: '—' }}</td>
                        <td>
                            @if($product->sku)
                                <span class="badge badge-gray">{{ $product->sku }}</span>
                            @else
                                <span style="color:#cbd5e1;">—</span>
                            @endif
                        </td>
                        <td>
                            @if($product->suppliers->isEmpty())
                                <span style="color:#94a3b8;">—</span>
                            @else
                                @php
                                    $sortedSuppliers = $product->suppliers->sortBy('pivot.cost_price')->values();
                                    $topTwoSuppliers = $sortedSuppliers->take(2);
                                    $cheapestSupplierId = $sortedSuppliers->first()?->id;
                                @endphp
                                <div style="display:flex; flex-direction:column; gap:2px; min-width:180px;">
                                    @foreach($topTwoSuppliers as $supplier)
                                        <div style="font-size:11.5px; line-height:1.25;">
                                            <span style="font-weight:600;">{{ $supplier->name }}</span>
                                            @if($supplier->id === $cheapestSupplierId)
                                                <span style="font-size:10px; font-weight:700; color:#166534; background:#dcfce7; border:1px solid #bbf7d0; border-radius:999px; padding:0 5px; margin-left:4px;">
                                                    Cheapest
                                                </span>
                                            @endif
                                            <span style="color:#334155;">- ₱{{ number_format($supplier->pivot->cost_price, 2) }}</span>
                                        </div>
                                    @endforeach
                                    @if($sortedSuppliers->count() > 2)
                                        <span style="font-size:11px; color:#64748b;">+{{ $sortedSuppliers->count() - 2 }} more</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td style="white-space:nowrap;">₱{{ number_format($product->purchase_price, 2) }}</td>
                        <td style="font-weight:600; white-space:nowrap;">₱{{ number_format($product->selling_price, 2) }}</td>
                        <td>
                            @if($product->stock_quantity <= 0)
                                <span style="display:inline-block; padding:2px 8px; border-radius:999px; font-weight:700; color:#dc2626; background:#fef2f2; border:1px solid #fecaca;">
                                    {{ $product->stock_quantity }}
                                </span>
                            @elseif($product->isLowStock())
                                <span style="display:inline-block; padding:2px 8px; border-radius:999px; font-weight:700; color:#d97706; background:#fffbeb; border:1px solid #fde68a;">
                                    {{ $product->stock_quantity }}
                                </span>
                            @else
                                <span style="font-weight:700; font-size:15px; color:#16a34a;">{{ $product->stock_quantity }}</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex; gap:4px; flex-wrap:nowrap; white-space:nowrap;">
                                <a href="{{ route('inventory.add-stock', $product) }}" class="btn btn-success btn-sm">+ Stock</a>
                                <a href="{{ route('inventory.adjust-stock', $product) }}" class="btn btn-warning btn-sm">Adjust</a>
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-secondary btn-sm">Edit</a>
                                <form action="{{ route('products.destroy', $product) }}" method="POST"
                                      style="display:inline; margin:0;" onsubmit="return confirm('Delete this product?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10">
                            <div class="empty-state">
                                <div class="empty-icon">📦</div>
                                <p>{{ $search ? 'No products found matching "' . $search . '".' : 'No products yet.' }}</p>
                                @if(!$search)
                                    <a href="{{ route('products.create') }}" class="btn btn-primary">Add Your First Product</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $products->links() }}</div>
@endsection
