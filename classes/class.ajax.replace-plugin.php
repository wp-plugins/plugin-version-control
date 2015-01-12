<?php

if ( !class_exists('Version_Control_Ajax')	) {

	/**
	*	This class loads miscellaneous WordPress AJAX listeners
	*/
	class Version_Control_Ajax {

		/**
		*	Initializes class
		*/
		public function __construct() {
			self::load_hooks();
		}

		/**
		*	Loads hooks and filters
		*/
		public static function load_hooks() {


			/* Adds listener to save email data */
			add_action( 'wp_ajax_version_control_replace_plugin', array( __CLASS__ , 'replace_plugin' ) );

		}


		/**
		*	Sends test email
		*/
		public static function replace_plugin() {

			if ( ! current_user_can('delete_plugins') ) {
				wp_die(__('You do not have sufficient permissions to delete plugins for this site.'));
			}

			/* load pclzip */
			include_once( ABSPATH . '/wp-admin/includes/class-pclzip.php');

			$version_download = $_POST['version_download'];
			$plugin = $_POST['plugin'];

			/* get plugin path */
			$plugin_path = WP_PLUGIN_DIR . '/' . $plugin;

			/* get files in plugin directory currently */
			self::delete_plugin_folder( $plugin_path );

			/* create temp file */
			$temp_file = tempnam('/tmp', 'TEMPPLUGIN' );

			/* get zip file contents from svn */
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $version_download);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_FAILONERROR, true);
			curl_setopt($ch, CURLOPT_AUTOREFERER, true);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER,true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			$file = curl_exec($ch);
			curl_close($ch);

			/* write zip file to temp file */
			$handle = fopen($temp_file, "w");
			fwrite($handle, $file);
			fclose($handle);


			/* extract temp file to plugins direction */
			$archive = new PclZip($temp_file);
			$result = $archive->extract( PCLZIP_OPT_PATH, WP_PLUGIN_DIR , PCLZIP_OPT_REPLACE_NEWER );
			if ($result == 0) {
				die("Error : ".$archive->errorInfo(true));
			}

			/* delete templ file */
			unlink($temp_file);

			header('HTTP/1.1 200 OK');
			echo 1;
			exit;
		}

		/**
		*	deletes plugin folder
		*/
		public static function delete_plugin_folder($dirPath) {
			if (is_dir($dirPath)) {
				$objects = scandir($dirPath);
				foreach ($objects as $object) {
					if ($object != "." && $object !="..") {
						if (filetype($dirPath . DIRECTORY_SEPARATOR . $object) == "dir") {
							self::delete_plugin_folder($dirPath . DIRECTORY_SEPARATOR . $object);
						} else {
							unlink($dirPath . DIRECTORY_SEPARATOR . $object);
						}
					}
				}
				reset($objects);
				rmdir($dirPath);
			}

		}
	}

	/* Loads Version_Control_Ajax pre init */
	$Version_Control_Ajax = new Version_Control_Ajax();

}