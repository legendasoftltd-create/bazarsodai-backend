@extends('layouts.vendor.app')
@section('title', translate('New Adjustment'))
@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-tune"></i></span>
            <span>{{ translate('New Inventory Adjustment') }}</span>
        </h1>
        <a href="{{ route('vendor.inventory.adjustments.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="tio-arrow-backward"></i> {{ translate('Back') }}
        </a>
    </div>

    <form action="{{ route('vendor.inventory.adjustments.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">{{ translate('Details') }}</h5></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="input-label">{{ translate('Note') }}</label>
                            <textarea name="note" class="form-control" rows="3" placeholder="{{ translate('Reason for adjustment, physical count date, etc.') }}">{{ old('note') }}</textarea>
                        </div>
                        <p class="text-muted small">{{ translate('This adjustment will be sent for admin approval before stock is changed.') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ translate('Items (Physical Count)') }}</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addLine">
                            <i class="tio-add"></i> {{ translate('Add Item') }}
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('Item') }}</th>
                                    <th style="width:130px">{{ translate('System Qty') }}</th>
                                    <th style="width:130px">{{ translate('Physical Qty') }}</th>
                                    <th style="width:100px">{{ translate('Diff') }}</th>
                                    <th style="width:50px"></th>
                                </tr>
                            </thead>
                            <tbody id="adjBody">
                                <tr class="adj-row">
                                    <td>
                                        <select name="items[0][item_id]" class="form-control form-control-sm item-sel" required>
                                            <option value="">{{ translate('Select item') }}</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}" data-stock="{{ $item->stock }}">{{ $item->name }} ({{ $item->stock }})</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm sys-qty" readonly placeholder="—"></td>
                                    <td><input type="number" name="items[0][physical_qty]" class="form-control form-control-sm phys-qty" step="0.01" min="0" required></td>
                                    <td><span class="diff-badge">—</span></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="tio-delete-outlined"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary px-4">{{ translate('Submit for Approval') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('script')
<script>
const itemData = @json($items->keyBy('id')->map(fn($i) => $i->stock));
let idx = 1;
const itemOptionsHtml = `@foreach($items as $item)<option value="{{ $item->id }}" data-stock="{{ $item->stock }}">{{ $item->name }} ({{ $item->stock }})</option>@endforeach`;

document.addEventListener('change', function(e) {
    if (e.target.matches('.item-sel')) {
        const row = e.target.closest('.adj-row');
        const stock = e.target.options[e.target.selectedIndex]?.dataset.stock ?? '';
        row.querySelector('.sys-qty').value = stock;
        updateDiff(row);
    }
});
document.addEventListener('input', function(e) {
    if (e.target.matches('.phys-qty')) updateDiff(e.target.closest('.adj-row'));
});
function updateDiff(row) {
    const sys  = parseFloat(row.querySelector('.sys-qty').value)  || 0;
    const phys = parseFloat(row.querySelector('.phys-qty').value) || 0;
    const diff = phys - sys;
    const badge = row.querySelector('.diff-badge');
    badge.textContent = diff >= 0 ? '+' + diff.toFixed(2) : diff.toFixed(2);
    badge.className = 'badge badge-soft-' + (diff > 0 ? 'success' : diff < 0 ? 'danger' : 'secondary');
}
document.getElementById('addLine').addEventListener('click', function() {
    const row = document.createElement('tr');
    row.className = 'adj-row';
    row.innerHTML = `
        <td><select name="items[${idx}][item_id]" class="form-control form-control-sm item-sel" required><option value="">{{ translate('Select item') }}</option>${itemOptionsHtml}</select></td>
        <td><input type="number" class="form-control form-control-sm sys-qty" readonly placeholder="—"></td>
        <td><input type="number" name="items[${idx}][physical_qty]" class="form-control form-control-sm phys-qty" step="0.01" min="0" required></td>
        <td><span class="diff-badge">—</span></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="tio-delete-outlined"></i></button></td>`;
    document.getElementById('adjBody').appendChild(row);
    idx++;
});
document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-row')) {
        const rows = document.querySelectorAll('.adj-row');
        if (rows.length > 1) e.target.closest('.adj-row').remove();
    }
});
</script>
@endpush
@endsection
