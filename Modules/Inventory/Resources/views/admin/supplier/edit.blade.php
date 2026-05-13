@extends('layouts.admin.app')
@section('title', translate('Edit Supplier'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-contacts-outlined"></i></span>
            <span>{{ translate('Edit Supplier') }} — {{ $supplier->name }}</span>
        </h1>
        <a href="{{ route('admin.inventory.suppliers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="tio-arrow-backward"></i> {{ translate('Back') }}
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <form action="{{ route('admin.inventory.suppliers.update', $supplier->id) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="form-group">
                            <label class="input-label">{{ translate('Vendor / Store') }}</label>
                            <select name="store_id" class="form-control">
                                <option value="">{{ translate('None') }}</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" {{ $supplier->store_id == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{ translate('Supplier Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $supplier->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">{{ translate('Phone') }}</label>
                                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $supplier->phone) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">{{ translate('Email') }}</label>
                                    <input type="email" name="email" class="form-control" value="{{ old('email', $supplier->email) }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{ translate('Address') }}</label>
                            <textarea name="address" class="form-control" rows="2">{{ old('address', $supplier->address) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{ translate('Status') }}</label>
                            <select name="status" class="form-control">
                                <option value="1" {{ $supplier->status ? 'selected' : '' }}>{{ translate('Active') }}</option>
                                <option value="0" {{ !$supplier->status ? 'selected' : '' }}>{{ translate('Inactive') }}</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">{{ translate('Update Supplier') }}</button>
                        <a href="{{ route('admin.inventory.suppliers.index') }}" class="btn btn-outline-secondary ml-2">{{ translate('Cancel') }}</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
