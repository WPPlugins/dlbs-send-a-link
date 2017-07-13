<?php
/*
Plugin Name: dlb's Send-A-Link
Plugin URI: http://wordpress.org/plugins/dlbs-send-a-link/
Description: dlb's Send-A-Link allows visitors to send someone an email containing a link to the post or page.
Version: 1.0
Author: Dave Bezaire
Author URI: http://davebezaire.com/
License: GPL2
*/
/*	Copyright 2013	David L Bezaire	 (email : Dave.B.Ohio@gmail.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/********************************************************
 * Globals and constants that can be customized
 */
	// Input validation: maximum number of characters in the comments
	define ( 'DSL_MAX_COMMENT_CHARS', 250);
	// Input validation: minimum number of characters in the name
	define ( 'DSL_MIN_NAME_CHARS', 1);
	// Input validation: maximum number of characters in the name
	define ( 'DSL_MAX_NAME_CHARS', 30);
	// Input validation: minimum interval between sends in seconds
	define ( 'DSL_MIN_SEND_INTERVAL', 30 );
	// The string shown in DSL_SUBMIT_BUTTON_VALUE is
	// shown on and returned by the form's submit button.
	// This is a global because it is a trigger used in an IF statement.
	// Changing it in this definition will automatically insert it into the form template
	// and in the corresponding IF statement so that changing it 
	// will not require any other alteration in the program.
	// It is also shown in the DSL_ERROR_ALERT message.
	define ( 'DSL_SUBMIT_BUTTON_VALUE', 'Send it!' ); 
	// Alert displayed to tell user that an error needs attention
	define ( 'DSL_ERROR_ALERT', 'Please correct the highlighted error(s) and ' . DSL_SUBMIT_BUTTON_VALUE . ' again' );
	// Message for an unexplained error
	define ( 'DSL_GENERAL_ERROR_MESSAGE', 'Unfortunately, a general processing error occurred. We apologize for the inconvenience and appreciate your patience. Please try again.' );
	// Message for an unexplained error while sending mail
	define ( 'DSL_SENDING_ERROR_MESSAGE', 'Unfortunately, an error occurred while attempting to send the message. We apologize for the inconvenience and appreciate your patience. Please try again.' );
	// Message for exceeding maximum characters
	define ( 'DSL_MAX_CHARS_ERROR', 'Must be no more than %d characters' );
	// Message for too few characters
	define ( 'DSL_MIN_CHARS_ERROR', 'Must be at least %d character(s)' );
	// Message for invalid email address
	define ( 'DSL_INVALID_EMAIL_ERROR', 'Must be a valid email address' );
	// Message for invalid characters in email address
	define ( 'DSL_INVALID_EMAIL_CHARS', 'May not contain invalid characters' );
	// Message for invalid characters in name
	define ( 'DSL_INVALID_NAME_CHARS', 'Must contain only: A-Z, a-z, 0-9, . , _ - " \' %' );
	// Message for invalid characters in comments
	define ( 'DSL_INVALID_COMMENTS_CHARS', 'Must not contain invalid characters (only limited HTML is allowed)' );
	// Message for invalid nonce
	define ( 'DSL_INVALID_NONCE', 'Security check failed' );
	// Message for incorrect CAPTCHA response
	define ( 'DSL_INCORRECT_CAPTCHA_ERROR', 'Must match characters in image' );
	// Message for internal CAPTCHA error
	define ( 'DSL_INTERNAL_CAPTCHA_ERROR', 'Internal captcha error' );
	// Message for violating minimum sending interval
	define ( 'DSL_MIN_SEND_INTERVAL_MESSAGE', 'Must wait at least %d seconds between sends<br />' );
	// Message for invalid show value
	define ( 'DSL_INVALID_SHOW_VALUE', 'Invalid value for show in dslLink' );
	// Default text for link
	define ( 'DSL_DEFAULT_LINK_TEXT', ' Send a link to this article' );
	// Default link icon file name
	define ( 'DSL_DEFAULT_ICON_FILE', 'email.gif' );
/********************************************************
 * Globals and constants that should not be changed
 */
	//Global data array used throughout
	$dslData = array();
	// The following four definitions use two pairs of WordPress functions
	// to produce references to the folder in which this plugin file is located, 
	// and to the folder which contains the currently active theme or child-theme files.
		// filesystem path to the plugin folder is used to load templates
		define ( 'DSL_PLUG_PATH', plugin_dir_path(__FILE__) );
		// URL to the plugin folder is used to load scripts and styles
		define ( 'DSL_PLUG_URL', plugin_dir_url(__FILE__)	);
		// filesystem path to the theme folder is used to load templates
		define ( 'DSL_THEME_PATH', get_stylesheet_directory() . '/' );
		// URL to the theme folder is used to load scripts and styles
		define ( 'DSL_THEME_URL', get_stylesheet_directory_uri() . '/' );
	// The following definitions specify the names of the customization files.
	// By default they are located relative to the plugin folder, 
	// but they can be overridden by a copy in the theme folder. 
		// filename for the captcha configuration parameters
		define ( 'DSL_CAPTCHA_CONFIG', 'dsl-captcha.php' );
		// filename for form/confirm page template
		define ( 'DSL_PAGE_TEMPLATE', 'dsl-page.html' );
		// filename for other templates
		define ( 'DSL_OTHER_TEMPLATES', 'dsl-templates.html' ); 
		// filename for CSS styles
		define ( 'DSL_CSS_FILE', 'dsl.css' ); 
	// filename for the Javascript; we only look for it in the plugin folder
	define ( 'DSL_JAVASCRIPT_FILE', 'dsl.js' ); 
	// Define our table name in the WordPress mySQL database
	// using the site's table prefix that we get from the $wpdb global.
	global $wpdb;
	define ( 'DSL_LOG_TABLE' , $wpdb->prefix . 'dslLog' ); 
	// Set plugin-database version
	define ( 'DSL_DB_VERSION', 'dbv1' );
	// Define the name used to store the db version in the database
	define ( 'DSL_NAME_OF_DB_VERSION_SETTING', 'dsl_db_version');
	// Set number of seconds to retain log records in database
	// e.g., 60 * 60 & 24 is 24 hours
	define ( 'DSL_LOG_KEEP_SECONDS', 60 * 60 * 24 );
