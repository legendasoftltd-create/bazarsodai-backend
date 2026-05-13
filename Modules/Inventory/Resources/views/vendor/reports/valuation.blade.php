@extends('layouts.vendor.app')
@section('title', translate('Stock Valuation'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-money"></i></span>
            <span>{{ translate('Stock Valuation') }}</span>
        </h1>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card text-center py-3">
                <h3 class="mb-0">{{ number_format($totalValue, 2) }}</h3>
                <p class="text-muted small mb-0">{{ translate('Total Stock Value') }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center py-3">
                <h4 class="mb-0">{{ $items->total() }}</h4>
                <p class="text-muted small mb-0">{{ translate('Items with Stock') }}</p>
            </div>
        </div>
        <div class="col-md-4 d-flex align-items-center justify-content-end">
            <a href="{{ route('vendor.inventory.reports.export', 'valuation') }}" class="btn btn-outline-success">
                <i class="tio-download"></i> {{ translate('Export') }}
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Item') }}</th>
                            <th>{{ translate('Current Stock') }}</th>
                            <th>{{ translate('Avg Cost') }}</th>
                            <th>{{ translate('Total Value') }}</th>
                            <th>{{ translate('Method') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                        <tr>
                            <td>
                                <a href="{{ route('vendor.inventory.item-detail', $item->id) }}">{{ $item->name }}</a>
                            </td>
                            <td>{{ number_format($item->stock, 2) }}</td>
                            <td>{{ number_format($item->average_cost ?? 0, 2) }}</td>
                            <td><strong>{{ number_format($item->total_stock_value ?? 0, 2) }}</strong></td>
                            <td><span class="badge badge-soft-info">{{ strtoupper($item->valuation_method ?? 'default') }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-4">{{ translate('No items with stock') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $items->links() }}</div>
        </div>
    </div>
</div>
@endsection
