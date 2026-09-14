# Requirements Specification — FA_PaymentDestinations

> Module code: **PD**
> Version: 2.0.0
> Platform: PHP 7.3 / FrontAccounting 2.4.19

---

## 1. Business Context

FrontAccounting (FA) posts sales invoice payments to a single default GL
account (the "POS account" configured on the POS/cash register). This is
insufficient when a business accepts multiple payment types — credit cards,
e-transfer, cheques, Square Invoices — each of which must post to a
distinct GL account for reconciliation.

FA_PaymentDestinations solves this by intercepting the `ST_SALESINVOICE`
transaction before it is written to the database, looking up a
configurable mapping from payment term to bank account, and rewriting the
GL destination on the fly.

---

## 2. Functional Requirements

### FR-01: Payment Term Mapping UI

**Title:** Admin page to CRUD payment destination mappings

**Description:** Provide an FA menu page (under Banking & General Ledger)
where an administrator can create, read, update, and delete mappings
between FA payment terms and destination bank accounts.

**Acceptance Criteria:**

1. Menu item "Payment Destinations" appears under GL &rarr; Banking.
2. Page displays a table of all existing mappings (payment term, bank
   account, and human-readable names).
3. Admin can add a new mapping by selecting a payment term and bank
   account from FA combo boxes.
4. Admin can delete an existing mapping via the Delete button.
5. Each payment term can have at most one mapping (PK = `payment_term`).
6. Page requires `SA_ksf_FA_PaymentDestinations` access permission.

**Code references:**

- `ksf_payment_destinations.php` — entry point, page security
- `class.ksf_payment_destinations.php` — controller CRUD methods
- `class.ksf_payment_destinations_view.php` — UI forms
- `class.ksf_payment_destinations_model.php` — data access

---

### FR-02: db_prewrite Hook — GL Account Redirection

**Title:** Intercept ST_SALESINVOICE and redirect GL posting

**Description:** The module's `db_prewrite` hook intercepts sales invoice
transactions before write, looks up the payment term in
`0_ksf_payment_destinations`, and if a mapping exists:

1. Rewrites `$cart->pos['pos_account']` to the mapped bank account ID.
2. Forces `$cart->payment_terms['cash_sale'] = 1` so FA auto-generates a
   customer payment alongside the invoice.

If no mapping exists, the hook returns without modification and FA
proceeds normally.

**Acceptance Criteria:**

1. Hook fires only for `ST_SALESINVOICE` transactions.
2. If `terms_indicator` is not set on `$cart->payment_terms`, hook returns
   without action.
3. If a mapping exists for the payment term, `pos_account` is rewritten.
4. If no mapping exists (model throws `KSF_FIELD_NOT_SET`), hook returns
   without error and FA default behavior applies.
5. `cash_sale` is forced to `1` whenever a mapping is found.
6. Square-Invoice destinations (`square_invoice*`) are NOT handled by
   this module — they are intercepted by ksf_FA_Square's own `db_prewrite`
   which runs first.

**Code references:**

- `hooks.php:132-164` — `db_prewrite` method

---

### FR-03: Module Activation

**Title:** Install SQL, menu registration, and security setup

**Description:** On module activation, create the
`0_ksf_payment_destinations` table and register the module menu item and
security areas.

**Acceptance Criteria:**

1. `activate_extension()` runs `sql/install.sql` to create the table.
2. `install_options()` registers "Payment Destinations" under GL and
   Orders applications (level 2).
3. `install_access()` defines `SS_ksf_FA_PaymentDestinations` (111 << 8)
   and two security areas: full access and view-only.
4. Table uses `0_` prefix, InnoDB engine, utf8mb4 charset.

**Code references:**

- `hooks.php:74-116` — `install_options`, `install_access`,
  `activate_extension`
- `sql/install.sql` — DDL

---

### FR-04: Inter-Module Communication

**Title:** Four standard hook methods for module discovery

**Description:** Implement the four standard inter-module communication
methods so other modules can query this module's capabilities and
constants.

**Acceptance Criteria:**

1. `getModuleConstants()` returns `KSF_PAYMENT_DESTINATIONS_MODULE`.
2. `getModuleCapabilities()` returns `payment_redirect` capability with
   `methods: ['db_prewrite']`.
3. `hasCapability()` returns true for `payment_redirect`, false otherwise.
4. `respondToCapabilityRequest()` dispatches to the appropriate method
   based on request type (`capabilities`, `constants`, `has:<name>`).

