@extends('layouts.admin.app')
@section('title', 'Adjustment #' . $adjustment->adjustment_number)

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-tune"></i></span>
            <span>{{ translate('Adjustment') }} #{{ $adjustment->adjustment_number }}</span>
        </h1>
        <a href="{{ route('admin.inventory.adjustments.index') }}" class="btn btn-outline-secondary btn-sm ml-auto">
            <i class="tio-arrow-backward"></i> {{ translate('Back') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @php $colors = ['draft'=>'secondary','pending_approval'=>'warning','approved'=>'success','rejected'=>'danger']; @endphp

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">{{ translate('Details') }}</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">{{ translate('Ref #') }}</dt>
                        <dd class="col-sm-7"><strong>{{ $adjustment->adjustment_number }}</strong></dd>

                        <dt class="col-sm-5">{{ translate('Vendor') }}</dt>
                        <dd class="col-sm-7">{{ $adjustment->store?->name ?? '—' }}</dd>

                        <dt class="col-sm-5">{{ translate('Status') }}</dt>
                        <dd class="col-sm-7">
                            <span class="badge badge-soft-{{ $colors[$adjustment->status] ?? 'secondary' }}">
                                {{ ucwords(str_replace('_', ' ', $adjustment->status)) }}
                            </span>
                        </dd>

                        <dt class="col-sm-5">{{ translate('Date') }}</dt>
                        <dd class="col-sm-7">{{ $adjustment->created_at->format('d M Y H:i') }}</dd>

                        @if($adjustment->approved_at)
                        <dt class="col-sm-5">{{ translate('Reviewed') }}</dt>
                        <dd class="col-sm-7">{{ $adjustment->approved_at->format('d M Y H:i') }}</dd>
                        @endif
                    </dl>

                    @if($adjustment->note)
                        <hr>
                        <p class="text-muted small mb-0">{{ $adjustment->note }}</p>
                    @endif
                </div>

                @if($adjustment->status === 'pending_approval')
                <div class="card-footer">
                    <form method="POST" action="{{ route('admin.inventory.adjustments.approve', $adjustment->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-success btn-block mb-2"
                            onclick="return confirm('{{ translate('Approve and apply all stock changes?') }}')">
                            <i class="tio-checkmark-circle"></i> {{ translate('Approve') }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.inventory.adjustments.reject', $adjustment->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-block"
                            onclick="return confirm('{{ translate('Reject this adjustment?') }}')">
                            <i class="tio-clear"></i> {{ translate('Reject') }}
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ translate('Items') }}</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('Item') }}</th>
                                    <th>{{ translate('System Qty') }}</th>
                                    <th>{{ translate('Physical Qty') }}</th>
                                    <th>{{ translate('Difference') }}</th>
                                    <th>{{ translate('Reason') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($adjustment->adjustmentItems as $line)
                                @php
                                    $diffClass = $line->difference > 0 ? 'text-success' : ($line->difference < 0 ? 'text-danger' : 'text-muted');
                                    $diffSign  = $line->difference >= 0 ? '+' : '';
                                @endphp
                                <tr>
                                    <td><strong>{{ $line->item?->name ?? "Item #{$line->item_id}" }}</strong></td>
                                    <td>{{ $line->system_qty }}</td>
                                    <td>{{ $line->physical_qty }}</td>
                                    <td class="{{ $diffClass }} fw-bold">{{ $diffSign }}{{ $line->difference }}</td>
                                    <td>{{ $line->reason ?? '—' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">{{ translate('No items') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
