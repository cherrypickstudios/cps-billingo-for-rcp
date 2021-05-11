<?php

// No direct access
defined('ABSPATH') or die('Hey, do not do this 😱');

class B4RCP_Settings {

	private static $instance = null; // Instance of this class.

	private $options = array(); //apikey and blockid

	/**
	 * Constructor, sets up option fields and menus
	 */
	private function __construct() {
		// Hook into the admin menu
		add_action( 'admin_menu', array( $this, 'create_plugin_settings_page' ), 20 );

		// Setup sections
		add_action( 'admin_init', array( $this, 'setup_sections' ) );

		// Setup fields
		add_action( 'admin_init', array( $this, 'setup_fields' ) );

		// Add options to $options array
		$this->options['api_key'] = get_option('b4rcp_billingo_apikey');
		$this->options['block_id'] = get_option('b4rcp_billingo_blockid');
		$this->options['is_connected'] = get_option('b4rcp_is_connected');
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

	/**
	 * Returns options
	 *
	 * @return  array  option_id => value
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * Sets billingo connection to false
	 */
	public function invalidate_connection() {
		// TODO send email to admin
		update_option( 'b4rcp_is_connected', false );
	}

	/**
	 * Register Billingo submenu under RCP topmenu.
	 */
	public function create_plugin_settings_page() {
		$parent_slug = 'rcp-members';
		$page_title = 'Billingo Settings';
		$menu_title = 'Billingo Settings';
		$capability = 'manage_options';
		$slug = 'b4rcp_billingo';
		$callback = array( $this, 'plugin_settings_page_content' );

		$hookname = add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $slug, $callback );
		add_action( 'load-' . $hookname, array( $this, 'plugin_settings_page_submit') );
	}

	/**
	 * Called before settins page renders
	 */
	public function plugin_settings_page_submit() {

		$args = array(
			'method' => 'GET',
			'headers' => array(
				'X-API-KEY' => b4rcp_get_api_key(),
			),
		);
		$url = BILLINGO_API_URL .'/document-blocks';

		$res = wp_remote_request( $url, $args );

		// check if apy key is okay
		if( $res['response']['code'] != 200 ) {
			add_action( 'admin_notices', function() { ?>
				<div class="notice notice-error">
					<p><?php _e( 'Somthing\'s wrong with your api key.', 'cps-billingo-for-rcp' ); ?></p>
				</div> <?php
			});
			update_option( 'b4rcp_is_connected', false );
			return false;
		}

		$blocks = json_decode($res['body'])->data;
		$block_key = array_search($this->options['block_id'], array_column($blocks, 'id'));

		// check if block id is okay
		if( $block_key === false ) {
			add_action( 'admin_notices', function() { ?>
				<div class="notice notice-error">
					<p><?php _e( 'The blockid you set up is wrong or there is no block with this id.', 'cps-billingo-for-rcp' ); ?></p>
				</div> <?php
			});
			update_option( 'b4rcp_is_connected', false );
			return false;
		}

		update_option( 'b4rcp_is_connected', true );
		return true;
	}

	/**
	 * Restrict/Billingo page content
	 */
	public function plugin_settings_page_content() { ?>
		<div class="wrap">
			<h2>Billingo settings</h2>
			<p><?php _e('Before you set api key and block id please make sure that you filled out <a href="https://app.billingo.hu/organization-setting/profile" target="_blank">organization settings</a> correctly. To test it, make an invoice manually!', 'cps-billingo-for-rcp' ); ?></p>
			<form method="post" action="options.php">
					<?php
						settings_fields( 'b4rcp_billingo' );
						do_settings_sections( 'b4rcp_billingo' );
						submit_button();
					?>
			</form>
		</div> <?php
	}

	/**
	 * Restrict/Billingo page sections
	 */
	public function setup_sections() {
		add_settings_section( 'billingo_connection', 'Connection to Billingo', array( $this, 'setup_section' ), 'b4rcp_billingo' );
		add_settings_section( 'billingo_block_settings', 'Block settings', array( $this, 'setup_section' ), 'b4rcp_billingo' );
	}

	/**
	 * Descriptions for the setions
	 */
	public function setup_section( $arguments ) {
		switch( $arguments['id'] ){
			case 'billingo_connection':
				_e('Generate your billingo api key <a href="https://app.billingo.hu/api-key" target="_blank">here</a> with reading and writing rule!', 'cps-billingo-for-rcp' );
				break;
			case 'billingo_block_settings':
				_e('Choose whitch block you\'d like to generate invoices into and copy-paste its API ID from <a href="https://app.billingo.hu/document-block/list" target="_blank">here</a>!', 'cps-billingo-for-rcp' );
				break;
		}
	}

	/**
	 * Adds fields and register setting to options
	 */
	public function setup_fields() {
		$fields = array(
			array( // api key
				'id'            => 'b4rcp_billingo_apikey',
				'label'         => 'Billingo apikey',
				'section'       => 'billingo_connection',
				'type'          => 'text',
				'options'       => false,
				'placeholder'   => '00000000-0000-0000-0000-00000000',
				//'helper'        => 'Does this help?',
				//'supplemental'  => 'I am underneath!',
				//'default'       => '01/01/2015',
			),
			array( // block id
				'id'              => 'b4rcp_billingo_blockid',
				'label'           => 'Billingo blockid',
				'section'         => 'billingo_block_settings',
				'type'            => 'text',
				'options'         => false,
				'placeholder'     => '12345',
				//'helper'          => 'Does this help?',
				//'supplemental'    => 'I am underneath!',
				//'default'         => '01/01/2015',
			),
		);

		foreach( $fields as $field ){
			add_settings_field( $field['id'], $field['label'], array( $this, 'field_callback' ), 'b4rcp_billingo', $field['section'], $field );
			register_setting( 'b4rcp_billingo', $field['id'] );
		}
	}

	/**
	 * Displays the input fields and the surranding elements.
	 */
	public function field_callback( $arguments ) {
		$value = get_option( $arguments['id'] ); // Get the current value, if there is one
		if( ! $value ) { // If no value exists
			$value = $arguments['default']; // Set to our default
		}

		// Check which type of field we want
		switch( $arguments['type'] ){
			case 'text': // If it is a text field
				printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['id'], $arguments['type'], $arguments['placeholder'], $value );
				break;
			case 'textarea': // If it is a textarea
				printf( '<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="5" cols="50">%3$s</textarea>', $arguments['id'], $arguments['placeholder'], $value );
				break;
			case 'select': // If it is a select dropdown
				if( ! empty ( $arguments['options'] ) && is_array( $arguments['options'] ) ){
					$options_markup;
					foreach( $arguments['options'] as $key => $label ){
						$options_markup .= sprintf( '<option value="%s" %s>%s</option>', $key, selected( $value, $key, false ), $label );
					}
					printf( '<select name="%1$s" id="%1$s">%2$s</select>', $arguments['uid'], $options_markup );
				}
				break;
		}

		// If there is help text
		if( $helper = $arguments['helper'] ){
			printf( '<span class="helper"> %s</span>', $helper ); // Show it
		}

		// If there is supplemental text
		if( $supplimental = $arguments['supplemental'] ){
			printf( '<p class="description">%s</p>', $supplimental ); // Show it
		}
	}

}

return B4RCP_Settings::get_instance();
