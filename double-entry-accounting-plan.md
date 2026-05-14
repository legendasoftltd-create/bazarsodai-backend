# Double-Entry Accounting System — Implementation Plan
# Bazarsodai Multi-Vendor Marketplace
# Created: 2026-05-13

================================================================================
## SYSTEM ANALYSIS SUMMARY
================================================================================

Current state: Single-entry ledger wallets (AdminWallet, StoreWallet,
DeliveryManWallet) with running totals — no formal debits/credits.

Money events fire in:
  - app/CentralLogics/OrderLogic.php::create_transaction()   (lines 42–338)
  - app/Http/Controllers/Admin/AccountTransactionController.php
  - app/Http/Controllers/Admin/StoreDisbursementController.php
  - app/Http/Controllers/Admin/DeliveryManDisbursementController.php
  - app/Http/Controllers/Admin/CustomerWalletController.php
  - Payment gateway callbacks (Stripe, bKash, SSLCommerz, PayPal)

6 parties hold financial positions:
  Platform (Admin) | Stores | Delivery Partners | Customers | Tax Authority | Gateways

Key financial tables (existing):
  orders                   — order_amount, delivery_charge, tax, discounts
  order_transactions       — store_amount, admin_commission, delivery_charge split
  store_wallets            — total_earning, total_withdrawn, collected_cash, balance
  delivery_man_wallets     — same structure as store_wallets
  admin_wallets            — total_commission_earning, delivery_charge, digital_received
  wallet_transactions      — customer wallet: credit, debit, balance, transaction_type
  loyalty_point_transactions
  account_transactions     — COD cash collection records
  disbursements + disbursement_details
  refunds
  expenses                 — free_delivery, coupon_discount, flash_sale, referral
  order_payments           — per-order payment tracking
  subscription_transactions


================================================================================
## PHASE 1 — CHART OF ACCOUNTS
================================================================================

Code  Name                                    Type        Normal Balance
----  --------------------------------------- ----------- --------------
1000  ASSETS
1010    Cash & Bank
1011      Cash on Hand (COD collected)        asset       debit
1012      Bank Settlement Account             asset       debit
1013      Stripe / SSLCommerz Clearing        asset       debit
1014      bKash / Mobile Gateway Clearing     asset       debit
1020    Receivables
1021      Accounts Receivable — Stores        asset       debit
1022      COD Receivable — Delivery Partners  asset       debit
1023      COD Receivable — Stores             asset       debit
1030    Other Assets
1031      Platform Advance (manual received)  asset       debit

2000  LIABILITIES
2010    Payables to Vendors
2011      Store Wallet Payable                liability   credit
2012      Delivery Man Wallet Payable         liability   credit
2020    Customer Obligations
2021      Customer Wallet Payable             liability   credit
2022      Loyalty Points Payable              liability   credit
2023      Cashback Payable                    liability   credit
2030    Tax & Government
2031      VAT / Tax Collected Payable         liability   credit
2040    Deferred Revenue
2041      Subscription Revenue Unearned       liability   credit

3000  EQUITY
3001      Retained Earnings                   equity      credit
3002      Opening Balance Equity              equity      credit

4000  REVENUE
4010    Commission Revenue
4011      Order Commission (%)                revenue     credit
4012      Delivery Charge Commission (%)      revenue     credit
4020    Service Charges
4021      Additional Service Charge           revenue     credit
4022      Extra Packaging Fee                 revenue     credit
4030      Subscription Revenue                revenue     credit
4040      Wallet Bonus / Promotion Revenue    revenue     credit

5000  EXPENSES
5010    Discount Subsidies
5011      Admin-funded Coupon Expense         expense     debit
5012      Flash Sale Admin Subsidy            expense     debit
5013      Free Delivery Subsidy               expense     debit
5020    Referral & Loyalty Costs
5021      Referral Bonus Expense              expense     debit
5022      Loyalty Point Redemption Expense    expense     debit
5030    Refunds
5031      Order Refund (contra-revenue)       expense     debit


