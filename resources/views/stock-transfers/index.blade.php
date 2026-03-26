@extends('layouts.app')
@section('title', 'Stock Transfers')
@section('content')
    <div class="page-header">
        <div><h2>Stock Transfers</h2><p>Record of stock movements between branches</p></div>
        <a href="{{ route('stock-transfers.create') }}" class="btn btn-primary">+ New Transfer</a>
    </div>

    {{-- Filters --}}
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('stock-transfers.index') }}"
                  style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
                <div class="form-group" style="margin:0; flex:1; min-width:180px;">
                    <label style="font-size:12px;">Product Search</label>
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Product name...">
                </div>
                <div class="form-group" style="margin:0;">
                    <label style="font-size:12px;">Branch</label>
                    <select name="branch_id" class="form-control">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label style="font-size:12px;">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                </div>
                <div class="form-group" style="margin:0;">
                    <label style="font-size:12px;">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                </div>
                <div style="display:flex; gap:6px;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('stock-transfers.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Product</th>
                        <th>From Branch</th>
                        <th>To Branch</th>
                        <th>Qty</th>
                        <th>Note</th>
                        <th>Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $t)
                    <tr>
                        <td style="color:#94a3b8; font-size:12px;">{{ $t->id }}</td>
                        <td style="white-space:nowrap;">
                            <div style="font-weight:600;">{{ $t->created_at->format('M d, Y') }}</div>
                            <div style="font-size:11px; color:#94a3b8;">{{ $t->created_at->format('h:i A') }}</div>
                        </td>
                        <td style="font-weight:600;">{{ $t->product->name }}</td>
                        <td>
                            <span class="badge badge-gray">{{ $t->fromBranch->code }}</span>
                            {{ $t->fromBranch->name }}
                        </td>
                        <td>
                            <span class="badge badge-info">{{ $t->toBranch->code }}</span>
                            {{ $t->toBranch->name }}
                        </td>
                        <td style="font-weight:700; font-size:15px;">{{ number_format($t->quantity) }}</td>
                        <td style="color:#64748b; font-size:13px;">{{ $t->note ?: '—' }}</td>
                        <td style="color:#64748b; font-size:13px;">{{ $t->transferredBy->name }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <div class="empty-icon">📦</div>
                                <p>No stock transfers recorded yet.</p>
                                <a href="{{ route('stock-transfers.create') }}" class="btn btn-primary">Record First Transfer</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $transfers->links() }}</div>
@endsection
