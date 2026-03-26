@extends('layouts.app')

@section('title', 'Edit Expense Category')

@section('content')
    <div class="page-header">
        <div>
            <h2>Edit Expense Category</h2>
            <p>Update category: <strong>{{ $expenseCategory->name }}</strong></p>
        </div>
        <a href="{{ route('expense-categories.index') }}" class="btn btn-secondary">← Back</a>
    </div>

    <div class="card" style="max-width: 620px;">
        <div class="card-title">Category Details</div>
        <div class="card-body">
            <form action="{{ route('expense-categories.update', $expenseCategory) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label>Category Name *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $expenseCategory->name) }}" required autofocus>
                    @error('name') <small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Optional category description">{{ old('description', $expenseCategory->description) }}</textarea>
                    @error('description') <small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small> @enderror
                </div>

                <div style="display:flex; gap:8px; margin-top:6px;">
                    <button type="submit" class="btn btn-primary">Update Category</button>
                    <a href="{{ route('expense-categories.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
