<?php
/**********************************************
Name: 
for FrontAccounting 2.3.15 by kfraser 
Free software under GNU GPL
***********************************************/

global $path_to_root;
$path_to_root = '../../';

$page_security = 'SA_ksf_payment_destinations';
include( __DIR__ . "/../../includes/session.inc");
add_access_extensions();
set_ext_domain('modules/ksf_payment_destinations');
include_once( __DIR__ . "/../../includes/ui.inc");
include_once( __DIR__ . "/../../includes/data_checks.inc");

//error_reporting(E_ALL);
//ini_set("display_errors", "on");

//global $db; // Allow access to the FA database connection
//$debug_sql = 0;  // Change to 1 for debug messages

	include_once( "class.ksf_payment_destinations.php");
	require_once( 'ksf_payment_destinations.inc.php' );
	$my_mod = new ksf_payment_destinations( ksf_payment_destinations_PREFS );
//	$found = $my_mod->is_installed();
//	$my_mod->set( 'found', $found );
	$my_mod->set_var( 'help_context', ksf_payment_destinations_HELP );
	$my_mod->set_var( 'redirect_to', "ksf_payment_destinations.php" );
	$my_mod->run();

