@extends('layouts.vendor.app')
@section('title', translate('Purchase Orders'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-shopping-cart-outlined"></i></span>
            <span>{{ translate('Purchase Orders') }}
                <span class="badge badge-soft-secondary">{{ $orders->total() }}</span>
            </span>
        </h1>
        <a href="{{ route('vendor.inventory.purchases.create') }}" class="btn btn-primary btn-sm">
            <i class="tio-add"></i> {{ translate('New Purchase Order') }}
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
                    <input type="text" name="search" class="form-control" placeholder="{{ translate('Search PO number') }}" value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-control">
                        <option value="">{{ translate('All Status') }}</option>
                        @foreach(['draft','ordered','partial','received','cancelled'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-block">{{ translate('Filter') }}</button>
                </div>
                <div class="col-md-3 text-right">
                    <a href="{{ route('vendor.inventory.index') }}" class="btn btn-outline-secondary">
                        <i class="tio-layers-outlined"></i> {{ translate('My Inventory') }}
                    </a>
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
                            <th>{{ translate('PO #') }}</th>
                            <th>{{ translate('Supplier') }}</th>
                            <th>{{ translate('Items') }}</th>
                            <th>{{ translate('Total Cost') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Date') }}</th>
                            <th>{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $statusColors = ['draft'=>'secondary','ordered'=>'info','partial'=>'warning','received'=>'success','cancelled'=>'danger']; @endphp
                        @forelse($orders as $po)
                        <tr>
                            <td><strong>{{ $po->po_number }}</strong></td>
                            <td>{{ $po->supplier?->name ?? '—' }}</td>
                            <td>{{ $po->total_qty ?? 0 }}</td>
                            <td>{{ number_format($po->total_cost, 2) }}</td>
                            <td><span class="badge badge-soft-{{ $statusColors[$po->status] ?? 'secondary' }}">{{ ucfirst($po->status) }}</span></td>
                            <td>{{ $po->ordered_at?->format('d M Y') ?? $po->created_at->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('vendor.inventory.purchases.show', $po->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="tio-visible-outlined"></i>
                                </a>
                                @if($po->status === 'draft')
                                <form action="{{ route('vendor.inventory.purchases.destroy', $po->id) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('{{ translate('Delete this PO?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="tio-delete-outlined"></i></button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-4">{{ translate('No purchase orders yet') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $orders->links() }}</div>
        </div>
    </div>
</div>
@endsection
