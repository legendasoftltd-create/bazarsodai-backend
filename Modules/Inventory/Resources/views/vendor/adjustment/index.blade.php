@extends('layouts.vendor.app')
@section('title', translate('Inventory Adjustments'))
@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-tune"></i></span>
            <span>{{ translate('Inventory Adjustments') }}
                <span class="badge badge-soft-secondary">{{ $adjustments->total() }}</span>
            </span>
        </h1>
        <a href="{{ route('vendor.inventory.adjustments.create') }}" class="btn btn-primary btn-sm">
            <i class="tio-add"></i> {{ translate('New Adjustment') }}
        </a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Ref #') }}</th>
                            <th>{{ translate('Items') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Date') }}</th>
                            <th>{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $colors = ['draft'=>'secondary','pending_approval'=>'warning','approved'=>'success','rejected'=>'danger']; @endphp
                        @forelse($adjustments as $adj)
                        <tr>
                            <td><strong>{{ $adj->adjustment_number }}</strong></td>
                            <td>{{ $adj->items_count ?? $adj->items()->count() }}</td>
                            <td><span class="badge badge-soft-{{ $colors[$adj->status] ?? 'secondary' }}">{{ ucwords(str_replace('_',' ',$adj->status)) }}</span></td>
                            <td>{{ $adj->created_at->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('vendor.inventory.adjustments.show', $adj->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="tio-visible-outlined"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-4">{{ translate('No adjustments yet') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $adjustments->links() }}</div>
        </div>
    </div>
</div>
@endsection
