@extends('layouts.admin.app')
@section('title', translate('Profit & Loss'))

@section('content')
<div class="content container-fluid">

    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <span class="page-header-icon"><i class="tio-chart-pie-1 nav-icon"></i></span>
                    {{ translate('Profit & Loss Statement') }}
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
                    <a href="{{ route('admin.accounts.reports.profit-loss') }}" class="btn btn-outline-secondary btn-block">{{ translate('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        {{-- Revenue --}}
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-soft-success py-2">
                    <h5 class="card-title mb-0 text-success">{{ translate('Revenue') }}</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('Code') }}</th>
                                <th>{{ translate('Account') }}</th>
                                <th class="text-right">{{ translate('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($revenue_rows as $row)
                                <tr>
                                    <td class="font-weight-bold">{{ $row->account_code }}</td>
                                    <td>{{ $row->account_name }}</td>
                                    <td class="text-right text-success">{{ number_format($row->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">{{ translate('No revenue in this period') }}</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="font-weight-bold bg-light">
                            <tr>
                                <td colspan="2" class="text-right">{{ translate('Total Revenue') }}</td>
                                <td class="text-right text-success">{{ number_format($total_revenue, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Expenses --}}
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-soft-danger py-2">
                    <h5 class="card-title mb-0 text-danger">{{ translate('Expenses') }}</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('Code') }}</th>
                                <th>{{ translate('Account') }}</th>
                                <th class="text-right">{{ translate('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expense_rows as $row)
                                <tr>
                                    <td class="font-weight-bold">{{ $row->account_code }}</td>
                                    <td>{{ $row->account_name }}</td>
                                    <td class="text-right text-danger">{{ number_format($row->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">{{ translate('No expenses in this period') }}</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="font-weight-bold bg-light">
                            <tr>
                                <td colspan="2" class="text-right">{{ translate('Total Expenses') }}</td>
                                <td class="text-right text-danger">{{ number_format($total_expenses, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Net Profit / Loss --}}
    <div class="card">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h4 class="mb-0">
                        {{ $net_profit >= 0 ? translate('Net Profit') : translate('Net Loss') }}
                        <small class="text-muted font-weight-normal ml-2">{{ $from }} &mdash; {{ $to }}</small>
                    </h4>
                </div>
                <div class="col-auto">
                    <h3 class="mb-0 {{ $net_profit >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $net_profit < 0 ? '(' : '' }}{{ number_format(abs($net_profit), 2) }}{{ $net_profit < 0 ? ')' : '' }}
                    </h3>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
