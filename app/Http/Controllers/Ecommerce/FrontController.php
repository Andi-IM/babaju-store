<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Province;
use Illuminate\Http\Request;

class FrontController extends Controller
{
    public function index()
    {
        // MEMBUAT QUERY UNTUK MENGAMBIL DATA PRODUK YANG DIURUTKAN BERDASARKAN TGL TERBARU
        // DI LOAD 10 DATA SETIAP PAGENYA
        $products = Product::orderBy('created_at', 'DESC')->paginate(10);

        // LOAD VIEW INDEX.BLADE.PHP DAN PASSING DATA DARI VARIABLE PRODUCTS
        return view('ecommerce.index', compact('products'));
    }

    public function product()
    {
        // BUAT QUERY UNTUK MENGAMBIL DATA PRODUK, LOAD PER PAGENYA KITA GUNAKAN 12 AGAR
        // PRESISI PADA HALAMAN TERSEBUT KARENA DALAM SEBARIS MEMUAT 4 BUAH PRODUK
        $products = Product::orderBy('created_at', 'DESC')->paginate(12);
        return view('ecommerce.product', compact('products'));
    }

    public function categoryProduct($slug)
    {
        // JADI QUERYNYA ADALAH KITA CARI DULU KATEGORI BERDASARKAN SLUG, SETELAH DATANYA DITEMUKAN
        // MAKA SLUG AKAN MENGAMBIL DATA PRODUCT YANG BERELASI MENGGUNAKAN METHOD product() YANG TELAH DIDEFINISIKAN
        // PADA FILE category.php SERTA DIURUTKAN BERDASARKAN created_at DAN DI-LOAD 12 DATA PER SEKALI LOAD
        $products = Category::where('slug', $slug)->first()->product()->orderBy('created_at','DESC')->paginate(12);
        // LOAD KE VIEW product.blade.php
        return view('ecommerce.product', compact('products'));
    }

    public function show($slug)
    {
        // QUERY UNTUK MENGAMBIL SINGLE DATA BERDASARKAN SLUG-NYA
        $product = Product::with('category')->where('slug', $slug)->first();

        // LOAD VIEW SHOW.BLADE.PHP DAN PASSING DATA PRODUCT
        return view('ecommerce.show', compact('product'));
    }

    public function verifyCustomerRegistration($token)
    {
        // JADI KITA BUAT QUERY UNTUK MENGMABIL DATA USER BERDASARKAN TOKEN YANG DITERIMA
        $customer = Customer::where('activate_token', $token)->first();
        if ($customer){
            // JIKA ADA MAKA DATANYA DIUPDATE DENGNA MENGOSONGKAN TOKENNYA DAN STATUSNYA JADI AKTIF
            $customer->update([
               'activate_token' => null,
               'status' => 1
            ]);

            // REDIRECT KE HALAMAN LOGIN DENGAN MENGIRIMKAN FLASH SESSION SUCCESS
            return redirect(route('customer.login'))->with(['success'=>'Verifikasi Berhasil, Silakan Login' ]);
        }

        // JIKA TIDAK ADA, MAKA REDIRECT KE HALAMAN LOGIN
        // DENGAN MENGIRIMKAN FLASH SESSION ERROR
        return redirect(route('customer.login'))->with(['error' => 'Verifikasi Gagal Silakan Coba lagi']);
    }

    public function customerSettingForm()
    {
        // MENGAMBIL DATA CUSTOMER YANG SEDANG LOGIN
        $customer = auth()->guard('customer')->user()->load('district');

        // GET DATA PROPINSI UNTUK DITAMPILKAN PADA SELECT BOX
        $provinces = Province::orderBy('name', 'ASC')->get();

        // LOAD VIEW setting.blade.php DAN PASSING DATA CUSTOMER - PROVINCES
        return view('ecommerce.setting', compact('customer', 'provinces'));
    }

    public function customerUpdateProfile(Request $request)
    {
        // VALIDASI DATA YANG DIKIRIM
        $this->validate($request, [
           'name' => 'required|string|max:100',
            'phone_name' => 'required|max:15',
            'address' => 'required|string',
            'district_id' => 'required|exists:districts,id',
            'password' => 'nullable|string|min:6'
        ]);

        // AMBIL DATA CUSTOMER YANG SEDANG LOGIN
        $user = auth()->guard('customer')->user();

        // AMBIL 4 DATA TERAKHIR YANG DIKIRIM DARI FORM
        $data = $request->only('name', 'phone_number', 'address', 'district_id');

        // CEK PASSWORD
        if ($request->getPassword() != '')
        {
            // TAMBAHKAN KE DALAM ARRAY
            $data['password'] = $request->getPassword();
        }

        // UPDATE DATANYA
        $user->update($data);

        // DAN REDIRECT KEMBALI DENGAN MENGIRIMKAN PESAN BERHASIL
        return redirect()->back()->with(['success' => 'Profil telah diperbarui!']);
    }

    public function referralProduct($user, $product)
    {
        // KITA MERGE USERID DAN PRODUCTID
        $code = $user . '-' . $product;

        // FIND PRODUCT BERDASARKAN PRODUCTID
        $product = Product::find($product);

        // BUAT COOKIE DENGAN NAMA DW-AFILIASI DAN VALUENYA ADALAH CODE YANG SUDAH DI-MERGE
        $cookie = cookie('babaju-afiliasi', json_encode($code), 2880);

        // KEMUDIAN REDIRECT KE HALAMAN SHOW PRODUCT DAN MENGIRIMKAN COOKIE KE BROWSER
        return redirect(route('front.show_product', $product->slug())-cookie($cookie));
    }

    public function listCommission()
    {
        // AMBIL DATA USER YANG LOGIN
        $user = auth()->guard('customer')->user();

        // QUERY BERDASARKAN ID USER DARI DATA REF YANG ADA DIORDER DENGAN STATUS 4 ATAU SELESAI
        $orders = Order::where('ref', $user->id)->where('status', 4)->paginate(10);

        // LOAD VIEW AFFILIATE.BLADE.PHP DAN PASSING DATA ORDERS
        return view('ecommerce.affiliate', compact('orders'));
    }
}
