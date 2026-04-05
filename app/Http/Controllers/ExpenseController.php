<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));

        $filtered = Expense::query()->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
            $q2->where('vendor', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('reference_no', 'like', "%{$search}%")
                ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$search}%"));
        }));

        $expenses = (clone $filtered)
            ->with('category')
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $totalAmount = (float) (clone $filtered)->sum('amount');

        return view('expenses.index', compact('expenses', 'totalAmount', 'search'));
    }

    public function create()
    {
        if (! auth()->user()->hasRole(['owner', 'cashier'])) {
            abort(403, 'Only owner or cashier can record expenses.');
        }

        $categories = ExpenseCategory::orderBy('name')->get();

        return view('expenses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'expense_date' => 'required|date',
            'reference_no' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'vendor' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:1000',
        ]);

        Expense::create($request->only([
            'expense_category_id',
            'expense_date',
            'reference_no',
            'amount',
            'vendor',
            'description',
        ]));

        return redirect()->route('expenses.index')
            ->with('success', 'Expense recorded successfully.');
    }

    public function edit(Expense $expense)
    {
        if (! auth()->user()->hasRole(['owner', 'cashier'])) {
            abort(403, 'Only owner or cashier can edit expenses.');
        }

        $categories = ExpenseCategory::orderBy('name')->get();

        return view('expenses.edit', compact('expense', 'categories'));
    }

    public function update(Request $request, Expense $expense)
    {
        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'expense_date' => 'required|date',
            'reference_no' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'vendor' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:1000',
        ]);

        $expense->update($request->only([
            'expense_category_id',
            'expense_date',
            'reference_no',
            'amount',
            'vendor',
            'description',
        ]));

        return redirect()->route('expenses.index')
            ->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        if (! auth()->user()->hasRole('owner')) {
            abort(403, 'Only owner can delete expenses.');
        }

        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('success', 'Expense deleted successfully.');
    }
}
