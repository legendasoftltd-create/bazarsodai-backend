@extends('layouts.vendor.app')
@section('title', translate('Account Statement'))

@section('content')
<div class="content container-fluid">

    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <span class="page-header-icon"><i class="tio-book-outlined nav-icon"></i></span>
                    {{ translate('Account Statement') }}
                    <small class="text-muted font-weight-normal ml-2">{{ $store->name }}</small>
                </h1>
            </div>
            <div class="col-sm-auto">
                <a href="{{ request()->fullUrlWithQuery(['format' => 'excel']) }}" class="btn btn-sm btn-outline-success">
                    <i class="tio-download mr-1"></i> {{ translate('Excel') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Filter --}}
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
                <div class="col-md-2">
                    <a href="{{ route('vendor.accounts.statement') }}" class="btn btn-outline-secondary btn-block">{{ translate('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card card-body text-center">
                <small class="text-muted">{{ translate('Opening Balance') }}</small>
                <h3 class="text-secondary mb-0">{{ \App\CentralLogics\Helpers::format_currency($statement['opening_balance']) }}</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-body text-center border-left border-primary">
                <small class="text-muted">{{ translate('Closing Balance') }}</small>
                <h3 class="{{ $statement['closing_balance'] >= 0 ? 'text-primary' : 'text-danger' }} mb-0">
                    {{ \App\CentralLogics\Helpers::format_currency($statement['closing_balance']) }}
                </h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-body text-center">
                <small class="text-muted">{{ translate('Period') }}</small>
                <h6 class="text-info mb-0">{{ $from }}</h6>
                <small class="text-muted">to {{ $to }}</small>
            </div>
        </div>
    </div>

    {{-- Ledger --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Date') }}</th>
                        <th>{{ translate('Entry #') }}</th>
                        <th>{{ translate('Event') }}</th>
                        <th>{{ translate('Reference') }}</th>
                        <th class="text-right">{{ translate('Debit') }}</th>
                        <th class="text-right">{{ translate('Credit') }}</th>
                        <th class="text-right">{{ translate('Balance') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="bg-light font-italic">
                        <td colspan="6" class="text-right font-weight-bold">{{ translate('Opening Balance') }}</td>
                        <td class="text-right font-weight-bold">{{ \App\CentralLogics\Helpers::format_currency($statement['opening_balance']) }}</td>
                    </tr>
                    @forelse($statement['rows'] as $row)
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
                            <td class="text-right text-danger">{{ $row->debit  > 0 ? \App\CentralLogics\Helpers::format_currency($row->debit)  : '—' }}</td>
                            <td class="text-right text-success">{{ $row->credit > 0 ? \App\CentralLogics\Helpers::format_currency($row->credit) : '—' }}</td>
                            <td class="text-right font-weight-bold {{ $row->running_balance < 0 ? 'text-danger' : '' }}">
                                {{ \App\CentralLogics\Helpers::format_currency($row->running_balance) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">{{ translate('No entries in this period.') }}</td></tr>
                    @endforelse
                    <tr class="bg-light font-weight-bold">
                        <td colspan="6" class="text-right">{{ translate('Closing Balance') }}</td>
                        <td class="text-right {{ $statement['closing_balance'] < 0 ? 'text-danger' : 'text-primary' }}">
                            {{ \App\CentralLogics\Helpers::format_currency($statement['closing_balance']) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
