@if(isset($inventoryLowStockCount) && $inventoryLowStockCount > 0)
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-warning d-flex align-items-center justify-content-between mb-0" role="alert">
            <div>
                <i class="tio-warning-outlined mr-2"></i>
                <strong>{{ $inventoryLowStockCount }}</strong>
                {{ translate($inventoryLowStockCount === 1 ? 'item has' : 'items have') }}
                {{ translate('fallen below their reorder threshold.') }}
            </div>
            <div>
                <a href="{{ route('admin.inventory.reorder-points.index') }}" class="btn btn-sm btn-warning mr-2">
                    {{ translate('View Reorder Points') }}
                </a>
                <a href="{{ route('admin.inventory.reports.low-stock') }}" class="btn btn-sm btn-outline-warning">
                    {{ translate('Low Stock Report') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endif
