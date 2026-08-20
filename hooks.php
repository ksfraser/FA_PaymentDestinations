<?php
declare(strict_types=1);

define('SS_ksf_FA_PaymentDestinations', 111 << 8);

$autoload = dirname(__FILE__) . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

/**
 * Payment Destinations — redirect non-cash payments to per-type GL accounts.
 *
 * Intercepts ST_SALESINVOICE via db_prewrite, rewrites pos_account to the
 * bank account mapped to the current payment term, and forces cash_sale=1
 * so FA auto-generates a customer payment alongside the invoice.
 *
 * The mapping table 0_ksf_payment_destinations links FA payment_terms IDs
 * to FA bank_accounts IDs.
 */
class hooks_ksf_FA_PaymentDestinations extends hooks
{
    var $module_name = 'ksf_FA_PaymentDestinations';
    var $version     = '2.0.0';

    public function getModuleConstants(&$data, $opts = null)
    {
        $constants = [
            'KSF_PAYMENT_DESTINATIONS_MODULE' => $this->module_name,
        ];
        $data['constants'] = $constants;
        return $constants;
    }

    public function getModuleCapabilities(&$data, $opts = null)
    {
        $capabilities = [
            'payment_redirect' => [
                'description' => 'Redirect non-cash payments to per-type GL accounts via db_prewrite',
                'methods'     => ['db_prewrite'],
            ],
        ];
        $data['capabilities'] = $capabilities;
        return $capabilities;
    }

    public function hasCapability(&$data, $opts = null)
    {
        $capability = $opts['capability'] ?? $data['capability'] ?? null;
        if ($capability === null) {
            $data['has_capability'] = false;
            return false;
        }
        $has = in_array($capability, ['payment_redirect']);
        $data['has_capability'] = $has;
        return $has;
    }

    public function respondToCapabilityRequest(&$data, $opts = null)
    {
        $request = $opts['request'] ?? $data['request'] ?? 'capabilities';
        switch ($request) {
            case 'capabilities':
                return $this->getModuleCapabilities($data, $opts);
            case 'constants':
                return $this->getModuleConstants($data, $opts);
            case (strpos($request, 'has:') === 0):
                return $this->hasCapability($data, ['capability' => substr($request, 4)]);
            default:
                return null;
        }
    }

    function install_options($app)
    {
        switch ($app->id) {
            case 'GL':
            case 'orders':
                $app->add_rapp_function(
                    2,
                    _('Payment Destinations'),
                    'modules/FA_PaymentDestinations/ksf_payment_destinations.php',
                    'SA_ksf_FA_PaymentDestinations'
                );
        }
    }

    function install_access()
    {
        global $security_sections, $security_areas;

        $security_sections[SS_ksf_FA_PaymentDestinations] = _("Payment Destinations");

        $security_areas['SA_ksf_FA_PaymentDestinations'] = [
            SS_ksf_FA_PaymentDestinations | 108,
            _("Payment Destinations")
        ];
        $security_areas['SA_ksf_FA_PaymentDestinationsVIEW'] = [
            SS_ksf_FA_PaymentDestinations | 1,
            _("View Payment Destinations")
        ];

        return [$security_areas, $security_sections];
    }

    function activate_extension($company, $check_only = true)
    {
        $updates = [];
        if (file_exists(dirname(__FILE__) . '/sql/install.sql')) {
            $updates['install.sql'] = [$this->module_name];
        }
        if (!empty($updates)) {
            return $this->update_databases($company, $updates, $check_only);
        }
        return true;
    }

    /**
     * Intercept ST_SALESINVOICE before write.
     *
     * FA direct-invoice lifecycle: Sales Order (30) → Delivery (13) → Invoice (10) → Payment (12).
     * The Payment step only fires if cash_sale=1.
     *
     * This hook:
     * 1. Looks up the payment term in 0_ksf_payment_destinations
     * 2. If found: rewrites $cart->pos['pos_account'] to the mapped bank account
     * 3. Forces cash_sale=1 so FA auto-generates a customer payment
     *
     * For Square-Invoice destinations (square_invoice*), ksf_FA_Square's db_prewrite
     * should fire FIRST and set cash_sale=0 to suppress the auto-payment.
     */
    function db_prewrite(&$cart, $trans_type)
    {
        if ($trans_type !== ST_SALESINVOICE) {
            return;
        }

        if (!isset($cart->payment_terms['terms_indicator'])) {
            return;
        }

        require_once(__DIR__ . '/class.ksf_payment_destinations_model.php');

        $pay = new ksf_payment_destinations_model(ksf_payment_destinations_PREFS, $this);
        $pay->set_var('payment_term', $cart->payment_terms['terms_indicator']);

        try {
            $pay->select_row();
            $cart->pos['pos_account'] = $pay->get('bank_account');
        } catch (\Exception $e) {
            if (KSF_FIELD_NOT_SET == $e->getCode()) {
                // No mapping for this payment term — let FA handle it normally
                return true;
            }
            display_error(__METHOD__ . ":" . __LINE__ . " " . $e->getMessage());
            return true;
        }

        if (!$cart->payment_terms['cash_sale']) {
            $cart->payment_terms['cash_sale'] = 1;
        }

        return true;
    }
}
