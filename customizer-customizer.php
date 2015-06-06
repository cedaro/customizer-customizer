<?php
/**
 * Customizer Customizer
 *
 * @package   CustomizerCustomizer
 * @author    Brady Vercher
 * @link      https://github.com/cedaro/customizer-customizer
 * @copyright Copyright (c) 2015 Cedaro, LLC
 * @license   GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Customizer Customizer
 * Plugin URI:  https://github.com/cedaro/customizer-customizer
 * Description: Hide selected panels and sections in the Customizer.
 * Version:     1.0.0
 * Author:      Cedaro
 * Author URI:  http://www.cedaro.com/?utm_source=wordpress-plugin&utm_medium=link&utm_content=customizer-customizer-author-uri&utm_campaign=plugins
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: customizer-customizer
 * Domain Path: /languages
 */

/**
 * Main plugin class.
 *
 * @package CustomizerCustomizer
 * @since 1.0.0
 */
class Cedaro_Customizer_Customizer {
	/**
	 * Load the plugin.
	 *
	 * @since 1.0.0
	 */
	public function load() {
		$this->load_textdomain();
		$this->register_hooks();
	}

	/**
	 * Localize the plugin's strings.
	 *
	 * @since 1.0.0
	 */
	protected function load_textdomain() {
		$plugin_rel_path = dirname( plugin_basename( __FILE__ ) ) . '/languages';
		load_plugin_textdomain( 'customizer-customizer', false, $plugin_rel_path );
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	protected function register_hooks() {
		add_action( 'customize_controls_enqueue_scripts',             array( $this, 'enqueue_customizer_controls_assets' ) );
		add_action( 'customize_controls_print_footer_scripts',        array( $this, 'print_templates' ) );
		add_action( 'wp_ajax_customizer_customizer_toggle_container', array( $this, 'ajax_toggle_container' ) );
	}

	/**
	 * Enqueue scripts to output in the Customizer.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_customizer_controls_assets() {
		$user = wp_get_current_user();

		wp_enqueue_script(
			'customizer-customizer-controls',
			plugin_dir_url( __FILE__ ) . 'assets/js/customize-controls.js',
			array( 'customize-controls', 'wp-backbone', 'wp-util' ),
			'1.0.0',
			true
		);

		wp_localize_script( 'customizer-customizer-controls', '_customizerCustomizerSettings', array(
			'hidden' => (array) get_user_meta( $user->ID, 'customizer_customizer_hidden_containers', true ),
			'groups' => array(
				array(
					'group' => 'panels',
					'type'  => 'panel',
					'title' => __( 'Hidden Panels', 'customizer-customizer' ),
				),
				array(
					'group' => 'sections',
					'type'  => 'section',
					'title' => __( 'Hidden Sections', 'customizer-customizer' ),
				),
			),
			'toggleNonce' => wp_create_nonce( 'toggle-container' ),
		) );
	}

	/**
	 * Print Underscore templates in the Customizer footer.
	 *
	 * @since 1.0.0
	 */
	public function print_templates() {
		?>
		<script type="text/html" id="tmpl-customizer-customizer-group">
			<h3>{{ data.title }}</h3>
		</script>

		<script type="text/html" id="tmpl-customizer-customizer-list-item">
			<label><input type="checkbox"> {{ data.title }}</label>
		</script>
		<?php
	}

	/**
	 * AJAX callback to toggle a container's visibility.
	 *
	 * @since 1.0.0
	 */
	public function ajax_toggle_container() {
		if ( empty( $_POST['container'] ) ) {
			wp_send_json_error();
		}

		check_ajax_referer( 'toggle-container', 'nonce' );

		$user       = wp_get_current_user();
		$meta_key   = 'customizer_customizer_hidden_containers';
		$container  = $_POST['container'];
		$group      = $container['group'];
		$is_visible = 'false' === $container['isVisible'] || ! $container['isVisible'] ? false : true;
		$hidden     = get_user_meta( $user->ID, $meta_key, true );

		if ( empty( $hidden ) ) {
			$hidden = array();
		}

		if ( $is_visible && isset( $hidden[ $group ] ) ) {
			$key = array_search( $container['id'], $hidden[ $group ] );
			unset( $hidden[ $group ][ $key ] );
			$hidden[ $group ] = array_values( $hidden[ $group ] );
		} elseif ( ! $is_visible ) {
			$hidden[ $group ][] = sanitize_key( $container['id'] );
			$hidden[ $group ] = array_values( array_unique( $hidden[ $group ] ) );
		}

		update_user_meta( $user->ID, $meta_key, $hidden );
		wp_send_json_success();
	}
}

/**
 * Load the plugin.
 */
$customizer_customizer = new Cedaro_Customizer_Customizer();
add_action( 'plugins_loaded', array( $customizer_customizer, 'load' ) );
