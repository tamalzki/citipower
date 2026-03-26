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

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('expenses.index') }}" style="display:grid; grid-template-columns: 1fr 1fr 1fr auto; gap:12px; align-items:end;">
                <div class="form-group" style="margin:0;">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" class="form-control">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ (string) $categoryId === (string) $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label for="date_from">Date From</label>
                    <input type="date" id="date_from" name="date_from" class="form-control" value="{{ $dateFrom }}">
                </div>
                <div class="form-group" style="margin:0;">
                    <label for="date_to">Date To</label>
                    <input type="date" id="date_to" name="date_to" class="form-control" value="{{ $dateTo }}">
                </div>
                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr);">
        <div class="stat-card">
            <div class="stat-icon-box icon-orange">💸</div>
            <div>
                <div class="stat-number">₱{{ number_format($totalAmount, 2) }}</div>
                <div class="stat-label">Total Filtered Expenses</div>
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
                                    <form action="{{ route('expenses.destroy', $expense) }}" method="POST" style="display:inline" onsubmit="return confirm('Delete this expense record?')">
                                        @csrf
                                        @method('DELETE')
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
                                    <p>No expenses recorded yet.</p>
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
