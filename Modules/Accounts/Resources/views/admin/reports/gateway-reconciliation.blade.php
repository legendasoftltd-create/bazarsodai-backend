@extends('layouts.admin.app')
@section('title', translate('Gateway Reconciliation'))

@section('content')
<div class="content container-fluid">

    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <span class="page-header-icon"><i class="tio-credit-card nav-icon"></i></span>
                    {{ translate('Gateway Reconciliation') }}
                    <small class="text-muted font-weight-normal ml-2">{{ translate('Account') }} {{ $account_code }}</small>
                </h1>
            </div>
            <div class="col-sm-auto d-flex gap-2">
                @include('accounts::admin.partials._scope_badge')
                <a href="{{ request()->fullUrlWithQuery(['format' => 'excel']) }}" class="btn btn-sm btn-outline-success">
                    <i class="tio-download mr-1"></i> {{ translate('Excel') }}
                </a>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="btn btn-sm btn-outline-danger">
                    <i class="tio-file-pdf mr-1"></i> {{ translate('PDF') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label mb-1">{{ translate('Account') }}</label>
                    <select name="account" class="form-control">
                        <option value="1013" {{ $account_code === '1013' ? 'selected' : '' }}>1013 — {{ translate('Gateway Clearing') }}</option>
                        <option value="1014" {{ $account_code === '1014' ? 'selected' : '' }}>1014 — {{ translate('bKash / Mobile') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">{{ translate('From') }}</label>
                    <input type="date" name="from" class="form-control" value="{{ $from }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">{{ translate('To') }}</label>
                    <input type="date" name="to" class="form-control" value="{{ $to }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-block">{{ translate('Apply') }}</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.accounts.reports.gateway-reconciliation') }}" class="btn btn-outline-secondary btn-block">{{ translate('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card card-body text-center">
                <small class="text-muted">{{ translate('Payments Received (DR)') }}</small>
                <h3 class="text-primary mb-0">{{ number_format($total_in, 2) }}</h3>
                <small class="text-muted">{{ translate('Inflows from gateway') }}</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-body text-center">
                <small class="text-muted">{{ translate('Settled to Bank (CR)') }}</small>
                <h3 class="text-success mb-0">{{ number_format($total_out, 2) }}</h3>
                <small class="text-muted">{{ translate('Swept out') }}</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-body text-center border-left {{ $outstanding > 0 ? 'border-warning' : 'border-success' }}">
                <small class="text-muted">{{ translate('Outstanding') }}</small>
                <h3 class="{{ $outstanding > 0 ? 'text-warning' : 'text-success' }} mb-0">{{ number_format($outstanding, 2) }}</h3>
                <small class="text-muted">{{ translate('Pending settlement') }}</small>
            </div>
        </div>
    </div>

    {{-- Breakdown by event type --}}
    @if($rows->isNotEmpty())
    <div class="card mb-3">
        <div class="card-header py-2"><h6 class="card-title mb-0">{{ translate('Breakdown by Event Type') }}</h6></div>
        <div class="card-body py-2">
            <div class="row">
                @foreach($rows->groupBy('event_type') as $eventType => $group)
                    <div class="col-auto mr-3 mb-2">
                        <small class="badge badge-soft-info d-block mb-1">{{ str_replace('_', ' ', $eventType) }}</small>
                        <span class="font-weight-bold">{{ number_format($group->sum('debit') - $group->sum('credit'), 2) }}</span>
                        <small class="text-muted ml-1">({{ $group->count() }} entries)</small>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Detail table --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Date') }}</th>
                        <th>{{ translate('Entry #') }}</th>
                        <th>{{ translate('Event') }}</th>
                        <th>{{ translate('Reference') }}</th>
                        <th>{{ translate('Description') }}</th>
                        <th class="text-right">{{ translate('In (DR)') }}</th>
                        <th class="text-right">{{ translate('Out (CR)') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td class="text-nowrap">{{ \Carbon\Carbon::parse($row->posted_at)->format('Y-m-d') }}</td>
                            <td class="font-weight-bold text-nowrap">{{ $row->entry_number }}</td>
                            <td><small class="badge badge-soft-info">{{ str_replace('_', ' ', $row->event_type) }}</small></td>
                            <td class="text-nowrap">
                                @if($row->reference_type && $row->reference_id)
                                    <small class="text-muted">{{ $row->reference_type }} #{{ $row->reference_id }}</small>
                                @else —
                                @endif
                            </td>
                            <td>{{ $row->description ?? '—' }}</td>
                            <td class="text-right text-primary">{{ $row->debit  > 0 ? number_format($row->debit,  2) : '—' }}</td>
                            <td class="text-right text-success">{{ $row->credit > 0 ? number_format($row->credit, 2) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">{{ translate('No gateway entries in this period.') }}</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="font-weight-bold bg-light">
                    <tr>
                        <td colspan="5" class="text-right">{{ translate('Totals') }}</td>
                        <td class="text-right text-primary">{{ number_format($total_in, 2) }}</td>
                        <td class="text-right text-success">{{ number_format($total_out, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection
