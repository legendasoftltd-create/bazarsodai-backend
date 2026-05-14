@isset($scope)
    @if($scope['type'] === 'central')
        <span class="badge badge-soft-secondary py-2 px-3 align-self-center">
            <i class="tio-world mr-1"></i>{{ translate('Central — All Modules') }}
        </span>
    @else
        <span class="badge badge-soft-primary py-2 px-3 align-self-center">
            <i class="tio-filter-list mr-1"></i>{{ translate('Module') }}: {{ $scope['label'] }}
        </span>
    @endif
@endisset
