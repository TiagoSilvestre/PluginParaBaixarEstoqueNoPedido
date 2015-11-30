<?php

/**

 * Plugin Name: WooCommerce Reduces inventory in order

 * Description: This pluggin reduces the inventory when new order is placed so order status is on-hold, and restock products when the order status change to Cancelled.

 * Version: 1.0

 * Author: Tiago Silvestre

 * Author URI: mailto:tiagosilvestreadm@gmail.com

 */



/*  Copyright 2015 - Tiago Silvestre 



*/



if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly



if ( ! class_exists( 'WC_Reduces_Inventory_In_Order' ) ) {



	class WC_Reduces_Inventory_In_Order {



		public function __construct() {


	add_action( 'woocommerce_order_status_on-hold', array( $this,'tiago_auto_stock_reduce' ), 10, 1 );

	add_filter( 'woocommerce_payment_complete_reduce_order_stock', array( $this, '__return_false'), 10, 1 );

	add_action( 'woocommerce_order_status_processing_to_cancelled', array( $this, 'restore_order_stock' ), 10, 1 );

	add_action( 'woocommerce_order_status_completed_to_cancelled', array( $this, 'restore_order_stock' ), 10, 1 );

	add_action( 'woocommerce_order_status_on-hold_to_cancelled', array( $this, 'restore_order_stock' ), 10, 1 );

	add_action( 'woocommerce_order_status_processing_to_refunded', array( $this, 'restore_order_stock' ), 10, 1 );

	add_action( 'woocommerce_order_status_completed_to_refunded', array( $this, 'restore_order_stock' ), 10, 1 );

	add_action( 'woocommerce_order_status_on-hold_to_refunded', array( $this, 'restore_order_stock' ), 10, 1 );

		} // End __construct()



		public function restore_order_stock( $order_id ) {

			$order = new WC_Order( $order_id );

			if ( ! get_option('woocommerce_manage_stock') == 'yes' && ! sizeof( $order->get_items() ) > 0 ) {

				return;

			}



			foreach ( $order->get_items() as $item ) {



				if ( $item['product_id'] > 0 ) {

					$_product = $order->get_product_from_item( $item );



					if ( $_product && $_product->exists() && $_product->managing_stock() ) {



						$old_stock = $_product->stock;



						$qty = apply_filters( 'woocommerce_order_item_quantity', $item['qty'], $this, $item );



						$new_quantity = $_product->increase_stock( $qty );



						do_action( 'woocommerce_auto_stock_restored', $_product, $item );



						$order->add_order_note( sprintf( __( 'Item #%s stock incremented from %s to %s.', 'woocommerce' ), $item['product_id'], $old_stock, $new_quantity) );



						$order->send_stock_notifications( $_product, $new_quantity, $item['qty'] );

					}

				}

			}

		} // End restore_order_stock()

		

		public function tiago_auto_stock_reduce( $order_id ) {

			$order = new WC_Order( $order_id );

			$order->reduce_order_stock(); // Payment is complete so reduce stock levels

			$order->add_order_note( 'Estoque reduzido automaticamente ao criar pedido.' );

		}

	}

	$GLOBALS['tiago_stock_reduce'] = new WC_Reduces_Inventory_In_Order();

}