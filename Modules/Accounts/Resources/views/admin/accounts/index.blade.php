@extends('layouts.admin.app')
@section('title', translate('Chart of Accounts'))

@section('content')
<div class="content container-fluid">

    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <span class="page-header-icon"><i class="tio-settings nav-icon"></i></span>
                    {{ translate('Chart of Accounts') }}
                </h1>
            </div>
            <div class="col-sm-auto">
                <a href="{{ route('admin.accounts.coa.create') }}" class="btn btn-primary">
                    <i class="tio-add mr-1"></i> {{ translate('Add Account') }}
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }} <button type="button" class="close" data-dismiss="alert">&times;</button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }} <button type="button" class="close" data-dismiss="alert">&times;</button></div>
    @endif

    @php
        $typeOrder = ['asset' => 1, 'liability' => 2, 'equity' => 3, 'revenue' => 4, 'expense' => 5];
        $typeBadge = ['asset' => 'badge-soft-primary', 'liability' => 'badge-soft-warning', 'equity' => 'badge-soft-info', 'revenue' => 'badge-soft-success', 'expense' => 'badge-soft-danger'];
        $sortedTypes = $accounts->keys()->sortBy(fn($k) => $typeOrder[$k] ?? 99);
    @endphp

    @foreach($sortedTypes as $type)
        @php $typeAccounts = $accounts[$type]; @endphp
        <div class="card mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <span class="badge {{ $typeBadge[$type] ?? 'badge-soft-secondary' }} mr-2">{{ ucfirst($type) }}</span>
                    <span class="text-muted font-weight-normal">{{ $typeAccounts->count() }} accounts</span>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th width="100">{{ translate('Code') }}</th>
                            <th>{{ translate('Account Name') }}</th>
                            <th>{{ translate('Parent') }}</th>
                            <th>{{ translate('Normal Balance') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th class="text-right" width="160">{{ translate('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($typeAccounts->sortBy('sort_order') as $account)
                            <tr class="{{ !$account->is_active ? 'text-muted' : '' }}">
                                <td class="font-weight-bold">{{ $account->code }}</td>
                                <td>
                                    @if($account->parent)
                                        <small class="text-muted mr-1">↳</small>
                                    @endif
                                    {{ $account->name }}
                                    @if($account->is_system)
                                        <span class="badge badge-soft-secondary badge-sm ml-1">sys</span>
                                    @endif
                                </td>
                                <td>
                                    @if($account->parent)
                                        <small class="text-muted">{{ $account->parent->code }} — {{ $account->parent->name }}</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $account->normal_balance === 'debit' ? 'badge-soft-primary' : 'badge-soft-success' }}">
                                        {{ ucfirst($account->normal_balance) }}
                                    </span>
                                </td>
                                <td>
                                    @if($account->is_active)
                                        <span class="badge badge-soft-success">{{ translate('Active') }}</span>
                                    @else
                                        <span class="badge badge-soft-secondary">{{ translate('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.accounts.coa.edit', $account) }}" class="btn btn-xs btn-outline-primary mr-1">
                                        <i class="tio-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.accounts.coa.toggle', $account) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-xs {{ $account->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" type="submit"
                                            {{ $account->is_system ? 'disabled title="System accounts cannot be toggled"' : '' }}>
                                            <i class="tio-{{ $account->is_active ? 'eye-off' : 'eye' }}"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

</div>
@endsection
