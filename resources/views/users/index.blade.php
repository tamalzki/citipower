@extends('layouts.app')

@section('title', 'User Management')

@section('content')

<div class="page-header">
    <div>
        <h2>User Management</h2>
        <p>Manage staff accounts and their access roles</p>
    </div>
    <a href="{{ route('users.create') }}" class="btn btn-primary">+ Add User</a>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:16px;">{{ session('error') }}</div>
@endif

{{-- Role legend --}}
<div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px;">
    @foreach([
        'owner'     => ['color'=>'#2563eb','bg'=>'#eff6ff','desc'=>'Full access to everything'],
        'cashier'   => ['color'=>'#16a34a','bg'=>'#f0fdf4','desc'=>'Sales & Expenses only'],
        'inventory' => ['color'=>'#d97706','bg'=>'#fffbeb','desc'=>'Products, POs & Inventory Logs'],
    ] as $role => $info)
    <div style="display:flex; align-items:center; gap:8px; padding:8px 14px;
                background:{{ $info['bg'] }}; border-radius:8px; border:1px solid {{ $info['color'] }}22;">
        <span style="font-size:12px; font-weight:700; color:{{ $info['color'] }}; text-transform:uppercase; letter-spacing:.4px;">
            {{ $role }}
        </span>
        <span style="font-size:12px; color:#64748b;">— {{ $info['desc'] }}</span>
    </div>
    @endforeach
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="width:34px; height:34px; border-radius:50%;
                                        background:#2563eb; color:#fff; display:flex;
                                        align-items:center; justify-content:center;
                                        font-size:13px; font-weight:700; flex-shrink:0;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-weight:600; color:#0f172a;">{{ $user->name }}</div>
                                @if($user->id === auth()->id())
                                    <span style="font-size:10px; color:#2563eb; font-weight:600;">You</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="color:#475569;">{{ $user->email }}</td>
                    <td>
                        @php
                            $roleColors = [
                                'owner'     => ['text'=>'#2563eb','bg'=>'#eff6ff'],
                                'cashier'   => ['text'=>'#16a34a','bg'=>'#f0fdf4'],
                                'inventory' => ['text'=>'#d97706','bg'=>'#fffbeb'],
                            ];
                            $rc = $roleColors[$user->role] ?? ['text'=>'#64748b','bg'=>'#f1f5f9'];
                        @endphp
                        <span style="display:inline-block; padding:3px 10px; border-radius:20px;
                                     background:{{ $rc['bg'] }}; color:{{ $rc['text'] }};
                                     font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.4px;">
                            {{ $user->role }}
                        </span>
                    </td>
                    <td style="color:#64748b; font-size:13px;">{{ $user->created_at->format('M d, Y') }}</td>
                    <td>
                        <div style="display:flex; gap:6px;">
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-secondary btn-sm">Edit</a>
                            @if($user->id !== auth()->id())
                                <form method="POST" action="{{ route('users.destroy', $user) }}"
                                      onsubmit="return confirm('Delete {{ addslashes($user->name) }}? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" style="color:#94a3b8;">No users found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
