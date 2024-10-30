<?php

/*
Plugin Name: Comment SPAM Wiper
Plugin URI: http://wordpress.org/extend/plugins/comment-spam-wiper/
Description: <strong>Comment SPAM Wiper</strong> protects your blog from comment and trackback spam.
Version: 1.2.1
Author: Intermod Group
Author URI: http://www.intermod.ro
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

require_once  dirname( __FILE__ ) . '/csw_sdk.php';
if ( is_admin() )
	require_once dirname( __FILE__ ) . '/admin.php'; 
	
$csw = new CSW_SDK;

function csw_get_key() {
	return get_option('csw_api_key');
}

function csw_verify_key( $key ) {
	global $csw;
	
	$key = csw_get_key();
	$blog = get_option('home');
	$response = $csw->verify_key($key, $blog);
	
	return $response;
}

function csw_classify_comment( $commentdata ) {
	global $csw;

	$param['ip'] = $_SERVER['REMOTE_ADDR'];
	$param['name'] = $commentdata['comment_author'];
	$param['email'] = $commentdata['comment_author_email'];
	$param['comment'] = $commentdata['comment_content'];
	$param['type'] = $commentdata['comment_type'];
	
	$param['site_ip'] = gethostbyname($_SERVER['HTTP_HOST']);
	$param['site_lang'] = substr(get_locale(),0,2);
	
	$param['client_url'] = $commentdata['comment_author_url'];
	$param['client_referer'] = $_SERVER['HTTP_REFERER'];
	$param['client_ua'] = $_SERVER['HTTP_USER_AGENT'];
	$param['client_proxy'] = ($csw->proxy_detect())?'y':'n';
	$param['client_lang'] = $csw->get_client_language();
	$param['type'] = $commentdata['comment_type'];
	
	$csw->api_key(csw_get_key());
	$response = $csw->classify($param);
	
	if ( $response == 'true' ) {
		add_filter('pre_comment_approved', 'csw_result_spam');
		
		do_action( 'csw_spam_caught' );
		
		if ( $post->post_type == 'post' && empty($commentdata['user_ID']) ) {
			if ( $incr = apply_filters('csw_spam_count_incr', 1) )
				update_option( 'csw_spam_count', get_option('csw_spam_count') + $incr );
			wp_safe_redirect( $_SERVER['HTTP_REFERER'] );
			die();
		}
	}
	
	if ( $response != 'true' && $response != 'false' ) {
		if ( !wp_get_current_user() ) {
			add_filter('pre_comment_approved', 'csw_result_hold');
		}
	}
	
	if ( function_exists('wp_next_scheduled') && function_exists('wp_schedule_event') ) {
		if ( !wp_next_scheduled('csw_scheduled_delete') )
			wp_schedule_event(time(), 'daily', 'csw_scheduled_delete');
	} elseif ( (mt_rand(1, 10) == 3) ) {
		csw_delete_old();
	}
	
	return $commentdata;
}

function csw_delete_old() {
	global $wpdb;
	$now_gmt = current_time('mysql', 1);
	$comment_ids = $wpdb->get_col("SELECT comment_id FROM $wpdb->comments WHERE DATE_SUB('$now_gmt', INTERVAL 15 DAY) > comment_date_gmt AND comment_approved = 'spam'");
	if ( empty( $comment_ids ) )
		return;
		
	$comma_comment_ids = implode( ', ', array_map('intval', $comment_ids) );

	do_action( 'delete_comment', $comment_ids );
	$wpdb->query("DELETE FROM $wpdb->comments WHERE comment_id IN ( $comma_comment_ids )");
	clean_comment_cache( $comment_ids );
	$n = mt_rand(1, 5000);
	if ( apply_filters('csw_optimize_table', ($n == 11)) )
		$wpdb->query("OPTIMIZE TABLE $wpdb->comments");

}

add_action('csw_scheduled_delete', 'csw_delete_old'); 

function csw_result_spam( $approved ) {
	if ( $incr = apply_filters('csw_spam_count_incr', 1) )
		update_option( 'csw_spam_count', get_option('csw_spam_count') + $incr );
	remove_filter( 'pre_comment_approved', 'csw_result_spam' );
	return 'spam';
}

function csw_result_hold( $approved ) {
	remove_filter( 'pre_comment_approved', 'csw_result_hold' );
	return '0';
}

function csw_get_user_roles($user_id ) {
	$roles = false;
	
	if ( !class_exists('WP_User') )
		return false;
	
	if ( $user_id > 0 ) {
		$comment_user = new WP_User($user_id);
		if ( isset($comment_user->roles) )
			$roles = join(',', $comment_user->roles);
	}

	if ( is_multisite() && is_super_admin( $user_id ) ) {
		if ( empty( $roles ) ) {
			$roles = 'super_admin';
		} else {
			$comment_user->roles[] = 'super_admin';
			$roles = join( ',', $comment_user->roles );
		}
	}

	return $roles;
}

function csw_markas_spam_comment ( $comment_id ) {
	global $wpdb, $csw, $current_user, $current_site;
	$comment_id = (int) $comment_id;

	$commentdata = $wpdb->get_row("SELECT * FROM $wpdb->comments WHERE comment_ID = '$comment_id'");
	if ( !$commentdata )
		return;
	if ( 'spam' != $commentdata->comment_approved )
		return;
	
	$param['ip'] = $commentdata->comment_author_IP;
	$param['name'] = $commentdata->comment_author;
	$param['email'] = $commentdata->comment_author_email;
	$param['comment'] = $commentdata->comment_content;
	$param['type'] = $commentdata->comment_type;
	
	$param['site_ip'] = gethostbyname($_SERVER['HTTP_HOST']);
	$param['site_lang'] = substr(get_locale(),0,2);
	
	$param['client_url'] = $commentdata->comment_author_url;
	$param['client_referer'] = $_SERVER['HTTP_REFERER'];
	$param['client_ua'] = $commentdata->comment_agent;
	$param['client_proxy'] = ($csw->proxy_detect())?'y':'n';
	$param['client_lang'] = $csw->get_client_language();

	$csw->api_key(csw_get_key());
	$response = $csw->markas_spam($param);
	
	return $commentdata;
}

function csw_markas_ham_comment ( $comment_id ) {
	global $wpdb, $csw, $current_user, $current_site;
	$comment_id = (int) $comment_id;

	$commentdata = $wpdb->get_row("SELECT * FROM $wpdb->comments WHERE comment_ID = '$comment_id'");
	if ( !$commentdata )
		return;
	
	$param['ip'] = $commentdata->comment_author_IP;
	$param['name'] = $commentdata->comment_author;
	$param['email'] = $commentdata->comment_author_email;
	$param['comment'] = $commentdata->comment_content;
	$param['type'] = $commentdata->comment_type;
	
	$param['site_ip'] = gethostbyname($_SERVER['HTTP_HOST']);
	$param['site_lang'] = substr(get_locale(),0,2);
	
	$param['client_url'] = $commentdata->comment_author_url;
	$param['client_referer'] = $_SERVER['HTTP_REFERER'];
	$param['client_ua'] = $commentdata->comment_agent;
	$param['client_proxy'] = ($csw->proxy_detect())?'y':'n';
	$param['client_lang'] = $csw->get_client_language();
	
	$csw->api_key(csw_get_key());
	$response = $csw->markas_ham($param);
	
	return $commentdata;
}

add_action('preprocess_comment', 'csw_classify_comment', 1);

function csw_transition_comment_status( $new_status, $old_status, $comment ) {
	if ( $new_status == $old_status )
		return;

	if ( $new_status == 'delete' )
		return;
		
	if ( !is_admin() )
		return;
		
	if ( !current_user_can( 'edit_post', $comment->comment_post_ID ) && !current_user_can( 'moderate_comments' ) )
		return;

	if ( defined('WP_IMPORTING') && WP_IMPORTING == true )
		return;
		
	global $current_user;
	$reporter = '';
	if ( is_object( $current_user ) )
		$reporter = $current_user->user_login;
	
	if ( isset($_POST['action']) || isset($_GET['action']) ) {
		if ( $new_status == 'spam' && ( $old_status == 'approved' || $old_status == 'unapproved' || !$old_status ) ) {
				return csw_markas_spam_comment( $comment->comment_ID );
		} elseif ( $old_status == 'spam' && ( $new_status == 'approved' || $new_status == 'unapproved' ) ) {
				return csw_markas_ham_comment( $comment->comment_ID );
		}
	}
}

add_action( 'transition_comment_status', 'csw_transition_comment_status', 10, 3 );

function copyrightf() {
	echo '<small>This site is protected by <a target="_blank" href="http://www.spamwipe.com/">Comment SPAM Wiper</a>. </small>';
}

add_action('wp_footer', 'copyrightf');

function csw_approve($approved) {
	global $user_ID, $_POST;

	if ( !$user_ID ) {
		if($_POST['no_session'] == 'hidden') {
			return $approved;
		} else {
			wp_die("We don't allow comment spam here.");
			return false;
		}
	} else {
		return $approved;
	}
}
add_action('pre_comment_approved', 'csw_approve');

function csw_comment_form()
{
    global $user_ID;

    // If the user is logged in, dont prompt for code

    if (isset($user_ID) && $user_ID >0) {
        return $post_ID;
    }
?>

<input type="hidden" name="no_session" id="no_session" size="6" tabindex="4" />
<script type="text/javascript">
	document.getElementById("no_session").value = 'hidden';
</script>

<?php

    return $post_ID;
}
add_action('comment_form', 'csw_comment_form');

function csw_comment_post($post_ID)
{
    global $user_ID, $_POST, $comment_type;

	$entry_id= $_POST['comment_post_ID'];
	$no_session = $_POST['no_session'];
			
    // If the user is not logged in check the security code
    if ( !$user_ID  && ($comment_type === '')) {
        // puke on an empty code
		if ($no_session != 'hidden') {
			wp_die('Error: Invalid comment form.');
			exit;
		}
    }
    return $post_ID;
}
add_action('comment_post', 'csw_comment_post');

?>