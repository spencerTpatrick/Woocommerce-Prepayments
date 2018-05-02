<?php

/*
   Plugin Name: Woocommerce Prepayments by MeccaProduction, LLC
   Plugin URI: https://meccaproduction.com/woocomerce-prepayments
   Description: Extend the Woocommerce Subscription plugin and get your payments faster by allowing your customers to prepay subscriptions.
   Version: 1.0
   Author: Mecca Product, LLC
   Author URI: https://www.meccaproduction.com
   License: GPL2
*/

/**********************************************************************************************************/
/**********************************************************************************************************/
/********************************************** PREPAYMENTS ***********************************************/
/**********************************************************************************************************/
/**********************************************************************************************************/

// exit if file accessed directly
defined( 'ABSPATH' ) or exit;

if ( !class_exists( 'WC_Admin_Settings_Subscription_Prepayments' ) ) {

	class WC_Admin_Settings_Subscription_Prepayments {

	    public static function init() {
	        add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
	        add_action( 'woocommerce_settings_tabs_settings_tab_prepayments', __CLASS__ . '::settings_tab' );
	        add_action( 'woocommerce_update_options_settings_tab_prepayments', __CLASS__ . '::update_settings' );
	    }
	    public static function add_settings_tab( $settings_tabs ) {
	        $settings_tabs['settings_tab_prepayments'] = __( 'Prepayments', 'woocommerce-settings-tab-prepayments' );
	        return $settings_tabs;
	    }

	    public static function settings_tab() {
	        woocommerce_admin_fields( self::get_prepayment_settings() );
	    }

	    public static function update_settings() {
	        woocommerce_update_options( self::get_prepayment_settings() );
	    }

	    public static function get_prepayment_settings() {
	        $settings = array(
	            'section_title' => array(
	                'name'     => __( 'Prepayments', 'woocommerce-settings-tab-prepayments' ),
	                'type'     => 'title',
	                'desc'     => '',
	                'id'       => 'wc_settings_prepayments_section_title'
	            ),
	            'prepayment_flag' => array(
	                'name' => __( 'Turn on Prepayments', 'woocommerce-settings-tab-prepayments' ),
	                'type' => 'checkbox',
	                'desc' => __( 'This will enable prepayments to be made on the site', 'woocommerce-settings-tab-prepayments' ),
	                'id'   => 'wc_settings_prepayments_flag'
	            ),
	            'prepayment_product_id' => array(
	                'name' => __( 'Prepayment Product ID', 'woocommerce-settings-tab-prepayments' ),
	                'type' => 'text',
	                'desc' => __( 'You must create a product to hold the prepayment amount.  The price and name will be overriden.  Make sure the option for sold individually is checked under the Inventory data settings.', 'woocommerce-settings-tab-prepayments' ),
	                'id'   => 'wc_settings_prepayments_product_id'
	            ),
	            'section_end' => array(
	                 'type' => 'sectionend',
	                 'id' => 'wc_settings_prepayments_section_end'
	            )
	        );
	        return apply_filters( 'wc_settings_tab_prepayments_settings', $settings );
	    }

	}

	// Check if woocommerce and woocommerce-subscriptions are active
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	  if ( in_array( 'woocommerce-subscriptions/woocommerce-subscriptions.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	    WC_Admin_Settings_Subscription_Prepayments::init();
	  }
	}

} // if ( !class_exists( 'WC_Admin_Settings_Subscription_Prepayments' ) ) {

class WC_Prepayment_Setup {
	
	public static $subscription_id; 
	public static $balance_remaining;

	public function init($subscription_id)
    {

		self::$subscription_id = $subscription_id;

		add_action('woocommerce_my_subscriptions_actions', __CLASS__ . '::draw_subscription_prepay_buttons', 10, 1);

    }

    public function draw_prepayment_remaining() {

    }

    public function draw_prepayment_partial(){

    }

    public function get_balance_remaining() {
    	return self::$balance_remaining;
    }

    public function calculate_balance_remaining(){
    	$subscription = new WC_Subscription( self::$subscription_id );
		$subscription_products = $subscription->get_items();

		$balance_paid = 0;
		$balance_remaining = 0;

		foreach($subscription_products as $subscription_product) {
		$product_id = $subscription_product['product_id'];
		}

		$balance_paid = get_subscription_balance_paid(self::$subscription_id);

		$subscription_length = WC_Subscriptions_Product::get_length($product_id);

		$balance_remaining = $subscription_length * $subscription->get_total() - $balance_paid;

		self::$balance_remaining = $balance_remaining;
    }

