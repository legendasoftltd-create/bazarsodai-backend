@extends('layouts.admin.app')
@section('title', translate('New Stock Transfer'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-swap-horizontal"></i></span>
            <span>{{ translate('New Stock Transfer') }}</span>
        </h1>
        <a href="{{ route('admin.inventory.transfers.index') }}" class="btn btn-outline-secondary btn-sm ml-auto">
            <i class="tio-arrow-backward"></i> {{ translate('Back') }}
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.inventory.transfers.store') }}">
        @csrf
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">{{ translate('Transfer Details') }}</h5></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">{{ translate('From Vendor') }} <span class="text-danger">*</span></label>
                            <select name="from_store_id" id="fromStore" class="form-control" required>
                                <option value="">{{ translate('Select vendor') }}</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" {{ old('from_store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{ translate('To Vendor') }} <span class="text-danger">*</span></label>
                            <select name="to_store_id" id="toStore" class="form-control" required>
                                <option value="">{{ translate('Select vendor') }}</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" {{ old('to_store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Note') }}</label>
                            <textarea name="note" class="form-control" rows="3" placeholder="{{ translate('Optional note...') }}">{{ old('note') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ translate('Items') }}</h5>
                        <button type="button" id="addRow" class="btn btn-outline-primary btn-sm">
                            <i class="tio-add"></i> {{ translate('Add Item') }}
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('Item') }}</th>
                                    <th width="130">{{ translate('Available') }}</th>
                                    <th width="130">{{ translate('Qty') }}</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <tr class="item-row">
                                    <td>
                                        <select name="items[0][item_id]" class="form-control item-select" required>
                                            <option value="">{{ translate('Select item') }}</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}" data-store="{{ $item->store_id }}" data-stock="{{ $item->stock }}">
                                                    {{ $item->name }} ({{ translate('stock') }}: {{ $item->stock }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><span class="available-qty text-muted">—</span></td>
                                    <td><input type="number" name="items[0][qty]" class="form-control" min="0.01" step="0.01" required></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="tio-delete"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="tio-swap-horizontal"></i> {{ translate('Initiate Transfer') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
(function () {
    let rowIndex = 1;
    const itemsJson = @json($items->keyBy('id'));

    function bindRow(row) {
        row.querySelector('.item-select').addEventListener('change', function () {
            const item = itemsJson[this.value];
            row.querySelector('.available-qty').textContent = item ? item.stock : '—';
        });
        row.querySelector('.remove-row').addEventListener('click', function () {
            if (document.querySelectorAll('.item-row').length > 1) row.remove();
        });
    }

    document.querySelectorAll('.item-row').forEach(bindRow);

    document.getElementById('addRow').addEventListener('click', function () {
        const tbody = document.getElementById('itemsBody');
        const tpl   = tbody.querySelector('.item-row').cloneNode(true);

        tpl.querySelectorAll('input').forEach(i => {
            i.value = '';
            i.name  = i.name.replace(/\[\d+\]/, '[' + rowIndex + ']');
        });
        tpl.querySelectorAll('select').forEach(s => {
            s.selectedIndex = 0;
            s.name = s.name.replace(/\[\d+\]/, '[' + rowIndex + ']');
        });
        tpl.querySelector('.available-qty').textContent = '—';

        tbody.appendChild(tpl);
        bindRow(tpl);
        rowIndex++;
    });
})();
</script>
@endsection
