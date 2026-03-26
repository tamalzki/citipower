@extends('layouts.app')
@section('title', $supplier->name . ' — Ledger')
@section('content')
    <div class="page-header">
        <div>
            <h2>{{ $supplier->name }}</h2>
            <p>Supplier Ledger — Deliveries & Payments</p>
        </div>
        <div style="display:flex; gap:8px;">
            <button onclick="document.getElementById('delivery-modal').style.display='flex'"
                    class="btn btn-secondary">+ Add Delivery (DR)</button>
            <button onclick="document.getElementById('payment-modal').style.display='flex'"
                    class="btn btn-primary">+ Record Payment</button>
            <a href="{{ route('supplier-ledger.index') }}" class="btn btn-secondary">← Back</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" style="display:flex; gap:10px; align-items:center;">
                <input type="text" name="search" class="form-control"
                       value="{{ $search ?? '' }}"
                       placeholder="Search DR number, date (e.g. 2025-01), notes..."
                       style="flex:1; max-width:420px;">
                <button type="submit" class="btn btn-primary">Search</button>
                @if(!empty($search))
                    <a href="{{ route('supplier-ledger.show', $supplier) }}" class="btn btn-secondary">Clear</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
        <div class="stat-card">
            <div class="stat-icon-box icon-blue">📦</div>
            <div class="stat-info">
                <div class="stat-number">₱{{ number_format($totalDelivered, 2) }}</div>
                <div class="stat-label">Total Delivered</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box icon-green">💳</div>
            <div class="stat-info">
                <div class="stat-number" style="color:#16a34a;">₱{{ number_format($totalPaid, 2) }}</div>
                <div class="stat-label">Total Paid</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box icon-red">⚖️</div>
            <div class="stat-info">
                <div class="stat-number" style="color:{{ $balance > 0 ? '#dc2626' : '#16a34a' }}; font-size:22px;">
                    ₱{{ number_format($balance, 2) }}
                </div>
                <div class="stat-label">Outstanding Balance</div>
            </div>
        </div>
    </div>

    {{-- Ledger Table --}}
    <div class="card">
        <div class="card-title">Transaction Ledger</div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>DR / Reference</th>
                        <th>Debit (Delivery)</th>
                        <th>Credit (Payment)</th>
                        <th>Running Balance</th>
                        <th>Notes</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                    <tr style="{{ $entry['type'] === 'payment' ? 'background:#f0fdf4;' : '' }}">
                        <td style="white-space:nowrap; font-weight:600;">
                            {{ \Carbon\Carbon::parse($entry['date'])->format('M d, Y') }}
                        </td>
                        <td>
                            @if($entry['type'] === 'delivery')
                                <span class="badge badge-info">Delivery</span>
                            @else
                                <span class="badge badge-success">Payment</span>
                            @endif
                        </td>
                        <td style="font-weight:600; color:#2563eb;">{{ $entry['dr'] }}</td>
                        <td style="font-weight:{{ $entry['debit'] > 0 ? '700' : '400' }}; color:{{ $entry['debit'] > 0 ? '#dc2626' : '#94a3b8' }};">
                            {{ $entry['debit'] > 0 ? '₱' . number_format($entry['debit'], 2) : '—' }}
                        </td>
                        <td style="font-weight:{{ $entry['credit'] > 0 ? '700' : '400' }}; color:{{ $entry['credit'] > 0 ? '#16a34a' : '#94a3b8' }};">
                            {{ $entry['credit'] > 0 ? '₱' . number_format($entry['credit'], 2) : '—' }}
                        </td>
                        <td style="font-weight:700; color:{{ $entry['running_balance'] > 0 ? '#dc2626' : '#16a34a' }};">
                            ₱{{ number_format($entry['running_balance'], 2) }}
                        </td>
                        <td style="font-size:12px; color:#64748b; max-width:200px;">{{ $entry['notes'] ?: '—' }}</td>
                        <td>
                            @if($entry['type'] === 'delivery')
                                <form action="{{ route('supplier-ledger.destroy-delivery', [$supplier, $entry['model']]) }}"
                                      method="POST" onsubmit="return confirm('Delete this delivery record?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm">✕</button>
                                </form>
                            @else
                                <form action="{{ route('supplier-ledger.destroy-payment', [$supplier, $entry['model']]) }}"
                                      method="POST" onsubmit="return confirm('Delete this payment record?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm">✕</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:30px; color:#94a3b8;">
                            No transactions recorded yet. Add a delivery or payment above.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($entries->count() > 0)
                <tfoot>
                    <tr style="background:#f8fafc; font-weight:700;">
                        <td colspan="3" style="padding:10px 12px; color:#64748b;">TOTAL</td>
                        <td style="padding:10px 12px; color:#dc2626;">₱{{ number_format($totalDelivered, 2) }}</td>
                        <td style="padding:10px 12px; color:#16a34a;">₱{{ number_format($totalPaid, 2) }}</td>
                        <td style="padding:10px 12px; color:{{ $balance > 0 ? '#dc2626' : '#16a34a' }}; font-size:16px;">
                            ₱{{ number_format($balance, 2) }}
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- ── Add Delivery Modal ── --}}
    <div id="delivery-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5);
         z-index:1000; align-items:center; justify-content:center; padding:20px;">
        <div style="background:#fff; border-radius:12px; padding:28px; width:100%; max-width:460px;
                    box-shadow:0 20px 60px rgba(0,0,0,.2);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="margin:0; font-size:16px;">Add Delivery (DR)</h3>
                <button onclick="document.getElementById('delivery-modal').style.display='none'"
                        style="background:none; border:none; font-size:20px; cursor:pointer; color:#64748b;">✕</button>
            </div>
            <form action="{{ route('supplier-ledger.store-delivery', $supplier) }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>DR Number *</label>
                    <input type="text" name="dr_number" class="form-control"
                           placeholder="e.g. #97861" required>
                </div>
                <div class="form-group">
                    <label>Delivery Date *</label>
                    <input type="date" name="delivery_date" class="form-control"
                           value="{{ now()->toDateString() }}" required>
                </div>
                <div class="form-group">
                    <label>Amount (₱) *</label>
                    <input type="number" name="amount" class="form-control"
                           placeholder="0.00" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label>Notes <span style="color:#94a3b8; font-weight:400;">(Optional)</span></label>
                    <textarea name="notes" class="form-control" rows="2"
                              placeholder="e.g. Delivered to main branch"></textarea>
                </div>
                <div style="display:flex; gap:8px; margin-top:4px;">
                    <button type="submit" class="btn btn-primary" style="flex:1;">Save Delivery</button>
                    <button type="button" onclick="document.getElementById('delivery-modal').style.display='none'"
                            class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Record Payment Modal ── --}}
    <div id="payment-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5);
         z-index:1000; align-items:center; justify-content:center; padding:20px;">
        <div style="background:#fff; border-radius:12px; padding:28px; width:100%; max-width:460px;
                    box-shadow:0 20px 60px rgba(0,0,0,.2);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <div>
                    <h3 style="margin:0; font-size:16px;">Record Payment to Supplier</h3>
                    @if($balance > 0)
                    <p style="margin:4px 0 0; font-size:12px; color:#dc2626;">
                        Outstanding: ₱{{ number_format($balance, 2) }}
                    </p>
                    @endif
                </div>
                <button onclick="document.getElementById('payment-modal').style.display='none'"
                        style="background:none; border:none; font-size:20px; cursor:pointer; color:#64748b;">✕</button>
            </div>
            <form action="{{ route('supplier-ledger.store-payment', $supplier) }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>Payment Date *</label>
                    <input type="date" name="payment_date" class="form-control"
                           value="{{ now()->toDateString() }}" required>
                </div>
                <div class="form-group">
                    <label>Amount (₱) *</label>
                    <input type="number" name="amount" class="form-control"
                           placeholder="0.00" step="0.01" min="0.01"
                           value="{{ $balance > 0 ? number_format($balance, 2, '.', '') : '' }}" required>
                </div>
                <div class="form-group">
                    <label>Payment Method *</label>
                    <select name="payment_method" class="form-control">
                        <option value="cash">Cash</option>
                        <option value="check">Check</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="e_wallet">E-Wallet</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Reference No <span style="color:#94a3b8; font-weight:400;">(Optional)</span></label>
                    <input type="text" name="reference_no" class="form-control" placeholder="Check/transfer reference">
                </div>
                <div class="form-group">
                    <label>Notes <span style="color:#94a3b8; font-weight:400;">(Optional)</span></label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
                <div style="display:flex; gap:8px; margin-top:4px;">
                    <button type="submit" class="btn btn-primary" style="flex:1;">Save Payment</button>
                    <button type="button" onclick="document.getElementById('payment-modal').style.display='none'"
                            class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Show modal if validation errors --}}
    @if($errors->any())
    <script>
        // Re-open whichever modal had the error based on session intent
        // Both modals will show validation errors via flash — simpler to just reopen last
    </script>
    @endif

    <script>
    // Close modals on backdrop click
    ['delivery-modal','payment-modal'].forEach(id => {
        document.getElementById(id).addEventListener('click', function(e) {
            if (e.target === this) this.style.display = 'none';
        });
    });
    </script>
@endsection
