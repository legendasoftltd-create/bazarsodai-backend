@extends('layouts.vendor.app')

@section('title',translate('messages.Update Flash Sale'))

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/edit.png')}}" class="w--26" alt="">
                </span>
                <span>{{translate('messages.flash_sale_update')}}</span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="card">
            <div class="card-body">
                <form action="{{route('vendor.flash-sale.update',[$flash_sale['id']])}}" method="post">
                    @csrf
                    @php($language=\App\Models\BusinessSetting::where('key','language')->first())
                    @php($language = $language->value ?? null)
                    @php($defaultLang = str_replace('_', '-', app()->getLocale()))
                    @if($language)
                        <ul class="nav nav-tabs mb-4">
                            <li class="nav-item">
                                <a class="nav-link lang_link active" href="#" id="default-link">{{translate('messages.default')}}</a>
                            </li>
                            @foreach (json_decode($language) as $lang)
                                <li class="nav-item">
                                    <a class="nav-link lang_link" href="#" id="{{ $lang }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang) . '(' . strtoupper($lang) . ')' }}</a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="row">
                            <div class="col-12">
                                <div class="lang_form" id="default-form">
                                    <div class="form-group">
                                        <label class="input-label" for="default_title">{{translate('messages.title')}} ({{translate('messages.default')}})</label>
                                        <input type="text" name="title[]" maxlength="100" id="default_title" class="form-control"
                                            placeholder="{{translate('messages.updated_flash_sale')}}" value="{{$flash_sale?->getRawOriginal('title')}}">
                                    </div>
                                    <input type="hidden" name="lang[]" value="default">
                                </div>
                                @foreach(json_decode($language) as $lang)
                                    <?php
                                        if(count($flash_sale['translations'])){
                                            $translate = [];
                                            foreach($flash_sale['translations'] as $t){
                                                if($t->locale == $lang && $t->key=="title"){
                                                    $translate[$lang]['title'] = $t->value;
                                                }
                                            }
                                        }
                                    ?>
                                    <div class="d-none lang_form" id="{{$lang}}-form">
                                        <div class="form-group">
                                            <label class="input-label" for="{{$lang}}_title">{{translate('messages.title')}} ({{strtoupper($lang)}})</label>
                                            <input type="text" name="title[]" maxlength="100" id="{{$lang}}_title" class="form-control"
                                                placeholder="{{translate('messages.updated_flash_sale')}}" value="{{$translate[$lang]['title']??''}}">
                                        </div>
                                        <input type="hidden" name="lang[]" value="{{$lang}}">
                                    </div>
                                @endforeach
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="input-label">{{ translate('messages.my_discount_share') }} (%)
                                        <span class="form-label-secondary" data-toggle="tooltip" data-placement="right" data-original-title="{{ translate('messages.percentage_of_the_discount_you_will_bear._remaining_will_be_covered_by_admin') }}">
                                            <img src="{{asset('public/assets/admin/img/info-circle.svg')}}" alt="">
                                        </span>
                                    </label>
                                    <input type="number" min=".01" step="0.001" max="100" name="vendor_discount_percentage"
                                        value="{{ $flash_sale->vendor_discount_percentage }}"
                                        class="form-control" placeholder="{{ translate('Ex_:_50') }}" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="input-label">{{ translate('messages.validity') }}</label>
                                </div>
                                <div class="row g-3 __bg-F8F9FC-card">
                                    <div class="col-6">
                                        <label class="input-label">{{translate('messages.start_date')}}</label>
                                        <input type="datetime-local" id="from" class="form-control" required name="start_date" value="{{ $flash_sale->start_date }}">
                                    </div>
                                    <div class="col-6">
                                        <label class="input-label">{{translate('messages.end_date')}}</label>
                                        <input type="datetime-local" id="to" class="form-control" required name="end_date" value="{{ $flash_sale->end_date }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="btn--container justify-content-end mt-5">
                        <button type="reset" class="btn btn--reset">{{translate('messages.reset')}}</button>
                        <button type="submit" class="btn btn--primary">{{translate('messages.update')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
<script src="{{asset('public/assets/admin')}}/js/view-pages/flash-sale-index.js"></script>
<script>
    "use strict";
    $(document).on('ready', function () {
        $('#from').attr('min', (new Date()).toISOString().split('T')[0]);
        $('#from').attr('max', '{{$flash_sale->end_date}}');
        $('#to').attr('min', '{{$flash_sale->start_date}}');
    });
</script>
@endpush
