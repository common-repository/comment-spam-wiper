<?php
function csw_register_menu() {
	add_menu_page('Comment SPAM Wiper', 'CSW', 'administrator', 'comment-spam-wiper/admin.php', 'csw_main_page', plugins_url('comment-spam-wiper/icon.png'));
	add_submenu_page('comment-spam-wiper/admin.php','','','administrator','comment-spam-wiper/admin.php','csw_main_page');
	add_submenu_page('comment-spam-wiper/admin.php', 'Comment SPAM Wiper Dashboard', 'Dashboard', 'administrator', 'csw_main_page', 'csw_main_page');
	add_submenu_page('comment-spam-wiper/admin.php', 'Comment SPAM Wiper Statistics', 'Statistics', 'administrator', 'csw_stats_page', 'csw_stats_page');
	add_submenu_page('comment-spam-wiper/admin.php', 'Comment SPAM Wiper Settings', 'Settings', 'administrator', 'csw_settings_page', 'csw_settings_page');
	
	add_action( 'admin_init', 'register_mysettings' );
}


function register_mysettings() {
	register_setting( 'csw-settings-group', 'csw_api_key');
}

function csw_main_page() {
	include_once(ABSPATH . WPINC . '/feed.php');
	$spams = csw_spam_count();
?>
<div class="wrap">
<h2><img src="<?php echo plugins_url('comment-spam-wiper/icon.png'); ?>" /> Comment SPAM Wiper</h2>
<br /><strong>Total SPAM comments blocked by CSW: <?php echo $spams; ?></strong>.

</div>
<?php
	$feed = array('http://www.spamwipe.com/feed.xml');
	$rss = fetch_feed($feed);
	if (!is_wp_error($rss)) { 
	    $maxitems = $rss->get_item_quantity($amount); 
	    $rss_items = $rss->get_items(0, $maxitems); 
	}
	echo '<h3>CSW News</h3><hr style="height:1px;border-width:0;background-color:#ccc"><ul>';
	if(is_array($rss_items) AND $rss_items) {	
		if ($maxitems == 0) {
			echo '<li class="text-wrap">No items</li>';
		} else {
			foreach ($rss_items as $item) {
		        echo '<li class="text-wrap">- <a href='.$item->get_permalink().' title="'.$item->get_title().'">'.$item->get_title().'</a> on '.$item->get_date('j F Y \a\t g:i a').'<br />'.$item->get_content().'</li>';
			}
		}
	} else {
		echo '<li class="text-wrap"><span class="rsserror">The feed appears to be invalid or corrupt!</span></li>';
	}
	echo '</ul>';
}

function csw_spam_count( $type = false ) {
	global $wpdb;

	if ( !$type ) {
		$count = wp_cache_get( 'csw_spam_count', 'widget' );
		if ( false === $count ) {
			if ( function_exists('wp_count_comments') ) {
				$count = wp_count_comments();
				$count = $count->spam;
			} else {
				$count = (int) $wpdb->get_var("SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_approved = 'spam'");
			}
			wp_cache_set( 'csw_spam_count', $count, 'widget', 3600 );
		}
		return $count;
	} elseif ( 'comments' == $type || 'comment' == $type ) {
		$type = '';
	} else {
		$type  = $wpdb->escape( $type );
	}

	return (int) $wpdb->get_var("SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_approved = 'spam' AND comment_type='$type'");
}

