@extends('layouts.admin.app')
@section('title', translate('Stock Transfers'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="d-flex align-items-center">
            <h1 class="page-header-title">
                <span class="page-header-icon"><i class="tio-swap-horizontal"></i></span>
                <span>{{ translate('Stock Transfers') }}</span>
            </h1>
        </div>
        <a href="{{ route('admin.inventory.transfers.create') }}" class="btn btn-primary btn-sm ml-auto">
            <i class="tio-add"></i> {{ translate('New Transfer') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <select name="store_id" class="form-control">
                        <option value="">{{ translate('All Vendors') }}</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-control">
                        <option value="">{{ translate('All Statuses') }}</option>
                        @foreach(['pending','in_transit','received'] as $s)
                            <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-block">{{ translate('Filter') }}</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.inventory.transfers.index') }}" class="btn btn-outline-secondary btn-block">{{ translate('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>{{ translate('Ref #') }}</th>
                            <th>{{ translate('From') }}</th>
                            <th>{{ translate('To') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Date') }}</th>
                            <th>{{ translate('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transfers as $i => $transfer)
                        @php
                            $colors = ['pending'=>'secondary','in_transit'=>'warning','received'=>'success'];
                        @endphp
                        <tr>
                            <td>{{ $transfers->firstItem() + $i }}</td>
                            <td><strong>{{ $transfer->transfer_number }}</strong></td>
                            <td>{{ $transfer->fromStore?->name ?? '—' }}</td>
                            <td>{{ $transfer->toStore?->name ?? '—' }}</td>
                            <td><span class="badge badge-soft-{{ $colors[$transfer->status] ?? 'secondary' }}">{{ ucwords(str_replace('_',' ',$transfer->status)) }}</span></td>
                            <td>{{ $transfer->created_at->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('admin.inventory.transfers.show', $transfer->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="tio-visible"></i>
                                </a>
                                @if($transfer->status === 'in_transit')
                                <form method="POST" action="{{ route('admin.inventory.transfers.receive', $transfer->id) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-success" onclick="return confirm('{{ translate('Mark as received?') }}')">
                                        <i class="tio-checkmark-circle"></i> {{ translate('Receive') }}
                                    </button>
                                </form>
                                @endif
                                @if($transfer->status === 'pending')
                                <form method="POST" action="{{ route('admin.inventory.transfers.destroy', $transfer->id) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ translate('Delete this transfer?') }}')">
                                        <i class="tio-delete"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-4">{{ translate('No transfers found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $transfers->links() }}</div>
        </div>
    </div>
</div>
@endsection
