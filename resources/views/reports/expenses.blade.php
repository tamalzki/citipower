@extends('layouts.app')
@section('title', 'Expense Report')

@section('content')
<style>
.er-kpi-row {
    display: flex;
    gap: 0;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 6px rgba(15,23,42,.06);
    margin-bottom: 16px;
}
.er-kpi-cell {
    flex: 1;
    padding: 16px 22px;
    border-right: 1px solid #f1f5f9;
    min-width: 0;
}
.er-kpi-cell:last-child { border-right: none; }
.er-kpi-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #94a3b8; margin-bottom: 4px; }
.er-kpi-value { font-size: 24px; font-weight: 800; line-height: 1.15; white-space: nowrap; }

.er-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 6px rgba(15,23,42,.06);
    margin-bottom: 16px;
}
.er-card-title {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
}
.er-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.er-table thead th {
    background: #1e293b;
    color: #fff;
    padding: 10px 14px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    text-align: left;
    white-space: nowrap;
}
.er-table thead th.r { text-align: right; }
.er-table tbody td { padding: 10px 14px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.er-table tbody tr:hover { background: #f9fafb; }
.er-table tfoot td { background: #0f172a; color: #fff; font-weight: 700; padding: 10px 14px; }
.er-table tfoot td.r { text-align: right; }

.cat-bar-wrap { background: #f1f5f9; border-radius: 6px; height: 8px; flex: 1; overflow: hidden; }
.cat-bar { height: 100%; border-radius: 6px; background: #6366f1; }
</style>

{{-- Page header --}}
<div class="page-header">
    <div>
        <a href="{{ route('reports.hub') }}"
           style="font-size:13px; font-weight:600; color:#64748b; text-decoration:none; display:inline-flex; align-items:center; gap:4px; margin-bottom:4px;">
            ← Back to Reports
        </a>
        <h2>Expense Report</h2>
        <p>Overall expense summary by category and date range</p>
    </div>
</div>

{{-- Date Filter --}}
<div class="er-card" style="margin-bottom:16px;">
    <div style="padding:14px 16px;">
        <form method="GET" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
            <div>
                <label style="display:block; font-size:11px; font-weight:700; color:#64748b; margin-bottom:4px; text-transform:uppercase; letter-spacing:.4px;">From</label>
                <input type="date" name="date_from" class="form-control"
                       value="{{ $dateFrom }}" style="width:160px;">
            </div>
            <div>
                <label style="display:block; font-size:11px; font-weight:700; color:#64748b; margin-bottom:4px; text-transform:uppercase; letter-spacing:.4px;">To</label>
                <input type="date" name="date_to" class="form-control"
                       value="{{ $dateTo }}" style="width:160px;">
            </div>
            <div style="display:flex; gap:8px;">
                <button type="submit" class="btn btn-primary">Apply Filter</button>
                <a href="{{ route('reports.expenses') }}" class="btn btn-secondary">This Month</a>
            </div>
            <div style="margin-left:auto; font-size:12px; color:#64748b; align-self:center;">
                Showing <strong>{{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }}</strong>
                — <strong>{{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}</strong>
            </div>
        </form>
    </div>
</div>

{{-- KPI Row --}}
<div class="er-kpi-row">
    <div class="er-kpi-cell">
        <div class="er-kpi-label">Total Expenses</div>
        <div class="er-kpi-value" style="color:#dc2626;">₱{{ number_format($totalAmount, 2) }}</div>
    </div>
    <div class="er-kpi-cell">
        <div class="er-kpi-label">Total Transactions</div>
        <div class="er-kpi-value" style="color:#1d4ed8;">{{ number_format($totalCount) }}</div>
    </div>
    <div class="er-kpi-cell">
        <div class="er-kpi-label">Categories Used</div>
        <div class="er-kpi-value" style="color:#7c3aed;">{{ $byCategory->count() }}</div>
    </div>
    <div class="er-kpi-cell">
        <div class="er-kpi-label">Avg per Transaction</div>
        <div class="er-kpi-value" style="color:#0f172a;">
            ₱{{ $totalCount > 0 ? number_format($totalAmount / $totalCount, 2) : '0.00' }}
        </div>
    </div>
</div>

{{-- Category Breakdown --}}
@if($byCategory->count())
<div class="er-card">
    <div class="er-card-title">Breakdown by Category</div>
    <div style="padding:16px;">
        @foreach($byCategory as $cat)
        @php $pct = $totalAmount > 0 ? ($cat->total / $totalAmount) * 100 : 0; @endphp
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
            <div style="width:160px; font-size:13px; font-weight:600; color:#0f172a; flex-shrink:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                {{ $cat->category?->name ?? 'Uncategorized' }}
            </div>
            <div class="cat-bar-wrap">
                <div class="cat-bar" style="width:{{ round($pct, 1) }}%;"></div>
            </div>
            <div style="width:80px; text-align:right; font-size:13px; font-weight:700; color:#dc2626; flex-shrink:0;">
                ₱{{ number_format($cat->total, 2) }}
            </div>
            <div style="width:44px; text-align:right; font-size:11px; color:#94a3b8; flex-shrink:0;">
                {{ round($pct, 1) }}%
            </div>
            <div style="width:40px; text-align:right; font-size:11px; color:#94a3b8; flex-shrink:0;">
                {{ $cat->count }}x
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Line Items --}}
<div class="er-card">
    <div class="er-card-title">
        Expense Details
        <span style="font-weight:500; color:#94a3b8; margin-left:6px;">({{ $expenses->total() }} records)</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="er-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Vendor</th>
                    <th>Reference</th>
                    <th>Description</th>
                    <th class="r">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $expense)
                <tr>
                    <td style="white-space:nowrap; font-weight:600; font-size:12px;">
                        {{ $expense->expense_date->format('m/d/Y') }}
                    </td>
                    <td>
                        <span class="badge badge-info">{{ $expense->category?->name ?? '—' }}</span>
                    </td>
                    <td style="color:#374151;">{{ $expense->vendor ?: '—' }}</td>
                    <td style="font-size:12px; color:#64748b;">{{ $expense->reference_no ?: '—' }}</td>
                    <td style="font-size:12px; color:#64748b; max-width:220px; word-break:break-word;">
                        {{ $expense->description ?: '—' }}
                    </td>
                    <td style="text-align:right; font-weight:700; color:#dc2626;">
                        ₱{{ number_format($expense->amount, 2) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:40px; color:#94a3b8;">
                        No expenses recorded in this date range.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($expenses->count())
            <tfoot>
                <tr>
                    <td colspan="5" style="font-size:11px; letter-spacing:.5px; color:#94a3b8;">
                        TOTAL (this page: {{ $expenses->count() }} of {{ $expenses->total() }})
                    </td>
                    <td class="r" style="font-size:15px; color:#fca5a5;">
                        ₱{{ number_format($totalAmount, 2) }}
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    <div style="padding:12px 16px;">{{ $expenses->links() }}</div>
</div>

@endsection
