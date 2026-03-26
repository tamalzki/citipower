@extends('layouts.app')

@section('title', 'Expenses')

@section('content')
    <div class="page-header">
        <div>
            <h2>Expenses</h2>
            <p>Track and manage business expenses by category</p>
        </div>
        <a href="{{ route('expenses.create') }}" class="btn btn-primary">+ Add Expense</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('expenses.index') }}"
                  style="display:flex; gap:10px; align-items:center;">
                <input type="text" name="search" class="form-control"
                       value="{{ $search }}"
                       placeholder="Search vendor, category, description, reference…"
                       style="flex:1; max-width:420px;">
                <button type="submit" class="btn btn-primary">Search</button>
                @if($search)
                    <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Clear</a>
                @endif
            </form>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr);">
        <div class="stat-card">
            <div class="stat-icon-box icon-orange">💸</div>
            <div>
                <div class="stat-number">₱{{ number_format($totalAmount, 2) }}</div>
                <div class="stat-label">{{ $search ? 'Filtered Total' : 'Total Expenses' }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box icon-blue">🧾</div>
            <div>
                <div class="stat-number">{{ number_format($expenses->total()) }}</div>
                <div class="stat-label">Total Records</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Vendor</th>
                        <th>Reference</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                        <tr>
                            <td>{{ $expense->id }}</td>
                            <td>{{ $expense->expense_date->format('M d, Y') }}</td>
                            <td><span class="badge badge-info">{{ $expense->category?->name }}</span></td>
                            <td style="font-weight:700;">₱{{ number_format($expense->amount, 2) }}</td>
                            <td>{{ $expense->vendor ?: '—' }}</td>
                            <td>{{ $expense->reference_no ?: '—' }}</td>
                            <td style="max-width:260px;">{{ $expense->description ?: '—' }}</td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-secondary btn-sm">Edit</a>
                                    @if(auth()->user()->role === 'owner')
                                    <form action="{{ route('expenses.destroy', $expense) }}" method="POST"
                                          style="display:inline" onsubmit="return confirm('Delete this expense?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <div class="empty-icon">💸</div>
                                    <p>{{ $search ? 'No expenses match your search.' : 'No expenses recorded yet.' }}</p>
                                    <a href="{{ route('expenses.create') }}" class="btn btn-primary">Add First Expense</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $expenses->links() }}</div>
@endsection
