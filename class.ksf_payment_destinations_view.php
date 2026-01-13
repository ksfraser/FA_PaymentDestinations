<?php


/*******************************************
 * If you change the list of properties below, ensure that you also modify
 * build_write_properties_array
 * */

require_once( '../ksf_modules_common/defines.inc.php' ); 
global $path_to_ksfcommon;
require_once( $path_to_ksfcommon . '/class.table_interface.php' ); 
require_once( $path_to_ksfcommon . '/class.generic_fa_interface.php' );

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
 * Provides:
        function __construct( $prefs )
        function define_table()
        function form_Payment Type to Bank Account
        function form_Payment Type to Bank Account_completed
        function action_show_form()
        
        function master_form()
 * 
 *
 * ***************************************************************/


class ksf_payment_destinations_view extends generic_fa_interface_view {
	var $id_ksf_payment_destinations;	//!< Index of table
	//var $controller;	//inherited where we will get config values from, etc
	function __construct( $prefs, $controller )
	{
		parent::__construct( null, null, null, null, $prefs, $controller, false );	//generic_interface has legacy mysql connection
		//$this->config_values = $controller->config_values; //g_fa_i_v takes care of this...
		$this->tabs[] = array( 'title' => 'Module How-To', 'action' => 'usage_form', 'form' => 'usage_form', 'hidden' => FALSE );
		$this->tabs[] = array( 'title' => 'Config Updated', 'action' => 'update', 'form' => 'checkprefs', 'hidden' => TRUE );
		$this->tabs[] = array( 'title' => 'Configuration', 'action' => 'config', 'form' => 'show_config_form', 'hidden' => FALSE );	//form is inherited
		
		$this->tabs[] = array( 'title' => 'Setup Payment Destination Mapping', 'action' => 'form_master_form', 'form' => 'master_form', 'hidden' => FALSE );
		//$this->tabs[] = array( 'title' => 'ksf_payment_destinations Updated2', 'action' => 'form_ksf_payment_destinations_completed', 'form' => 'form_ksf_payment_destinations_completed', 'hidden' => TRUE );
		//$this->tabs[] = array( 'title' => 'Update ksf_payment_destinations2', 'action' => 'form_ksf_payment_destinations', 'form' => 'form_ksf_payment_destinations', 'hidden' => FALSE );
		//We could be looking for plugins here, adding menu's to the items.
		$this->add_submodules();
							
	}
	function usage_form()
	{
		$this->title = "How to Use this Module";
		start_form(true);
                start_table(TABLESTYLE2, "width=40%");
                table_section_title( "How to use this module" );
		table_section(1);
		label_row( "Configuration", "Configuration screen for things like DEBUG level." );
		label_row( "Setup Payment Destination Mapping", "This tab is where you associate payment types (e.g. cheque) to a destination bank account (e.g. cas drawer).  We have different credit card processors and we track the cashflow so we want different destinations." );
		label_row( "", "" );
		//end_table(1);
                //start_table(TABLESTYLE2, "width=40%");
                table_section_title( "Known Bugs" );
		label_row( "Bug 1 - Edit button", "Edit buttons (pencil) doesn't actually launch an edit screen.  Low priority at this time as I have DB access.  Work around is to delete and re-add.  See Delete bug below." );
		label_row( "Bug 2 - Delete Button", "Delete button (X) doesn't reload the table listing the mappings. Work around is to switch tabs (e.g. come to this tab) and return." );
		label_row( "Bug 3 - Configuration", "Config screen doesn't display any of the config variables.  As the only one this module has is DEBUG, this is very low priority as there isn't any non EXCEPTION debugging code currently included" );
		//end_table(1);
                //start_table(TABLESTYLE2, "width=40%");
                table_section_title( "Roadmap" );
		label_row( "V2", "No Planned enhancements other than bug fixes." );
                table_section_title( "Developer Documentation" );
		label_row( "Documentation", '<a href="html/index.html">Class and member Documentation</a>' );
		end_table(1);
                end_form();
	}
	/*************************************************************//**
	 * Using FA routine display a combo box of bank accounts
	 *
	 * ***************************************************************/
	function comboBankAccountList()
	{
		//require_once( $path_to_root . "/includes/ui/ui_lists.inc" );
		echo "<td>" . bank_accounts_list("bank_account", null, false, false ) . "</td>";
	}
	function comboPaymentList()
	{
		//require_once( $path_to_root . "/includes/ui/ui_lists.inc" );
		echo "<td>" . sale_payment_list("payment_term", "", null, false) . "</td>";
	}
	function form_ksf_payment_destinations_completed()
	{	//Need to add code here to do whatever this submodule is for...\
		$this->master_form();
	}
	/**************************************************//**
	 * Set the focus on the cart's displayed table 
	 *
	 * @returns NONE
	 * ***************************************************/
	function line_start_focus() 
	{
	  global $Ajax;
	  $Ajax->activate('edit_form');
	  set_focus('action');
	}
	/*html table header row*/function form_header()
	{
		$th = array();
		$this->tabledef2headers();
		foreach( $this->header_arr as $name => $label )
			$th[] = $label;
		if( $this->show_inactive )
			inactive_control_column($th);
		table_header($th);
		return $th;
	}
	function form_item_rows()
	{
		if( isset( $this->controller ) )
		{
			if( isset( $this->controller->model ) )
			{
				$result = $this->controller->model->getAll();
				$primary_key = $this->controller->model->getPrimaryKey();
				$k = 0;
				while ($myrow = db_fetch($result)) 
				{			
					alt_table_row_color($k);
					foreach( $this->header_arr as $name => $label )
					{
						if( isset( $myrow[$name] ) )
							label_cell( $myrow[$name] );
						else
						if( $name == 'edit' )
							edit_button_cell("Edit" . $myrow[$primary_key], _("Edit"), "Edit item");
						else
						if( $name == 'delete' )
							delete_button_cell("Delete" . $myrow[$primary_key], _("Delete"), "Delete Item");
						else
							label_cell("");
					}
					if( $this->show_inactive )
						inactive_control_cell($myrow[$primary_key], $myrow["inactive"], $primary_key, $primary_key);
					end_row();
				}
				start_row();
				//hidden('action', $action );
				hidden('ksf_payment_destinations', $this->action );
				end_row();
			}
		}
	
	}
	function form_add( $action, $msg )
	{
		div_start( 'form_add' );
		start_form();
	       	start_table(TABLESTYLE2, "width=40%");
		//table_section_title( $msg );
		$th = array( _("Payment Terms"), _("Bank Account") );
		table_header( $th );
		start_row();
		$this->comboPaymentList();
		$this->comboBankAccountList();
		end_row();
		start_row();
		//hidden('action', $action );
		hidden('ksf_payment_destinations', $action );
		end_row();
		if( $this->controller->selected_id >= 0 )
		{
			start_row();
			$this->comboPaymentList();
			$this->comboBankAccountList();
			end_row();
		}
	        end_table(1);
		//$name, $value, $echo=true, $title=false, $atype=false, $icon=false
	        submit_center( $action, $msg );
		end_form();
		div_end();
	}
	function edit_item_form( )
	{
		div_start( 'edit_form' );
		start_form();
	       	start_table(TABLESTYLE2, "width=40%");
		//table_section_title( $msg );
		$th = array( _("Payment Terms"), _("Bank Account") );
		table_header( $th );
		start_row();
		$this->comboPaymentList();
		$this->comboBankAccountList();
		end_row();
		start_row();
		hidden('action', $action );
		hidden('time', time() );
		hidden('ksf_payment_destinations', $action );
		end_row();
	        end_table(1);
		//$name, $value, $echo=true, $title=false, $atype=false, $icon=false
	        submit_center( "Update", "Update" );
		end_form();
		div_end();
		global $Ajax;
		$Ajax->activate('edit_form');
		set_focus( 'Update' );
	}
	function item_form()
	{
		display_heading("Payment Terms to Bank Account Map");
		div_start('display_form');
		start_form();
		//start_form(true);	//Makes a multi-part/encoded form
		start_table(TABLESTYLE2, "width=40%");
		$th = $this->form_header();
		$this->form_item_rows();
		if( $this->show_inactive )
			inactive_control_row($th);
	        end_table(1);
	        end_form();
		div_end();
	}
	/*********************************************************************************//**
	 *master_form
	 *	Display the summary of items with edit/delete
	 *		
	 *	assumes entry_array has been built (constructor)
	 *	assumes table_details has been built (constructor)
	 *	assumes selected_id has been set (constructor?)
	 *	assumes iam has been set (constructor)
	 *
	 * ***********************************************************************************/
	function master_form()
	{
		//display_notification( __FILE__ . "::" . __CLASS__ . "::"  . __METHOD__ . ":" . __LINE__, "WARN" );
		$title = "Map Payment Term to Bank Account";
		$_SESSION['page_title'] = _($help_context = $title);
		$this->item_form();
		$msg = "Map the accounts";
		$action = $this->action;
		$this->form_add( $action, $msg );
	}

	
}
