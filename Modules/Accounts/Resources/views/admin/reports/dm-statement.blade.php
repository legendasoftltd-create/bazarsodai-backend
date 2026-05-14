@extends('layouts.admin.app')
@section('title', translate('Delivery Man Statement'))

@section('content')
<div class="content container-fluid">

    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <span class="page-header-icon"><i class="tio-user nav-icon"></i></span>
                    {{ translate('Delivery Man Statement') }}
                    <small class="text-muted font-weight-normal ml-2">{{ translate('Account 2012 — DM Wallet Payable') }}</small>
                </h1>
            </div>
            @if($statement)
            <div class="col-sm-auto d-flex gap-2">
                @include('accounts::admin.partials._scope_badge')
                <a href="{{ request()->fullUrlWithQuery(['format' => 'excel']) }}" class="btn btn-sm btn-outline-success">
                    <i class="tio-download mr-1"></i> {{ translate('Excel') }}
                </a>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="btn btn-sm btn-outline-danger">
                    <i class="tio-file-pdf mr-1"></i> {{ translate('PDF') }}
                </a>
            </div>
            @endif
        </div>
    </div>

    {{-- Filter --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label mb-1">{{ translate('Delivery Man') }}</label>
                    <select name="dm_id" class="form-control">
                        <option value="">-- {{ translate('Select Delivery Man') }} --</option>
                        @foreach($deliveryMen as $dm)
                            <option value="{{ $dm->id }}" {{ (string)$dmId === (string)$dm->id ? 'selected' : '' }}>
                                {{ $dm->f_name }} {{ $dm->l_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">{{ translate('From') }}</label>
                    <input type="date" name="from" class="form-control" value="{{ $from }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">{{ translate('To') }}</label>
                    <input type="date" name="to" class="form-control" value="{{ $to }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-block">{{ translate('Apply') }}</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.accounts.reports.dm-statement') }}" class="btn btn-outline-secondary btn-block">{{ translate('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    @if($statement)

        {{-- Summary --}}
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card card-body text-center">
                    <small class="text-muted">{{ translate('Opening Balance') }}</small>
                    <h3 class="text-secondary mb-0">{{ number_format($statement['opening_balance'], 2) }}</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-body text-center border-left border-primary">
                    <small class="text-muted">{{ translate('Closing Balance') }}</small>
                    <h3 class="{{ $statement['closing_balance'] >= 0 ? 'text-primary' : 'text-danger' }} mb-0">
                        {{ number_format($statement['closing_balance'], 2) }}
                    </h3>
                    <small class="text-muted">{{ translate('Amount owed to DM') }}</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-body text-center">
                    <small class="text-muted">{{ translate('Period Transactions') }}</small>
                    <h3 class="text-info mb-0">{{ $statement['rows']->count() }}</h3>
                </div>
            </div>
        </div>

        {{-- Ledger table --}}
        <div class="card">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    {{ translate('Account') }} {{ $statement['account']->code }} — {{ $statement['account']->name }}
                </h5>
                <small class="text-muted">{{ $from }} &mdash; {{ $to }}</small>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Date') }}</th>
                            <th>{{ translate('Entry #') }}</th>
                            <th>{{ translate('Event') }}</th>
                            <th>{{ translate('Reference') }}</th>
                            <th>{{ translate('Description') }}</th>
                            <th class="text-right">{{ translate('Debit') }}</th>
                            <th class="text-right">{{ translate('Credit') }}</th>
                            <th class="text-right">{{ translate('Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="bg-light font-italic">
                            <td colspan="7" class="text-right font-weight-bold">{{ translate('Opening Balance') }}</td>
                            <td class="text-right font-weight-bold">{{ number_format($statement['opening_balance'], 2) }}</td>
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
                                <td>{{ $row->description ?? '—' }}</td>
                                <td class="text-right text-danger">{{ $row->debit  > 0 ? number_format($row->debit,  2) : '—' }}</td>
                                <td class="text-right text-success">{{ $row->credit > 0 ? number_format($row->credit, 2) : '—' }}</td>
                                <td class="text-right font-weight-bold {{ $row->running_balance < 0 ? 'text-danger' : '' }}">
                                    {{ number_format($row->running_balance, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    {{ translate('No entries for this delivery man in the selected period.') }}
                                </td>
                            </tr>
                        @endforelse

                        <tr class="bg-light font-weight-bold">
                            <td colspan="7" class="text-right">{{ translate('Closing Balance') }}</td>
                            <td class="text-right {{ $statement['closing_balance'] < 0 ? 'text-danger' : 'text-primary' }}">
                                {{ number_format($statement['closing_balance'], 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    @else
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="tio-user" style="font-size:3rem"></i>
                <p class="mt-2">{{ translate('Select a delivery man above to view their statement.') }}</p>
            </div>
        </div>
    @endif

</div>
@endsection