/********************************************************
 * dslLink()
 *
 * Adds shortcode "dsl-link" and function dslLink()
 * which produce the html tags to display a
 * link to the email request form.
 *
 * The shortcode is used within the contents of a page or post.
 * The function is used in templates. 
 * 
 * The resulting link sends the ID of the post or page in which it is clicked.
 * It also sends the URL of the page (which could be either a single post or a 
 * page of many posts) for use in a back link.
 *
 * Accepts the parameters defined in the $defaults array below to show either
 * icon only, icon and text, or text only. Can be called bare (i.e. [dsl_link])
 * to use the defaults. Or, can specify the parameters explicitly, 
 * e.g., [dsl-link text="Link it!" iconfile="myIcon.gif" show="both"].
 */
add_shortcode("dsl-link", "dslLink");
function dslLink( $atts = array() ){
	// Establish parameter names and defaults.
	// These are the values that can be provided within the shortcode brackets,
	// and the default values used if they are not specified.
	$defaults = array(
						'show' => 'both',						// one of 'icon', 'text', 'both'
						'icon_file' => DSL_DEFAULT_ICON_FILE,	// filename, including path if not same place as this script
						'text' => DSL_DEFAULT_LINK_TEXT			// link text
					);
	// Merge the defaults with the inputs from the shortcode 
	// that WordPress provides in the $atts array,
	// and "extract" the result to individual variables named 
	// the same as the "keys" of the $defaults array
	extract(shortcode_atts($defaults, $atts));
	// Next we calculate the link that initiates a dsl-send-link process.
	// It is simply the URL for the top of the blog
	$link_page = get_bloginfo('siteurl');
	// To which we add the ID of the post
	$post_id = get_the_ID();
	// formatted as a URL query variable
	$query = '?dsl_pid=' . $post_id;
	// Next we add the current page URL to the query
	$query .= '&dsl_back=' . "http://" . $_SERVER['HTTP_HOST']	. $_SERVER['REQUEST_URI'];
	// Use WordPress esc_attr to be sure there are no invalid or dangerous characters in text
	$text = esc_attr($text);
	// Build the anchor tag
	// Use the WordPress esc_url functions for extra safety against sending out invalid or dangerous characters
	$anchor = '<a href="' . esc_url($link_page . $query) . '" title="' . $text . '">';
	// Build the image tag
	// Check that specified icon file exists by calling PHP function getimagesize()
	// which returns an array with the size already formatted for an html tag at index 3
	// or FALSE if file does not exist.
	// If file doesn't exist, fall back to default.
	if ( false === ( $imgsiz = getimagesize(DSL_PLUG_PATH . $icon_file) ) ) { 
		$icon_file = $defaults['icon_file']; 
		$imgsiz = getimagesize(DSL_PLUG_PATH . $icon_file);
	}
	$imgurl = DSL_PLUG_URL . $icon_file;
	// Put together the entire img tag
	// Escape the url for safety, but trust the output from getimagesize to be valid
	$img = '<img src="' . esc_url($imgurl) . '" ' . $imgsiz[3] . ' />';
	// Now we use the value of the $show parameter to determine what to output
	switch ($show) {
// special option for debug
// comment this out for production
// case 'debug':
// $output = "from my code";
// $output .= ", the post id is " . $post_id;
// $link_icon_text = $anchor . $img . $text . '</a>';
// $link_icon_only = $anchor . $img . '</a>';
// $link_text_only = $anchor . $text . '</a>';
// $output .= " Here are the links: " . $link_icon_only . " and " . $link_icon_text. " and " . $link_text_only;
// break;
		case 'both':
			$output = $anchor . $img . $text . '</a>';
			break;
		case 'text':
			$output = $anchor . $text . '</a>';
			break;
		case 'icon':
			$output = $anchor . $img . '</a>';
			break;
		default:
			$output = DSL_INVALID_SHOW_VALUE;
	}
	return $output;
}
/********************************************************
 * dslMain()
 *
 * This is the entry point for all requests to dlb's Send-A-Link.
 *
 * WordPress calls this routine every time it outputs any page or post, 
 * because of the call to add_action() with 'template_redirect'.
 * WordPress also calls this routine whenever it receives an AJAX request that
 * specifies "action" equal to "dsl-form" because of the calls to add_action() 
 * with	 'wp_ajax_nopriv' (called when a registered user is logged in) and 
 * 'wp_ajax' (called for an unregistered visitor). 
 *
 * This routine checks for existance of parameters in the URL query and POST variables
 * that indicate a dsl page request and handles the request. It quickly determines if
 * the requested page needs this routine or not, and simply returns if not needed.
 *
 * This routine displays a page that contains both the form and the confirmation
 * message, with the confirmation message initially hidden. When the AJAX request sends
 * the user input, this routine validates it sends an AJAX response which can
 * either be error message(s) or a confirmation message. If the user does not have
 * Javascript turned on, then the response is sent by the browser as a regular POST
 * instead of an AJAX, so this routine will send its response as a new page.
 *
 * When there are no errors, this routine actually sends the email and records it in a log.
 *
 * The trigger for this routine to take over is existance of query parameter 'dsl_pid'
 * which must contain the ID of a page or post, e.g., http://Bezaires.com?dsl_pid=1234
 */
