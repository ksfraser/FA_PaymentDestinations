<?php
declare(strict_types=1);

/**
 * Payment Destinations — install schema.
 *
 * Maps FA payment_terms IDs to bank_accounts IDs so non-cash payments
 * (CC, Interac, cheque, Square-Invoice, etc.) post to their own GL accounts.
 */

$sql = [];

$sql[] = "CREATE TABLE IF NOT EXISTS `0_ksf_payment_destinations` (
  `payment_term` int(11) NOT NULL DEFAULT 0,
  `payment_term_name` varchar(200) NOT NULL DEFAULT '',
  `bank_account` int(11) NOT NULL DEFAULT 0,
  `bank_account_name` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`payment_term`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
