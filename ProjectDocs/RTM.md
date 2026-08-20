# Requirements Traceability Matrix — FA_PaymentDestinations

> Module code: **PD**
> Auto-generated from `@BABOK Related` references in code and tests.
> Do not maintain a separate hand-written RTM.

---

## FR → UC → Code → Test

| FR | Description | UC | Code File(s) | Test IDs |
|----|-------------|----|--------------|----------|
| FR-PD-001-001 | Payment term mapping UI (admin CRUD) | UC-PD-001 | `class.ksf_payment_destinations.php`, `class.ksf_payment_destinations_view.php`, `class.ksf_payment_destinations_model.php`, `ksf_payment_destinations.php` | UT-PD-001-001-001, UAT-PD-001, UAT-PD-002, UAT-PD-003 |
| FR-PD-002-001 | db_prewrite hook (intercept, lookup, rewrite, force cash_sale) | UC-PD-002 | `hooks.php:132-164` | UT-PD-002-001-001, UAT-PD-004, UAT-PD-005, UAT-PD-006 |
| FR-PD-003-001 | Module activation (install SQL, menu, security) | UC-PD-003 | `hooks.php:74-116`, `sql/install.sql` | UT-PD-003-001-001 |
| FR-PD-004-001 | Inter-module communication (4 standard methods) | UC-PD-002, UC-PD-003 | `hooks.php:26-72` | UT-PD-004-001-001 |

---

## BR → FR Mapping

| BR | Description | FR(s) |
|----|-------------|-------|
| BR-PD-001 | Payment destination configuration | FR-PD-001-001, FR-PD-003-001 |
| BR-PD-002 | Automatic GL redirection on invoice post | FR-PD-002-001 |
| BR-PD-003 | Decoupled Square-Invoice handling | FR-PD-002-001, FR-PD-004-001 |

---

## UC → FR Mapping

| UC | Description | FR(s) |
|----|-------------|-------|
| UC-PD-001 | Configure Payment Destinations | FR-PD-001-001 |
| UC-PD-002 | Route Payment to GL Account | FR-PD-002-001, FR-PD-004-001 |
| UC-PD-003 | Module Installation | FR-PD-003-001 |

---

## UAT → FR Mapping

| UAT | Description | FR(s) |
|-----|-------------|-------|
| UAT-PD-001 | Admin can add a payment destination mapping | FR-PD-001-001 |
| UAT-PD-002 | Admin can edit an existing mapping | FR-PD-001-001 |
| UAT-PD-003 | Admin can delete a mapping | FR-PD-001-001 |
| UAT-PD-004 | Sales invoice with mapped term → GL redirected | FR-PD-002-001 |
| UAT-PD-005 | Sales invoice with unmapped term → normal flow | FR-PD-002-001 |
| UAT-PD-006 | cash_sale forced to 1 for mapped terms | FR-PD-002-001 |

---

## Coverage Summary

| Artifact Type | Count | Fully Covered |
|---------------|-------|---------------|
| Business Requirements | 3 | Yes |
| Functional Requirements | 4 | Yes |
| Use Cases | 3 | Yes |
| UAT Cases | 6 | Yes |
| Code Files | 7 | Yes |

All requirements have full traceability from BR through FR, UC, code, and
test. No orphaned requirements or untested code paths.
