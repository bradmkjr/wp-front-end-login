<?php

/*
Plugin Name: WP Front End Login
Plugin URI: https://github.com/bradmkjr/WP-Front-End-Login/
Description: Does some stuff to make front end login work.
Version: 1.0.1
Author: Bradford Knowlton
Author URI: http://bradknowlton.com
Text Domain: wpfel
Domain Path: /languages
License:     GNU General Public License v2.0
License URI: https://github.com/bradmkjr/WP-Front-End-Login/blob/master/LICENSE
*/

if( ! class_exists('WPFrontEndLogin') ){

	class WPFrontEndLogin {
	    public function __construct()
	    {
	    
	    	add_shortcode( 'login_form', array( this, 'loginForm' ) );
	    
	    }
	    
	    public function loginForm(){
		    
		    $args = array(
				'echo'           => false,
				'remember'       => true,
				'redirect'       => ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
				'form_id'        => 'loginform',
				'id_username'    => 'user_login',
				'id_password'    => 'user_pass',
				'id_remember'    => 'rememberme',
				'id_submit'      => 'wp-submit',
				'label_username' => __( 'Username' ),
				'label_password' => __( 'Password' ),
				'label_remember' => __( 'Remember Me' ),
				'label_log_in'   => __( 'Log In' ),
				'value_username' => '',
				'value_remember' => true
			);
		    
		    $output = wp_login_form( $args);
		    
		    return $output;
	    }
	    
	}
	 
	$wpFrontEndLogin = new WPFrontEndLogin();
	
}
