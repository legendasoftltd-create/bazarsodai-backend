@extends('layouts.admin.app')
@section('title', translate('Stock Ledger'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-chart-bar-1"></i></span>
            <span>{{ translate('Stock Ledger') }}</span>
        </h1>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <select name="store_id" class="form-control">
                        <option value="">{{ translate('All Vendors') }}</option>
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}" {{ request('store_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="module_id" class="form-control">
                        <option value="">{{ translate('All Modules') }}</option>
                        @foreach($modules as $m)
                            <option value="{{ $m->id }}" {{ request('module_id') == $m->id ? 'selected' : '' }}>{{ $m->module_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-control">
                        <option value="">{{ translate('All Types') }}</option>
                        @foreach(['opening','purchase','purchase_return','sale','sale_return','damaged','broken','internal_use','adjustment_add','adjustment_sub','transfer_in','transfer_out'] as $t)
                            <option value="{{ $t }}" {{ request('type') == $t ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$t)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                </div>
                <div class="col-md-1">
                    <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-block">{{ translate('Filter') }}</button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('admin.inventory.reports.stock-ledger') }}" class="btn btn-outline-secondary btn-block">{{ translate('Reset') }}</a>
                </div>
                <div class="col-md-1 text-right">
                    <a href="{{ route('admin.inventory.reports.export', 'stock-ledger') }}?{{ http_build_query(request()->all()) }}" class="btn btn-outline-success btn-block">
                        <i class="tio-download"></i>
                    </a>
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
                            <th>{{ translate('Date') }}</th>
                            <th>{{ translate('Item') }}</th>
                            <th>{{ translate('Vendor') }}</th>
                            <th>{{ translate('Type') }}</th>
                            <th>{{ translate('Qty') }}</th>
                            <th>{{ translate('Unit Cost') }}</th>
                            <th>{{ translate('Total Cost') }}</th>
                            <th>{{ translate('Note') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $typeColors = ['sale'=>'danger','purchase'=>'success','opening'=>'info','damaged'=>'warning','broken'=>'warning','sale_return'=>'success','purchase_return'=>'warning','transfer_in'=>'info','transfer_out'=>'secondary','adjustment_add'=>'success','adjustment_sub'=>'danger','internal_use'=>'warning'];
                        @endphp
                        @forelse($movements as $m)
                        <tr>
                            <td>{{ $m->created_at->format('d M Y H:i') }}</td>
                            <td>
                                @if($m->item)
                                    <a href="{{ route('admin.inventory.item-detail', $m->item_id) }}">{{ $m->item->name }}</a>
                                @else
                                    Item #{{ $m->item_id }}
                                @endif
                            </td>
                            <td>{{ $m->store?->name ?? '—' }}</td>
                            <td><span class="badge badge-soft-{{ $typeColors[$m->type] ?? 'secondary' }}">{{ ucwords(str_replace('_',' ',$m->type)) }}</span></td>
                            <td class="{{ $m->qty < 0 ? 'text-danger' : 'text-success' }}">{{ $m->qty >= 0 ? '+' : '' }}{{ $m->qty }}</td>
                            <td>{{ number_format($m->unit_cost, 2) }}</td>
                            <td>{{ number_format(abs($m->total_cost ?? ($m->qty * $m->unit_cost)), 2) }}</td>
                            <td class="text-muted small">{{ $m->note }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center py-4">{{ translate('No movements found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $movements->links() }}</div>
        </div>
    </div>
</div>
@endsection
