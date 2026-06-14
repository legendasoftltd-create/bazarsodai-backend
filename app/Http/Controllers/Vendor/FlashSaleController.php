<?php

namespace App\Http\Controllers\Vendor;

use DateTime;
use App\Models\Item;
use App\Models\FlashSale;
use Illuminate\Http\Request;
use App\Models\FlashSaleItem;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Config;

class FlashSaleController extends Controller
{
    public function index(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $key = explode(' ', $request['search']);

        $flash_sales = FlashSale::where('module_id', Config::get('module.current_module_id'))
            ->where('store_id', $store_id)
            ->orderBy('title')
            ->when(isset($key), function ($q) use ($key) {
                $q->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('title', 'like', "%{$value}%");
                    }
                });
            })
            ->paginate(config('default_pagination'));

        return view('vendor-views.flash-sale.index', compact('flash_sales'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|max:100',
            'title.0' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'vendor_discount_percentage' => 'required|numeric|min:0.01|max:100',
        ], [
            'title.required' => translate('messages.title is required!'),
            'title.0.required' => translate('default_data_is_required'),
        ]);

        $vendor_discount = (float) $request->vendor_discount_percentage;
        $admin_discount = 100 - $vendor_discount;

        $start_date = (new DateTime($request->start_date))->format('Y-m-d H:i:s');
        $end_date = (new DateTime($request->end_date))->format('Y-m-d H:i:s');

        $flash_sale = new FlashSale();
        $flash_sale->title = $request->title[array_search('default', $request->lang)];
        $flash_sale->start_date = $start_date;
        $flash_sale->end_date = $end_date;
        $flash_sale->module_id = Config::get('module.current_module_id');
        $flash_sale->store_id = Helpers::get_store_id();
        $flash_sale->is_publish = 0;
        $flash_sale->admin_discount_percentage = $admin_discount;
        $flash_sale->vendor_discount_percentage = $vendor_discount;
        $flash_sale->save();

        Helpers::add_or_update_translations(request: $request, key_data: 'title', name_field: 'title', model_name: 'FlashSale', data_id: $flash_sale->id, data_value: $flash_sale->title);
        Toastr::success(translate('messages.flash_sale_added_successfully'));
        return back();
    }

    public function edit($id)
    {
        $store_id = Helpers::get_store_id();
        $flash_sale = FlashSale::withoutGlobalScope('translate')
            ->where('store_id', $store_id)
            ->findOrFail($id);

        return view('vendor-views.flash-sale.edit', compact('flash_sale'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|max:100',
            'title.0' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'vendor_discount_percentage' => 'required|numeric|min:0.01|max:100',
        ], [
            'title.required' => translate('messages.title is required!'),
            'title.0.required' => translate('default_data_is_required'),
        ]);

        $vendor_discount = (float) $request->vendor_discount_percentage;
        $admin_discount = 100 - $vendor_discount;

        $start_date = (new DateTime($request->start_date))->format('Y-m-d H:i:s');
        $end_date = (new DateTime($request->end_date))->format('Y-m-d H:i:s');

        $store_id = Helpers::get_store_id();
        $flash_sale = FlashSale::where('store_id', $store_id)->findOrFail($id);
        $flash_sale->title = $request->title[array_search('default', $request->lang)];
        $flash_sale->start_date = $start_date;
        $flash_sale->end_date = $end_date;
        $flash_sale->admin_discount_percentage = $admin_discount;
        $flash_sale->vendor_discount_percentage = $vendor_discount;
        $flash_sale->save();

        Helpers::add_or_update_translations(request: $request, key_data: 'title', name_field: 'title', model_name: 'FlashSale', data_id: $flash_sale->id, data_value: $flash_sale->title);
        Toastr::success(translate('messages.flash_sale_updated_successfully'));
        return back();
    }

    public function delete(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $flash_sale = FlashSale::where('store_id', $store_id)->findOrFail($request->id);
        $flash_sale->products()->delete();
        $flash_sale->translations()->delete();
        $flash_sale->delete();
        Toastr::success(translate('messages.flash_sale_deleted_successfully'));
        return back();
    }

    public function publish(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $flash_sale = FlashSale::where('store_id', $store_id)->find($request->id);
        if ($flash_sale) {
            $flash_sale->is_publish = $request->publish;
            $flash_sale->save();

            FlashSale::whereNot('id', $request->id)
                ->where('store_id', $store_id)
                ->where('module_id', Config::get('module.current_module_id'))
                ->update(['is_publish' => 0]);
        }
        Toastr::success(translate('messages.flash_sale_publish_updated'));
        return back();
    }

    public function add_product(Request $request, $id)
    {
        $store_id = Helpers::get_store_id();
        $flash_sale = FlashSale::where('store_id', $store_id)->findOrFail($id);

        $key = explode(' ', $request['search']);

        $items = FlashSaleItem::where('flash_sale_id', $flash_sale->id)
            ->whereHas('item', function ($q) use ($store_id) {
                $q->where('store_id', $store_id);
            })
            ->when(isset($key), function ($q) use ($key) {
                $q->whereHas('item', function ($q) use ($key) {
                    $q->where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->orWhere('name', 'like', "%{$value}%");
                        }
                    });
                });
            })
            ->paginate(config('default_pagination'));

        return view('vendor-views.flash-sale.product-index', compact('flash_sale', 'items'));
    }

    public function store_product(Request $request)
    {
        $request->validate([
            'item_id' => 'required',
            'stock' => 'required|min:1',
            'discount_type' => 'required',
            'discount' => 'required_if:discount_type,percent,amount',
        ], [
            'item_id.required' => translate('messages.product is required!'),
        ]);

        $store_id = Helpers::get_store_id();
        $flash_sale = FlashSale::where('store_id', $store_id)->findOrFail($request->flash_sale_id);

        $existing = FlashSaleItem::where('flash_sale_id', $flash_sale->id)
            ->where('item_id', $request->item_id)
            ->first();

        if ($existing) {
            Toastr::error(translate('messages.Item_already_exists'));
            return back();
        }

        $item = Item::where('store_id', $store_id)->findOrFail($request->item_id);

        if ($request->stock > $item->stock) {
            Toastr::error(translate('messages.Item_stock_exceeded'));
            return back();
        }

        if ($request->discount_type == 'percent') {
            $discount_amount = ($item->price / 100) * $request->discount;
        } else {
            $discount_amount = $request->discount;
        }

        if ($discount_amount >= $item->price) {
            Toastr::error(translate('messages.Item_discount_amount_exceeded'));
            return back();
        }

        $flash_sale_item = new FlashSaleItem();
        $flash_sale_item->flash_sale_id = $flash_sale->id;
        $flash_sale_item->item_id = $request->item_id;
        $flash_sale_item->stock = $request->stock;
        $flash_sale_item->available_stock = $request->stock;
        $flash_sale_item->discount_type = $request->discount_type;
        $flash_sale_item->discount = $request->discount;
        $flash_sale_item->discount_amount = $discount_amount;
        $flash_sale_item->price = $item->price - $discount_amount;
        $flash_sale_item->save();

        Toastr::success(translate('messages.Item_added_successfully'));
        return back();
    }

    public function delete_product(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $item = FlashSaleItem::whereHas('flashSale', function ($q) use ($store_id) {
            $q->where('store_id', $store_id);
        })->findOrFail($request->id);

        $item->delete();
        Toastr::success(translate('messages.item_deleted_successfully'));
        return back();
    }

    public function status_product(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $item = FlashSaleItem::whereHas('flashSale', function ($q) use ($store_id) {
            $q->where('store_id', $store_id);
        })->findOrFail($request->id);

        $item->status = $request->status;
        $item->save();
        Toastr::success(translate('messages.flash_sale_publish_updated'));
        return back();
    }

    public function get_items(Request $request)
    {
        $store_id = Helpers::get_store_id();

        $items = Item::active()
            ->where('store_id', $store_id)
            ->whereDoesntHave('flashSaleItems.flashSale', function ($query) {
                $now = now();
                $query->where('start_date', '<=', $now)->where('end_date', '>=', $now);
            })
            ->get();

        $res = '';
        if (count($items) > 0) {
            $res = '<option value="0" disabled selected>---' . translate('messages.select') . '---</option>';
        }

        foreach ($items as $row) {
            $res .= '<option value="' . e($row->id) . '">'
                . e($row->name) . ' (' . translate('Stock:') . ' ' . e($row->stock) . ')'
                . '</option>';
        }

        return response()->json(['options' => $res]);
    }
}
