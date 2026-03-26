<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - @yield('title')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            font-size: 13px;
            line-height: 1.5;
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* ══════════════════════════════
           SIDEBAR
        ══════════════════════════════ */
        .sidebar {
            width: 220px;
            background: #0f172a;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 100;
        }

        .sidebar-brand {
            padding: 16px 18px;
            border-bottom: 1px solid #1e293b;
        }

        .sidebar-brand .brand-logo {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sidebar-brand .brand-icon {
            width: 28px;
            height: 28px;
            background: #2563eb;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .sidebar-brand h2 {
            font-size: 14px;
            font-weight: 700;
            color: #f8fafc;
            letter-spacing: -0.3px;
        }

        .sidebar-brand p {
    font-size: 10.5px;
    color: #64748b;
    margin-top: 1px;
}

        .sidebar-menu {
            flex: 1;
            padding: 10px 10px;
            overflow-y: auto;
        }

        .sidebar-menu .menu-label {
            padding: 8px 10px 3px;
            font-size: 9.5px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.9px;
            margin-top: 4px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 10px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            border-radius: 6px;
            margin-bottom: 1px;
            transition: all 0.15s ease;
        }

        .sidebar-menu a .menu-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #475569;
            flex-shrink: 0;
            transition: background 0.15s;
        }

        .sidebar-menu a:hover {
            background: #1e293b;
            color: #e2e8f0;
        }

        .sidebar-menu a:hover .menu-dot {
            background: #94a3b8;
        }

        .sidebar-menu a.active {
            background: #2563eb;
            color: #fff;
            font-weight: 600;
        }

        .sidebar-menu a.active .menu-dot {
            background: #fff;
        }

        .sidebar-footer {
            padding: 12px 16px;
            border-top: 1px solid #1e293b;
        }

        .sidebar-footer .user-block {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .sidebar-footer .avatar {
            width: 30px;
            height: 30px;
            background: #2563eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }

        .sidebar-footer .user-name {
            font-size: 12.5px;
            font-weight: 600;
            color: #f1f5f9;
            line-height: 1.2;
        }

        .sidebar-footer .user-role {
            font-size: 10.5px;
            color: #64748b;
        }

        .sidebar-footer .logout-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #64748b;
            text-decoration: none;
            padding: 6px 8px;
            border-radius: 5px;
            transition: all 0.15s;
            font-weight: 500;
        }

        .sidebar-footer .logout-btn:hover {
            background: #1e293b;
            color: #ef4444;
        }

        /* ══════════════════════════════
           MAIN CONTENT
        ══════════════════════════════ */
        .main {
            margin-left: 220px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .topbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0 24px;
            height: 52px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-left h1 {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.3px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .topbar-date {
            display: flex;
            align-items: center;
            gap: 5px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11.5px;
            color: #64748b;
            font-weight: 500;
        }

        .content {
            padding: 20px 24px;
            flex: 1;
        }

        /* ══════════════════════════════
           PAGE HEADER
        ══════════════════════════════ */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .page-header h2 {
            font-size: 17px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.4px;
        }

        .page-header p {
            font-size: 12px;
            color: #64748b;
            margin-top: 1px;
        }

        /* ══════════════════════════════
           CARDS
        ══════════════════════════════ */
        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 16px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
            overflow: hidden;
        }

        .card-body {
            padding: 16px 20px;
        }

        .card-title {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            padding: 13px 20px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        /* ══════════════════════════════
           TABLES
        ══════════════════════════════ */
        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        table thead tr {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        table th {
            padding: 9px 14px;
            text-align: left;
            font-size: 10.5px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        table td {
            padding: 9px 14px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }

        table tbody tr:last-child td {
            border-bottom: none;
        }

        table tbody tr:hover td {
            background: #f8fafc;
        }

        /* ══════════════════════════════
           BUTTONS
        ══════════════════════════════ */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 7px 14px;
            font-size: 12.5px;
            font-weight: 600;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
            transition: all 0.15s ease;
            white-space: nowrap;
            letter-spacing: -0.1px;
        }

        .btn:hover { opacity: 0.88; }

        .btn-primary  { background: #2563eb; color: #fff; }
        .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .btn-success  { background: #16a34a; color: #fff; }
        .btn-warning  { background: #d97706; color: #fff; }
        .btn-danger   { background: #dc2626; color: #fff; }
        .btn-info     { background: #0284c7; color: #fff; }

        .btn-sm {
            padding: 4px 10px;
            font-size: 11.5px;
            border-radius: 5px;
        }

        /* ══════════════════════════════
           BADGES
        ══════════════════════════════ */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: 0.2px;
            white-space: nowrap;
        }

        .badge-danger  { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .badge-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .badge-warning { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
        .badge-info    { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .badge-gray    { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

        /* ══════════════════════════════
           FORMS
        ══════════════════════════════ */
        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            display: block;
            font-size: 11.5px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 5px;
            letter-spacing: 0.1px;
        }

        .form-control {
            width: 100%;
            padding: 7px 10px;
            border: 1.5px solid #e2e8f0;
            border-radius: 6px;
            font-size: 13px;
            color: #1e293b;
            background: #fff;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .form-control:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.08);
        }

        .form-control::placeholder { color: #cbd5e1; }

        /* Make dropdown fields visually obvious app-wide */
        select.form-control:not([multiple]):not([size]) {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            padding-right: 34px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2364758b' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 14px 14px;
        }

        /* ══════════════════════════════
           ALERTS
        ══════════════════════════════ */
        .alert {
            padding: 10px 14px;
            border-radius: 7px;
            margin-bottom: 16px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
        .alert-danger  { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }

        /* ══════════════════════════════
           STAT CARDS
        ══════════════════════════════ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stat-card .stat-icon-box {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .stat-card .stat-number {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }

        .stat-card .stat-label {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
            font-weight: 500;
        }

        .icon-blue   { background: #eff6ff; }
        .icon-red    { background: #fef2f2; }
        .icon-green  { background: #f0fdf4; }
        .icon-orange { background: #fffbeb; }

        /* ══════════════════════════════
           EMPTY STATE
        ══════════════════════════════ */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
        }

        .empty-state .empty-icon { font-size: 32px; margin-bottom: 10px; }
        .empty-state p { font-size: 13px; margin-bottom: 14px; }
    </style>
</head>
<body>
@php($currentUser = auth()->user())
@php($role = $currentUser?->role ?? 'owner')
<div class="wrapper">

    {{-- ══ SIDEBAR ══ --}}
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">
            <img src="{{ asset('logo.png') }}" alt="Citipower"
                 style="width:46px; height:46px; object-fit:contain; flex-shrink:0;
                        filter:drop-shadow(0 2px 8px rgba(245,171,0,.35));">
            <div>
                <h2>Citipower</h2>
                <p style="padding-left:0; margin-top:1px;">Electronic Supply</p>
            </div>
        </div>
    </div>

    <nav class="sidebar-menu">

        {{-- ── Overview ── --}}
        <div class="menu-label">Overview</div>

        <a href="{{ route('dashboard') }}"
           class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" style="flex-shrink:0;">
                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
            </svg>
            Dashboard
        </a>

        {{-- ── Operations ── --}}
        @if(in_array($role, ['owner', 'cashier']))
        <div class="menu-label" style="margin-top:10px;">Operations</div>

        <a href="{{ route('sales.index') }}"
           class="{{ request()->routeIs('sales.*') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" style="flex-shrink:0;">
                <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
            </svg>
            Sales
        </a>

        <a href="{{ route('expenses.index') }}"
           class="{{ request()->routeIs('expenses.*') || request()->routeIs('expense-categories.*') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" style="flex-shrink:0;">
                <line x1="12" y1="1" x2="12" y2="23"/>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
            Expenses
        </a>
        @endif

        {{-- ── Inventory ── --}}
        @if(in_array($role, ['owner', 'inventory']))
        <div class="menu-label" style="margin-top:10px;">Inventory</div>

        <a href="{{ route('products.index') }}"
           class="{{ request()->routeIs('products.*') || request()->routeIs('inventory.*') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" style="flex-shrink:0;">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
            </svg>
            Products
        </a>

        <a href="{{ route('suppliers.index') }}"
           class="{{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" style="flex-shrink:0;">
                <rect x="1" y="3" width="15" height="13" rx="1"/>
                <path d="M16 8h4l3 5v3h-7V8z"/>
                <circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
            </svg>
            Suppliers
        </a>

        <a href="{{ route('purchase-orders.index') }}"
           class="{{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" style="flex-shrink:0;">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="8" y1="13" x2="16" y2="13"/>
                <line x1="8" y1="17" x2="16" y2="17"/>
            </svg>
            Purchase Orders
        </a>

        <a href="{{ route('inventory-logs.index') }}"
           class="{{ request()->routeIs('inventory-logs.*') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" style="flex-shrink:0;">
                <path d="M3 3h18v4H3z"/><path d="M3 10h18v4H3z"/><path d="M3 17h18v4H3z"/>
            </svg>
            Inventory Logs
        </a>

        <a href="{{ route('stock-transfers.index') }}"
           class="{{ request()->routeIs('stock-transfers.*') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" style="flex-shrink:0;">
                <path d="M5 12h14"/><polyline points="12 5 19 12 12 19"/>
            </svg>
            Stock Transfers
        </a>

        
        @endif

        {{-- ── Supplier Ledger (owner only) ── --}}
        @if($role === 'owner')
        <a href="{{ route('supplier-ledger.index') }}"
           class="{{ request()->routeIs('supplier-ledger.*') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" style="flex-shrink:0;">
                <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
            </svg>
            Supplier Ledger
        </a>
        @endif

        {{-- ── Reports (owner only) ── --}}
        @if($role === 'owner')
        <div class="menu-label" style="margin-top:10px;">Reports</div>

        <a href="{{ route('reports.hub') }}"
           class="{{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" style="flex-shrink:0;">
                <rect x="3" y="3" width="7" height="7"/>
                <rect x="14" y="3" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/>
                <rect x="3" y="14" width="7" height="7"/>
            </svg>
            Reports
        </a>
        @endif

        {{-- ── Settings (owner only) ── --}}
        @if($role === 'owner')
        <div class="menu-label" style="margin-top:10px;">Settings</div>

        <a href="{{ route('users.index') }}"
           class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" style="flex-shrink:0;">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            Users
        </a>
        @endif

    </nav>

    <div class="sidebar-footer">
        <div class="user-block">
            <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            <div>
                <div class="user-name">{{ $currentUser->name }}</div>
                <div class="user-role">{{ ucfirst($role) }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <a href="#" class="logout-btn" onclick="this.closest('form').submit()">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Logout
            </a>
        </form>
    </div>
</div>

    {{-- ══ MAIN ══ --}}
    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h1>@yield('title')</h1>
            </div>
            <div class="topbar-right">
                @if(in_array($role, ['owner', 'cashier']))
                    <a href="{{ route('sales.create') }}" class="btn btn-primary btn-sm">+ New Sale</a>
                @endif
                <div class="topbar-date">
                    📅 {{ now()->format('F d, Y') }}
                </div>
            </div>
        </div>

        <div class="content">
            @if(session('success'))
                <div class="alert alert-success">✅ {{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">❌ {{ session('error') }}</div>
            @endif

            @yield('content')
        </div>
    </div>

</div>
</body>
</html>