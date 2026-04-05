@extends('layouts.app')

@section('title', 'Edit User')

@section('content')

<div class="page-header">
    <div>
        <h2>Edit User</h2>
        <p>Update account details or change role for <strong>{{ $user->name }}</strong></p>
    </div>
    <a href="{{ route('users.index') }}" class="btn btn-secondary">← Back</a>
</div>

<div style="max-width:520px;">
    <div class="card">
        <div class="card-body">

            @if($errors->any())
                <div class="alert alert-danger" style="margin-bottom:16px;">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('users.update', $user) }}" id="user-edit-form">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name', $user->name) }}" required>
                </div>

                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email', $user->email) }}" required>
                </div>

                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" class="form-control" required
                            {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                        <option value="owner"     {{ old('role', $user->role) === 'owner'     ? 'selected' : '' }}>Owner — Full access</option>
                        <option value="cashier"   {{ old('role', $user->role) === 'cashier'   ? 'selected' : '' }}>Cashier — Sales &amp; Expenses</option>
                        <option value="inventory" {{ old('role', $user->role) === 'inventory' ? 'selected' : '' }}>Inventory — Products &amp; Purchase Orders</option>
                    </select>
                    @if($user->id === auth()->id())
                        <input type="hidden" name="role" value="{{ $user->role }}">
                        <small style="color:#d97706; margin-top:4px; display:block;">
                            You cannot change your own role.
                        </small>
                    @else
                        <small style="color:#64748b; margin-top:4px; display:block;">
                            Changing role takes effect on their next page load.
                        </small>
                    @endif
                </div>

                <div style="border-top:1px solid #f1f5f9; padding-top:16px; margin-top:4px;">
                    <div style="font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;
                                letter-spacing:.5px; margin-bottom:10px;">
                        Change Password <span style="font-weight:400; text-transform:none;">(leave blank to keep current)</span>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" class="form-control"
                               minlength="8" placeholder="Minimum 8 characters">
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-control"
                               placeholder="Repeat new password">
                    </div>
                </div>

                <div style="display:flex; gap:10px; margin-top:20px;">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    (function () {
        const f = document.getElementById('user-edit-form');
        if (!f || !window.CitiOffline?.queueUserUpdate) return;
        f.addEventListener('submit', async function (e) {
            if (navigator.onLine) return;
            e.preventDefault();
            const pw = f.querySelector('[name="password"]')?.value || '';
            if (pw.trim() !== '') {
                alert('Password changes require an online connection. Clear the password fields to queue name, email, and role only, or try again when online.');
                return;
            }
            const payload = {
                user_id: {{ (int) $user->id }},
                name: f.querySelector('[name="name"]')?.value?.trim(),
                email: f.querySelector('[name="email"]')?.value?.trim(),
                role: f.querySelector('[name="role"]')?.value,
            };
            try {
                const ref = await window.CitiOffline.queueUserUpdate(payload);
                alert('Offline: User update queued. Ref: ' + ref.slice(0, 8));
                window.location.href = '{{ route('users.index') }}';
            } catch (err) { alert((err && err.message) || 'Queue failed.'); }
        });
    })();
</script>
@endsection