add_action( 'template_redirect', 'dslMain' );
add_action( 'wp_ajax_nopriv_' . 'dsl-form', 'dslMain' );
add_action( 'wp_ajax_' . 'dsl-form', 'dslMain' );
function dslMain() {
	global $dslData;
	// load the inputs into the $dslData array
	dslGetInputs();
	// quickly return if we do not handle this type of request
	// return if we don't have a URL query parameter of 'dsl_pid'
	if( ! isset($dslData['dsl_pid']) ) return;
	// insure received value is an integer,
	$dslData['dsl_pid'] = filter_var($dslData['dsl_pid'], FILTER_SANITIZE_NUMBER_INT);
	// get the post using it as a post id number
	$dslData['post'] = get_post($dslData['dsl_pid']);
	// return if we did not find a post or a page
	if (NULL == $dslData['post'] || ('post' != $dslData['post']->post_type && 'page' != $dslData['post']->post_type) ) return;
	// Now we know that request is of the type we handle
	// Check value of back link
	// Replace it with one pointing to the post if:
	// - it was not provided at all
	// - it contains invalid URL characters
	// - it does not contain the URL of this blog
	if (	
			( ! isset( $dslData['dsl_back'] ) )
		||	filter_var( $dslData['dsl_back'], FILTER_SANITIZE_URL ) != ( $dslData['dsl_back'] )
		||	( false === strpos( $dslData['dsl_back'], home_url() ) ) 
		) {
		// Replace by setting it to a URL that points to the post/page
		$dslData['dsl_back'] = get_permalink( $dslData['post'] );
	}
	// setup array to accumulate the responses we'll send back
	$msg = array();
	// Check for security or structural errors that may impede later processing including
	// the submit button, the WordPress nonce, and the IP address.
	// If nonce fails, consider the entire request a suspect security risk.
	// If an unknown submit button value, not sure what we have, so don't trust it.
	// Not sure how we get an invalid IP address, but it's a bad thing if we do.
	// For all of these cases, send a general error message and reset the form fields.
	if (	( $dslData['dsl_submit'] != '' && $dslData['dsl_submit'] != DSL_SUBMIT_BUTTON_VALUE )
		 ||	( $dslData['dsl_submit'] != '' && ( ! wp_verify_nonce( $dslData['dsl_nonce'], 'dsl-form' ) ) )
		 ||	( false === $dslData['ip'] )
		)  {
		// flag to force values to be reset
		$msg['reset'] = true;
		// the $errors array generally contains error messsages with the array key
		// equal to the name of the form field that contains the error. to handle
		// this form-level error, we put the message into the hidden post id field,
		// which is at the top of the form, so that is where it will be displayed.
		$errors['dsl_pid'] = DSL_GENERAL_ERROR_MESSAGE;
	}
	// If no errors yet, we know we have a valid request. 
	// The driving parameters have been tested,
	// and inputs have been recorded in the $dslData array.
	// Use the submit button value to determine 
	// whether to show the form or to process the inputs.
	if (	( count($errors) == 0 ) 
		&&	( $dslData['dsl_submit'] == '')
		) {
			// there was no submit button value, 
			// so show the blank form and then exit
			dslShowPage('blank');
			exit;
	}
	// If no errors yet, we know we have received inputs from the user
	if (count($errors) == 0) {
		// Insure the proper version of the database table is installed
		dslSetupDatabase();
		// get log record for this IP address
		$dslData['log'] = dslReadLog( $dslData['ip'] );
		// check inputs for errors
		$errors = dslValidateInputs(); 
	}
	// If no errors yet, try to send the mail
	// If fails, load an error message to the $errors array
	// If succeeds, load confirm to the $msg array 
	// and record in the log
	if (count($errors) == 0) {
		dslSendMail(); 
		if ( TRUE !== $dslData['mail-result'] ) {
			// the send failed for no known reason,
			// so load a form-level error message
			$errors['dsl_pid'] = DSL_SENDING_ERROR_MESSAGE;
		} else {
			// add the confirms object to the response array
			$msg['confirms'] = array(
										'status'	=> $dslData['mail-result'],
										'body'		=> dslFormatConfirm()
									);
			// update log for IP with current time
			dslUpdateLog( $dslData['ip'], time() );
			// clear old records out of the log
			// (this should really be a CRON job or a mySQL trigger someday)
			dslFlushLog();
		}
	}
	// At this point, we either have message(s) in the $error array,
	// or we have successfully sent the email and noted it in the $msg array
	// If there are errors, put them into $msg array 
	// and turn off CAPTCHA
	if (count($errors) > 0) {
		$msg['errors'] = $errors;
		if ( (! $msg['reset']) && (! isset($errors['dsl_captcha'])) ) {
			// No reset errors and no CAPTCHA error, 
			// so we believe we are dealing with a person.
			// Create a new nonce to use as a stand-in
			// so that user doesn't have to do CAPTCHA again when correcting other errors
			// and add it to the response array
			$msg['secure'] =  wp_create_nonce( 'dsl-form-noCaptcha' );
		}
	}
	// At this point we have the $msg array containing all our responses.
	// Now we add the received values to the response array.
	// They are used when the client does not have Javascript in dslFormatFeedback().
	// This is also helpful during development and debug because they are visible in Firebug.
	$msg['values'] = $dslData;
	// decide whether user has Javascript enabled and respond accordingly 
	if ( isset($dslData['action']) && $dslData['action'] == 'dsl-form' ) {
		// we received the response via AJAX, so send it back that way
		// convert all of the responses in the array into a JSON string
		$msg = json_encode($msg);
		// send header and response string
		header( "Content-Type: application/json" ); 
		echo $msg;
	} else {
		// user doesn't have Javascript available, so resend entire page
		dslShowPage('with-feedback', $msg);
	}
	exit();
}
/********************************************************
 * dslGetInputs()
 *
 * Pulls all of the inputs received via URL query or POST into an array
 */
