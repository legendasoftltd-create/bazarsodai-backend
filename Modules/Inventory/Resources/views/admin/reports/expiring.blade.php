@extends('layouts.admin.app')
@section('title', translate('Expiring Stock Report'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-time"></i></span>
            <span>{{ translate('Expiring Stock Report') }}</span>
        </h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <select name="store_id" class="form-control">
                        <option value="">{{ translate('All Vendors') }}</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="days" class="form-control">
                        @foreach([7, 14, 30, 60, 90] as $d)
                            <option value="{{ $d }}" {{ $days == $d ? 'selected' : '' }}>{{ translate('Within') }} {{ $d }} {{ translate('days') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-block">{{ translate('Filter') }}</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.inventory.reports.expiring') }}" class="btn btn-outline-secondary btn-block">{{ translate('Reset') }}</a>
                </div>
                <div class="col-md-3 text-right">
                    <a href="{{ route('admin.inventory.reports.export', 'expiring') }}" class="btn btn-outline-success btn-block">
                        <i class="tio-file-text-outlined"></i> {{ translate('Export') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card text-center py-2 border-danger">
                <h4 class="text-danger mb-0">{{ $expiredCount }}</h4>
                <p class="text-muted small mb-0">{{ translate('Already Expired') }}</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center py-2 border-warning">
                <h4 class="text-warning mb-0">{{ $expiringCount }}</h4>
                <p class="text-muted small mb-0">{{ translate('Expiring within') }} {{ $days }} {{ translate('days') }}</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center py-2">
                <h4 class="mb-0">{{ number_format($totalExpiringQty, 1) }}</h4>
                <p class="text-muted small mb-0">{{ translate('Total Qty at Risk') }}</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center py-2">
                <h4 class="mb-0">{{ number_format($totalExpiringValue, 2) }}</h4>
                <p class="text-muted small mb-0">{{ translate('Value at Risk') }}</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Item') }}</th>
                            <th>{{ translate('Vendor') }}</th>
                            <th>{{ translate('Batch #') }}</th>
                            <th>{{ translate('Qty Remaining') }}</th>
                            <th>{{ translate('Unit Cost') }}</th>
                            <th>{{ translate('Value') }}</th>
                            <th>{{ translate('Expires') }}</th>
                            <th>{{ translate('Days Left') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batches as $batch)
                        @php
                            $daysLeft = (int) now()->diffInDays($batch->expires_at, false);
                            $rowClass = $daysLeft < 0 ? 'table-danger' : ($daysLeft <= 7 ? 'table-warning' : '');
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td><strong>{{ $batch->item?->name ?? "Item #{$batch->item_id}" }}</strong></td>
                            <td>{{ $batch->item?->store?->name ?? '—' }}</td>
                            <td>{{ $batch->batch_number ?? "Batch #{$batch->id}" }}</td>
                            <td>{{ $batch->qty_remaining }}</td>
                            <td>{{ number_format($batch->unit_cost, 2) }}</td>
                            <td>{{ number_format($batch->qty_remaining * $batch->unit_cost, 2) }}</td>
                            <td>{{ $batch->expires_at?->format('d M Y') }}</td>
                            <td>
                                @if($daysLeft < 0)
                                    <span class="badge badge-soft-danger">{{ translate('Expired') }} ({{ abs($daysLeft) }}d ago)</span>
                                @else
                                    <span class="badge badge-soft-{{ $daysLeft <= 7 ? 'danger' : ($daysLeft <= 14 ? 'warning' : 'info') }}">
                                        {{ $daysLeft }} {{ translate('days') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center py-4">{{ translate('No expiring stock found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $batches->links() }}</div>
        </div>
    </div>
</div>
@endsection
