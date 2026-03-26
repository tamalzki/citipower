@extends('layouts.app')

@section('title', 'Profit & Loss')

@section('content')
    <div class="page-header">
        <div>
            <h2>Profit & Loss (Accrual)</h2>
            <p>Financial performance based on recognized sales, COGS, and operating expenses</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.profit-loss') }}" style="display:grid; grid-template-columns:1fr 1fr auto; gap:12px; align-items:end;">
                <div class="form-group" style="margin:0;">
                    <label for="date_from">Date From</label>
                    <input id="date_from" type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                </div>
                <div class="form-group" style="margin:0;">
                    <label for="date_to">Date To</label>
                    <input id="date_to" type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                </div>
                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('reports.profit-loss') }}" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon-box icon-blue">💵</div>
            <div>
                <div class="stat-number">₱{{ number_format($summary['gross_sales'], 2) }}</div>
                <div class="stat-label">Gross Sales</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box icon-orange">🏷️</div>
            <div>
                <div class="stat-number">₱{{ number_format($summary['discounts'], 2) }}</div>
                <div class="stat-label">Discounts</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box icon-green">🧾</div>
            <div>
                <div class="stat-number">₱{{ number_format($summary['net_sales'], 2) }}</div>
                <div class="stat-label">Net Sales</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box icon-orange">📦</div>
            <div>
                <div class="stat-number">₱{{ number_format($summary['cogs'], 2) }}</div>
                <div class="stat-label">Cost of Goods Sold</div>
            </div>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr);">
        <div class="stat-card">
            <div class="stat-icon-box icon-blue">📈</div>
            <div>
                <div class="stat-number">₱{{ number_format($summary['gross_profit'], 2) }}</div>
                <div class="stat-label">Gross Profit</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box icon-red">💸</div>
            <div>
                <div class="stat-number">₱{{ number_format($summary['operating_expenses'], 2) }}</div>
                <div class="stat-label">Operating Expenses</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Net Profit</div>
        <div class="card-body">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div style="font-size:13px; color:#64748b;">Net Sales − COGS − Operating Expenses</div>
                <div style="font-size:26px; font-weight:700; color:{{ $summary['net_profit'] >= 0 ? '#16a34a' : '#dc2626' }};">
                    ₱{{ number_format($summary['net_profit'], 2) }}
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Operating Expense Breakdown</div>
        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>Category</th>
                    <th>Total Expense</th>
                </tr>
                </thead>
                <tbody>
                @forelse($expenseBreakdown as $item)
                    <tr>
                        <td>{{ $item->category?->name ?? 'Uncategorized' }}</td>
                        <td style="font-weight:600;">₱{{ number_format($item->total_amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" style="color:#94a3b8;">No expenses in selected period.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Recent Sales Included In P&L</div>
        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>Sale #</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Net Sale</th>
                </tr>
                </thead>
                <tbody>
                @forelse($salesSummaryRows as $sale)
                    <tr>
                        <td>{{ $sale->id }}</td>
                        <td>{{ $sale->created_at->format('M d, Y h:i A') }}</td>
                        <td>{{ $sale->items->sum('quantity') }}</td>
                        <td style="font-weight:600;">₱{{ number_format($sale->total_amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="color:#94a3b8;">No sales in selected period.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Top Product Profit Contributors</div>
        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>Product</th>
                    <th>Units Sold</th>
                    <th>Revenue</th>
                    <th>COGS</th>
                    <th>Gross Profit</th>
                </tr>
                </thead>
                <tbody>
                @forelse($productProfitability as $item)
                    <tr>
                        <td>{{ $item->product?->name ?? 'Deleted Product' }}</td>
                        <td>{{ number_format($item->units_sold) }}</td>
                        <td>₱{{ number_format($item->gross_revenue, 2) }}</td>
                        <td>₱{{ number_format($item->cogs, 2) }}</td>
                        <td style="font-weight:600; color:{{ $item->gross_profit >= 0 ? '#16a34a' : '#dc2626' }};">
                            ₱{{ number_format($item->gross_profit, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="color:#94a3b8;">No product profit data in selected period.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
