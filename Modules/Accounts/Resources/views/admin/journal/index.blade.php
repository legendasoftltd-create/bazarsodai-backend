@extends('layouts.admin.app')
@section('title', translate('Journal Entries'))

@section('content')
<div class="content container-fluid">

    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <span class="page-header-icon"><i class="tio-book-outlined nav-icon"></i></span>
                    {{ translate('Journal Entries') }}
                </h1>
            </div>
            <div class="col-sm-auto">
                <a href="{{ route('admin.accounts.journal.create') }}" class="btn btn-primary">
                    <i class="tio-add mr-1"></i> {{ translate('Manual Entry') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Search --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <input type="text" name="q" class="form-control" placeholder="{{ translate('Entry #, event, reference…') }}" value="{{ request('q') }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control">
                        <option value="">{{ translate('All statuses') }}</option>
                        @foreach(['posted','reversed'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <button class="btn btn-primary btn-block">{{ translate('Search') }}</button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('admin.accounts.journal.index') }}" class="btn btn-outline-secondary btn-block">{{ translate('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Entry #') }}</th>
                        <th>{{ translate('Date') }}</th>
                        <th>{{ translate('Event') }}</th>
                        <th>{{ translate('Reference') }}</th>
                        <th>{{ translate('Description') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th class="text-right">{{ translate('Total DR') }}</th>
                        <th class="text-right" width="60">{{ translate('View') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                        <tr>
                            <td class="font-weight-bold text-nowrap">{{ $entry->entry_number }}</td>
                            <td class="text-nowrap">{{ $entry->posted_at?->format('Y-m-d H:i') }}</td>
                            <td><small class="badge badge-soft-info">{{ str_replace('_', ' ', $entry->event_type) }}</small></td>
                            <td class="text-nowrap">
                                @if($entry->reference_type && $entry->reference_id)
                                    <small class="text-muted">{{ $entry->reference_type }} #{{ $entry->reference_id }}</small>
                                @else —
                                @endif
                            </td>
                            <td>{{ Str::limit($entry->description, 40) ?? '—' }}</td>
                            <td>
                                @if($entry->status === 'posted')
                                    <span class="badge badge-soft-success">{{ translate('Posted') }}</span>
                                @else
                                    <span class="badge badge-soft-secondary">{{ ucfirst($entry->status) }}</span>
                                @endif
                            </td>
                            <td class="text-right">{{ number_format($entry->lines->sum('debit'), 2) }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.accounts.journal.show', $entry) }}" class="btn btn-xs btn-outline-primary">
                                    <i class="tio-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-4 text-muted">{{ translate('No journal entries found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $entries->links() }}
        </div>
    </div>

</div>
@endsection
