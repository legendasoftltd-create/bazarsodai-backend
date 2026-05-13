@extends('layouts.vendor.app')
@section('title', translate('Add Supplier'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-contacts-outlined"></i></span>
            <span>{{ translate('Add Supplier') }}</span>
        </h1>
        <a href="{{ route('vendor.inventory.suppliers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="tio-arrow-backward"></i> {{ translate('Back') }}
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('vendor.inventory.suppliers.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="input-label">{{ translate('Supplier Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" placeholder="{{ translate('Enter supplier name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">{{ translate('Phone') }}</label>
                                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="+880...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">{{ translate('Email') }}</label>
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                        value="{{ old('email') }}" placeholder="supplier@example.com">
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{ translate('Address') }}</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="{{ translate('Full address') }}">{{ old('address') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">{{ translate('Save Supplier') }}</button>
                        <a href="{{ route('vendor.inventory.suppliers.index') }}" class="btn btn-outline-secondary ml-2">{{ translate('Cancel') }}</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
