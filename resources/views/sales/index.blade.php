@extends('layouts.app')

@section('title', 'Sales')

@section('content')
    <div class="page-header">
        <div>
            <h2>Sales</h2>
            <p>All recorded sales transactions</p>
        </div>
        <a href="{{ route('sales.create') }}" class="btn btn-primary">+ New Sale</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('sales.index') }}"
                  style="display:flex; gap:10px; align-items:center;">
                <input type="text" name="search" class="form-control"
                       value="{{ $search }}"
                       placeholder="Search sale #, product, SKU, POC, note..."
                       style="flex:1; max-width:420px;">
                <button type="submit" class="btn btn-primary">Search</button>
                @if($search)
                    <a href="{{ route('sales.index') }}" class="btn btn-secondary">Clear</a>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date & Time</th>
                        <th>Items</th>
                        <th>Total Amount</th>
                        <th>POC</th>
                        <th>Receipt</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                    <tr>
                        <td style="color:#94a3b8; font-size:12px;">{{ $sale->id }}</td>
                        <td style="white-space:nowrap;">
                            <div style="font-weight:600; color:#0f172a;">
                                {{ $sale->created_at->format('M d, Y') }}
                            </div>
                            <div style="font-size:11px; color:#94a3b8;">
                                {{ $sale->created_at->format('h:i A') }}
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-info">
                                {{ $sale->items->count() }} item(s)
                            </span>
                        </td>
                        <td>
                            <span style="font-weight:700; font-size:14px; color:#0f172a;">
                                ₱{{ number_format($sale->total_amount, 2) }}
                            </span>
                        </td>
                        <td style="color:#64748b;">{{ $sale->poc ?? '—' }}</td>
                        <td>
                            @if($sale->issued_receipt)
                                <span class="badge badge-success">Yes</span>
                            @else
                                <span class="badge badge-gray">No</span>
                            @endif
                        </td>
                        <td><span class="badge badge-success">Paid</span></td>
                        <td>
                            <div style="display:flex; gap:6px;">
                                <a href="{{ route('sales.show', $sale) }}"
                                   class="btn btn-secondary btn-sm">View</a>
                                @if(auth()->user()->role === 'owner')
                                <form action="{{ route('sales.destroy', $sale) }}" method="POST"
                                      style="display:inline"
                                      onsubmit="return confirm('Void this sale? Stock will be restored.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Void</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <div class="empty-icon">🧾</div>
                                <p>{{ $search ? 'No sales match your search.' : 'No sales recorded yet.' }}</p>
                                <a href="{{ route('sales.create') }}" class="btn btn-primary">Record First Sale</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $sales->links() }}</div>
@endsection