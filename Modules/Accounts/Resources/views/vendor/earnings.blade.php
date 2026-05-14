@extends('layouts.vendor.app')
@section('title', translate('Earnings Report'))

@section('content')
<div class="content container-fluid">

    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <span class="page-header-icon"><i class="tio-chart-pie-1 nav-icon"></i></span>
                    {{ translate('Earnings Report') }}
                </h1>
            </div>
            <div class="col-sm-auto">
                <span class="badge badge-soft-success py-2 px-3">
                    <i class="tio-filter-list mr-1"></i>{{ $store->name }}
                </span>
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
                            @forelse($revenueRows as $row)
                                <tr>
                                    <td class="font-weight-bold">{{ $row->account_code }}</td>
                                    <td>{{ $row->account_name }}</td>
                                    <td class="text-right text-success">{{ \App\CentralLogics\Helpers::format_currency($row->amount) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">{{ translate('No revenue in this period') }}</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="font-weight-bold bg-light">
                            <tr>
                                <td colspan="2" class="text-right">{{ translate('Total Revenue') }}</td>
                                <td class="text-right text-success">{{ \App\CentralLogics\Helpers::format_currency($totalRevenue) }}</td>
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
                    <h5 class="card-title mb-0 text-danger">{{ translate('Expenses / Deductions') }}</h5>
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
                            @forelse($expenseRows as $row)
                                <tr>
                                    <td class="font-weight-bold">{{ $row->account_code }}</td>
                                    <td>{{ $row->account_name }}</td>
                                    <td class="text-right text-danger">{{ \App\CentralLogics\Helpers::format_currency($row->amount) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">{{ translate('No deductions in this period') }}</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="font-weight-bold bg-light">
                            <tr>
                                <td colspan="2" class="text-right">{{ translate('Total Deductions') }}</td>
                                <td class="text-right text-danger">{{ \App\CentralLogics\Helpers::format_currency($totalExpenses) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Net --}}
    <div class="card">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h4 class="mb-0">
                        {{ $netEarnings >= 0 ? translate('Net Earnings') : translate('Net Loss') }}
                        <small class="text-muted font-weight-normal ml-2">{{ $from }} — {{ $to }}</small>
                    </h4>
                </div>
                <div class="col-auto">
                    <h3 class="mb-0 {{ $netEarnings >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ \App\CentralLogics\Helpers::format_currency(abs($netEarnings)) }}
                        @if($netEarnings < 0)<small>(Loss)</small>@endif
                    </h3>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