    public function draw_subscription_prepay_buttons(){ 

		if(WC_Admin_Settings::get_option('wc_settings_prepayments_flag') == 'yes') {

			$balance_remaining = get_subscription_balance_remaining(self::$subscription_id);

		}

		self::$balance_remaining = $balance_remaining;

		echo self::$balance_remaining;
	}
}

$subscription_id = '56';


$prepayment_setup = WC_Prepayment_Setup::init($subscription_id);
echo WC_Prepayment_Setup::get_balance_remaining(); 

function get_subscription_balance_remaining($subscription_id){

  $subscription = new WC_Subscription($subscription_id);
  $subscription_products = $subscription->get_items();

  $balance_paid = 0;
  $balance_remaining = 0;

  foreach($subscription_products as $subscription_product) {
    $product_id = $subscription_product['product_id'];
  }

  $balance_paid = get_subscription_balance_paid($subscription_id);

  $subscription_length = WC_Subscriptions_Product::get_length($product_id);

  $balance_remaining = $subscription_length * $subscription->get_total() - $balance_paid;

  return $balance_remaining;

}

function get_subscription_balance_paid($subscription_id) {

  $subscription = new WC_Subscription($subscription_id);
  $order_ids = $subscription->get_related_orders();

  $balance_paid = 0;

  foreach($order_ids as $id) {
    $order = wc_get_order($id);

    If($order->get_status() == 'processing' || $order->get_status() == 'completed'){
      $balance_paid += $order->get_total();
    }

  }

  return $balance_paid;

}

// Draws prepay buttons on my-subscriptions.php
// Two types: remaining and partial
add_action('woocommerce_my_subscriptions_actions', 'draw_subscription_prepay_buttons', 10, 1);
function draw_subscription_prepay_buttons($subscription){ 

  $allow_prepayments = WC_Admin_Settings::get_option('wc_settings_prepayments_flag');

  $subscription_id = $subscription->id;

  if($allow_prepayments == 'yes') {

    $balance_remaining = get_subscription_balance_remaining($subscription_id);

    $installment_amount = $subscription->get_total();

    $payments_remaining = floor($balance_remaining/$installment_amount);

    if($subscription->get_status() == 'active' || $subscription->get_status() == 'on-hold') { ?>

      <div id="prepay-remaining-section">
        <form action="<?php echo esc_url( get_permalink( woocommerce_get_page_id( 'cart' ) ) ) ?>" method="post">
          <input type="hidden" name="subscription_id" id="subscription_id" value="<?php echo $subscription_id; ?>">
          <input type="hidden" name="prepay_type" id="prepay_type" value="remaining">
          <input type="hidden" name="remaining" id="remaining" value="<?php echo $balance_remaining ?>">
          <button type="submit" class="button view">Pay Remaining Balance</button>
        </form>
      </div>
      <?php if ($balance_remaining > $installment_amount) { ?>
      <div id="prepay-partial-section">
        <button class="button view prepay-partial-toggle">Prepay Future Installments</button>
        <form action="<?php echo esc_url( get_permalink( woocommerce_get_page_id( 'cart' ) ) ) ?>" method="post" id="prepay-custom-form-<?php echo $subscription_id ?>" class="prepay-partial-form">
          <input type="hidden" name="subscription_id" id="subscription_id" value="<?php echo $subscription_id ?>">
          <input type="hidden" name="prepay_type" id="prepay_type" value="partial">
          <!-- <input onchange="checkMaxPrepayAmount(this, <?php echo $balance_remaining ?>)" type="text" name="remaining" id="remaining" max="<?php echo $balance_remaining ?>"> 
          <span class="prepay-error"></span> -->
          <select name="remaining" id="remaining">
            <?php 
              for ($x = 1; $x <= $payments_remaining; $x++) {
                $prepay_amount = $installment_amount * $x;

                $plural = ( $x == 1  ? '' : 's');

                echo '<option value="' . $prepay_amount . '">' . $x . ' installment' . $plural . ' in advanced</option>';

              }
            ?>
          </select>
          <button type="submit" class="button view">Submit</button>
        </form>
      </div> <?php
      } //if $installment_amount >= $balance_remaining {

    } //If($subscription_status == 'active' || $subscription_status == 'on-hold') {
  } //if ($allow_prepayments == True) {
}

// Adding custom javascript to toggle prepay buttons on my-subscriptions.php
add_action( 'wp_enqueue_scripts', 'show_hide_prepay_custom_amount_form' );
function show_hide_prepay_custom_amount_form(){
    wp_enqueue_script('show_hide_prepay_custom_amount_form', get_stylesheet_directory_uri().'/assets/js/prepay.js',array('jquery'), '1', true);
}

