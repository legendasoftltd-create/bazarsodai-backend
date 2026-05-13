@extends('layouts.admin.app')
@section('title', translate('COGS Report'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-chart-pie-1"></i></span>
            <span>{{ translate('Cost of Goods Sold (COGS)') }}</span>
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
                    <input type="date" name="from" class="form-control" value="{{ $from }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="to" class="form-control" value="{{ $to }}">
                </div>
                <div class="col-md-2"><button class="btn btn-primary btn-block">{{ translate('Filter') }}</button></div>
                <div class="col-md-1">
                    <a href="{{ route('admin.inventory.reports.cogs') }}" class="btn btn-outline-secondary btn-block">{{ translate('Reset') }}</a>
                </div>
                <div class="col-md-2 text-right">
                    <a href="{{ route('admin.inventory.reports.export', 'cogs') }}?{{ http_build_query(request()->all()) }}" class="btn btn-outline-danger btn-block">
                        <i class="tio-file-text-outlined"></i> {{ translate('PDF') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card text-center py-3 border-primary">
                <h3 class="text-primary mb-0">{{ number_format($totalCogs, 2) }}</h3>
                <p class="text-muted small mb-0">{{ translate('Total COGS') }} ({{ $from }} → {{ $to }})</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center py-3">
                <h4 class="mb-0">{{ $rows->total() }}</h4>
                <p class="text-muted small mb-0">{{ translate('Sale Movements') }}</p>
            </div>
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
                            <th>{{ translate('Qty Sold') }}</th>
                            <th>{{ translate('Unit Cost') }}</th>
                            <th>{{ translate('COGS') }}</th>
                            <th>{{ translate('Reference') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $m)
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
                            <td>{{ abs($m->qty) }}</td>
                            <td>{{ number_format($m->unit_cost, 2) }}</td>
                            <td><strong>{{ number_format(abs($m->total_cost ?? ($m->qty * $m->unit_cost)), 2) }}</strong></td>
                            <td class="text-muted small">{{ $m->note }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-4">{{ translate('No sales in this period') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $rows->links() }}</div>
        </div>
    </div>
</div>
@endsection
