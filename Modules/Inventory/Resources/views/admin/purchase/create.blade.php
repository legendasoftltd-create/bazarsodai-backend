@extends('layouts.admin.app')
@section('title', translate('New Purchase Order'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-shopping-cart-outlined"></i></span>
            <span>{{ translate('New Purchase Order') }}</span>
        </h1>
        <a href="{{ route('admin.inventory.purchases.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="tio-arrow-backward"></i> {{ translate('Back') }}
        </a>
    </div>

    <form action="{{ route('admin.inventory.purchases.store') }}" method="POST" id="poForm">
        @csrf
        <div class="row">
            {{-- Left: PO details --}}
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">{{ translate('Order Details') }}</h5></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="input-label">{{ translate('Vendor / Store') }} <span class="text-danger">*</span></label>
                            <select name="store_id" id="storeSelect" class="form-control @error('store_id') is-invalid @enderror" required>
                                <option value="">{{ translate('Select vendor') }}</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" data-module="{{ $store->module_id }}" {{ old('store_id') == $store->id ? 'selected' : '' }}>
                                        {{ $store->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('store_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{ translate('Supplier') }}</label>
                            <select name="supplier_id" id="supplierSelect" class="form-control">
                                <option value="">{{ translate('Select supplier') }}</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->id }}" data-store="{{ $sup->store_id }}" {{ old('supplier_id') == $sup->id ? 'selected' : '' }}>
                                        {{ $sup->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{ translate('Expected Delivery') }}</label>
                            <input type="date" name="expected_at" class="form-control" value="{{ old('expected_at') }}" min="{{ date('Y-m-d') }}">
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{ translate('Note') }}</label>
                            <textarea name="note" class="form-control" rows="2">{{ old('note') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Items --}}
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ translate('Items') }}</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addLine">
                            <i class="tio-add"></i> {{ translate('Add Item') }}
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0" id="itemsTable">
                                <thead class="thead-light">
                                    <tr>
                                        <th>{{ translate('Item') }}</th>
                                        <th style="width:110px">{{ translate('Qty') }}</th>
                                        <th style="width:130px">{{ translate('Unit Cost') }}</th>
                                        <th style="width:120px">{{ translate('Total') }}</th>
                                        <th style="width:50px"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <tr class="item-row">
                                        <td>
                                            <select name="items[0][item_id]" class="form-control form-control-sm item-select" required>
                                                <option value="">{{ translate('Select item') }}</option>
                                                @foreach($items as $item)
                                                    <option value="{{ $item->id }}" data-store="{{ $item->store_id }}">{{ $item->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="number" name="items[0][qty]" class="form-control form-control-sm qty-input" step="0.01" min="0.01" required></td>
                                        <td><input type="number" name="items[0][unit_cost]" class="form-control form-control-sm cost-input" step="0.01" min="0" required></td>
                                        <td><span class="row-total">0.00</span></td>
                                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="tio-delete-outlined"></i></button></td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-light">
                                        <td colspan="3" class="text-right font-weight-bold">{{ translate('Grand Total') }}</td>
                                        <td><strong id="grandTotal">0.00</strong></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="tio-save"></i> {{ translate('Place Order') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('script')
<script>
let rowIndex = 1;
const allItems = @json($items->map(fn($i) => ['id' => $i->id, 'name' => $i->name, 'store_id' => $i->store_id]));

function buildItemOptions(storeId) {
    let opts = '<option value="">{{ translate("Select item") }}</option>';
    allItems.forEach(item => {
        if (!storeId || item.store_id == storeId) {
            opts += `<option value="${item.id}">${item.name}</option>`;
        }
    });
    return opts;
}

function recalcRow(row) {
    const qty  = parseFloat(row.querySelector('.qty-input').value)  || 0;
    const cost = parseFloat(row.querySelector('.cost-input').value) || 0;
    row.querySelector('.row-total').textContent = (qty * cost).toFixed(2);
    recalcGrand();
}

function recalcGrand() {
    let total = 0;
    document.querySelectorAll('.row-total').forEach(el => total += parseFloat(el.textContent) || 0);
    document.getElementById('grandTotal').textContent = total.toFixed(2);
}

document.getElementById('storeSelect').addEventListener('change', function() {
    const storeId = this.value;
    document.querySelectorAll('.item-select').forEach(sel => {
        const current = sel.value;
        sel.innerHTML = buildItemOptions(storeId);
        if (current) sel.value = current;
    });
});

document.getElementById('addLine').addEventListener('click', function() {
    const storeId = document.getElementById('storeSelect').value;
    const tbody   = document.getElementById('itemsBody');
    const row     = document.createElement('tr');
    row.className = 'item-row';
    row.innerHTML = `
        <td><select name="items[${rowIndex}][item_id]" class="form-control form-control-sm item-select" required>${buildItemOptions(storeId)}</select></td>
        <td><input type="number" name="items[${rowIndex}][qty]" class="form-control form-control-sm qty-input" step="0.01" min="0.01" required></td>
        <td><input type="number" name="items[${rowIndex}][unit_cost]" class="form-control form-control-sm cost-input" step="0.01" min="0" required></td>
        <td><span class="row-total">0.00</span></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="tio-delete-outlined"></i></button></td>`;
    tbody.appendChild(row);
    rowIndex++;
});

document.addEventListener('input', function(e) {
    const row = e.target.closest('.item-row');
    if (row && (e.target.matches('.qty-input') || e.target.matches('.cost-input'))) {
        recalcRow(row);
    }
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-row')) {
        const rows = document.querySelectorAll('.item-row');
        if (rows.length > 1) { e.target.closest('.item-row').remove(); recalcGrand(); }
    }
});
</script>
@endpush
@endsection
