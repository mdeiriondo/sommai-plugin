<?php
defined( 'ABSPATH' ) || exit;

class SommAI_Shortcode {

    public static function init() {
        add_shortcode( 'sommai', array( __CLASS__, 'render' ) );
    }

    /**
     * [sommai] shortcode.
     *
     * Supported attributes (all optional — fall back to admin settings):
     *   title              Widget heading text
     *   locale             "es" or "en"
     *   accent             Hex color e.g. #8B1A1A
     *   placeholder        Search input placeholder text
     *   cart_provider      commerce7 | woocommerce | ecellar | custom
     *   cart_c7tenant      Commerce7 tenant ID
     *   cart_wc_endpoint   WooCommerce Store API endpoint URL
     *   cart_wc_nonce      WooCommerce nonce
     *   cart_ecellar_url   eCellar store base URL
     *   cart_custom_url    Custom URL template with {product_id}
     */
    public static function render( $atts ) {
        $opts = SommAI_Admin::get_opts();

        // Merge shortcode atts over defaults from settings
        $atts = shortcode_atts(
            array(
                'title'            => $opts['widget_title'] ?? '',
                'locale'           => $opts['locale'] ?? 'es',
                'accent'           => $opts['accent_color'] ?? '#6b2737',
                'placeholder'      => '',
                'cart_provider'    => $opts['cart_provider'] ?? '',
                'cart_c7tenant'    => $opts['cart_c7_tenant'] ?? '',
                'cart_wc_endpoint' => $opts['cart_wc_endpoint'] ?? '',
                'cart_wc_nonce'    => $opts['cart_wc_nonce'] ?? '',
                'cart_ecellar_url' => $opts['cart_ecellar_url'] ?? '',
                'cart_custom_url'  => $opts['cart_custom_url'] ?? '',
            ),
            $atts,
            'sommai'
        );

        $license    = $opts['license_key'] ?? '';
        $worker_url = $opts['worker_url'] ?? '';

        // Bail early with a helpful message if not configured (only visible to admins)
        if ( empty( $license ) || empty( $worker_url ) ) {
            if ( current_user_can( 'manage_options' ) ) {
                $url = admin_url( 'options-general.php?page=sommai' );
                return sprintf(
                    '<p style="padding:16px;background:#fff3cd;border:1px solid #ffc107;border-radius:6px;">' .
                    '🍷 <strong>SommAI:</strong> <a href="%s">Complete the setup</a> to display the wine finder.' .
                    '</p>',
                    esc_url( $url )
                );
            }
            return '';
        }

        // Enqueue the widget script
        self::enqueue_widget( $opts );

        // Build suggestions JSON from settings (newline-separated)
        $raw_suggestions = $opts['suggestions'] ?? '';
        $suggestions_arr = array_values( array_filter( array_map( 'trim', explode( "\n", $raw_suggestions ) ) ) );
        $suggestions_json = ! empty( $suggestions_arr ) ? wp_json_encode( $suggestions_arr ) : '';

        // Build data attributes — values are escaped for HTML attribute context
        $data = array(
            'data-sommai' => '',
            'data-worker'     => esc_url( $worker_url ),
            'data-license'    => esc_attr( $license ),
            'data-locale'     => esc_attr( $atts['locale'] ),
        );

        if ( ! empty( $atts['title'] ) ) {
            $data['data-title'] = esc_attr( $atts['title'] );
        }
        if ( ! empty( $atts['accent'] ) ) {
            $data['data-accent-color'] = esc_attr( $atts['accent'] );
        }
        if ( ! empty( $atts['placeholder'] ) ) {
            $data['data-placeholder'] = esc_attr( $atts['placeholder'] );
        }
        if ( $suggestions_json ) {
            $data['data-suggestions'] = esc_attr( $suggestions_json );
        }

        // Build cart config JSON if a provider is set
        $cart_provider = $atts['cart_provider'];
        if ( ! empty( $cart_provider ) ) {
            $cart = array( 'provider' => $cart_provider );
            switch ( $cart_provider ) {
                case 'commerce7':
                    if ( ! empty( $atts['cart_c7tenant'] ) ) $cart['c7_tenant'] = $atts['cart_c7tenant'];
                    break;
                case 'woocommerce':
                    if ( ! empty( $atts['cart_wc_endpoint'] ) ) $cart['wc_endpoint'] = $atts['cart_wc_endpoint'];
                    if ( ! empty( $atts['cart_wc_nonce'] ) )    $cart['wc_nonce']    = $atts['cart_wc_nonce'];
                    break;
                case 'ecellar':
                    if ( ! empty( $atts['cart_ecellar_url'] ) ) $cart['ecellar_url'] = $atts['cart_ecellar_url'];
                    break;
                case 'custom':
                    if ( ! empty( $atts['cart_custom_url'] ) ) $cart['custom_url'] = $atts['cart_custom_url'];
                    break;
            }
            $data['data-cart'] = esc_attr( wp_json_encode( $cart ) );
        }

        $attr_string = '';
        foreach ( $data as $key => $value ) {
            if ( $value === '' && $key === 'data-sommai' ) {
                $attr_string .= ' ' . $key;
            } else {
                $attr_string .= sprintf( ' %s="%s"', $key, $value );
            }
        }

        return sprintf( '<div%s></div>', $attr_string );
    }

    private static function enqueue_widget( array $opts ) {
        if ( wp_script_is( 'sommai-widget', 'enqueued' ) ) {
            return;
        }

        // Use custom CDN URL if set, otherwise fall back to bundled file
        $cdn = $opts['cdn_url'] ?? '';
        if ( $cdn ) {
            $src = esc_url( $cdn );
        } else {
            $src = SOMMAI_URL . 'assets/sommai.min.js';
        }

        wp_enqueue_script(
            'sommai-widget',
            $src,
            array(),           // no WP dependencies
            SOMMAI_VERSION,
            array(
                'strategy'  => 'defer',
                'in_footer' => true,
            )
        );
    }
}
