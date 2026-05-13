@extends('layouts.admin.app')
@section('title', translate('Stock Valuation'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-money"></i></span>
            <span>{{ translate('Current Stock Valuation') }}</span>
        </h1>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <select name="store_id" class="form-control">
                        <option value="">{{ translate('All Vendors') }}</option>
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}" {{ request('store_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="module_id" class="form-control">
                        <option value="">{{ translate('All Modules') }}</option>
                        @foreach($modules as $m)
                            <option value="{{ $m->id }}" {{ request('module_id') == $m->id ? 'selected' : '' }}>{{ $m->module_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-block">{{ translate('Filter') }}</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.inventory.reports.valuation') }}" class="btn btn-outline-secondary btn-block">{{ translate('Reset') }}</a>
                </div>
                <div class="col-md-2 text-right">
                    <a href="{{ route('admin.inventory.reports.export', 'valuation') }}?{{ http_build_query(request()->all()) }}" class="btn btn-outline-success btn-block">
                        <i class="tio-download"></i> {{ translate('Export') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card text-center py-3">
                <h4 class="mb-0">{{ number_format($totalValue, 2) }}</h4>
                <p class="text-muted small mb-0">{{ translate('Total Stock Value') }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center py-3">
                <h4 class="mb-0">{{ $items->total() }}</h4>
                <p class="text-muted small mb-0">{{ translate('Items with Stock') }}</p>
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
                            <th>{{ translate('Module') }}</th>
                            <th>{{ translate('Stock') }}</th>
                            <th>{{ translate('Avg Cost') }}</th>
                            <th>{{ translate('Total Value') }}</th>
                            <th>{{ translate('Valuation Method') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                        <tr>
                            <td>
                                <a href="{{ route('admin.inventory.item-detail', $item->id) }}">{{ $item->name }}</a>
                            </td>
                            <td>{{ $item->store?->name ?? '—' }}</td>
                            <td>{{ $item->module?->module_name ?? '—' }}</td>
                            <td>{{ number_format($item->stock, 2) }}</td>
                            <td>{{ number_format($item->average_cost ?? 0, 2) }}</td>
                            <td><strong>{{ number_format($item->total_stock_value ?? 0, 2) }}</strong></td>
                            <td>
                                <span class="badge badge-soft-info">
                                    {{ strtoupper($item->valuation_method ?? 'default') }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-4">{{ translate('No items with stock found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $items->links() }}</div>
        </div>
    </div>
</div>
@endsection
