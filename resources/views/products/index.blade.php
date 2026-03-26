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

    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>SKU</th>
                        <th>Purchase Price</th>
                        <th>Selling Price</th>
                        <th>Profit</th>
                        <th>Margin</th>
                        <th>Stock</th>
                        <th>Min Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td style="color:#94a3b8; font-size:12px;">{{ $loop->iteration }}</td>
                        <td>
                            <span style="font-weight:600; color:#0f172a;">{{ $product->name }}</span>
                        </td>
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
                            <span style="font-weight:600; color:{{ $product->profitAmount() >= 0 ? '#16a34a' : '#dc2626' }}">
                                ₱{{ number_format($product->profitAmount(), 2) }}
                            </span>
                        </td>
                        <td>
                            <span style="font-weight:600; color:{{ $product->profitMargin() >= 0 ? '#16a34a' : '#dc2626' }}">
                                {{ number_format($product->profitMargin(), 1) }}%
                            </span>
                        </td>
                        <td style="font-weight:700; font-size:15px;">{{ $product->stock_quantity }}</td>
                        <td style="color:#94a3b8;">{{ $product->minimum_stock }}</td>
                        <td>
                            @if($product->isLowStock())
                                <span class="badge badge-danger">⚠ Low Stock</span>
                            @else
                                <span class="badge badge-success">✓ OK</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                <a href="{{ route('inventory.add-stock', $product) }}" class="btn btn-success btn-sm">+ Stock</a>
                                <a href="{{ route('inventory.adjust-stock', $product) }}" class="btn btn-warning btn-sm">Adjust</a>
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-secondary btn-sm">Edit</a>
                                <form action="{{ route('products.destroy', $product) }}" method="POST"
                                      style="display:inline"
                                      onsubmit="return confirm('Delete this product?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11">
                            <div class="empty-state">
                                <div class="empty-icon">📦</div>
                                <p>No products yet.</p>
                                <a href="{{ route('products.create') }}" class="btn btn-primary">Add Your First Product</a>
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