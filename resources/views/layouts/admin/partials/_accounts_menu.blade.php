@if(\Illuminate\Support\Facades\Route::has('admin.accounts.reports.trial-balance'))

{{-- ══ Account Management Section ══════════════════════════════════════════ --}}
<li class="nav-item">
    <small class="nav-subtitle" title="{{ translate('Account Management') }}">{{ translate('Account Management') }}</small>
    <small class="tio-more-horizontal nav-subtitle-replacer"></small>
</li>

{{-- ── All Accounts ──────────────────────────────────────────────────────── --}}
<li class="navbar-vertical-aside-has-menu {{ Request::is('admin/accounts/coa*','admin/accounts/rules*') ? 'show active' : '' }}">
    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
       title="{{ translate('All Accounts') }}">
        <i class="tio-settings nav-icon"></i>
        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('All Accounts') }}</span>
    </a>
    <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
        style="display: {{ Request::is('admin/accounts/coa*','admin/accounts/rules*') ? 'block' : 'none' }}">

        <li class="nav-item {{ Request::is('admin/accounts/coa*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.accounts.coa.index') }}" title="{{ translate('Chart of Accounts') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Chart of Accounts') }}</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('admin/accounts/rules*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.accounts.rules.index') }}" title="{{ translate('Accounting Rules') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Accounting Rules') }}</span>
            </a>
        </li>

    </ul>
</li>

{{-- ── Journal Entry ────────────────────────────────────────────────────── --}}
<li class="navbar-vertical-aside-has-menu {{ Request::is('admin/accounts/journal*') ? 'show active' : '' }}">
    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
       title="{{ translate('Journal Entry') }}">
        <i class="tio-book-outlined nav-icon"></i>
        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('Journal Entry') }}</span>
    </a>
    <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
        style="display: {{ Request::is('admin/accounts/journal*') ? 'block' : 'none' }}">

        <li class="nav-item {{ Request::is('admin/accounts/journal') && !Request::is('admin/accounts/journal/create') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.accounts.journal.index') }}" title="{{ translate('All Entries') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('All Entries') }}</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('admin/accounts/journal/create') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.accounts.journal.create') }}" title="{{ translate('New Entry') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('New Entry') }}</span>
            </a>
        </li>

    </ul>
</li>

{{-- ── Reports ───────────────────────────────────────────────────────────── --}}
<li class="navbar-vertical-aside-has-menu {{ Request::is('admin/accounts/reports*') ? 'show active' : '' }}">
    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
       title="{{ translate('Accounts Reports') }}">
        <i class="tio-chart-bar-1 nav-icon"></i>
        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('Accounts Reports') }}</span>
    </a>
    <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
        style="display: {{ Request::is('admin/accounts/reports*') ? 'block' : 'none' }}">

        <li class="nav-item {{ Request::is('admin/accounts/reports/trial-balance*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.accounts.reports.trial-balance') }}" title="{{ translate('Trial Balance') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Trial Balance') }}</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('admin/accounts/reports/general-ledger*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.accounts.reports.general-ledger') }}" title="{{ translate('General Ledger') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('General Ledger') }}</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('admin/accounts/reports/profit-loss*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.accounts.reports.profit-loss') }}" title="{{ translate('Profit & Loss') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Profit & Loss') }}</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('admin/accounts/reports/balance-sheet*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.accounts.reports.balance-sheet') }}" title="{{ translate('Balance Sheet') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Balance Sheet') }}</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('admin/accounts/reports/tax-report*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.accounts.reports.tax-report') }}" title="{{ translate('Tax Report') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Tax Report') }}</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('admin/accounts/reports/cod-reconciliation*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.accounts.reports.cod-reconciliation') }}" title="{{ translate('COD Reconciliation') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('COD Reconciliation') }}</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('admin/accounts/reports/gateway-reconciliation*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.accounts.reports.gateway-reconciliation') }}" title="{{ translate('Gateway Reconciliation') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Gateway Reconciliation') }}</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('admin/accounts/reports/store-statement*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.accounts.reports.store-statement') }}" title="{{ translate('Store Statement') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('Store Statement') }}</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('admin/accounts/reports/dm-statement*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.accounts.reports.dm-statement') }}" title="{{ translate('DM Statement') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ translate('DM Statement') }}</span>
            </a>
        </li>

    </ul>
</li>

@endif
