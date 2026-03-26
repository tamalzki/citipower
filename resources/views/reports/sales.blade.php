@extends('layouts.app')

@section('title', 'Sales Report')

@section('content')
    <div class="page-header">
        <div>
            <a href="{{ route('reports.hub') }}"
               style="font-size:13px; font-weight:600; color:#64748b; text-decoration:none; display:inline-flex; align-items:center; gap:4px; margin-bottom:4px;">
                ← Back to Reports
            </a>
            <h2>Sales Report</h2>
            <p>Revenue, profit, and product performance by date range</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.sales') }}" style="display:grid; grid-template-columns: 1fr 1fr auto; gap:12px; align-items:end;">
                <div class="form-group" style="margin:0;">
                    <label for="date_from">Date From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ $dateFrom }}" class="form-control">
                </div>
                <div class="form-group" style="margin:0;">
                    <label for="date_to">Date To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ $dateTo }}" class="form-control">
                </div>
                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('reports.sales') }}" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon-box icon-blue">🧾</div>
            <div>
                <div class="stat-number">{{ number_format($summary['sales_count']) }}</div>
                <div class="stat-label">Total Sales</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box icon-green">💵</div>
            <div>
                <div class="stat-number">₱{{ number_format($summary['net_sales'], 2) }}</div>
                <div class="stat-label">Net Sales</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box icon-orange">📊</div>
            <div>
                <div class="stat-number">₱{{ number_format($summary['total_cost'], 2) }}</div>
                <div class="stat-label">Cost of Goods Sold</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box icon-blue">📈</div>
            <div>
                <div class="stat-number">₱{{ number_format($summary['gross_profit'], 2) }}</div>
                <div class="stat-label">Gross Profit</div>
            </div>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr);">
        <div class="stat-card">
            <div class="stat-icon-box icon-orange">🏷️</div>
            <div>
                <div class="stat-number">₱{{ number_format($summary['total_discounts'], 2) }}</div>
                <div class="stat-label">Total Discounts</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box icon-blue">🛍️</div>
            <div>
                <div class="stat-number">₱{{ number_format($summary['gross_sales'], 2) }}</div>
                <div class="stat-label">Gross Before Discount</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Product Performance</div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Units Sold</th>
                        <th>Revenue</th>
                        <th>Cost</th>
                        <th>Profit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productPerformance as $item)
                        @php
                            $profit = $item->revenue - $item->cost;
                        @endphp
                        <tr>
                            <td style="font-weight:600; color:#0f172a;">{{ $item->product?->name ?? 'Deleted Product' }}</td>
                            <td>{{ number_format($item->units_sold) }}</td>
                            <td>₱{{ number_format($item->revenue, 2) }}</td>
                            <td>₱{{ number_format($item->cost, 2) }}</td>
                            <td>
                                <span class="badge {{ $profit >= 0 ? 'badge-success' : 'badge-danger' }}">
                                    ₱{{ number_format($profit, 2) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <div class="empty-icon">📭</div>
                                    <p>No sales data found in this period.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $productPerformance->links() }}</div>

    <div class="card">
        <div class="card-title">Recent Sales In Selected Period</div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Sale #</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentSales as $sale)
                        <tr>
                            <td>{{ $sale->id }}</td>
                            <td>{{ $sale->created_at->format('M d, Y h:i A') }}</td>
                            <td>{{ $sale->items->count() }}</td>
                            <td>₱{{ number_format($sale->total_amount, 2) }}</td>
                            <td>{{ $sale->note ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <div class="empty-icon">📭</div>
                                    <p>No recent sales available for this period.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
