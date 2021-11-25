<?php

namespace App\Http\Controllers;

use App\Jobs\ProductJob;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        // BUAT QUERY MENGGUNAKAN MODEL PRODUCT, DENGAN MENGURUTKAN DATA BERDASARKAN [created_at]
        // KEMUDIAN LOAD TABLE YANG BERELASI MENGGUNAKAN EAGER LOADING [with()]
        // ADAPUN CATEGORY ADALAH NAMA FUNGSI YANG NNTINYA AKAN DITAMBAHKAN PADA PRODUCT MODEL
        $product = Product::with(['category'])->orderBy('created_at', 'DESC');

        // JIKA TERDAPAT PARAMETER PENCARIAN DI URL ATAU q (query) PADA URL TIDAK KOSONG
        if (request()->q != '') {
            // MAKA LAKUKAN FILTERING DATA BERDASARKAN NAME DAN VALUENYA SESUAI DENGAN PENCARIAN YANG DILAKUKAN USER
            $product = $product->where('name', 'LIKE', '%' . \request()->q . '%');
        }

        // LOAD 10 DATA PER HALAMANNYA
        $product = $product->paginate(10);

        // LOAD VIEW [index.blade.php] YANG BERADA DIDALAM FOLDER PRODUCTS
        // DAN PASSING VARIABLE [$product] KE VIEW AGAR DAPAT DIGUNAKAN
        return view('products.index', compact('product'));
    }

    public function create()
    {
        // QUERY UNTUK MENGAMBIL SEMUA DATA CATEGORY
        $category = Category::orderBy('name', 'DESC')->get();

        // LOAD VIEW [create.blade.php] YANG BERADA DI DALAM FOLDER PRODUCTS
        // DAN PASSING DATA CATEGORY
        return view('products.create', compact('category'));
    }

    public function store(Request $request)
    {
        // VALIDASI REQUESTNYA
        $this->validate($request, [
            'name' => 'required|string|max:100',
            'description' => 'required',

            // [category_id] KITA CEK HARUS ADA DI TABLE CATEGORIES DENGAN FIELD ID
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|integer',
            'weight' => 'required|integer',

            // GAMBAR DIVALIDASI HARUS BERTIPE PNG,JPG DAN JPEG
            'image' => 'required|image|mimes:png,jpeg,jpg'
        ]);

        // JIKA FILENYA ADA
        if ($request->hasFile('image')) {
            // MAKA KITA SIMPAN SEMENTARA FILE TERSEBUT KEDALAM VARIABLE FILE
            $file = $request->file('image');

            // KEMUDIAN NAMA FILENYA KITA BUAT CUSTOMER DENGAN PERPADUAN time() DAN slug() DARI NAMA PRODUK.
            // ADAPUN EXTENSIONNYA KITA GUNAKAN BAWAAN FILE TERSEBUT
            $fileName = time() . Str::slug($request->name) . '.' . $file->getClientOriginalExtension();

            // SIMPAN FILENYA KEDALAM FOLDER public/products, DAN PARAMETER KEDUA ADALAH NAMA CUSTOM UNTUK FILE TERSEBUT
            $file->storeAs('public/products', $fileName);

            // SETELAH FILE TERSEBUT DISIMPAN, KITA SIMPAN INFORMASI PRODUKNYA KEDALAM DATABASE
            Product::create([
                'name' => $request->name,
                'slug' => $request->name,
                'category_id' => $request->category_id,
                'description' => $request->description,

                // PASTIKAN MENGGUNAKAN VARIABLE [filename] YANG HANYA BERISI NAMA FILE SAJA (STRING)
                'image' => $fileName,
                'price' => $request->price,
                'weight' => $request->weight,
                'status' => $request->status
            ]);

            // JIKA SUDAH DISIMPAN MAKA REDIRECT KE LIST PRODUK
            return redirect(route('product.index'))->with(['success' => 'Produk Baru Ditambahkan!']);
        }
    }

    public function destroy($id)
    {
        // QUERY UNTUK MENGAMBIL DATA PRODUK BERDASARKAN ID
        $product = Product::find($id);

        // HAPUS FILE IMAGE DARI STORAGE PATH DIIKUTI DENGNA NAMA IMAGE YANG DIAMBIL DARI DATABASE
        File::delete(storage_path('app/public/products/' . $product->image));

        // KEMUDIAN HAPUS DATA PRODUK DARI DATABASE
        $product->delete();

        // DAN REDIRECT KE HALAMAN LIST PRODUK
        return redirect(route('product.index'))->with(['success' => 'Produck Telah Dihapus']);
    }

    public function massUploadForm()
    {
        $category = Category::orderBy('name', 'DESC')->get();
        return view('products.bulk', compact('category'));
    }

    public function massUpload(Request $request)
    {
        // VALIDASI DATA YANG DIKIRIM
        $this->validate($request, [
            'category_id' => 'required|exists:categories,id',
            // MEMASTIKAN FORMAT [file] YANG DITERIMA ADALAH XLSX
            'file' => 'required|mimes:xlsx'
        ]);

        // JIKA FILE-NYA ADA
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '-product.' . $file->getClientOriginalExtension();

            // MAKA SIMPAN FILE TERSEBUT DI [storage/app/public/uploads]
            $file->storeAs('public/uploads', $fileName);

            // membuat jadwal untuk proses file
            ProductJob::dispatch($request->category_id, $fileName);
            return redirect()->back()->with(['success' => 'Upload Produk dijadwalkan']);
        }
    }

    public function edit($id)
    {
        $product = Product::find($id);
        $category = Category::orderBy('name', 'DESC')->get();
        return view('products.edit', compact('product', 'category'));
    }

    public function update(Request $request, $id)
    {
        // validasi data yang dikirim
        $this->validate($request, [
            'name' => 'required|string|max:100',
            'description' => 'required',
            'category_id' => 'required|exist:categories,id',
            'price' => 'required|integer',
            'weight' => 'required|integer',
            'image' => 'nullable|image|mimes:png,jpeg,jpg'
        ]);

        //AMBIL DATA PRODUK YANG AKAN DIEDIT BERDASARKAN ID
        $product = Product::find($id);

        //SIMPAN SEMENTARA NAMA FILE IMAGE SAAT INI
        $fileName = $product->image;

        // JIKA ADA FILE GAMBAR YANG DIKIRIM
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . Str::slug($request->name) . '.' . $file->getClientOriginalExtension();

            // MAKA UPLOAD FILE TERSEBUT
            $file->storeAs('public/products', $fileName);

            // DAN HAPUS FILE GAMBAR YANG LAMA
            File::delete(storage_path('app/public/products/' . $product->image));
        }

        // KEMUDIAN UPDATE PRODUK TERSEBUT
        $product->update([
            'name' => $request->name,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'price' => $request->price,
            'weight' => $request->weight,
            'image' => $fileName
        ]);

        return redirect(route('product.index'))->with(['success' => 'Data Product Diperbarui!']);
    }
}
