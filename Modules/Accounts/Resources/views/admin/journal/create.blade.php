@extends('layouts.admin.app')
@section('title', translate('Manual Journal Entry'))

@section('content')
<div class="content container-fluid">

    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-add nav-icon"></i></span>
            {{ translate('Manual Journal Entry') }}
        </h1>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="close" data-dismiss="alert">&times;</button></div>
    @endif

    <form method="POST" action="{{ route('admin.accounts.journal.store') }}" id="je-form">
        @csrf
        <div class="card mb-3">
            <div class="card-header py-2"><h6 class="card-title mb-0">{{ translate('Entry Info') }}</h6></div>
            <div class="card-body">
                <div class="col-md-6 px-0">
                    <label class="form-label">{{ translate('Description / Memo') }}</label>
                    <input type="text" name="description" class="form-control" value="{{ old('description') }}" placeholder="{{ translate('Optional description for this adjustment') }}">
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">{{ translate('Lines') }}</h6>
                <div>
                    <span class="mr-3">
                        {{ translate('DR') }}: <strong id="total-dr" class="text-primary">0.00</strong>
                        &nbsp;{{ translate('CR') }}: <strong id="total-cr" class="text-success">0.00</strong>
                        &nbsp;<span id="balance-badge" class="badge badge-soft-secondary">{{ translate('Enter amounts') }}</span>
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-line">
                        <i class="tio-add mr-1"></i>{{ translate('Add Line') }}
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0" id="lines-table">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:45%">{{ translate('Account') }}</th>
                            <th style="width:20%">{{ translate('Side') }}</th>
                            <th style="width:20%">{{ translate('Amount') }}</th>
                            <th style="width:15%"></th>
                        </tr>
                    </thead>
                    <tbody id="lines-body">
                        @for($i = 0; $i < 2; $i++)
                            <tr class="line-row">
                                <td>
                                    <select name="lines[{{ $i }}][account_id]" class="form-control form-control-sm" required>
                                        <option value="">— {{ translate('Select account') }} —</option>
                                        @foreach($accounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="lines[{{ $i }}][side]" class="form-control form-control-sm side-select" required>
                                        <option value="debit">{{ translate('Debit') }}</option>
                                        <option value="credit">{{ translate('Credit') }}</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="lines[{{ $i }}][amount]" class="form-control form-control-sm amount-input" step="0.01" min="0.01" placeholder="0.00" required>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-xs btn-outline-danger remove-line" {{ $i < 2 ? 'disabled' : '' }}><i class="tio-delete"></i></button>
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" id="submit-btn" disabled>
            {{ translate('Post Entry') }}
        </button>
        <a href="{{ route('admin.accounts.journal.index') }}" class="btn btn-outline-secondary ml-2">{{ translate('Cancel') }}</a>
    </form>

</div>
@endsection

@push('script_2')
<script>
(function () {
    const accountOptions = `{!! $accounts->map(fn($a) => "<option value=\"{$a->id}\">{$a->code} — {$a->name}</option>")->implode('') !!}`;
    let lineIndex = 2;

    function recalc() {
        let dr = 0, cr = 0;
        document.querySelectorAll('.line-row').forEach(row => {
            const side   = row.querySelector('.side-select').value;
            const amount = parseFloat(row.querySelector('.amount-input').value) || 0;
            if (side === 'debit')  dr += amount;
            else                   cr += amount;
        });
        document.getElementById('total-dr').textContent = dr.toFixed(2);
        document.getElementById('total-cr').textContent = cr.toFixed(2);
        const badge = document.getElementById('balance-badge');
        const submitBtn = document.getElementById('submit-btn');
        if (dr > 0 && Math.abs(dr - cr) < 0.005) {
            badge.className = 'badge badge-soft-success';
            badge.textContent = '✓ {{ translate("Balanced") }}';
            submitBtn.disabled = false;
        } else {
            badge.className = 'badge badge-soft-danger';
            badge.textContent = dr > 0 ? 'Δ ' + Math.abs(dr - cr).toFixed(2) : '{{ translate("Enter amounts") }}';
            submitBtn.disabled = true;
        }
    }

    document.getElementById('add-line').addEventListener('click', function () {
        const tbody = document.getElementById('lines-body');
        const tr = document.createElement('tr');
        tr.className = 'line-row';
        tr.innerHTML = `
            <td><select name="lines[${lineIndex}][account_id]" class="form-control form-control-sm" required>
                <option value="">— {{ translate('Select account') }} —</option>${accountOptions}</select></td>
            <td><select name="lines[${lineIndex}][side]" class="form-control form-control-sm side-select" required>
                <option value="debit">{{ translate('Debit') }}</option>
                <option value="credit">{{ translate('Credit') }}</option>
            </select></td>
            <td><input type="number" name="lines[${lineIndex}][amount]" class="form-control form-control-sm amount-input" step="0.01" min="0.01" placeholder="0.00" required></td>
            <td><button type="button" class="btn btn-xs btn-outline-danger remove-line"><i class="tio-delete"></i></button></td>`;
        tbody.appendChild(tr);
        lineIndex++;
        updateRemoveButtons();
        tr.querySelectorAll('.side-select, .amount-input').forEach(el => el.addEventListener('input', recalc));
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('.remove-line')) {
            const rows = document.querySelectorAll('.line-row');
            if (rows.length > 2) {
                e.target.closest('.line-row').remove();
                updateRemoveButtons();
                recalc();
            }
        }
    });

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('.line-row');
        rows.forEach(row => {
            row.querySelector('.remove-line').disabled = rows.length <= 2;
        });
    }

    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('side-select') || e.target.classList.contains('amount-input')) {
            recalc();
        }
    });
})();
</script>
@endpush
