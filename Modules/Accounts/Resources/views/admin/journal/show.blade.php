@extends('layouts.admin.app')
@section('title', $entry->entry_number)

@section('content')
<div class="content container-fluid">

    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <span class="page-header-icon"><i class="tio-book-outlined nav-icon"></i></span>
                    {{ $entry->entry_number }}
                    <span class="badge {{ $entry->status === 'posted' ? 'badge-soft-success' : 'badge-soft-secondary' }} ml-2">
                        {{ ucfirst($entry->status) }}
                    </span>
                </h1>
            </div>
            <div class="col-sm-auto d-flex gap-2">
                @if($entry->status === 'posted')
                    <form method="POST" action="{{ route('admin.accounts.journal.reverse', $entry) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-warning"
                            onclick="return confirm('{{ translate('Create a reversing entry for') }} {{ $entry->entry_number }}?')">
                            <i class="tio-swap-horizontal mr-1"></i> {{ translate('Reverse') }}
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.accounts.journal.index') }}" class="btn btn-sm btn-outline-secondary">
                    {{ translate('Back') }}
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Meta --}}
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header py-2"><h6 class="card-title mb-0">{{ translate('Entry Details') }}</h6></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5 text-muted">{{ translate('Entry #') }}</dt>
                        <dd class="col-7 font-weight-bold">{{ $entry->entry_number }}</dd>

                        <dt class="col-5 text-muted">{{ translate('Date') }}</dt>
                        <dd class="col-7">{{ $entry->posted_at?->format('Y-m-d H:i') }}</dd>

                        <dt class="col-5 text-muted">{{ translate('Event') }}</dt>
                        <dd class="col-7"><span class="badge badge-soft-info">{{ str_replace('_', ' ', $entry->event_type) }}</span></dd>

                        @if($entry->reference_type)
                        <dt class="col-5 text-muted">{{ translate('Reference') }}</dt>
                        <dd class="col-7"><small>{{ $entry->reference_type }} #{{ $entry->reference_id }}</small></dd>
                        @endif

                        @if($entry->description)
                        <dt class="col-5 text-muted">{{ translate('Description') }}</dt>
                        <dd class="col-7">{{ $entry->description }}</dd>
                        @endif

                        @if($entry->reversal_of_id)
                        <dt class="col-5 text-muted">{{ translate('Reversal of') }}</dt>
                        <dd class="col-7">
                            <a href="{{ route('admin.accounts.journal.show', $entry->reversal_of_id) }}">
                                #{{ $entry->reversal_of_id }}
                            </a>
                        </dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        {{-- Lines --}}
        <div class="col-md-8 mb-3">
            <div class="card h-100">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">{{ translate('Journal Lines') }}</h6>
                    @php $balanced = abs($entry->lines->sum('debit') - $entry->lines->sum('credit')) < 0.01; @endphp
                    @if($balanced)
                        <span class="badge badge-soft-success"><i class="tio-checkmark-circle mr-1"></i>{{ translate('Balanced') }}</span>
                    @else
                        <span class="badge badge-soft-danger"><i class="tio-warning mr-1"></i>{{ translate('UNBALANCED') }}</span>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('Account') }}</th>
                                <th class="text-right">{{ translate('Debit') }}</th>
                                <th class="text-right">{{ translate('Credit') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entry->lines as $line)
                                <tr>
                                    <td>
                                        <span class="font-weight-bold mr-1">{{ $line->account->code }}</span>
                                        {{ $line->account->name }}
                                        @if($line->description)
                                            <small class="text-muted d-block">{{ $line->description }}</small>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ $line->debit  > 0 ? number_format($line->debit,  2) : '—' }}</td>
                                    <td class="text-right">{{ $line->credit > 0 ? number_format($line->credit, 2) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="font-weight-bold bg-light">
                            <tr>
                                <td class="text-right">{{ translate('Totals') }}</td>
                                <td class="text-right">{{ number_format($entry->lines->sum('debit'),  2) }}</td>
                                <td class="text-right">{{ number_format($entry->lines->sum('credit'), 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
