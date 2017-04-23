<?php
/*
Plugin Name: WP Front End Login
Plugin URI: https://github.com/bradmkjr/wp-front-end-login
Description: Does some stuff to make front end login work.
Version: 1.3.1
Author: Bradford Knowlton
Author URI: http://bradknowlton.com
License:     GNU General Public License v2.0
License URI: https://github.com/bradmkjr/wp-front-end-login/blob/master/LICENSE
*/

if( ! class_exists('WPFrontEndLogin') ){

	class WPFrontEndLogin {
	
		// Setup private variables to pass values from after_theme_setup function to shortcode
	
		private $args = array( 'action', 'registration', 'checkemail', '_wpnonce', 'loggedout', 'error', 'redirect_to' );
	
		private $wp_error;
		
		private $error;
		
		private $errors;
		
		private $interim_login;
		
		private $action;
		
		private $message;
		
		private $redirect_to;
		
		private $rp_key;
		
		private $user_login;
		
		private $user_email;
		
		private $input_id;
	
		private $aria_describedby_error;
		
		private $rememberme;
	
	    public function __construct()
	    {
	    
	    	if( ! is_admin() ){
	    	
	    		// wp_enqueue_style( 'login' );
	    		
	    		// cheat to load up strenght meter CSS
	    		wp_enqueue_style( 'forms' );
		
				add_action( 'init', array( $this, 'init' ) );
	    
		    	add_shortcode( 'login_form', array( $this, 'loginForm' ) );
		    	
		    	add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ) );
		    	
		    	add_action( 'wp_head', array( $this, 'wp_head' ) );
		    	
		    	add_filter( 'site_url', array( $this, 'site_url' ), 10, 4 );
		    	
		    	add_filter( 'logout_redirect', array( $this, 'logout_redirect' ), 10, 3 );
		    	
		    	add_filter( 'lostpassword_url', array( $this, 'lostpassword_url' ), 10, 2 );
		    	
		    	add_filter( 'login_url', array( $this, 'login_url' ), 10, 3 );
		    	
		    	add_filter( 'logout_url', array( $this, 'logout_url' ), 10, 2 );
		    	
		    	add_filter( 'register_url', array( $this, 'register_url' ) );
		    	
				add_filter( 'login_redirect', array( $this, 'login_redirect' ), 10, 3 );
				
				add_filter( 'registration_redirect', array( $this, 'registration_redirect' ) );
				
				add_filter( 'lostpassword_redirect', array( $this, 'lostpassword_redirect' ) );    	
		    	
	    	}
	    
	    }
	    
	    /*
	    * block access to dashboard for all logged in users without admin privilages
	    */
	    public function init() {
			if ( is_admin() && ! current_user_can( 'administrator' ) &&
				! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				wp_redirect( home_url() );
				exit;
			}
		}
	    
	    /* LoginForm shortcode function */
	    public function loginForm(){
	    
	    	/**
			 * Filters the message to display above the login form.
			 *
			 * @since 2.1.0
			 *
			 * @param string $message Login message text.
			 */
			$message = apply_filters( 'login_message', $this->message );
			if ( !empty( $message ) )
				echo $message . "\n";
		
			if ( empty( $this->wp_error ) )
				$this->wp_error = new WP_Error();
				
			if( is_wp_error( $this->errors ) ){
				$this->wp_error = $this->errors;
			}	
		
			// In case a plugin uses $error rather than the $wp_errors object
			if ( !empty( $this->error ) ) {
				$this->wp_error->add('error', $this->error);
				unset($this->error);
			}
			
			if ( $this->wp_error->get_error_code() ) {
				$this->errors = '';
				$this->messages = '';
				foreach ( $this->wp_error->get_error_codes() as $code ) {
					$severity = $this->wp_error->get_error_data( $code );
					foreach ( $this->wp_error->get_error_messages( $code ) as $error_message ) {
						if ( 'message' == $severity )
							$this->messages .= '	' . $error_message . "<br />\n";
						else
							$this->errors .= '	' . $error_message . "<br />\n";
					}
				}
				if ( ! empty( $this->errors ) ) {
					/**
					 * Filters the error messages displayed above the login form.
					 *
					 * @since 2.1.0
					 *
					 * @param string $errors Login error message.
					 */
					$output .= '<div id="login_error">' . apply_filters( 'login_errors', $this->errors ) . "</div>\n";
				}
				if ( ! empty( $this->messages ) ) {
					/**
					 * Filters instructional messages displayed above the login form.
					 *
					 * @since 2.5.0
					 *
					 * @param string $messages Login messages.
					 */
					$output .= '<p class="message">' . apply_filters( 'login_messages', $this->messages ) . "</p>\n";
				}
			}
			
			switch ($this->action) {

			case 'lostpassword' :
			case 'retrievepassword' :
	    	
	    	ob_start();
	    	?>
			<form name="lostpasswordform" id="lostpasswordform" action="<?php echo esc_url( network_site_url( 'wp-login.php?action=lostpassword', 'login_post' ) ); ?>" method="post">
				<p>
					<label for="user_login" ><?php _e( 'Username or Email Address' ); ?><br />
					<input type="text" name="user_login" id="user_login" class="input" value="<?php echo esc_attr($this->user_login); ?>" size="20" /></label>
				</p>
				<?php
				/**
				 * Fires inside the lostpassword form tags, before the hidden fields.
				 *
				 * @since 2.1.0
				 */
				do_action( 'lostpassword_form' ); ?>
				<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $this->redirect_to ); ?>" />
				<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Get New Password'); ?>" /></p>
			</form>
			
			<p id="nav">
			<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php _e('Log in') ?></a>
			<?php
			if ( get_option( 'users_can_register' ) ) :
				$registration_url = sprintf( '<a href="%s">%s</a>', esc_url( wp_registration_url() ), __( 'Register' ) );
			
				/** This filter is documented in wp-includes/general-template.php */
				echo ' | ' . apply_filters( 'register', $registration_url );
			endif;
			?>
			</p>
			<?php
	    	$output .= ob_get_contents();
			ob_end_clean();
			
			$this->input_id = 'user_login';
	    	
	    	break;
	    	
		    case 'resetpass' :
			case 'rp' :	
		    
		    ob_start();
		    	?>
				<form name="resetpassform" id="resetpassform" action="<?php echo esc_url( network_site_url( 'wp-login.php?action=resetpass', 'login_post' ) ); ?>" method="post" autocomplete="off">
					<input type="hidden" id="user_login" value="<?php echo esc_attr( $this->rp_login ); ?>" autocomplete="off" />
				
					<div class="user-pass1-wrap">
						<p>
							<label for="pass1"><?php _e( 'New password' ) ?></label>
						</p>
				
						<div class="wp-pwd">
							<span class="password-input-wrapper">
								<input type="password" data-reveal="1" data-pw="<?php echo esc_attr( wp_generate_password( 16 ) ); ?>" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" aria-describedby="pass-strength-result" />
							</span>
							<div id="pass-strength-result" class="hide-if-no-js" aria-live="polite"><?php _e( 'Strength indicator' ); ?></div>
						</div>
					</div>
				
					<p class="user-pass2-wrap">
						<label for="pass2"><?php _e( 'Confirm new password' ) ?></label><br />
						<input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" />
					</p>
				
					<p class="description indicator-hint"><?php echo wp_get_password_hint(); ?></p>
					<br class="clear" />
				
					<?php
					/**
					 * Fires following the 'Strength indicator' meter in the user password reset form.
					 *
					 * @since 3.9.0
					 *
					 * @param WP_User $user User object of the user whose password is being reset.
					 */
					do_action( 'resetpass_form', $user );
					?>
					<input type="hidden" name="rp_key" value="<?php echo esc_attr( $this->rp_key ); ?>" />
					<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Reset Password'); ?>" /></p>
				</form>
				
				<p id="nav">
				<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php _e( 'Log in' ); ?></a>
				<?php
				if ( get_option( 'users_can_register' ) ) :
					$registration_url = sprintf( '<a href="%s">%s</a>', esc_url( wp_registration_url() ), __( 'Register' ) );
				
					/** This filter is documented in wp-includes/general-template.php */
					echo ' | ' . apply_filters( 'register', $registration_url );
				endif;
				?>
				</p>
				
				<?php
	    	
				$output .= ob_get_contents();
				ob_end_clean();
				
				$this->input_id = 'user_pass';
	    	
			break; // end reset pass
			
			
			case 'register' :
				
				ob_start();
				
				?>
			<form name="registerform" id="registerform" action="<?php echo esc_url( site_url( 'wp-login.php?action=register', 'login_post' ) ); ?>" method="post" novalidate="novalidate">
				<p>
					<label for="user_login"><?php _e('Username') ?><br />
					<input type="text" name="user_login" id="user_login" class="input" value="<?php echo esc_attr( wp_unslash( $this->user_login ) ); ?>" size="20" /></label>
				</p>
				<p>
					<label for="user_email"><?php _e('Email') ?><br />
					<input type="email" name="user_email" id="user_email" class="input" value="<?php echo esc_attr( wp_unslash( $this->user_email ) ); ?>" size="25" /></label>
				</p>
				<?php
				/**
				 * Fires following the 'Email' field in the user registration form.
				 *
				 * @since 2.1.0
				 */
				do_action( 'register_form' );
				?>
				<p id="reg_passmail"><?php _e( 'Registration confirmation will be emailed to you.' ); ?></p>
				<br class="clear" />
				<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $this->redirect_to ); ?>" />
				<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Register'); ?>" /></p>
			</form>
			
			<p id="nav">
			<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php _e( 'Log in' ); ?></a> |
			<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php _e( 'Lost your password?' ); ?></a>
			</p>
			
			<?php
			
			$output .= ob_get_contents();
			ob_end_clean();
			
			$this->input_id = 'user_login';
	    	
	    	break;
	    	
	    	case 'login' :
			default:
			
			ob_start();
	    	?>
			
			<form name="loginform" id="loginform" action="<?php echo esc_url( site_url( 'wp-login.php', 'login_post' ) ); ?>" method="post">
				<p>
					<label for="user_login"><?php _e( 'Username or Email Address' ); ?><br />
					<input type="text" name="log" id="user_login"<?php echo $this->aria_describedby_error; ?> class="input" value="<?php echo esc_attr( $this->user_login ); ?>" size="20" /></label>
				</p>
				<p>
					<label for="user_pass"><?php _e( 'Password' ); ?><br />
					<input type="password" name="pwd" id="user_pass"<?php echo $this->aria_describedby_error; ?> class="input" value="" size="20" /></label>
				</p>
				<?php
				/**
				 * Fires following the 'Password' field in the login form.
				 *
				 * @since 2.1.0
				 */
				do_action( 'login_form' );
				?>
				<p class="forgetmenot"><label for="rememberme"><input name="rememberme" type="checkbox" id="rememberme" value="forever" <?php checked( $this->rememberme ); ?> /> <?php esc_html_e( 'Remember Me' ); ?></label></p>
				<p class="submit">
					<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Log In'); ?>" />
			<?php	if ( $this->interim_login ) { ?>
					<input type="hidden" name="interim-login" value="1" />
			<?php	} else { ?>
					<input type="hidden" name="redirect_to" value="<?php echo esc_attr($this->redirect_to); ?>" />
			<?php 	} ?>
			<?php   if ( $this->customize_login ) : ?>
					<input type="hidden" name="customize-login" value="1" />
			<?php   endif; ?>
					<input type="hidden" name="testcookie" value="1" />
				</p>
			</form>
			
			<?php if ( ! $this->interim_login ) { ?>
			<p id="nav">
			<?php if ( ! isset( $_GET['checkemail'] ) || ! in_array( $_GET['checkemail'], array( 'confirm', 'newpass' ) ) ) :
				if ( get_option( 'users_can_register' ) ) :
					$registration_url = sprintf( '<a href="%s">%s</a>', esc_url( wp_registration_url() ), __( 'Register' ) );
			
					/** This filter is documented in wp-includes/general-template.php */
					echo apply_filters( 'register', $registration_url ) . ' | ';
				endif;
				?>
				<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php _e( 'Lost your password?' ); ?></a>
			<?php endif; ?>
			</p>
			<?php } ?>
			
			<script type="text/javascript">
			function wp_attempt_focus(){
			setTimeout( function(){ try{
			<?php if ( $this->user_login ) { ?>
			d = document.getElementById('user_pass');
			d.value = '';
			<?php } else { ?>
			d = document.getElementById('user_login');
			<?php if ( 'invalid_username' == $this->wp_error->get_error_code() ) { ?>
			if( d.value != '' )
			d.value = '';
			<?php
			}
			}?>
			d.focus();
			d.select();
			} catch(e){}
			}, 200);
			}
			
			<?php if ( !$this->error ) { ?>
			wp_attempt_focus();
			<?php } ?>
			if(typeof wpOnload=='function')wpOnload();
			<?php if ( $this->interim_login ) { ?>
			(function(){
			try {
				var i, links = document.getElementsByTagName('a');
				for ( i in links ) {
					if ( links[i].href )
						links[i].target = '_blank';
				}
			} catch(e){}
			}());
			<?php } ?>
			</script>
			
			<?php
			
			$output .= ob_get_contents();
			ob_end_clean();
			
			break;
	    	
	    	
			} // end action switch
	        
		    if ( !empty($this->input_id) ) : 
		    	$output .= '<script type="text/javascript">
				try{document.getElementById("'.$this->input_id.'").focus();}catch(e){}
				if(typeof wpOnload=="function")wpOnload();
				</script>';
			endif;
		    
		    return $output;
	    }
	    
	    public function after_setup_theme(){

			//
			// Main
			//
			
			if( isset($_REQUEST['action']) || isset($_GET['key']) || isset($_GET['registration']) || !empty($_POST['log']) || isset( $_GET['checkemail'] ) ){

			$this->action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login';
			
			if ( empty($this->errors) )
				$this->errors = new WP_Error();
			
			if ( isset($_GET['key']) )
				$this->action = 'resetpass';
			
			// validate action so as to default to the login screen
			if ( !in_array( $this->action, array( 'postpass', 'logout', 'lostpassword', 'retrievepassword', 'resetpass', 'rp', 'register', 'login' ), true ) && false === has_filter( 'login_form_' . $this->action ) )
				$this->action = 'login';
				
			nocache_headers();
			
			if ( defined( 'RELOCATE' ) && RELOCATE ) { // Move flag is set
				if ( isset( $_SERVER['PATH_INFO'] ) && ($_SERVER['PATH_INFO'] != $_SERVER['PHP_SELF']) )
					$_SERVER['PHP_SELF'] = str_replace( $_SERVER['PATH_INFO'], '', $_SERVER['PHP_SELF'] );
			
				$url = dirname( set_url_scheme( 'http://' .  $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] ) );
				if ( $url != get_option( 'siteurl' ) )
					update_option( 'siteurl', $url );
			}
			
			//Set a cookie now to see if they are supported by the browser.
			$secure = ( 'https' === parse_url( wp_login_url(), PHP_URL_SCHEME ) );
			setcookie( TEST_COOKIE, 'WP Cookie check', 0, COOKIEPATH, COOKIE_DOMAIN, $secure );
			if ( SITECOOKIEPATH != COOKIEPATH )
				setcookie( TEST_COOKIE, 'WP Cookie check', 0, SITECOOKIEPATH, COOKIE_DOMAIN, $secure );
				
			/**
			 * Fires when the login form is initialized.
			 *
			 * @since 3.2.0
			 */
			do_action( 'login_init' );
			/**
			 * Fires before a specified login form action.
			 *
			 * The dynamic portion of the hook name, `$action`, refers to the action
			 * that brought the visitor to the login form. Actions include 'postpass',
			 * 'logout', 'lostpassword', etc.
			 *
			 * @since 2.8.0
			 */
			do_action( "login_form_{$action}" );
			
			$http_post = ('POST' == $_SERVER['REQUEST_METHOD']);
			$interim_login = isset($_REQUEST['interim-login']);
			
			switch ($this->action) {

			case 'postpass' :
				if ( ! array_key_exists( 'post_password', $_POST ) ) {
					wp_safe_redirect( wp_get_referer() );
					exit();
				}
			
				$hasher = new PasswordHash( 8, true );
			
				/**
				 * Filters the life span of the post password cookie.
				 *
				 * By default, the cookie expires 10 days from creation. To turn this
				 * into a session cookie, return 0.
				 *
				 * @since 3.7.0
				 *
				 * @param int $expires The expiry time, as passed to setcookie().
				 */
				$expire = apply_filters( 'post_password_expires', time() + 10 * DAY_IN_SECONDS );
				$referer = wp_get_referer();
				if ( $referer ) {
					$secure = ( 'https' === parse_url( $referer, PHP_URL_SCHEME ) );
				} else {
					$secure = false;
				}
				setcookie( 'wp-postpass_' . COOKIEHASH, $hasher->HashPassword( wp_unslash( $_POST['post_password'] ) ), $expire, COOKIEPATH, COOKIE_DOMAIN, $secure );
			
				wp_safe_redirect( wp_get_referer() );
				exit();
				
			// end postpass	

			case 'logout' :
				check_admin_referer('log-out');
			
				$user = wp_get_current_user();
			
				wp_logout();
			
				if ( ! empty( $_REQUEST['redirect_to'] ) ) {
					$redirect_to = $requested_redirect_to = $_REQUEST['redirect_to'];
				} else {
					$redirect_to = 'wp-login.php?loggedout=true';
					$requested_redirect_to = '';
				}
			
				/**
				 * Filters the log out redirect URL.
				 *
				 * @since 4.2.0
				 *
				 * @param string  $redirect_to           The redirect destination URL.
				 * @param string  $requested_redirect_to The requested redirect destination URL passed as a parameter.
				 * @param WP_User $user                  The WP_User object for the user that's logging out.
				 */
				$redirect_to = apply_filters( 'logout_redirect', $redirect_to, $requested_redirect_to, $user );
				wp_safe_redirect( $redirect_to );
				exit();
			
			// end logout
			
			case 'lostpassword' :
			case 'retrievepassword' :
			
				if ( $http_post ) {
					$errors = retrieve_password();
					if ( !is_wp_error($errors) ) {
						$redirect_to = !empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : 'wp-login.php?checkemail=confirm';
						wp_safe_redirect( $redirect_to );
						exit();
					}
				}
			
				if ( isset( $_GET['error'] ) ) {
					if ( 'invalidkey' == $_GET['error'] ) {
						$errors->add( 'invalidkey', __( 'Your password reset link appears to be invalid. Please request a new link below.' ) );
					} elseif ( 'expiredkey' == $_GET['error'] ) {
						$errors->add( 'expiredkey', __( 'Your password reset link has expired. Please request a new link below.' ) );
					}
				}
			
				$lostpassword_redirect = ! empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '';
				/**
				 * Filters the URL redirected to after submitting the lostpassword/retrievepassword form.
				 *
				 * @since 3.0.0
				 *
				 * @param string $lostpassword_redirect The redirect destination URL.
				 */
				$this->redirect_to = apply_filters( 'lostpassword_redirect', $lostpassword_redirect );
			
				/**
				 * Fires before the lost password form.
				 *
				 * @since 1.5.1
				 */
				do_action( 'lost_password' );
			
				/* login_header(__('Lost Password'), '<p class="message">' . __('Please enter your username or email address. You will receive a link to create a new password via email.') . '</p>', $errors); */
			
				$this->user_login = isset($_POST['user_login']) ? wp_unslash($_POST['user_login']) : '';
			
				// login_footer('user_login');
				
			break; // end lost password
			
			
			case 'resetpass' :
			case 'rp' :
				list( $rp_path ) = explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) );
				$rp_cookie = 'wp-resetpass-' . COOKIEHASH;
				if ( isset( $_GET['key'] ) ) {
					$value = sprintf( '%s:%s', wp_unslash( $_GET['login'] ), wp_unslash( $_GET['key'] ) );
					setcookie( $rp_cookie, $value, 0, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
					wp_safe_redirect( remove_query_arg( array( 'key', 'login' ) ) );
					exit;
				}
				
				if ( isset( $_COOKIE[ $rp_cookie ] ) && 0 < strpos( $_COOKIE[ $rp_cookie ], ':' ) ) {
					list( $this->rp_login, $this->rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );
					$user = check_password_reset_key( $this->rp_key, $this->rp_login );
					if ( isset( $_POST['pass1'] ) && ! hash_equals( $this->rp_key, $_POST['rp_key'] ) ) {
						$user = false;
					}
				} else {
					$user = false;
				}
				
				if ( ! $user || is_wp_error( $user ) ) {
					setcookie( $rp_cookie, ' ', time() - YEAR_IN_SECONDS, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
					if ( $user && $user->get_error_code() === 'expired_key' )
						wp_redirect( site_url( 'wp-login.php?action=lostpassword&error=expiredkey' ) );
					else
						wp_redirect( site_url( 'wp-login.php?action=lostpassword&error=invalidkey' ) );
					exit;
				}
			
				$this->errors = new WP_Error();
			
				if ( isset($_POST['pass1']) && $_POST['pass1'] != $_POST['pass2'] )
					$this->errors->add( 'password_reset_mismatch', __( 'The passwords do not match.' ) );
			
				/**
				 * Fires before the password reset procedure is validated.
				 *
				 * @since 3.5.0
				 *
				 * @param object           $errors WP Error object.
				 * @param WP_User|WP_Error $user   WP_User object if the login and reset key match. WP_Error object otherwise.
				 */
				do_action( 'validate_password_reset', $this->errors, $user );
			
				if ( ( ! $this->errors->get_error_code() ) && isset( $_POST['pass1'] ) && !empty( $_POST['pass1'] ) ) {
					reset_password($user, $_POST['pass1']);
					setcookie( $rp_cookie, ' ', time() - YEAR_IN_SECONDS, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
					// login_header( __( 'Password Reset' ), '<p class="message reset-pass">' . __( 'Your password has been reset.' ) . ' <a href="' . esc_url( wp_login_url() ) . '">' . __( 'Log in' ) . '</a></p>' );
					// login_footer();
					
					$this->message = '<p class="message reset-pass">' . __( 'Your password has been reset.' ) . ' <a href="' . esc_url( wp_login_url() ) . '">' . __( 'Log in' ) . '</a></p>';
					
					return;
					exit;
				}
			
				wp_enqueue_script('utils');
				wp_enqueue_script('user-profile');
			
				// login_header(__('Reset Password'), '<p class="message reset-pass">' . __('Enter your new password below.') . '</p>', $errors );
			
				// login_footer('user_pass');
			
			break;
			
			
			case 'register' :
				if ( is_multisite() ) {
					/**
					 * Filters the Multisite sign up URL.
					 *
					 * @since 3.0.0
					 *
					 * @param string $sign_up_url The sign up URL.
					 */
					wp_redirect( apply_filters( 'wp_signup_location', network_site_url( 'wp-signup.php' ) ) );
					exit;
				}
			
				if ( !get_option('users_can_register') ) {
					wp_redirect( site_url('wp-login.php?registration=disabled') );
					exit();
				}
			
				$this->user_login = '';
				$this->user_email = '';
				if ( $http_post ) {
					$this->user_login = isset( $_POST['user_login'] ) ? $_POST['user_login'] : '';
					$this->user_email = isset( $_POST['user_email'] ) ? $_POST['user_email'] : '';
					$this->errors = register_new_user($this->user_login, $this->user_email);
					if ( !is_wp_error($this->errors) ) {
						$this->redirect_to = !empty( $_POST['redirect_to'] ) ? $_POST['redirect_to'] : 'wp-login.php?checkemail=registered';
						wp_safe_redirect( $this->redirect_to );
						exit();
					}
				}
			
				$registration_redirect = ! empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '';
				/**
				 * Filters the registration redirect URL.
				 *
				 * @since 3.0.0
				 *
				 * @param string $registration_redirect The redirect destination URL.
				 */
				$this->redirect_to = apply_filters( 'registration_redirect', $registration_redirect );
				
				// login_header(__('Registration Form'), '<p class="message register">' . __('Register For This Site') . '</p>', $errors);
						
			// login_footer('user_login');
			break;
			
			
			case 'login' :
			default:
			
				$secure_cookie = '';
				$customize_login = isset( $_REQUEST['customize-login'] );
				if ( $customize_login )
					wp_enqueue_script( 'customize-base' );
			
				// If the user wants ssl but the session is not ssl, force a secure cookie.
				if ( !empty($_POST['log']) && !force_ssl_admin() ) {
					$this->user_name = sanitize_user($_POST['log']);
					$user = get_user_by( 'login', $this->user_name );
			
					if ( ! $user && strpos( $this->user_name, '@' ) ) {
						$user = get_user_by( 'email', $this->user_name );
					}
			
					if ( $user ) {
						if ( get_user_option('use_ssl', $user->ID) ) {
							$secure_cookie = true;
							force_ssl_admin(true);
						}
					}
				}
				
				if ( isset( $_REQUEST['redirect_to'] ) ) {
					$this->redirect_to = $_REQUEST['redirect_to'];
					// Redirect to https if user wants ssl
					if ( $secure_cookie && false !== strpos($this->redirect_to, 'wp-admin') )
						$this->redirect_to = preg_replace('|^http://|', 'https://', $this->redirect_to);
				} else {
					$this->redirect_to = admin_url();
				}
			
				$reauth = empty($_REQUEST['reauth']) ? false : true;
				
				$user = wp_signon( array(), $secure_cookie );
			
				if ( empty( $_COOKIE[ LOGGED_IN_COOKIE ] ) ) {
					if ( headers_sent() ) {
						/* translators: 1: Browser cookie documentation URL, 2: Support forums URL */
						$user = new WP_Error( 'test_cookie', sprintf( __( '<strong>ERROR</strong>: Cookies are blocked due to unexpected output. For help, please see <a href="%1$s">this documentation</a> or try the <a href="%2$s">support forums</a>.' ),
							__( 'https://codex.wordpress.org/Cookies' ), __( 'https://wordpress.org/support/' ) ) );
					} elseif ( isset( $_POST['testcookie'] ) && empty( $_COOKIE[ TEST_COOKIE ] ) ) {
						// If cookies are disabled we can't log in even with a valid user+pass
						/* translators: 1: Browser cookie documentation URL */
						$user = new WP_Error( 'test_cookie', sprintf( __( '<strong>ERROR</strong>: Cookies are blocked or not supported by your browser. You must <a href="%s">enable cookies</a> to use WordPress.' ),
							__( 'https://codex.wordpress.org/Cookies' ) ) );
					}
				}
			
				$requested_redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '';
				/**
				 * Filters the login redirect URL.
				 *
				 * @since 3.0.0
				 *
				 * @param string           $redirect_to           The redirect destination URL.
				 * @param string           $requested_redirect_to The requested redirect destination URL passed as a parameter.
				 * @param WP_User|WP_Error $user                  WP_User object if login was successful, WP_Error object otherwise.
				 */
				$this->redirect_to = apply_filters( 'login_redirect', $redirect_to, $requested_redirect_to, $user );
				
				if ( !is_wp_error($user) && !$reauth ) {
					if ( $this->interim_login ) {
						$this->message = '<p class="message">' . __('You have logged in successfully.') . '</p>';
						$this->interim_login = 'success';
						// login_header( '', $message ); ?>
						</div>
						<?php
						/** This action is documented in wp-login.php */
						do_action( 'login_footer' ); ?>
						<?php if ( $customize_login ) : ?>
							<script type="text/javascript">setTimeout( function(){ new wp.customize.Messenger({ url: '<?php echo wp_customize_url(); ?>', channel: 'login' }).send('login') }, 1000 );</script>
						<?php endif; ?>
						</body></html>
			<?php		exit;
					}
			
					if ( ( empty( $this->redirect_to ) || $this->redirect_to == 'wp-admin/' || $this->redirect_to == admin_url() ) ) {
						// If the user doesn't belong to a blog, send them to user admin. If the user can't edit posts, send them to their profile.
						if ( is_multisite() && !get_active_blog_for_user($user->ID) && !is_super_admin( $user->ID ) )
							$this->redirect_to = user_admin_url();
						elseif ( is_multisite() && !$user->has_cap('read') )
							$this->redirect_to = get_dashboard_url( $user->ID );
						elseif ( !$user->has_cap('edit_posts') )
							$this->redirect_to = $user->has_cap( 'read' ) ? admin_url( 'profile.php' ) : home_url();
			
						wp_redirect( $this->redirect_to );
						exit();
					}
					
					wp_safe_redirect($this->redirect_to);
					exit();
				}
			
				
				$this->errors = $user;
				
				// Clear errors if loggedout is set.
				if ( !empty($_GET['loggedout']) || $reauth )
					$this->errors = new WP_Error();
			
				if ( $this->interim_login ) {
					if ( ! $this->errors->get_error_code() )
						$this->errors->add( 'expired', __( 'Your session has expired. Please log in to continue where you left off.' ), 'message' );
				} else {
					// Some parts of this script use the main login form to display a message
					if		( isset($_GET['loggedout']) && true == $_GET['loggedout'] )
						$this->errors->add('loggedout', __('You are now logged out.'), 'message');
					elseif	( isset($_GET['registration']) && 'disabled' == $_GET['registration'] )
						$this->errors->add('registerdisabled', __('User registration is currently not allowed.'));
					elseif	( isset($_GET['checkemail']) && 'confirm' == $_GET['checkemail'] )
						$this->errors->add('confirm', __('Check your email for the confirmation link.'), 'message');
					elseif	( isset($_GET['checkemail']) && 'newpass' == $_GET['checkemail'] )
						$this->errors->add('newpass', __('Check your email for your new password.'), 'message');
					elseif	( isset($_GET['checkemail']) && 'registered' == $_GET['checkemail'] )
						$this->errors->add('registered', __('Registration complete. Please check your email.'), 'message');
					elseif ( strpos( $redirect_to, 'about.php?updated' ) )
						$this->errors->add('updated', __( '<strong>You have successfully updated WordPress!</strong> Please log back in to see what&#8217;s new.' ), 'message' );
				}
				
				/**
				 * Filters the login page errors.
				 *
				 * @since 3.6.0
				 *
				 * @param object $errors      WP Error object.
				 * @param string $redirect_to Redirect destination URL.
				 */
				$this->errors = apply_filters( 'wp_login_errors', $this->errors, $redirect_to );
				
				$this->wp_error = $this->errors;
				
				// Clear any stale cookies.
				if ( $reauth )
					wp_clear_auth_cookie();
			
				// login_header(__('Log In'), '', $errors);
				
				if ( isset($_POST['log']) )
					$this->user_login = ( 'incorrect_password' == $this->errors->get_error_code() || 'empty_password' == $this->errors->get_error_code() ) ? esc_attr(wp_unslash($_POST['log'])) : '';
				$this->rememberme = ! empty( $_POST['rememberme'] );
			
				if ( ! empty( $this->errors->errors ) ) {
					$this->aria_describedby_error = ' aria-describedby="login_error"';
				} else {
					$this->aria_describedby_error = '';
				}
			
			// login_footer();
			break;
			
			
			} // end action switch

	    	if ( empty($this->wp_error) )
				$this->wp_error = new WP_Error();

		    // Shake it!
			$shake_error_codes = array( 'empty_password', 'empty_email', 'invalid_email', 'invalidcombo', 'empty_username', 'invalid_username', 'incorrect_password' );
			/**
			 * Filters the error codes array for shaking the login form.
			 *
			 * @since 3.0.0
			 *
			 * @param array $shake_error_codes Error codes that shake the login form.
			 */
			$shake_error_codes = apply_filters( 'shake_error_codes', $shake_error_codes );
		
			if ( $shake_error_codes && $this->wp_error->get_error_code() && in_array( $this->wp_error->get_error_code(), $shake_error_codes ) )
				add_action( 'login_head', 'wp_shake_js', 12 );
		
			$separator = is_rtl() ? ' &rsaquo; ' : ' &lsaquo; ';
			
			if ( is_multisite() ) {
				$login_header_url   = network_home_url();
				$login_header_title = get_network()->site_name;
			} else {
				$login_header_url   = __( 'https://wordpress.org/' );
				$login_header_title = __( 'Powered by WordPress' );
			}
			
			/**
			 * Filters link URL of the header logo above login form.
			 *
			 * @since 2.1.0
			 *
			 * @param string $login_header_url Login header logo URL.
			 */
			$login_header_url = apply_filters( 'login_headerurl', $login_header_url );
			
			/**
			 * Filters the title attribute of the header logo above login form.
			 *
			 * @since 2.1.0
			 *
			 * @param string $login_header_title Login header logo title attribute.
			 */
			$login_header_title = apply_filters( 'login_headertitle', $login_header_title );
			
			}
		    
	    }// end after_setup_theme()
	    
	    
	    public function wp_head(){
	    
	    	if ( empty($this->wp_error) )
				$this->wp_error = new WP_Error();
			
	    	/*
			 * Remove all stored post data on logging out.
			 * This could be added by add_action('login_head'...) like wp_shake_js(),
			 * but maybe better if it's not removable by plugins
			 */
			if ( 'loggedout' == $this->wp_error->get_error_code() ) {
				?>
				<script>if("sessionStorage" in window){try{for(var key in sessionStorage){if(key.indexOf("wp-autosave-")!=-1){sessionStorage.removeItem(key)}}}catch(e){}};</script>
				<?php
			}
			
			
			/**
			 * Enqueue scripts and styles for the login page.
			 *
			 * @since 3.1.0
			 */
			do_action( 'login_enqueue_scripts' );
		
			/**
			 * Fires in the login page header after scripts are enqueued.
			 *
			 * @since 2.1.0
			 */
			do_action( 'login_head' );
			
			$this->classes = array( 'login-action-' . $this->action, 'wp-core-ui' );
			if ( is_rtl() )
				$this->classes[] = 'rtl';
			if ( $this->interim_login ) {
				$this->classes[] = 'interim-login';
				?>
				<style type="text/css">html{background-color: transparent;}</style>
				<?php
		
				if ( 'success' ===  $this->interim_login )
					$this->classes[] = 'interim-login-success';
			}
			$this->classes[] =' locale-' . sanitize_html_class( strtolower( str_replace( '_', '-', get_locale() ) ) );			
			
			/**
			 * Filters the login page body classes.
			 *
			 * @since 3.5.0
			 *
			 * @param array  $classes An array of body classes.
			 * @param string $action  The action that brought the visitor to the login page.
			 */
			$classes = apply_filters( 'login_body_class', $classes, $action );

			add_filter( 'body_class', array( $this, 'body_classes') );
				    
	    }// end wp_head()
	    
	    public function body_classes( $body_classes ) {
				return array_merge( $body_classes, $this->classes );
			}
	    
	    public function site_url( $url, $path, $scheme, $blog_id ){
	    
	    	global $key, $user_login;
		    
		    if( 'wp-login.php' == $path ){
			   
			   $url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			   
			   $url = remove_query_arg( $this->args, $url ); 
			    
		    }else if( 'wp-login.php' == $path && 'login_post' == $scheme ){
			   
			   $url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			   
			   $url = remove_query_arg( $this->args, $url ); 
			    
		    }else if( 'wp-login.php?action=resetpass' == $path && 'login_post' == $scheme ){
			   
			   $url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			   
			   $url = remove_query_arg( $this->args, $url ); 
			   
			   $url = add_query_arg( 'action', 'resetpass', $url );
			    
		    }else if( 'wp-login.php?action=lostpassword' == $path && 'login_post' == $scheme ){
			   
			   $url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			   
			   $url = remove_query_arg( $this->args, $url ); 
			
			   $url = add_query_arg( 'action', 'lostpassword', $url );
			    
		    }else if( 'wp-login.php?registration=disabled' == $path ){
		    
		    	$url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		    	
				$url = remove_query_arg( $this->args, $url );  
		    	
		    	$url = add_query_arg( 'registration', 'disabled', $url );
		    
		    }else if( 'wp-login.php?action=register' == $path ){
		    
		    	$url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		    	
				$url = remove_query_arg( $this->args, $url );  
		    	
		    	$url = add_query_arg( 'action', 'register', $url );
		    
		    }else if( 'wp-login.php?action=lostpassword&error=invalidkey' == $path ){
		    
		    	$url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		    	
				$url = remove_query_arg( $this->args, $url );  
		    	
		    	$args = array( 'action'=> 'lostpassword', 'error' => 'invalidkey' );
		    	
		    	$url = add_query_arg( $args, $url );
		    
		    }else if( 'wp-login.php?action=lostpassword&error=expiredkey'  == $path ){
		    
		    	$url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		    	
				$url = remove_query_arg( $this->args, $url );  
		    	
		    	$args = array( 'action'=> 'lostpassword', 'error' => 'expiredkey' );
		    	
		    	$url = add_query_arg( $args, $url );
		    
		    }else if( "wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login) == $path && 'login' == $scheme ){
			   
			   $url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			   
			   $url = remove_query_arg( $this->args, $url ); 
			   
			   $args = array( 'action'=> 'rp', 'key' => $key, 'login' => rawurlencode($user_login) );
		    	
			   $url = add_query_arg( $args, $url );
			    
		    }
		    
		    return $url;
	    }
	    
	    public function login_url( $login_url, $redirect, $force_reauth ){
		    
		    $login_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			   
			$login_url = remove_query_arg( $this->args, $login_url ); 
			
			if ( !empty($redirect) )
				$login_url = add_query_arg('redirect_to', urlencode($redirect), $login_url);
			
			if ( $force_reauth )
				$login_url = add_query_arg('reauth', '1', $login_url);  
			
		    return $login_url;
		    
	    }
	    
	    public function logout_url( $logout_url, $redirect ){
		    
		    $logout_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			   
			$logout_url = remove_query_arg( $this->args, $logout_url ); 
			
			$args = array( 'action' => 'logout' );
	        if ( !empty($redirect) ) {
	                $args['redirect_to'] = urlencode( $redirect );
	        }
			
			$logout_url = add_query_arg($args, $logout_url);
				
			$logout_url = wp_nonce_url( $logout_url, 'log-out' );	
			
			return $logout_url;
		    
	    }
	    
	    public function lostpassword_url( $lostpassword_url, $redirect ){
		    
		    $lostpassword_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			   
			$lostpassword_url = remove_query_arg( $this->args, $lostpassword_url );     
			   
			$lostpassword_url = add_query_arg( 'action', 'lostpassword', $lostpassword_url );
		    
		    return $lostpassword_url;
		    
	    }
	    
	    public function logout_redirect( $redirect_to, $requested_redirect_to, $user ){
		    
		    $redirect_to = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			
			$redirect_to = remove_query_arg( $this->args, $redirect_to );     
			   
			$redirect_to = add_query_arg( 'loggedout', 'true', $redirect_to );
		    
		    return $redirect_to;
	    }
	    
	    public function register_url( $register_url ){
		    
		    $register_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			
			$register_url = remove_query_arg( $this->args, $register_url );       
			   
			$register_url = add_query_arg( 'action', 'register', $register_url );
		    
		    return $register_url;
		    
	    }
	    
	    
	    public function login_redirect( $redirect_to, $requested_redirect_to, $user ){
		    
		    $redirect_to = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			   
			$redirect_to = remove_query_arg( $this->args, $redirect_to );       
			
			return $redirect_to;
		    
	    }
	    
	    public function registration_redirect( $registration_redirect ){
		    
		    $registration_redirect = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			   
			$registration_redirect = remove_query_arg( $this->args, $registration_redirect );       
			   
			$registration_redirect = add_query_arg( 'checkemail', 'registered', $registration_redirect );
		   
			return $registration_redirect;
		    
	    }
	    
	    public function lostpassword_redirect( $lostpassword_redirect ){
		    
		    $lostpassword_redirect = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			   
			$lostpassword_redirect = remove_query_arg( $this->args, $lostpassword_redirect );       
			   
			$lostpassword_redirect = add_query_arg( 'checkemail', 'confirm', $lostpassword_redirect );
		   
			return $lostpassword_redirect;
		    
	    }
	    
	    
	    
	    
	    
	}
	 
	$wpFrontEndLogin = new WPFrontEndLogin();
	
}


