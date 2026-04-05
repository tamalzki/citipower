@extends('layouts.app')
@section('title', 'Add Branch')
@section('content')
    <div class="page-header">
        <div><h2>Add Branch</h2><p>Add a new store location or warehouse</p></div>
        <a href="{{ route('branches.index') }}" class="btn btn-secondary">← Back</a>
    </div>

    <div class="card" style="max-width:480px;">
        <div class="card-title">Branch Information</div>
        <div class="card-body">
            <form action="{{ route('branches.store') }}" method="POST" id="branch-create-form">
                @csrf
                <div class="form-group">
                    <label>Branch Name *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                           placeholder="e.g. Main Branch" required autofocus>
                    @error('name')<small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small>@enderror
                </div>
                <div class="form-group">
                    <label>Code *</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code') }}"
                           placeholder="e.g. MAIN" style="text-transform:uppercase;" required>
                    <small style="color:#94a3b8; font-size:11px;">Short code, e.g. MAIN, WH, BR2</small>
                    @error('code')<small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small>@enderror
                </div>
                <div style="display:flex; gap:8px; margin-top:6px;">
                    <button type="submit" class="btn btn-primary">Save Branch</button>
                    <a href="{{ route('branches.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        (function () {
            const f = document.getElementById('branch-create-form');
            if (!f || !window.CitiOffline?.queueBranchCreate) return;
            f.addEventListener('submit', async function (e) {
                if (navigator.onLine) return;
                e.preventDefault();
                const name = f.querySelector('[name="name"]')?.value?.trim();
                const code = f.querySelector('[name="code"]')?.value?.trim();
                if (!name || !code) { alert('Name and code are required.'); return; }
                try {
                    const ref = await window.CitiOffline.queueBranchCreate({ name: name, code: code });
                    alert('Offline: Branch queued. Ref: ' + ref.slice(0, 8));
                    window.location.href = '{{ route('branches.index') }}';
                } catch (err) { alert((err && err.message) || 'Queue failed.'); }
            });
        })();
    </script>
@endsection
