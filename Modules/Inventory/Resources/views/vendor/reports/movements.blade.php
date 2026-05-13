@extends('layouts.vendor.app')
@section('title', translate('Movement History'))
@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-swap-horizontal"></i></span>
            <span>{{ translate('Movement History') }}</span>
        </h1>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <select name="item_id" class="form-control">
                        <option value="">{{ translate('All Items') }}</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" {{ request('item_id') == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-control">
                        <option value="">{{ translate('All Types') }}</option>
                        @foreach(['opening','purchase','purchase_return','sale','sale_return','damaged','broken','internal_use','adjustment_add','adjustment_sub','transfer_in','transfer_out'] as $t)
                            <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$t)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="to" class="form-control" value="{{ request('to') }}">
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
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Date') }}</th>
                            <th>{{ translate('Item') }}</th>
                            <th>{{ translate('Type') }}</th>
                            <th>{{ translate('In') }}</th>
                            <th>{{ translate('Out') }}</th>
                            <th>{{ translate('Stock After') }}</th>
                            <th>{{ translate('Unit Cost') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $colors = ['sale'=>'danger','purchase'=>'success','opening'=>'info','damaged'=>'warning','broken'=>'warning','sale_return'=>'success','purchase_return'=>'warning','transfer_in'=>'info','transfer_out'=>'secondary','adjustment_add'=>'success','adjustment_sub'=>'danger','internal_use'=>'warning']; @endphp
                        @forelse($movements as $m)
                        <tr>
                            <td>{{ $m->created_at->format('d M Y H:i') }}</td>
                            <td>{{ $m->item?->name ?? "Item #{$m->item_id}" }}</td>
                            <td><span class="badge badge-soft-{{ $colors[$m->type] ?? 'secondary' }}">{{ ucwords(str_replace('_',' ',$m->type)) }}</span></td>
                            <td class="text-success">{{ $m->qty_in > 0 ? '+' . $m->qty_in : '—' }}</td>
                            <td class="text-danger">{{ $m->qty_out > 0 ? '-' . $m->qty_out : '—' }}</td>
                            <td><strong>{{ $m->stock_after }}</strong></td>
                            <td>{{ number_format($m->unit_cost, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-4">{{ translate('No movements found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $movements->links() }}</div>
        </div>
    </div>
</div>
@endsection