// Need to add function
if( ! function_exists( 'wp_shake_js' )){
	
	/**
	 * @since 3.0.0
	 */
	function wp_shake_js() {
	?>
	<script type="text/javascript">
	addLoadEvent = function(func){if(typeof jQuery!="undefined")jQuery(document).ready(func);else if(typeof wpOnload!='function'){wpOnload=func;}else{var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}};
	function s(id,pos){g(id).left=pos+'px';}
	function g(id){return document.getElementById(id).style;}
	function shake(id,a,d){c=a.shift();s(id,c);if(a.length>0){setTimeout(function(){shake(id,a,d);},d);}else{try{g(id).position='static';wp_attempt_focus();}catch(e){}}}
	addLoadEvent(function(){ var p=new Array(15,30,15,0,-15,-30,-15,0);p=p.concat(p.concat(p));var i=document.forms[0].id;g(i).position='relative';shake(i,p,20);});
	</script>
	<?php
	}
	
	
}

if( ! function_exists( 'wp_login_viewport_meta' )){

	/**
	 * @since 3.7.0
	 */
	function wp_login_viewport_meta() {
		?>
		<meta name="viewport" content="width=device-width" />
		<?php
	}

}



if( ! function_exists( 'retrieve_password' )){
	
	/**
	 * Handles sending password retrieval email to user.
	 *
	 * @return bool|WP_Error True: when finish. WP_Error on error
	 */
	function retrieve_password() {
		
		// set variables to global to be able to fix url in email
		global $key, $user_login;
		
		$errors = new WP_Error();
	
		if ( empty( $_POST['user_login'] ) ) {
			$errors->add('empty_username', __('<strong>ERROR</strong>: Enter a username or email address.'));
		} elseif ( strpos( $_POST['user_login'], '@' ) ) {
			$user_data = get_user_by( 'email', trim( wp_unslash( $_POST['user_login'] ) ) );
			if ( empty( $user_data ) )
				$errors->add('invalid_email', __('<strong>ERROR</strong>: There is no user registered with that email address.'));
		} else {
			$login = trim($_POST['user_login']);
			$user_data = get_user_by('login', $login);
		}
	
		/**
		 * Fires before errors are returned from a password reset request.
		 *
		 * @since 2.1.0
		 * @since 4.4.0 Added the `$errors` parameter.
		 *
		 * @param WP_Error $errors A WP_Error object containing any errors generated
		 *                         by using invalid credentials.
		 */
		do_action( 'lostpassword_post', $errors );
	
		if ( $errors->get_error_code() )
			return $errors;
	
		if ( !$user_data ) {
			$errors->add('invalidcombo', __('<strong>ERROR</strong>: Invalid username or email.'));
			return $errors;
		}
	
		// Redefining user_login ensures we return the right case in the email.
		$user_login = $user_data->user_login;
		$user_email = $user_data->user_email;
		$key = get_password_reset_key( $user_data );
	
		if ( is_wp_error( $key ) ) {
			return $key;
		}
	
		$message = __('Someone has requested a password reset for the following account:') . "\r\n\r\n";
		$message .= network_home_url( '/' ) . "\r\n\r\n";
		$message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
		$message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
		$message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
		$message .= '<' . network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login') . ">\r\n";
	
		if ( is_multisite() ) {
			$blogname = get_network()->site_name;
		} else {
			/*
			 * The blogname option is escaped with esc_html on the way into the database
			 * in sanitize_option we want to reverse this for the plain text arena of emails.
			 */
			$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		}
	
		/* translators: Password reset email subject. 1: Site name */
		$title = sprintf( __('[%s] Password Reset'), $blogname );
	
		/**
		 * Filters the subject of the password reset email.
		 *
		 * @since 2.8.0
		 * @since 4.4.0 Added the `$user_login` and `$user_data` parameters.
		 *
		 * @param string  $title      Default email title.
		 * @param string  $user_login The username for the user.
		 * @param WP_User $user_data  WP_User object.
		 */
		$title = apply_filters( 'retrieve_password_title', $title, $user_login, $user_data );
	
		/**
		 * Filters the message body of the password reset mail.
		 *
		 * @since 2.8.0
		 * @since 4.1.0 Added `$user_login` and `$user_data` parameters.
		 *
		 * @param string  $message    Default mail message.
		 * @param string  $key        The activation key.
		 * @param string  $user_login The username for the user.
		 * @param WP_User $user_data  WP_User object.
		 */
		$message = apply_filters( 'retrieve_password_message', $message, $key, $user_login, $user_data );
	
		if ( $message && !wp_mail( $user_email, wp_specialchars_decode( $title ), $message ) )
			wp_die( __('The email could not be sent.') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function.') );
	
		return true;
	}


}


// add_filter( 'wp_nav_menu_items', 'wti_loginout_menu_link', 10, 2 );

function wti_loginout_menu_link( $items, $args ) {
   // if ($args->theme_location == 'primary') {
      if (is_user_logged_in()) {
         $items .= '<li class="right"><a href="'. wp_logout_url() .'">'. __("Log Out") .'</a></li>';
      } else {
         $items .= '<li class="right"><a href="'. wp_login_url(get_permalink()) .'">'. __("Log In") .'</a></li>';
      }
   // }
   return $items;
}