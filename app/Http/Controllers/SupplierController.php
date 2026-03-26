<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $search    = $request->get('search');
        $suppliers = Supplier::when($search, fn($q) => $q->where(function ($q2) use ($search) {
                $q2->where('name',           'like', "%{$search}%")
                   ->orWhere('contact_person','like', "%{$search}%")
                   ->orWhere('phone',         'like', "%{$search}%")
                   ->orWhere('email',         'like', "%{$search}%");
            }))
            ->withSum('deliveries', 'amount')
            ->withSum('supplierPayments', 'amount')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers', 'search'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string|max:255',
        ]);

        Supplier::create($request->only(['name', 'contact_person', 'phone', 'email', 'address']));

        return redirect()->route('suppliers.index')->with('success', 'Supplier created successfully.');
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string|max:255',
        ]);

        $supplier->update($request->only(['name', 'contact_person', 'phone', 'email', 'address']));

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->purchaseOrders()->exists()) {
            return redirect()->route('suppliers.index')->with('error', 'Cannot delete supplier with purchase orders.');
        }

        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully.');
    }
}
