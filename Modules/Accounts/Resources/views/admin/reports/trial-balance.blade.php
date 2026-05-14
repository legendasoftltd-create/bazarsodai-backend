@extends('layouts.admin.app')
@section('title', translate('Trial Balance'))

@section('content')
<div class="content container-fluid">

    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <span class="page-header-icon"><i class="tio-chart-bar-1 nav-icon"></i></span>
                    {{ translate('Trial Balance') }}
                </h1>
            </div>
            <div class="col-sm-auto">
                @include('accounts::admin.partials._scope_badge')
                @if($balanced)
                    <span class="badge badge-soft-success py-2 px-3"><i class="tio-checkmark-circle mr-1"></i>{{ translate('Balanced') }}</span>
                @else
                    <span class="badge badge-soft-danger py-2 px-3"><i class="tio-warning mr-1"></i>{{ translate('UNBALANCED') }}</span>
                @endif
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
                    <a href="{{ route('admin.accounts.reports.trial-balance') }}" class="btn btn-outline-secondary btn-block">{{ translate('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-header py-2">
            <h5 class="card-title mb-0">
                {{ translate('Period') }}: {{ $from }} &mdash; {{ $to }}
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Code') }}</th>
                        <th>{{ translate('Account') }}</th>
                        <th>{{ translate('Type') }}</th>
                        <th class="text-right">{{ translate('Debit') }}</th>
                        <th class="text-right">{{ translate('Credit') }}</th>
                        <th class="text-right">{{ translate('Balance') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $typeLabel = ucfirst($row->type);
                            $typeBadge = match($row->type) {
                                'asset'    => 'badge-soft-primary',
                                'liability'=> 'badge-soft-warning',
                                'equity'   => 'badge-soft-info',
                                'revenue'  => 'badge-soft-success',
                                'expense'  => 'badge-soft-danger',
                                default    => 'badge-soft-secondary',
                            };
                        @endphp
                        <tr>
                            <td class="font-weight-bold">{{ $row->account_code }}</td>
                            <td>{{ $row->account_name }}</td>
                            <td><span class="badge {{ $typeBadge }}">{{ $typeLabel }}</span></td>
                            <td class="text-right">{{ number_format($row->total_debit, 2) }}</td>
                            <td class="text-right">{{ number_format($row->total_credit, 2) }}</td>
                            <td class="text-right font-weight-bold {{ $row->balance < 0 ? 'text-danger' : '' }}">
                                {{ number_format(abs($row->balance), 2) }}
                                @if($row->balance < 0) <small class="text-muted">(Cr)</small> @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">{{ translate('No journal entries in this period.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="font-weight-bold bg-light">
                    <tr>
                        <td colspan="3" class="text-right">{{ translate('Totals') }}</td>
                        <td class="text-right">{{ number_format($total_debit, 2) }}</td>
                        <td class="text-right">{{ number_format($total_credit, 2) }}</td>
                        <td class="text-right {{ !$balanced ? 'text-danger' : '' }}">
                            {{ number_format(abs($total_debit - $total_credit), 2) }}
                            @if(!$balanced) <i class="tio-warning text-danger ml-1"></i> @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection
