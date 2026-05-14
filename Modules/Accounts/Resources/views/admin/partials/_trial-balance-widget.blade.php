@php
    try {
        $tbWidget = app(\Modules\Accounts\Services\AccountingService::class)
            ->trialBalance(now()->startOfMonth()->toDateString(), now()->toDateString());
        $tbBalanced = $tbWidget['balanced'];
        $tbDiff     = abs($tbWidget['total_debit'] - $tbWidget['total_credit']);
    } catch (\Exception $e) {
        $tbWidget = null;
    }
@endphp

@if(isset($tbWidget))
<div class="col-md-3 col-sm-6 mb-3">
    <div class="card card-body text-center h-100 {{ $tbBalanced ? 'border-success' : 'border-danger' }}" style="border-left-width:4px!important;border-left-style:solid!important">
        <small class="text-muted d-block mb-1">{{ translate('Trial Balance') }} <small>({{ translate('this month') }})</small></small>
        @if($tbBalanced)
            <span class="badge badge-soft-success py-2 px-3 mx-auto">
                <i class="tio-checkmark-circle mr-1"></i>{{ translate('Balanced') }}
            </span>
        @else
            <span class="badge badge-soft-danger py-2 px-3 mx-auto">
                <i class="tio-warning mr-1"></i>{{ translate('Unbalanced') }} Δ{{ number_format($tbDiff, 2) }}
            </span>
        @endif
        <a href="{{ route('admin.accounts.reports.trial-balance') }}" class="btn btn-xs btn-outline-secondary mt-2">
            {{ translate('View Report') }}
        </a>
    </div>
</div>
@endif
