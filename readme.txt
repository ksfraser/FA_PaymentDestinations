FA_PaymentDestinations
======================

Redirect non-cash payments to per-type GL accounts in FrontAccounting.

What It Does
------------
When processing a direct invoice, this module intercepts the ST_SALESINVOICE
transaction via db_prewrite. It looks up the payment term in the mapping table
and rewrites the cart's pos_account to the configured bank account. It then
forces cash_sale=1 so FA auto-generates a customer payment record alongside
the invoice — posting to the correct GL account for that payment type.

Example: A "Visa MC" payment term maps to a "Credit Card Processing" bank
account. When a customer pays by Visa, the payment posts to the CC GL account
instead of the default cash account.

Installation
------------
1. Copy FA_PaymentDestinations to modules/
2. FA → Setup → Install/Activate Extensions → activate
3. FA → Setup → Access Setup → grant access
4. FA → Banking and General Ledger → Payment Destinations → configure mappings

Configuration
-------------
Map each FA payment term to its destination bank account:
- Payment Terms: dropdown from FA's payment_terms table
- Bank Account: dropdown from FA's bank_accounts table

Square-Invoice Support
----------------------
For Square-Invoice destinations (square_invoice, square_invoice_email,
square_invoice_card), the ksf_FA_Square module's db_prewrite fires first
and suppresses the auto-payment (cash_sale=0). The Square module then
creates a Square Invoice via API and stores the mapping for later matching
when the Square transaction is imported.
