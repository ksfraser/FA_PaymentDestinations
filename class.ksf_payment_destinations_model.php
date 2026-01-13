<?php

/*******************************************
 * If you change the list of properties below, ensure that you also modify
 * build_write_properties_array
 * */
$path_to_ksfcommon = __DIR__ . '/../ksf_modules_common/';
require_once( __DIR__ . '/../ksf_modules_common/defines.inc.php' ); 
//global $path_to_ksfcommon;
require_once( $path_to_ksfcommon . '/class.table_interface.php' ); 
require_once( $path_to_ksfcommon . '/class.generic_fa_interface.php' );
require_once( 'ksf_payment_destinations.inc.php' );

/*************************************************************//**
 * 
 *
 * Inherits:
 *                 function __construct( $host, $user, $pass, $database, $pref_tablename )
                function eventloop( $event, $method )
                function eventregister( $event, $method )
                function add_submodules()
                function module_install()
                function install()
                function loadprefs()
                function updateprefs()
                function checkprefs()
                function call_table( $action, $msg )
                function action_show_form()
                function show_config_form()
                function form_export()
                function related_tabs()
                function show_form()
                function base_page()
                function display()
                function run()
                function modify_table_column( $tables_array )
                / *@fp@* /function append_file( $filename )
                /*@fp@* /function overwrite_file( $filename )
                /*@fp@* /function open_write_file( $filename )
                function write_line( $fp, $line )
                function close_file( $fp )
                function file_finish( $fp )
                function backtrace()
                function write_sku_labels_line( $stock_id, $category, $description, $price )
		function show_generic_form($form_array)
		function get( $field )
        	function set( $field, $value = null, $enforce = false )
		function select_row()
		function install()
	        function insert_data( $data_arr )
        
 * Provides:
  	function __construct( $prefs = ksf_payment_destinations_PREFS, $controller )
        function getPaymentTerms()
        function getBankAccountFromTerm()
        function define_table()
        
	*

 * ***************************************************************/


class ksf_payment_destinations_model extends generic_fa_interface_model {
	var $id_ksf_payment_destinations;	//!< Index of table
	protected $payment_term;			//!< int payment type
	protected $payment_term_name;
	protected $bank_account;			//!< int GL number
	protected $bank_account_name;
	function __construct( $prefs = ksf_payment_destinations_PREFS, $controller )
	{
		parent::__construct( null, null, null, null, $prefs );	//generic_interface has legacy mysql connection
									//not needed with the $prefs
		$this->controller = $controller;
		$this->table_interface = new table_interface();
		$this->define_table();
	}
	function getPaymentTerms()
	{
		//display_notification( __FILE__ . "::" . __CLASS__ . "::"  . __METHOD__ . ":" . __LINE__, "WARN" );
		$terms_arr = array();
		$show_inactive = false;
		$res = get_payment_terms( $show_inactive );
		while( $data = db_fetch( $res ) != null )
		{
			$terms_arr[] = $data;
		}
		return $terms_arr;

	}
	function getBankAccountFromTerm()
	{
		//display_notification( __FILE__ . "::" . __CLASS__ . "::"  . __METHOD__ . ":" . __LINE__, "WARN" );
		if( !isset( $this->payment_term ) )
			throw new Exception( "Payment Term not set" );
		$this->sql = "select * from " . $this->table_interface->table_details['tablename'] . "WHERE payment_term = '" . $this->payment_term . "'";
		$this->sqlerrmsg = "Couldn't get pay2bank details";
		$this->mysql_query();
		return $this->data['bank_account'];
	}
	function define_table()
	{
		//display_notification( __FILE__ . "::" . __CLASS__ . "::"  . __METHOD__ . ":" . __LINE__, "WARN" );
		$descl = 'varchar(' . DESCRIPTION_LENGTH . ')';
		$this->table_interface->table_details['tablename'] = TB_PREF . 'ksf_payment_destinations';
		$this->table_interface->fields_array[] = array('name' => 'payment_term', 'label' => 'Payment Term number', 'type' => 'int(11)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'default' => '0' );
		$this->table_interface->fields_array[] = array('name' => 'payment_term_name', 'label' => 'Payment Term name', 'type' => $descl, 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'default' => '0' );
		$this->table_interface->fields_array[] = array('name' => 'bank_account', 'label' => 'bank account number', 'type' => 'int(11)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'default' => '0' );
		$this->table_interface->fields_array[] = array('name' => 'bank_account_name', 'label' => 'bank account name', 'type' => $descl, 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'default' => '0' );
		$this->table_interface->table_details['primarykey'] = "payment_term";
	}
	function insert_data( $arr )
	{
		global $path_to_ksfcommon;

		require_once( $path_to_ksfcommon . "/class.fa_bank_accounts.php" );
		$ba = new fa_bank_accounts( $this );
		$ba->set( 'id', $arr['bank_account'] );
		$ba->getById();
		$arr['bank_account_name'] = $ba->get( 'bank_account_name' );

		require_once( $path_to_ksfcommon . "/class.fa_payment_terms.php" );
		$pt = new fa_payment_terms( $this );
		$pt->set( 'terms_indicator', $arr['payment_term'] );
		$pt->getById();
		$arr['payment_term_name'] = $pt->get( 'terms' );

		parent::insert_data( $arr );
	}

}
