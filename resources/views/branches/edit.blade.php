@extends('layouts.app')
@section('title', 'Edit Branch')
@section('content')
    <div class="page-header">
        <div><h2>Edit Branch</h2><p>Update {{ $branch->name }}</p></div>
        <a href="{{ route('branches.index') }}" class="btn btn-secondary">← Back</a>
    </div>

    <div class="card" style="max-width:480px;">
        <div class="card-title">Branch Information</div>
        <div class="card-body">
            <form action="{{ route('branches.update', $branch) }}" method="POST">
                @csrf @method('PUT')
                <div class="form-group">
                    <label>Branch Name *</label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name', $branch->name) }}" required autofocus>
                    @error('name')<small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small>@enderror
                </div>
                <div class="form-group">
                    <label>Code *</label>
                    <input type="text" name="code" class="form-control"
                           value="{{ old('code', $branch->code) }}" required>
                    @error('code')<small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small>@enderror
                </div>
                <div style="display:flex; gap:8px; margin-top:6px;">
                    <button type="submit" class="btn btn-primary">Update Branch</button>
                    <a href="{{ route('branches.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
