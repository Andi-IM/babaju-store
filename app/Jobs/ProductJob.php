<?php

namespace App\Jobs;

use App\Imports\ProductImport;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $category;
    protected $fileName;

    /**
     * Create a new job instance.
     * DISPATCH MENGIRIMKAN 2 PARAMETER [$category, $filename]
     * @return void
     */
    public function __construct($category, $filename)
    {
        $this->category = $category;
        $this->fileName = $filename;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // IMPORT DATA EXCEL TADI YANG SUDAH DISIMPAN DI STORAGE, KEMUDIAN CONVERT MENJADI ARRAY
        $files = (new ProductImport)->toArray(storage_path('app/public/uploads/' . $this->fileName));

        foreach ($files[0] as $row) {

            // FORMATTING URLNYA UNTUK MENGAMBIL FILE-NAMENYA BESERTA EXTENSION
            $explodeURL = explode('/', $row[4]);
            $explodeExtension = explode('.', end($explodeURL));
            $fileName = time() . Str::random(6) . '.' . end($explodeExtension);

            // DOWNLOAD GAMBAR TERSEBUT DARI URL TERKAIT
            file_put_contents(storage_path('app/public/products') . '/' . $fileName, file_get_contents($row[4]));

            // KEMUDIAN SIMPAN DATANYA DI DATABASE
            Product::create([
                'name' => $row[0],
                'slug' => $row[0],
                'category_id' => $this->category,
                'description' => $row[1],
                'price' => $row[2],
                'weight' => $row[3],
                'image' => $fileName,
                'status' => true

            ]);
        }

        // JIKA PROSESNYA SUDAH SELESAI MAKA FILE YANG ADA DI STORAGE AKAN DIHAPUS
        File::delete(storage_path('app/public/uploads' . $this->fileName));
    }
}
