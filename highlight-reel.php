<?php 

/*
Plugin Name: Highlight Reel
Plugin URI: http://www.highlightreelwp.com
Description: A simple plugin that enables you to easily display your latest Dribbble shots on your website.
Author: Jamie Cassidy
Version: 1.1
Author URI: http://www.jamiecassidy.co.uk
*/

class HighlightReel {

    private $dribbble_username;
    private $shots_to_show;
    private $plugin_path;
    private $cache_path;
    private $feed_url;

    function __construct() 
    {	
    	$this->dribbble_username = get_option( 'hr_dribbble_username' );
    	$this->shots_to_show = get_option( 'hr_shots_to_show' );
    	$this->plugin_path = plugin_dir_path(__FILE__);
    	$this->cache_path = $this->plugin_path . '/cache/';
    	$this->feed_url = "http://api.dribbble.com/players/" . $this->dribbble_username . "/shots?per_page=" . $this->shots_to_show;
    	
        register_activation_hook( __FILE__, array($this, 'hr_activate') );
        
        add_action( 'admin_notices', array(&$this, 'hr_admin_notice') );
        add_action( 'admin_menu', array(&$this, 'hr_options_menu') );
        add_action( 'admin_init', array(&$this, 'hr_register_settings') );
        add_shortcode( 'highlight-reel', array(&$this, 'hr_shortcode') );
    }
    
    function hr_activate() 
    {
        update_option( 'hr_shots_to_show', '3' );
        chmod($this->cache_path, 0777);
    }
    
    function hr_options_menu() {
     	add_options_page( 'Highlight Reel Settings', 'Highlight Reel', 'manage_options', 'hr-options', array(&$this, 'hr_options') );
    }
    
    function hr_register_settings() {
    	register_setting( 'hr-settings-group', 'hr_dribbble_username' );
    	register_setting( 'hr-settings-group', 'hr_shots_to_show' );
    }
    
    function hr_options() {
    	if (!current_user_can( 'manage_options' )) {
    		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    	}
    	
    	if(isset($_POST['hr_dribbble_username'])) {
    		$this->hr_force_fresh_feed( $this->feed_url, $this->dribbble_username );
    	}
    	
    	echo '<div class="wrap">' . PHP_EOL;
    		echo '<h2>Highlight Reel Settings</h2>' . PHP_EOL;
    		echo '<form method="post" action="options.php">' . PHP_EOL;
    			settings_fields( 'hr-settings-group' );
    			do_settings_sections( 'hr' );
    			echo '<p><label for="hr_dribbble_username">Dribble Username:</label><br />' . PHP_EOL;
    			echo '<input type="text" name="hr_dribbble_username" id="hr_dibbble_username" value="' . get_option( 'hr_dribbble_username' ) . '" size="40" /></p>' . PHP_EOL;
    			echo '<p><label for="hr_shots_to_show">Number of Shots to show:</label><br />' . PHP_EOL;
    			echo '<input type="text" name="hr_shots_to_show" id="hr_shots_to_show" value="' . get_option( 'hr_shots_to_show' ) . '" size="10" /> <span class="description">(Leave blank for unlimited)</span></p>' . PHP_EOL;
    			submit_button();
    		echo '</form>' . PHP_EOL;
    	echo '</div>' . PHP_EOL;
    }
    
    function hr_admin_notice() {
    	if( $this->hr_check_username() == false ) {
    		echo '<div class="error"><p><strong>Highlight Reel:</strong> You must <a href="options-general.php?page=hr-options">enter your Dribbble username</a> before this plugin will function.</p></div>';
    	}	
    }
    
    function hr_shortcode() { 
    	
    	if( $this->hr_check_username() == false ) {
    		
    		$output .= '<strong>Highlight Reel:</strong> You must specify your Dribbble username in the Highlight Reel settings area before this plugin will function correctly.';
    	
    	} else {
    		
    		
    		$shots = $this->hr_get_feed( $this->feed_url, $this->dribbble_username );
    		if(sizeof($shots->shots) != $this->shots_to_show) {
    			$shots = $this->hr_force_fresh_feed( $this->feed_url, $this->dribbble_username );
    		}
    		
    		//print_r( $shots );
    		
    		if( $shots->message == "Not found" ) {
    			$output .= '<strong>Highlight Reel:</strong> The username you specified <em>(' . $this->dribbble_username . ')</em> could not be found on Dribbble.';
    		} else if($shots->total < 1) {
    			$output .= 'No shots to show for the Dribbble username <strong>' . $this->dribbble_username. '</strong>.';
    		} else {
    			$output .= '<ul class="highlight-reel">' . PHP_EOL;
    			foreach( $shots->shots as $shot ) {
    				$output .= '<li>' . PHP_EOL;
    					$output .= '<a href="' . $shot->url . '"><img src="' . $shot->image_url . '" alt="' . $shot->title . ' by ' . $shot->player->name . '" /></a>' . PHP_EOL;
    					$output .= '<span class="hr_caption"><a href="' . $shot->url . '">' . $shot->title . '</a></span>' . PHP_EOL;
    				$output .= '</li>' . PHP_EOL;
    			}
    			$output .= '</ul>' . PHP_EOL;
    		}
    		
    	}
    	
    	return $output;
    }
    
    function hr_check_username() {
    	if( empty( $this->dribbble_username ) ) {
    		return false;
    	} else {
    		return true;
    	}
    }
        
    function hr_get_feed($url, $username) {
    	$cache = $this->cache_path . $username . '.cache';
    	if(file_exists($cache)){
    		if(filemtime($cache) + 3600 >= time()) {
				$result = file_get_contents($cache);
			} else {
				$json = $this->do_curl($url);
				file_put_contents($cache, $json);
				$result = file_get_contents($cache);
			}
		} else {
			$json = $this->do_curl($url);
			file_put_contents($cache, $json);
			$result = file_get_contents($cache);
    	}
    	
    	return json_decode($result);
    }
    
    function hr_force_fresh_feed($url, $username) {
    	$cache = $this->cache_path . $username . '.cache';
    	$json = $this->do_curl($url);
    	file_put_contents($cache, $json);
    	$result = file_get_contents($cache);
    	
    	return json_decode($result);
    }
    
    function do_curl($url) {
    	$ch = curl_init();
    	curl_setopt( $ch, CURLOPT_URL, $url );
    	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    	$result = curl_exec( $ch );
    	curl_close( $ch );
    	
    	return $result;
    }

}

new HighlightReel();


/* Template Tag */
function highlight_reel() {
	$hr = new HighlightReel();
	echo $hr->hr_shortcode();
}

?>