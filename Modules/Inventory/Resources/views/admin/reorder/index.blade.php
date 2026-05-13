@extends('layouts.admin.app')
@section('title', translate('Reorder Points'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-bell-outlined"></i></span>
            <span>{{ translate('Reorder Points') }}
                <span class="badge badge-soft-secondary">{{ $reorderPoints->total() }}</span>
            </span>
        </h1>
        <a href="{{ route('admin.inventory.reorder-points.create') }}" class="btn btn-primary btn-sm">
            <i class="tio-add"></i> {{ translate('Add Reorder Point') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="{{ translate('Search item') }}" value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="store_id" class="form-control">
                        <option value="">{{ translate('All Vendors') }}</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-block">{{ translate('Filter') }}</button>
                </div>
                <div class="col-md-3 text-right">
                    <a href="{{ route('admin.inventory.reorder-points.index') }}" class="btn btn-outline-secondary">{{ translate('Reset') }}</a>
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
                            <th>{{ translate('Item') }}</th>
                            <th>{{ translate('Vendor') }}</th>
                            <th>{{ translate('Current Stock') }}</th>
                            <th>{{ translate('Reorder At') }}</th>
                            <th>{{ translate('Reorder Qty') }}</th>
                            <th>{{ translate('Alert') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reorderPoints as $rp)
                        @php $belowThreshold = $rp->item && $rp->item->stock <= $rp->reorder_at; @endphp
                        <tr class="{{ $belowThreshold ? 'table-warning' : '' }}">
                            <td>
                                <strong>{{ $rp->item?->name ?? "Item #{$rp->item_id}" }}</strong>
                                @if($rp->variation_key)
                                    <small class="text-muted d-block">{{ $rp->variation_key }}</small>
                                @endif
                            </td>
                            <td>{{ $rp->store?->name ?? '—' }}</td>
                            <td>
                                <span class="{{ $belowThreshold ? 'text-danger font-weight-bold' : 'text-success' }}">
                                    {{ $rp->item?->stock ?? '—' }}
                                </span>
                            </td>
                            <td>{{ $rp->reorder_at }}</td>
                            <td>{{ $rp->reorder_qty }}</td>
                            <td>
                                @if($rp->auto_notify)
                                    <span class="badge badge-soft-success">{{ translate('Email On') }}</span>
                                @else
                                    <span class="badge badge-soft-secondary">{{ translate('Email Off') }}</span>
                                @endif
                            </td>
                            <td>
                                @if($belowThreshold)
                                    <span class="badge badge-soft-danger">{{ translate('Below Threshold') }}</span>
                                @else
                                    <span class="badge badge-soft-success">{{ translate('OK') }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.inventory.reorder-points.edit', $rp->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="tio-edit"></i>
                                </a>
                                <form action="{{ route('admin.inventory.reorder-points.destroy', $rp->id) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('{{ translate('Delete this reorder point?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="tio-delete-outlined"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center py-4">{{ translate('No reorder points configured') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $reorderPoints->links() }}</div>
        </div>
    </div>
</div>
@endsection
