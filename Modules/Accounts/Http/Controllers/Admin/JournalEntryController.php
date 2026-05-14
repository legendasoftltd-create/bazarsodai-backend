<?php

namespace Modules\Accounts\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Entities\Account;
use Modules\Accounts\Entities\JournalEntry;
use Modules\Accounts\Entities\JournalLine;
use Modules\Accounts\Exceptions\UnbalancedJournalException;
use Modules\Accounts\Services\AccountingService;

class JournalEntryController extends Controller
{
    public function __construct(private readonly AccountingService $svc) {}

    public function index(Request $request)
    {
        $query = JournalEntry::with('lines')
            ->latest('posted_at')
            ->latest('id');

        if ($q = $request->get('q')) {
            $query->where(function ($sq) use ($q) {
                $sq->where('entry_number', 'like', "%{$q}%")
                   ->orWhere('event_type', 'like', "%{$q}%")
                   ->orWhere('reference_type', 'like', "%{$q}%")
                   ->orWhere('reference_id', $q);
            });
        }
        if ($from = $request->get('from')) {
            $query->whereDate('posted_at', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->whereDate('posted_at', '<=', $to);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $entries = $query->paginate(30)->withQueryString();

        return view('accounts::admin.journal.index', compact('entries'));
    }

    public function show(JournalEntry $journalEntry)
    {
        $journalEntry->load('lines.account');
        return view('accounts::admin.journal.show', ['entry' => $journalEntry]);
    }

    public function create()
    {
        $accounts = Account::active()->orderBy('sort_order')->orderBy('code')->get();
        return view('accounts::admin.journal.create', compact('accounts'));
    }

    public function reverse(JournalEntry $journalEntry)
    {
        if ($journalEntry->status !== 'posted') {
            return back()->with('error', translate('Only posted entries can be reversed.'));
        }

        $reversal = $this->svc->reverse($journalEntry, 'Manual reversal by ' . (auth('admin')->user()->f_name ?? 'admin'));

        return redirect()->route('admin.accounts.journal.show', $reversal)
            ->with('success', translate('Reversal entry ') . $reversal->entry_number . translate(' created.'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'description'          => 'nullable|string|max:500',
            'lines'                => 'required|array|min:2',
            'lines.*.account_id'   => 'required|exists:accounts,id',
            'lines.*.side'         => 'required|in:debit,credit',
            'lines.*.amount'       => 'required|numeric|min:0.01',
        ]);

        $rawLines = collect($request->lines)->map(fn($l) => [
            'account_code' => Account::find($l['account_id'])->code,
            'side'         => $l['side'],
            'amount'       => (float)$l['amount'],
        ])->all();

        try {
            $entry = $this->svc->postDirect(
                'manual_adjustment',
                $rawLines,
                [
                    'description' => $request->description,
                    'created_by'  => auth('admin')->id(),
                ]
            );
        } catch (UnbalancedJournalException $e) {
            return back()->withInput()->with('error', translate('Entry is not balanced: ') . $e->getMessage());
        }

        return redirect()->route('admin.accounts.journal.show', $entry)
            ->with('success', translate('Journal entry ') . $entry->entry_number . translate(' posted.'));
    }
}
