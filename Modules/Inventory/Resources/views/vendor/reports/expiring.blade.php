@extends('layouts.vendor.app')
@section('title', translate('Expiring Stock'))
@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-time"></i></span>
            <span>{{ translate('Expiring Stock') }}</span>
        </h1>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <select name="days" class="form-control">
                        @foreach([7, 14, 30, 60, 90] as $d)
                            <option value="{{ $d }}" {{ $days == $d ? 'selected' : '' }}>{{ translate('Within') }} {{ $d }} {{ translate('days') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-block">{{ translate('Filter') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Item') }}</th>
                        <th>{{ translate('Batch #') }}</th>
                        <th>{{ translate('Qty Remaining') }}</th>
                        <th>{{ translate('Expires') }}</th>
                        <th>{{ translate('Days Left') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                    @php $daysLeft = now()->diffInDays($batch->expires_at, false); @endphp
                    <tr>
                        <td><strong>{{ $batch->item?->name ?? "Item #{$batch->item_id}" }}</strong></td>
                        <td>{{ $batch->batch_number ?? '—' }}</td>
                        <td>{{ $batch->qty_remaining }}</td>
                        <td>{{ $batch->expires_at?->format('d M Y') }}</td>
                        <td>
                            <span class="badge badge-soft-{{ $daysLeft <= 7 ? 'danger' : ($daysLeft <= 14 ? 'warning' : 'info') }}">
                                {{ $daysLeft }} {{ translate('days') }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-4">{{ translate('No expiring stock found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $batches->links() }}</div>
    </div>
</div>
@endsection
