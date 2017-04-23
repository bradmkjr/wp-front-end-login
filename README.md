# WP Front End Login 
**Contributors:** bradmkjr  
**Donate link:** http://bradknowlton.com/  
**Tags:** wp-login.php, shortcode, login, wp-login, authentication  
**Requires at least:** 4.7.3  
**Tested up to:** 4.7.4  
**Stable tag:** 1.3.0  
**License:** GPLv2  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

This plugin utilizes the shortcode [login_form] to present users with login form, lost password recovery and password reset fields on the front end of a WordPress site.


## Description 

This plugin utilizes the shortcode [login_form] to present users with login form, lost password recovery and password reset fields on the front end of a WordPress site. The plugin heavily filters login and authentication urls to keep visitors from being sent to wp-login.php. It does not prevent a user from going directly to wp-login. The plugin blocks all non administrators from accessing the dashboard by doing a redirect to the homepage.

This plugin is designed for use with [Gravity Forms by Rocketgenius, Inc.](http://www.gravityforms.com/ "Gravity Forms"), presenting a visitor a standard login box in place of forms which are restricted to logged in users only. It can also be used with other plugins that show content for non-logged in users, such as [User Status Shortcode By Story Block Media](https://wordpress.org/plugins/user-status-shortcode/ "User Status Shortcode").

A few notes about this plugin:

*   Plugin not tested with WordPress Network
*   Password reset and password recovery are all done on the page with the shortcode


## Installation 

Installation is very basic:

1. Upload the plugin files to the `/wp-content/plugins/wp-front-end-login` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Insert [login_form] shortcode on page or post.


## Frequently Asked Questions 


### Is this plugin secure? 

All login and authentication logic is indentical to functions used in wp-login, just adjusted to work in a shortcode.


### Is this plugin compatible with other login and security plugins? 

This plugin has not been tested with other login and security plugins, such as recaptcha or simple math. Feedback is welcome for any success or failures with other login plugins.


### Are all the login styles and scripts loaded? 

This plugin only loads the admin forms css, not the complete login css collection. This seems to work well with most themes during testing. 


## Screenshots 

### 1. This screenshot shows the standard login form on the front end of a WordPress website.
[missing image]

### 2. This screenshot shows the lost password box on the front end of a WordPress site.
[missing image]



## Changelog 


### 1.3.1 
* Submitted to WordPress Plugin Archive


### 1.2.1 
* Refactored plugin to minimize use of global variables


### 1.1.3 
* First working version with login functionality.


### 1.0.0 
* Initial version, using `wp_login_form()` function


## Upgrade Notice 


### 1.2.1 
Removed global variables, replaced with private Class variables.
