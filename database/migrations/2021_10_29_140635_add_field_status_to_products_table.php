<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldStatusToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // MEMASUKKAN DATA KE TABLE PRODUCTS
        Schema::table('products', function (Blueprint $table) {
            // MENAMBAHKAN FIELD BARU DENGAN TIPE DATA BOOLEAN DAN DISIMPAN SETELAH FIELD WEIGHT
            $table->boolean('status')->default(true)->after('weight');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // MENGHAPUS FIELD STATUS APABILA ROLLBACK TABLE PRODUCTS DIJALANKAN
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
