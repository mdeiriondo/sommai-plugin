<?php
defined( 'ABSPATH' ) || exit;

class BetterSOMM_Admin {

    // Default suggestion chips (matches widget Suggestions.tsx defaults)
    const DEFAULT_SUGGESTIONS = array(
        'Pairing with grilled steak 🥩',
        'Something for a special date night 💕',
        'A nice bottle to gift my dad 🎁',
        'Light wine for a summer picnic ☀️',
        'Bold red for a cold winter night 🔥',
        'Something to go with salmon 🐟',
    );

    public static function init() {
        add_action( 'admin_menu',            array( __CLASS__, 'add_menu' ) );
        add_action( 'admin_init',            array( __CLASS__, 'register_settings' ) );
        add_action( 'admin_notices',         array( __CLASS__, 'maybe_show_setup_notice' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
    }

    public static function enqueue_admin_assets( $hook ) {
        if ( 'settings_page_bettersomm' !== $hook ) return;

        wp_enqueue_script(
            'bettersomm-admin',
            BETTERSOMM_URL . 'assets/admin.js',
            array(),
            BETTERSOMM_VERSION,
            true
        );

        // Pass existing suggestions (or defaults) to JS
        $opts = self::get_opts();
        $raw  = trim( $opts['suggestions'] ?? '' );

        if ( $raw === '' ) {
            $suggestions = self::DEFAULT_SUGGESTIONS;
        } else {
            $suggestions = array_values( array_filter( array_map( 'trim', explode( "\n", $raw ) ) ) );
        }

        wp_localize_script( 'bettersomm-admin', 'bsBetterSommSuggestions', $suggestions );
    }

    public static function add_menu() {
        add_options_page( 'BetterSOMM', 'BetterSOMM', 'manage_options', 'bettersomm', array( __CLASS__, 'render_page' ) );
    }

    public static function register_settings() {
        register_setting( 'bettersomm_group', BETTERSOMM_OPTION, array( 'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ) ) );

        // ── Connection ───────────────────────────────────────────────
        add_settings_section( 'bettersomm_connection', 'Connection', '__return_false', 'bettersomm' );
        add_settings_field( 'license_key', 'License Key',   array( __CLASS__, 'field_license_key' ),  'bettersomm', 'bettersomm_connection' );
        add_settings_field( 'catalog_url', 'Product Feed URL', array( __CLASS__, 'field_catalog_url' ), 'bettersomm', 'bettersomm_connection' );

        // ── Widget ───────────────────────────────────────────────────
        add_settings_section( 'bettersomm_widget', 'Widget', '__return_false', 'bettersomm' );
        add_settings_field( 'locale',       'Language',     array( __CLASS__, 'field_locale' ),       'bettersomm', 'bettersomm_widget' );
        add_settings_field( 'widget_title', 'Widget Title', array( __CLASS__, 'field_widget_title' ), 'bettersomm', 'bettersomm_widget' );
        add_settings_field( 'accent_color', 'Accent Color', array( __CLASS__, 'field_accent_color' ), 'bettersomm', 'bettersomm_widget' );

        // ── Suggestions ──────────────────────────────────────────────
        add_settings_section( 'bettersomm_suggestions', 'Search Suggestions', array( __CLASS__, 'section_suggestions_desc' ), 'bettersomm' );
        add_settings_field( 'suggestions', 'Suggestions', array( __CLASS__, 'field_suggestions' ), 'bettersomm', 'bettersomm_suggestions' );

        // ── Commerce7 ────────────────────────────────────────────────
        add_settings_section( 'bettersomm_c7', 'Commerce7 Integration', '__return_false', 'bettersomm' );
        add_settings_field( 'c7_tenant', 'Tenant ID', array( __CLASS__, 'field_c7_tenant' ), 'bettersomm', 'bettersomm_c7' );

        // ── Developer (rows hidden by default via JS + .bs-dev-row class) ───
        add_settings_section( 'bettersomm_dev', 'Developer', array( __CLASS__, 'section_dev_header' ), 'bettersomm' );
        add_settings_field( 'worker_url', 'Worker URL',     array( __CLASS__, 'field_worker_url' ), 'bettersomm', 'bettersomm_dev', array( 'class' => 'bs-dev-row' ) );
        add_settings_field( 'cdn_url',    'Custom JS URL',  array( __CLASS__, 'field_cdn_url' ),    'bettersomm', 'bettersomm_dev', array( 'class' => 'bs-dev-row' ) );
    }

    public static function sanitize_settings( $input ) {
        $clean = array();
        $clean['license_key']  = sanitize_text_field( $input['license_key'] ?? '' );
        $clean['worker_url']   = esc_url_raw( rtrim( $input['worker_url'] ?? '', '/' ) );
        $clean['catalog_url']  = esc_url_raw( $input['catalog_url'] ?? '' );
        $clean['locale']       = in_array( $input['locale'] ?? '', array( 'es', 'en' ), true ) ? $input['locale'] : 'es';
        $clean['widget_title'] = sanitize_text_field( $input['widget_title'] ?? '' );
        $clean['accent_color'] = sanitize_hex_color( $input['accent_color'] ?? '' ) ?: '#6b2737';
        $clean['cdn_url']      = esc_url_raw( $input['cdn_url'] ?? '' );
        $clean['c7_tenant']    = sanitize_text_field( $input['c7_tenant'] ?? '' );

        $raw = $input['suggestions'] ?? array();
        if ( is_array( $raw ) ) {
            $lines = array_values( array_filter( array_map( 'sanitize_text_field', $raw ) ) );
        } else {
            $lines = array_values( array_filter( array_map( 'sanitize_text_field', explode( "\n", (string) $raw ) ) ) );
        }
        $clean['suggestions'] = implode( "\n", $lines );

        return $clean;
    }

    // ── Field renderers ───────────────────────────────────────────────

    public static function field_license_key() {
        $opts = self::get_opts(); $val = $opts['license_key'] ?? '';
        ?>
        <input type="password" name="<?php echo esc_attr( BETTERSOMM_OPTION ); ?>[license_key]"
            value="<?php echo esc_attr( $val ); ?>" class="regular-text"
            placeholder="bsomm_live_..." autocomplete="off" />
        <p class="description">Your BetterSOMM license key. Get one at bettersomm.com.</p>
        <?php
    }

    public static function field_catalog_url() {
        $opts = self::get_opts();
        ?>
        <input type="url" name="<?php echo esc_attr( BETTERSOMM_OPTION ); ?>[catalog_url]"
            value="<?php echo esc_attr( $opts['catalog_url'] ?? '' ); ?>" class="regular-text"
            placeholder="https://your-store.com/product-feed.xml" />
        <p class="description">URL of your product feed (Google Shopping XML, WooCommerce feed, or Commerce7 XML).</p>
        <?php
    }

    public static function field_locale() {
        $opts = self::get_opts(); $locale = $opts['locale'] ?? 'es';
        ?>
        <select name="<?php echo esc_attr( BETTERSOMM_OPTION ); ?>[locale]">
            <option value="es" <?php selected( $locale, 'es' ); ?>>Spanish (es)</option>
            <option value="en" <?php selected( $locale, 'en' ); ?>>English (en)</option>
        </select>
        <?php
    }

    public static function field_widget_title() {
        $opts = self::get_opts();
        ?>
        <input type="text" name="<?php echo esc_attr( BETTERSOMM_OPTION ); ?>[widget_title]"
            value="<?php echo esc_attr( $opts['widget_title'] ?? '' ); ?>" class="regular-text"
            placeholder="Find your perfect wine" />
        <p class="description">Heading shown above the search bar. Leave blank to hide.</p>
        <?php
    }

    public static function field_accent_color() {
        $opts = self::get_opts(); $color = $opts['accent_color'] ?? '#6b2737';
        ?>
        <input type="color" name="<?php echo esc_attr( BETTERSOMM_OPTION ); ?>[accent_color]" value="<?php echo esc_attr( $color ); ?>" />
        <input type="text"  name="<?php echo esc_attr( BETTERSOMM_OPTION ); ?>[accent_color]"
            value="<?php echo esc_attr( $color ); ?>" class="small-text"
            pattern="#[0-9a-fA-F]{6}" style="margin-left:8px;width:90px;" />
        <p class="description">Primary color for buttons and accents.</p>
        <?php
    }

    public static function section_suggestions_desc() {
        echo '<p>Clickable chips shown below the search bar. Edit or remove any suggestion below. Leave all blank to use the built-in defaults.</p>';
    }

    public static function field_suggestions() {
        // Populated by admin.js using window.bsBetterSommSuggestions
        ?>
        <div id="bs-suggestions-list" style="max-width:500px;"></div>
        <button type="button" id="bs-add-suggestion" class="button" style="margin-top:4px;">+ Add suggestion</button>
        <p class="description">Tip: add an emoji at the end for a nicer look, e.g. <code>Pairing with steak 🥩</code></p>
        <?php
    }

    public static function field_c7_tenant() {
        $opts = self::get_opts();
        ?>
        <input type="text" name="<?php echo esc_attr( BETTERSOMM_OPTION ); ?>[c7_tenant]"
            value="<?php echo esc_attr( $opts['c7_tenant'] ?? '' ); ?>" class="regular-text"
            placeholder="my-winery" />
        <p class="description">Your Commerce7 tenant ID. When set, wine cards show an <strong>Add to Cart</strong> button. Leave blank to disable.</p>
        <?php
    }

    public static function section_dev_header() {
        ?>
        <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;color:#666;font-size:13px;">
            <input type="checkbox" id="bs-dev-mode" style="width:auto;margin:0;" />
            Show developer settings
        </label>
        <?php
    }

    public static function field_worker_url() {
        $opts = self::get_opts();
        ?>
        <input type="url" name="<?php echo esc_attr( BETTERSOMM_OPTION ); ?>[worker_url]"
            value="<?php echo esc_attr( $opts['worker_url'] ?? 'https://bettersomm-worker.miriondo-f3d.workers.dev' ); ?>"
            class="regular-text" />
        <p class="description">Leave as default unless you self-host the worker.</p>
        <?php
    }

    public static function field_cdn_url() {
        $opts = self::get_opts();
        ?>
        <input type="url" name="<?php echo esc_attr( BETTERSOMM_OPTION ); ?>[cdn_url]"
            value="<?php echo esc_attr( $opts['cdn_url'] ?? '' ); ?>" class="regular-text"
            placeholder="https://cdn.bettersomm.com/bettersomm.min.js" />
        <p class="description">Override the bundled JS with a CDN URL. Leave blank to use the plugin-bundled file.</p>
        <?php
    }

    // ── Page render ───────────────────────────────────────────────────

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $opts       = self::get_opts();
        $configured = ! empty( $opts['license_key'] ) && ! empty( $opts['catalog_url'] );
        ?>
        <div class="wrap">
            <h1 style="display:flex;align-items:center;gap:10px;"><span style="font-size:1.4em;">🍷</span> BetterSOMM Settings</h1>

            <?php if ( $configured ) : ?>
                <div class="notice notice-success inline">
                    <p>Widget ready. Add <code>[bettersomm]</code> to any page or post.</p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields( 'bettersomm_group' ); do_settings_sections( 'bettersomm' ); submit_button( 'Save Settings' ); ?>
            </form>

            <hr style="margin-top:30px;" />
            <h2>Usage</h2>
            <table class="widefat striped" style="max-width:700px;">
                <thead><tr><th>Shortcode</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><code>[bettersomm]</code></td><td>Widget with all settings from this page.</td></tr>
                    <tr><td><code>[bettersomm title="Find your wine"]</code></td><td>Override the widget title.</td></tr>
                    <tr><td><code>[bettersomm locale="en" accent="#8B1A1A"]</code></td><td>Override language and accent color.</td></tr>
                    <tr><td><code>[bettersomm catalog="https://...xml"]</code></td><td>Override the product feed URL.</td></tr>
                    <tr><td><code>[bettersomm c7tenant="my-winery"]</code></td><td>Override Commerce7 tenant ID.</td></tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function maybe_show_setup_notice() {
        $screen = get_current_screen();
        if ( ! $screen || $screen->id === 'settings_page_bettersomm' ) return;
        $opts = self::get_opts();
        if ( empty( $opts['license_key'] ) || empty( $opts['catalog_url'] ) ) {
            $url = admin_url( 'options-general.php?page=bettersomm' );
            echo '<div class="notice notice-warning is-dismissible"><p>';
            printf(
                wp_kses( '<strong>BetterSOMM</strong> is not fully configured. <a href="%s">Complete the setup</a> to activate the widget.',
                    array( 'strong' => array(), 'a' => array( 'href' => array() ) ) ),
                esc_url( $url )
            );
            echo '</p></div>';
        }
    }

    public static function get_opts() {
        return (array) get_option( BETTERSOMM_OPTION, array() );
    }
}
