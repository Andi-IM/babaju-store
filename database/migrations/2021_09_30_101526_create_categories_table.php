<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Membuat Table [categories]
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            // string = VARCHAR
            $table->string('name');

            // menjadikan category ini memiliki anak kategori
            // parent_id yang memiliki nilai adalah anak kategori
            // parent_id yang null bukan anak kategori.
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('slug');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // menghapus tabel jika dilakukan rollback
        Schema::dropIfExists('categories');
    }
}
