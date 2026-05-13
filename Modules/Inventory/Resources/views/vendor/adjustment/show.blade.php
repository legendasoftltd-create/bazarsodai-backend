@extends('layouts.vendor.app')
@section('title', 'Adjustment #' . $adjustment->adjustment_number)
@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-tune"></i></span>
            <span>{{ translate('Adjustment') }} #{{ $adjustment->adjustment_number }}</span>
        </h1>
        <a href="{{ route('vendor.inventory.adjustments.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="tio-arrow-backward"></i> {{ translate('Back') }}
        </a>
    </div>

    @php $colors = ['draft'=>'secondary','pending_approval'=>'warning','approved'=>'success','rejected'=>'danger']; @endphp
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">{{ translate('Details') }}</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">{{ translate('Ref #') }}</dt>
                        <dd class="col-sm-7">{{ $adjustment->adjustment_number }}</dd>
                        <dt class="col-sm-5">{{ translate('Status') }}</dt>
                        <dd class="col-sm-7"><span class="badge badge-soft-{{ $colors[$adjustment->status] ?? 'secondary' }}">{{ ucwords(str_replace('_',' ',$adjustment->status)) }}</span></dd>
                        <dt class="col-sm-5">{{ translate('Date') }}</dt>
                        <dd class="col-sm-7">{{ $adjustment->created_at->format('d M Y H:i') }}</dd>
                    </dl>
                    @if($adjustment->note)
                    <hr><p class="text-muted small mb-0">{{ $adjustment->note }}</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ translate('Items') }}</h5></div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('Item') }}</th>
                                <th>{{ translate('System Qty') }}</th>
                                <th>{{ translate('Physical Qty') }}</th>
                                <th>{{ translate('Difference') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($adjustment->adjustmentItems as $line)
                            <tr>
                                <td>{{ $line->item?->name ?? "Item #{$line->item_id}" }}</td>
                                <td>{{ $line->system_qty }}</td>
                                <td>{{ $line->physical_qty }}</td>
                                <td class="{{ $line->difference > 0 ? 'text-success' : ($line->difference < 0 ? 'text-danger' : 'text-muted') }}">
                                    {{ $line->difference >= 0 ? '+' : '' }}{{ $line->difference }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
