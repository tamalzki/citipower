@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
    <div class="page-header">
        <div>
            <h2>Suppliers</h2>
            <p>Manage supplier records for purchase orders</p>
        </div>
        <a href="{{ route('suppliers.create') }}" class="btn btn-primary">+ Add Supplier</a>
    </div>

    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Contact Person</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($suppliers as $supplier)
                    <tr>
                        <td>{{ $supplier->id }}</td>
                        <td style="font-weight:600;">{{ $supplier->name }}</td>
                        <td>{{ $supplier->contact_person ?: '—' }}</td>
                        <td>{{ $supplier->phone ?: '—' }}</td>
                        <td>{{ $supplier->email ?: '—' }}</td>
                        <td>
                            <div style="display:flex; gap:6px;">
                                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-secondary btn-sm">Edit</a>
                                <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirm('Delete this supplier?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="color:#94a3b8;">No suppliers yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $suppliers->links() }}</div>
@endsection