function csw_settings_page() {
	global $csw;
	if (!current_user_can('manage_options'))  {
	    wp_die( __('You do not have sufficient permissions to access this page.') );
  	}
  	$mainurl = "options.php";
?>
<div class="wrap">
<h2><img src="<?php echo plugins_url('comment-spam-wiper/icon.png'); ?>" /> Comment SPAM Wiper Settings</h2>

<form method="post" action="<?php echo $mainurl; ?>">
    <?php settings_fields( 'csw-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">API Key:</th>
        <td width="150px;"><input type="text" name="csw_api_key" value="<?php echo get_option('csw_api_key'); ?>" /></td>
        <td><?php echo csw_verify_key(csw_get_key()); ?></td>
        </tr>
    </table>
    
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div>
<?php
}

function csw_stats_page() {
	global $csw;
	if (!current_user_can('manage_options'))  {
	    wp_die( __('You do not have sufficient permissions to access this page.') );
  	}
  	$sip = file_get_contents('http://www.spamwipe.com/exip.php');	// $sip = $_SERVER["SERVER_ADDR"];
?>
<div class="wrap">
<h2><img src="<?php echo plugins_url('comment-spam-wiper/icon.png'); ?>" /> Comment SPAM Wiper Statistics</h2>
<iframe src="http://api.spamwipe.com/stats/?sip=<?php echo $sip; ?>&apikey=<?php echo csw_get_key(); ?>" style="width:100%;height:600px;"></iframe>
</div>
<?php
}

function csw_activate() {
	add_option('csw_api_key', '');
}

function csw_deactivate() {
	delete_option('csw_api_key');
}

register_activation_hook( __FILE__, 'csw_activate' );
register_deactivation_hook( __FILE__, 'csw_deactivate' );

add_action('admin_menu', 'csw_register_menu');

function csw_dashboard_page() {
	$amount = 5;
	include_once(ABSPATH . WPINC . '/feed.php');
	$spams = csw_spam_count();
?>
<strong>Total SPAM comments blocked by CSW: <?php echo $spams; ?></strong>.
<?php
	$feed = array('http://www.spamwipe.com/feed.xml');
	$rss = fetch_feed($feed);
	if (!is_wp_error($rss)) { 
	    $maxitems = $rss->get_item_quantity($amount); 
	    $rss_items = $rss->get_items(0, $maxitems); 
	}
	if(is_array($rss_items) AND $rss_items) {
		echo '<br /><br /><h4><strong>CSW News</strong></h4><hr style="height:1px;border-width:0;background-color:#ccc"><ul>';
		if ($maxitems == 0) {
			echo '<li class="text-wrap">No items</li>';
		} else {
			foreach ($rss_items as $item) {
		        echo '<li class="text-wrap">- <a href='.$item->get_permalink().' title="'.$item->get_title().'">'.$item->get_title().'</a> on '.$item->get_date('j F Y \a\t g:i a').'</li>';
			}
		}
		echo '</ul>';
	}
}

function csw_add_dashboard_widgets() {
	wp_add_dashboard_widget('csw_dashboard_widget', 'Comment SPAM Wiper Statistics', 'csw_dashboard_page');
} 

add_action('wp_dashboard_setup', 'csw_add_dashboard_widgets' );

function csw_recheck_queue() {
	global $wpdb, $csw;

	if ( ! ( isset( $_GET['recheckqueue'] ) || ( isset( $_REQUEST['action'] ) && 'csw_recheck_queue' == $_REQUEST['action'] ) ) )
		return;
		
	$moderation = $wpdb->get_results( "SELECT * FROM $wpdb->comments WHERE comment_approved = '0'", ARRAY_A );
	foreach ( (array) $moderation as $commentdata ) {

		$param['ip'] = $commentdata['comment_author_IP'];
		$param['name'] = $commentdata['comment_author'];
		$param['email'] = $commentdata['comment_author_email'];
		$param['comment'] = $commentdata['comment_content'];
		$param['type'] = $commentdata['comment_type'];
		
		$param['site_ip'] = gethostbyname($_SERVER['HTTP_HOST']);
		$param['site_lang'] = substr(get_locale(),0,2);
		
		$param['client_url'] = $commentdata['comment_author_url'];
		$param['client_ua'] = $commentdata['comment_agent'];
		$param['client_proxy'] = ($csw->proxy_detect())?'y':'n';
		$param['client_lang'] = $csw->get_client_language();
		$param['recheck'] = 'true';
		
		$csw->api_key(csw_get_key());
		$response = $csw->classify($param);
		$response = trim($response);
		if ( $response == 'true' ) {
			wp_set_comment_status($commentdata['comment_ID'], 'spam');
		}
	}
	wp_safe_redirect( $_SERVER['HTTP_REFERER'] );
	exit;
}
add_action('admin_action_csw_recheck_queue', 'csw_recheck_queue');

function csw_admin_notice() {
	$var = csw_verify_key(csw_get_key());
	switch ($var) {
		case 'not paid':
			echo '<div class="updated">
	       		<p><strong>CSW:</strong> Your current plan is not paid. Please contact us <a target="_blank" href="http://www.spamwipe.com/contact.html">here</a>.</p>
	    		</div>';
			break;
		case 'plan exceeded':
			echo '<div class="updated">
	       		<p><strong>CSW:</strong> Your API calls exceeded your current billing plan. For more API calls per day update your plan. Please check our billing plans <a target="_blank" href="http://www.spamwipe.com/pricing.html">here</a>.</p>
	    		</div>';
			break;
		case 'invalid':
			echo '<div class="updated">
	       		<p><strong>CSW:</strong> API Key is not valid. Please enter a <strong>valid</strong> API Key <a href="admin.php?page=csw_settings_page">here</a> otherwise the CSW plugin will not work.</p>
	    		</div>';
	    	break;
	    case 'valid':
			break;
	}
}
add_action('admin_notices', 'csw_admin_notice');

function csw_check_for_spam_button($comment_status) {
	if ( 'approved' == $comment_status )
		return;
	if ( function_exists('plugins_url') )
		$link = 'admin.php?action=csw_recheck_queue';
	else
		$link = 'edit-comments.php?page=comment-spam-wiper/admin.php&amp;recheckqueue=true&amp;noheader=true';
	echo "</div><div class='alignleft'><a class='button-secondary checkforspam' href='$link'>" . __('Check for Spam') . "</a>";
}

add_action('manage_comments_nav', 'csw_check_for_spam_button');

?>