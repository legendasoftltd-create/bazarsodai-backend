@extends('layouts.admin.app')
@section('title', translate('Balance Sheet'))

@section('content')
<div class="content container-fluid">

    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <span class="page-header-icon"><i class="tio-chart-bar-2 nav-icon"></i></span>
                    {{ translate('Balance Sheet') }}
                </h1>
            </div>
            <div class="col-sm-auto d-flex gap-2">
                @include('accounts::admin.partials._scope_badge')
                @if($balanced)
                    <span class="badge badge-soft-success py-2 px-3 align-self-center"><i class="tio-checkmark-circle mr-1"></i>{{ translate('Balanced') }}</span>
                @else
                    <span class="badge badge-soft-danger py-2 px-3 align-self-center"><i class="tio-warning mr-1"></i>{{ translate('UNBALANCED') }}</span>
                @endif
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
                    <label class="form-label mb-1">{{ translate('As at Date') }}</label>
                    <input type="date" name="date" class="form-control" value="{{ $date }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-block">{{ translate('Apply') }}</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.accounts.reports.balance-sheet') }}" class="btn btn-outline-secondary btn-block">{{ translate('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    @php
        $sectionTable = function(string $title, $rows, float $total, string $colorClass) { return [$title, $rows, $total, $colorClass]; };
    @endphp

    <div class="row">
        {{-- Left: Assets --}}
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header bg-soft-primary py-2">
                    <h5 class="card-title mb-0 text-primary">{{ translate('Assets') }}</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light"><tr><th>{{ translate('Code') }}</th><th>{{ translate('Account') }}</th><th class="text-right">{{ translate('Balance') }}</th></tr></thead>
                        <tbody>
                            @forelse($asset_rows as $row)
                                <tr>
                                    <td class="font-weight-bold">{{ $row->account_code }}</td>
                                    <td>{{ $row->account_name }}</td>
                                    <td class="text-right">{{ number_format($row->balance, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">{{ translate('No assets') }}</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="font-weight-bold bg-light">
                            <tr>
                                <td colspan="2" class="text-right">{{ translate('Total Assets') }}</td>
                                <td class="text-right text-primary">{{ number_format($total_assets, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Middle: Liabilities --}}
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header bg-soft-warning py-2">
                    <h5 class="card-title mb-0 text-warning">{{ translate('Liabilities') }}</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light"><tr><th>{{ translate('Code') }}</th><th>{{ translate('Account') }}</th><th class="text-right">{{ translate('Balance') }}</th></tr></thead>
                        <tbody>
                            @forelse($liability_rows as $row)
                                <tr>
                                    <td class="font-weight-bold">{{ $row->account_code }}</td>
                                    <td>{{ $row->account_name }}</td>
                                    <td class="text-right">{{ number_format($row->balance, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">{{ translate('No liabilities') }}</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="font-weight-bold bg-light">
                            <tr>
                                <td colspan="2" class="text-right">{{ translate('Total Liabilities') }}</td>
                                <td class="text-right text-warning">{{ number_format($total_liabilities, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Right: Equity --}}
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header bg-soft-info py-2">
                    <h5 class="card-title mb-0 text-info">{{ translate('Equity') }}</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light"><tr><th>{{ translate('Code') }}</th><th>{{ translate('Account') }}</th><th class="text-right">{{ translate('Balance') }}</th></tr></thead>
                        <tbody>
                            @forelse($equity_rows as $row)
                                <tr>
                                    <td class="font-weight-bold">{{ $row->account_code }}</td>
                                    <td>{{ $row->account_name }}</td>
                                    <td class="text-right">{{ number_format($row->balance, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-2"></td></tr>
                            @endforelse
                            <tr class="{{ $net_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                <td class="font-weight-bold">—</td>
                                <td><em>{{ translate('Current Period Net') }}</em></td>
                                <td class="text-right font-weight-bold">
                                    {{ $net_profit < 0 ? '(' : '' }}{{ number_format(abs($net_profit), 2) }}{{ $net_profit < 0 ? ')' : '' }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="font-weight-bold bg-light">
                            <tr>
                                <td colspan="2" class="text-right">{{ translate('Total Equity') }}</td>
                                <td class="text-right text-info">{{ number_format($total_equity, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Accounting Equation Check --}}
    <div class="card">
        <div class="card-body py-3">
            <div class="row text-center">
                <div class="col">
                    <small class="text-muted d-block">{{ translate('Total Assets') }}</small>
                    <h4 class="text-primary mb-0">{{ number_format($total_assets, 2) }}</h4>
                </div>
                <div class="col-auto align-self-center"><h4 class="mb-0 text-muted">=</h4></div>
                <div class="col">
                    <small class="text-muted d-block">{{ translate('Total Liabilities') }}</small>
                    <h4 class="text-warning mb-0">{{ number_format($total_liabilities, 2) }}</h4>
                </div>
                <div class="col-auto align-self-center"><h4 class="mb-0 text-muted">+</h4></div>
                <div class="col">
                    <small class="text-muted d-block">{{ translate('Total Equity') }}</small>
                    <h4 class="text-info mb-0">{{ number_format($total_equity, 2) }}</h4>
                </div>
                <div class="col-auto align-self-center">
                    @if($balanced)
                        <span class="badge badge-soft-success py-2 px-3"><i class="tio-checkmark-circle"></i></span>
                    @else
                        <span class="badge badge-soft-danger py-2 px-3">
                            <i class="tio-warning"></i>
                            Δ {{ number_format(abs($total_assets - ($total_liabilities + $total_equity)), 2) }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
