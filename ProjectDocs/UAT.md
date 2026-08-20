# User Acceptance Test Cases — FA_PaymentDestinations

> Module code: **PD**
> Platform: PHP 7.3 / FrontAccounting 2.4.19

---

## UAT-PD-001: Admin Can Add a Payment Destination Mapping

**@BABOK Related: FR-PD-001-001**

| Field | Value |
|-------|-------|
| **Actor** | FA Administrator |
| **Preconditions** | Module installed; SA_ksf_FA_PaymentDestinations access granted; at least one payment term and one bank account exist in FA |
| **Priority** | High |

### Steps

1. Navigate to Banking &amp; General Ledger &rarr; Payment Destinations.
2. On the "Map Payment Term to Bank Account" form, select a payment term
   (e.g., "Visa MC") from the Payment Terms dropdown.
3. Select a bank account (e.g., "Credit Card Processing") from the Bank
   Account dropdown.
4. Click "Map the accounts".

### Expected Result

- The mapping appears in the mappings table with the correct payment term
  name and bank account name.
- The mapping is persisted in `0_ksf_payment_destinations`.

---

## UAT-PD-002: Admin Can Edit an Existing Mapping

**@BABOK Related: FR-PD-001-001**

| Field | Value |
|-------|-------|
| **Actor** | FA Administrator |
| **Preconditions** | At least one mapping exists in `0_ksf_payment_destinations` |
| **Priority** | Low (known bug — pencil button non-functional) |

### Steps

1. Navigate to Payment Destinations.
2. Click the Edit (pencil) button on an existing mapping row.
3. Change the bank account selection.
4. Click "Update".

### Expected Result

- The mapping is updated with the new bank account.

### Known Issue

The Edit button may not launch an edit screen (documented bug in the
view). Workaround: delete the mapping and re-add it.

---

## UAT-PD-003: Admin Can Delete a Mapping

**@BABOK Related: FR-PD-001-001**

| Field | Value |
|-------|-------|
| **Actor** | FA Administrator |
| **Preconditions** | At least one mapping exists in `0_ksf_payment_destinations` |
| **Priority** | Medium |

### Steps

1. Navigate to Payment Destinations.
2. Click the Delete (X) button on an existing mapping row.
3. Refresh the page or switch tabs and return if the table does not
   auto-refresh.

### Expected Result

- The mapping is removed from `0_ksf_payment_destinations`.
- The mappings table no longer shows the deleted row.

### Known Issue

The table may not auto-refresh after delete. Workaround: switch to
another tab and return.

---

## UAT-PD-004: Sales Invoice with Mapped Payment Term Redirects GL Posting

**@BABOK Related: FR-PD-002-001**

| Field | Value |
|-------|-------|
| **Actor** | FA User (Invoice Entry) |
| **Preconditions** | A payment term-to-bank-account mapping exists; a customer with that payment term; a direct sales invoice can be posted |
| **Priority** | High |

### Steps

1. Create a direct sales invoice for a customer whose payment term has a
   mapping (e.g., "Visa MC" → "Credit Card Processing" bank account).
2. Post the invoice.

### Expected Result

- The GL posting for the payment targets the mapped bank account (not the
  default POS account).
- A customer payment record is auto-generated alongside the invoice.
- The transaction is recorded as a cash sale.

### Verification

- Check the GL account for the bank account used in the mapping.
- Verify the payment amount appears in that GL account, not in the
  default cash account.

---

## UAT-PD-005: Sales Invoice with Unmapped Payment Term Follows Normal Flow

**@BABOK Related: FR-PD-002-001**

| Field | Value |
|-------|-------|
| **Actor** | FA User (Invoice Entry) |
| **Preconditions** | The customer's payment term does NOT have a mapping in `0_ksf_payment_destinations` |
| **Priority** | High |

### Steps

1. Create a direct sales invoice for a customer whose payment term has no
   mapping (e.g., "Net 30").
2. Post the invoice.

### Expected Result

- The GL posting uses the default POS account (no redirection).
- FA behaves exactly as it would without this module installed.
- No errors or warnings are displayed.

### Verification

- The payment term indicator is not in the mapping table.
- The GL posting matches FA's default behavior.

---

## UAT-PD-006: cash_sale Forced to 1 for Mapped Terms

**@BABOK Related: FR-PD-002-001**

| Field | Value |
|-------|-------|
| **Actor** | FA system (automatic) |
| **Preconditions** | A payment term-to-bank-account mapping exists; a sales invoice is posted with that payment term |
| **Priority** | High |

### Steps

1. Post a direct sales invoice with a mapped payment term.
2. Inspect the `$cart->payment_terms['cash_sale']` value after
   `db_prewrite` runs (debug/logging).

### Expected Result

- `$cart->payment_terms['cash_sale']` is `1` after the hook runs.
- FA records the invoice as a cash transaction.
- No payment entry form is presented to the user (auto-generated payment).

### Verification

- The invoice record in FA shows as a cash sale.
- A corresponding payment record exists in the bank transaction log
  against the mapped bank account.

---

## Summary

| UAT ID | Description | FR | Priority |
|--------|-------------|----|----------|
| UAT-PD-001 | Admin can add a mapping | FR-PD-001-001 | High |
| UAT-PD-002 | Admin can edit a mapping | FR-PD-001-001 | Low |
| UAT-PD-003 | Admin can delete a mapping | FR-PD-001-001 | Medium |
| UAT-PD-004 | Mapped term → GL redirected | FR-PD-002-001 | High |
| UAT-PD-005 | Unmapped term → normal flow | FR-PD-002-001 | High |
| UAT-PD-006 | cash_sale forced to 1 | FR-PD-002-001 | High |
