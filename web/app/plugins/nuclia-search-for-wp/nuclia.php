<?php
/**
 * Plugin Name:       Nuclia search for WP
 * Plugin URI:        https://github.com/kalyx/nuclia-search-for-wp
 * Description:       Integrate the powerful Nuclia search service with WordPress
 * Version:           1.1.1
 * Requires at least: 5.6
 * Requires PHP:      7.2
 * Author:            Serge Rauber
 * Author URI:        https://kalyx.fr
 * License:           GNU General Public License v2.0 / MIT License
 * Text Domain:       klx-nuclia-search-for-wp
 * Domain Path:       /languages
 *
 * @since   1.0.0
 * @package kalyx\nuclia-search-for-wp
 */


// Nothing to see here if not loaded in WP context.
if ( ! defined( 'WPINC' ) ) die;


// The Nuclia Search plugin version.
define( 'KLX_NUCLIA_VERSION', '1.1.0' );

// The minmum required PHP version.
define( 'KLX_NUCLIA_MIN_PHP_VERSION', '7.2' );

// The minimum required WordPress version.
define( 'KLX_NUCLIA_MIN_WP_VERSION', '5.6' );


define( 'KLX_NUCLIA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'KLX_NUCLIA_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
define( 'KLX_NUCLIA_PATH', __DIR__ . '/' );


/**
 * Check for required PHP version.
 *
 * @since   1.0.0
 *
 * @return bool
 */
function nuclia_php_version_check() {
	if ( version_compare( PHP_VERSION, KLX_NUCLIA_MIN_PHP_VERSION, '<' ) ) {
		return false;
	}
	return true;
}


/**
 * Check for required WordPress version.
 *
 * @since   1.0.0
 *
 * @return bool
 */
function nuclia_wp_version_check() {
	if ( version_compare( $GLOBALS['wp_version'], KLX_NUCLIA_MIN_WP_VERSION, '<' ) ) {
		return false;
	}
	return true;
}


/**
 * Admin notices if requirements aren't met.
 *
 * @since   1.0.0
 */
function nuclia_requirements_error_notice() {
	$notices = array();
	if ( ! nuclia_php_version_check() ) {
		$notices[] = sprintf(
			/* translators: placeholder 1 is minimum required PHP version, placeholder 2 is installed PHP version. */
			esc_html__( 'Nuclia plugin requires PHP %1$s or higher. You’re still on %2$s.', 'klx-nuclia-search-for-wp' ),
			KLX_NUCLIA_MIN_PHP_VERSION,
			PHP_VERSION
		);

	}

	if ( ! nuclia_wp_version_check() ) {
		$notices[] = sprintf(
			/* translators: placeholder 1 is minimum required WordPress version, placeholder 2 is installed WordPress version. */
			esc_html__( 'Nuclia plugin requires at least WordPress in version %1$s, You are on %2$s.', 'klx-nuclia-search-for-wp' ),
			KLX_NUCLIA_MIN_WP_VERSION,
			esc_html( $GLOBALS['wp_version'] )
		);
	}

	foreach ( $notices as $notice ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $notice ) . '</p></div>';
	}
}


/**
 * I18n.
 *
 * @since   1.0.0
 */
function nuclia_load_textdomain() {
	load_plugin_textdomain( 'klx-nuclia-search-for-wp', false, plugin_basename( dirname( __FILE__ ) ) . '/languages/' );
}

add_action( 'init', 'nuclia_load_textdomain' );

/**
 * Debug log function
 *
 * @since   1.0.0
 *
 * @var string	$notice	The notice to log
 */
function nuclia_log( $notice ) {
	if ( true === WP_DEBUG ) {
		error_log( $notice."\n" );
	};
}

// load plugin if requirements are met or display admin notice
if ( nuclia_php_version_check() && nuclia_wp_version_check() ) {
	require_once KLX_NUCLIA_PATH . 'includes/class-nuclia-widget.php';
	require_once KLX_NUCLIA_PATH . 'includes/nuclia-searchbox-shortcode.php';
	require_once KLX_NUCLIA_PATH . 'includes/class-nuclia-api.php';
	require_once KLX_NUCLIA_PATH . 'includes/class-nuclia-plugin.php';
	require_once KLX_NUCLIA_PATH . 'includes/class-nuclia-settings.php';
	if ( is_admin() ) {
		require_once KLX_NUCLIA_PATH . 'includes/admin/class-nuclia-admin-page-settings.php';
	}
	$nuclia = Nuclia_Plugin_Factory::create();
} else {
	add_action( 'admin_notices', 'nuclia_requirements_error_notice' );
}

/**
 * Class Nuclia_Plugin_Factory
 *
 * Responsible for creating a shared instance of the main Nuclia_Plugin object.
 *
 * @since 1.0.0
 */
class Nuclia_Plugin_Factory {

	/**
	 * Create and return a shared instance of the Nuclia_Plugin.
	 *
	 * @since  1.0.0
	 *
	 * @return Nuclia_Plugin The shared plugin instance.
	 */
	public static function create(): Nuclia_Plugin {

		/**
		 * The static instance to share, else null.
		 *
		 * @since  1.0.0
		 *
		 * @var null|Nuclia_Plugin $plugin
		 */
		static $plugin = null;

		if ( null !== $plugin ) {
			return $plugin;
		}

		$plugin = new Nuclia_Plugin();

		return $plugin;
	}
}
