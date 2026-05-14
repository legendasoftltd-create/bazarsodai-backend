@extends('layouts.admin.app')
@section('title', translate('Accounting Rules'))

@section('content')
<div class="content container-fluid">

    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <span class="page-header-icon"><i class="tio-settings nav-icon"></i></span>
                    {{ translate('Accounting Rules') }}
                </h1>
            </div>
            <div class="col-sm-auto">
                <a href="{{ route('admin.accounts.rules.create') }}" class="btn btn-primary">
                    <i class="tio-add mr-1"></i> {{ translate('Add Rule') }}
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="close" data-dismiss="alert">&times;</button></div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Event Type') }}</th>
                        <th>{{ translate('Description') }}</th>
                        <th>{{ translate('Lines') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th class="text-right" width="80">{{ translate('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rules as $rule)
                        <tr class="{{ !$rule->is_active ? 'text-muted' : '' }}">
                            <td class="font-weight-bold text-nowrap">
                                <code>{{ $rule->event_type }}</code>
                            </td>
                            <td>{{ Str::limit($rule->description, 60) ?? '—' }}</td>
                            <td>
                                @foreach($rule->lines as $line)
                                    <div class="text-nowrap">
                                        <span class="badge {{ $line['side'] === 'debit' ? 'badge-soft-primary' : 'badge-soft-success' }} badge-sm">{{ strtoupper(substr($line['side'], 0, 2)) }}</span>
                                        <span class="font-weight-bold">{{ $line['account_code'] }}</span>
                                        <small class="text-muted">{{ $line['amount_field'] }}</small>
                                    </div>
                                @endforeach
                            </td>
                            <td>
                                @if($rule->is_active)
                                    <span class="badge badge-soft-success">{{ translate('Active') }}</span>
                                @else
                                    <span class="badge badge-soft-secondary">{{ translate('Inactive') }}</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.accounts.rules.edit', $rule) }}" class="btn btn-xs btn-outline-primary">
                                    <i class="tio-edit"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">{{ translate('No rules defined.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