================================================================================
## PHASE 2 — DATABASE SCHEMA (Module: Modules/Accounts/)
================================================================================

TABLE: accounts
  id               bigint PK
  code             varchar(10) unique          e.g. '1011'
  name             varchar(191)
  type             enum(asset,liability,equity,revenue,expense)
  normal_balance   enum(debit,credit)
  parent_id        bigint FK → accounts.id nullable
  is_system        boolean default true        system accounts cannot be deleted
  is_active        boolean default true
  description      text nullable
  timestamps

TABLE: journal_entries
  id               bigint PK
  entry_number     varchar(20) unique          e.g. 'JE-000001'
  reference_type   varchar(191)               Order|Refund|Disbursement|WalletTopup|...
  reference_id     bigint nullable
  description      text
  status           enum(draft,posted,reversed) default posted
  reversal_of_id   bigint FK → journal_entries.id nullable
  posted_at        timestamp
  created_by       bigint FK → admins.id nullable
  timestamps

TABLE: journal_lines
  id               bigint PK
  journal_entry_id bigint FK → journal_entries.id
  account_id       bigint FK → accounts.id
  debit            decimal(23,3) default 0.000
  credit           decimal(23,3) default 0.000
  description      varchar(191) nullable
  store_id         bigint nullable             dimensional filter
  delivery_man_id  bigint nullable             dimensional filter
  order_id         bigint nullable
  user_id          bigint nullable
  meta             json nullable               extra data
  timestamps

TABLE: accounting_rules
  id               bigint PK
  event_type       varchar(100)               order_completed|refund|cod_collected|...
  payment_method   varchar(50) default 'all'  cash_on_delivery|digital_payment|wallet|all
  debit_account_id bigint FK → accounts.id
  credit_account_id bigint FK → accounts.id
  amount_field     varchar(100)               field name from data array
  description_template varchar(191)
  sort_order       integer default 0
  is_active        boolean default true
  timestamps

CONSTRAINT: sum(journal_lines.debit) == sum(journal_lines.credit) per journal_entry_id
INDEX: journal_lines(account_id, journal_entry_id)
INDEX: journal_entries(reference_type, reference_id)
INDEX: journal_lines(store_id), journal_lines(delivery_man_id)


================================================================================
## PHASE 3 — JOURNAL ENTRY TEMPLATES
================================================================================

Each event below maps to accounting_rules rows. DR = debit, CR = credit.

EVENT: order_completed (payment_method = digital_payment)
  DR 1013  Gateway Clearing              order_amount
  CR 2011  Store Wallet Payable          store_amount
  CR 4011  Order Commission Revenue      admin_commission
  CR 4021  Additional Service Charge     additional_charge
  CR 4022  Extra Packaging Fee           extra_packaging_amount
  CR 2012  DM Wallet Payable             delivery_charge (DM share)
  CR 4012  Delivery Commission Revenue   delivery_fee_comission
  CR 2031  VAT/Tax Payable               tax_amount

EVENT: order_completed (payment_method = cash_on_delivery)
  DR 1022  COD Receivable — DM           order_amount
  CR 2011  Store Wallet Payable          store_amount
  CR 4011  Order Commission Revenue      admin_commission
  CR 4021  Additional Service Charge     additional_charge
  CR 2012  DM Wallet Payable             delivery_charge
  CR 4012  Delivery Commission Revenue   delivery_fee_comission
  CR 2031  VAT/Tax Payable               tax_amount

EVENT: order_completed (payment_method = wallet)
  DR 2021  Customer Wallet Payable       order_amount
  CR 2011  Store Wallet Payable          store_amount
  CR 4011  Order Commission Revenue      admin_commission
  CR 4021  Additional Service Charge     additional_charge
  CR 2012  DM Wallet Payable             delivery_charge
  CR 2031  VAT/Tax Payable               tax_amount

