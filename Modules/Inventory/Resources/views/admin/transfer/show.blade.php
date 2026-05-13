@extends('layouts.admin.app')
@section('title', 'Transfer #' . $transfer->transfer_number)

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-swap-horizontal"></i></span>
            <span>{{ translate('Transfer') }} #{{ $transfer->transfer_number }}</span>
        </h1>
        <a href="{{ route('admin.inventory.transfers.index') }}" class="btn btn-outline-secondary btn-sm ml-auto">
            <i class="tio-arrow-backward"></i> {{ translate('Back') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @php $colors = ['pending'=>'secondary','in_transit'=>'warning','received'=>'success']; @endphp

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">{{ translate('Transfer Info') }}</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">{{ translate('Ref #') }}</dt>
                        <dd class="col-sm-7"><strong>{{ $transfer->transfer_number }}</strong></dd>

                        <dt class="col-sm-5">{{ translate('Status') }}</dt>
                        <dd class="col-sm-7">
                            <span class="badge badge-soft-{{ $colors[$transfer->status] ?? 'secondary' }}">
                                {{ ucwords(str_replace('_', ' ', $transfer->status)) }}
                            </span>
                        </dd>

                        <dt class="col-sm-5">{{ translate('From') }}</dt>
                        <dd class="col-sm-7">{{ $transfer->fromStore?->name ?? '—' }}</dd>

                        <dt class="col-sm-5">{{ translate('To') }}</dt>
                        <dd class="col-sm-7">{{ $transfer->toStore?->name ?? '—' }}</dd>

                        <dt class="col-sm-5">{{ translate('Created') }}</dt>
                        <dd class="col-sm-7">{{ $transfer->created_at->format('d M Y H:i') }}</dd>

                        @if($transfer->transferred_at)
                        <dt class="col-sm-5">{{ translate('Transferred') }}</dt>
                        <dd class="col-sm-7">{{ $transfer->transferred_at->format('d M Y H:i') }}</dd>
                        @endif

                        @if($transfer->received_at)
                        <dt class="col-sm-5">{{ translate('Received') }}</dt>
                        <dd class="col-sm-7">{{ $transfer->received_at->format('d M Y H:i') }}</dd>
                        @endif
                    </dl>

                    @if($transfer->note)
                        <hr>
                        <p class="text-muted small mb-0">{{ $transfer->note }}</p>
                    @endif
                </div>

                @if($transfer->status === 'in_transit')
                <div class="card-footer">
                    <form method="POST" action="{{ route('admin.inventory.transfers.receive', $transfer->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-success btn-block"
                            onclick="return confirm('{{ translate('Mark all items as received and add to destination stock?') }}')">
                            <i class="tio-checkmark-circle"></i> {{ translate('Receive Transfer') }}
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
                                    <th>{{ translate('Qty Requested') }}</th>
                                    <th>{{ translate('Qty Transferred') }}</th>
                                    <th>{{ translate('Qty Received') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transfer->items as $line)
                                <tr>
                                    <td>{{ $line->item?->name ?? "Item #{$line->item_id}" }}</td>
                                    <td>{{ $line->qty_requested }}</td>
                                    <td>{{ $line->qty_transferred }}</td>
                                    <td>
                                        @if($line->qty_received !== null)
                                            <span class="text-success">{{ $line->qty_received }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">{{ translate('No items') }}</td></tr>
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