function dslGetInputs(){
	global $dslData;
	// Loop through all of the parameters sent via either
	// the URL (i.e., the $_GET variables like http://theBlog.com?name=value", 
	// or through the the POST mechanism which somewhat hides them, 
	// or via a cookie (which we are not using for this plugin).
	// PHP gathers all of these into one convenient $_REQUEST array.
	// Specifically, we expect to find dsl_pid and dsl_back on the URL query,
	// and all the rest in the POST variables.
	$tmp = $_REQUEST;
	foreach ($tmp as $key => $value) {
		if (substr($key, 0, 3) == 'dsl' || $key == 'action') {
			// All of our variables begin with "dsl", so we find and store them.
			// We also need the "action" that WordPress uses with AJAX
			// It seems that stripslashes() is needed because WordPress apparently does an addslashes()
			// which puts a backslash before every apostrophe. This is similar to a vestigal
			// PHP functionality called "magic_quotes_gpc", and a read of the doco about it
			// in the PHP manual gives some interesting background.
			$dslData[$key] = stripslashes($value); 
		} 
	}
	// add the user's IP address
	$dslData['ip'] = dslGetIP();
}
/********************************************************
 * dslGetIP()
 *
 * Gets ip address from any of multiple locations,
 * or returns FALSE if none can be found.
 * Verifies against invalid IP formats or characters with 
 * PHP function filter_var()
 * 
 * copied from http://www.stevekamerman.com/2006/06/storing-ip-addresses-in-mysql-with-php/
 */
function dslGetIP() {
	if (filter_var($_SERVER["HTTP_CLIENT_IP"], FILTER_VALIDATE_IP)) {
		return $_SERVER["HTTP_CLIENT_IP"];
	}
	foreach (explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]) as $ip) {
		if (filter_var(trim($ip), FILTER_VALIDATE_IP)) {
			return $ip;
		}
	}
	if (filter_var($_SERVER["HTTP_PC_REMOTE_ADDR"], FILTER_VALIDATE_IP)) {
		return $_SERVER["HTTP_PC_REMOTE_ADDR"];
	} elseif (filter_var($_SERVER["HTTP_X_FORWARDED"], FILTER_VALIDATE_IP)) {
		return $_SERVER["HTTP_X_FORWARDED"];
	} elseif (filter_var($_SERVER["HTTP_FORWARDED_FOR"], FILTER_VALIDATE_IP)) {
		return $_SERVER["HTTP_FORWARDED_FOR"];
	} elseif (filter_var($_SERVER["HTTP_FORWARDED"], FILTER_VALIDATE_IP)) {
		return $_SERVER["HTTP_FORWARDED"];
	} elseif (filter_var($_SERVER["REMOTE_ADDR"], FILTER_VALIDATE_IP)) {
		return $_SERVER["REMOTE_ADDR"];
	} else {
		return false;
	}
}
/********************************************************
 * dslValidateInputs()
 *
 *	Given the global array of field values which has indices equal to the field names,
 *	check for valid inputs.
 *	Returns an array of error messages with indices equal to the field names.
 */
function dslValidateInputs(){
	global $dslData;
		
	// Check security field (anti-bot)
	if ( isset($dslData['dsl_secure']) && $dslData['dsl_secure'] != '' ) {
		// check the second nonce that was sent after a captcha was previously satisfied
		if (false === wp_verify_nonce( $dslData['dsl_secure'], 'dsl-form-noCaptcha' )) {
			$errors[dsl_secure] = DSL_INVALID_NONCE;
		}
	} else {
		// check the CAPTCHA
		$captcha = dslMakeCaptcha();
		if ( ! $captcha->validate($dslData['dsl_captcha_random']) ) {
			$errors['dsl_captcha'] = DSL_INTERNAL_CAPTCHA_ERROR;
		} elseif ( ! $captcha->verify($dslData['dsl_captcha']) ) {
			$errors['dsl_captcha'] = DSL_INCORRECT_CAPTCHA_ERROR;
		}
	}
	
	// Check for sufficient time since last send by this IP address
	if ( (time() - $dslData['log']->time) < DSL_MIN_SEND_INTERVAL ) {
		$errors['dsl_submit'] = sprintf(DSL_MIN_SEND_INTERVAL_MESSAGE, DSL_MIN_SEND_INTERVAL );
	}
	
	// Check for valid email addresses
	dslCheckAddress('dsl_saddress', $errors); // sender's
	dslCheckAddress('dsl_raddress', $errors); // recipient's

	// Check for valid names
	dslCheckName('dsl_sname', $errors); // sender's
	dslCheckName('dsl_rname', $errors); // recipient's
	
	// Check for valid comments using rules that WordPress applies to comments
	if (strlen(trim($dslData['dsl_comments'])) > DSL_MAX_COMMENT_CHARS ) {
		$errors['dsl_comments'] = sprintf(DSL_MAX_CHARS_ERROR, DSL_MAX_COMMENT_CHARS);
	} elseif ( $dslData['dsl_comments'] != wp_kses( $dslData['dsl_comments'], wp_kses_allowed_html( 'comment' ) ) ) {
		$errors['dsl_comments'] = DSL_INVALID_COMMENTS_CHARS;
	}
	
	return $errors;
}
/********************************************************
 * dslCheckAddress()
 *
 * Given a field name and array of field values,
 * checks for a valid email address.
 * If there is an error in the value for that field,
 * puts error message into the array passed by reference.
 */
