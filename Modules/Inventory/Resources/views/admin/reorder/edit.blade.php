@extends('layouts.admin.app')
@section('title', translate('Edit Reorder Point'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-bell-outlined"></i></span>
            <span>{{ translate('Edit Reorder Point') }} — {{ $reorderPoint->item?->name }}</span>
        </h1>
        <a href="{{ route('admin.inventory.reorder-points.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="tio-arrow-backward"></i> {{ translate('Back') }}
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card">
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    {{-- Info row --}}
                    <div class="d-flex mb-3">
                        <div class="mr-4">
                            <span class="text-muted small">{{ translate('Item') }}</span>
                            <p class="mb-0 font-weight-bold">{{ $reorderPoint->item?->name }}</p>
                        </div>
                        <div class="mr-4">
                            <span class="text-muted small">{{ translate('Vendor') }}</span>
                            <p class="mb-0 font-weight-bold">{{ $reorderPoint->store?->name }}</p>
                        </div>
                        <div>
                            <span class="text-muted small">{{ translate('Current Stock') }}</span>
                            <p class="mb-0 font-weight-bold {{ $reorderPoint->item?->stock <= $reorderPoint->reorder_at ? 'text-danger' : 'text-success' }}">
                                {{ $reorderPoint->item?->stock }}
                            </p>
                        </div>
                    </div>

                    <form action="{{ route('admin.inventory.reorder-points.update', $reorderPoint->id) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">{{ translate('Alert When Stock') }} &le;</label>
                                    <input type="number" name="reorder_at" class="form-control"
                                        value="{{ old('reorder_at', $reorderPoint->reorder_at) }}" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">{{ translate('Suggested Reorder Qty') }}</label>
                                    <input type="number" name="reorder_qty" class="form-control"
                                        value="{{ old('reorder_qty', $reorderPoint->reorder_qty) }}" min="0" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="autoNotify" name="auto_notify" value="1"
                                    {{ $reorderPoint->auto_notify ? 'checked' : '' }}>
                                <label class="custom-control-label" for="autoNotify">{{ translate('Send email notification when triggered') }}</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
                        <a href="{{ route('admin.inventory.reorder-points.index') }}" class="btn btn-outline-secondary ml-2">{{ translate('Cancel') }}</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
