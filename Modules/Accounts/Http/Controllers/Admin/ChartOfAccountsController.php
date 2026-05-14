<?php

namespace Modules\Accounts\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounts\Entities\Account;

class ChartOfAccountsController extends Controller
{
    public function index()
    {
        $accounts = Account::with('parent')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->groupBy('type');

        return view('accounts::admin.accounts.index', compact('accounts'));
    }

    public function create()
    {
        $parents = Account::active()->orderBy('sort_order')->orderBy('code')->get();
        return view('accounts::admin.accounts.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'           => 'required|string|max:20|unique:accounts,code',
            'name'           => 'required|string|max:191',
            'type'           => 'required|in:asset,liability,equity,revenue,expense',
            'normal_balance' => 'required|in:debit,credit',
            'parent_id'      => 'nullable|exists:accounts,id',
            'sort_order'     => 'nullable|integer',
            'description'    => 'nullable|string|max:500',
        ]);

        Account::create([
            'code'           => $request->code,
            'name'           => $request->name,
            'type'           => $request->type,
            'normal_balance' => $request->normal_balance,
            'parent_id'      => $request->parent_id ?: null,
            'sort_order'     => $request->sort_order ?? 999,
            'description'    => $request->description,
            'is_system'      => false,
            'is_active'      => true,
        ]);

        return redirect()->route('admin.accounts.coa.index')->with('success', translate('Account created.'));
    }

    public function edit(Account $account)
    {
        $parents = Account::active()->where('id', '!=', $account->id)->orderBy('sort_order')->orderBy('code')->get();
        return view('accounts::admin.accounts.edit', compact('account', 'parents'));
    }

    public function update(Request $request, Account $account)
    {
        $request->validate([
            'code'           => 'required|string|max:20|unique:accounts,code,' . $account->id,
            'name'           => 'required|string|max:191',
            'type'           => 'required|in:asset,liability,equity,revenue,expense',
            'normal_balance' => 'required|in:debit,credit',
            'parent_id'      => 'nullable|exists:accounts,id',
            'sort_order'     => 'nullable|integer',
            'description'    => 'nullable|string|max:500',
        ]);

        $account->update([
            'code'           => $request->code,
            'name'           => $request->name,
            'type'           => $request->type,
            'normal_balance' => $request->normal_balance,
            'parent_id'      => $request->parent_id ?: null,
            'sort_order'     => $request->sort_order ?? $account->sort_order,
            'description'    => $request->description,
        ]);

        return redirect()->route('admin.accounts.coa.index')->with('success', translate('Account updated.'));
    }

    public function toggleActive(Account $account)
    {
        if ($account->is_system) {
            return back()->with('error', translate('System accounts cannot be deactivated.'));
        }
        $account->update(['is_active' => !$account->is_active]);
        return back()->with('success', translate($account->is_active ? 'Account activated.' : 'Account deactivated.'));
    }
}