function dslCheckAddress($fieldName, &$errors){
	global $dslData;
	if ( $dslData[$fieldName] != filter_var($dslData[$fieldName], FILTER_SANITIZE_EMAIL)) {
		$errors[$fieldName] = DSL_INVALID_EMAIL_CHARS;
	} elseif ( ! filter_var($dslData[$fieldName], FILTER_VALIDATE_EMAIL)) {
		$errors[$fieldName] = DSL_INVALID_EMAIL_ERROR;
	}
}
/********************************************************
 * dslCheckName()
 *
 * Given a field name and array of field values,
 * checks for a valid name.
 * If there is an error in the value for that field,
 * puts error message into the array passed by reference.
 */
function dslCheckName($fieldName, &$errors){
	global $dslData;
	$invalidNameChars = "/[^ a-zA-Z0-9._%-',\"]/"; // invalid chars are those NOT in the list
	if (strlen(trim($dslData[$fieldName])) < DSL_MIN_NAME_CHARS) {
		$errors[$fieldName] = sprintf( DSL_MIN_CHARS_ERROR, DSL_MIN_NAME_CHARS );
	} elseif (strlen(trim($dslData[$fieldName])) > DSL_MAX_NAME_CHARS) {
		$errors[$fieldName] = sprintf( DSL_MAX_CHARS_ERROR, DSL_MAX_NAME_CHARS );
	} elseif (1 === preg_match($invalidNameChars, $dslData[$fieldName])) {
		$errors[$fieldName] = DSL_INVALID_NAME_CHARS;
	}
}
/********************************************************
 * dslShowPage()
 *
 * Shows page with form and hidden confirmation 
 * 
 * Normally shows the blank form and then AJAX/Javascript
 * handles responses and displaying feedback. 
 * But, if no Javascript on client, this routine has to
 * show the page with feedback.
 *
 * $type parameter can be either:
 * - 'blank' to show the blank form
 * - 'with-feedback' to show the form with error or confirmation 
 */
function dslShowPage($type, $msg = null){
	global $dslData;
	// First tell WordPress to run the function that adds the scripts and styles to the page
	add_action('wp_enqueue_scripts', 'dslEnq');
	// read template from file
	$input = file_get_contents( dslCustomized( 'PATH', DSL_PAGE_TEMPLATE ) );
	// perform substitutions for placeholders
	dslSubstitutePlaceholders($input);
	// if no Javascript, format the response into the template
	if ($type == 'with-feedback') { dslFormatFeedback($input, $msg); }
	// send page headers
	get_header();
	// send template
	echo $input;
	// send footers and sidebar
	get_sidebar();
	get_footer();
}
/********************************************************
 * dslFormatFeedback()
 *
 * When there is no Javascript on the client, we need to do everthing
 * here that dsl.js normally handles
 *
 * Accepts the array of responses that normally would have been sent via AJAX
 * and a reference to the blank template, and modifies the template string directly
 */
function dslFormatFeedback(&$blank, $msg){
	global $dslData;
	if ( $msg['reset'] ) {
		// perform reset, showing the form-level error
		dslFormatReset($blank, $msg['errors']['dsl_pid']);
	} elseif ( $msg['errors'] != null ) {
		// redisplay the form with error messages 
		// put error messages into label spans
		foreach ($msg['errors'] as $field => $txt) {
			$pattern = '#(<label.*?for="' . $field . '".*?>.*?<span.*?class="dsl-error-field".*?>).*?</span>#s';
			$replacement = '$1<br />' . $txt . '</span>';
			$blank = preg_replace($pattern, $replacement, $blank);
		}
		// put values into inputs
		$fieldsToDo = array('dsl_rname', 'dsl_raddress', 'dsl_sname', 'dsl_saddress');
		foreach ($fieldsToDo as $fld){
			if ( isset( $msg['values'][$fld] ) ) {
				$pattern = '#<input([^>]*?name="' . $fld . '")#s';
				$replacement = '<input value="' . htmlentities($msg['values'][$fld]) . '" $1';
				$blank = preg_replace($pattern, $replacement, $blank);
			}
		}
		// put value into textarea
		$pattern = '#(<textarea[^>]*?name="dsl_comments"[^>]*?>)(</textarea>)#';
		$replacement = '$1' . htmlentities($msg['values']['dsl_comments']) . '$2';
		$blank = preg_replace($pattern, $replacement, $blank);
		// turn off CAPTCHA is already satisfied
		if ( $msg['secure'] != null ) {
			// put value of secure into input value
			$pattern = '#(<input[^>]*?name="dsl_secure"[^>]*?value=)"("[^>]*?>)#';
			$replacement = '$1"' . $msg['secure'] . '$2';
			$blank = preg_replace($pattern, $replacement, $blank);
			// hide captcha div
			$pattern = '#(<div[^>]*?id="dsl-captcha")([^>]*?>)#';
			$replacement = '$1 style="display:none;" $2';
			$blank = preg_replace($pattern, $replacement, $blank);
		}
	} elseif ( $msg['confirms'] != null ) {
		// send the confirm page
		// use display:none to hide the form
		$pattern = '#(<div[^>]*?id="dsl-form-div")([^>]*?>)#';
		$replacement = '$1 style="display:none;" $2';
		$blank = preg_replace($pattern, $replacement, $blank);
		// use display:block to show the confirm
		$pattern = '#(<div[^>]*?id="dsl-confirm-div")([^>]*?>)#';
		$replacement = '$1 style="display:block;" $2';
		$blank = preg_replace($pattern, $replacement, $blank);
		// put the confirm text into the div
		$pattern = '#(<div[^>]*?id="dsl-confirm-div"[^>]*?>.*?<div [^>]*?>)#s';
		$replacement = '$1' . $msg['confirms']['body'];
		$blank = preg_replace($pattern, $replacement, $blank);
	} else {
		// general error reset
		dslFormatReset( $blank, DSL_GENERAL_ERROR_MESSAGE );
	}
}
/********************************************************
 * dslFormatReset()
 *
 * do reset by not filling in values 
 * and not showing errors other than
 * the general processing error
 */
