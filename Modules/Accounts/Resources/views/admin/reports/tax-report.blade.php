@extends('layouts.admin.app')
@section('title', translate('Tax Report'))

@section('content')
<div class="content container-fluid">

    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <span class="page-header-icon"><i class="tio-receipt nav-icon"></i></span>
                    {{ translate('Tax Report') }}
                    <small class="text-muted font-weight-normal ml-2">{{ translate('Account 2031 — VAT / Tax Collected Payable') }}</small>
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
                    <a href="{{ route('admin.accounts.reports.tax-report') }}" class="btn btn-outline-secondary btn-block">{{ translate('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card card-body text-center border-left border-success">
                <small class="text-muted">{{ translate('Total Collected') }}</small>
                <h3 class="text-success mb-0">{{ number_format($total_collected, 2) }}</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-body text-center border-left border-danger">
                <small class="text-muted">{{ translate('Total Remitted') }}</small>
                <h3 class="text-danger mb-0">{{ number_format($total_remitted, 2) }}</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-body text-center border-left border-warning">
                <small class="text-muted">{{ translate('Net Payable') }}</small>
                <h3 class="{{ $net_payable > 0 ? 'text-warning' : 'text-success' }} mb-0">{{ number_format($net_payable, 2) }}</h3>
            </div>
        </div>
    </div>

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
                        <th class="text-right">{{ translate('Remitted (DR)') }}</th>
                        <th class="text-right">{{ translate('Collected (CR)') }}</th>
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
                            <td class="text-right text-danger">{{ $row->debit  > 0 ? number_format($row->debit,  2) : '—' }}</td>
                            <td class="text-right text-success">{{ $row->credit > 0 ? number_format($row->credit, 2) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">{{ translate('No tax entries in this period.') }}</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="font-weight-bold bg-light">
                    <tr>
                        <td colspan="5" class="text-right">{{ translate('Totals') }}</td>
                        <td class="text-right text-danger">{{ number_format($total_remitted, 2) }}</td>
                        <td class="text-right text-success">{{ number_format($total_collected, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection
