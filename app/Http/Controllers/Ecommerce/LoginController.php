<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function loginForm()
    {
        if (auth()->guard('customer')->check()) return redirect(route('customer.dashboard'));
        return view('ecommerce.login');
    }

    public function login(Request $request)
    {
        // VALIDASI DATA YANG DITERIMA
        $this->validate($request, [
           'email' => 'required|email|exists:customers,email',
           'password' => 'required|string'
        ]);

        // CUKUP MENGAMBIL EMAIL DAN PASSWORD SAJA DARI REQUEST
        // KARENA JUGA DISERTAKAN TOKEN
        $auth = $request->only('email', 'password');

        // STATUS YANG BISA LOGIN HARUS 1
        $auth['status'] = 1;

        // CHECK UNTUK PROSES OTENTIKASI
        // DARI GUARD CUSTOMER, ATTEMPT PROSESNYA DARI DATA $auth
        if (auth()->guard('customer')->attempt($auth)){
            // JIKA BERHASIL MAKA REDIRECT KE DASHBOARD
            return redirect()->intended(route('customer.dashboard'));
        }

        // JIKA GAGAL MAKA REDIRECT KEMBALI BERSERTA NOTIFIKASI
        return redirect()->back()->with(['error' => 'Email / Password Salah!']);
    }

    public function dashboard()
    {
        // Order::selectRaw() memungkinkan kita membuat custom query
        $orders = Order::selectRaw('COALESCE(sum(CASE WHEN status = 0 THEN subtotal END), 0) as pending,
        COALESCE(count(CASE WHEN status = 3 THEN subtotal END), 0) as shipping,
        COALESCE(count(CASE WHEN status = 4 THEN subtotal END), 0) as completeOrder')->where('customer_id',
        auth()->guard('customer')->user()->id)->get();

        return view('ecommerce.dashboard', compact('orders'));
    }

    public function logout()
    {
        // LOGOUT SESSION DARI GUARD CUSTOMER
        auth()->guard('customer')->logout();
        return redirect(route('customer.login'));
    }
}
