@extends('layouts.app')

@section('title', 'Edit Expense')

@section('content')
    <div class="page-header">
        <div>
            <h2>Edit Expense</h2>
            <p>Update expense record #{{ $expense->id }}</p>
        </div>
        <a href="{{ route('expenses.index') }}" class="btn btn-secondary">← Back</a>
    </div>

    <div class="card" style="max-width: 760px;">
        <div class="card-title">Expense Information</div>
        <div class="card-body">
            <form action="{{ route('expenses.update', $expense) }}" method="POST" id="expense-edit-form">
                @csrf
                @method('PUT')

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="expense_category_id" class="form-control" required>
                            <option value="">Select category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('expense_category_id', $expense->expense_category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('expense_category_id') <small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small> @enderror
                    </div>

                    <div class="form-group">
                        <label>Expense Date *</label>
                        <input type="date" name="expense_date" class="form-control" value="{{ old('expense_date', $expense->expense_date->toDateString()) }}" required>
                        @error('expense_date') <small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div class="form-group">
                        <label>Amount (₱) *</label>
                        <input type="number" name="amount" class="form-control" value="{{ old('amount', $expense->amount) }}" min="0.01" step="0.01" required>
                        @error('amount') <small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small> @enderror
                    </div>

                    <div class="form-group">
                        <label>Reference No</label>
                        <input type="text" name="reference_no" class="form-control" value="{{ old('reference_no', $expense->reference_no) }}" placeholder="Invoice / OR number">
                        @error('reference_no') <small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label>Vendor</label>
                    <input type="text" name="vendor" class="form-control" value="{{ old('vendor', $expense->vendor) }}" placeholder="Supplier or payee name">
                    @error('vendor') <small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Optional notes">{{ old('description', $expense->description) }}</textarea>
                    @error('description') <small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small> @enderror
                </div>

                <div style="display:flex; gap:8px; margin-top:6px;">
                    <button type="submit" class="btn btn-primary">Update Expense</button>
                    <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        (function () {
            const form = document.getElementById('expense-edit-form');
            if (!form) return;

            form.addEventListener('submit', async function (e) {
                if (navigator.onLine || !window.CitiOffline || typeof window.CitiOffline.queueExpenseUpdate !== 'function') {
                    return;
                }

                e.preventDefault();

                const categoryId = form.querySelector('[name="expense_category_id"]')?.value;
                const expenseDate = form.querySelector('[name="expense_date"]')?.value;
                const amount = parseFloat(form.querySelector('[name="amount"]')?.value || '0');
                if (!categoryId || !expenseDate || amount <= 0) {
                    alert('Category, date, and amount are required.');
                    return;
                }

                const payload = {
                    expense_id: {{ (int) $expense->id }},
                    expense_category_id: parseInt(categoryId, 10),
                    expense_date: expenseDate,
                    reference_no: form.querySelector('[name="reference_no"]')?.value || '',
                    amount: amount,
                    vendor: form.querySelector('[name="vendor"]')?.value || '',
                    description: form.querySelector('[name="description"]')?.value || '',
                };

                try {
                    const localId = await window.CitiOffline.queueExpenseUpdate(payload);
                    alert('Offline: Expense update queued and will auto-sync when online. Ref: ' + localId.slice(0, 8));
                    window.location.href = '{{ route('expenses.index') }}';
                } catch (err) {
                    alert((err && err.message) || 'Failed to queue expense update offline.');
                }
            });
        })();
    </script>
@endsection
