@extends('layouts.app')
@section('title', 'Supplier Ledger')
@section('content')
    <div class="page-header">
        <div><h2>Supplier Ledger</h2><p>Outstanding balances and payment status per supplier</p></div>
    </div>

    {{-- Search --}}
    <div class="card">
        <div class="card-body">
            <form method="GET" style="display:flex; gap:10px; align-items:center;">
                <input type="text" name="search" class="form-control"
                       value="{{ $search }}" placeholder="Search supplier name..."
                       style="flex:1; max-width:340px;">
                <button type="submit" class="btn btn-primary">Search</button>
                @if($search)<a href="{{ route('supplier-ledger.index') }}" class="btn btn-secondary">Clear</a>@endif
            </form>
        </div>
    </div>

    {{-- Summary KPIs --}}
    @php
        $totalBalance   = $suppliers->sum('balance');
        $totalDelivered = $suppliers->sum('total_delivered');
        $totalPaid      = $suppliers->sum('total_paid');
    @endphp
    <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
        <div class="stat-card">
            <div class="stat-icon-box icon-blue">📋</div>
            <div class="stat-info">
                <div class="stat-number">₱{{ number_format($totalDelivered, 2) }}</div>
                <div class="stat-label">Total Delivered (All)</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box icon-green">✅</div>
            <div class="stat-info">
                <div class="stat-number">₱{{ number_format($totalPaid, 2) }}</div>
                <div class="stat-label">Total Paid (All)</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box icon-red">💳</div>
            <div class="stat-info">
                <div class="stat-number" style="color:{{ $totalBalance > 0 ? '#dc2626' : '#16a34a' }};">
                    ₱{{ number_format($totalBalance, 2) }}
                </div>
                <div class="stat-label">Total Outstanding Balance</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Contact</th>
                        <th>Total Delivered</th>
                        <th>Total Paid</th>
                        <th>Outstanding Balance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $supplier)
                    <tr>
                        <td>
                            <div style="font-weight:600; color:#0f172a;">{{ $supplier->name }}</div>
                        </td>
                        <td style="color:#64748b; font-size:13px;">{{ $supplier->contact_person ?: '—' }}</td>
                        <td style="font-weight:600;">₱{{ number_format($supplier->total_delivered, 2) }}</td>
                        <td style="font-weight:600; color:#16a34a;">₱{{ number_format($supplier->total_paid, 2) }}</td>
                        <td>
                            @if($supplier->balance > 0)
                                <span style="font-weight:700; font-size:15px; color:#dc2626;">
                                    ₱{{ number_format($supplier->balance, 2) }}
                                </span>
                                <span class="badge badge-danger" style="margin-left:4px;">Unpaid</span>
                            @else
                                <span style="font-weight:700; color:#16a34a;">Fully Paid</span>
                                <span class="badge badge-success" style="margin-left:4px;">✓</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('supplier-ledger.show', $supplier) }}" class="btn btn-primary btn-sm">View Ledger</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-icon">📋</div>
                                <p>No suppliers found.</p>
                                <a href="{{ route('suppliers.create') }}" class="btn btn-primary">Add Supplier</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
