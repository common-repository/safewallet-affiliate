<?php
/*
Plugin Name: SafeWallet Affiliate
Description: Adds affiliate link from Wordpress Forgot Your Password page that invites users to download SafeWallet to help manage their passwords. The plugin contains your unique affiliate ID, so each user who registers SafeWallet directed from your site is identified and listed, even if the purchase is made at a later date.
Version: 1.00.03
Author: SafeWallet
Author URI: http://www.sbsh.net
*/

$NO_RESULT = -1; // Const No-result
$aff_result_code = $NO_RESULT;
$aff_id = "";

if ( isset($_POST['action']) && !empty($_POST['action']) )
{
	// If user pressed the option to setup an affiliate
	if ($_POST['action'] == "setup_affiliate")
	{
		// Use CURL to call SafeWallet server to create new affiliate ID or obtain existing affiliate ID for this account
		$curl = curl_init();

		# CURL SETTINGS.
		curl_setopt($curl, CURLOPT_URL, "http://www.sbsh.net/db_mng/services/aff_obtain_id.php?aff_email=".base64_encode($_POST['aff_email']));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);

		# GRAB THE ID RESULT
		$aff_result = curl_exec($curl);
		
		curl_close($curl);

		# Extract the result code
		if (strlen($aff_result) > 0)
		{
			switch ($aff_result[0])
			{
				// New affiliate ID created for this account email address - store affiliate email & ID
				case "1":
					$aff_result_code = 1;
					$aff_id = substr($aff_result, (1 - strlen($aff_result)));
					update_option("sw_aff_email", base64_encode($_POST['aff_email']));
					update_option("sw_aff_id", $aff_id);					
					break;
				// Existing affiliate ID available for this account - store affiliate email & ID
				case "2":
					$aff_result_code = 2;
					$aff_id = substr($aff_result, (1 - strlen($aff_result)));
					update_option("sw_aff_email", base64_encode($_POST['aff_email']));
					update_option("sw_aff_id", $aff_id);
					break;
				// If "0" or anything other than the available options indicates an error
				default:
					$aff_result_code = 0;
					break;
			}
		}
	}
	else if ($_POST['action'] == "customize_message")
	{
		// If we need to store the new customize top margin
		if (isset($_POST['customize_top_margin']))
		{
			update_option("sw_customize_top_margin", $_POST['customize_top_margin']);
		}
	}
}


function safewallet_box()
{ 
	// Get the affiliate ID from WP settings
	$aff_id = get_option("sw_aff_id");
	echo '<div style="line-height:18px; position:relative; background-color:#455667; top:'.get_box_top_margin_html_text().'px; margin:0px; padding:0px; margin:0 -17px 0 -25px;"><div style="position:absolute; margin:0px; padding:0px;"><p class="message"><b>Tired of recovering passwords?</b><br /><br style="line-height:6px;"/><a href="http://www.sbsh.net/password-manager-safewallet/?aff='.$aff_id.'" target="_blank">Try SafeWallet, the world\'s leading password manager</a>, and never forget a password again!</p></div></div>';

	// Try doing initial affiliate registration if didn't perform yet
	do_initial_affiliate_registration();

	return true;
}

add_action('lostpassword_form', 'safewallet_box', 100, 0);
add_action('admin_menu', 'safewallet_affiliate_plugin_menu');

function safewallet_affiliate_plugin_menu()
{
	add_options_page( 'SafeWallet Affiliate', 'SafeWallet Affiliate', 'manage_options', 'safewallet-affiliate-plugin', 'safewallet_affiliate_plugin_options' );
}

