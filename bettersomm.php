<?php
/**
 * BetterSOMM WordPress Plugin
 * © 2026 Mariano de Iriondo (Gorilion)
 *
 * Proprietary software. All rights reserved.
 * WordPress integration for AI-powered wine recommendations.
 *
 * Plugin Name:       BetterSOMM
 * Plugin URI:        https://bettersomm.com
 * Description:       Embed an AI-powered wine finder on any page using the [bettersomm] shortcode.
 * Version:           1.0.3
 * Creator:           Mariano de Iriondo (Gorilion)
 * Creator URI:       https://gorilion.com
 * Author:            Mariano de Iriondo
 * Author URI:        https://marianodeiriondo.com
 * Author Email:      mariano@gorilion.com
 * License:           Proprietary
 * License URI:       https://bettersomm.com/license
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Text Domain:       bettersomm
 */

defined( 'ABSPATH' ) || exit;

define( 'BETTERSOMM_VERSION', '1.0.3' );
define( 'BETTERSOMM_DIR', plugin_dir_path( __FILE__ ) );
define( 'BETTERSOMM_URL', plugin_dir_url( __FILE__ ) );
define( 'BETTERSOMM_OPTION', 'bettersomm_settings' );

require_once BETTERSOMM_DIR . 'includes/class-admin.php';
require_once BETTERSOMM_DIR . 'includes/class-shortcode.php';

BetterSOMM_Admin::init();
BetterSOMM_Shortcode::init();

register_activation_hook( __FILE__, 'bettersomm_activate' );
function bettersomm_activate() {
    if ( ! get_option( BETTERSOMM_OPTION ) ) {
        // Pre-populate with the same 6 default suggestions shown in the widget
        $default_suggestions = implode( "\n", BetterSOMM_Admin::DEFAULT_SUGGESTIONS );
        update_option( BETTERSOMM_OPTION, array(
            'license_key'  => '',
            'worker_url'   => 'https://bettersomm-worker.miriondo-f3d.workers.dev',
            'catalog_url'  => '',
            'locale'       => 'es',
            'widget_title' => '',
            'accent_color' => '#6b2737',
            'cdn_url'      => '',
            'suggestions'  => $default_suggestions,
            'c7_tenant'    => '',
        ) );
    }
}
