@extends('layouts.app')
@section('title', 'Stock Transfers')
@section('content')
    <div class="page-header">
        <div><h2>Stock Transfers</h2><p>Fixed route: Davao - Main Branch → Digos - Second Branch</p></div>
        <a href="{{ route('stock-transfers.create') }}" class="btn btn-primary">+ New Transfer</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('stock-transfers.index') }}"
                  style="display:flex; gap:10px; align-items:center;">
                <input type="text" name="search" class="form-control"
                       value="{{ $search }}"
                       placeholder="Search by product name, branch..."
                       style="flex:1; max-width:420px;">
                <button type="submit" class="btn btn-primary">Search</button>
                @if($search)
                    <a href="{{ route('stock-transfers.index') }}" class="btn btn-secondary">Clear</a>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Branch Product Totals</div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Branch</th>
                        <th style="text-align:right;">QTY</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-weight:700;">{{ $mainBranch->name }}</td>
                        <td style="text-align:right; font-weight:700;">{{ number_format($branchTotals['main']) }}</td>
                        <td style="display:flex; gap:8px;">
                            <button type="button" class="btn btn-secondary btn-sm view-branch-btn"
                                    data-branch="{{ $mainBranch->name }}"
                                    data-products='@json($mainBranchProducts)'>
                                View
                            </button>
                            <a href="{{ route('stock-transfers.create') }}" class="btn btn-primary btn-sm">Transfer</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight:700;">{{ $secondBranch->name }}</td>
                        <td style="text-align:right; font-weight:700;">{{ number_format($branchTotals['second']) }}</td>
                        <td style="display:flex; gap:8px;">
                            <button type="button" class="btn btn-secondary btn-sm view-branch-btn"
                                    data-branch="{{ $secondBranch->name }}"
                                    data-products='@json($secondBranchProducts)'>
                                View
                            </button>
                            <a href="{{ route('stock-transfers.create') }}" class="btn btn-primary btn-sm">Transfer</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Product</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Qty</th>
                        <th>Note</th>
                        <th>Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $t)
                    <tr>
                        <td style="color:#94a3b8; font-size:12px;">{{ $t->id }}</td>
                        <td style="white-space:nowrap;">
                            <div style="font-weight:600;">{{ $t->created_at->format('M d, Y') }}</div>
                            <div style="font-size:11px; color:#94a3b8;">{{ $t->created_at->format('h:i A') }}</div>
                        </td>
                        <td style="font-weight:600;">{{ $t->product->name }}</td>
                        <td>
                            <span class="badge badge-gray">{{ $t->fromBranch->code }}</span>
                            {{ $t->fromBranch->name }}
                        </td>
                        <td>
                            <span class="badge badge-info">{{ $t->toBranch->code }}</span>
                            {{ $t->toBranch->name }}
                        </td>
                        <td style="font-weight:700; font-size:15px;">{{ number_format($t->quantity) }}</td>
                        <td style="color:#64748b; font-size:13px;">{{ $t->note ?: '—' }}</td>
                        <td style="color:#64748b; font-size:13px;">{{ $t->transferredBy->name }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <div class="empty-icon">📦</div>
                                <p>No stock transfers recorded yet.</p>
                                <a href="{{ route('stock-transfers.create') }}" class="btn btn-primary">Record First Transfer</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $transfers->links() }}</div>

    {{-- Branch products modal --}}
    <div id="branch-products-modal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); z-index:1100; align-items:center; justify-content:center; padding:20px;">
        <div style="background:#fff; border-radius:14px; width:100%; max-width:680px; max-height:88vh; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 20px 60px rgba(15,23,42,.22);">
            <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h3 id="branch-products-title" style="margin:0; font-size:16px; font-weight:700;">Branch Products</h3>
                    <p style="margin:2px 0 0; font-size:12px; color:#64748b;">All products and quantities in this branch</p>
                </div>
                <button type="button" id="branch-products-close" style="background:none; border:none; font-size:20px; cursor:pointer; color:#94a3b8;">✕</button>
            </div>
            <div style="overflow:auto; padding:0;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f8fafc;">
                            <th style="padding:10px 14px; text-align:left;">Product</th>
                            <th style="padding:10px 14px; text-align:left;">SKU</th>
                            <th style="padding:10px 14px; text-align:right;">QTY</th>
                        </tr>
                    </thead>
                    <tbody id="branch-products-body"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('branch-products-modal');
            const closeBtn = document.getElementById('branch-products-close');
            const title = document.getElementById('branch-products-title');
            const body = document.getElementById('branch-products-body');

            function openModal() {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
            function closeModal() {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }

            closeBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

            document.querySelectorAll('.view-branch-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    title.textContent = btn.dataset.branch + ' — Products';
                    const products = JSON.parse(btn.dataset.products || '[]');
                    body.innerHTML = products.map((p) => `
                        <tr>
                            <td style="padding:10px 14px; border-bottom:1px solid #f1f5f9; font-weight:600;">${escapeHtml(p.name ?? '—')}</td>
                            <td style="padding:10px 14px; border-bottom:1px solid #f1f5f9; color:#64748b;">${escapeHtml(p.sku ?? '—')}</td>
                            <td style="padding:10px 14px; border-bottom:1px solid #f1f5f9; text-align:right; font-weight:700;">${Number(p.qty || 0).toLocaleString()}</td>
                        </tr>
                    `).join('');
                    openModal();
                });
            });

            function escapeHtml(str) {
                const d = document.createElement('div');
                d.textContent = String(str ?? '');
                return d.innerHTML;
            }
        })();
    </script>
@endsection