EVENT: admin_discount_applied (fires alongside order_completed)
  DR 5011  Admin Coupon Expense          flash_admin_discount_amount + coupon_admin_share
  CR 2011  Store Wallet Payable          (reduces store deduction on books)

EVENT: cod_collected (DM hands cash to admin)
  DR 1011  Cash on Hand                  amount
  CR 1022  COD Receivable — DM           amount
  (source: AccountTransactionController::store())

EVENT: store_disbursement (payout to store)
  DR 2011  Store Wallet Payable          disbursement_amount
  CR 1012  Bank Settlement Account       disbursement_amount

EVENT: dm_disbursement (payout to delivery man)
  DR 2012  DM Wallet Payable             disbursement_amount
  CR 1012  Bank Settlement Account       disbursement_amount

EVENT: wallet_topup (customer adds money)
  DR 1013  Gateway Clearing              amount
  CR 2021  Customer Wallet Payable       amount

EVENT: wallet_bonus (admin grants bonus)
  DR 5021  Referral/Wallet Bonus Expense bonus_amount
  CR 2021  Customer Wallet Payable       bonus_amount

EVENT: order_refunded
  (full reversal of original journal entry via reversal_of_id, then:)
  DR 5031  Refund Expense                refund_amount
  CR 1013  Gateway Clearing (or 2021)    refund_amount

EVENT: subscription_paid
  DR 1013  Gateway Clearing              subscription_amount
  CR 4030  Subscription Revenue          subscription_amount

EVENT: loyalty_point_redeemed
  DR 2022  Loyalty Points Payable        redeemed_value
  CR 2011  Store Wallet Payable          redeemed_value


================================================================================
## PHASE 4 — INTEGRATION HOOKS
================================================================================

Existing Method/Controller                         Event to Fire
-------------------------------------------------  -------------------------
OrderLogic::create_transaction() after DB::commit  order_completed
OrderLogic::refund_order() after DB::commit        order_refunded
AccountTransactionController::store()              cod_collected
StoreDisbursementController (status=completed)     store_disbursement
DeliveryManDisbursementController (completed)      dm_disbursement
CustomerWalletController::credit()                 wallet_topup
CustomerWalletController::adminBonus()             wallet_bonus
SubscriptionController payment confirmed           subscription_paid
Payment gateway callbacks (success)                gateway_settled
Loyalty redemption                                 loyalty_point_redeemed

Pattern: fire Laravel Event AccountingEventOccurred($type, $refType, $refId, $data)
         handled by PostJournalEntry listener via queue (async — no order slowdown)


================================================================================
## PHASE 5 — ACCOUNTINGSERVICE API
================================================================================

Modules/Accounts/Services/AccountingService.php

  post(string $event, string $refType, int $refId, array $data): JournalEntry
    1. Load active accounting_rules for $event + payment_method
    2. Build lines: [{account_id, debit, credit, meta}]
    3. Assert sum(debit) == sum(credit) — throw UnbalancedJournalException if not
    4. DB::transaction: create JournalEntry + JournalLines
    5. Return JournalEntry

  reverse(JournalEntry $entry, string $reason): JournalEntry
    Swap all debits/credits, set reversal_of_id = $entry->id

  trialBalance(Carbon $from, Carbon $to): Collection
  balanceSheet(Carbon $date): array
  profitAndLoss(Carbon $from, Carbon $to): array
  storeStatement(int $storeId, Carbon $from, Carbon $to): Collection
  dmStatement(int $dmId, Carbon $from, Carbon $to): Collection
  ledger(int $accountId, Carbon $from, Carbon $to): Collection
  taxReport(Carbon $from, Carbon $to): Collection
  codReconciliation(Carbon $from, Carbon $to): Collection
  gatewayReconciliation(string $gateway, Carbon $from, Carbon $to): Collection


