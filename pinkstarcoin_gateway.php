<?php
/*
Plugin Name: PinkstarcoinV2 - WooCommerce Gateway
Plugin URI: http://pinkstarcoin.com
Description: Extends WooCommerce by adding the PinkstarcoinV2 Gateway
Version: 0.3
Author: mtl1979
*/
if(!defined('ABSPATH')) {
	exit;
}

//Load Plugin
add_action('plugins_loaded', 'pinkstarcoin_init', 0 );

function pinkstarcoin_init() {
	if(!class_exists('WC_Payment_Gateway')) return;
	
	include_once('include/pinkstarcoin_payments.php');
	require_once('library.php');

    add_filter( 'woocommerce_payment_gateways', 'pinkstarcoin_gateway');
	function pinkstarcoin_gateway( $methods ) {
		$methods[] = 'Pinkstarcoin_Gateway';
		return $methods;
	}
}

//Add action link
add_filter('plugin_action_links_' . plugin_basename( __FILE__ ), 'pinkstarcoin_payment');

function pinkstarcoin_payment($links) {
	$plugin_links = array('<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout') . '">' . __('Settings', 'pinkstarcoin_payment') . '</a>',);
	return array_merge($plugin_links, $links);	
}

//Configure currency
add_filter('woocommerce_currencies','add_my_currency');
add_filter('woocommerce_currency_symbol','add_my_currency_symbol', 10, 2);

function add_my_currency($currencies) {
     $currencies['PSTAR'] = __('Pinkstarcoin','woocommerce');
     return $currencies;
}

function add_my_currency_symbol($currency_symbol, $currency) {
    switch($currency) {
        case 'PSTAR': $currency_symbol = 'PSTAR'; break;
    }
    return $currency_symbol;
}

//Create Database
register_activation_hook(__FILE__,'createDatabase');

function createDatabase() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'woocommerce_pinkstarcoin';
    
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
