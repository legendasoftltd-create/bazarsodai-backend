@extends('layouts.vendor.app')

@section('title',translate('messages.flash_sales'))

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/condition.png')}}" class="w--26" alt="">
                </span>
                <span>{{translate('messages.flash_sale_product_setup')}}</span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="row g-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{route('vendor.flash-sale.store-product')}}" method="post">
                            @csrf
                            <input type="hidden" name="flash_sale_id" value="{{ $flash_sale->id }}">
                            <div class="row mb-3">
                                <div class="col-12 mb-2">
                                    <div class="form-group mb-0">
                                        <label class="input-label">{{translate('messages.select_item')}}</label>
                                        <select name="item_id" id="choice_item" class="form-control js-select2-custom" placeholder="{{translate('messages.select_item')}}"></select>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label">{{ translate('messages.total_stock') }}</label>
                                        <input type="number" placeholder="{{ translate('messages.Ex:_10') }}" class="form-control" name="stock" min="0" id="quantity">
                                    </div>
                                </div>
                                <div class="col-sm-4 col-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label">{{ translate('messages.discount_type') }}</label>
                                        <select name="discount_type" id="discount_type" class="form-control js-select2-custom">
                                            <option value="percent">{{ translate('messages.percent') }}</option>
                                            <option value="amount">{{ translate('messages.amount') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label">{{ translate('messages.discount') }}</label>
                                        <input type="number" min="0" value="0" step="0.001" name="discount" class="form-control" id="discount_amount" placeholder="{{ translate('messages.Ex:') }} 100">
                                    </div>
                                </div>
                            </div>
                            <div class="btn--container justify-content-end">
                                <button type="reset" class="btn btn--reset">{{translate('messages.reset')}}</button>
                                <button type="submit" class="btn btn--primary">{{translate('messages.submit')}}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-header py-2 border-0">
                        <div class="search--button-wrapper">
                            <h5 class="card-title">
                                {{translate('messages.flash_sale_product_list')}}<span class="badge badge-soft-dark ml-2" id="itemCount">{{$items->total()}}</span>
                            </h5>
                            <form class="search-form">
                                <div class="input-group input--group">
                                    <input id="datatableSearch_" value="{{ request()?->search ?? null }}" type="search" name="search" class="form-control"
                                        placeholder="{{translate('ex_:_product_name')}}" aria-label="Search">
                                    <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="table-responsive datatable-custom">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                            data-hs-datatables-options='{"order":[],"orderCellsTop":true,"paging":false}'>
                            <thead class="thead-light">
                                <tr class="text-center">
                                    <th class="border-0">{{translate('sl')}}</th>
                                    <th class="border-0">{{translate('messages.product')}}</th>
                                    <th class="border-0">{{translate('messages.stock_for_this_sale')}}</th>
                                    <th class="border-0">{{translate('messages.Qty_Sold')}}</th>
                                    <th class="border-0">{{translate('messages.price')}}</th>
                                    <th class="border-0">{{translate('messages.status')}}</th>
                                    <th class="border-0">{{translate('messages.action')}}</th>
                                </tr>
                            </thead>
                            <tbody id="set-rows">
                                @foreach($items as $key=>$item)
                                    <tr>
                                        <td class="text-center">{{$key+$items->firstItem()}}</td>
                                        <td class="text-center">
                                            <a class="media align-items-center" href="{{route('vendor.item.view',[$item['item_id']])}}">
                                                <img class="avatar avatar-lg mr-3 onerror-image" src="{{ $item->item['image_full_url'] }}"
                                                    data-onerror-image="{{asset('public/assets/admin/img/160x160/img2.jpg')}}" alt="{{$item->item->name}} image">
                                                <div class="media-body">
                                                    <h5 title="{{ $item->item['name'] }}" class="text-hover-primary mb-0">{{Str::limit($item->item['name'],20,'...')}}</h5>
                                                </div>
                                            </a>
                                        </td>
                                        <td class="text-center">{{ $item['stock'] }}</td>
                                        <td class="text-center">{{ $item['sold'] }}</td>
                                        <td class="text-center">{{ \App\CentralLogics\Helpers::format_currency($item['price']) }}</td>
                                        <td class="text-center">
                                            <label class="toggle-switch toggle-switch-sm" for="publishCheckbox{{$item->id}}">
                                                <input type="checkbox"
                                                    data-url="{{route('vendor.flash-sale.status-product',[$item['id'],$item->status?0:1])}}"
                                                    class="toggle-switch-input redirect-url"
                                                    id="publishCheckbox{{$item->id}}" {{$item->status?'checked':''}}>
                                                <span class="toggle-switch-label mx-auto">
                                                    <span class="toggle-switch-indicator"></span>
                                                </span>
                                            </label>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn--container justify-content-center">
                                                <a class="btn action-btn btn--danger btn-outline-danger form-control form-alert" href="javascript:"
                                                    data-id="item-{{$item['id']}}"
                                                    data-message="{{ translate('Want to delete this item ?') }}"
                                                    title="{{translate('messages.delete')}}">
                                                    <i class="tio-delete-outlined"></i>
                                                </a>
                                                <form action="{{route('vendor.flash-sale.delete-product',[$item['id']])}}" method="post" id="item-{{$item['id']}}">
                                                    @csrf @method('delete')
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(count($items) !== 0)
                        <hr>
                    @endif
                    <div class="page-area">
                        {!! $items->links() !!}
                    </div>
                    @if(count($items) === 0)
                        <div class="empty--data">
                            <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                            <h5>{{translate('no_data_found')}}</h5>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
<script>
    "use strict";
    function get_items() {
        let nurl = '{{ route('vendor.flash-sale.get-items') }}';
        $.get({
            url: nurl,
            dataType: 'json',
            success: function (data) {
                $('#choice_item').empty().append(data.options);
            }
        });
    }

    $(document).on('ready', function () {
        get_items();
        $('.js-select2-custom').each(function () {
            let select2 = $.HSCore.components.HSSelect2.init($(this));
        });
    });
</script>
@endpush
