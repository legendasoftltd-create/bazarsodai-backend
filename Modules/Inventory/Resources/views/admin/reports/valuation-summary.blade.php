@extends('layouts.admin.app')
@section('title', translate('Valuation Summary'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-money"></i></span>
            <span>{{ translate('Stock Valuation Summary') }}</span>
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
                <div class="col-md-2"><button class="btn btn-primary btn-block">{{ translate('Filter') }}</button></div>
                <div class="col-md-2">
                    <a href="{{ route('admin.inventory.reports.valuation-summary') }}" class="btn btn-outline-secondary btn-block">{{ translate('Reset') }}</a>
                </div>
                <div class="col-md-2 text-right">
                    <a href="{{ route('admin.inventory.reports.export', 'valuation-summary') }}?{{ http_build_query(request()->all()) }}" class="btn btn-outline-danger btn-block">
                        <i class="tio-file-text-outlined"></i> {{ translate('PDF') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Grand total card --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card text-center py-3">
                <h3 class="mb-0">{{ number_format($grandTotal, 2) }}</h3>
                <p class="text-muted small mb-0">{{ translate('Grand Total Stock Value') }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center py-3">
                <h3 class="mb-0">{{ $rows->count() }}</h3>
                <p class="text-muted small mb-0">{{ translate('Items with Stock') }}</p>
            </div>
        </div>
    </div>

    {{-- By valuation method --}}
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">{{ translate('Breakdown by Valuation Method') }}</h5></div>
        <div class="card-body p-0">
            <table class="table table-bordered mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Valuation Method') }}</th>
                        <th>{{ translate('Items') }}</th>
                        <th>{{ translate('Total Stock Qty') }}</th>
                        <th>{{ translate('Total Value') }}</th>
                        <th>{{ translate('% of Total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($byMethod as $method => $data)
                    <tr>
                        <td><span class="badge badge-soft-info">{{ strtoupper(str_replace('_',' ',$method)) }}</span></td>
                        <td>{{ $data['count'] }}</td>
                        <td>{{ number_format($data['total_stock'], 2) }}</td>
                        <td><strong>{{ number_format($data['total_value'], 2) }}</strong></td>
                        <td>{{ $grandTotal > 0 ? number_format($data['total_value'] / $grandTotal * 100, 1) : 0 }}%</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="thead-light">
                        <td colspan="2"><strong>{{ translate('Total') }}</strong></td>
                        <td><strong>{{ number_format($rows->sum('stock'), 2) }}</strong></td>
                        <td><strong>{{ number_format($grandTotal, 2) }}</strong></td>
                        <td><strong>100%</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Item detail table --}}
    <div class="card">
        <div class="card-header"><h5 class="mb-0">{{ translate('Item Detail') }}</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Item') }}</th>
                            <th>{{ translate('Vendor') }}</th>
                            <th>{{ translate('Stock') }}</th>
                            <th>{{ translate('Avg Cost') }}</th>
                            <th>{{ translate('Total Value') }}</th>
                            <th>{{ translate('Method') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $item)
                        <tr>
                            <td><a href="{{ route('admin.inventory.item-detail', $item->id) }}">{{ $item->name }}</a></td>
                            <td>{{ $item->store?->name ?? '—' }}</td>
                            <td>{{ number_format($item->stock, 2) }}</td>
                            <td>{{ number_format($item->average_cost ?? 0, 2) }}</td>
                            <td><strong>{{ number_format($item->total_stock_value ?? 0, 2) }}</strong></td>
                            <td><span class="badge badge-soft-secondary">{{ strtoupper($item->valuation_method ?? 'default') }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-3">{{ translate('No items') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