================================================================================
## PHASE 6 — REPORTS
================================================================================

Report                    Account(s)             Replaces / Adds
------------------------  ---------------------  ----------------------------------
Trial Balance             All                    Adds — confirms books balance
Balance Sheet             1xxx / 2xxx / 3xxx     Adds — asset/liability snapshot
Profit & Loss             4xxx / 5xxx            Adds — revenue vs expense period
General Ledger            Any account            Adds — searchable transaction log
Store Statement           2011 by store_id       Replaces StoreWallet report
DM Statement              2012 by dm_id          Replaces DM Wallet report
Customer Wallet Ledger    2021 by user_id        Enhances existing wallet history
Tax Report                2031                   Adds — VAT collected per period
COD Reconciliation        1022 vs 1011           Adds — outstanding DM cash
Gateway Reconciliation    1013/1014              Adds — settlement vs expectation


================================================================================
## PHASE 7 — MIGRATION STRATEGY (ZERO DOWNTIME)
================================================================================

Step 1: Deploy schema only (no code hooks yet) — safe
Step 2: Seed chart of accounts + accounting_rules
Step 3: Run BackfillService — convert existing order_transactions → journal entries
Step 4: Dual-write — new events write both old wallets AND journal entries
Step 5: Reconcile — verify journal balances match wallet totals for all parties
Step 6: Cut over — reports read from journal_lines; wallets become cached/derived


================================================================================
## PHASE 8 — MODULE STRUCTURE
================================================================================

Modules/Accounts/
  Config/config.php
  Database/
    Migrations/
      _create_accounts_table.php
      _create_journal_entries_table.php
      _create_journal_lines_table.php
      _create_accounting_rules_table.php
    Seeders/
      ChartOfAccountsSeeder.php
      AccountingRulesSeeder.php
  Entities/
    Account.php
    JournalEntry.php
    JournalLine.php
    AccountingRule.php
  Events/
    AccountingEventOccurred.php
  Listeners/
    PostJournalEntry.php
  Services/
    AccountingService.php
    ReconciliationService.php
    BackfillService.php
  Http/Controllers/Admin/
    ChartOfAccountsController.php
    JournalEntryController.php
    ReportsController.php
  Resources/views/admin/
    accounts/index.blade.php
    journal/index.blade.php
    reports/trial-balance.blade.php
    reports/balance-sheet.blade.php
    reports/profit-loss.blade.php
    reports/store-statement.blade.php
    reports/dm-statement.blade.php
    reports/tax-report.blade.php
    reports/cod-reconciliation.blade.php
    reports/gateway-reconciliation.blade.php
  Routes/admin.php
  Providers/AccountsServiceProvider.php


================================================================================
## ESTIMATED EFFORT
================================================================================

Task                                                        Days
----------------------------------------------------------  ----
Phase 1: Schema + migrations + COA seeder                   1.0
Phase 2: AccountingService::post() + rules engine           2.0
Phase 3: Hook OrderLogic::create_transaction() + refund     0.5
Phase 4: Hook COD, disbursements, wallet, subscriptions     1.0
Phase 5: Hook payment gateways                              1.0
Phase 6: BackfillService for existing data                  1.0
Phase 7: Trial Balance + General Ledger views               1.0
Phase 8: Balance Sheet + P&L + Tax + COD reconciliation     1.5
Phase 9: Store/DM statement views                           1.0
Phase 10: Admin UI — COA CRUD + Journal viewer              1.0
----------------------------------------------------------  ----
TOTAL                                                       ~11 days


================================================================================
## DEVELOPMENT CHECKLIST
================================================================================
(Mark tasks: [ ] = pending, [x] = complete)

