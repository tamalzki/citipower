@extends('layouts.app')

@section('title', 'Inventory Logs')

@section('content')
    <div class="page-header">
        <h2>Inventory Logs</h2>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Product</th>
                    <th>Type</th>
                    <th>Qty Change</th>
                    <th>Previous Stock</th>
                    <th>New Stock</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td style="white-space:nowrap; color:#666; font-size:12px;">
                        {{ $log->created_at->format('M d, Y h:i A') }}
                    </td>
                    <td><strong>{{ $log->product->name ?? '—' }}</strong></td>
                    <td>
                        @if($log->type === 'add')
                            <span class="badge badge-success">ADD</span>
                        @elseif($log->type === 'adjust')
                            <span class="badge badge-warning">ADJUST</span>
                        @else
                            <span class="badge badge-info">SALE</span>
                        @endif
                    </td>
                    <td>
                        @php $qty = $log->quantity; @endphp
                        <span style="font-weight:600; color:{{ $qty >= 0 ? '#2e7d32' : '#c62828' }}">
                            {{ $qty >= 0 ? '+' : '' }}{{ $qty }}
                        </span>
                    </td>
                    <td>{{ $log->previous_stock }}</td>
                    <td><strong>{{ $log->new_stock }}</strong></td>
                    <td style="color:#666;">{{ $log->note ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; color:#999; padding:30px;">
                        No inventory logs yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top:16px;">
            {{ $logs->links() }}
        </div>
    </div>
@endsection