function dslFormatReset(&$blank, $txt) {
	// do reset by not filling in values 
	// and not showing errors other than
	// the general processing error
	$field = 'dsl_pid';
	$pattern = '#(<label.*?for="' . $field . '".*?>.*?<span.*?class="dsl-error-field".*?>).*?</span>#s';
	$replacement = '$1<br />' . $txt . '</span>';
	$blank = preg_replace($pattern, $replacement, $blank);
}
/********************************************************
 * dslEnq()
 *
 * Enqueue scripts, styles, and Javascript globals by telling
 * WordPress what to add into the <HEAD> section of the page
 *
 * This routine is called by the action hook 'wp-enqueue-scripts'
 *
 * For further explanation see http://www.garyc40.com/2010/03/5-tips-for-using-ajax-in-wordpress/
 */
function dslEnq(){
	global $dslData;
	// Each enqueue call causes WordPress to put the associated link or script
	// tags into the <head> section of the page. The first parameter is a handle so
	// that task can be referred to later. 
	// First enqueue the base dsl styles file from the plugin folder.
	wp_enqueue_style( 'dsl-css', DSL_PLUG_URL . DSL_CSS_FILE );
	// Enqueue the style overrides from the theme folder.
	// The 3rd parameter is the handle from the previous call, and thus insures
	// that this one comes later.
	if ( file_exists(DSL_THEME_PATH . DSL_CSS_FILE) ) { 
		wp_enqueue_style( 'dsl-css-from-theme', DSL_THEME_URL . DSL_CSS_FILE, array('dsl-css') ); 
	}
	// Enqueue the Javascript file
	wp_enqueue_script( 'dsl-js', DSL_PLUG_URL . DSL_JAVASCRIPT_FILE );
	// The localize call will put a script tag into the <HEAD> section of the page
	// to create a Javascript object (ajaxData in this case) containing an array of key:value pairs.
	// Thus, in the Javascript, ajaxData becomes a global variable that we use to send some data
	// from the server.
	wp_localize_script( 'dsl-js', 'ajaxData', 
		array(
			 // Javascript sends the AJAX response to this URL
			'url' =>  admin_url( 'admin-ajax.php' ),
			// Javascript includes this 'action' to tell WordPress which routine
			// will handle the AJAX response as defined in the add_action('wp-ajax_') call
			'action' => 'dsl-form',
			// this is text for the error alert
			'errorAlert' => sanitize_text_field( DSL_ERROR_ALERT ),
			// this is text for an unexplained error
			'errorGeneral' => sanitize_text_field( DSL_GENERAL_ERROR_MESSAGE ) 
		) 
	); 
}
/********************************************************
 * dslCustomized()
 *
 * Return URL or PATH to file in theme folder or plugin folder
 *
 * Given a filename with path relative to the plugin folder,
 * returns a reference to it in the theme folder if the file exists there, 
 * or to it in the plugin folder if it exists there, or
 * FALSE if it exists in neither place. 
 *
 * $type can be either URL or PATH
 */
function dslCustomized($type, $file){
	// check in theme folder
	$tmp = DSL_THEME_PATH . $file;
	if ( file_exists($tmp) ) {
		// found it, so return URL or PATH to it
		$tmp = ($type == 'PATH') ?  $tmp : DSL_THEME_URL . $file;
	} else {
		// check in plugin folder
		$tmp = DSL_PLUG_PATH . $file;
		if ( file_exists($tmp) ) {
			// found it, so return URL or PATH
			$tmp = ($type == 'PATH') ?  $tmp : DSL_PLUG_URL . $file;
		} else {
			// not found, so return false
			$tmp = FALSE;
		}
	}
	return $tmp;
}
/********************************************************
 * dslMakeCaptcha()
 *
 * Returns a Captchas.Net object
 *
 * This routine reads in all of the CAPTCHA customization parameters
 * from a configuration file.
 * The $id and $key must be replaced with values received by registering
 * at http://captchas.net.
 *
 * Uses the web service at http://captchas.net
 * See Captcha comparisons at http://davebezaire.com/tst/captcha/captcha.html
 */
function dslMakeCaptcha(){
	// load the configuration file, hopefully from the theme folder, 
	// with fallback to that in the plugin folder
	include ( dslCustomized( 'PATH', DSL_CAPTCHA_CONFIG ) );
	// load the library that was copied from http://captchas.net
	require_once(DSL_PLUG_PATH .'CaptchasDotNet.php');
	// create and return the object
	return new CaptchasDotNet ($id, $key, $store, $time, $alphabet, $count, $width, $height, $color);
}
/********************************************************
 * dslSendMail()
 *
 * Sends email message
 *
 * Reads the email message template from an external file,
 * formats the various parts of the email message, and
 * puts them in the global array. The body of the message is
 * also included separately so that it can be
 * displayed on the confirmation page. Then sends the email.
 *
 * Result of the mail send call is also put in the global array
 */
