@extends('layouts.admin.app')
@section('title', translate('Low Stock Report'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-warning-outlined"></i></span>
            <span>{{ translate('Low Stock Report') }}</span>
        </h1>
        <a href="{{ route('admin.inventory.reorder-points.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="tio-bell-outlined"></i> {{ translate('Manage Reorder Points') }}
        </a>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <select name="store_id" class="form-control">
                        <option value="">{{ translate('All Vendors') }}</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="filter" class="form-control">
                        <option value="reorder" {{ request('filter','reorder') === 'reorder' ? 'selected' : '' }}>{{ translate('Below Reorder Point') }}</option>
                        <option value="out"     {{ request('filter') === 'out' ? 'selected' : '' }}>{{ translate('Out of Stock') }}</option>
                        <option value="low"     {{ request('filter') === 'low' ? 'selected' : '' }}>{{ translate('Low Stock (threshold)') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-block">{{ translate('Filter') }}</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.inventory.reports.low-stock') }}" class="btn btn-outline-secondary btn-block">{{ translate('Reset') }}</a>
                </div>
                <div class="col-md-2 text-right">
                    <a href="{{ route('admin.inventory.reports.export', 'low-stock') }}" class="btn btn-outline-success btn-block">
                        <i class="tio-file-text-outlined"></i> {{ translate('Export') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card text-center py-2 border-danger">
                <h4 class="text-danger mb-0">{{ $outOfStockCount }}</h4>
                <p class="text-muted small mb-0">{{ translate('Out of Stock') }}</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center py-2 border-warning">
                <h4 class="text-warning mb-0">{{ $belowReorderCount }}</h4>
                <p class="text-muted small mb-0">{{ translate('Below Reorder Point') }}</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center py-2 border-info">
                <h4 class="text-info mb-0">{{ $lowStockCount }}</h4>
                <p class="text-muted small mb-0">{{ translate('Low Stock (threshold)') }}</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center py-2">
                <h4 class="mb-0">{{ $totalItems }}</h4>
                <p class="text-muted small mb-0">{{ translate('Total Items') }}</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Item') }}</th>
                            <th>{{ translate('Vendor') }}</th>
                            <th>{{ translate('Current Stock') }}</th>
                            <th>{{ translate('Reorder At') }}</th>
                            <th>{{ translate('Reorder Qty') }}</th>
                            <th>{{ translate('Avg Cost') }}</th>
                            <th>{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                        @php
                            $rp    = $item->reorderPoints->first();
                            $below = $rp && $item->stock <= $rp->reorder_at;
                        @endphp
                        <tr>
                            <td><strong>{{ $item->name }}</strong></td>
                            <td>{{ $item->store?->name ?? '—' }}</td>
                            <td>
                                <span class="{{ $item->stock <= 0 ? 'text-danger' : 'text-warning' }} font-weight-bold">
                                    {{ $item->stock }}
                                </span>
                            </td>
                            <td>{{ $rp?->reorder_at ?? '—' }}</td>
                            <td>{{ $rp?->reorder_qty ?? '—' }}</td>
                            <td>{{ number_format($item->average_cost, 2) }}</td>
                            <td>
                                <a href="{{ route('admin.inventory.item-detail', $item->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="tio-visible-outlined"></i>
                                </a>
                                <a href="{{ route('admin.inventory.purchases.create') }}" class="btn btn-sm btn-outline-success">
                                    {{ translate('Reorder') }}
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-4">{{ translate('No items match this filter') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $items->links() }}</div>
        </div>
    </div>
</div>
@endsection
