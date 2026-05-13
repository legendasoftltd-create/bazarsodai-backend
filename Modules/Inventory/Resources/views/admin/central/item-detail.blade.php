@extends('layouts.admin.app')
@section('title', translate('Item Inventory Detail'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-layers-outlined"></i></span>
            <span>{{ $item->name }} — {{ translate('Inventory Detail') }}</span>
        </h1>
        <a href="{{ route('admin.inventory.central') }}" class="btn btn-outline-secondary btn-sm">
            <i class="tio-arrow-backward"></i> {{ translate('Back') }}
        </a>
    </div>

    {{-- Stock Summary --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="{{ $item->stock <= 0 ? 'text-danger' : 'text-success' }}">{{ $item->stock }}</h4>
                    <p class="text-muted mb-0">{{ translate('Current Stock') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4>{{ number_format($item->average_cost, 2) }}</h4>
                    <p class="text-muted mb-0">{{ translate('Avg Cost') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4>{{ number_format($totalValue, 2) }}</h4>
                    <p class="text-muted mb-0">{{ translate('Stock Value') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4>{{ $item->valuation_method ?? $item->store?->config?->inventory_valuation_method ?? 'average' }}</h4>
                    <p class="text-muted mb-0">{{ translate('Valuation Method') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Special Transaction Forms --}}
    <div class="row mb-4">
        {{-- Opening Stock --}}
        @if($item->stock == 0)
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ translate('Opening Stock') }}</h5></div>
                <div class="card-body">
                    <form action="{{ route('admin.inventory.opening-stock') }}" method="POST">
                        @csrf
                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                        <div class="form-group">
                            <label>{{ translate('Quantity') }}</label>
                            <input type="number" name="qty" step="0.01" class="form-control" required min="0.01">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Unit Cost') }}</label>
                            <input type="number" name="unit_cost" step="0.01" class="form-control" required min="0">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Note') }}</label>
                            <input type="text" name="note" class="form-control">
                        </div>
                        <button class="btn btn-success btn-block">{{ translate('Save Opening Stock') }}</button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- Damaged --}}
        <div class="col-md-4">
            <div class="card border-danger">
                <div class="card-header bg-soft-danger"><h5 class="mb-0 text-danger">{{ translate('Record Damaged / Broken') }}</h5></div>
                <div class="card-body">
                    <form action="{{ route('admin.inventory.damaged') }}" method="POST">
                        @csrf
                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                        <div class="form-group">
                            <label>{{ translate('Type') }}</label>
                            <select name="_route" class="form-control" id="dmg-type">
                                <option value="damaged">{{ translate('Damaged') }}</option>
                                <option value="broken">{{ translate('Broken') }}</option>
                                <option value="internal_use">{{ translate('Internal Use') }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Quantity') }}</label>
                            <input type="number" name="qty" step="0.01" class="form-control" required min="0.01">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Note') }}</label>
                            <input type="text" name="note" class="form-control">
                        </div>
                        <button class="btn btn-danger btn-block">{{ translate('Record Loss') }}</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Reorder Point --}}
        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-header bg-soft-info"><h5 class="mb-0">{{ translate('Reorder Point') }}</h5></div>
                <div class="card-body">
                    <form action="{{ route('admin.inventory.reorder-points.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                        <input type="hidden" name="store_id" value="{{ $item->store_id }}">
                        <div class="form-group">
                            <label>{{ translate('Alert When Stock') }} &le;</label>
                            <input type="number" name="reorder_at" class="form-control" value="{{ $reorderPoint?->reorder_at ?? '' }}" required min="0">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Suggested Reorder Qty') }}</label>
                            <input type="number" name="reorder_qty" class="form-control" value="{{ $reorderPoint?->reorder_qty ?? '' }}" required min="0">
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="autoNotify" name="auto_notify" value="1" {{ $reorderPoint?->auto_notify ? 'checked' : 'checked' }}>
                                <label class="custom-control-label" for="autoNotify">{{ translate('Email notification') }}</label>
                            </div>
                        </div>
                        <button class="btn btn-info btn-block">{{ translate('Save') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Valuation Method Override + Store Valuation --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-secondary">
                <div class="card-header"><h5 class="mb-0">{{ translate('Item Valuation Override') }}</h5></div>
                <div class="card-body">
                    <p class="text-muted small mb-2">
                        {{ translate('Override the store default for this item only.') }}
                        {{ translate('Store default') }}: <strong>{{ $item->store?->config?->inventory_valuation_method ?? 'average' }}</strong>
                    </p>
                    <form action="{{ route('admin.inventory.item-valuation', $item->id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <select name="valuation_method" class="form-control">
                                <option value="" {{ empty($item->valuation_method) ? 'selected' : '' }}>{{ translate('Use store default') }}</option>
                                <option value="average" {{ $item->valuation_method === 'average' ? 'selected' : '' }}>{{ translate('Average Cost') }}</option>
                                <option value="fifo"    {{ $item->valuation_method === 'fifo'    ? 'selected' : '' }}>FIFO</option>
                                <option value="lifo"    {{ $item->valuation_method === 'lifo'    ? 'selected' : '' }}>LIFO</option>
                            </select>
                        </div>
                        <button class="btn btn-secondary">{{ translate('Save Override') }}</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-secondary">
                <div class="card-header"><h5 class="mb-0">{{ translate('Store Valuation Method') }}</h5></div>
                <div class="card-body">
                    <p class="text-muted small mb-2">{{ translate('Default for all items in this store.') }}</p>
                    <form action="{{ route('admin.inventory.store-valuation', $item->store_id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <select name="inventory_valuation_method" class="form-control">
                                <option value="average" {{ ($item->store?->config?->inventory_valuation_method ?? 'average') === 'average' ? 'selected' : '' }}>{{ translate('Average Cost') }}</option>
                                <option value="fifo"    {{ ($item->store?->config?->inventory_valuation_method ?? '') === 'fifo'    ? 'selected' : '' }}>FIFO</option>
                                <option value="lifo"    {{ ($item->store?->config?->inventory_valuation_method ?? '') === 'lifo'    ? 'selected' : '' }}>LIFO</option>
                            </select>
                        </div>
                        <button class="btn btn-secondary">{{ translate('Save') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Active Batches --}}
    @if($batches->count())
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">{{ translate('Active Batches') }}</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Batch #') }}</th>
                            <th>{{ translate('Method') }}</th>
                            <th>{{ translate('Initial Qty') }}</th>
                            <th>{{ translate('Remaining') }}</th>
                            <th>{{ translate('Unit Cost') }}</th>
                            <th>{{ translate('Expires') }}</th>
                            <th>{{ translate('Received') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($batches as $batch)
                        @php
                            $expiring = $batch->expires_at && $batch->expires_at->lte(now()->addDays(30));
                            $expired  = $batch->expires_at && $batch->expires_at->lt(now());
                        @endphp
                        <tr class="{{ $expired ? 'table-danger' : ($expiring ? 'table-warning' : '') }}">
                            <td>{{ $batch->batch_number ?? "Batch #{$batch->id}" }}</td>
                            <td><span class="badge badge-soft-info">{{ strtoupper($batch->valuation_method) }}</span></td>
                            <td>{{ $batch->qty_initial }}</td>
                            <td><strong>{{ $batch->qty_remaining }}</strong></td>
                            <td>{{ number_format($batch->unit_cost, 2) }}</td>
                            <td>
                                @if($batch->expires_at)
                                    <span class="{{ $expired ? 'text-danger' : ($expiring ? 'text-warning' : '') }}">
                                        {{ $batch->expires_at->format('d M Y') }}
                                        @if($expired) <small>({{ translate('Expired') }})</small>
                                        @elseif($expiring) <small>({{ now()->diffInDays($batch->expires_at) }}d)</small>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $batch->created_at->format('d M Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Movement History --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ translate('Movement History') }}</h5>
            <form method="GET" class="d-flex gap-2">
                <select name="type" class="form-control form-control-sm">
                    <option value="">{{ translate('All Types') }}</option>
                    @foreach(['opening','purchase','purchase_return','sale','sale_return','damaged','broken','internal_use','adjustment_add','adjustment_sub','transfer_in','transfer_out'] as $t)
                        <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ translate(ucwords(str_replace('_', ' ', $t))) }}</option>
                    @endforeach
                </select>
                <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                <button type="submit" class="btn btn-sm btn-primary">{{ translate('Filter') }}</button>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-nowrap table-align-middle">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Date') }}</th>
                            <th>{{ translate('Type') }}</th>
                            <th>{{ translate('In') }}</th>
                            <th>{{ translate('Out') }}</th>
                            <th>{{ translate('Before') }}</th>
                            <th>{{ translate('After') }}</th>
                            <th>{{ translate('Unit Cost') }}</th>
                            <th>{{ translate('Total Cost') }}</th>
                            <th>{{ translate('Method') }}</th>
                            <th>{{ translate('Note') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $m)
                        <tr>
                            <td>{{ $m->created_at->format('d M Y H:i') }}</td>
                            <td>
                                @php
                                    $colors = ['sale'=>'danger','purchase'=>'success','opening'=>'info','damaged'=>'warning','broken'=>'warning','sale_return'=>'success','purchase_return'=>'warning','transfer_in'=>'info','transfer_out'=>'secondary','adjustment_add'=>'success','adjustment_sub'=>'danger','internal_use'=>'warning'];
                                @endphp
                                <span class="badge badge-soft-{{ $colors[$m->type] ?? 'secondary' }}">{{ translate(ucwords(str_replace('_', ' ', $m->type))) }}</span>
                            </td>
                            <td class="text-success">{{ $m->qty_in > 0 ? '+' . $m->qty_in : '—' }}</td>
                            <td class="text-danger">{{ $m->qty_out > 0 ? '-' . $m->qty_out : '—' }}</td>
                            <td>{{ $m->stock_before }}</td>
                            <td><strong>{{ $m->stock_after }}</strong></td>
                            <td>{{ number_format($m->unit_cost, 2) }}</td>
                            <td>{{ number_format($m->total_cost, 2) }}</td>
                            <td><span class="badge badge-soft-info">{{ strtoupper($m->valuation_method) }}</span></td>
                            <td class="text-muted small">{{ $m->note ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center py-4">{{ translate('No movements found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $movements->links() }}</div>
        </div>
    </div>
</div>
@endsection