function dslSendMail(){
	global $dslData;
	// read templates from file
	$input = file_get_contents(dslCustomized( 'PATH', DSL_OTHER_TEMPLATES ));
	// get the message section from the templates
	$input = dslGetSection( $input, 'message');
	// perform substitutions for placeholders
	// this puts in the names, receiver's address, sender's comments, post title, etc.
	dslSubstitutePlaceholders(&$input);
	// if no comments, remove comments paragraphs 
	if (trim($dslData['dsl_comments']) == '') {
		$input = preg_replace("#<p\s*?class=\"comment\".*?</p>#s", "", $input);
	}
	// extract the body for display on confirmation page
	preg_match("#<body>(.*)</body>#s", $input, $tmp);
	$msgBody = $tmp[1];
	// extract title for use as subject
	preg_match("#<title>(.*)</title>#", $input, $tmp);
	$msgSubj = $tmp[1];
	// subject is plain text, so convert emphasis tags to quotes, and remove htmlentities()
	// and sanitize as text
	$msgSubj = str_replace('<em>', '"', str_replace('</em>', '"', $msgSubj));
	$msgSubj = html_entity_decode($msgSubj);
	$msgSubj = sanitize_text_field($msgSubj);
	// To send HTML mail, the Content-type headers must be set
	// Note that the charset is also mentioned in the <HEAD> in the template
	$hdrs  = 'MIME-Version: 1.0' . "\r\n";
	$hdrs .= 'Content-type: text/html; charset=utf-8' . "\r\n";
	// add senders name and address in the headers
	$hdrs .= 'From:' . sanitize_text_field($dslData['dsl_sname']) . ' <' . $dslData['dsl_saddress'] . '>' . "\r\n";
	// Store the results in the $dslData array
	$dslData['mail-to'] = $dslData['dsl_raddress'];
	$dslData['mail-subject'] = $msgSubj;
	$dslData['mail-message'] = $input;
	$dslData['mail-message-body'] = $msgBody;
	$dslData['mail-headers'] = $hdrs;
	// send the email
	$dslData['mail-result'] = mail(	$dslData['mail-to'], 
									$dslData['mail-subject'], 
									$dslData['mail-message'], 
									$dslData['mail-headers']);
}
/********************************************************
 * dslFormatConfirm()
 *
 * Returns the html for the confirmation page
 */
function dslFormatConfirm(){
	global $dslData;
	// read template from file
	$input = file_get_contents(dslCustomized( 'PATH', DSL_OTHER_TEMPLATES ));
	// get the confirm section
	$out .= dslGetSection( $input, 'confirm');
	// perform the substitutions
	dslSubstitutePlaceholders($out);
	return $out;
}
/********************************************************
 * dslGetSection()
 *
 * Returns the contents of a section from the template file
 */
function dslGetSection($str, $tag) {
	// regex pattern finds what is between tags constructed as <tag></tag>
	// the leading and trailing #'s are required delimiters
	// the trailing 's' after the last # says that dot should match newlines
	// the \s* followed by \S consumes white space, including newlines, 
	// between the end and start of the tag brackets and included content
	$pattern = '#<' . $tag . '>\s*(\S.*\S)\s*</' . $tag . '>#s';
	preg_match($pattern, $str, $match); // stores the first capture group in $match[1]
	return $match[1];
}
/********************************************************
 * dslSubstitutePlaceholders()
 *
 * Substitutes current values for the placeholders in 
 * the templates.
 *
 * Accepts a reference to a template string,
 * and performs the substitutions directly on it.
 */
function dslSubstitutePlaceholders(&$template) {
	global $dslData;
	// get post author and date
	$author = get_userdata($dslData['post']->post_author);
	$author = $author->display_name;
	$date = date('l, F j', strtotime($dslData['post']->post_date));
	// get an instance of the CAPTCHA object
	$captcha = dslMakeCaptcha();
	// Substitute current values for the placeholders
	$template = str_replace("#dsl-post-title#",			htmlentities($dslData['post']->post_title),		$template);
	$template = str_replace("#dsl-recipient-name#",		sanitize_text_field($dslData['dsl_rname']),		$template);
	$template = str_replace("#dsl-recipient-address#",	$dslData['dsl_raddress'],						$template);
	$template = str_replace("#dsl-sender-name#",		sanitize_text_field($dslData['dsl_sname']),		$template);
	$template = str_replace("#dsl-post-id#",			$dslData['dsl_pid'],							$template);
	$template = str_replace("#dsl-post-author#",		$author,										$template);
	$template = str_replace("#dsl-post-date#",			$date,											$template);
	$template = str_replace("#dsl-back-link#",			esc_url($dslData['dsl_back']),					$template);
	$template = str_replace("#dsl-comments#",			nl2br($dslData['dsl_comments']),				$template);
	$template = str_replace("#dsl-message-subject#",	sanitize_text_field($dslData['mail-subject']),	$template);
	$template = str_replace("#dsl-message-body#",		$dslData['mail-message-body'],					$template);
	$template = str_replace("#dsl-submit-button#",		DSL_SUBMIT_BUTTON_VALUE,						$template);
	$template = str_replace("#wp-nonce#",				wp_create_nonce('dsl-form'),					$template);
	$template = str_replace("#captcha-random#",			$captcha->random (),							$template);
	$template = str_replace("#captcha-image#",			$captcha->image(),								$template);
	$template = str_replace("#captcha-audio#",			$captcha->audio_url (),							$template);
	$template = str_replace("#max-comment-chars#",		DSL_MAX_COMMENT_CHARS,							$template);
	$template = str_replace("#min-name-chars#",			DSL_MIN_NAME_CHARS,								$template);
	$template = str_replace("#max-name-chars#",			DSL_MAX_NAME_CHARS,								$template);
}
/********************************************************
 * dslSetupDatabase()
 *
 * Do one-time, initial setup tasks.
 *
 * WordPress calls this routine when the user presses the "Activate" link
 * in the Administrator's Plugins menu due to the register_activation_hook() call.
 * Since there is no hookable event when an upgraded version is installed,
 * we also call this routine on every run of the script after 
 * we know we will be handling the page request.
 *
 * This routine sets up a database table to log 
 * the IP address and time of each send.
 */
