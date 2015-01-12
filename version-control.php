<?php
/*
Plugin Name: Plugin Version Switching
Plugin URI: http://www.hudsonatwell.co
Description: Provides easy way to 'roll back' and 'roll forward' plugin versions that support version tagging.
Version: 1.0.1
Author: Hudson Atwell
Author URI: http://www.hudsonatwell.co
Text Domain: version-control
Domain Path: lang
*/

if ( !class_exists('Version_Control_Plugin')	) {

	final class Version_Control_Plugin {

		/**
		* Main Version_Control_Plugin Instance
		*/
		public function __construct() {
			self::define_constants();
			self::includes();
			self::load_text_domain_init();
		}

		/**
		* Setup plugin constants
		*
		*/
		private static function define_constants() {

			define('VERSION_CONTROL_CURRENT_VERSION', '2.2.1' );
			define('VERSION_CONTROL_URLPATH', WP_PLUGIN_URL.'/'.plugin_basename( dirname(__FILE__) ).'/' );
			define('VERSION_CONTROL_PATH', WP_PLUGIN_DIR.'/'.plugin_basename( dirname(__FILE__) ).'/' );
			define('VERSION_CONTROL_SLUG', plugin_basename( dirname(__FILE__) ) );
			define('VERSION_CONTROL_FILE', __FILE__ );

		}

		/* Include required plugin files */
		private static function includes() {

			switch (is_admin()) :
				case true :
					/* loads admin files */
					include_once('classes/class.version-control.php');					
					include_once('classes/class.ajax.replace-plugin.php');				

					BREAK;

				case false :
					/* load front-end files */
					

					BREAK;
			endswitch;
		}

		/**
		*	Loads the correct .mo file for this plugin
		*
		*/
		private static function load_text_domain_init() {
			add_action( 'init' , array( __CLASS__ , 'load_text_domain' ) );
		}

		public static function load_text_domain() {
			load_plugin_textdomain( 'inbound-email' , false , VERSION_CONTROL_SLUG . '/lang/' );
		}


	}

	/* Initiate Plugin */
	$GLOBALS['Version_Control_Plugin'] = new Version_Control_Plugin;
	


}
