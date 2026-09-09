<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\FarmerExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FarmerExpenseController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if ($user->role !== 'farmer') abort(403);

        // The expense logbook lives inside the Profit & Expense report tab.
        return redirect()->to(route('farmer.reports.profit-expense') . '#expense-logbook');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'farmer') abort(403);

        $validated = $request->validate([
            'category'    => ['required', 'in:' . implode(',', FarmerExpense::CATEGORIES)],
            'crop_id'     => ['nullable', 'exists:crops,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'amount'      => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        FarmerExpense::create([
            'user_id'      => $user->id,
            'crop_id'      => $validated['crop_id'] ?? null,
            'category'     => $validated['category'],
            'description'  => $validated['description'] ?? null,
            'amount'       => $validated['amount'],
            'expense_date' => $validated['expense_date'],
        ]);

        return redirect()->to(route('farmer.reports.profit-expense') . '#expense-logbook')
            ->with('success', 'Expense recorded.');
    }

    public function destroy(FarmerExpense $expense)
    {
        $user = Auth::user();
        if ($user->role !== 'farmer' || $expense->user_id !== $user->id) abort(403);

        $expense->delete();

        return redirect()->to(route('farmer.reports.profit-expense') . '#expense-logbook')
            ->with('success', 'Expense deleted.');
    }
}
