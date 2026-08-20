# BABOK Business Analysis — FA_PaymentDestinations

> Module code: **PD**
> Aligned to BABOK v3 knowledge areas

---

## 1. Stakeholders

| Stakeholder | Role | Interest |
|-------------|------|----------|
| FA Administrator | Configures payment-to-GL mappings | Ensures correct routing for reconciliation |
| Accountant / Bookkeeper | Posts invoices, reviews GL postings | Payments land in correct GL accounts |
| Business Owner | Reviews financial reports | Accurate cash-basis reporting by payment type |
| Square Integration (ksf_FA_Square) | Intercepts Square-Invoice terms | Avoids conflict with non-Square redirections |

---

## 2. Needs Assessment

### Business Need

The business accepts multiple payment types (cash, cheques, credit cards,
e-transfers, Square Invoices) but FA posts all invoice payments to a
single default GL account. This makes bank reconciliation and cash-flow
analysis by payment type impossible without manual journal entries.

### Stakeholder Needs

| Need | Source | Priority |
|------|--------|----------|
| Route each payment type to its own GL account | Accountant | High |
| Configure mappings without code changes | Administrator | High |
| Square-Invoice payments handled separately | Square integration | High |
| Minimal disruption to existing FA workflow | All | High |
| Historical readability of payment destinations | Bookkeeper | Medium |

---

## 3. Business Requirements

### BR-PD-001: Payment Destination Configuration

**Statement:** The system shall provide an administrative interface to
define which bank account (GL account) receives postings for each FA
payment term.

**Rationale:** Different payment types flow through different bank
accounts or clearing accounts. Without per-type routing, reconciliation
requires manual intervention.

**Acceptance Criteria:**

- Administrator can add, view, and delete mappings.
- Each payment term maps to exactly one bank account.
- Mappings are persisted in `0_ksf_payment_destinations`.

**FR mapping:** FR-PD-001-001

---

### BR-PD-002: Automatic GL Redirection on Invoice Post

**Statement:** The system shall automatically redirect the GL posting of a
sales invoice to the bank account associated with the invoice's payment
term, without manual intervention.

**Rationale:** Manual GL correction after every invoice is error-prone
and does not scale. Automatic redirection at post time ensures
consistency.

**Acceptance Criteria:**

- When a sales invoice is posted with a mapped payment term, the GL
  posting targets the configured bank account.
- The transaction is recorded as a cash sale (auto-generated payment).
- Unmapped payment terms proceed with FA's default behavior.

**FR mapping:** FR-PD-002-001

---

### BR-PD-003: Decoupled Square-Invoice Handling

**Statement:** The system shall not interfere with Square-Invoice payment
terms (`square_invoice`, `square_invoice_email`, `square_invoice_card`),
which are handled exclusively by the ksf_FA_Square module.

**Rationale:** Square-Invoice transactions require API-level interaction
(handshake, payment processing, import matching) that is outside this
module's scope. Running two hooks on the same transaction requires clear
ownership.

**Acceptance Criteria:**

- This module does not import or reference ksf_FA_Square.
- Square-Invoice terms are handled by ksf_FA_Square's `db_prewrite`
  which fires first.
- If no mapping exists for a payment term, this module takes no action.

**FR mapping:** FR-PD-002-001, FR-PD-004-001

---

## 4. Functional Requirements Mapping

| FR | BR | Description |
|----|----|-------------|
| FR-PD-001-001 | BR-PD-001 | Payment term mapping UI (admin CRUD) |
| FR-PD-002-001 | BR-PD-002 | db_prewrite hook (intercept, lookup, rewrite, force cash_sale) |
| FR-PD-003-001 | BR-PD-001 | Module activation (install SQL, menu, security) |
| FR-PD-004-001 | BR-PD-003 | Inter-module communication (4 standard methods) |

---

## 5. Solution Approach

### Architecture

Hook-based interception within FA's extension framework:

1. **Configuration layer:** MVC pattern (controller + model + view)
   provides admin CRUD for the mapping table.
2. **Runtime layer:** `db_prewrite` hook in `hooks.php` intercepts
   `ST_SALESINVOICE` transactions before database write.
3. **Data layer:** Single table (`0_ksf_payment_destinations`) with
   denormalized names for audit trail.

### Data Flow

```
Admin UI → 0_ksf_payment_destinations (CRUD)
                              ↓
Sales Invoice Posted → db_prewrite → lookup mapping → rewrite pos_account → force cash_sale=1
                              ↓
FA writes invoice + auto-generated payment to GL
```

### Trade-offs

| Decision | Rationale |
|----------|-----------|
| Denormalized names (`payment_term_name`, `bank_account_name`) | Historical readability if source records change |
| `cash_sale = 1` forcing | FA only auto-generates payments for cash sales; this suppresses the payment form |
| Hook execution order dependency on ksf_FA_Square | FA runs hooks alphabetically by module name; `FA_PaymentDestinations` < `FA_Square` |
| Single-table design | Simple mapping; no need for composite keys or history table |
