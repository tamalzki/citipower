@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <div class="page-header">
        <div>
            <h2>{{ $product->name }}</h2>
            <p>{{ implode(' · ', array_filter([$product->brand, $product->category, $product->model])) ?: 'Product Detail' }}</p>
        </div>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('inventory.add-stock', $product) }}" class="btn btn-success">+ Add Stock</a>
            <a href="{{ route('products.edit', $product) }}" class="btn btn-secondary">Edit</a>
            <a href="{{ route('products.index') }}" class="btn btn-secondary">← Back</a>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; align-items:start;">

        {{-- Product Info --}}
        <div class="card">
            <div class="card-title">Product Information</div>
            <div class="card-body">
                <table style="width:100%; border-collapse:collapse;">
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:8px 0; color:#64748b; width:140px; font-size:13px;">Name</td>
                        <td style="padding:8px 0; font-weight:600;">{{ $product->name }}</td>
                    </tr>
                    @if($product->brand)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:8px 0; color:#64748b; font-size:13px;">Brand</td>
                        <td style="padding:8px 0;">{{ $product->brand }}</td>
                    </tr>
                    @endif
                    @if($product->category)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:8px 0; color:#64748b; font-size:13px;">Category</td>
                        <td style="padding:8px 0;">{{ $product->category }}</td>
                    </tr>
                    @endif
                    @if($product->model)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:8px 0; color:#64748b; font-size:13px;">Model</td>
                        <td style="padding:8px 0;">{{ $product->model }}</td>
                    </tr>
                    @endif
                    @if($product->sku)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:8px 0; color:#64748b; font-size:13px;">SKU</td>
                        <td style="padding:8px 0;"><span class="badge badge-gray">{{ $product->sku }}</span></td>
                    </tr>
                    @endif
                    @if($product->description)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:8px 0; color:#64748b; font-size:13px; vertical-align:top;">Description</td>
                        <td style="padding:8px 0; font-size:13px; line-height:1.6;">{{ $product->description }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        {{-- Pricing & Stock --}}
        <div>
            <div class="stats-grid" style="grid-template-columns:1fr 1fr; margin-bottom:0;">
                <div class="stat-card">
                    <div class="stat-icon-box icon-blue">📦</div>
                    <div class="stat-info">
                        <div class="stat-number" style="color:{{ $product->stock_quantity <= 0 ? '#dc2626' : ($product->isLowStock() ? '#d97706' : '#16a34a') }};">
                            {{ $product->stock_quantity }}
                        </div>
                        <div class="stat-label">Stock Qty</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-box icon-orange">⚠️</div>
                    <div class="stat-info">
                        <div class="stat-number">{{ $product->minimum_stock }}</div>
                        <div class="stat-label">Min Stock</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-box icon-green">💰</div>
                    <div class="stat-info">
                        <div class="stat-number">₱{{ number_format($product->selling_price, 2) }}</div>
                        <div class="stat-label">Selling Price</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-box icon-blue">📊</div>
                    <div class="stat-info">
                        <div class="stat-number" style="color:{{ $product->profitMargin() >= 0 ? '#16a34a' : '#dc2626' }};">
                            {{ number_format($product->profitMargin(), 1) }}%
                        </div>
                        <div class="stat-label">Margin</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Supplier Price Comparison --}}
    <div class="card">
        <div class="card-title" style="display:flex; justify-content:space-between; align-items:center;">
            Supplier Price Comparison
            <a href="{{ route('products.edit', $product) }}" class="btn btn-secondary btn-sm">Manage Suppliers</a>
        </div>
        @php($cheapest = $product->cheapestSupplier())
        @if($product->suppliers->isEmpty())
            <div class="card-body">
                <p style="color:#94a3b8; font-size:13px;">No suppliers linked yet.
                    <a href="{{ route('products.edit', $product) }}">Add suppliers</a> to compare prices.
                </p>
            </div>
        @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Contact</th>
                        <th>Cost Price</th>
                        <th>vs Selling Price</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($product->suppliers->sortBy('pivot.cost_price') as $supplier)
                    @php($isCheapest = $cheapest && $supplier->id === $cheapest->id)
                    <tr style="{{ $isCheapest ? 'background:#f0fdf4;' : '' }}">
                        <td>
                            <span style="font-weight:600;">{{ $supplier->name }}</span>
                            @if($isCheapest)
                                <span class="badge badge-success" style="margin-left:6px;">Cheapest</span>
                            @endif
                        </td>
                        <td style="color:#64748b; font-size:13px;">{{ $supplier->contact_person ?: '—' }}</td>
                        <td style="font-weight:700; font-size:15px; color:{{ $isCheapest ? '#16a34a' : '#0f172a' }};">
                            ₱{{ number_format($supplier->pivot->cost_price, 2) }}
                        </td>
                        <td>
                            @php($diff = $product->selling_price - $supplier->pivot->cost_price)
                            <span style="color:{{ $diff >= 0 ? '#16a34a' : '#dc2626' }}; font-weight:600;">
                                {{ $diff >= 0 ? '+' : '' }}₱{{ number_format($diff, 2) }} margin
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('suppliers.index') }}" class="btn btn-secondary btn-sm">View Supplier</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
@endsection
