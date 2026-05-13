@if(isset($inventoryLowStockCount) && $inventoryLowStockCount > 0)
<div class="alert __alert-4 m-0 py-1 px-2 mb-2" role="alert" style="background:#fff3cd;border:1px solid #ffc107;border-radius:6px;">
    <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <i class="tio-warning-outlined text-warning mr-2 fz--20"></i>
            <div>
                <strong>{{ translate('Inventory Alert') }}:</strong>
                {{ $inventoryLowStockCount }}
                {{ translate($inventoryLowStockCount === 1 ? 'item is' : 'items are') }}
                {{ translate('below their reorder point.') }}
            </div>
        </div>
        <div>
            <a href="{{ route('vendor.inventory.reorder-points.index') }}" class="btn btn-sm btn-warning ml-2">
                {{ translate('View') }}
            </a>
            <a href="{{ route('vendor.inventory.purchases.create') }}" class="btn btn-sm btn-outline-success ml-1">
                {{ translate('Reorder') }}
            </a>
        </div>
    </div>
</div>
@endif
