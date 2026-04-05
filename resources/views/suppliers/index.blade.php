@extends('layouts.app')
@section('title', 'Suppliers')
@section('content')
    <div class="page-header">
        <div><h2>Suppliers</h2><p>Manage supplier records and view payment ledgers</p></div>
        <a href="{{ route('suppliers.create') }}" class="btn btn-primary">+ Add Supplier</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" style="display:flex; gap:10px; align-items:center;">
                <input type="text" name="search" class="form-control"
                       value="{{ $search ?? '' }}"
                       placeholder="Search by name, contact, phone, email..."
                       style="flex:1; max-width:380px;">
                <button type="submit" class="btn btn-primary">Search</button>
                @if(!empty($search))<a href="{{ route('suppliers.index') }}" class="btn btn-secondary">Clear</a>@endif
            </form>
        </div>
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
                    <th>Balance</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($suppliers as $supplier)
                    @php
                        $delivered = (float) ($supplier->deliveries_sum_amount ?? 0);
                        $paid      = (float) ($supplier->supplier_payments_sum_amount ?? 0);
                        $balance   = max(0, $delivered - $paid);
                    @endphp
                    <tr>
                        <td style="color:#94a3b8; font-size:12px;">{{ $loop->iteration }}</td>
                        <td style="font-weight:600;">{{ $supplier->name }}</td>
                        <td>{{ $supplier->contact_person ?: '—' }}</td>
                        <td>{{ $supplier->phone ?: '—' }}</td>
                        <td>{{ $supplier->email ?: '—' }}</td>
                        <td>
                            @if($balance > 0)
                                <span style="font-weight:700; color:#dc2626;">₱{{ number_format($balance, 2) }}</span>
                            @else
                                <span style="color:#16a34a; font-weight:600;">Paid</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex; gap:6px;">
                                <a href="{{ route('supplier-ledger.show', $supplier) }}" class="btn btn-primary btn-sm">Ledger</a>
                                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-secondary btn-sm">Edit</a>
                                <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}"
                                      class="offline-supplier-delete-form" data-supplier-id="{{ $supplier->id }}"
                                      onsubmit="return confirm('Delete this supplier?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="empty-icon">🏭</div>
                                <p>{{ !empty($search) ? 'No suppliers found.' : 'No suppliers yet.' }}</p>
                                @if(empty($search))
                                    <a href="{{ route('suppliers.create') }}" class="btn btn-primary">Add Supplier</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $suppliers->links() }}</div>
    <script>
        (function () {
            document.querySelectorAll('.offline-supplier-delete-form').forEach(function (form) {
                form.addEventListener('submit', async function (e) {
                    if (navigator.onLine || !window.CitiOffline?.queueSupplierDelete) return;
                    e.preventDefault();
                    if (!confirm('Delete this supplier?')) return;
                    const sid = parseInt(form.dataset.supplierId || '0', 10);
                    try {
                        const ref = await window.CitiOffline.queueSupplierDelete({ supplier_id: sid });
                        alert('Offline: Delete queued. Ref: ' + ref.slice(0, 8));
                        form.closest('tr')?.remove();
                    } catch (err) { alert((err && err.message) || 'Queue failed.'); }
                });
            });
        })();
    </script>
@endsection
