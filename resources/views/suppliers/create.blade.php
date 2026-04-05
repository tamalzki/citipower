@extends('layouts.app')

@section('title', 'Add Supplier')

@section('content')
    <div class="page-header">
        <div>
            <h2>Add Supplier</h2>
            <p>Create supplier details for procurement</p>
        </div>
        <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">← Back</a>
    </div>

    <div class="card" style="max-width:760px;">
        <div class="card-body">
            <form method="POST" action="{{ route('suppliers.store') }}" id="supplier-create-form">
                @csrf
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label>Contact Person</label>
                        <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person') }}">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address" class="form-control" value="{{ old('address') }}">
                    </div>
                </div>
                <div style="display:flex; gap:8px;">
                    <button class="btn btn-primary">Save Supplier</button>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        (function () {
            const f = document.getElementById('supplier-create-form');
            if (!f || !window.CitiOffline?.queueSupplierCreate) return;
            f.addEventListener('submit', async function (e) {
                if (navigator.onLine) return;
                e.preventDefault();
                const name = f.querySelector('[name="name"]')?.value?.trim();
                if (!name) { alert('Name is required.'); return; }
                try {
                    const ref = await window.CitiOffline.queueSupplierCreate({
                        name: name,
                        contact_person: f.querySelector('[name="contact_person"]')?.value || '',
                        phone: f.querySelector('[name="phone"]')?.value || '',
                        email: f.querySelector('[name="email"]')?.value || '',
                        address: f.querySelector('[name="address"]')?.value || '',
                    });
                    alert('Offline: Supplier queued. Ref: ' + ref.slice(0, 8));
                    window.location.href = '{{ route('suppliers.index') }}';
                } catch (err) { alert((err && err.message) || 'Queue failed.'); }
            });
        })();
    </script>
@endsection
