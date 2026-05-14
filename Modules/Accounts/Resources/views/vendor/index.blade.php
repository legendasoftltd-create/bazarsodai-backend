@extends('layouts.vendor.app')
@section('title', translate('Account Overview'))

@section('content')
<div class="content container-fluid">

    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <span class="page-header-icon"><i class="tio-chart-bar-1 nav-icon"></i></span>
                    {{ translate('Account Overview') }}
                </h1>
            </div>
            <div class="col-sm-auto">
                <span class="badge badge-soft-success py-2 px-3">
                    <i class="tio-filter-list mr-1"></i>{{ $store->name }}
                </span>
            </div>
        </div>
    </div>

    {{-- Date filter --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
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
            </form>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card card-body text-center border-left border-success">
                <small class="text-muted">{{ translate('Opening Balance') }}</small>
                <h3 class="text-secondary mb-0">{{ \App\CentralLogics\Helpers::format_currency($statement['opening_balance']) }}</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-body text-center border-left border-primary">
                <small class="text-muted">{{ translate('Current Balance') }}</small>
                <h3 class="{{ $statement['closing_balance'] >= 0 ? 'text-primary' : 'text-danger' }} mb-0">
                    {{ \App\CentralLogics\Helpers::format_currency($statement['closing_balance']) }}
                </h3>
                <small class="text-muted">{{ translate('Earnings pending payout') }}</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-body text-center border-left border-info">
                <small class="text-muted">{{ translate('Transactions This Period') }}</small>
                <h3 class="text-info mb-0">{{ $statement['rows']->count() }}</h3>
            </div>
        </div>
    </div>

    {{-- Quick links --}}
    <div class="row mb-3">
        <div class="col-md-6">
            <a href="{{ route('vendor.accounts.statement') }}" class="card card-body text-center h-100 text-decoration-none">
                <i class="tio-book-outlined mb-2" style="font-size:2rem;color:#005555"></i>
                <h6 class="mb-0">{{ translate('Account Statement') }}</h6>
                <small class="text-muted">{{ translate('Full debit/credit ledger with running balance') }}</small>
            </a>
        </div>
        <div class="col-md-6">
            <a href="{{ route('vendor.accounts.earnings') }}" class="card card-body text-center h-100 text-decoration-none">
                <i class="tio-chart-pie-1 mb-2" style="font-size:2rem;color:#005555"></i>
                <h6 class="mb-0">{{ translate('Earnings Report') }}</h6>
                <small class="text-muted">{{ translate('Revenue & expense breakdown') }}</small>
            </a>
        </div>
    </div>

    {{-- Recent journal entries --}}
    <div class="card">
        <div class="card-header py-2">
            <h6 class="card-title mb-0">{{ translate('Recent Activity') }}</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Date') }}</th>
                        <th>{{ translate('Entry #') }}</th>
                        <th>{{ translate('Event') }}</th>
                        <th>{{ translate('Reference') }}</th>
                        <th>{{ translate('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentEntries as $je)
                        <tr>
                            <td class="text-nowrap">{{ $je->posted_at?->format('Y-m-d H:i') }}</td>
                            <td class="font-weight-bold">{{ $je->entry_number }}</td>
                            <td><small class="badge badge-soft-info">{{ str_replace('_',' ',$je->event_type) }}</small></td>
                            <td>
                                @if($je->reference_type && $je->reference_id)
                                    <small class="text-muted">{{ $je->reference_type }} #{{ $je->reference_id }}</small>
                                @else —
                                @endif
                            </td>
                            <td><span class="badge badge-soft-success">{{ ucfirst($je->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-3 text-muted">{{ translate('No activity in this period.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($recentEntries->count() >= 10)
        <div class="card-footer py-2 text-right">
            <a href="{{ route('vendor.accounts.statement', ['from' => $from, 'to' => $to]) }}" class="btn btn-sm btn-outline-primary">
                {{ translate('View Full Statement') }} →
            </a>
        </div>
        @endif
    </div>

</div>
@endsection
