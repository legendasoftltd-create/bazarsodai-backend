@extends('layouts.vendor.app')
@section('title', translate('My Reorder Points'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-bell-outlined"></i></span>
            <span>{{ translate('Reorder Points') }}
                <span class="badge badge-soft-secondary">{{ $reorderPoints->total() }}</span>
            </span>
        </h1>
        <a href="{{ route('vendor.inventory.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="tio-arrow-backward"></i> {{ translate('Back to Inventory') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="alert alert-info">
        <i class="tio-info-outined mr-1"></i>
        {{ translate('To add or update a reorder point, go to the item detail page.') }}
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Item') }}</th>
                            <th>{{ translate('Current Stock') }}</th>
                            <th>{{ translate('Reorder At') }}</th>
                            <th>{{ translate('Reorder Qty') }}</th>
                            <th>{{ translate('Email Alert') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reorderPoints as $rp)
                        @php $below = $rp->item && $rp->item->stock <= $rp->reorder_at; @endphp
                        <tr class="{{ $below ? 'table-warning' : '' }}">
                            <td>
                                <strong>{{ $rp->item?->name ?? "Item #{$rp->item_id}" }}</strong>
                                @if($rp->variation_key)
                                    <small class="text-muted d-block">{{ $rp->variation_key }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="{{ $below ? 'text-danger font-weight-bold' : 'text-success' }}">
                                    {{ $rp->item?->stock ?? '—' }}
                                </span>
                            </td>
                            <td>{{ $rp->reorder_at }}</td>
                            <td>{{ $rp->reorder_qty }}</td>
                            <td>
                                @if($rp->auto_notify)
                                    <span class="badge badge-soft-success"><i class="tio-checkmark-circle"></i> {{ translate('On') }}</span>
                                @else
                                    <span class="badge badge-soft-secondary">{{ translate('Off') }}</span>
                                @endif
                            </td>
                            <td>
                                @if($below)
                                    <span class="badge badge-soft-danger">{{ translate('Low') }}</span>
                                @else
                                    <span class="badge badge-soft-success">{{ translate('OK') }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('vendor.inventory.item-detail', $rp->item_id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="tio-edit"></i> {{ translate('Edit') }}
                                </a>
                                <form action="{{ route('vendor.inventory.reorder-points.destroy', $rp->id) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('{{ translate('Remove this reorder point?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="tio-delete-outlined"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                {{ translate('No reorder points set yet.') }}
                                <br>
                                <small class="text-muted">{{ translate('Go to an item detail page to set one.') }}</small>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $reorderPoints->links() }}</div>
        </div>
    </div>
</div>
@endsection
