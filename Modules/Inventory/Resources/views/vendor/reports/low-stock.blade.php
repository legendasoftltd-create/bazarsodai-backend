@extends('layouts.vendor.app')
@section('title', translate('Low Stock Report'))
@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-warning-outlined"></i></span>
            <span>{{ translate('Low Stock Report') }}</span>
        </h1>
        <span class="text-muted">{{ translate('Items at or below threshold') }}: <strong>{{ $threshold }}</strong></span>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>{{ translate('Item') }}</th>
                        <th>{{ translate('Current Stock') }}</th>
                        <th>{{ translate('Avg Cost') }}</th>
                        <th>{{ translate('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $item->name }}</strong></td>
                        <td><span class="badge badge-soft-warning">{{ $item->stock }}</span></td>
                        <td>{{ number_format($item->average_cost, 2) }}</td>
                        <td>
                            <a href="{{ route('vendor.inventory.item-detail', $item->id) }}" class="btn btn-sm btn-outline-primary">{{ translate('Detail') }}</a>
                            <a href="{{ route('vendor.inventory.purchases.create') }}" class="btn btn-sm btn-outline-success">{{ translate('Reorder') }}</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-4">{{ translate('No low stock items') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $items->links() }}</div>
    </div>
</div>
@endsection
