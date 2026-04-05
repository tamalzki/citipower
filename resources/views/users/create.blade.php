@extends('layouts.app')

@section('title', 'Add User')

@section('content')

<div class="page-header">
    <div>
        <h2>Add User</h2>
        <p>Create a new staff account and assign a role.</p>
        <p style="font-size:13px; color:#64748b; margin-top:6px; max-width:520px;">
            Adding users while offline is not supported. Create new accounts in the database, or use this form when you are online.
        </p>
    </div>
    <a href="{{ route('users.index') }}" class="btn btn-secondary">← Back</a>
</div>

<div style="max-width:520px;">
    <div class="card">
        <div class="card-body">

            @if($errors->any())
                <div class="alert alert-danger" style="margin-bottom:16px;">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('users.store') }}">
                @csrf

                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name') }}" required placeholder="e.g. Maria Santos">
                </div>

                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email') }}" required placeholder="staff@example.com">
                </div>

                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" class="form-control" required>
                        <option value="">— Select role —</option>
                        <option value="owner"     {{ old('role') === 'owner'     ? 'selected' : '' }}>Owner — Full access</option>
                        <option value="cashier"   {{ old('role') === 'cashier'   ? 'selected' : '' }}>Cashier — Sales &amp; Expenses</option>
                        <option value="inventory" {{ old('role') === 'inventory' ? 'selected' : '' }}>Inventory — Products &amp; Purchase Orders</option>
                    </select>
                    <small style="color:#64748b; margin-top:4px; display:block;">
                        This determines what the user can see and do in the system.
                    </small>
                </div>

                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" class="form-control"
                           required minlength="8" placeholder="Minimum 8 characters">
                </div>

                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="password_confirmation" class="form-control"
                           required placeholder="Repeat password">
                </div>

                <div style="display:flex; gap:10px; margin-top:20px;">
                    <button type="submit" class="btn btn-primary">Create User</button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
