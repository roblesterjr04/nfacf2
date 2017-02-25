<?php

class AcceptStripePaymentsShortcode {

    var $AcceptStripePayments = null;

    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected static $instance = null;
    protected static $payment_buttons = array();

    function __construct() {
        $this->AcceptStripePayments = AcceptStripePayments::get_instance();

        add_shortcode('accept_stripe_payment', array(&$this, 'shortcode_accept_stripe_payment'));
        add_shortcode('accept_stripe_payment_checkout', array(&$this, 'shortcode_accept_stripe_payment_checkout'));
        if (!is_admin()) {
            add_filter('widget_text', 'do_shortcode');
        }

    }

    public function interfer_for_redirect() {
        global $post;
        if (!is_admin()) {
            if (has_shortcode($post->post_content, 'accept_stripe_payment_checkout')) {
                $this->shortcode_accept_stripe_payment_checkout();
                exit;
            }
        }
    }

    /**
     * Return an instance of this class.
     *
     * @since     1.0.0
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance() {

        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    function shortcode_accept_stripe_payment($atts) {

        extract(shortcode_atts(array(
            'name' => 'Item Name',
            'price' => '0',
            'quantity' => '1',
            'description' => '',
            'url' => '',
            'item_logo' => '',
            'billing_address' => '',
            'shipping_address' => '',
            'currency' => $this->AcceptStripePayments->get_setting('currency_code'),
            'button_text' => $this->AcceptStripePayments->get_setting('button_text'),
        ), $atts));

        if (!empty($url)) {
            $url = base64_encode($url);
        }
        else{
            $url = '';
        }
        if(!is_numeric($quantity)){
            $quantity = strtoupper($quantity);
        }
	if($quantity == "N/A"){
            $quantity = "NA";
        }
        $button_id = 'stripe_button_' . count(self::$payment_buttons);
        self::$payment_buttons[] = $button_id;
        $paymentAmount = ("$quantity" === "NA" ? $price : ($price * $quantity));
        $priceInCents = $paymentAmount * 100 ;

        $output = "<form action='" . $this->AcceptStripePayments->get_setting('checkout_url') . "' METHOD='POST'> ";

        $output .= "<script src='https://checkout.stripe.com/checkout.js' class='stripe-button'
          data-key='".$this->AcceptStripePayments->get_setting('api_publishable_key')."'
          data-panel-label='Pay'
          data-amount='{$priceInCents}' 
          data-name='{$name}'";
	if (isset($description) && !empty($description)){
            //Use the custom description specified in the shortcode.
            $output .= "data-description='{$description}'";
        }
	else{
            //Create a description using quantity and payment amount
            $output .= "data-description='{$quantity} piece".($quantity <> 1 ? "s" : "")." for {$paymentAmount} {$currency}'";
        }
        $output .="data-label='{$button_text}'";
        $output .="data-currency='{$currency}'";
        if(!empty($item_logo)){//Show item logo/thumbnail in the stripe payment window
            $output .="data-image='{$item_logo}'";
        }        
        if(!empty($billing_address)){//Show billing address in the stipe payment window
            $output .="data-billingAddress='true'";
        }
        if(!empty($shipping_address)){//Show shipping address in the stipe payment window
            $output .="data-shippingAddress='true'";
        }
        $output .= apply_filters('asp_additional_stripe_checkout_data_parameters', '');//Filter to allow the addition of extra data parameters for stripe checkout.
        $output .="></script>";
        
        $trans_name = 'stripe-payments-' . sanitize_title_with_dashes($name);//Create key using the item name.
        set_transient( $trans_name, $price, 2 * 3600 );//Save the price for this item for 2 hours.
        $output .= wp_nonce_field('stripe_payments', '_wpnonce', true, false);
        $output .= "<input type='hidden' value='{$name}' name='item_name' />";
        $output .= "<input type='hidden' value='{$price}' name='item_price' />";
        $output .= "<input type='hidden' value='{$quantity}' name='item_quantity' />";
        $output .= "<input type='hidden' value='{$currency}' name='currency_code' />";
        $output .= "<input type='hidden' value='{$url}' name='item_url' />";
        $output .= "<input type='hidden' value='{$description}' name='charge_description' />";//
        $output .= "</form>";

        return $output;
    }

    /*
     * This shortcode processes the payment data after the payment.
     */
    public function shortcode_accept_stripe_payment_checkout($atts = array()) {
        
        extract(shortcode_atts(array(
            'currency' => $this->AcceptStripePayments->get_setting('currency_code'),
            ), $atts)
        );
        //Check nonce
        $nonce = $_REQUEST['_wpnonce'];
        if ( !wp_verify_nonce($nonce, 'stripe_payments')){
            //The user is likely directly viewing this page.
            echo '<div style="background: #FFF6D5; border: 1px solid #D1B655; color: #3F2502; margin: 10px 0px; padding: 10px;">';
            echo '<p>The message in this box is ONLY visible to you because you are viewing this page directly. Your customers won\'t see this message.</p>';
            echo '<p>Your customers will get sent to this page after the transaction. This page will work correctly when customers get redirected here AFTER the payment.</p>';
            echo '<p>You can edit this page from your admin dashboard and add extra message that your customers will see after the payment.</p>';
            echo '<p>Nonce Security Check Failed!</p>';
            echo '</div>';
            return;
        }
        if (!isset($_POST['item_name']) || empty($_POST['item_name'])) {
            echo ('Invalid Item name');
            return;
        }
        if (!isset($_POST['stripeToken']) || empty($_POST['stripeToken'])) {
            echo ('Invalid Stripe Token');
            return;
        }
        if (!isset($_POST['stripeTokenType']) || empty($_POST['stripeTokenType'])) {
            echo ('Invalid Stripe Token Type');
            return;
        }
        if (!isset($_POST['stripeEmail']) || empty($_POST['stripeEmail'])) {
            echo ('Invalid Request');
            return;
        }
        if (!isset($_POST['currency_code']) || empty($_POST['currency_code'])) {
            echo ('Invalid Currency Code');
            return;
        }
        
        $item_name = sanitize_text_field($_POST['item_name']);
        $stripeToken = sanitize_text_field($_POST['stripeToken']);
        $stripeTokenType = sanitize_text_field($_POST['stripeTokenType']);  
        $stripeEmail = sanitize_email($_POST['stripeEmail']);        
        $item_quantity = sanitize_text_field($_POST['item_quantity']);
        $item_url = sanitize_text_field($_POST['item_url']);
        $charge_description = sanitize_text_field($_POST['charge_description']);
        
        //$item_price = sanitize_text_field($_POST['item_price']);
        $trans_name = 'stripe-payments-' . sanitize_title_with_dashes($item_name);
        $item_price = get_transient($trans_name);//Read the price for this item from the system.
        if(!is_numeric($item_price)){
            echo ('Invalid item price');
            return;
        }
        $currency_code = sanitize_text_field($_POST['currency_code']);
        $paymentAmount = ($item_quantity !== "NA" ? ($item_price * $item_quantity) : $item_price);

        $currencyCodeType = strtolower($currency_code);


        Stripe::setApiKey($this->AcceptStripePayments->get_setting('api_secret_key'));


        $GLOBALS['asp_payment_success'] = false;

        ob_start();
        try {

            $customer = Stripe_Customer::create(array(
                'email' => $stripeEmail,
                'card'  => $stripeToken
            ));

            $charge = Stripe_Charge::create(array(
                'customer' => $customer->id,
                'amount'   => $paymentAmount*100,
                'currency' => $currencyCodeType,
                'description' => $charge_description,
            ));
            
            //Grab the charge ID and set it as the transaction ID.
            $txn_id = $charge->id;//$charge->balance_transaction;
                    
            //Core transaction data
            $data = array();
            $data['item_name'] = $item_name;
            $data['stripeToken'] = $stripeToken;
            $data['stripeTokenType'] = $stripeTokenType;
            $data['stripeEmail'] = $stripeEmail;
            $data['item_quantity'] = $item_quantity;
            $data['item_price'] = $item_price;
            $data['currency_code'] = $currency_code;
            $data['txn_id'] = $txn_id;//The Stripe charge ID
            $data['charge_description'] = $charge_description;
            
            $post_data = array_map('sanitize_text_field', $data);
            
            //Billing address data (if any)
            $billing_address = "";
            $billing_address .= sanitize_text_field($_POST['stripeBillingName'])."\n";
            $billing_address .= sanitize_text_field($_POST['stripeBillingAddressLine1'])." ".sanitize_text_field($_POST['stripeBillingAddressApt'])."\n";
            $billing_address .= sanitize_text_field($_POST['stripeBillingAddressZip'])."\n";
            $billing_address .= sanitize_text_field($_POST['stripeBillingAddressCity'])."\n";
            $billing_address .= sanitize_text_field($_POST['stripeBillingAddressState'])."\n";
            $billing_address .= sanitize_text_field($_POST['stripeBillingAddressCountry'])."\n";            
            $post_data['billing_address'] = $billing_address;
            
            //Shipping address data (if any)
            $shipping_address = "";
            $shipping_address .= sanitize_text_field($_POST['stripeShippingName'])."\n";
            $shipping_address .= sanitize_text_field($_POST['stripeShippingAddressLine1'])." ".sanitize_text_field($_POST['stripeShippingAddressApt'])."\n";
            $shipping_address .= sanitize_text_field($_POST['stripeShippingAddressZip'])."\n";
            $shipping_address .= sanitize_text_field($_POST['stripeShippingAddressCity'])."\n";
            $shipping_address .= sanitize_text_field($_POST['stripeShippingAddressState'])."\n";
            $shipping_address .= sanitize_text_field($_POST['stripeShippingAddressCountry'])."\n";           
            $post_data['shipping_address'] = $shipping_address;

            //Insert the order data to the custom post
            $order = ASPOrder::get_instance();
            $order->insert($post_data, $charge);

            //Action hook with the checkout post data parameters.
            do_action('asp_stripe_payment_completed', $post_data, $charge);
            
            //Action hook with the order object.
            do_action('AcceptStripePayments_payment_completed', $order, $charge);

            $GLOBALS['asp_payment_success'] = true;
            $item_url = base64_decode($item_url);

        }catch (Exception $e) {
            //If the charge fails (payment unsuccessful), this code will get triggered.
            if(!empty($charge->failure_code))
                $GLOBALS['asp_error'] = $charge->failure_code.": ".$charge->failure_message;
            else {
                $GLOBALS['asp_error'] =  $e->getMessage();
            }
        }

        //Show the "payment success" or "payment failure" info on the checkout complete page.
        include dirname(dirname(__FILE__)) . '/views/checkout.php';

        return ob_get_clean();

    }

}
