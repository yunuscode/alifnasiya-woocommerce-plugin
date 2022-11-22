<?php
/**
 * Plugin Name: Plugin for AlifNasiya
 * Plugin URI: https://alifnasiya.uz
 * Description: It is credit based payment module
 * Version: 1.0.0
 * Author: YunusCode
 * Author URI: https://github.com/yunuscode
 * Developer: Yunus
 * Developer URI: https://github.com/yunuscode
 * Text Domain: alifnasiya
 * Domain Path: /languages
 *
 * Woo: 12345:342928dfsfhsf8429842374wdf4234sfd
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'alifnasiya_add_gateway_class');
function alifnasiya_add_gateway_class($gateways)
{
    $gateways[] = 'WC_ALIF_Gateway'; // your class name is here
    return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action('plugins_loaded', 'alif_init_gateway_class');

function alif_init_gateway_class()
{
    class WC_ALIF_Gateway extends WC_Payment_Gateway
    {
        /**
         * Class constructor, more about it in Step 3
         */
        public function __construct()
        {
            $this->id = "alifnasiya";
            $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = 'Alifnasiya';
            $this->method_description = 'Alifnasiya orqali to\'lash';

            $this->supports = array(
                'products'
            );

            // Method with all the options fields
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
            $this->private_key = $this->get_option('private_key');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

        }

        /**
         * Plugin options, we deal with it in Step 3 too
         */
        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => 'Enable/Disable',
                    'label' => 'Enable Gateway',
                    'type' => 'checkbox',
                    'description' => '',
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => 'Title',
                    'type' => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default' => 'Alifnasiya',
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => 'Description',
                    'type' => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default' => 'Alifnasiya orqali to\'lash',
                ),
                'private_key' => array(
                    'title' => 'Live Private Key',
                    'type' => 'password'
                )
            );

        }

        /**
         * You will need it if you want your custom credit card form, Step 4 is about it
         */
        public function payment_fields()
        {

            if ($this->description) {
                echo wpautop(wp_kses_post($this->description));

            }

            echo '<div class="bacs-fields" style="padding:10px 0;">';

            woocommerce_form_field('alif_phone', array(
                'type' => 'text',
                'label' => __("Telefon raqamingiz", "woocommerce"),
                'class' => array('form-row-wide'),
                'required' => true,
            ), '');

            echo '<div>';
        }




        /*
         * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
         */
        public function payment_scripts()
        {
            if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
                return;
            }

            // if our payment gateway is disabled, we do not have to enqueue JS too
            if ('no' === $this->enabled) {
                return;
            }

            // no reason to enqueue JavaScript if API keys are not set
            if (empty($this->private_key)) {
                return;
            }

            // and this is our custom JS in your plugin directory that works with token.js
            wp_register_script('woocommerce_alif', plugins_url('alif.js', __FILE__), array('jquery', 'alif_js'));

            // in most payment processors you have to use PUBLIC KEY to obtain a token
            wp_localize_script(
                'woocommerce_alif',
                'alif_params'
            );



            wp_enqueue_script('woocommerce_alif');

        }

        /*
         * Fields validation, more in Step 5
         */
        public function validate_fields()
        {
            // wc_add_notice(json_encode($_POST), 'error');

            if ($_POST['payment_method'] === 'alifnasiya' && isset($_POST['alif_phone']) && empty($_POST['alif_phone'])) {
                wc_add_notice(__('Telefon raqam kiritish majburiy'), 'error');

            }



        }

        /*
         * We're processing the payments here, everything about it is in Step 5
         */
        public function process_payment($order_id)
        {
            global $woocommerce;

            // we need it to get any order detailes
            $order = wc_get_order($order_id);
            $orders = $order->get_items();
            $test = $order->get_payment_method();

            $orderForAlif = array();

            wc_add_notice(json_encode($test));


            foreach ($order->get_items() as $item_id => $item) {

                // Get an instance of corresponding the WC_Product object
                $product = $item->get_product();

                $product_name = $item->get_name(); // Get the item name (product name)

                $item_quantity = $item->get_quantity(); // Get the item quantity

                $item_total = $item->get_total(); // Get the item line total discounted

                $orderForAlif[] = array(
                    'name' => $product_name,
                    'price' => $item_total,
                    'count' => $item_quantity
                );

            }

            // wc_add_notice(json_encode($orderForAlif));



        }

        /*
         * In case you need a webhook, like PayPal IPN etc
         */
        public function webhook()
        {

        }
    }
}




?>