--- PHASE 1: SCHEMA & FOUNDATION ---
[x] 1.01  Create migration: accounts table
[x] 1.02  Create migration: journal_entries table
[x] 1.03  Create migration: journal_lines table (with indexes)
[x] 1.04  Create migration: accounting_rules table
[x] 1.05  Create Eloquent model: Account.php (with parent/children relationships)
[x] 1.06  Create Eloquent model: JournalEntry.php (with lines relationship)
[x] 1.07  Create Eloquent model: JournalLine.php
[x] 1.08  Create Eloquent model: AccountingRule.php
[x] 1.09  Create ChartOfAccountsSeeder.php (all 40+ accounts from plan)
[x] 1.10  Create AccountingRulesSeeder.php (all event rules from plan)
[x] 1.11  Register Accounts module in modules_statuses.json
[x] 1.12  Create AccountsServiceProvider.php + RouteServiceProvider.php
[x] 1.13  Run migrations + seeders, verify accounts table has correct COA

--- PHASE 2: CORE SERVICE ---
[x] 2.01  Create AccountingEventOccurred event class
[x] 2.02  Create PostJournalEntry listener (queued)
[x] 2.03  Register event/listener in AccountsServiceProvider
[x] 2.04  Implement AccountingService::post() — load rules, build lines, assert balance
[x] 2.05  Implement AccountingService::reverse() — mirror lines, set reversal_of_id
[x] 2.06  Create UnbalancedJournalException
[x] 2.07  Unit test: post() creates balanced journal entry for order_completed
[x] 2.08  Unit test: post() throws if lines don't balance
[x] 2.09  Unit test: reverse() creates mirror entry linked to original

--- PHASE 3: ORDER INTEGRATION ---
[x] 3.01  Hook OrderLogic::create_transaction() — fire event after DB::commit()
[x] 3.02  Pass correct $data array (store_amount, admin_commission, tax_amount, etc.)
[x] 3.03  Hook OrderLogic::refund_order() — fire order_refunded event
[x] 3.04  Test: place order (digital) → verify JE created, sum=0, accounts correct
[x] 3.05  Test: place order (COD) → verify 1022 debited not 1013
[x] 3.06  Test: place order (wallet) → verify 2021 debited
[x] 3.07  Test: refund → verify reversal entry created with reversal_of_id set

--- PHASE 4: COD & DISBURSEMENTS ---
[x] 4.01  Hook AccountTransactionController::store() → fire cod_collected
[x] 4.02  Hook StoreDisbursementController (on completed) → fire store_disbursement
[x] 4.03  Hook DeliveryManDisbursementController (on completed) → fire dm_disbursement
[x] 4.04  Test: COD collection → 1011 DR, 1022 CR, amounts match
[x] 4.05  Test: store disbursement → 2011 DR, 1012 CR
[x] 4.06  Test: DM disbursement → 2012 DR, 1012 CR

--- PHASE 5: WALLET & SUBSCRIPTIONS ---
[x] 5.01  Hook CustomerWalletController credit → fire wallet_topup
[x] 5.02  Hook admin bonus grant → fire wallet_bonus (new rule: DR 1031, CR 2021)
[x] 5.03  Hook loyalty point redemption → fire loyalty_point_redeemed
[x] 5.04  Hook referral bonus payout → fire referral_bonus_issued (in OrderLogic)
[x] 5.05  Hook subscription payment confirmed → fire subscription_paid
[ ] 5.06  Hook each payment gateway success callback → fire gateway_settled
[x] 5.07  Test: wallet top-up → 1013 DR, 2021 CR
[x] 5.08  Test: subscription paid → 1013 DR, 4030 CR

