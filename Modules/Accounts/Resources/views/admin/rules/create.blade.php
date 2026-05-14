@extends('layouts.admin.app')
@section('title', translate('Add Accounting Rule'))
@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title"><span class="page-header-icon"><i class="tio-add nav-icon"></i></span>{{ translate('Add Accounting Rule') }}</h1>
    </div>
    <div class="card" style="max-width:760px">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.accounts.rules.store') }}">
                @csrf
                @php $rule = null; @endphp
                @include('accounts::admin.rules._form')
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">{{ translate('Save Rule') }}</button>
                    <a href="{{ route('admin.accounts.rules.index') }}" class="btn btn-outline-secondary">{{ translate('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
