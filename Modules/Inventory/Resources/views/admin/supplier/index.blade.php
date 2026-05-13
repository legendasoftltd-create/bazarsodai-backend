@extends('layouts.admin.app')
@section('title', translate('Suppliers'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-contacts-outlined"></i></span>
            <span>{{ translate('Suppliers') }}
                <span class="badge badge-soft-secondary">{{ $suppliers->total() }}</span>
            </span>
        </h1>
        <a href="{{ route('admin.inventory.suppliers.create') }}" class="btn btn-primary btn-sm">
            <i class="tio-add"></i> {{ translate('Add Supplier') }}
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
                    <input type="text" name="search" class="form-control" placeholder="{{ translate('Search by name') }}" value="{{ request('search') }}">
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
                    <a href="{{ route('admin.inventory.suppliers.index') }}" class="btn btn-outline-secondary">{{ translate('Reset') }}</a>
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
                            <th>{{ translate('Name') }}</th>
                            <th>{{ translate('Phone') }}</th>
                            <th>{{ translate('Email') }}</th>
                            <th>{{ translate('Vendor') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $supplier)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $supplier->name }}</strong></td>
                            <td>{{ $supplier->phone ?? '—' }}</td>
                            <td>{{ $supplier->email ?? '—' }}</td>
                            <td>{{ $supplier->store?->name ?? '—' }}</td>
                            <td>
                                @if($supplier->status)
                                    <span class="badge badge-soft-success">{{ translate('Active') }}</span>
                                @else
                                    <span class="badge badge-soft-secondary">{{ translate('Inactive') }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.inventory.suppliers.edit', $supplier->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="tio-edit"></i>
                                </a>
                                <form action="{{ route('admin.inventory.suppliers.destroy', $supplier->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ translate('Delete this supplier?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="tio-delete-outlined"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-4">{{ translate('No suppliers found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $suppliers->links() }}</div>
        </div>
    </div>
</div>
@endsection
