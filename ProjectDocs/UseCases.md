# Use Cases — FA_PaymentDestinations

> Module code: **PD**

---

## UC-PD-001: Configure Payment Destinations

**Actor:** FA Administrator

**Trigger:** Administrator navigates to Banking &amp; General Ledger &rarr;
Payment Destinations.

**Preconditions:**

- FA administrator has `SA_ksf_FA_PaymentDestinations` access.
- FA payment terms are configured (standard FA setup).
- FA bank accounts are configured (standard FA setup).

**Postconditions:**

- Mapping is persisted in `0_ksf_payment_destinations`.
- Subsequent invoices using that payment term will be redirected.

### Basic Flow

1. Administrator opens the Payment Destinations page.
2. System displays the "Map Payment Term to Bank Account" form with
   existing mappings table.
3. Administrator selects a payment term from the Payment Terms dropdown.
4. Administrator selects a bank account from the Bank Account dropdown.
5. Administrator clicks "Map the accounts".
6. System resolves human-readable names for the term and account.
7. System inserts the mapping into `0_ksf_payment_destinations`.
8. System refreshes the mapping table.

### Alternative Flows

**AF-1: Duplicate payment term**

1. At step 6, system detects the payment term already has a mapping.
2. System displays an error notification.
3. Flow returns to step 3.

**AF-2: Delete mapping**

1. Administrator clicks the Delete button on an existing mapping row.
2. System deletes the row from `0_ksf_payment_destinations`.
3. System refreshes the mapping table.

### Notes

- The page also includes a "Module How-To" tab with usage instructions
  and known bugs.
- Edit functionality (pencil button) is documented as non-functional in
  the view; workaround is delete-and-re-add.

---

## UC-PD-002: Route Payment to GL Account

**Actor:** FA system (automatic — triggered when a sales invoice is posted)

**Trigger:** A user posts a direct sales invoice (ST_SALESINVOICE) in FA.

**Preconditions:**

- The `0_ksf_payment_destinations` table has at least one mapping.
- The invoice's payment term matches an entry in the mapping table.
- If the payment term is a Square-Invoice term, ksf_FA_Square's
  `db_prewrite` has already run and set `cash_sale = 0`.

**Postconditions:**

- The GL posting targets the bank account configured for the payment
  term (not the default POS account).
- A customer payment record is auto-generated alongside the invoice.
- FA records the transaction as a cash sale.

### Basic Flow

1. User posts a sales invoice in FA.
2. FA invokes `db_prewrite` hooks before writing to the database.
3. `hooks_ksf_FA_PaymentDestinations::db_prewrite()` receives the cart
   and `ST_SALESINVOICE` transaction type.
4. Hook verifies `$trans_type === ST_SALESINVOICE`; exits if not.
5. Hook checks that `$cart->payment_terms['terms_indicator']` is set;
   exits if not.
6. Hook instantiates `ksf_payment_destinations_model` and queries
   `0_ksf_payment_destinations` for the payment term.
7. **If mapping found:**
   a. Hook rewrites `$cart->pos['pos_account']` to the mapped
      `bank_account` ID.
   b. Hook forces `$cart->payment_terms['cash_sale'] = 1`.
   c. Hook returns `true`.
8. **If no mapping found** (model throws `KSF_FIELD_NOT_SET`):
   a. Hook returns without error; FA proceeds with default behavior.
9. FA writes the invoice and auto-generated payment to the database.

### Alternative Flows

**AF-1: Non-invoice transaction**

1. At step 4, `$trans_type !== ST_SALESINVOICE`.
2. Hook returns immediately; no modification.

**AF-2: Payment terms not set on cart**

1. At step 5, `$cart->payment_terms['terms_indicator']` is not set.
2. Hook returns immediately; no modification.

**AF-3: Square-Invoice payment term**

1. At step 6, the payment term is `square_invoice`,
   `square_invoice_email`, or `square_invoice_card`.
2. ksf_FA_Square's `db_prewrite` has already run and set `cash_sale = 0`.
3. This module's hook either finds no mapping (ksf_FA_Square owns these
   terms) or finds a mapping and redirects — but since Square terms
   should not be in this module's mapping table, the normal path is "no
   mapping found" and FA default behavior applies.

**AF-4: Database error**

1. At step 6, a database error occurs.
2. Hook catches the exception, displays an error notification, and
   returns `true` to avoid blocking the transaction.

---

## UC-PD-003: Module Installation

**Actor:** FA Administrator

**Trigger:** Administrator activates the module in FA Setup &rarr;
Install/Activate Extensions.

**Preconditions:**

- Module files are copied to `modules/FA_PaymentDestinations/`.
- FA administrator has install permissions.

**Postconditions:**

- `0_ksf_payment_destinations` table exists in the database.
- "Payment Destinations" menu item appears under GL &amp; Orders.
- Security sections and areas are registered.

### Basic Flow

1. Administrator activates the module.
2. FA calls `activate_extension()`.
3. Hook runs `sql/install.sql` to create the table.
4. FA calls `install_options()` to register menu items.
5. FA calls `install_access()` to register security sections and areas.
6. Module is ready for configuration.

### Alternative Flows

**AF-1: Table already exists**

1. At step 3, `CREATE TABLE IF NOT EXISTS` succeeds silently.
2. No error; module activation completes.
