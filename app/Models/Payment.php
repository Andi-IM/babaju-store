<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    // UNTUK MENGIZINKAN MENYIMPAN SEMUA FIELD PADA DATA
    protected $guarded = [];
}
