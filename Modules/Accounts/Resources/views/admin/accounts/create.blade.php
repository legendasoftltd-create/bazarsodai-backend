@extends('layouts.admin.app')
@section('title', translate('Add Account'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-add nav-icon"></i></span>
            {{ translate('Add Account') }}
        </h1>
    </div>

    <div class="card" style="max-width:640px">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.accounts.coa.store') }}">
                @csrf
                @include('accounts::admin.accounts._form', ['account' => null])
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
                    <a href="{{ route('admin.accounts.coa.index') }}" class="btn btn-outline-secondary">{{ translate('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
