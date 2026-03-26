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
                       placeholder="Search by name, SKU, brand, model, category..."
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
                        <th>#</th>
                        <th>Name</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th>Model</th>
                        <th>SKU</th>
                        <th>Purchase Price</th>
                        <th>Selling Price</th>
                        <th>Margin</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td style="color:#94a3b8; font-size:12px;">{{ $loop->iteration + ($products->currentPage() - 1) * $products->perPage() }}</td>
                        <td>
                            <a href="{{ route('products.show', $product) }}"
                               style="font-weight:600; color:#2563eb; text-decoration:none;">
                                {{ $product->name }}
                            </a>
                        </td>
                        <td>{{ $product->brand ?: '—' }}</td>
                        <td>{{ $product->category ?: '—' }}</td>
                        <td>{{ $product->model ?: '—' }}</td>
                        <td>
                            @if($product->sku)
                                <span class="badge badge-gray">{{ $product->sku }}</span>
                            @else
                                <span style="color:#cbd5e1;">—</span>
                            @endif
                        </td>
                        <td>₱{{ number_format($product->purchase_price, 2) }}</td>
                        <td style="font-weight:600;">₱{{ number_format($product->selling_price, 2) }}</td>
                        <td>
                            <span style="font-weight:600; color:{{ $product->profitMargin() >= 0 ? '#16a34a' : '#dc2626' }}">
                                {{ number_format($product->profitMargin(), 1) }}%
                            </span>
                        </td>
                        <td style="font-weight:700; font-size:15px;">{{ $product->stock_quantity }}</td>
                        <td>
                            @if($product->stock_quantity <= 0)
                                <span class="badge badge-danger">Out of Stock</span>
                            @elseif($product->isLowStock())
                                <span class="badge badge-warning">Low Stock</span>
                            @else
                                <span class="badge badge-success">OK</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex; gap:5px; flex-wrap:wrap;">
                                <a href="{{ route('products.show', $product) }}" class="btn btn-secondary btn-sm">View</a>
                                <a href="{{ route('inventory.add-stock', $product) }}" class="btn btn-success btn-sm">+ Stock</a>
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-secondary btn-sm">Edit</a>
                                <form action="{{ route('products.destroy', $product) }}" method="POST"
                                      style="display:inline" onsubmit="return confirm('Delete this product?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12">
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
