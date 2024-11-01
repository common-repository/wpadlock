<?php 
/**
 * @package wPadlock
 */
/*
Plugin Name: wPadlock
Plugin URI: https://developer.wordpress.org/plugins/wpadlock/
Description: wPadlock makes your login page a more secure place! 
Version: 1.0
Author: Goran Jovanovic
Author URI: https://wpadlock.com
License:      GPLv2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/

/*
Copyright 2017 Goran Jovanovic
*/


//define('WP_DEBUG', true);
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Add admin page to setup plugin ----------------------------------------------------------------------
add_action('admin_menu', 'wpdlck_add_the_settings_page');

function wpdlck_add_the_settings_page(){
	add_menu_page( 'wPadlock', 'wPadlock', 'manage_options', 'wpadlock-settings', 'wpdlck_settings_page', plugins_url( 'wpadlock/images/logo.png', dirname(__FILE__) ), 2 );
	add_action( 'admin_init', 'wpdlck_update_settings' );
}

if( !function_exists("wpdlck_update_settings") ) {
	function wpdlck_update_settings() {
	  register_setting( 'wpadlock_settings', 'wpadlock_key' );
	}
}

function wpdlck_settings_page(){
	
	if (substr($_SERVER['REMOTE_ADDR'], 0, 4) == '127.' || $_SERVER['REMOTE_ADDR'] == '::1') {
		echo "<h2>You are on localhost. Protection is disabled. Testing of API key is disabled.</h2><hr>";
	}
	
	echo "<form method='post' action='options.php'>
	<h1>wPadlock Settings</h1>
	<p>After registering your website with <a href='https://wpadlock.com' title='wPadlock official website' targer='_blank'>wPadlock</a> you'll receive the key. Paste your key below to activate the pin security.</p>";
	settings_fields( 'wpadlock_settings' );
	do_settings_sections( 'wpadlock_settings' );
	echo "<input size='80' type='text' id='wpadlock_key' name='wpadlock_key' value='".esc_attr( get_option('wpadlock_key') )."' placeholder='e.g. 64df88kfi25xsr9a06ac1bfbc6e9c45768frghercs4 / leave blank to disable' autocomplete='off'/>";
	echo "<a href='#' class='button button-primary' id='test-connection'>Test Key</a>";
	echo "<span style='line-height: 25px; margin-left: 5px;' id='connection_test_result'></span>";
	submit_button("Save Key");		
	echo"</form>";
	
	echo "<script>
	jQuery('#test-connection').click(function(){
		the_key = jQuery('#wpadlock_key').val();
		jQuery('#connection_test_result').addClass('dashicons dashicons-marker');
		jQuery.ajax('https://wpadlock.com/test/'+the_key+'/".base64_encode(get_site_url())."/', {
		   success: function(data) {
			  if(data){
				  console.log(data);
				jQuery('#connection_test_result').removeClass().addClass('dashicons dashicons-yes');
			  }else{
				jQuery('#connection_test_result').removeClass().addClass('dashicons dashicons-no');
			  }
		   },
		   error: function() {
				jQuery('#connection_test_result').html('There has been an error. Please try later.');
		   }
		});
	});
	</script>";		
}

function wpdlck_login_script() {
	if (substr($_SERVER['REMOTE_ADDR'], 0, 4) == '127.' || $_SERVER['REMOTE_ADDR'] == '::1') {
		// Development mode (localhost detected). Protection disabled.
	}
	else{
		if(!empty(esc_attr( get_option('wpadlock_key') )))
			wp_enqueue_script( 'locker', plugin_dir_url( __FILE__ ) . '/js/locker.js', array( 'jquery' ), '1.0.0', true );
	}
}
add_action( 'login_enqueue_scripts', 'wpdlck_login_script' );


function wpdlck_external_auth() {
	
	if (substr($_SERVER['REMOTE_ADDR'], 0, 4) == '127.' || $_SERVER['REMOTE_ADDR'] == '::1') {
			// Localhost detected. Protection disabled.
	}
	else{
	
		if(!empty(esc_attr( get_option('wpadlock_key') ))){
			if(isset($_POST['auth_code']) && !empty($_POST['auth_code'])){
				$response = wp_remote_get( 'https://wpadlock.com/check/'.esc_attr( get_option('wpadlock_key') ).'/'.$_POST['auth_code'].'', array( 'timeout' => 120, 'httpversion' => '1.1' ) );
				$decoded = json_decode($response['body']);
				if($decoded->response != "true"){
					wp_logout();
				}
			}else{
				wp_logout();
			}
		}
	}
}
add_action('wp_login', 'wpdlck_external_auth', 10, 2);

function wpdlck_deactivation() {
  delete_option( 'wpadlock_key' );
}
register_deactivation_hook( __FILE__, 'wpdlck_deactivation' );
?>