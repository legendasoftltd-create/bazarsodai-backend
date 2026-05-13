@extends('layouts.admin.app')
@section('title', translate('Inventory Adjustments'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-tune"></i></span>
            <span>{{ translate('Inventory Adjustments') }}</span>
        </h1>
        <a href="{{ route('admin.inventory.adjustments.create') }}" class="btn btn-primary btn-sm ml-auto">
            <i class="tio-add"></i> {{ translate('New Adjustment') }}
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
                        @foreach(['draft','pending_approval','approved','rejected'] as $s)
                            <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-block">{{ translate('Filter') }}</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.inventory.adjustments.index') }}" class="btn btn-outline-secondary btn-block">{{ translate('Reset') }}</a>
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
                            <th>{{ translate('Vendor') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Date') }}</th>
                            <th>{{ translate('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($adjustments as $i => $adj)
                        @php
                            $colors = ['draft'=>'secondary','pending_approval'=>'warning','approved'=>'success','rejected'=>'danger'];
                        @endphp
                        <tr>
                            <td>{{ $adjustments->firstItem() + $i }}</td>
                            <td><strong>{{ $adj->adjustment_number }}</strong></td>
                            <td>{{ $adj->store?->name ?? '—' }}</td>
                            <td><span class="badge badge-soft-{{ $colors[$adj->status] ?? 'secondary' }}">{{ ucwords(str_replace('_',' ',$adj->status)) }}</span></td>
                            <td>{{ $adj->created_at->format('d M Y') }}</td>
                            <td class="d-flex gap-1">
                                <a href="{{ route('admin.inventory.adjustments.show', $adj->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="tio-visible"></i>
                                </a>
                                @if($adj->status === 'pending_approval')
                                    <form method="POST" action="{{ route('admin.inventory.adjustments.approve', $adj->id) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-success" onclick="return confirm('{{ translate('Approve and apply stock changes?') }}')">
                                            <i class="tio-checkmark-circle"></i> {{ translate('Approve') }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.inventory.adjustments.reject', $adj->id) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ translate('Reject this adjustment?') }}')">
                                            <i class="tio-clear"></i> {{ translate('Reject') }}
                                        </button>
                                    </form>
                                @endif
                                @if($adj->status === 'draft')
                                    <form method="POST" action="{{ route('admin.inventory.adjustments.destroy', $adj->id) }}" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ translate('Delete?') }}')">
                                            <i class="tio-delete"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-4">{{ translate('No adjustments found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $adjustments->links() }}</div>
        </div>
    </div>
</div>
@endsection
