<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        $products = Product::with(['suppliers' => function ($q) {
                $q->orderBy('name');
            }])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('name',     'like', "%{$search}%")
                       ->orWhere('sku',      'like', "%{$search}%")
                       ->orWhere('brand',    'like', "%{$search}%")
                       ->orWhere('model',    'like', "%{$search}%")
                       ->orWhere('category', 'like', "%{$search}%")
                       ->orWhereHas('suppliers', function ($qs) use ($search) {
                           $qs->where('suppliers.name', 'like', "%{$search}%")
                              ->orWhere('product_suppliers.cost_price', 'like', "%{$search}%");
                       });
                });
            })
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return view('products.index', compact('products', 'search'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        return view('products.create', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'sku'            => 'nullable|string|max:100|unique:products,sku',
            'brand'          => 'nullable|string|max:100',
            'category'       => 'nullable|string|max:100',
            'model'          => 'nullable|string|max:100',
            'description'    => 'nullable|string',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price'  => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'minimum_stock'  => 'required|integer|min:0',
            'supplier_ids'   => 'nullable|array',
            'supplier_ids.*' => 'exists:suppliers,id',
            'supplier_costs' => 'nullable|array',
            'supplier_costs.*' => 'nullable|numeric|min:0',
        ]);

        $product = Product::create($request->only([
            'name', 'sku', 'brand', 'category', 'model', 'description',
            'purchase_price', 'selling_price', 'stock_quantity', 'minimum_stock',
        ]));

        $this->syncSuppliers($product, $request);

        return redirect()->route('products.index')
            ->with('success', 'Product added successfully.');
    }

    public function show(Product $product)
    {
        $product->load('suppliers');
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $suppliers = Supplier::orderBy('name')->get();
        $product->load('suppliers');
        return view('products.edit', compact('product', 'suppliers'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'sku'            => 'nullable|string|max:100|unique:products,sku,' . $product->id,
            'brand'          => 'nullable|string|max:100',
            'category'       => 'nullable|string|max:100',
            'model'          => 'nullable|string|max:100',
            'description'    => 'nullable|string',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price'  => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'minimum_stock'  => 'required|integer|min:0',
            'supplier_ids'   => 'nullable|array',
            'supplier_ids.*' => 'exists:suppliers,id',
            'supplier_costs' => 'nullable|array',
            'supplier_costs.*' => 'nullable|numeric|min:0',
        ]);

        $product->update($request->only([
            'name', 'sku', 'brand', 'category', 'model', 'description',
            'purchase_price', 'selling_price', 'stock_quantity', 'minimum_stock',
        ]));

        $this->syncSuppliers($product, $request);

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }

    private function syncSuppliers(Product $product, Request $request): void
    {
        $supplierIds = $request->input('supplier_ids', []);
        $costs       = $request->input('supplier_costs', []);

        if (empty($supplierIds)) {
            $product->suppliers()->detach();
            return;
        }

        $syncData = [];
        foreach ($supplierIds as $index => $supplierId) {
            $syncData[$supplierId] = ['cost_price' => (float) ($costs[$index] ?? 0)];
        }

        $product->suppliers()->sync($syncData);
    }
}