function safewallet_affiliate_plugin_options()
{
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	echo '<div class="wrap" style="padding:10px 0 0 10px; text-align:left">';
	echo '<h2>SafeWallet Affiliate Plug-In</h2><br>';
	echo 'Start earning money in the least expected page in your WordPress blog! Join our ubercool affiliate program!<br><br>';
	echo '<a href="http://www.sbsh.net/password-manager-safewallet/">SafeWallet</a> is one of the world\'s leading password managers. This plug-in adds a simple textual link to the bottom of your WordPress "Forgot Your Password" page, inviting users to use SafeWallet so they won\'t have to recover their login again and again in the future. Install the plugin to join our affiliate program and turn the least expected page on your blog to additional revenue source.<br><br>';
	echo '<b>The details</b><br>For each user that registers SafeWallet through our site we\'ll give you a 40% affiliate commission from the order total - with no other fees. We pay on the start of each month via PayPal (email us to discuss other payment options), with a threshold of $100 (payment is accumulated on a monthly basis until trashold reached).<br><br>';
	echo '<a href="'.get_option('siteurl').'/wp-content/plugins/wp-safewallet/screenshot-1.png"><b>Screenshot</b></a><br><br>';
	echo '<b>Initial setup</b><br>Enter your email below and press the "Request affiliate ID" button to setup your affiliate account for the first time.<br><br>';
	echo '<form method="post" action="">';
	echo '<input type="hidden" name="action" value="setup_affiliate">';
	echo '<table>';
	echo '<tr><td align="right">Your email:</td><td style="padding-left:10px;"><input type="text" name="aff_email" style="width:200px;" value="'.get_affiliate_email_html_text().'"></td></tr>';
	echo '<tr style="height:10px;"><td colspan="2"></td></tr>';
	echo '<tr><td align="right">Your affiliate ID:</td><td style="padding-left:12px;"><b>'.get_affiliate_id_html_text().'</b></td></tr>';
	echo '</table><br>';
	echo '<table border="0"><tr><td><input type="submit" name="submit" value="Request affiliate ID" class="button-primary tadv_btn"></td><td style="padding-left:10px; font-size:11px;">'.get_affiliate_result_code_html_text().'</td></tr></table><br><br>';
	echo '</form>';
	echo '<span style="font-size:10px;">Please verify the email address you entered is correct. We will contact you through this email to setup payment for the first time. For any questions and support, please feel free to contact us: affiliate[at]sbsh.net.<br>';
	echo 'If you already have an affiliate account, enter your existing affiliate account email address and press Request affiliate ID to obtain your existing affiliate ID. You can also use the same affiliate account on multiple blogs that you own.</span><br><br>';

	echo '<b>Customize link</b><br>';

	echo '<form method="post" action="">';
	echo '<input type="hidden" name="action" value="customize_message">';
	echo '<table>';
	echo '<tr><td align="right">Link top margin (pixels):</td><td style="padding-left:10px;"><input type="text" name="customize_top_margin" style="width:60px;" value="'.get_box_top_margin_html_text().'"></td></tr>';
	echo '<tr style="height:10px;"><td colspan="2"></td></tr>';
	echo '</table>';
	echo '<table border="0"><tr><td><input type="submit" name="submit" value="Save" class="button-primary tadv_btn"></td><td style="padding-left:10px; font-size:11px;"></td></tr></table><br><br>';
	echo '</form>';

	echo '</div>';

	// Perform first time new affiliate registration with our system
	do_initial_affiliate_registration();
}

// -------------------------------------------------------------------------------------
//
//	This helper function receivs the server side affiliate result code and returns the
//	html text to display for this specific return code.
//
// -------------------------------------------------------------------------------------
function get_affiliate_result_code_html_text()
{
	global $aff_result_code;

	switch ($aff_result_code)
	{
		case 0:
			return "<b><font color=red>Error occurred while creating affiliate ID. Please contact us at affiliate@sbsh.net for further support.</font></b>";
		case 1:
			return "<font color=green><b>Affiliate ID for this email already exists and was setup successfully.</b> If you believe the existing affiliate ID is a mistake please contact us at affiliate@sbsh.net.</font>";
		case 2:
			return "<b><font color=green>New affiliate ID generated successfully.</font></b>";
	}

	return "";
}

// -------------------------------------------------------------------------------------
//
//	This helper function returns the current affiliate ID html text to be displayed.
//
// -------------------------------------------------------------------------------------
function get_affiliate_id_html_text()
{
	// If affiliate email exists in the options return it
	$aff_id = get_option("sw_aff_id");
	if (isset($aff_id) && (strlen($aff_id) > 0))
	{
		return $aff_id;
	}

	// If no affiliate value exists return an empty string
	return "Press 'Request affiliate ID' button for first setup.";
}

function get_affiliate_email_html_text()
{
	// If affiliate email exists in the options return it
	$aff_email = base64_decode(get_option("sw_aff_email"));
	if (isset($aff_email) && (strlen($aff_email) > 0))
	{
		return $aff_email;
	}

	// If no affiliate value exists return an empty string
	return "";
}

// -------------------------------------------------------------------------------------
//
//	Used to receive the affiliate link box top margin
//
// -------------------------------------------------------------------------------------
function get_box_top_margin_html_text()
{
	// If top margin exists in the options return it
	$top_margin = get_option("sw_customize_top_margin");
	if (isset($top_margin) && (strlen($top_margin) > 0) && is_numeric($top_margin))
	{
		return $top_margin;
	}

	// If no affiliate value exists return an empty string
	return "100";
}

// -------------------------------------------------------------------------------------
//
//	Performs first time registration of the affiliate site with our system - for
//	reaching out to our partners.
//
// -------------------------------------------------------------------------------------
function do_initial_affiliate_registration()
{
	$initial_registration_performed = get_option("sw_initial_registration_performed");

	// Perform initial affiliate site registration if it wasn't performed for this site yet
	if (!isset($initial_registration_performed) || (strlen($initial_registration_performed) == 0))
	{
		// Use CURL to call SafeWallet server to create new affiliate ID or obtain existing affiliate ID for this account
		$curl = curl_init();

		# CURL SETTINGS.
		curl_setopt($curl, CURLOPT_URL, "http://www.sbsh.net/db_mng/services/aff_initial_registration.php?u=".base64_encode(get_option('siteurl'))."&platform=wp");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);

		# GRAB THE ID RESULT
		$curl_result = curl_exec($curl);

		curl_close($curl);

		// Store flag of initial registration performed
		update_option('sw_initial_registration_performed', 'yes');
	}
}

?>