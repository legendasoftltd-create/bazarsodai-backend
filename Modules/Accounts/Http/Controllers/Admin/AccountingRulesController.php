<?php

namespace Modules\Accounts\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounts\Entities\Account;
use Modules\Accounts\Entities\AccountingRule;

class AccountingRulesController extends Controller
{
    public function index()
    {
        $rules = AccountingRule::orderBy('is_active', 'desc')->orderBy('event_type')->get();
        return view('accounts::admin.rules.index', compact('rules'));
    }

    public function create()
    {
        $accounts = Account::active()->orderBy('sort_order')->orderBy('code')->get();
        return view('accounts::admin.rules.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        AccountingRule::create([
            'event_type'  => $data['event_type'],
            'description' => $data['description'],
            'lines'       => $data['lines'],
            'is_active'   => true,
        ]);

        return redirect()->route('admin.accounts.rules.index')->with('success', translate('Rule created.'));
    }

    public function edit(AccountingRule $rule)
    {
        $accounts = Account::active()->orderBy('sort_order')->orderBy('code')->get();
        return view('accounts::admin.rules.edit', compact('rule', 'accounts'));
    }

    public function update(Request $request, AccountingRule $rule)
    {
        $data = $this->validated($request, $rule);

        $rule->update([
            'event_type'  => $data['event_type'],
            'description' => $data['description'],
            'lines'       => $data['lines'],
            'is_active'   => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.accounts.rules.index')->with('success', translate('Rule updated.'));
    }

    private function validated(Request $request, ?AccountingRule $rule = null): array
    {
        $request->validate([
            'event_type'              => 'required|string|max:100|unique:accounting_rules,event_type' . ($rule ? ",{$rule->id}" : ''),
            'description'             => 'nullable|string|max:500',
            'lines'                   => 'required|array|min:2',
            'lines.*.account_code'    => 'required|exists:accounts,code',
            'lines.*.side'            => 'required|in:debit,credit',
            'lines.*.amount_field'    => 'required|string|max:60',
        ]);

        return [
            'event_type'  => $request->event_type,
            'description' => $request->description,
            'lines'       => collect($request->lines)->map(fn($l) => [
                'account_code' => $l['account_code'],
                'side'         => $l['side'],
                'amount_field' => $l['amount_field'],
            ])->values()->all(),
        ];
    }
}
