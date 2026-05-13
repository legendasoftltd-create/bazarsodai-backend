@extends('layouts.admin.app')
@section('title', translate('Module Inventory') . ' — ' . $module->module_name)

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-apps"></i></span>
            <span>{{ $module->module_name }} — {{ translate('Inventory') }}
                <span class="badge badge-soft-secondary">{{ $items->total() }}</span>
            </span>
        </h1>
        <a href="{{ route('admin.inventory.central') }}" class="btn btn-outline-secondary btn-sm">
            <i class="tio-arrow-backward"></i> {{ translate('Back') }}
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="{{ translate('Search item') }}" value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <select name="store_id" class="form-control">
                        <option value="">{{ translate('All Vendors') }}</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-block">{{ translate('Filter') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>{{ translate('Item') }}</th>
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
                            <td><strong>{{ $item->name }}</strong></td>
                            <td>{{ $item->store?->name ?? '—' }}</td>
                            <td class="{{ $item->stock <= 0 ? 'text-danger' : '' }}"><strong>{{ $item->stock }}</strong></td>
                            <td>{{ number_format($item->average_cost, 2) }}</td>
                            <td>{{ number_format($item->total_stock_value, 2) }}</td>
                            <td>
                                @if($item->stock <= 0)
                                    <span class="badge badge-soft-danger">{{ translate('Out') }}</span>
                                @elseif($item->stock <= ($item->store?->config?->minimum_stock_for_warning ?? 10))
                                    <span class="badge badge-soft-warning">{{ translate('Low') }}</span>
                                @else
                                    <span class="badge badge-soft-success">{{ translate('OK') }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.inventory.item-detail', $item->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="tio-visible-outlined"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center py-4">{{ translate('No items found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $items->links() }}</div>
        </div>
    </div>
</div>
@endsection
