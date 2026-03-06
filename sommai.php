<?php
/**
 * SommAI WordPress Plugin
 * © 2026 Mariano de Iriondo (Gorilion)
 *
 * Proprietary software. All rights reserved.
 * WordPress integration for AI-powered wine recommendations.
 *
 * Plugin Name:       SommAI
 * Plugin URI:        https://sommai.com
 * Description:       Embed an AI-powered wine finder on any page using the [sommai] shortcode.
 * Version:           1.0.4
 * Creator:           Mariano de Iriondo (Gorilion)
 * Creator URI:       https://gorilion.com
 * Author:            Mariano de Iriondo
 * Author URI:        https://marianodeiriondo.com
 * Author Email:      mariano@gorilion.com
 * License:           Proprietary
 * License URI:       https://sommai.com/license
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Text Domain:       sommai
 */

defined( 'ABSPATH' ) || exit;

define( 'SOMMAI_VERSION', '1.0.4' );
define( 'SOMMAI_DIR', plugin_dir_path( __FILE__ ) );
define( 'SOMMAI_URL', plugin_dir_url( __FILE__ ) );
define( 'SOMMAI_OPTION', 'sommai_settings' );

require_once SOMMAI_DIR . 'includes/class-admin.php';
require_once SOMMAI_DIR . 'includes/class-shortcode.php';

SommAI_Admin::init();
SommAI_Shortcode::init();

register_activation_hook( __FILE__, 'sommai_activate' );
function sommai_activate() {
    if ( ! get_option( SOMMAI_OPTION ) ) {
        // Pre-populate with the same 6 default suggestions shown in the widget
        $default_suggestions = implode( "\n", SommAI_Admin::DEFAULT_SUGGESTIONS );
        update_option( SOMMAI_OPTION, array(
            'license_key'      => '',
            'worker_url'       => 'https://sommai-worker.miriondo-f3d.workers.dev',
            'locale'           => 'es',
            'widget_title'     => '',
            'accent_color'     => '#6b2737',
            'cdn_url'          => '',
            'suggestions'      => $default_suggestions,
            'cart_provider'    => '',
            'cart_c7_tenant'   => '',
            'cart_wc_endpoint' => '',
            'cart_wc_nonce'    => '',
            'cart_ecellar_url' => '',
            'cart_custom_url'  => '',
        ) );
    }
}
