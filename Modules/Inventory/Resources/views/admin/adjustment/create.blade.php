@extends('layouts.admin.app')
@section('title', translate('New Inventory Adjustment'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-tune"></i></span>
            <span>{{ translate('New Inventory Adjustment') }}</span>
        </h1>
        <a href="{{ route('admin.inventory.adjustments.index') }}" class="btn btn-outline-secondary btn-sm ml-auto">
            <i class="tio-arrow-backward"></i> {{ translate('Back') }}
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.inventory.adjustments.store') }}">
        @csrf
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">{{ translate('Details') }}</h5></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">{{ translate('Vendor') }} <span class="text-danger">*</span></label>
                            <select name="store_id" id="storeFilter" class="form-control" required>
                                <option value="">{{ translate('Select vendor') }}</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" {{ old('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
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
                            <i class="tio-add"></i> {{ translate('Add Row') }}
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('Item') }}</th>
                                    <th width="110">{{ translate('System Qty') }}</th>
                                    <th width="110">{{ translate('Physical Qty') }}</th>
                                    <th width="90">{{ translate('Diff') }}</th>
                                    <th>{{ translate('Reason') }}</th>
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
                                                    {{ $item->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][system_qty]" class="form-control system-qty" min="0" step="0.01" readonly required>
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][physical_qty]" class="form-control physical-qty" min="0" step="0.01" required>
                                    </td>
                                    <td><span class="diff-display text-muted fw-bold">—</span></td>
                                    <td><input type="text" name="items[0][reason]" class="form-control" placeholder="{{ translate('Optional reason') }}"></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="tio-delete"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="tio-save"></i> {{ translate('Submit for Approval') }}
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
    const selectedStore = () => document.getElementById('storeFilter').value;

    function updateDiff(row) {
        const sys  = parseFloat(row.querySelector('.system-qty').value) || 0;
        const phys = parseFloat(row.querySelector('.physical-qty').value) || 0;
        const diff = phys - sys;
        const el   = row.querySelector('.diff-display');
        el.textContent = isNaN(diff) ? '—' : (diff >= 0 ? '+' + diff.toFixed(2) : diff.toFixed(2));
        el.className = 'diff-display fw-bold ' + (diff > 0 ? 'text-success' : (diff < 0 ? 'text-danger' : 'text-muted'));
    }

    function filterOptions(select) {
        const storeId = selectedStore();
        Array.from(select.options).forEach(opt => {
            if (!opt.value) return;
            const item = itemsJson[opt.value];
            opt.hidden = storeId && item ? String(item.store_id) !== String(storeId) : false;
        });
        if (select.selectedIndex > 0 && select.options[select.selectedIndex]?.hidden) {
            select.selectedIndex = 0;
            const row = select.closest('.item-row');
            row.querySelector('.system-qty').value = '';
            updateDiff(row);
        }
    }

    function bindRow(row) {
        const itemSelect = row.querySelector('.item-select');
        const physInput  = row.querySelector('.physical-qty');

        filterOptions(itemSelect);

        itemSelect.addEventListener('change', function () {
            const item = itemsJson[this.value];
            row.querySelector('.system-qty').value = item ? item.stock : '';
            updateDiff(row);
        });
        physInput.addEventListener('input', () => updateDiff(row));
        row.querySelector('.remove-row').addEventListener('click', function () {
            if (document.querySelectorAll('.item-row').length > 1) row.remove();
        });
    }

    document.querySelectorAll('.item-row').forEach(bindRow);

    document.getElementById('storeFilter').addEventListener('change', function () {
        document.querySelectorAll('.item-select').forEach(filterOptions);
    });

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
        tpl.querySelector('.diff-display').textContent = '—';

        tbody.appendChild(tpl);
        bindRow(tpl);
        rowIndex++;
    });
})();
</script>
@endsection
