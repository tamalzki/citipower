@extends('layouts.app')

@section('title', 'Add Expense')

@section('content')
    <div class="page-header">
        <div>
            <h2>Add Expense</h2>
            <p>Record a new business expense</p>
        </div>
        <a href="{{ route('expenses.index') }}" class="btn btn-secondary">← Back</a>
    </div>

    <div class="card" style="max-width: 760px;">
        <div class="card-title">Expense Information</div>
        <div class="card-body">
            <form action="{{ route('expenses.store') }}" method="POST">
                @csrf

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="expense_category_id" class="form-control" required>
                            <option value="">Select category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('expense_category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('expense_category_id') <small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small> @enderror
                    </div>

                    <div class="form-group">
                        <label>Expense Date *</label>
                        <input type="date" name="expense_date" class="form-control" value="{{ old('expense_date', now()->toDateString()) }}" required>
                        @error('expense_date') <small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div class="form-group">
                        <label>Amount (₱) *</label>
                        <input type="number" name="amount" class="form-control" value="{{ old('amount') }}" min="0.01" step="0.01" placeholder="0.00" required>
                        @error('amount') <small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small> @enderror
                    </div>

                    <div class="form-group">
                        <label>Reference No</label>
                        <input type="text" name="reference_no" class="form-control" value="{{ old('reference_no') }}" placeholder="Invoice / OR number">
                        @error('reference_no') <small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label>Vendor</label>
                    <input type="text" name="vendor" class="form-control" value="{{ old('vendor') }}" placeholder="Supplier or payee name">
                    @error('vendor') <small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Optional notes">{{ old('description') }}</textarea>
                    @error('description') <small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small> @enderror
                </div>

                <div style="display:flex; gap:8px; margin-top:6px;">
                    <button type="submit" class="btn btn-primary">Save Expense</button>
                    <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
