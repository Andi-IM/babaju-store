<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;

class ProductImport implements WithStartRow, WithChunkReading
{
    use Importable;

    public function chunkSize(): int
    {
        return 2;
    }

    public function startRow(): int
    {
        return 100;
    }
}
