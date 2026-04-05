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
            <form action="{{ route('branches.update', $branch) }}" method="POST" id="branch-edit-form">
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
    <script>
        (function () {
            const f = document.getElementById('branch-edit-form');
            if (!f || !window.CitiOffline?.queueBranchUpdate) return;
            f.addEventListener('submit', async function (e) {
                if (navigator.onLine) return;
                e.preventDefault();
                const name = f.querySelector('[name="name"]')?.value?.trim();
                const code = f.querySelector('[name="code"]')?.value?.trim();
                if (!name || !code) { alert('Name and code are required.'); return; }
                try {
                    const ref = await window.CitiOffline.queueBranchUpdate({
                        branch_id: {{ (int) $branch->id }},
                        name: name,
                        code: code,
                    });
                    alert('Offline: Update queued. Ref: ' + ref.slice(0, 8));
                    window.location.href = '{{ route('branches.index') }}';
                } catch (err) { alert((err && err.message) || 'Queue failed.'); }
            });
        })();
    </script>
@endsection