**Code references:**

- `hooks.php:26-72` — four standard methods

---

### FR-05: Square-Invoice Term Decoupling

**Title:** Do not handle Square-Invoice payment terms

**Description:** The module SHALL NOT intercept or handle Square-Invoice
payment terms (`square_invoice`, `square_invoice_email`,
`square_invoice_card`). These are owned exclusively by ksf_FA_Square.
If this module's `db_prewrite` fires after ksf_FA_Square's and the
payment term is a Square-Invoice term, this module SHALL find no
mapping and take no action.

**Acceptance Criteria:**

1. Square-Invoice terms are NOT inserted into `0_ksf_payment_destinations`.
2. If a Square-Invoice term accidentally has a mapping, this module's
   hook still proceeds normally (no special-casing).
3. No code in this module imports or references ksf_FA_Square.
4. Hook execution order: FA runs hooks alphabetically by module name;
   `FA_PaymentDestinations` < `FA_Square`, so Square's hook fires first.

**Code references:**

- `hooks.php:132-164` — `db_prewrite` (exits on `KSF_FIELD_NOT_SET`)

---

### FR-06: Hook Execution Order Coordination

**Title:** Coordinate db_prewrite with ksf_FA_Square via hook order

**Description:** When both ksf_FA_Square and FA_PaymentDestinations are
installed, their `db_prewrite` hooks fire in alphabetical order by
module name. ksf_FA_Square fires first, handles `square_invoice*` terms
(cash_sale=0), and this module fires second, handles non-Square
redirections (cash_sale=1). If ksf_FA_Square sets cash_sale=0 for a
Square term, this module's hook should not override it.

**Acceptance Criteria:**

1. This module's `db_prewrite` only fires for `ST_SALESINVOICE`.
2. If `terms_indicator` is not set, hook returns immediately.
3. If no mapping exists, hook returns without error.
4. This module never sets `cash_sale=0` (only Square does).

---

### FR-07: GL Mismatch Visibility in Import Review

**Title:** Coordinate with ISU for GL mismatch detection during staged transaction review

**Description:** When staged transactions (from Square, WooCommerce, etc.)
are matched to existing FA transactions during the ISU review/match
phase, the system SHALL compare GL accounts. If the GL account on the
staged transaction (from the importing module's config, e.g., `square_gl`)
differs from the GL account on the existing FA transaction (potentially
routed by this module's PaymentDestinations mapping), the mismatch SHALL
be flagged as an advisory warning in the review UI.

**Acceptance Criteria:**

1. ISU's `findMatchingTransactions()` and related match methods compare
   GL accounts between staged and matched FA transactions.
2. The staged GL comes from the importing module's config (e.g., Square's
   `square_gl` setting).
3. The FA GL comes from `gl_trans` for the matched transaction.
4. Mismatch is displayed as a visual warning (icon/text) on the match row.
5. Mismatch is advisory only — does not block processing.
6. This module does NOT participate directly in mismatch detection;
   the comparison is performed by ISU using data from both this module's
   routing and the importing module's config.

**Code references:**

- ISU `class.ksf_import_square.php` — match display methods
- ISU `class.ksf_import_square_transactions_model.php` — match queries

---

## 3. Non-Functional Requirements

| ID | Requirement |
|----|-------------|
| NFR-01 | PHP 7.3 compatible — no typed properties, no nullsafe operator, no named arguments |
| NFR-02 | FA 2.4.19 compatible — class-based hooks pattern, `0_` table prefix |
| NFR-03 | Idempotent operations — re-running install.sql uses `IF NOT EXISTS` |
| NFR-04 | Audit trail — `payment_term_name` and `bank_account_name` stored denormalized for historical readability |
| NFR-05 | Decoupled from ksf_FA_Square — no direct imports or dependencies between modules |
| NFR-06 | TDD workflow — all new code backed by PHPUnit tests with 100% coverage target |
| NFR-07 | PHPDoc standards — `@param`, `@return`, `@throws`, `@since` required on all public methods |
| NFR-08 | No secrets or keys in code or config |

---

## Version History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 2.0 | 2026-08-20 | KSFraser | Initial requirements with FR-01 through FR-06 |
| 2.1 | 2026-08-20 | KSFraser | Added FR-07 (GL mismatch visibility in import review) |
