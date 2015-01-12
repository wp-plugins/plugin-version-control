<?php

if ( !class_exists('Version_Control')	) {

	class Version_Control {
		
		static $plugins_api = "https://api.wordpress.org/plugins/info/1.0/%s.json?fields=versions"; /* plugin data api url */
		static $plugin; /* placeholder for current plugin being processed */
		static $plugin_shrtname; /* placeholder for current plugin being processed */
		static $json; /* placeholder for transient json data */
		static $response; /* result from wordpress api */
		static $plugin_data; /* array version of api response containing plugin data */
		
		/**
		* Load class instance
		*/
		public function __construct() {
			self::load_hooks();
		}

		/**
		* Load hooks and filters
		*
		*/
		private static function load_hooks() {
			/* add controls */
			add_filter('plugin_action_links', array( __CLASS__ ,  'add_plugin_options' ) , 10 , 2); 
			
			/* enqueue js includes */
			add_action( 'admin_enqueue_scripts', array( __CLASS__ , 'enqueue_admin_scripts' ) );
			
			/* add js listeners */
			add_action( 'admin_print_footer_scripts', array( __CLASS__ , 'print_js_css' ) );
		}

		/**
		*  Adds version control options to plugin links
		*/
		public static function add_plugin_options( $links, $plugin ) {
			
			/* get plugin slug */
			$parts = explode( '/', $plugin );
			
			/* set current plugin being processed */
			self::$plugin = $parts[0];
			
			self::generate_plugin_shortname();
			
			/* Load plugin api data into transient */
			self::load_api_data();
			
			/* load tags into dropbox */
			$tags = self::generate_version_dropdown_html();
			
			if ($tags) {
				$links['tags'] = $tags;
				$links['switch'] = self::generate_switch_button_html();
			}
			//echo self::$plugin;
			return $links;
		}

		/**
		*  Attempts to generate plugin id under 45 chars for transient data storage
		*/
		public static function generate_plugin_shortname() {
			$vowels = array("a", "e", "i", "o", "u", "A", "E", "I", "O", "U", " ");
			self::$plugin_shrtname = str_replace( $vowels, "", self::$plugin );
			self::$plugin_shrtname = str_replace( ' ', "", self::$plugin_shrtname );
		}
		
		/**
		*  Load api data into transient
		*/
		public static function load_api_data( ) {
			
			$transient = get_transient( self::$plugin_shrtname );
			
			if ( $transient ) {
				self::$plugin_data = $transient;
				return $transient;
			}
			
			/* load json from plugin */
			self::get_json_data(); 
		}
		
		/**
		*  Connect to wp api to get plugin information
		*/
		public static function get_json_data() {
			/*  build url */
			$url = sprintf( self::$plugins_api , self::$plugin );
			
			/* poll API */
			self::$response = wp_remote_retrieve_body( wp_remote_get( $url ) );
			
			/* set json response into array dataset */
			self::$plugin_data = ( is_array(self::$response) ) ? json_decode( self::$response , true ) : array( 'skip' );
			
			/* remove clutter -  sections */
			if ( isset(self::$plugin_data['sections']) ) {
				unset( self::$plugin_data['sections'] );
			}
			
			/* remove clutter -  changelog */
			if ( isset(self::$plugin_data['sections']) ) {
				unset( self::$plugin_data['sections'] );
			}
			
			set_transient( self::$plugin_shrtname , self::$plugin_data , 60 * 60 * 60 * 12 );
			
		}
		
		/**
		*  Build dropdown input from available tags
		*/
		public static function generate_version_dropdown_html() {
				
			if (!isset( self::$plugin_data['versions'] ) || !self::$plugin_data['versions'] ) {				
				return '';
			} 	
			
			$html = "<select id='".self::$plugin."-versions' class='version-dropdown'>";
			
			foreach( self::$plugin_data['versions'] as $version => $download ) {
				
				if ($version == 'trunk') {
					continue;
				}
				
				$html .= "<option value='".$download."'>".$version . "</option>";
			}			
			
			$html .= "</select>";
			
			return $html;
		}
		
		
		/**
		*  Generate switch version dropdown button
		*/
		public static function generate_switch_button_html() {
			return '<span class="switch-versions button-primary primary" data-style="expand-right" data-spinner-color="#ffffff" id="'.self::$plugin.'" title="'.__( 'Switch Versions' , 'version-control' ).'">'.__( 'Switch' , 'version-control' ).'</span><div class="spinner" id="spinner-'.self::$plugin.'"></div>';
		}
		
		/**
		*  Enqueues JS
		*/
		public static function enqueue_admin_scripts() {
			
			$screen = get_current_screen();
			
			if ( $screen->base != 'plugins' ) {
				return;
			}
			
			
		}
		
		/**
		*  Print JS Listners for Switching Plugins
		*/
		public static function print_js_css() {
			
			$screen = get_current_screen();
			
			if ( $screen->base != 'plugins' ) {
				return;
			}
			
			
			
			?>
			<script>
			jQuery( 'document' ).ready( function() {
				
				jQuery( '.switch-versions' ).on( 'click' , function() {
					
					var result = confirm("<?php _e('Are you sure you want to delete the current version and install selected version? This switch will not reverse any database upgrade routines already performed by a plugin update.' , 'version-control' ); ?>");
					
					if (!result) {
						return;
					}
					
					/* get download url */
					var version_download = jQuery( '#' + this.id + '-versions' ).val();
					
					/* toggle spinner */
					jQuery('#spinner-'+this.id).show();
					
					/* run ajax to replace plugin */
					jQuery.ajax({
						type: "POST",
						url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
						data: {
							action: 'version_control_replace_plugin',
							plugin: this.id,
							version_download : version_download
						},
						dataType: 'html',
						timeout: 10000,
						success: function (response) {
							if ( response == 1 ) {
								
								/* toggle spinner */
								jQuery('#spinner-'+this.id).show();
					
								/* reload page */
								location.reload();
								
							} else {
								alert( response );								
								
								/* toggle spinner */
								jQuery('#spinner-'+this.id).show();
							}
						},
						error: function(request, status, err) {
							alert(status);
						}
					});


				});
			
			});
			</script>
			<style>
			.row-actions .version-dropdown {
				font-size:10px;
				height:19px;
			}
			
			body .switch .switch-versions{
				vertical-align:top;
				font-size:10px;
				height:23px;
				line-height:21px;
			}
			</style>
			<?php
		
		}
	}

	$GLOBALS['Version_Control'] = new Version_Control;
}
