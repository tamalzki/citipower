@extends('layouts.app')
@section('title', 'Record Stock Transfer')
@section('content')
    <div class="page-header">
        <div><h2>Record Stock Transfer</h2><p>Log a stock movement between branches</p></div>
        <a href="{{ route('stock-transfers.index') }}" class="btn btn-secondary">← Back</a>
    </div>

    <div class="card" style="max-width:540px;">
        <div class="card-title">Transfer Details</div>
        <div class="card-body">
            <form action="{{ route('stock-transfers.store') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label>Product *</label>
                    <select name="product_id" class="form-control" required>
                        <option value="">Select Product...</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }}{{ $p->sku ? ' — ' . $p->sku : '' }}
                                (Stock: {{ $p->stock_quantity }})
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')<small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small>@enderror
                </div>

                <div style="display:grid; grid-template-columns:1fr auto 1fr; gap:12px; align-items:end; margin-bottom:14px;">
                    <div class="form-group" style="margin:0;">
                        <label>From Branch *</label>
                        <select name="from_branch_id" class="form-control" required>
                            <option value="">Select...</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ old('from_branch_id') == $b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('from_branch_id')<small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small>@enderror
                    </div>
                    <div style="padding-bottom:8px; color:#64748b; font-size:18px; font-weight:700;">→</div>
                    <div class="form-group" style="margin:0;">
                        <label>To Branch *</label>
                        <select name="to_branch_id" class="form-control" required>
                            <option value="">Select...</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ old('to_branch_id') == $b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('to_branch_id')<small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small>@enderror
                    </div>
                </div>

                <div class="form-group">
                    <label>Quantity *</label>
                    <input type="number" name="quantity" class="form-control"
                           value="{{ old('quantity') }}" min="1" placeholder="e.g. 10" required>
                    @error('quantity')<small style="color:#dc2626; font-size:11px; display:block; margin-top:4px;">{{ $message }}</small>@enderror
                </div>

                <div class="form-group">
                    <label>Note <span style="color:#94a3b8; font-weight:400;">(Optional)</span></label>
                    <textarea name="note" class="form-control" rows="2"
                              placeholder="e.g. Monthly restock from warehouse to main branch">{{ old('note') }}</textarea>
                </div>

                <div style="display:flex; gap:8px; margin-top:6px;">
                    <button type="submit" class="btn btn-primary">Record Transfer</button>
                    <a href="{{ route('stock-transfers.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
