@extends('layouts.app')

@section('title', 'Inventory Report')

@section('content')
    <div class="page-header">
        <div>
            <h2>Inventory Report</h2>
            <p>Current stock levels, value, and reorder visibility</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.inventory') }}" style="display:grid; grid-template-columns: 1.2fr 1fr auto; gap:12px; align-items:end;">
                <div class="form-group" style="margin:0;">
                    <label for="search">Search Product / SKU</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ $search }}"
                        class="form-control"
                        placeholder="Type product name or SKU">
                </div>
                <div class="form-group" style="margin:0;">
                    <label for="status">Stock Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All</option>
                        <option value="low" {{ $status === 'low' ? 'selected' : '' }}>Low Stock</option>
                        <option value="out" {{ $status === 'out' ? 'selected' : '' }}>Out of Stock</option>
                    </select>
                </div>
                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('reports.inventory') }}" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon-box icon-blue">📦</div>
            <div>
                <div class="stat-number">{{ number_format($summary['total_products']) }}</div>
                <div class="stat-label">Total Products</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box icon-green">🧮</div>
            <div>
                <div class="stat-number">{{ number_format($summary['total_units']) }}</div>
                <div class="stat-label">Total Units In Stock</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box icon-orange">⚠️</div>
            <div>
                <div class="stat-number">{{ number_format($summary['low_stock_count']) }}</div>
                <div class="stat-label">Low Stock Products</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box icon-red">⛔</div>
            <div>
                <div class="stat-number">{{ number_format($summary['out_of_stock_count']) }}</div>
                <div class="stat-label">Out of Stock</div>
            </div>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr);">
        <div class="stat-card">
            <div class="stat-icon-box icon-blue">💰</div>
            <div>
                <div class="stat-number">₱{{ number_format($summary['inventory_cost_value'], 2) }}</div>
                <div class="stat-label">Inventory Cost Value</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box icon-green">🏷️</div>
            <div>
                <div class="stat-number">₱{{ number_format($summary['inventory_retail_value'], 2) }}</div>
                <div class="stat-label">Inventory Retail Value</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Product-Level Inventory Details</div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Stock</th>
                        <th>Min Stock</th>
                        <th>Status</th>
                        <th>Cost Value</th>
                        <th>Retail Value</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        @php
                            $costValue = $product->stock_quantity * $product->purchase_price;
                            $retailValue = $product->stock_quantity * $product->selling_price;
                        @endphp
                        <tr>
                            <td style="font-weight:600; color:#0f172a;">{{ $product->name }}</td>
                            <td>{{ $product->sku }}</td>
                            <td>{{ number_format($product->stock_quantity) }}</td>
                            <td>{{ number_format($product->minimum_stock) }}</td>
                            <td>
                                @if($product->stock_quantity <= 0)
                                    <span class="badge badge-danger">Out of Stock</span>
                                @elseif($product->stock_quantity <= $product->minimum_stock)
                                    <span class="badge badge-warning">Low Stock</span>
                                @else
                                    <span class="badge badge-success">Healthy</span>
                                @endif
                            </td>
                            <td>₱{{ number_format($costValue, 2) }}</td>
                            <td>₱{{ number_format($retailValue, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-icon">📭</div>
                                    <p>No products match the selected filters.</p>
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
