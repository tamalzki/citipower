@extends('layouts.app')
@section('title', 'Branches')
@section('content')
    <div class="page-header">
        <div><h2>Branches</h2><p>Manage store locations and warehouses</p></div>
        <a href="{{ route('branches.create') }}" class="btn btn-primary">+ Add Branch</a>
    </div>

    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Branch Name</th>
                        <th>Code</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($branches as $branch)
                    <tr>
                        <td style="color:#94a3b8; font-size:12px;">{{ $loop->iteration }}</td>
                        <td style="font-weight:600;">{{ $branch->name }}</td>
                        <td><span class="badge badge-gray">{{ $branch->code }}</span></td>
                        <td>
                            <div style="display:flex; gap:6px;">
                                <a href="{{ route('branches.edit', $branch) }}" class="btn btn-secondary btn-sm">Edit</a>
                                <form action="{{ route('branches.destroy', $branch) }}" method="POST"
                                      style="display:inline" onsubmit="return confirm('Delete this branch?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <div class="empty-icon">🏢</div>
                                <p>No branches yet.</p>
                                <a href="{{ route('branches.create') }}" class="btn btn-primary">Add Branch</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
