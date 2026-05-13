@extends('layouts.vendor.app')
@section('title', translate('My Inventory'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-layers-outlined"></i></span>
            <span>{{ translate('My Inventory') }}
                <span class="badge badge-soft-secondary">{{ $items->total() }}</span>
            </span>
        </h1>
        <div class="page-header-content mt-1">
            <span class="text-muted">{{ translate('Total Stock Value') }}:</span>
            <strong class="ml-1">{{ number_format($totalValue, 2) }}</strong>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card text-center py-2">
                <h4 class="text-success mb-0">{{ $items->where('stock', '>', 0)->count() }}</h4>
                <p class="text-muted small mb-0">{{ translate('In Stock') }}</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center py-2">
                <h4 class="text-warning mb-0">{{ $items->where('stock', '>', 0)->where('stock', '<=', $store->config?->minimum_stock_for_warning ?? 10)->count() }}</h4>
                <p class="text-muted small mb-0">{{ translate('Low Stock') }}</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center py-2">
                <h4 class="text-danger mb-0">{{ $items->where('stock', '<=', 0)->count() }}</h4>
                <p class="text-muted small mb-0">{{ translate('Out of Stock') }}</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center py-2">
                <h4 class="text-info mb-0">{{ number_format($totalValue, 0) }}</h4>
                <p class="text-muted small mb-0">{{ translate('Stock Value') }}</p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="{{ translate('Search item') }}" value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="stock_status" class="form-control">
                        <option value="">{{ translate('All Stock') }}</option>
                        <option value="low"  {{ request('stock_status') === 'low'  ? 'selected' : '' }}>{{ translate('Low Stock') }}</option>
                        <option value="out"  {{ request('stock_status') === 'out'  ? 'selected' : '' }}>{{ translate('Out of Stock') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-block">{{ translate('Filter') }}</button>
                </div>
                <div class="col-md-3 text-right">
                    <a href="{{ route('vendor.inventory.purchases.index') }}" class="btn btn-outline-success">
                        <i class="tio-shopping-cart-outlined"></i> {{ translate('Purchase Orders') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>{{ translate('Item') }}</th>
                            <th>{{ translate('Stock') }}</th>
                            <th>{{ translate('Avg Cost') }}</th>
                            <th>{{ translate('Stock Value') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $item->name }}</strong></td>
                            <td>
                                <strong class="{{ $item->stock <= 0 ? 'text-danger' : ($item->stock <= ($store->config?->minimum_stock_for_warning ?? 10) ? 'text-warning' : 'text-success') }}">
                                    {{ $item->stock }}
                                </strong>
                            </td>
                            <td>{{ number_format($item->average_cost, 2) }}</td>
                            <td>{{ number_format($item->total_stock_value, 2) }}</td>
                            <td>
                                @if($item->stock <= 0)
                                    <span class="badge badge-soft-danger">{{ translate('Out of Stock') }}</span>
                                @elseif($item->stock <= ($store->config?->minimum_stock_for_warning ?? 10))
                                    <span class="badge badge-soft-warning">{{ translate('Low Stock') }}</span>
                                @else
                                    <span class="badge badge-soft-success">{{ translate('In Stock') }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('vendor.inventory.item-detail', $item->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="tio-visible-outlined"></i> {{ translate('Detail') }}
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-4">{{ translate('No items found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $items->links() }}</div>
        </div>
    </div>
</div>
@endsection
