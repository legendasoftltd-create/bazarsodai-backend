@extends('layouts.admin.app')
@section('title', translate('General Ledger'))

@section('content')
<div class="content container-fluid">

    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <span class="page-header-icon"><i class="tio-book-outlined nav-icon"></i></span>
                    {{ translate('General Ledger') }}
                </h1>
            </div>
            <div class="col-sm-auto">
                @include('accounts::admin.partials._scope_badge')
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label mb-1">{{ translate('Account') }}</label>
                    <select name="account_id" class="form-control">
                        <option value="">-- {{ translate('Select Account') }} --</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ (string)$accountId === (string)$acc->id ? 'selected' : '' }}>
                                {{ $acc->code }} — {{ $acc->name }}
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
                    <a href="{{ route('admin.accounts.reports.general-ledger') }}" class="btn btn-outline-secondary btn-block">{{ translate('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    @if($ledger)
        @php
            $account = $ledger['account'];
            $rows    = $ledger['rows'];
        @endphp

        <div class="card">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <span class="font-weight-bold">{{ $account->code }}</span> — {{ $account->name }}
                    <span class="badge badge-soft-secondary ml-2">{{ ucfirst($account->type) }}</span>
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
                        {{-- Opening balance row --}}
                        <tr class="bg-light font-italic">
                            <td colspan="7" class="text-right font-weight-bold">{{ translate('Opening Balance') }}</td>
                            <td class="text-right font-weight-bold">
                                {{ number_format(abs($ledger['opening_balance']), 2) }}
                                @if($ledger['opening_balance'] < 0) <small class="text-muted">(Cr)</small> @endif
                            </td>
                        </tr>

                        @forelse($rows as $row)
                            <tr>
                                <td class="text-nowrap">{{ \Carbon\Carbon::parse($row->posted_at)->format('Y-m-d') }}</td>
                                <td class="text-nowrap">
                                    <span class="font-weight-bold">{{ $row->entry_number }}</span>
                                </td>
                                <td>
                                    <small class="badge badge-soft-info">{{ str_replace('_', ' ', $row->event_type) }}</small>
                                </td>
                                <td class="text-nowrap">
                                    @if($row->reference_type && $row->reference_id)
                                        <small class="text-muted">{{ $row->reference_type }} #{{ $row->reference_id }}</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $row->description ?? '—' }}</td>
                                <td class="text-right">{{ $row->debit > 0 ? number_format($row->debit, 2) : '—' }}</td>
                                <td class="text-right">{{ $row->credit > 0 ? number_format($row->credit, 2) : '—' }}</td>
                                <td class="text-right font-weight-bold {{ $row->running_balance < 0 ? 'text-danger' : '' }}">
                                    {{ number_format(abs($row->running_balance), 2) }}
                                    @if($row->running_balance < 0) <small class="text-muted">(Cr)</small> @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    {{ translate('No entries for this account in the selected period.') }}
                                </td>
                            </tr>
                        @endforelse

                        {{-- Closing balance row --}}
                        <tr class="bg-light font-weight-bold">
                            <td colspan="7" class="text-right">{{ translate('Closing Balance') }}</td>
                            <td class="text-right">
                                {{ number_format(abs($ledger['closing_balance']), 2) }}
                                @if($ledger['closing_balance'] < 0) <small class="text-muted">(Cr)</small> @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    @else
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="tio-book-outlined" style="font-size:3rem"></i>
                <p class="mt-2">{{ translate('Select an account above to view its ledger.') }}</p>
            </div>
        </div>
    @endif

</div>
@endsection
