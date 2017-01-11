<?php
/*
Plugin Name: Jigoshop Cardstream
Plugin URI:
Description: This plugin extends the Jigoshop payment gateways to add the Cardstream payment gateway to Jigoshop
Version: 1.0
Author: Cardstream
Author URI: https://www.cardstream.com
*/
/**
 * Cardstream Hosted Gateway
 *
 * DISCLAIMER
 * Do not edit or add directly to this file if you wish to upgrade Jigoshop to newer
 * versions in the future. If you wish to customise Jigoshop core for your needs,
 * please use our GitHub repository to publish essential changes for consideration.
 *
 * @package             Jigoshop
 * @category            Checkout
 * @author              Cardstream
 * @copyright           Copyright © 2016-Present Cardstream.
 * @license             GNU General Public License v3
 */

// Commented out due to image size issue for now
// Define file location for logo on checkout page
// define('JIGOSHOP_PLUGIN_LOGO_URL', 'https://www.cardstream.com/img/logo.png');

function jigoshop_cardstream_payment_gateway() {

  // if the Jigoshop payment gateway class is not available, do nothing
  if (!class_exists('jigoshop_payment_gateway')) return;

  class jigoshop_cardstream_hosted extends jigoshop_payment_gateway {

    const GATEWAY =  'cardstream';
    const MERCHANTID = '100001';
    const SIGNATUREKEY = 'Circle4Take40Idea';
    const HOSTEDURL = 'https://gateway.cardstream.com/hosted/';

    /**
     * Construct Cardstream Hosted on checkout page
     */
    public function __construct() {

      parent::__construct();

      $options = Jigoshop_Base::get_options();

      $this->id = self::GATEWAY . '_hosted';
      // $this->icon = apply_filters('jigoshop_' . self::GATEWAY . '_icon', JIGOSHOP_PLUGIN_LOGO_URL);
      $this->has_fields = false;

      $this->enabled = $options->get_option('jigoshop_' . self::GATEWAY . '_enabled');
      $this->title = $options->get_option('jigoshop_' . self::GATEWAY . '_title');
      $this->merchantID = $options->get_option('jigoshop_' . self::GATEWAY . '_merchant_id');
      $this->signatureKey = $options->get_option('jigoshop_' . self::GATEWAY . '_signature_key');
			$this->description = $options->get_option('jigoshop_' . self::GATEWAY . '_description');
      $this->formResponsive = $options->get_option('jigoshop_' . self::GATEWAY . '_form_responsive');
      $this->customURL = $options->get_option('jigoshop_' . self::GATEWAY . '_custom_form');
      $this->currencyCode = $options->get_option('jigoshop_' . self::GATEWAY . '_currency_code');
      $this->countryCode = $options->get_option('jigoshop_' . self::GATEWAY . '_country_code');

      add_action('jigoshop_update_options', array($this, 'process_admin_options'));
      add_action('receipt_' . self::GATEWAY . '_hosted', array($this, 'receipt_page'));
      add_action('jigoshop_api_js_gateway_cardstream_hosted', array($this, 'process_response'));
      add_action('jigoshop_api_js_gateway_cardstream_callback', array($this, 'process_callback'));

    }

    /**
     * Sets the gateway configuration options
     *
     * @return array
     */
    protected function get_default_options() {

      $options = array();

      $options[] = array(
        'name' => __(ucfirst(self::GATEWAY) . ' Hosted', 'jigoshop'),
        'type' => 'title',
        'desc' => __('Allows the use of the ' . ucfirst(self::GATEWAY) . ' Hosted integration method to take payments through your website', 'jigoshop'),
      );

      $options[] = array(
        'name' => __('Enable ' . ucfirst(self::GATEWAY) . ' Hosted', 'jigoshop'),
        'desc' => '',
        'tip' => '',
        'id' => 'jigoshop_' . self::GATEWAY . '_enabled',
        'std' => 'no',
        'type' => 'checkbox',
        'choices' => array(
          'no' => __('No', 'jigoshop'),
          'yes' => __('Yes', 'jigoshop'),
        )
      );

      $options[] = array(
        'name' => __('Method Title', 'jigoshop'),
        'desc' => '',
        'tip' => __('This controls the title the customer will see during checkout', 'jigoshop'),
        'id' => 'jigoshop_'  . self::GATEWAY . '_title',
        'std' => __(ucfirst(self::GATEWAY) . ' Hosted', 'jigoshop'),
        'type' => 'text',
      );

      $options[] = array(
        'name' => __('Merchant ID', 'jigoshop'),
        'desc' => '',
        'tip' => __('This field is for your ' . ucfirst(self::GATEWAY) . ' Merchant ID, this will be provided in your live letter', 'jigoshop'),
        'id' => 'jigoshop_' . self::GATEWAY . '_merchant_id',
        'std' => __(self::MERCHANTID, 'jigoshop'),
        'type' => 'text',
      );

      $options[] = array(
        'name' => __('Signature Key', 'jigoshop'),
        'desc' => '',
        'tip' => __('This field is for your ' . ucfirst(self::GATEWAY) . ' Signature Key, this will be provided in your live letter', 'jigoshop'),
        'id' => 'jigoshop_' . self::GATEWAY . '_signature_key',
        'std' => __(self::SIGNATUREKEY, 'jigoshop'),
        'type' => 'text',
      );

      $options[] = array(
        'name' => __('Description', 'jigoshop'),
        'desc' => '',
        'tip' => __('This controls the description which the user sees during checkout.', 'jigoshop'),
        'id' => 'jigoshop_' . self::GATEWAY . '_description',
        'std' => __('Pay securely via Credit / Debit Card with ' . ucfirst(self::GATEWAY), 'jigoshop'),
        'type' => 'longtext',
      );

      $options[] = array(
        'name' => __('Responsive Form', 'jigashop'),
        'desc' => '',
        'tip' => __('Enable the payment form to be responsive', 'jigoshop'),
        'id' => 'jigoshop_' . self::GATEWAY . '_form_responsive',
        'std' => 'no',
        'type' => 'select',
        'choices' => array(
          'no' => __('No', 'jigoshop'),
          'yes' => __('Yes', 'jigoshop'),
        )
      );

      $options[] = array(
        'name' => __('Custom Form', 'jigashop'),
        'desc' => '',
        'tip' => __('Enter your custom form URL, if you don\'t have one leave it as the default'),
        'id' => 'jigoshop_' . self::GATEWAY . '_custom_form',
        'std' => __(self::HOSTEDURL, 'jigoshop'),
        'type' => 'longtext',
      );

      $options[] = array(
        'name' => __('Currency Code', 'jigoshop'),
        'desc' => '',
        'tip' => __('The currency code your transactions will be in', 'jigoshop'),
        'id' => 'jigoshop_' . self::GATEWAY . '_currency_code',
        'std' => __('826', 'jigoshop'),
        'type' => 'text',
      );

      $options[] = array(
        'name' => __('Country Code', 'jigoshop'),
        'desc' => '',
        'tip' => __('The country code of your store', 'jigoshop'),
        'id' => 'jigoshop_' . self::GATEWAY . '_country_code',
        'std' => __('826', 'jigoshop'),
        'type' => 'text',
      );

      return $options;
    }

    /**
     * Store configuration options in database
     */
    public function process_admin_options() {
      if(isset($_POST['jigoshop_' . self::GATEWAY . '_enabled'])) update_option('jigoshop_' . self::GATEWAY . '_enabled', jigowatt_clean($_POST['jigoshop_' . self::GATEWAY . '_enabled'])); else @delete_option('jigoshop_' . self::GATEWAY . '_enabled');
      if(isset($_POST['jigoshop_' . self::GATEWAY . '_title'])) update_option('jigoshop_' . self::GATEWAY . '_title', jigowatt_clean($_POST['jigoshop_' . self::GATEWAY . '_title'])); else @delete_option('jigoshop_' . self::GATEWAY . '_title');
      if(isset($_POST['jigoshop_' . self::GATEWAY . '_description'])) update_option('jigoshop_' . self::GATEWAY . '_description', jigowatt_clean($_POST['jigoshop_' . self::GATEWAY . '_description'])); else @delete_option('jigoshop_' . self::GATEWAY . '_description');
    }

    /**
     * There are payment fields, but a signature needs to be created before contacting Cardstream
     * This is displayed on the Checkout for the gateway description when selected
	   */
    public function payment_fields() {
  		if ($this->description)
  			echo wpautop(wptexturize($this->description));
	  }

    /**
     * Process the order and return the result
     *
     * @return array
     */
    public function process_payment($order_id) {
      $order = new jigoshop_order($order_id);

      return array(
				'result' => 'success',
				'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(jigoshop_get_page_id('pay'))))
			);
    }

    /**
     * Receipt page call Cardstream form creation function
     */
    public function receipt_page($order) {
			echo '<p>' . __('Thank you for your order, please click the button below to pay with ' . ucfirst(self::GATEWAY), 'jigoshop') . '</p>';
			echo $this->create_hosted_form($order);
		}

    /**
     * Prepare transaction to be posted to Cardstream
     *
     * @return form
     */
    protected function create_hosted_form($order_id) {
      $order = new jigoshop_order($order_id);

      $amount = $order->order_total * 100;

      $request = array(
        'merchantID' => $this->merchantID,
        'action' => 'SALE',
        'amount' => $amount,
        'orderRef' => $order_id,
        'transactionUnique' => $order_id,
        //'currencyCode' => (isset($this->currencyCode) && !empty($this->currencyCode) ? $this->$currencyCode : )
        'customerName' => $order->billing_first_name . ' ' . $order->billing_last_name,
        'customerAddress' => $order->billing_address_1 . ' '
        . $order->billing_city,
        'customerPostcode' => $order->billing_postcode,
        'customerPhone' => $order->billing_phone,
        'customerEmail' => $order->billing_email,
        'redirectURL' => add_query_arg('js-api', 'JS_Gateway_Cardstream_Hosted', home_url('/')),
        'callbackURL' => add_query_arg('js-api', 'JS_Gateway_Cardstream_Hosted', home_url('/')),
        'formResponsive' => (isset($this->formResponsive) && $this->formResponsive == "no" ? "N" : "Y"),
        'merchantData' => 'Jigoshop 1.8',
      );

      $request['signature'] = $this->createSignature($request, $this->signatureKey) . '|' . implode(',', array_keys($request));

      $form = '<form action="' . (isset($this->customURL) && !empty($this->customURL) ? $this->customURL : self::HOSTEDURL) . '" method="post">';

      foreach ($request as $key => $value) {
        $form .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
      }

      $form .= '<input type="submit" class="button-alt" id="submit_' . self::GATEWAY . '_payment_form" value="Pay securely via ' . ucfirst(self::GATEWAY) . '" />';

      return $form;

    }

    public function process_response() {

      $orderNotes = 'Response Message: ' . $_POST['responseMessage'] . '<br/>';
      $orderNotes .= 'Transaction ID: ' . $_POST['xref'];

      $order = new jigoshop_order( (int) $_POST['orderRef']);

      if (isset($_POST['responseCode'])) {
        if ($order->status !== 'completed') {
          if ($_POST['responseCode'] === '0') {

            $order->add_order_note(__(ucfirst(self::GATEWAY) . ' payment completed. <br/>' . $orderNotes, 'jigoshop'));
            $order->payment_complete();

            wp_safe_redirect(add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(jigoshop_get_page_id('thanks')))));

          } else {
            $order->update_status('pending', __(ucfirst(self::GATEWAY) . ' payment failed <br/>' . $orderNotes, 'jigoshop'));

            wp_safe_redirect(add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(jigoshop_get_page_id('cart')))));
          }
        } else {
          if ($_POST['responseCode'] === '0') {

            wp_safe_redirect(add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(jigoshop_get_page_id('thanks')))));

          } else {

            wp_safe_redirect(add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(jigoshop_get_page_id('cart')))));
          }
        }
      }
    }

    /**
     * Create the Cardstream signature based on the request
     *
     * @return Signature Hash (string)
     */
    public function createSignature($request, $key) {
      // Sort by field name
      ksort($request);

      // Create the URL encoded signature string
      $ret = http_build_query($request, '', '&');

      // Normalise all line endings (CRNL|NLCR|NL|CR) to just NL (%0A)
      $ret = str_replace(array('%0D%0A', '%0A%0D', '%0D'), '%0A', $ret);

      // Hash the signature string and the key together
      return hash('sha512', $ret . $key);
    }

  }

  /**
   * Add Cardstream to Jigashop gateways
   *
   * @return array
   */
  function add_cardstream_payment_gateway($methods) {
    $methods[] = 'jigoshop_cardstream_hosted';
    return $methods;
  }
  add_filter( 'jigoshop_payment_gateways', 'add_cardstream_payment_gateway', 55);
}
add_action( 'plugins_loaded', 'jigoshop_cardstream_payment_gateway', 0 );
