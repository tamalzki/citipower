@extends('layouts.app')

@section('title', 'Sale Details')

@section('content')
    <div class="page-header">
        <div>
            <h2>Sale #{{ $sale->id }}</h2>
            <p>{{ $sale->created_at->format('F d, Y — h:i A') }}</p>
        </div>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('sales.index') }}" class="btn btn-secondary">← Back</a>
            @if(auth()->user()->role === 'owner')
            <form action="{{ route('sales.destroy', $sale) }}" method="POST"
                  onsubmit="return confirm('Void this sale? Stock will be restored.')">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger">Void Sale</button>
            </form>
            @endif
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 300px; gap:16px; align-items:start;">

        {{-- Items Table --}}
        <div class="card">
            <div class="card-title">🛒 Items Sold</div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Purchase Price</th>
                            <th>Selling Price</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                            <th>Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->items as $item)
                        <tr>
                            <td style="color:#94a3b8; font-size:12px;">{{ $loop->iteration }}</td>
                            <td><strong>{{ $item->product->name ?? 'Deleted Product' }}</strong></td>
                            <td>₱{{ number_format($item->purchase_price, 2) }}</td>
                            <td>₱{{ number_format($item->price, 2) }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td style="font-weight:600;">₱{{ number_format($item->subtotal, 2) }}</td>
                            <td>
                                @php
                                    $profit = ($item->price - $item->purchase_price) * $item->quantity;
                                @endphp
                                <span style="font-weight:600; color:{{ $profit >= 0 ? '#16a34a' : '#dc2626' }}">
                                    ₱{{ number_format($profit, 2) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Summary --}}
        <div class="card">
            <div class="card-title">📋 Summary</div>
            <div class="card-body">
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <div style="display:flex; justify-content:space-between; font-size:13px;">
                        <span style="color:#64748b;">Total Items</span>
                        <strong>{{ $sale->items->count() }}</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:13px;">
                        <span style="color:#64748b;">Total Qty</span>
                        <strong>{{ $sale->items->sum('quantity') }}</strong>
                    </div>
                    <div style="border-top:1px solid #f1f5f9; padding-top:10px;
                                display:flex; justify-content:space-between; font-size:13px;">
                        <span style="color:#64748b;">Subtotal</span>
                        <strong>₱{{ number_format($sale->items->sum('subtotal'), 2) }}</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:13px;">
                        <span style="color:#64748b;">Discount</span>
                        <strong style="color:#dc2626;">−₱{{ number_format($sale->discount_amount ?? 0, 2) }}</strong>
                    </div>
                    <div style="border-top:1px solid #f1f5f9; padding-top:10px;
                                display:flex; justify-content:space-between;">
                        <span style="font-size:13px; color:#64748b; font-weight:600;">TOTAL</span>
                        <span style="font-size:20px; font-weight:700; color:#0f172a;">
                            ₱{{ number_format($sale->total_amount, 2) }}
                        </span>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:13px;">
                        <span style="color:#64748b;">Paid</span>
                        <strong style="color:#16a34a;">₱{{ number_format($sale->paid_amount, 2) }}</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:13px;">
                        <span style="color:#64748b;">Balance</span>
                        <strong style="color:#dc2626;">₱{{ number_format($sale->balance_due, 2) }}</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:13px;">
                        <span style="color:#64748b;">Payment Status</span>
                        <span class="badge {{ $sale->payment_status === 'paid' ? 'badge-success' : ($sale->payment_status === 'partial' ? 'badge-warning' : 'badge-danger') }}">
                            {{ ucfirst($sale->payment_status) }}
                        </span>
                    </div>
                    @php
                        $totalProfit = $sale->items->sum(fn($i) =>
                            ($i->price - $i->purchase_price) * $i->quantity);
                    @endphp
                    <div style="display:flex; justify-content:space-between; font-size:13px;">
                        <span style="color:#64748b;">Total Profit</span>
                        <strong style="color:{{ $totalProfit >= 0 ? '#16a34a' : '#dc2626' }}">
                            ₱{{ number_format($totalProfit, 2) }}
                        </strong>
                    </div>
                    <div style="display:flex; gap:10px; margin-top:8px; flex-wrap:wrap;">
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px;
                                    padding:8px 12px; flex:1;">
                            <div style="font-size:10px; color:#64748b; font-weight:600; text-transform:uppercase;">Person in Charge</div>
                            <div style="font-size:13px; margin-top:2px; font-weight:600;">{{ $sale->poc ?: '—' }}</div>
                        </div>
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px;
                                    padding:8px 12px; flex:1;">
                            <div style="font-size:10px; color:#64748b; font-weight:600; text-transform:uppercase;">Receipt Issued</div>
                            <div style="margin-top:2px;">
                                @if($sale->issued_receipt)
                                    <span class="badge badge-success">Yes</span>
                                @else
                                    <span class="badge badge-gray">No</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @if($sale->note)
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px;
                                padding:10px 12px; margin-top:8px;">
                        <div style="font-size:10.5px; color:#64748b; font-weight:600;
                                    text-transform:uppercase; letter-spacing:0.5px;">Note</div>
                        <div style="font-size:13px; margin-top:3px;">{{ $sale->note }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    <div class="card" style="margin-top:16px;">
        <div class="card-title">Payments</div>
        <div class="card-body">
            @if(auth()->user()->hasRole(['owner', 'cashier']) && $sale->balance_due > 0)
                <form method="POST" action="{{ route('sales.payments.store', $sale) }}" style="display:grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap:10px; align-items:end; margin-bottom:14px;">
                    @csrf
                    <div class="form-group" style="margin:0;">
                        <label>Date</label>
                        <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label>Amount</label>
                        <input type="number" name="amount" class="form-control" min="0.01" step="0.01" max="{{ $sale->balance_due }}" placeholder="0.00" required>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label>Method</label>
                        <select name="payment_method" class="form-control">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="e_wallet">E-Wallet</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label>Reference</label>
                        <input type="text" name="reference_no" class="form-control" placeholder="Optional">
                    </div>
                    <button type="submit" class="btn btn-primary">Add Payment</button>
                </form>
            @endif

            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($sale->payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date->format('M d, Y h:i A') }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                            <td>{{ $payment->reference_no ?: '—' }}</td>
                            <td style="font-weight:600;">₱{{ number_format($payment->amount, 2) }}</td>
                            <td>
                                @if(auth()->user()->role === 'owner')
                                <form action="{{ route('sales.payments.destroy', [$sale, $payment]) }}" method="POST" onsubmit="return confirm('Delete this payment?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="color:#94a3b8;">No payments yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection