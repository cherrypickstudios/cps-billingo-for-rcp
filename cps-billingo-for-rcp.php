<?php
/**
 * Plugin Name:       CPS | Billingo for Restrict Content Pro
 * Plugin URI:        https://www.cherrypickstudios.com/
 * Description:       Basic Billingo connection for Restrict Content Pro
 * Version:           0.3.0
 * Author:            Gabor Bankuti & Surbma
 * Author URI:        https://www.cherrypickstudios.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cps-billingo-for-rcp
 * Domain Path:       /languages
 */

// No direct access
defined('ABSPATH') or die('Hey, do not do this 😱');

// Localization
add_action( 'plugins_loaded', function() {
	load_plugin_textdomain( 'cps-billingo-for-rcp', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
} );

// Const for billingo api url
if (!defined('BILLINGO_API_URL')) {
	define('BILLINGO_API_URL', 'https://api.billingo.hu/v3');
}

// Const for billingo app url
if (!defined('BILLINGO_APP_URL')) {
	define('BILLINGO_APP_URL', 'https://app.billingo.hu');
}

class BillingoForRCP {

	private static $instance = null;

	/**
	* Initializes the plugin by setting ...
	*/
	private function __construct() {
		// error if RCP is inactive
		if( !is_plugin_active('restrict-content-pro/restrict-content-pro.php') ) {
			add_action( 'admin_notices', function() {
				?>
				<div class="notice notice-error">
				<p><?php _e( 'To use this plugin you need Restrict Content Pro activated.', 'cps-billingo-for-rcp' ); ?></p>
				</div>
				<?php
			} );
		} else {
			// configures connection to billingo & sets wp menu
			require_once plugin_dir_path( __FILE__ ) . 'includes/settings.php';

			// adds fields to reg and edit forms and creates a billingo partner for each rcp customer
			require_once plugin_dir_path( __FILE__ ) . 'includes/customers.php';

			// creates billingo documents after payment
			require_once plugin_dir_path( __FILE__ ) . 'includes/payments.php';

			// utility functions
			require_once plugin_dir_path( __FILE__ ) . 'includes/utils.php';
			require_once plugin_dir_path( __FILE__ ) . 'includes/utils-countries.php';

			if( !b4rcp_is_connected() ) {
				add_action( 'admin_notices', function() {
					?>
					<div class="notice notice-error">
					<p><?php _e( 'Please check billingo connection settings.', 'cps-billingo-for-rcp' ); ?></p>
					</div>
					<?php
				});
			}
		}
	}

	/**
	* Creates or returns an instance of this class.
	*/
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

}

/**
 * Init B4RCP
 */
add_action('plugins_loaded', array('BillingoForRCP', 'get_instance'));
