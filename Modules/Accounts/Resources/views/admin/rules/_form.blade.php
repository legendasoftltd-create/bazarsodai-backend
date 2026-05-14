@if($errors->any())
    <div class="alert alert-danger mb-3">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="mb-3">
    <label class="form-label">{{ translate('Event Type') }} <span class="text-danger">*</span></label>
    <input type="text" name="event_type" class="form-control" value="{{ old('event_type', $rule?->event_type) }}"
        placeholder="e.g. order_completed_digital" required>
    <small class="text-muted">{{ translate('Unique snake_case identifier used in code when calling AccountingService::post()') }}</small>
</div>

<div class="mb-3">
    <label class="form-label">{{ translate('Description') }}</label>
    <input type="text" name="description" class="form-control" value="{{ old('description', $rule?->description) }}">
</div>

@if(isset($rule))
<div class="mb-3">
    <div class="custom-control custom-switch">
        <input type="checkbox" name="is_active" class="custom-control-input" id="is_active" value="1" {{ $rule->is_active ? 'checked' : '' }}>
        <label class="custom-control-label" for="is_active">{{ translate('Active') }}</label>
    </div>
</div>
@endif

<div class="card mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">{{ translate('Lines') }}</h6>
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-rule-line">
            <i class="tio-add mr-1"></i>{{ translate('Add Line') }}
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:40%">{{ translate('Account Code') }}</th>
                    <th style="width:25%">{{ translate('Side') }}</th>
                    <th style="width:25%">{{ translate('Amount Field') }}</th>
                    <th style="width:10%"></th>
                </tr>
            </thead>
            <tbody id="rule-lines-body">
                @php $existingLines = old('lines', $rule?->lines ?? [['account_code'=>'','side'=>'debit','amount_field'=>''], ['account_code'=>'','side'=>'credit','amount_field'=>'']]); @endphp
                @foreach($existingLines as $i => $line)
                    <tr class="rule-line-row">
                        <td>
                            <select name="lines[{{ $i }}][account_code]" class="form-control form-control-sm" required>
                                <option value="">— {{ translate('Select') }} —</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->code }}" {{ ($line['account_code'] ?? '') === $acc->code ? 'selected' : '' }}>
                                        {{ $acc->code }} — {{ $acc->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select name="lines[{{ $i }}][side]" class="form-control form-control-sm" required>
                                <option value="debit"  {{ ($line['side'] ?? '') === 'debit'  ? 'selected' : '' }}>{{ translate('Debit') }}</option>
                                <option value="credit" {{ ($line['side'] ?? '') === 'credit' ? 'selected' : '' }}>{{ translate('Credit') }}</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="lines[{{ $i }}][amount_field]" class="form-control form-control-sm"
                                value="{{ $line['amount_field'] ?? '' }}" placeholder="e.g. amount" required>
                        </td>
                        <td>
                            <button type="button" class="btn btn-xs btn-outline-danger remove-rule-line" {{ $i < 2 ? 'disabled' : '' }}>
                                <i class="tio-delete"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer py-2">
        <small class="text-muted">
            {{ translate('Amount Field: the key in $data passed to AccountingService::post(), e.g. "amount", "bonus_amount", "disbursement_amount"') }}
        </small>
    </div>
</div>

@push('script_2')
<script>
(function () {
    const accountOptions = `{!! $accounts->map(fn($a) => "<option value=\"{$a->code}\">{$a->code} — {$a->name}</option>")->implode('') !!}`;
    let idx = {{ count($existingLines) }};

    document.getElementById('add-rule-line').addEventListener('click', function () {
        const tbody = document.getElementById('rule-lines-body');
        const tr = document.createElement('tr');
        tr.className = 'rule-line-row';
        tr.innerHTML = `
            <td><select name="lines[${idx}][account_code]" class="form-control form-control-sm" required>
                <option value="">—</option>${accountOptions}</select></td>
            <td><select name="lines[${idx}][side]" class="form-control form-control-sm" required>
                <option value="debit">{{ translate('Debit') }}</option>
                <option value="credit">{{ translate('Credit') }}</option>
            </select></td>
            <td><input type="text" name="lines[${idx}][amount_field]" class="form-control form-control-sm" placeholder="amount" required></td>
            <td><button type="button" class="btn btn-xs btn-outline-danger remove-rule-line"><i class="tio-delete"></i></button></td>`;
        tbody.appendChild(tr);
        idx++;
        updateRemoveButtons();
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('.remove-rule-line')) {
            const rows = document.querySelectorAll('.rule-line-row');
            if (rows.length > 2) {
                e.target.closest('.rule-line-row').remove();
                updateRemoveButtons();
            }
        }
    });

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('.rule-line-row');
        rows.forEach(row => row.querySelector('.remove-rule-line').disabled = rows.length <= 2);
    }
})();
</script>
@endpush
