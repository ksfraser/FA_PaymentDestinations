<?php



/*******************************************
 * If you change the list of properties below, ensure that you also modify
 * build_write_properties_array
 * */

require_once( '../ksf_modules_common/defines.inc.php' );
require_once( $path_to_ksfcommon . '/class.table_interface.php' ); 
require_once( $path_to_ksfcommon . '/class.generic_fa_interface.php' );

/*************************************************************//**
 * Redirect non cash payments to act like a cash payment if we
 * have a direct invoice.
 *
 * This class acts effectively as a controller.
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
        function form_ksf_payment_destinations
        function form_ksf_payment_destinations_completed
        function action_show_form()
        function install()
        function master_form()
 * 
 *
 * ***************************************************************/


class ksf_payment_destinations extends generic_fa_interface_controller {
	var $id_ksf_payment_destinations;	//!< Index of table
	var $table_interface;
	function __construct( $prefs )
	{
		//display_notification( __FILE__ . "::" . __CLASS__ . "::"  . __METHOD__ . ":" . __LINE__, "WARN" );
		parent::__construct( null, null, null, null, $prefs );	//generic_interface has legacy mysql connection
			//sets ->edit, ->delete, ->selected_id
		//not needed with the $prefs
		$this->set_var( 'found', $this->is_installed() );
		$this->config_values[] = array( 'pref_name' => 'debug', 'label' => 'Debug (0,1+)' );	//Used in the view by the inherited
													//show_config_form from g_fa_i_v
		require_once( 'class.ksf_payment_destinations_view.php' );
		$this->view = new ksf_payment_destinations_view( $prefs, $this );
		$this->view->set( "found", $this->get( "found" ) );
		$this->tabs = $this->view->tabs;	//Short term work around until VIEW code everywhere

		//We could be looking for plugins here, adding menu's to the items.
		$this->add_submodules();
		require_once( 'class.ksf_payment_destinations_model.php' );
		$this->model = new ksf_payment_destinations_model( ksf_payment_destinations_PREFS, $this );	//defines the table
		$this->model->set( "found", $this->get( "found" ) );
	}
	/**//**
	 * Handle the updating of the count of a stock_id
	 * */
	function handle_edit()
	{
		$_POST['func'] = 'handle_edit';
	}
	/**//**
	 * Handle the deleting from the cart of an item
	 * */
	function handle_delete()
	{
		//display_notification( __FILE__ . "::" . __CLASS__ . "::"  . __METHOD__ . ":" . __LINE__ , "WARN" );
		//throw new Exception( __FILE__ . "::" . __CLASS__ . "::"  . __METHOD__ . ":" . __LINE__  );
		$prikey = $this->model->table_interface->table_details['primarykey'];
		$this->model->set( $prikey, $this->delete );
		$this->model->table_interface->delete_table();
		$_POST['func'] = 'handle_delete';
	}

	function run()
	{
		global $Ajax;
		//display_notification( __FILE__ . "::" . __CLASS__ . "::"  . __METHOD__ . ":" . __LINE__, "WARN" );
		if( isset( $_POST['payment_term'] ) and $this->selected_id < 0 )
		{
			$this->model->insert_data( $_POST );
			unset( $_POST['ksf_payment_destinations'] );
			//display_notification( __FILE__ . "::" . __CLASS__ . "::"  . __METHOD__ . ":" . __LINE__, "WARN" );
			//$this->view->page_modified(true);			//Makes an endless loop of reload.
			//
			//When an insert happens, the view portion runs some of it twice.  We are seeing the tabs[] options 2x
			//with the first at the top of the screen and the 'mapping' tab solid, but the second time doesn't do
			//that (action reset?)
			//Footer is also there 2x.
			//display_notifications are only 1x as is master menu choices so page itself is not updating
			//but an AJAX div is being filled?
		}
		else if( $this->delete >= 0 )
		{
			$this->handle_delete();
		}
		else if( $this->edit >= 0 )
		{
			$this->handle_edit();
		}
		//$this->view->page_modified(false);			//Makes an endless loop of reload.
		parent::run();
		$Ajax->activate('display_form');
		set_focus('time');
	}
	function action_show_form()
	{
		//display_notification( __FILE__ . "::" . __CLASS__ . "::"  . __METHOD__ . ":" . __LINE__, "WARN" );
		$this->install();
		parent::action_show_form();
	}
	function install()
	{
		//display_notification( __FILE__ . "::" . __CLASS__ . "::"  . __METHOD__ . ":" . __LINE__, "WARN" );
		$this->model->create_table();
		$this->model->install();
		display_notification( __FILE__ . "::" . __CLASS__ . "::"  . __METHOD__ . ":" . __LINE__, "WARN" );
		//parent::install();	//_model calls parent::isntall as well so we shouldn't need to.
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
		global $Ajax;
		div_start('form');
		$count = $this->fields_array2var();
		
		$sql = "SELECT ";
		$rowcount = 0;
		foreach( $this->entry_array as $row )
		{
			if( $rowcount > 0 ) $sql .= ", ";
			$sql .= $row['name'];
			$rowcount++;
		}
		$sql .= " from " . $this->table_interface->table_details['tablename'];
		if( isset( $this->table_interface->table_details['orderby'] ) )
			$sql .= " ORDER BY " . $this->table_interface->table_details['orderby'];
	
		$this->display_table_with_edit( $sql, $this->entry_array, $this->table_interface->table_details['primarykey'] );
		div_end();
		div_start('generate');
		div_end();
	}

	
}
