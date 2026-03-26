@extends('layouts.app')
@section('title', 'Reports')

@section('content')
<style>
.report-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px;
    margin-top: 8px;
}
.report-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 28px 22px 22px;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 1px 6px rgba(15,23,42,.05);
    transition: box-shadow .15s, transform .15s, border-color .15s;
    cursor: pointer;
}
.report-card:hover {
    box-shadow: 0 6px 24px rgba(15,23,42,.10);
    transform: translateY(-2px);
    border-color: #c7d2fe;
    text-decoration: none;
    color: inherit;
}
.report-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}
.report-card-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.3;
}
.report-card-desc {
    font-size: 12px;
    color: #64748b;
    line-height: 1.5;
}
.report-arrow {
    margin-top: auto;
    font-size: 13px;
    font-weight: 600;
    color: #6366f1;
}
</style>

<div class="page-header">
    <div>
        <h2>Reports</h2>
        <p>View and export business reports</p>
    </div>
</div>

<div class="report-grid">

    <a href="{{ route('reports.sales') }}" class="report-card">
        <div class="report-icon" style="background:#eff6ff;">📈</div>
        <div>
            <div class="report-card-title">Sales Report</div>
            <div class="report-card-desc">Daily and monthly sales summary, discounts, COGS, and gross profit.</div>
        </div>
        <div class="report-arrow">View Report →</div>
    </a>

    <a href="{{ route('reports.inventory') }}" class="report-card">
        <div class="report-icon" style="background:#f0fdf4;">📦</div>
        <div>
            <div class="report-card-title">Inventory Report</div>
            <div class="report-card-desc">Current stock levels, low-stock alerts, and product valuation overview.</div>
        </div>
        <div class="report-arrow">View Report →</div>
    </a>

    <a href="{{ route('reports.profit-loss') }}" class="report-card">
        <div class="report-icon" style="background:#fefce8;">💰</div>
        <div>
            <div class="report-card-title">Profit & Loss</div>
            <div class="report-card-desc">Accrual-based P&L with gross profit, operating expenses, and net income.</div>
        </div>
        <div class="report-arrow">View Report →</div>
    </a>

    <a href="{{ route('reports.expenses') }}" class="report-card">
        <div class="report-icon" style="background:#fff7ed;">💸</div>
        <div>
            <div class="report-card-title">Expense Report</div>
            <div class="report-card-desc">Expense summary by category and date range with totals and breakdown.</div>
        </div>
        <div class="report-arrow">View Report →</div>
    </a>

</div>
@endsection
