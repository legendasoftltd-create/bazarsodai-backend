@extends('layouts.admin.app')
@section('title', translate('Purchase Report'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-shopping-cart-outlined"></i></span>
            <span>{{ translate('Purchase Report') }}</span>
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
                <div class="col-md-2">
                    <select name="module_id" class="form-control">
                        <option value="">{{ translate('All Modules') }}</option>
                        @foreach($modules as $m)
                            <option value="{{ $m->id }}" {{ request('module_id') == $m->id ? 'selected' : '' }}>{{ $m->module_name }}</option>
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
                    <a href="{{ route('admin.inventory.reports.purchases') }}" class="btn btn-outline-secondary btn-block">{{ translate('Reset') }}</a>
                </div>
                <div class="col-md-2 text-right">
                    <a href="{{ route('admin.inventory.reports.export', 'purchases') }}?{{ http_build_query(request()->all()) }}" class="btn btn-outline-success btn-block">
                        <i class="tio-download"></i> {{ translate('Export') }}
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
                        @forelse($movements as $m)
                        @php
                            $isReturn = $m->type === 'purchase_return';
                        @endphp
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
                            <td>
                                <span class="badge badge-soft-{{ $isReturn ? 'warning' : 'success' }}">
                                    {{ $isReturn ? translate('Return') : translate('Purchase') }}
                                </span>
                            </td>
                            <td class="{{ $isReturn ? 'text-danger' : 'text-success' }}">
                                {{ $isReturn ? '' : '+' }}{{ $m->qty }}
                            </td>
                            <td>{{ number_format($m->unit_cost, 2) }}</td>
                            <td>{{ number_format(abs($m->total_cost ?? ($m->qty * $m->unit_cost)), 2) }}</td>
                            <td class="text-muted small">{{ $m->note }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center py-4">{{ translate('No purchase records found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $movements->links() }}</div>
        </div>
    </div>
</div>
@endsection