// Adding the prepay product to the cart
// Product ID needs to be the ID of the dummy product you are adding
add_action( 'template_redirect', 'add_product_to_cart' );
function add_product_to_cart() {
  if ( ! is_admin() ) {

    If (! empty($_POST['subscription_id'])) {
      WC()->session->set( 'subscription_id' , sanitize_text_field( $_POST['subscription_id'] ) );
      WC()->session->set( 'remaining_balance' , sanitize_text_field( $_POST['remaining'] ) );
      WC()->session->set( 'prepay_type' , sanitize_text_field( $_POST['prepay_type'] ) );
      WC()->session->set( 'prepay_next_payment_date' , get_prepay_next_payment_date(sanitize_text_field( $_POST['subscription_id'] ), sanitize_text_field( $_POST['prepay_type'] )) );
    }

    $subscription_id = WC()->session->get( 'subscription_id' );
    $custom_price = WC()->session->get( 'remaining_balance' );
    $prepay_type = WC()->session->get( 'prepay_type' );

    If (! empty($subscription_id) && ! empty($custom_price)) {

      $product_id = WC_Admin_Settings::get_option('wc_settings_prepayments_product_id');
      //$product_id = get_subscription_product($subscription_id);
      $found = false;
      //check if product already in cart
      if ( sizeof( WC()->cart->get_cart() ) > 0 ) {
        foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
          $_product = $values['data'];
          if ( $_product->id == $product_id )
            $found = true;
        }
        // if product not found, add it
        if ( ! $found )
          WC()->cart->add_to_cart( $product_id);
      } else {
        // if no products in cart, add it
        WC()->cart->add_to_cart( $product_id);
      }
    }
  }
}

// Updating the product's price and name in the cart
add_action( 'woocommerce_before_calculate_totals', 'add_custom_price_and_name' );
function add_custom_price_and_name( $cart_object ) {

    $woo_ver = WC()->version; 

    $subscription_id = WC()->session->get( 'subscription_id' );
    $custom_price = WC()->session->get( 'remaining_balance' );
    $prepay_type = WC()->session->get( 'prepay_type' );

    If (! empty($subscription_id) && ! empty($custom_price)) {

      $target_product_id = WC_Admin_Settings::get_option('wc_settings_prepayments_product_id');
      //$target_product_id = get_subscription_product($subscription_id);
      foreach ( $cart_object->cart_contents as $value ) {
        $price = get_post_meta($value['product_id'] , '_subscription_price', true);

        if ( $value['product_id'] == $target_product_id && $price == 0) {
          if ($woo_ver < "3.0.0" && $woo_ver < "2.7.0") {
            $value['data']->price = $custom_price ;
          } else {
            $value['data']->set_price( $custom_price );
          }

          If($prepay_type == 'remaining'){
            $value['data']->set_name( 'Prepay Remaining Balance (' . wc_price($custom_price) . ') - Installment ' . $subscription_id);
          }else{
            $value['data']->set_name( 'Prepay ' . wc_price($custom_price) . ' - Installment ' . $subscription_id);
          }
          
          $value['subscription_id'] =  $subscription_id;
          $value['prepay_type'] =  $prepay_type; //Either Remaining or Partial
        }
      }
    }
}


function get_subscription_product($subscription_id) {
  $subscription = new WC_Subscription($subscription_id);

  $subscription_products = $subscription->get_items();

  foreach ( $subscription_products as $item ) {
      $subscription_product_id = $item->get_product_id();
  }

  return $subscription_product_id;
}

add_action('woocommerce_cart_item_removed', 'remove_prepay_session_variables');
function remove_prepay_session_variables(){
  
    WC()->session->__unset('subscription_id');
    WC()->session->__unset('remaining_balance');
    WC()->session->__unset('prepay_type');
    WC()->session->__unset('prepay_next_payment_date');

}

