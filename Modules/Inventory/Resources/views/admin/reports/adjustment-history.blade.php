@extends('layouts.admin.app')
@section('title', translate('Adjustment History'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-tune"></i></span>
            <span>{{ translate('Adjustment History') }}</span>
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
                    <select name="status" class="form-control">
                        <option value="">{{ translate('All Statuses') }}</option>
                        @foreach(['draft','pending_approval','approved','rejected'] as $s)
                            <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                </div>
                <div class="col-md-1">
                    <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                </div>
                <div class="col-md-2"><button class="btn btn-primary btn-block">{{ translate('Filter') }}</button></div>
                <div class="col-md-1">
                    <a href="{{ route('admin.inventory.reports.adjustment-history') }}" class="btn btn-outline-secondary btn-block">{{ translate('Reset') }}</a>
                </div>
                <div class="col-md-2 text-right">
                    <a href="{{ route('admin.inventory.reports.export', 'adjustment-history') }}?{{ http_build_query(request()->all()) }}" class="btn btn-outline-success btn-block">
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
                            <th>{{ translate('Ref #') }}</th>
                            <th>{{ translate('Vendor') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Date') }}</th>
                            <th>{{ translate('Approved') }}</th>
                            <th>{{ translate('Note') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $colors = ['draft'=>'secondary','pending_approval'=>'warning','approved'=>'success','rejected'=>'danger']; @endphp
                        @forelse($adjustments as $adj)
                        <tr>
                            <td><strong>{{ $adj->adjustment_number }}</strong></td>
                            <td>{{ $adj->store?->name ?? '—' }}</td>
                            <td><span class="badge badge-soft-{{ $colors[$adj->status] ?? 'secondary' }}">{{ ucwords(str_replace('_',' ',$adj->status)) }}</span></td>
                            <td>{{ $adj->created_at->format('d M Y') }}</td>
                            <td>{{ $adj->approved_at?->format('d M Y') ?? '—' }}</td>
                            <td class="text-muted small">{{ Str::limit($adj->note, 40) }}</td>
                            <td>
                                <a href="{{ route('admin.inventory.adjustments.show', $adj->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="tio-visible"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-4">{{ translate('No adjustments found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $adjustments->links() }}</div>
        </div>
    </div>
</div>
@endsection
