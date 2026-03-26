@extends('layouts.app')
@section('title', 'Supplier Ledger')
@section('content')
    <div class="page-header">
        <div>
            <h2>Supplier Ledger</h2>
            <p>Select a supplier first to open its ledger</p>
        </div>
        <a href="{{ route('suppliers.create') }}" class="btn btn-primary">+ Add Supplier</a>
    </div>

    <div class="card" style="margin-bottom:12px;">
        <div class="card-body">
            <form method="GET" style="display:flex; gap:10px; align-items:center;">
                <input type="text" name="search" class="form-control"
                       value="{{ $search }}" placeholder="Search supplier name or contact person..."
                       style="flex:1; max-width:420px;">
                <button type="submit" class="btn btn-primary">Search</button>
                @if($search)
                    <a href="{{ route('supplier-ledger.index') }}" class="btn btn-secondary">Clear</a>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>Supplier</th>
                    <th>Contact Person</th>
                    <th style="text-align:right;">Outstanding</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($suppliers as $supplier)
                    <tr>
                        <td style="font-weight:600;">{{ $supplier->name }}</td>
                        <td>{{ $supplier->contact_person ?: '—' }}</td>
                        <td style="text-align:right; font-weight:700; color:{{ $supplier->balance > 0 ? '#dc2626' : '#16a34a' }};">
                            ₱{{ number_format($supplier->balance, 2) }}
                        </td>
                        <td style="width:160px;">
                            <a href="{{ route('supplier-ledger.show', $supplier) }}" class="btn btn-primary btn-sm">
                                Open Ledger
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <div class="empty-icon">📋</div>
                                <p>{{ $search ? 'No supplier matches your search.' : 'No suppliers have been added yet.' }}</p>
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
