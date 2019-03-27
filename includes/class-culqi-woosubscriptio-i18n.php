<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       http://daxes.net/
 * @since      1.0.0
 *
 * @package    Culqi_Woosubscriptio
 * @subpackage Culqi_Woosubscriptio/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Culqi_Woosubscriptio
 * @subpackage Culqi_Woosubscriptio/includes
 * @author     Laurence HR <laurencehr@gmail.com>
 */
class Culqi_Woosubscriptio_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'culqi-woosubscriptio',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
