@extends('layouts.vendor.app')
@section('title', translate('Damage & Loss Report'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-remove-from-trash"></i></span>
            <span>{{ translate('Damage & Loss Report') }}</span>
        </h1>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <select name="type" class="form-control">
                        <option value="">{{ translate('All Types') }}</option>
                        <option value="damaged"      {{ request('type') === 'damaged'      ? 'selected' : '' }}>{{ translate('Damaged') }}</option>
                        <option value="broken"       {{ request('type') === 'broken'       ? 'selected' : '' }}>{{ translate('Broken') }}</option>
                        <option value="internal_use" {{ request('type') === 'internal_use' ? 'selected' : '' }}>{{ translate('Internal Use') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                </div>
                <div class="col-md-2"><button class="btn btn-primary btn-block">{{ translate('Filter') }}</button></div>
                <div class="col-md-1">
                    <a href="{{ route('vendor.inventory.reports.damage-loss') }}" class="btn btn-outline-secondary btn-block">{{ translate('Reset') }}</a>
                </div>
                <div class="col-md-2 text-right">
                    <a href="{{ route('vendor.inventory.reports.export', 'damage-loss') }}?{{ http_build_query(request()->all()) }}" class="btn btn-outline-success btn-block">
                        <i class="tio-download"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card text-center py-3 border-danger">
                <h4 class="text-danger mb-0">{{ number_format($totalLoss, 2) }}</h4>
                <p class="text-muted small mb-0">{{ translate('Total Loss Value') }}</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Date') }}</th>
                            <th>{{ translate('Item') }}</th>
                            <th>{{ translate('Type') }}</th>
                            <th>{{ translate('Qty') }}</th>
                            <th>{{ translate('Unit Cost') }}</th>
                            <th>{{ translate('Loss Value') }}</th>
                            <th>{{ translate('Note') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $typeColors = ['damaged'=>'warning','broken'=>'danger','internal_use'=>'info']; @endphp
                        @forelse($movements as $m)
                        <tr>
                            <td>{{ $m->created_at->format('d M Y H:i') }}</td>
                            <td>
                                <a href="{{ route('vendor.inventory.item-detail', $m->item_id) }}">
                                    {{ $m->item?->name ?? "Item #{$m->item_id}" }}
                                </a>
                            </td>
                            <td><span class="badge badge-soft-{{ $typeColors[$m->type] ?? 'secondary' }}">{{ ucwords(str_replace('_',' ',$m->type)) }}</span></td>
                            <td class="text-danger">{{ abs($m->qty) }}</td>
                            <td>{{ number_format($m->unit_cost, 2) }}</td>
                            <td class="text-danger fw-bold">{{ number_format(abs($m->total_cost ?? ($m->qty * $m->unit_cost)), 2) }}</td>
                            <td class="text-muted small">{{ $m->note }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-4">{{ translate('No damage/loss records found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $movements->links() }}</div>
        </div>
    </div>
</div>
@endsection
