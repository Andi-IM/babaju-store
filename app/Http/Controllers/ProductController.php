<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use File;

class ProductController extends Controller
{
    public function index()
    {
        $product = Product::with(['category'])->orderBy('created_at', 'DESC');
        if (request()->q != '') {
            $product = $product->where('name', 'LIKE', '%' . \request()->q . '%');
        }

        $product = $product->paginate(10);
        return view('products.index', compact('product'));
    }

    public function create()
    {
        $category = Category::orderBy('name', 'DESC')->get();
        return view('products.create', compact('category'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:100',
            'description' => 'required',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|integer',
            'weight' => 'required|integer',
            'image' => 'required|image|mimes:png,jpeg,jpg'
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . Str::slug($request->name) . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/products', $fileName);

            $product = Product::create([
                'name' => $request->name,
                'slug' => $request->name,
                'category_id' => $request->category_id,
                'description' => $request->description,
                'image' => $fileName,
                'price' => $request->price,
                'weight' => $request->weight,
                'status' => $request->status
            ]);

            redirect(route('product.index'))->with(['success' => 'Produk Baru Ditambahkan!']);
        }
    }

    public function destroy($id)
    {
        $product = Product::find($id);
        File::delete(storage_path('app/public/products/' . $product->image));
        $product->delete();
        return redirect(route('product.index'))->with(['success' => 'Produck Telah Dihapus']);
    }
}