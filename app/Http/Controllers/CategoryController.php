<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        // BUAT QUERY KE DATABASE MENGGUNAKAN MODEL CATEGORY DENGAN MENGURUTKAN BERDASARKAN created_at DAN
        // DISET DESCending, KEMUDIAN paginate(10) BERARTI HANYA MENGAMBIL 10 DATA SETIAP PAGE-NYA
        // YANG MENARIK ADALAH FUNGSI with(), DIMANA FUNGSI INI DISEBUT [EAGER LOADING]
        // ADAPUN NAMA YANG DISEBUTKAN DIDALAMNYA ADALAH [NAMA METHOD] YANG DIDEFINISIKAN DIDALAM MODEL CATEGORY
        // METHOD TERSEBUT BERISI FUNGSI RELATIONSHIPS ANTAR TABLE
        // JIKA LEBIH DARI 1 MAKA DAPAT DIPISAHKAN DENGAN KOMA,
        // CONTOH: with(['parent', 'contoh1', 'contoh2'])
        $category = Category::with(['parent'])->orderBy('created_at', 'DESC')->paginate(10);

        // QUERY INI MENGAMBIL SEMUA LIST CATEGORY DARI TABLE CATEGORIES, PERHATIKAN AKHIRANNYA ADALAH get()
        // TANPA ADA LIMIT LALU getParent() DARI MANA? METHOD TERSEBUT ADALAH SEBUAH [LOCAL SCOPE]
        $parent = Category::getParent()->orderBy('name', 'ASC')->get();

        //LOAD VIEW DARI FOLDER categories, DAN DIDALAMNYA ADA FILE index.blade.php
        //KEMUDIAN PASSING DATA DARI VARIABLE $category & $parent KE VIEW AGAR DAPAT DIGUNAKAN PADA VIEW TERKAIT
        return view('categories.index', compact('category', 'parent'));
    }

    public function store(Request $request)
    {
        // MEMVALIDASI DATA YANG DITERIMA, DI MANA [NAMA KATEGORI] WAJIB DIISI
        // TIPENYA ADA STRING DAN MAX KARATERNYA ADALAH 50 DAN BERSIFAT UNIK
        // UNIK MAKSUDNYA JIKA DATA DENGAN NAMA YANG SAMA SUDAH ADA MAKA VALIDASINYA AKAN MENGEMBALIKAN ERROR
        $this->validate($request, [
            'name' => 'required|string|max:50|unique:categories'
        ]);

        // FIELD slug AKAN DITAMBAHKAN KEDALAM COLLECTION $REQUEST
        $request->request->add(['slug' => $request->name]);

        // SEHINGGA PADA BAGIAN INI KITA TINGGAL MENGGUNAKAN $request->except()
        // YAKNI MENGGUNAKAN SEMUA DATA YANG ADA DIDALAM $REQUEST KECUALI INDEX [_token]
        // FUNGSI REQUEST INI SECARA OTOMATIS AKAN MENJADI ARRAY
        // [Category::create()] ADALAH MASS ASSIGNMENT UNTUK MEMBERIKAN INSTRUKSI KE MODEL
        // AGAR MENAMBAHKAN DATA KE TABLE TERKAIT
        Category::create($request->except('_token'));

        // APABILA BERHASIL, MAKA REDIRECT KE HALAMAN [category list]
        // DAN MEMBUAT FLASH SESSION MENGGUNAKAN with()
        // JADI with() DISINI BERBEDA FUNGSINYA DENGAN with() YANG DISAMBUNGKAN DENGAN MODEL
        return redirect(route('category.index'))->with(['success' => 'Kategori Baru Ditambahkan!']);
    }

    public function edit($id)
    {
        // QUERY MENGAMBIL DATA BERDASARKAN [$id]
        $category = Category::find($id);
        // INI SAMA DENGAN QUERY YANG ADA PADA METHOD INDEX
        $parent = Category::getParent()->orderBy('name', 'ASC')->get();

        // LOAD VIEW [edit.blade.php] PADA FOLDER CATEGORIES
        // DAN PASSING VARIABLE CATEGORY & PARENT
        return view('categories.edit', compact('category', 'parent'));
    }

    public function update(Request $request, $id)
    {
        // VALIDASI FIELD NAME
        // YANG BERBEDA ADA TAMBAHAN PADA RULE UNIQUE
        // FORMATNYA ADALAH [unique:nama_table,nama_field,id_ignore]
        // JADI KITA TETAP MENGECEK UNTUK MEMASTIKAN BAHWA NAMA CATEGORY-NYA UNIK
        // AKAN TETAPI KHUSUS DATA DENGAN ID YANG AKAN DIUPDATE DATANYA DIKECUALIKAN
        $this->validate($request, [
            'name' => 'required|string|max:50|unique:categories,name,' . $id
        ]);

        $category = Category::find($id); //QUERY UNTUK MENGAMBIL DATA BERDASARKAN ID

        // KEMUDMIAN PERBAHARUI DATANYA
        // POSISI KIRI (key) ADALAH NAMA FIELD YANG ADA DITABLE CATEGORIES
        // POSISI KANAN (value) ADALAH VALUE DARI FORM EDIT
        $category->update([
            'name' => $request->name,
            'parent_id' => $request->parent_id
        ]);

        // REDIRECT KE HALAMAN LIST KATEGORI
        return redirect(route('category.index'))->with(['success' => 'Data Berhasil Diubah!']);
    }

    public function destroy($id)
    {
        // Buat query untuk mengambil category berdasarkan id menggunakan method find()
        // ADAPUN withCount() SERUPA DENGAN EAGER LOADING YANG MENGGUNAKAN with()
        // HANYA SAJA withCount() RETURNNYA ADALAH INTEGER
        // JADI NNTI HASIL QUERYNYA AKAN MENAMBAHKAN FIELD BARU BERNAMA child_count, dan product_count
        // YANG BERISI JUMLAH DATA ANAK KATEGORI
        $category = Category::withCount(['child', 'product'])->find($id);

        // JIKA KATEGORI INI TIDAK DIGUNAKAN SEBAGAI PARENT ATAU CHILDNYA = 0 dan juga Productnya = 0
        if ($category->child_count == 0 && $category->product_count == 0) {
            // MAKA HAPUS KATEGORI INI DIIZINKAN
            $category->delete();

            // DAN REDIRECT KEMBALI KE HALAMAN LIST KATEGORI
            return redirect(route('category.index'))->with(['success' => 'Kategori Dihapus!']);
        }

        // SELAIN ITU, MAKA REDIRECT KE LIST TAPI FLASH MESSAGENYA ERROR YANG BERARTI KATEGORI INI SEDANG DIGUNAKAN
        return redirect(route('category.index'))->with(['error' => 'Kategori Ini Memiliki Anak Kategori!']);
    }
}
