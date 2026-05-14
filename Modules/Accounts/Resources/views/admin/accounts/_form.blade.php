@if($errors->any())
    <div class="alert alert-danger mb-3">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">{{ translate('Account Code') }} <span class="text-danger">*</span></label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $account?->code) }}" placeholder="e.g. 1042" required>
    </div>
    <div class="col-md-8">
        <label class="form-label">{{ translate('Account Name') }} <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $account?->name) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ translate('Type') }} <span class="text-danger">*</span></label>
        <select name="type" class="form-control" required>
            @foreach(['asset','liability','equity','revenue','expense'] as $t)
                <option value="{{ $t }}" {{ old('type', $account?->type) === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ translate('Normal Balance') }} <span class="text-danger">*</span></label>
        <select name="normal_balance" class="form-control" required>
            <option value="debit"  {{ old('normal_balance', $account?->normal_balance) === 'debit'  ? 'selected' : '' }}>Debit</option>
            <option value="credit" {{ old('normal_balance', $account?->normal_balance) === 'credit' ? 'selected' : '' }}>Credit</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ translate('Sort Order') }}</label>
        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $account?->sort_order ?? 999) }}">
    </div>
    <div class="col-12">
        <label class="form-label">{{ translate('Parent Account') }}</label>
        <select name="parent_id" class="form-control">
            <option value="">— {{ translate('No parent') }} —</option>
            @foreach($parents as $p)
                <option value="{{ $p->id }}" {{ old('parent_id', $account?->parent_id) == $p->id ? 'selected' : '' }}>
                    {{ $p->code }} — {{ $p->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">{{ translate('Description') }}</label>
        <textarea name="description" class="form-control" rows="2">{{ old('description', $account?->description) }}</textarea>
    </div>
</div>
