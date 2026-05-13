@extends('layouts.admin.app')
@section('title', translate('Add Reorder Point'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-bell-outlined"></i></span>
            <span>{{ translate('Add Reorder Point') }}</span>
        </h1>
        <a href="{{ route('admin.inventory.reorder-points.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="tio-arrow-backward"></i> {{ translate('Back') }}
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.inventory.reorder-points.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="input-label">{{ translate('Vendor / Store') }} <span class="text-danger">*</span></label>
                            <select name="store_id" id="storeSelect" class="form-control @error('store_id') is-invalid @enderror" required>
                                <option value="">{{ translate('Select vendor') }}</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" {{ old('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                                @endforeach
                            </select>
                            @error('store_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{ translate('Item') }} <span class="text-danger">*</span></label>
                            <select name="item_id" id="itemSelect" class="form-control @error('item_id') is-invalid @enderror" required>
                                <option value="">{{ translate('Select item') }}</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}" data-store="{{ $item->store_id }}" data-stock="{{ $item->stock }}" {{ old('item_id') == $item->id ? 'selected' : '' }}>
                                        {{ $item->name }} ({{ translate('Stock') }}: {{ $item->stock }})
                                    </option>
                                @endforeach
                            </select>
                            @error('item_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">{{ translate('Alert When Stock') }} &le; <span class="text-danger">*</span></label>
                                    <input type="number" name="reorder_at" class="form-control @error('reorder_at') is-invalid @enderror"
                                        value="{{ old('reorder_at', 10) }}" min="0" required>
                                    @error('reorder_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">{{ translate('Suggested Reorder Qty') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="reorder_qty" class="form-control @error('reorder_qty') is-invalid @enderror"
                                        value="{{ old('reorder_qty', 50) }}" min="0" required>
                                    @error('reorder_qty')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="autoNotify" name="auto_notify" value="1"
                                    {{ old('auto_notify', 1) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="autoNotify">{{ translate('Send email notification when triggered') }}</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
                        <a href="{{ route('admin.inventory.reorder-points.index') }}" class="btn btn-outline-secondary ml-2">{{ translate('Cancel') }}</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
document.getElementById('storeSelect').addEventListener('change', function() {
    const storeId = this.value;
    document.querySelectorAll('#itemSelect option').forEach(opt => {
        opt.style.display = (!storeId || opt.dataset.store == storeId || !opt.value) ? '' : 'none';
    });
    document.getElementById('itemSelect').value = '';
});
</script>
@endpush
@endsection
