
<?php
define ('SS_ksf_payment_destinations', 111<<8);
	/**************NOTE**********************************
	 * Using DISPLAY_* causes the next screen (print receipt etc) to not appear.  This is due to the AJAX - going to next screen
	 * using a URL redirect nukes the messages!  See line 465 in sales_order_entry.php
	 * **************************************************/

/***************************************************************************************
 *
 * Hooks is what adds menus, etc to FrontAccounting.
 * It also appears to be called pre and post database transactions
 * for certain modules (see includes/hooks.inc) around line 360
 * 	hook_db_prewrite
 * 	hook_db_postwrite
 * 	hook_db_prevoid
 *
 * Looks like we could also provide our own authentication module
 * 	hook_authenticate (useful for REST?)
 *
 * ***********************************************************************************/
class hooks_ksf_payment_destinations extends hooks {
	var $module_name = 'ksf_payment_destinations'; 

	/*
		Install additonal menu options provided by module
	*/
	function install_options($app) {
		switch($app->id) {
			case 'GL':
			//case 'system':
			//case 'stock':
			//case 'AP':
			case 'orders':
			//case 'stock':
				$app->add_rapp_function(2, _('ksf_payment_destinations'), 
					 'modules/ksf_payment_destinations/ksf_payment_destinations.php', 'SA_ksf_payment_destinations');
		}
	}

	function install_access()
	{
		$security_sections[SS_ksf_payment_destinations] = _("ksf_payment_destinations");

		$security_areas['SA_ksf_payment_destinations'] = array(SS_ksf_payment_destinations|101, _("ksf_payment_destinations"));

		return array($security_areas, $security_sections);
	}
	/**************NOTE**********************************
	 * Using DISPLAY_* causes the next screen (print receipt etc) to not appear.  This is due to the AJAX - going to next screen
	 * using a URL redirect nukes the messages!  See line 465 in sales_order_entry.php
	 * **************************************************/
	function db_prewrite(&$cart, $trans_type)
	{
		//display_notification( __FILE__ . ":" . __LINE__  );
		//Want to trap payment types so we can post to an account like cash does
		//type 30 == sales_order
		//type 13 == delivery
		//type 10 == invoice
		//type 12 == payment
		//If we are on a direct invoice, we will do a 30->13->10 and then ->12 if cash_sales set to one
		if( $trans_type === ST_SALESINVOICE )
		{
			//display_notification( __FILE__ . ":" . __LINE__ . ":: trans_type = " . $trans_type );
			//Match the payment type (e.g. Dream) to the appropriate bank account
			if( require_once( 'class.ksf_payment_destinations_model.php' ) )
			{
				//display_notification( __FILE__ . ":" . __LINE__  );
				$pay = new ksf_payment_destinations_model( ksf_payment_destinations_PREFS, $this );
				$pay->set_var( "payment_term", $cart->payment_terms['terms_indicator'] );	//Primary Key
				$old = $cart->pos['pos_account'];
				//display_notification( __FILE__ . ":" . __LINE__ . " Terms: " . $cart->payment_terms['terms_indicator'] . " and Account: " . $cart->pos['pos_account'] . " And CASH SALE: " . $cart->payment_terms['cash_sale'] );
				try {
					//display_notification( __FILE__ . ":" . __LINE__  );
					$pay->select_row();	//Primary Key is set.
					$cart->pos['pos_account'] = $pay->get( "bank_account" );
				} catch( Exception $e )
				{
					//display_notification( __FILE__ . ":" . __LINE__  );
					//var_dump( $pay );
					if( KSF_FIELD_NOT_SET == $e->getCode() )
					{
						//the bank_account does not match a config in our module so no redirect
						if( FALSE != strpos( $e->getMessage(), "bank_account" ) )
							return true;
					}
					else
						display_error( __METHOD__ . ":" . __LINE__ . " " . $e->getMessage() );
				}
				if( ! $cart->payment_terms['cash_sale'] )
				{
					//Generate a payment
					//display_notification( __FILE__ . ":" . __LINE__  );
					$cart->payment_terms['cash_sale'] = 1;
				}
				//display_notification( __FILE__ . ":" . __LINE__ . "NEW Terms: " . $cart->payment_terms['terms_indicator'] . " and Account: " . $cart->pos['pos_account'] . " And CASH SALE: " . $cart->payment_terms['cash_sale'] );
				return true;
			}
			else
			{
				//display_notification( __FILE__ . ":" . __LINE__ . "Didn't require_once model file" );
			}

		}
		else
		{
			//display_notification( __FILE__ . ":" . __LINE__ . ":: trans_type != SALESINVOICE:: " . $trans_type . " NOT touching anything!!" );
		}

	}
}
