@extends('layouts.admin.app')
@section('title', translate('Vendor Stock Summary'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-briefcase-outlined"></i></span>
            <span>{{ translate('Vendor-wise Stock Summary') }}</span>
        </h1>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <select name="module_id" class="form-control">
                        <option value="">{{ translate('All Modules') }}</option>
                        @foreach($modules as $m)
                            <option value="{{ $m->id }}" {{ request('module_id') == $m->id ? 'selected' : '' }}>{{ $m->module_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-primary btn-block">{{ translate('Filter') }}</button></div>
                <div class="col-md-2">
                    <a href="{{ route('admin.inventory.reports.vendor-summary') }}" class="btn btn-outline-secondary btn-block">{{ translate('Reset') }}</a>
                </div>
                <div class="col-md-4 text-right">
                    <a href="{{ route('admin.inventory.reports.export', 'valuation') }}" class="btn btn-outline-success">
                        <i class="tio-download"></i> {{ translate('Export All') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Vendor') }}</th>
                            <th>{{ translate('Total Items') }}</th>
                            <th>{{ translate('In Stock') }}</th>
                            <th>{{ translate('Out of Stock') }}</th>
                            <th>{{ translate('Total Value') }}</th>
                            <th>{{ translate('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summary as $row)
                        <tr>
                            <td><strong>{{ $row['store']->name }}</strong></td>
                            <td>{{ $row['item_count'] }}</td>
                            <td><span class="text-success">{{ $row['in_stock'] }}</span></td>
                            <td>
                                @if($row['out_of_stock'] > 0)
                                    <span class="text-danger">{{ $row['out_of_stock'] }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td><strong>{{ number_format($row['total_value'], 2) }}</strong></td>
                            <td>
                                <a href="{{ route('admin.inventory.by-vendor', $row['store']->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="tio-visible"></i> {{ translate('View') }}
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-4">{{ translate('No data found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
