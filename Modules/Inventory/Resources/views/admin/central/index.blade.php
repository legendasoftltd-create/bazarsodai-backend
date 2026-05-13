@extends('layouts.admin.app')
@section('title', translate('Central Inventory'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-layers-outlined"></i></span>
            <span>{{ translate('Central Inventory') }}
                <span class="badge badge-soft-secondary">{{ $items->total() }}</span>
            </span>
        </h1>
        <div class="page-header-content mt-2">
            <span class="text-muted">{{ translate('Total Stock Value') }}:</span>
            <strong class="ml-1">{{ number_format($totalValue, 2) }}</strong>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="{{ translate('Search item name') }}" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="module_id" class="form-control">
                        <option value="">{{ translate('All Modules') }}</option>
                        @foreach($modules as $module)
                            <option value="{{ $module->id }}" {{ request('module_id') == $module->id ? 'selected' : '' }}>{{ $module->module_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="store_id" class="form-control">
                        <option value="">{{ translate('All Vendors') }}</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="stock_status" class="form-control">
                        <option value="">{{ translate('All Stock') }}</option>
                        <option value="low" {{ request('stock_status') === 'low' ? 'selected' : '' }}>{{ translate('Low Stock') }}</option>
                        <option value="out" {{ request('stock_status') === 'out' ? 'selected' : '' }}>{{ translate('Out of Stock') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-block">{{ translate('Filter') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="row mb-3">
        <div class="col-md-3">
            <a href="{{ route('admin.inventory.by-module', request('module_id', 1)) }}" class="btn btn-outline-primary btn-block">
                <i class="tio-apps"></i> {{ translate('Module View') }}
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.inventory.reports.low-stock') }}" class="btn btn-outline-warning btn-block">
                <i class="tio-warning-outlined"></i> {{ translate('Low Stock Report') }}
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.inventory.reports.valuation') }}" class="btn btn-outline-info btn-block">
                <i class="tio-chart-bar-1"></i> {{ translate('Valuation Report') }}
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.inventory.purchases.index') }}" class="btn btn-outline-success btn-block">
                <i class="tio-shopping-cart-outlined"></i> {{ translate('Purchase Orders') }}
            </a>
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
                            <th>{{ translate('Module') }}</th>
                            <th>{{ translate('Vendor') }}</th>
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
                            <td>
                                <strong>{{ $item->name }}</strong>
                            </td>
                            <td><span class="badge badge-soft-secondary">{{ $item->module_id }}</span></td>
                            <td>{{ $item->store?->name ?? '—' }}</td>
                            <td>
                                <strong class="{{ $item->stock <= 0 ? 'text-danger' : ($item->stock <= ($item->store?->config?->minimum_stock_for_warning ?? 10) ? 'text-warning' : 'text-success') }}">
                                    {{ $item->stock }}
                                </strong>
                            </td>
                            <td>{{ number_format($item->average_cost, 2) }}</td>
                            <td>{{ number_format($item->total_stock_value, 2) }}</td>
                            <td>
                                @if($item->stock <= 0)
                                    <span class="badge badge-soft-danger">{{ translate('Out of Stock') }}</span>
                                @elseif($item->stock <= ($item->store?->config?->minimum_stock_for_warning ?? 10))
                                    <span class="badge badge-soft-warning">{{ translate('Low Stock') }}</span>
                                @else
                                    <span class="badge badge-soft-success">{{ translate('In Stock') }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.inventory.item-detail', $item->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="tio-visible-outlined"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center py-4">{{ translate('No items found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $items->links() }}</div>
        </div>
    </div>
</div>
@endsection
