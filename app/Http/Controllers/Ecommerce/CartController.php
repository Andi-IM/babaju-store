<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Mail\CustomerRegisterMail;
use App\Models\Product;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use http\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Psy\Util\Str;

class CartController extends Controller
{
    public function getCarts()
    {
        $carts = json_decode(\request()->cookie('babaju-carts'), true);
        return $carts != '' ? $carts : [];
    }

    public function addToCart(Request $request)
    {
        $this->validate($request, [
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer'
        ]);

        $carts = $this->getCarts();
        if ($carts && array_key_exists($request->product_id, $carts)) {
            $carts[$request->product_id]['qty'] += $request->qty;
        } else {
            $product = Product::find($request->product_id);
            $carts[$request->product_id] = [
                'qty' => $request->qty,
                'product_id' => $product->id,
                'product_name' => $product->price,
                'product_price' => $product->price,
                'product_image' => $product->image
            ];
        }

        // limit in 48 hours
        $cookie = cookie('babaju-carts', json_encode($carts), 2880);
        return redirect()->back()->cookie($cookie);
    }

    public function listCart()
    {
        $carts = $this->getCarts();
        $subtotal = collect($carts)->sum(function ($q) {
            return $q['qty'] * $q['product_price'];
        });

        return view('ecommerce.cart', compact('carts', $subtotal));
    }

    public function updateCart(Request $request)
    {
        $carts = $this->getCarts();
        foreach ($request->product_id as $key => $row) {
            if ($request->qty[$key] == 0) {
                unset($carts[$row]);
            } else {
                $carts[$row]['qty'] = $request->qty[$key];
            }
        }

        $cookie = cookie('babaju-carts', json_encode($carts));
        return redirect()->back()->cookie($cookie);
    }

    public function checkout()
    {
        $provinces = Province::orderBy('created_at', 'DESC')->get();
        $carts = $this->getCarts();
        $subTotal = collect($carts)->sum(function ($q) {
            return $q['qty'] * $q['product_price'];
        });

        return view('ecommerce.checkout', compact('provinces', 'carts', 'subTotal'));
    }

    public function getCity()
    {
        $cities = City::where('province_id', request()->province_id)->get();
        return response()->json(['status' => 'success', 'data' => $cities]);
    }

    public function getDistrict()
    {
        $districts = District::where('city_id', request()->city_id)->get();
        return response()->json(['status' => 'success', 'data' => $districts]);
    }

    public function prosesCheckout(Request $request)
    {
        $this->validate($request, [
            'customer-name' => 'required|string|max:100',
            'customer_phone' => 'required',
            'email' => 'required|email',
            'customer_address' => 'required|string',
            'province_id' => 'required|exists:cities,id',
            'city_id' => 'required|exists:cities,id',
            'district_id' => 'required|exists:districts,id'
        ]);

        DB::beginTransaction();
        try {
            $affiliate = json_decode(request()->cookie('babaju-affiliasi'));
            $explodeAffiliate = explode('-', $affiliate);

            $customer = Cutomer::where('email', $request->email)->first();

            if (!auth()->guard('customer')->check() && $customer)
            {
                return redirect()->back()->with(['error' => 'Silakan Login Terlebih Dahulu!']);
            }

            $carts = $this->getCarts();
            $subtotal = collect($carts)->sum(function ($q) {
                return $q['qty'] * $q['product_price'];
            });

            if (!auth()->guard('customer')->check())
            {
                $password = Str::random(8);
                $customer = Customer::create([
                   'name' => $request->customer_name,
                   'email' => $request->email,
                   'password' => $password,
                   'phone_number' => $request->customer_phone,
                   'address' => $request->customer_address,
                   'district_id' => $request->district_id,
                   'activate_token' => Str::random(30),
                   'status' => false
                ]);

                $order = Order::create([
                    'invoice' => Str::random(4) . '-' . time(),
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'customer_phone' => $customer->customer_phone,
                    'customer_address' => $customer->customer_address,
                    'district_id' => $customer->district_id,
                    'subtotal' => $subtotal,
                    'ref' => $affiliate != '' && $explodeAffiliate[0] != auth()->guard('customer')->user()->id ? $affiliate:NULL
                ]);

                foreach ($carts as $row) {
                    $product = Product::find($row['product_id']);
                    OrderDetail::create([
                        'order_id' => $order->id,
                        'product_id' => $row['product_id'],
                        'price' => $row['product_price'],
                        'qty' => $row['qty'],
                        'weight' => $product->weight
                    ]);
                }

                DB::commit();

                $carts = [];
                $cookie = cookie('babaju-carts', json_encode($carts), 2880);
                Cookie::queue(Cookie::forget('babaju-affiliasi'));

                if (!auth()->guard('customer')->check())
                {
                    Mail::to($request->email)->send(new CustomerRegisterMail($customer, $password));
                }
            }
        }catch (Exception $e){
            DB::rollBack();
            return redirect()->back()->with(['error' => $e->getMessage()]);
        }
    }

    public function checkoutFinish($invoice)
    {
        $order = Order::with(['district.city'])->where('invoice', $invoice)->first();
        return view('ecommerce.checkout_finish', compact('order'));
    }
}
