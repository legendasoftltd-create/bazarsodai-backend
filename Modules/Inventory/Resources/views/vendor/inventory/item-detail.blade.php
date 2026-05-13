@extends('layouts.vendor.app')
@section('title', $item->name . ' — ' . translate('Inventory Detail'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-layers-outlined"></i></span>
            <span>{{ $item->name }} — {{ translate('Inventory Detail') }}</span>
        </h1>
        <a href="{{ route('vendor.inventory.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="tio-arrow-backward"></i> {{ translate('Back') }}
        </a>
    </div>

    {{-- Stock Summary --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="{{ $item->stock <= 0 ? 'text-danger' : 'text-success' }}">{{ $item->stock }}</h3>
                    <p class="text-muted mb-0">{{ translate('Current Stock') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3>{{ number_format($item->average_cost, 2) }}</h3>
                    <p class="text-muted mb-0">{{ translate('Avg Cost') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3>{{ number_format($totalValue, 2) }}</h3>
                    <p class="text-muted mb-0">{{ translate('Stock Value') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3>{{ $reorderPoint?->reorder_at ?? '—' }}</h3>
                    <p class="text-muted mb-0">{{ translate('Reorder At') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        {{-- Opening Stock (only when stock is 0) --}}
        @if($item->stock == 0)
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-header"><h5 class="mb-0">{{ translate('Enter Opening Stock') }}</h5></div>
                <div class="card-body">
                    <form action="{{ route('vendor.inventory.opening-stock') }}" method="POST">
                        @csrf
                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                        <div class="form-group">
                            <label>{{ translate('Quantity') }}</label>
                            <input type="number" name="qty" class="form-control" step="0.01" min="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Unit Cost') }}</label>
                            <input type="number" name="unit_cost" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Note') }}</label>
                            <input type="text" name="note" class="form-control">
                        </div>
                        <button class="btn btn-success btn-block">{{ translate('Save') }}</button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- Record Loss --}}
        <div class="col-md-4">
            <div class="card border-danger">
                <div class="card-header bg-soft-danger"><h5 class="mb-0 text-danger">{{ translate('Record Loss') }}</h5></div>
                <div class="card-body">
                    <form id="lossForm" method="POST">
                        @csrf
                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                        <div class="form-group">
                            <label>{{ translate('Type') }}</label>
                            <select class="form-control" id="lossType">
                                <option value="{{ route('vendor.inventory.damaged') }}">{{ translate('Damaged') }}</option>
                                <option value="{{ route('vendor.inventory.broken') }}">{{ translate('Broken') }}</option>
                                <option value="{{ route('vendor.inventory.internal-use') }}">{{ translate('Internal Use') }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Quantity') }}</label>
                            <input type="number" name="qty" class="form-control" step="0.01" min="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Note') }}</label>
                            <input type="text" name="note" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-danger btn-block">{{ translate('Record') }}</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Reorder Point --}}
        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-header bg-soft-info"><h5 class="mb-0">{{ translate('Reorder Point') }}</h5></div>
                <div class="card-body">
                    <form action="{{ route('vendor.inventory.reorder-points.set') }}" method="POST">
                        @csrf
                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                        <div class="form-group">
                            <label>{{ translate('Alert When Stock') }} &le;</label>
                            <input type="number" name="reorder_at" class="form-control" value="{{ $reorderPoint?->reorder_at }}" required min="0">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Reorder Quantity') }}</label>
                            <input type="number" name="reorder_qty" class="form-control" value="{{ $reorderPoint?->reorder_qty }}" required min="0">
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="autoNotify" name="auto_notify" value="1" {{ $reorderPoint?->auto_notify ? 'checked' : '' }}>
                                <label class="custom-control-label" for="autoNotify">{{ translate('Email notification') }}</label>
                            </div>
                        </div>
                        <button class="btn btn-info btn-block">{{ translate('Save') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Valuation Method Override --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-secondary">
                <div class="card-header"><h5 class="mb-0">{{ translate('Valuation Method') }}</h5></div>
                <div class="card-body">
                    <p class="text-muted small mb-2">
                        {{ translate('Store default') }}: <strong>{{ $item->store?->config?->inventory_valuation_method ?? 'average' }}</strong>
                    </p>
                    <form action="{{ route('vendor.inventory.item-valuation', $item->id) }}" method="POST" class="d-flex gap-2 align-items-center">
                        @csrf
                        <select name="valuation_method" class="form-control">
                            <option value="" {{ empty($item->valuation_method) ? 'selected' : '' }}>{{ translate('Use store default') }}</option>
                            <option value="average" {{ $item->valuation_method === 'average' ? 'selected' : '' }}>{{ translate('Average Cost') }}</option>
                            <option value="fifo"    {{ $item->valuation_method === 'fifo'    ? 'selected' : '' }}>FIFO</option>
                            <option value="lifo"    {{ $item->valuation_method === 'lifo'    ? 'selected' : '' }}>LIFO</option>
                        </select>
                        <button class="btn btn-secondary btn-sm ml-2">{{ translate('Save') }}</button>
                    </form>
                </div>
            </div>
        </div>
        @if($batches->count())
        <div class="col-md-6">
            <div class="card border-secondary">
                <div class="card-header"><h5 class="mb-0">{{ translate('Active Batches') }} <span class="badge badge-soft-secondary">{{ $batches->count() }}</span></h5></div>
                <div class="card-body p-0">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('Batch') }}</th>
                                <th>{{ translate('Remaining') }}</th>
                                <th>{{ translate('Cost') }}</th>
                                <th>{{ translate('Expires') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($batches as $batch)
                            @php $expiring = $batch->expires_at && $batch->expires_at->lte(now()->addDays(30)); @endphp
                            <tr class="{{ $expiring ? 'table-warning' : '' }}">
                                <td>{{ $batch->batch_number ?? "#{$batch->id}" }}</td>
                                <td><strong>{{ $batch->qty_remaining }}</strong></td>
                                <td>{{ number_format($batch->unit_cost, 2) }}</td>
                                <td>
                                    @if($batch->expires_at)
                                        <span class="{{ $expiring ? 'text-warning' : '' }}">
                                            {{ $batch->expires_at->format('d M Y') }}
                                        </span>
                                    @else —
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Movement History --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ translate('Movement History') }}</h5>
            <form method="GET" class="d-flex gap-2">
                <select name="type" class="form-control form-control-sm">
                    <option value="">{{ translate('All Types') }}</option>
                    @foreach(['opening','purchase','purchase_return','sale','sale_return','damaged','broken','internal_use','adjustment_add','adjustment_sub','transfer_in','transfer_out'] as $t)
                        <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $t)) }}</option>
                    @endforeach
                </select>
                <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                <button type="submit" class="btn btn-sm btn-primary">{{ translate('Go') }}</button>
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
                            <th>{{ translate('Stock Before') }}</th>
                            <th>{{ translate('Stock After') }}</th>
                            <th>{{ translate('Unit Cost') }}</th>
                            <th>{{ translate('Note') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $m)
                        @php $colors = ['sale'=>'danger','purchase'=>'success','opening'=>'info','damaged'=>'warning','broken'=>'warning','sale_return'=>'success','purchase_return'=>'warning','transfer_in'=>'info','transfer_out'=>'secondary','adjustment_add'=>'success','adjustment_sub'=>'danger','internal_use'=>'warning']; @endphp
                        <tr>
                            <td>{{ $m->created_at->format('d M Y H:i') }}</td>
                            <td><span class="badge badge-soft-{{ $colors[$m->type] ?? 'secondary' }}">{{ ucwords(str_replace('_', ' ', $m->type)) }}</span></td>
                            <td class="text-success">{{ $m->qty_in > 0 ? '+' . $m->qty_in : '—' }}</td>
                            <td class="text-danger">{{ $m->qty_out > 0 ? '-' . $m->qty_out : '—' }}</td>
                            <td>{{ $m->stock_before }}</td>
                            <td><strong>{{ $m->stock_after }}</strong></td>
                            <td>{{ number_format($m->unit_cost, 2) }}</td>
                            <td class="text-muted small">{{ $m->note ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center py-4">{{ translate('No movements yet') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $movements->links() }}</div>
        </div>
    </div>
</div>

@push('script')
<script>
    document.getElementById('lossForm').addEventListener('submit', function(e) {
        this.action = document.getElementById('lossType').value;
    });
</script>
@endpush
@endsection