add_action('woocommerce_checkout_update_order_meta', 'update_related_order_meta', 1);
function update_related_order_meta($order_id){
  $prepay_subscription_id = WC()->session->get( 'subscription_id' );
  $prepay_amount = WC()->session->get( 'remaining_balance' );
  $prepay_type = WC()->session->get( 'prepay_type' );
  $prepay_next_payment_date = get_prepay_next_payment_date($prepay_subscription_id, $prepay_amount);

  If (! empty($prepay_subscription_id)) {
    update_post_meta( $order_id, '_subscription_renewal', sanitize_text_field( $prepay_subscription_id ) );
    update_post_meta( $order_id, 'prepay_subscription_id', sanitize_text_field( $prepay_subscription_id ) );
    update_post_meta( $order_id, 'prepay_amount', sanitize_text_field( $prepay_amount ) );
    update_post_meta( $order_id, 'prepay_type', sanitize_text_field( $prepay_type ) );
    update_post_meta( $order_id, 'prepay_next_payment_date', sanitize_text_field( $prepay_next_payment_date ) );
    update_post_meta( $order_id, 'prepay_completed', '0' );
  }
}

add_action('woocommerce_thankyou', 'update_prepay_subscription', 10, 1);
function update_prepay_subscription($prepayment_order_id) {

  $order_meta = get_post_meta($prepayment_order_id);

  $subscription_id = $order_meta['prepay_subscription_id'][0];
  $prepay_amount = $order_meta['prepay_amount'][0];
  $prepay_type = $order_meta['prepay_type'][0];
  $prepay_next_payment_date = $order_meta['prepay_next_payment_date'][0];

  $prepay_completed = $order_meta['prepay_completed'][0];

  If (! empty($subscription_id) && ! empty($prepay_type) && $prepay_completed != '1') {

    $subscription = new WC_Subscription($subscription_id);
    $date = date('Y-m-d H:i:s', strtotime('-4 hours'));

    $status = $subscription->get_status();

    $expired_payment_date = $subscription->get_date('end_date', 'gmt');

    $dates['next_payment'] = $prepay_next_payment_date;

    If($prepay_type == 'remaining'){
      If($status != 'expired'){
        $subscription->update_status('expired', 'Customer paid remaining balance on ' . $date);
        remove_prepay_session_variables();
      }
    } else { //Partial Payment
      if($expired_payment_date == $prepay_next_payment_date){
        $subscription->update_status('expired', 'Customer paid remaining balance (' . wc_price($prepay_amount) . ') on ' . $date);
      } else {
        $subscription->update_dates($dates);
        $subscription->add_order_note('Customer partial prepay of ' . wc_price($prepay_amount) . ' on ' . $date . '.  Billing halted until ' . $prepay_next_payment_date);
      }
      remove_prepay_session_variables();
      
    }

    complete_open_payments_on_subscription($subscription_id, $prepayment_order_id);
    update_post_meta( $prepayment_order_id, 'prepay_completed', '1' );

  }
}

function get_prepay_next_payment_date($subscription_id, $prepay_amount){

  $subscription = new WC_Subscription($subscription_id);

  $subscription_total = $subscription->get_total();
  $subscription_billing_period = $subscription->get_billing_period();

  $prepaid_skipped_periods = floor($prepay_amount / $subscription_total);

  $next_payment_date = $subscription->get_date('next_payment', 'gmt');

  $modified_next_payment_date = date('Y-m-d H:i:s', strtotime('+' . $prepaid_skipped_periods . ' ' . $subscription_billing_period ,strtotime($next_payment_date)));
  
  return $modified_next_payment_date;

}

function get_prepay_skipped_periods($prepayment_order_id){

  $order_meta = get_post_meta($prepayment_order_id);

  $subscription_id = $order_meta['prepay_subscription_id'][0];
  $prepay_amount = $order_meta['prepay_amount'][0];

  $subscription = new WC_Subscription($subscription_id);

  $subscription_total = $subscription->get_total();

  $prepaid_skipped_periods = floor($prepay_amount / $subscription_total);

  return $prepaid_skipped_periods;
}

function complete_open_payments_on_subscription($subscription_id, $prepayment_order_id) {

  $subscription = new WC_Subscription($subscription_id);
  $order_meta = get_post_meta($prepayment_order_id);

  $subscription_related_order_ids = $subscription->get_related_orders();

  $skipped_orders = get_prepay_skipped_periods($prepayment_order_id);

  foreach($subscription_related_order_ids as $id) {
    $order = wc_get_order($id);

    If($order->get_status() == 'pending'){
      If($skipped_orders != '0'){
        $order->update_status('cancelled');
        $order->add_order_note('Order cancelled due to prepayment from order ID ' . $prepayment_order_id . '.');
        $skipped_orders--;
      } else {
        exit();
      }
    }
  }

}


/**********************************************************************************************************/
/**********************************************************************************************************/
/********************************************** PREPAYMENTS ***********************************************/
/**********************************************************************************************************/
/**********************************************************************************************************/


?>