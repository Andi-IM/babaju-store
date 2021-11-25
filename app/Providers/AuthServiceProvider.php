<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // MEMBUAT GATE DENGAN NAMA order-view
        Gate::define('order-view', function ($customer, $order){
            // KEMUDIAN DICEK, APAKAH CUSTOMER ID SAMA DENGAN CUSTOMER_ID YANG ADA PADA TABLE ORDER
            // GATE INI HANYA AKAN ME-RETURN TRUE/FALSE SEBAGAI TANDA DIIZINKAN ATAU TIDAK
            return $customer->id == $order->customer_id;
        });
    }
}