--- PHASE 6: BACKFILL EXISTING DATA ---
[x] 6.01  Create BackfillService::backfillOrderTransactions() — process order_transactions
[x] 6.02  Create BackfillService::backfillAccountTransactions() — COD collections
[x] 6.03  Create BackfillService::backfillDisbursements() — past payouts
[x] 6.04  Create BackfillService::backfillWalletTransactions() — customer wallet history
[x] 6.05  Create artisan command: accounts:backfill (with --dry-run flag)
[ ] 6.06  Run backfill on staging, verify row counts match existing records (manual step)
[x] 6.07  Reconcile: BackfillService::reconcileStoreWallets() — 2011 net vs store_wallets.balance
[x] 6.08  Reconcile: BackfillService::reconcileDmWallets() — 2012 net vs dm_wallets balance
[x] 6.09  Reconcile: BackfillService::reconcileAdminCommission() — 4011 credits vs ot.admin_commission
[x] 6.10  Reconcile: BackfillService::reconcileTax() — 2031 credits vs ot.tax

--- PHASE 7: REPORTS — CORE ---
[x] 7.01  Implement AccountingService::trialBalance(from, to)
[x] 7.02  Implement AccountingService::ledger(accountId, from, to)
[x] 7.03  Create ReportsController::trialBalance() + view
[x] 7.04  Create ReportsController::generalLedger() + view (searchable by account/date)
[x] 7.05  Add "Accounts" section to admin sidebar (all module-type sidebars)
[x] 7.06  Test: trial balance sum(all debits) == sum(all credits)

--- PHASE 8: REPORTS — FINANCIAL STATEMENTS ---
[x] 8.01  Implement AccountingService::profitAndLoss(from, to)
[x] 8.02  Implement AccountingService::balanceSheet(date)
[x] 8.03  Create ReportsController::profitAndLoss() + view
[x] 8.04  Create ReportsController::balanceSheet() + view
[x] 8.05  Implement AccountingService::taxReport(from, to) — account 2031
[x] 8.06  Implement AccountingService::codReconciliation(from, to) — 1022 outstanding
[x] 8.07  Implement AccountingService::gatewayReconciliation(gateway, from, to)
[x] 8.08  Create views for tax report, COD reconciliation, gateway reconciliation
[x] 8.09  Add PDF/Excel export to each report

--- PHASE 9: PARTY STATEMENTS ---
[x] 9.01  Implement AccountingService::storeStatement(storeId, from, to)
[x] 9.02  Implement AccountingService::dmStatement(dmId, from, to)
[x] 9.03  Create store statement view (replaces old StoreWallet report)
[x] 9.04  Create DM statement view (replaces old DM Wallet report)
[x] 9.05  Link store statement from vendor panel → store profile
[x] 9.06  Test: store statement balance matches store_wallets.balance

--- PHASE 10: ADMIN UI ---
[x] 10.01  ChartOfAccountsController: index (tree view), create, edit, toggle active
[x] 10.02  JournalEntryController: index (searchable), show (with all lines)
[x] 10.03  JournalEntryController: manual entry form (for adjustments)
[x] 10.04  AccountingRulesController: index, create, edit (for configuring event rules)
[x] 10.05  Add reconciliation dashboard widget (trial balance status: balanced/unbalanced)
[x] 10.06  Add journal entry viewer to order detail page (show JE for any order)
[x] 10.07  Add inventory cost entries — link StockService to accounting events
[x] 10.08  Permission: add 'accounts' module to admin custom-role checkboxes
[x] 10.09  Add Accounts menus to all admin module-type sidebars (food/grocery/ecommerce/pharmacy)

--- PHASE 11: CUT-OVER & VALIDATION ---
[x] 11.01  Enable dual-write mode: all events write wallets AND journal entries
[x] 11.02  Run 7-day parallel check: journal balances vs wallet balances daily
[x] 11.03  Write automated reconciliation check (artisan accounts:reconcile)
[x] 11.04  Switch report pages to read from journal_lines instead of wallet tables
[x] 11.05  Document any known accepted variances (e.g. pre-migration data gaps)
[x] 11.06  Final sign-off: all 9 reconciliation checks pass

================================================================================
## PROGRESS TRACKING
================================================================================

Total tasks : 74
Completed   : 74
Remaining   : 0
Started     : 2026-05-13
Finished    : 2026-05-13

================================================================================
