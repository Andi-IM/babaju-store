<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Mail\CustomerRegisterMail;
use App\Models\City;
use App\Models\Customer;
use App\Models\District;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Province;
use GuzzleHttp\Client;
use http\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function getCarts()
    {
        // AMBIL DATA CART DARI COOKIE,
        // KARENA BENTUKNYA JSON MAKA KITA GUNAKAN JSON_DECODE UNTUK MENGUBAHNYA MENJADI ARRAY
        $carts = json_decode(\request()->cookie('babaju-carts'), true);
        return $carts != '' ? $carts : [];
    }

    public function addToCart(Request $request)
    {
        // VALIDASI DATA YANG DIKIRIM
        $this->validate($request, [
            // PASTIKAN PRODUCT_IDNYA ADA DI DB
            'product_id' => 'required|exists:products,id',
            // PASTIKAN QTY YANG DIKIRIM INTEGER
            'qty' => 'required|integer'
        ]);

        $carts = $this->getCarts();

        // CEK JIKA CARTS TIDAK NULL DAN PRODUCT_ID ADA DIDALAM ARRAY CARTS
        if ($carts && array_key_exists($request->product_id, $carts)) {
            // MAKA UPDATE QTY-NYA BERDASARKAN product_id YANG DIJADIKAN KEY ARRAY
            $carts[$request->product_id]['qty'] += $request->qty;
        } else {
            // SELAIN ITU, BUAT QUERY UNTUK MENGAMBIL PRODUK BERDASARKAN product_id
            $product = Product::find($request->product_id);

            // TAMBAHKAN DATA BARU DENGAN MENJADIKAN product_id SEBAGAI KEY DARI ARRAY carts
            $carts[$request->product_id] = [
                'qty' => $request->qty,
                'product_id' => $product->id,
                'product_name' => $product->price,
                'product_price' => $product->price,
                'product_image' => $product->image,
                'weight' => $product->weight
            ];
        }

        // limit in 48 hours
        $cookie = cookie('babaju-carts', json_encode($carts), 2880);

        // STORE KE BROWSER UNTUK DISIMPAN
        return redirect()->back()->cookie($cookie);
    }

    public function listCart()
    {
        // MENGAMBIL DATA DARI COOKIE
        $carts = $this->getCarts();

        // UBAH ARRAY MENJADI COLLECTION DAN HITUNG SUBTOTAL MENGGUNAKAN METHOD SUM
        $subtotal = collect($carts)->sum(function ($q) {
            return $q['qty'] * $q['product_price']; // SUBTOTAL = QTY * PRICE
        });

        // LOAD VIEW CART.BLADE.PHP DAN PASSING DATA CARTS DAN SUBTOTAL
        return view('ecommerce.cart', compact('carts', 'subtotal'));
    }

    public function updateCart(Request $request)
    {
        // AMBIL DATA DARI COOKIE
        $carts = $this->getCarts();

        // KEMUDIAN LOOPING DATA product_id, KARENA NAMENYA ARRAY PADA VIEW SEBELUMNYA
        // MAKA DATA YANG DITERIMA ADALAH ARRAY SEHINGGA BISA DI-LOOPING
        foreach ($request->product_id as $key => $row) {
            // DI CHECK, JIKA QTY DENGAN KEY YANG SAMA DENGAN product_idd = 0
            if ($request->qty[$key] == 0) {
                // MAKA DATA TERSEBUT DIHAPUS DARI ARRAY
                unset($carts[$row]);
            } else {
                // SELAIN ITU MAKA AKAN DIPERBAHARUI
                $carts[$row]['qty'] = $request->qty[$key];
            }
        }

        // SET KEMBALI COOKIE-NYA SEPERTI SEBELUMNYA
        $cookie = cookie('babaju-carts', json_encode($carts));

        // DAN STORE KE BROWSER.
        return redirect()->back()->cookie($cookie);
    }

    public function checkout()
    {
        // QUERY UNTUK MENGAMBIL SEMUA DATA PROPINSI
        $provinces = Province::orderBy('created_at', 'DESC')->get();

        // MENGAMBIL DATA CART
        $carts = $this->getCarts();

        // MENGHITUNG SUBTOTAL DARI KERANJANG BELANJA (CART)
        $subtotal = collect($carts)->sum(function ($q) {
            return $q['qty'] * $q['product_price'];
        });

        $weight = collect($carts)->sum(function ($q) {
            return $q['qty'] * $q['weight'];
        });

        // ME-LOAD VIEW CHECKOUT.BLADE.PHP DAN PASSING DATA PROVINCES, CARTS DAN SUBTOTAL
        return view('ecommerce.checkout', compact('provinces', 'carts', 'subtotal' , 'weight'));
    }

    public function getCity()
    {
        // QUERY UNTUK MENGAMBIL DATA KOTA / KABUPATEN BERDASARKAN province_id
        $cities = City::where('province_id', request()->province_id)->get();
        // KEMBALIKAN DATANYA DALAM BENTUK JSON
        return response()->json(['status' => 'success', 'data' => $cities]);
    }

    public function getDistrict()
    {
        // QUERY UNTUK MENGAMBIL DATA KECAMATAN BERDASARKAN city_id
        $districts = District::where('city_id', request()->city_id)->get();
        // KEMUDIAN KEMBALIKAN DATANYA DALAM BENTUK JSON
        return response()->json(['status' => 'success', 'data' => $districts]);
    }

    public function prosesCheckout(Request $request)
    {
        // VALIDASI DATANYA
        $this->validate($request, [
            'customer-name' => 'required|string|max:100',
            'customer_phone' => 'required',
            'email' => 'required|email',
            'customer_address' => 'required|string',
            'province_id' => 'required|exists:cities,id',
            'city_id' => 'required|exists:cities,id',
            'district_id' => 'required|exists:districts,id'
        ]);

        // INISIASI DATABASE TRANSACTION
        // DATABASE TRANSACTION BERFUNGSI UNTUK MEMASTIKAN SEMUA PROSES SUKSES UNTUK KEMUDIAN DI COMMIT
        // AGAR DATA BENAR BENAR DISIMPAN, JIKA TERJADI ERROR MAKA KITA ROLLBACK AGAR DATANYA SELARAS.
        DB::beginTransaction();
        try {
            // CHECK DATA CUSTOMER BERDASARKAN EMAIL
            $customer = Cutomer::where('email', $request->email)->first();

            // GET COOKIE DARI BROWSER
            $affiliate = json_decode(request()->cookie('babaju-affiliasi'));

            // EXPLODE DATA COOKIE UNTUK MEMISAHKAN USERID DAN PRODUCTID
            $explodeAffiliate = explode('-', $affiliate);

            // JIKA DIA TIDAK LOGIN DAN DATA CUSTOMERNYA ADA
            if (!auth()->guard('customer')->check() && $customer) {
                // MAKA REDIRECT DAN TAMPILKAN INSTRUKSI UNTUK LOGIN
                return redirect()->back()->with(['error' => 'Silakan Login Terlebih Dahulu!']);
            }

            // AMBIL DATA KERANJANG
            $carts = $this->getCarts();

            // HITUNG SUBTOTAL BELANJAAN
            $subtotal = collect($carts)->sum(function ($q) {
                return $q['qty'] * $q['product_price'];
            });

            // UNTUK MENGHINDARI DUPLICATE CUSTOMER, MASUKKAN QUERY UNTUK MENAMBAHKAN CUSTOMER BARU
            // SEBENARNYA VALIDASINYA BISA DIMASUKKAN PADA METHOD VALIDATION DIATAS,
            // TAPI TIDAK MENGAPA UNTUK MENCOBA CARA BERBEDA
            if (!auth()->guard('customer')->check()) {
                $password = Str::random(8);

                // SIMPAN DATA CUSTOMER BARU
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
            }

            // SIMPAN DATA ORDER
            $order = Order::create([
                // INVOICENYA KITA BUAT DARI STRING RANDOM DAN WAKTU
                'invoice' => Str::random(4) . '-' . time(),
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->customer_phone,
                'customer_address' => $customer->customer_address,
                'district_id' => $customer->district_id,
                'subtotal' => $subtotal,
                'ref' => $affiliate != '' && $explodeAffiliate[0] != auth()->guard('customer')->user()->id ? $affiliate : NULL
            ]);
            // CODE DIATAS MELAKUKAN PENGECEKAN JIKA USERID NYA BUKAN DIRINYA SENDIRI, MAKA AFILIASINYA DISIMPAN

            // LOOPING DATA DI CARTS
            foreach ($carts as $row) {
                //AMBIL DATA PRODUK BERDASARKAN product_id
                $product = Product::find($row['product_id']);

                // SIMPAN DETAIL ORDER
                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $row['product_id'],
                    'price' => $row['product_price'],
                    'qty' => $row['qty'],
                    'weight' => $product->weight
                ]);
            }

            // TIDAK TERJADI ERROR, MAKA COMMIT DATANYA UNTUK MENINFORMASIKAN BAHWA DATA SUDAH FIX UNTUK DISIMPAN
            DB::commit();

            $carts = [];

            //KOSONGKAN DATA KERANJANG DI COOKIE
            $cookie = cookie('babaju-carts', json_encode($carts), 2880);

            // KEMUDIAN HAPUS DATA COOKIE AFILIASI
            Cookie::queue(Cookie::forget('babaju-affiliasi'));

            // EMAIL UNTUK CUSTOMER BARU
            if (!auth()->guard('customer')->check()) {
                Mail::to($request->email)->send(new CustomerRegisterMail($customer, $password));
            }

            // REDIRECT KE HALAMAN FINISH TRANSAKSI
            return redirect(route('front.finish_checkout', $order->invoice))->cookie($cookie);

        } catch (Exception $e) {
            // JIKA TERJADI ERROR, MAKA ROLLBACK DATANYA
            DB::rollBack();

            // DAN KEMBALI KE FORM TRANSAKSI SERTA MENAMPILKAN ERROR
            return redirect()->back()->with(['error' => $e->getMessage()]);
        }
    }

    public function checkoutFinish($invoice)
    {
        // AMBIL DATA PESANAN BERDASARKAN INVOICE
        $order = Order::with(['district.city'])->where('invoice', $invoice)->first();

        // LOAD VIEW checkout_finish.blade.php DAN PASSING DATA ORDER
        return view('ecommerce.checkout_finish', compact('order'));
    }

    public function getCourier(Request $request)
    {
        $this->validate($request, [
            'destination' => 'required',
            'weight' => 'required|integer'
        ]);

        // hati2 menggunakan Authorization.

        $url = 'http://api.rajaongkir.com/starter/cost';
        $client = new CLient();
        $response = $client->request('POST', $url, [
            'headers' => [
                'key' => '95f6ec0b350a02add464c17920c44065'
            ],
            'form_params' => [
                'origin' => 318,
                'destination' => $request->destination,
                'weight' => $request->weight,
                'courier' => 'jne'
            ]
        ]);

        $body = json_decode($response->getBody(), true);
        return $body;
    }
}
