<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;
    // mengizinkan data di dalam array untuk dimodifikasi
    protected $fillable = ['name', 'parent_id', 'slug'];

    //INI ADALAH METHOD UNTUK MENG-HANDLE RELATIONSHIPS
    public function parent()
    {
        // KARENA RELASINYA DENGAN DIRINYA SENDIRI, MAKA CLASS MODEL DI DALAM belongsTo() ADALAH NAMA CLASSNYA SENDIRI
        // YAKNI [Category] belongsTo DIGUNAKAN UNTUK REFLEKSI KE DATA INDUKNYA
        return $this->belongsTo(Category::class);
    }

    // UNTUK LOCAL SCOPE NAMA METHODNYA DIAWAL DENGAN KATA scope DAN DIIKUTI DENGAN NAMA METHOD YANG DIINGINKAN
    // CONTOH: scopeNamaMethod()
    public function scopeGetParent($query)
    {
        // SEMUA QUERY YANG MENGGUNAKAN LOCAL SCOPE INI AKAN SECARA OTOMATIS DITAMBAHKAN KONDISI whereNull('parent_id')
        return $query->whereNull('parent_id');
    }

    // Mutator / Setter
    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = Str::slug($value);
    }

    // Accessor / Getter
    public function getNameAttribute($value)
    {
        return ucfirst($value);
    }

    public function child()
    {
        // MENGGUNAKAN RELASI ONE TO MANY DENGAN FOREIGN KEY parent_id
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function product()
    {
        // JENIS RELASINYA ADALAH ONE TO MANY, YANG BERARTI KATEGORI INI BISA DIGUNAKAN OLEH BANYAK PRODUK
        return $this->hasMany(Product::class);
    }
}
