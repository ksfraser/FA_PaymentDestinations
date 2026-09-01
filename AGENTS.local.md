<!-- Repo-specific appendix to the shared AGENTS.md. Generic conventions live in AGENTS_ARCH.md (hardlinked). -->

# AGENTS.local.md — FA_PaymentDestinations
## Module Overview
FA_PaymentDestinations is a FrontAccounting module that intercepts
`ST_SALESINVOICE` transactions via the `db_prewrite` hook. When a sales
invoice is posted, it checks the customer's `payment_terms` against a
mapping table (`0_ksf_payment_destinations`). If a mapping exists, it
rewrites `$cart->pos['pos_account']` to redirect the GL posting to the
correct bank account. It also forces `$cart->payment_terms['cash_sale'] = 1`
so FA records it as a cash transaction, suppressing the normal payment
entry form.
## Naming Conventions
| Element | Convention | Example |
|---------|-----------|---------|
| Hooks class | `hooks_ksf_FA_<ModuleName>` | `hooks_ksf_FA_PaymentDestinations` |
| Security section | `SS_ksf_FA_<ModuleName>` | `SS_ksf_FA_PaymentDestinations` (111 << 8) |
| Security area | `SA_ksf_FA_<ModuleName>` | `SA_ksf_FA_PaymentDestinations` |
| SQL tables | `0_ksf_<tablename>` | `0_ksf_payment_destinations` |
| Module code | PD | FR-PD-001-001, UC-PD-001, UAT-PD-001 |
| Config format | gzip compressed, `Key: Value` | `_init/config` |
## Key Files
| File | Purpose |
|------|---------|
| `hooks.php` | FA hooks class — db_prewrite, menu, security, install |
| `class.ksf_payment_destinations.php` | MVC controller (CRUD orchestration) |
| `class.ksf_payment_destinations_model.php` | Model — reads/writes `0_ksf_payment_destinations` |
| `class.ksf_payment_destinations_view.php` | View — UI forms and table rendering |
| `ksf_payment_destinations.php` | Entry point page (menu target) |
| `ksf_payment_destinations.inc.php` | Constants (PREFS, HELP) |
| `sql/install.sql` | CREATE TABLE DDL |
## SQL Tables
```sql
CREATE TABLE IF NOT EXISTS `0_ksf_payment_destinations` (
  `payment_term`       int(11) NOT NULL DEFAULT 0,       -- FK to payment_terms.terms_indicator
  `payment_term_name`  varchar(200) NOT NULL DEFAULT '',  -- Human-readable term name
  `bank_account`       int(11) NOT NULL DEFAULT 0,        -- FK to bank_accounts.id
  `bank_account_name`  varchar(200) NOT NULL DEFAULT '',  -- Human-readable account name
  PRIMARY KEY (`payment_term`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```
Use `0_` prefix (NOT `@TB_PREF@` or `{TB_PREF}`).
## Decoupling from ksf_FA_Square
This module is **fully decoupled** from `ksf_FA_Square`. Neither module
imports the other. The interaction is implicit via hook execution order:
- **ksf_FA_Square** `db_prewrite` fires FIRST for Square-Invoice payment
  terms (`square_invoice`, `square_invoice_email`, `square_invoice_card`)
  and sets `cash_sale = 0` to suppress auto-payment.
- **FA_PaymentDestinations** `db_prewrite` fires SECOND and only handles
  non-Square payment term redirections.
If no mapping exists for a given payment term, this module's hook returns
normally and FA proceeds with default behavior.
## Documentation Naming
All requirements live under `ProjectDocs/`. Naming conventions:
| Type | Pattern | Example |
|------|---------|---------|
| Business Requirement | `BR-PD-<SEQ>-<short-name>.md` | `BR-PD-001-payment-routing.md` |
| Functional Requirement | `FR-PD-<SEQ>-<SEQ2>-<short-name>.md` | `FR-PD-001-001-mapping-ui.md` |
| Use Case | `UC-PD-<SEQ>-<short-name>.md` | `UC-PD-001-configure-destinations.md` |
| Unit Test | `UT-PD-<SEQ>-<SEQ2>-<SEQ3>-<short-name>.md` | `UT-PD-001-001-001-add-mapping.md` |
| UAT Case | `UAT-PD-<SEQ>-<short-name>.md` | `UAT-PD-001-admin-add-mapping.md` |
