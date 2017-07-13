<?php
/**************************************
 * Captchas.net Configuration
 *
 * These are the settings that control the 
 * CAPTCHA on the form. For security purposes,
 * it is essential that you register (free) at
 * http://captchas.net to obtain your own
 * user name and secret key.
 *
 * Place a copy of this file into your theme folder.
 * Edit that copy and insert your user name and
 * secret key below.
 */
	// Replace "demo" with the user name that you
	// specified during registration at captchas.net
	$id = 'demo'; 
	// Replace "secret" with the secret key that you
	// received in an email from captchas.net
	$key = 'secret'; 
	// storage location for strings??? not really sure how this works
	$store = '/tmp/captchasnet-random-strings'; 
	// maximum time (seconds) that a captcha can be valid
	$time = '1800'; 
	// characters to use in the image
	$alphabet = 'abcdghkmnpqrstvwyz345'; 
	// number of characters in the image
	$count = '4'; 
	// size in pixels
	$height = '80'; 
	$width = '140';
	// color as an RGB hex value
	$color = 'A0590A';
?>