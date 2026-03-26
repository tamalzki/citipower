@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php($role = auth()->user()?->role ?? 'owner')

<div class="page-header">
    <div>
        <h2>Business Overview</h2>
        <p>Quick snapshot of inventory, sales, and expenses</p>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        @if(in_array($role, ['owner', 'cashier']))
            <a href="{{ route('sales.create') }}" class="btn btn-primary">+ New Sale</a>
            <a href="{{ route('expenses.create') }}" class="btn btn-warning">+ Add Expense</a>
        @endif
        @if(in_array($role, ['owner', 'inventory']))
            <a href="{{ route('products.create') }}" class="btn btn-secondary">+ Add Product</a>
        @endif
    </div>
</div>

{{-- ── KPI row 1 ── --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-box icon-blue">📦</div>
        <div class="stat-info">
            <div class="stat-number">{{ number_format($totalProducts) }}</div>
            <div class="stat-label">Total Products</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-box icon-red">⚠️</div>
        <div class="stat-info">
            <div class="stat-number">{{ number_format($lowStockCount) }}</div>
            <div class="stat-label">Low Stock Items</div>
        </div>
    </div>
    @if(in_array($role, ['owner', 'cashier']))
    <div class="stat-card">
        <div class="stat-icon-box icon-green">💰</div>
        <div class="stat-info">
            <div class="stat-number">₱{{ number_format($todaySales, 2) }}</div>
            <div class="stat-label">Today's Sales</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-box icon-orange">💸</div>
        <div class="stat-info">
            <div class="stat-number">₱{{ number_format($todayExpenses, 2) }}</div>
            <div class="stat-label">Today's Expenses</div>
        </div>
    </div>
    @else
    <div class="stat-card">
        <div class="stat-icon-box icon-blue">🧮</div>
        <div class="stat-info">
            <div class="stat-number">{{ number_format($totalStockUnits) }}</div>
            <div class="stat-label">Total Stock Units</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-box icon-red">⛔</div>
        <div class="stat-info">
            <div class="stat-number">{{ number_format($outOfStockCount) }}</div>
            <div class="stat-label">Out of Stock</div>
        </div>
    </div>
    @endif
</div>

{{-- ── KPI row 2 ── --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-box icon-blue">🧮</div>
        <div class="stat-info">
            <div class="stat-number">{{ number_format($totalStockUnits) }}</div>
            <div class="stat-label">Total Stock Units</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-box icon-red">⛔</div>
        <div class="stat-info">
            <div class="stat-number">{{ number_format($outOfStockCount) }}</div>
            <div class="stat-label">Out of Stock</div>
        </div>
    </div>
    @if(in_array($role, ['owner', 'cashier']))
    <div class="stat-card">
        <div class="stat-icon-box icon-green">📈</div>
        <div class="stat-info">
            <div class="stat-number">₱{{ number_format($monthSales, 2) }}</div>
            <div class="stat-label">Sales This Month</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-box icon-orange">📉</div>
        <div class="stat-info">
            <div class="stat-number">₱{{ number_format($monthExpenses, 2) }}</div>
            <div class="stat-label">Expenses This Month</div>
        </div>
    </div>
    @endif
</div>

{{-- ── Quick Navigation ── --}}
<div class="card">
    <div class="card-title">Quick Navigation</div>
    <div class="card-body" style="display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap:12px;">
        @if(in_array($role, ['owner', 'cashier']))
            <a href="{{ route('sales.index') }}"    class="btn btn-secondary" style="justify-content:flex-start;">🧾 Sales</a>
            <a href="{{ route('expenses.index') }}" class="btn btn-secondary" style="justify-content:flex-start;">💸 Expenses</a>
        @endif
        @if(in_array($role, ['owner', 'inventory']))
            <a href="{{ route('products.index') }}"         class="btn btn-secondary" style="justify-content:flex-start;">📦 Products</a>
            <a href="{{ route('purchase-orders.index') }}"  class="btn btn-secondary" style="justify-content:flex-start;">📋 Purchase Orders</a>
            <a href="{{ route('inventory-logs.index') }}"   class="btn btn-secondary" style="justify-content:flex-start;">📚 Inventory Logs</a>
        @endif
        @if($role === 'owner')
            <a href="{{ route('reports.sales') }}"          class="btn btn-secondary" style="justify-content:flex-start;">📈 Sales Report</a>
            <a href="{{ route('reports.inventory') }}"      class="btn btn-secondary" style="justify-content:flex-start;">📊 Inventory Report</a>
            <a href="{{ route('reports.profit-loss') }}"    class="btn btn-secondary" style="justify-content:flex-start;">💹 Profit &amp; Loss</a>
        @endif
        <a href="{{ route('profile.edit') }}" class="btn btn-secondary" style="justify-content:flex-start;">👤 My Profile</a>
    </div>
</div>

{{-- ── Recent tables (owner/cashier only) ── --}}
@if(in_array($role, ['owner', 'cashier']))
<div class="stats-grid" style="grid-template-columns: 1fr 1fr;">
    <div class="card" style="margin-bottom:0;">
        <div class="card-title">Recent Sales</div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Sale #</th>
                        <th>Date</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentSales as $sale)
                        <tr style="cursor:pointer;" onclick="window.location='{{ route('sales.show', $sale) }}'">
                            <td style="font-weight:600; color:#2563eb;">#{{ $sale->id }}</td>
                            <td>{{ $sale->created_at->format('M d, Y h:i A') }}</td>
                            <td style="font-weight:600;">₱{{ number_format($sale->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="color:#94a3b8;">No recent sales found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-bottom:0;">
        <div class="card-title">Recent Expenses</div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentExpenses as $expense)
                        <tr>
                            <td>{{ $expense->expense_date->format('M d, Y') }}</td>
                            <td>{{ $expense->category?->name ?? '—' }}</td>
                            <td style="font-weight:600;">₱{{ number_format($expense->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="color:#94a3b8;">No recent expenses found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@endsection
