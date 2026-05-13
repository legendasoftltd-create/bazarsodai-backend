@extends('layouts.vendor.app')
@section('title', 'PO #' . $po->po_number)

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-shopping-cart-outlined"></i></span>
            <span>{{ translate('Purchase Order') }} #{{ $po->po_number }}</span>
        </h1>
        <a href="{{ route('vendor.inventory.purchases.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="tio-arrow-backward"></i> {{ translate('Back') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @php $statusColors = ['draft'=>'secondary','ordered'=>'info','partial'=>'warning','received'=>'success','cancelled'=>'danger']; @endphp

    <div class="row">
        {{-- PO Info --}}
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">{{ translate('Order Info') }}</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">{{ translate('PO #') }}</dt>
                        <dd class="col-sm-7">{{ $po->po_number }}</dd>
                        <dt class="col-sm-5">{{ translate('Status') }}</dt>
                        <dd class="col-sm-7"><span class="badge badge-soft-{{ $statusColors[$po->status] ?? 'secondary' }}">{{ ucfirst($po->status) }}</span></dd>
                        <dt class="col-sm-5">{{ translate('Supplier') }}</dt>
                        <dd class="col-sm-7">{{ $po->supplier?->name ?? '—' }}</dd>
                        <dt class="col-sm-5">{{ translate('Ordered') }}</dt>
                        <dd class="col-sm-7">{{ $po->ordered_at?->format('d M Y') ?? '—' }}</dd>
                        <dt class="col-sm-5">{{ translate('Expected') }}</dt>
                        <dd class="col-sm-7">{{ $po->expected_at?->format('d M Y') ?? '—' }}</dd>
                        @if($po->received_at)
                        <dt class="col-sm-5">{{ translate('Received') }}</dt>
                        <dd class="col-sm-7">{{ $po->received_at->format('d M Y') }}</dd>
                        @endif
                        <dt class="col-sm-5">{{ translate('Total') }}</dt>
                        <dd class="col-sm-7"><strong>{{ number_format($po->total_cost, 2) }}</strong></dd>
                    </dl>
                    @if($po->note)
                    <hr>
                    <p class="text-muted small mb-0"><strong>{{ translate('Note') }}:</strong> {{ $po->note }}</p>
                    @endif
                </div>
            </div>

            {{-- Receive stock --}}
            @if(!in_array($po->status, ['received','cancelled']))
            <div class="card mb-3 border-success">
                <div class="card-header bg-soft-success"><h5 class="mb-0 text-success">{{ translate('Receive Stock') }}</h5></div>
                <div class="card-body">
                    <form action="{{ route('vendor.inventory.purchases.receive', $po->id) }}" method="POST">
                        @csrf
                        <p class="text-muted small">{{ translate('Enter quantities received, then click Receive.') }}</p>
                        @foreach($po->items as $line)
                        <div class="form-group">
                            <label class="small">{{ $line->item?->name }} ({{ translate('Ordered') }}: {{ $line->qty_ordered }}, {{ translate('Received') }}: {{ $line->qty_received }})</label>
                            <input type="number" name="received[{{ $line->id }}]"
                                class="form-control form-control-sm"
                                value="{{ $line->qty_ordered - $line->qty_received }}"
                                min="0" step="0.01">
                        </div>
                        @endforeach
                        <button type="submit" class="btn btn-success btn-block">{{ translate('Mark Received') }}</button>
                    </form>
                </div>
            </div>
            @endif

            {{-- Purchase return --}}
            @if($po->status === 'received')
            <div class="card border-warning">
                <div class="card-header bg-soft-warning"><h5 class="mb-0 text-warning">{{ translate('Purchase Return') }}</h5></div>
                <div class="card-body">
                    <form action="{{ route('vendor.inventory.purchases.return', $po->id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="small">{{ translate('Item') }}</label>
                            <select name="item_id" class="form-control form-control-sm" required>
                                <option value="">{{ translate('Select item') }}</option>
                                @foreach($po->items as $line)
                                    <option value="{{ $line->item_id }}">{{ $line->item?->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="small">{{ translate('Qty to Return') }}</label>
                            <input type="number" name="qty" class="form-control form-control-sm" step="0.01" min="0.01" required>
                        </div>
                        <div class="form-group">
                            <label class="small">{{ translate('Reason') }}</label>
                            <input type="text" name="note" class="form-control form-control-sm">
                        </div>
                        <button type="submit" class="btn btn-warning btn-block btn-sm">{{ translate('Record Return') }}</button>
                    </form>
                </div>
            </div>
            @endif
        </div>

        {{-- Items table --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ translate('Order Items') }}</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('Item') }}</th>
                                    <th>{{ translate('Ordered') }}</th>
                                    <th>{{ translate('Received') }}</th>
                                    <th>{{ translate('Pending') }}</th>
                                    <th>{{ translate('Unit Cost') }}</th>
                                    <th>{{ translate('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($po->items as $line)
                                <tr>
                                    <td>{{ $line->item?->name ?? "Item #{$line->item_id}" }}</td>
                                    <td>{{ $line->qty_ordered }}</td>
                                    <td class="text-success">{{ $line->qty_received }}</td>
                                    <td class="{{ $line->qty_ordered - $line->qty_received > 0 ? 'text-warning' : 'text-muted' }}">
                                        {{ $line->qty_ordered - $line->qty_received }}
                                    </td>
                                    <td>{{ number_format($line->unit_cost, 2) }}</td>
                                    <td>{{ number_format($line->total_cost, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <td colspan="5" class="text-right font-weight-bold">{{ translate('Grand Total') }}</td>
                                    <td><strong>{{ number_format($po->total_cost, 2) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
