<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    // JIKA FILLABLE AKAN MENGIZINKAN FIELD APA SAJA YANG ADA DIDALAM ARRAYNYA
    // MAKA [$guarded] AKAN MEMBLOK FIELD APA SAJA YANG ADA DIDALAM ARRAY-NYA
    // JADI APABILA FIELDNYA BANYAK MAKA KITA BISA MANFAATKAN DENGAN HANYA MENULISKAN ARRAY KOSONG
    // YANG BERARTI TIDAK ADA FIELD YANG DIBLOCK SEHINGGA SEMUA FIELD TERSEBUT SUDAH DIIZINKAN
    // HAL INI MEMUDAHKAN KITA KARENA TIDAK PERLU MENULISKANNYA SATU PERSATU
    protected $guarded = [];

    // INI ADALAH ACCESSOR, JADI KITA MEMBUAT KOLOM BARU BERNAMA [status_label]
    // KOLOM TERSEBUT DIHASILKAN OLEH ACCESSOR, MESKIPUN FIELD TERSEBUT TIDAK ADA DITABLE PRODUCTS
    // AKAN TETAPI AKAN DISERTAKAN PADA HASIL QUERY
    public function getStatusLabelAttribute()
    {
        // AKAN MENCETAK HTML BERDASARKAN VALUE DARI FIELD STATUS
        if ($this->status == 0) {
            return '<span class="badge badge-secondary">Draft</span>';
        }
        return '<span class="badge badge-success">Aktif</span>';
    }

    // Mutator / Setter
    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = Str::slug($value);
    }

    // FUNGSI YANG MENG-HANDLE RELASI KE TABLE CATEGORY
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
