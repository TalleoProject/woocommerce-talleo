<?php
/*
Plugin Name: Talleo - WooCommerce Gateway
Plugin URI: https://www.talleo.org
Description: Extends WooCommerce by adding the Talleo Gateway
Version: 0.3
Author: mtl1979
*/
if(!defined('ABSPATH')) {
	exit;
}

//Load Plugin
add_action('plugins_loaded', 'talleo_init', 0 );

function talleo_init() {
	if(!class_exists('WC_Payment_Gateway')) return;

	include_once('include/talleo_payments.php');
	require_once('library.php');

    add_filter( 'woocommerce_payment_gateways', 'talleo_gateway');
	function talleo_gateway( $methods ) {
		$methods[] = 'Talleo_Gateway';
		return $methods;
	}
}

//Add action link
add_filter('plugin_action_links_' . plugin_basename( __FILE__ ), 'talleo_payment');

function talleo_payment($links) {
	$plugin_links = array('<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout') . '">' . __('Settings', 'talleo_payment') . '</a>',);
	return array_merge($plugin_links, $links);
}

//Configure currency
add_filter('woocommerce_currencies','add_my_currency');
add_filter('woocommerce_currency_symbol','add_my_currency_symbol', 10, 2);

function add_my_currency($currencies) {
     $currencies['TLO'] = __('Talleo','woocommerce');
     return $currencies;
}

function add_my_currency_symbol($currency_symbol, $currency) {
    switch($currency) {
        case 'TLO': $currency_symbol = 'TLO'; break;
    }
    return $currency_symbol;
}

//Create Database
register_activation_hook(__FILE__,'createDatabase');

function createDatabase() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'woocommerce_talleo';

	$sql = "CREATE TABLE $table_name (
            `id` INT(32) NOT NULL AUTO_INCREMENT,
	    `oid` INT(32) NOT NULL,
            `pid` VARCHAR(64) NOT NULL,
            `hash` VARCHAR(120) NOT NULL,
            `amount` DECIMAL(12, 2) NOT NULL,
	    `conversion` DECIMAL(12,2) NOT NULL,
            `paid` INT(1) NOT NULL,
            UNIQUE KEY id (id)
	) $charset_collate;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}