register_activation_hook( __FILE__, 'dslSetupDatabase' );
function dslSetupDatabase() {
	global $wpdb;
	// check if the currently installed database version 
	// matches what will be installed by this routine,
	// and if so, simply return
	if ( get_option(DSL_NAME_OF_DB_VERSION_SETTING) == DSL_DB_VERSION ) return;
	// Define the command that will tell WordPress to create our table.
	// Note that this command is not strict SQL because the WordPress routines process it first.
	// This table will be used to remember sends for just a very short time, a day at the most.
	// It will store the user's IP address and the time of each send as follows:
	// - idnum is a unique key for each record added to the table
	// - ip is the address stored as a long integer by using PHP function ip2long()
	// - time is the time of the last send stored as a UNIX time stamp which is in seconds
	// - comment is for testing and learning about mySQL and myPhpAdmin
	// idnum is used as the unique, primary index
	// ip is indexed because it will be the search term on every query
	// time is indexed because it will be the sort term on every query
	$sqlCmd = "CREATE TABLE " . DSL_LOG_TABLE . " (
											idnum int NOT NULL AUTO_INCREMENT,
											ip int,
											time int,
											comment text,
											UNIQUE KEY  idnum (idnum),
											KEY ip (ip),
											KEY time (time)
											);";
	// Load the WordPress upgrade module which knows how to deal with structuring tables.
	// In particular, it handles a "create" query by analysing what is already in place
	// and changing it as necessary.
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	// the WordPress dbDelta function executes our command
	$e = dbDelta ( $sqlCmd );
	// Put a setting in the WordPress options table with the Plugin's database version number
	add_option( DSL_NAME_OF_DB_VERSION_SETTING, $wpdb->escape(DSL_DB_VERSION) );
}
/********************************************************
 * dslDeActivate()
 *
 * Deletes the plugin's database table and option.
 *
 * WordPress calls this routine when the user presses the "Deactivate" link
 * in the Administrator's Plugins menu. 
 */
register_deactivation_hook( __FILE__, 'dslDeActivate' );
function dslDeActivate() {
	global $wpdb;
	// delete options created by plugin
	delete_option( DSL_NAME_OF_DB_VERSION_SETTING );
	// define command that will tell MySQL to delete the table created by plugin
	$sqlCmd = 'DROP TABLE IF EXISTS ' . DSL_LOG_TABLE .';';
	// send command thru the WordPress query object
	$e = $wpdb->query( $sqlCmd );
}
/********************************************************
 * dslUpdateLog
 *
 * Adds a log record for IP address with time of last send
 * and optional comment
 * See http://stackoverflow.com/questions/2754340/inet-aton-and-inet-ntoa-in-php
 * for info about storing an IP address in MySQL.
 */
function dslUpdateLog($ip, $time, $comment = '') {
	global $wpdb;
	// setup data to be stored
	$fields = array(
					'ip'		=> ip2long( $ip ), // IP address converted to long integer
					'time'		=> $time, // current time as UNIX timestamp
					'comment' 	=> $wpdb->escape( $comment ) // optional comment
					);
	// Tell WordPress to insert the record
	$wpdb->insert( DSL_LOG_TABLE, $fields );
}
/********************************************************
 * dslReadLog($ip)
 *
 * Returns a log object with:
 *  - the most recent log record for the given IP address, or 
 *  - if no existing record for that IP, a dummy record with time equal to zero.
 */
function dslReadLog($ip) {
	global $wpdb;
	// Define sql query to get all records for this IP and sort them
	// by time descending
	$sqlCmd = 	' SELECT * FROM ' . DSL_LOG_TABLE . 
				' WHERE ip = ' . ip2long($ip) .
				' ORDER BY time DESC'
				;
	// Tell WordPress to execute the query and return the top row
	$logRow = $wpdb->get_row( $sqlCmd );
	// If row was found, convert IP back from integer to string
	// If not found, create a dummy row with time of zero
	if ( $logRow != null ) {
		$logRow->ip = long2ip($logRow->ip);
		return $logRow;
	} else {
		return (object)array('ip' => $ip, 'time' => 0, 'comment' => 'dummy');
	}
}
/********************************************************
 * dslFlushLog($ip)
 *
 * Deletes old records from the log
 *
 * Old are those earlier than the current time
 * less the DSL_LOG_KEEP_SECONDS. 
 */
function dslFlushLog() {
	global $wpdb;
	// calculate the earliest time to be kept
	$deleteTime = time() - DSL_LOG_KEEP_SECONDS;
	// Define sql command to find earlier records and delete them
	$sqlCmd = 	' DELETE FROM ' . DSL_LOG_TABLE . 
				' WHERE time < ' . $deleteTime
				;
	// Tell WordPress to execute the query
	$wpdb->query( $sqlCmd );
}
?>