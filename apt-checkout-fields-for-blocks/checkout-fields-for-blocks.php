<?php
/**
 * Plugin Name: Checkout Fields for Blocks
 * Plugin URI: https://www.wpdesk.net/products/checkout-fields-for-blocks/
 * Description: Checkout Fields for Blocks
 * Version: 1.0.2
 * Author: WP Desk
 * Author URI: https://www.wpdesk.net/
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: checkout-fields-for-blocks
 * Domain Path: /lang/
 * ​
 * Requires at least: 6.4
 * Tested up to: 6.7
 * WC requires at least: 9.2
 * WC tested up to: 9.5
 * Requires PHP: 7.4
 * ​
 * Copyright 2024 WP Desk Ltd.
 * ​
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use WPDesk\CBFields\Plugin;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/vendor/autoload.php';

/* THESE TWO VARIABLES CAN BE CHANGED AUTOMATICALLY */
$plugin_version = '1.0.2';

$plugin_name        = 'Checkout Fields for Blocks';
$plugin_class_name  = Plugin::class;
$plugin_text_domain = 'checkout-fields-for-blocks';
$product_id         = 'Checkout Fields for Blocks';
$plugin_file        = __FILE__;
$plugin_dir         = __DIR__;

$requirements = [
	'php'          => '7.4',
	'wp'           => '6.0',
	'repo_plugins' => [
		[
			'name'      => 'woocommerce/woocommerce.php',
			'nice_name' => 'WooCommerce',
			'version'   => '8.0',
		],
	],
];

require __DIR__ . '/vendor_prefixed/wpdesk/wp-plugin-flow-common/src/plugin-init-php52-free.php';
