<?php
declare(strict_types=1);

$page_security = 'SA_ksf_FA_PaymentDestinations';
$path_to_root = '../../';

include_once $path_to_root . '/includes/session.inc';
add_access_extensions();

include_once $path_to_root . '/includes/ui.inc';
include_once $path_to_root . '/includes/data_checks.inc';

include_once __DIR__ . '/class.ksf_payment_destinations.php';
require_once __DIR__ . '/ksf_payment_destinations.inc.php';

$my_mod = new ksf_payment_destinations(ksf_payment_destinations_PREFS);
$my_mod->set_var('help_context', ksf_payment_destinations_HELP);
$my_mod->set_var('redirect_to', 'ksf_payment_destinations.php');
$my_mod->run();
