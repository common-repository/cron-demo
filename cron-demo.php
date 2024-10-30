<?php
/*
Plugin Name: Cron Developers Demo
Plugin URI: http://wordpress.designpraxis.at
Description: This is a demo for WordPress plugin developers. It demonstrates the pseudo cron scheduling feature.
Version: 1.1
Author: Roland Rust
Author URI: http://wordpress.designpraxis.at
*/

/* 
Changelog 

Changes in version 1.1

Bug fix for dprx_crondemo_more_reccurences();
added german language

*/

/* This enables internationalisation for that plugin */

add_action('init', 'dprx_crondemo_init_locale');
function dprx_crondemo_init_locale() {
	$locale = get_locale();
	$mofile = dirname(__FILE__) . "/locale/".$locale.".mo";
	load_textdomain('dprx_crondemo', $mofile);
}

/* options are deleted in case of plugin deactivation */
add_action('deactivate_cron-demo/cron-demo.php', 'dprx_crondemo_deactivate');
function dprx_crondemo_deactivate() {
	delete_option("dprx_crondemo_mail");
	delete_option("dprx_crondemo_inseconds");
	delete_option("dprx_crondemo_recc");
	delete_option("dprx_crondemo_triggercount");
}

/* Admin options page display function is called */
add_action('admin_menu', 'dprx_crondemo_add_admin_pages');
function dprx_crondemo_add_admin_pages() {
	add_options_page('Cron Demo', 'Cron Demo', 10, __FILE__, 'dprx_crondemo_options_page');
}

/* Options sent by the options form are set here */
/* Schedules are activated and deactivated */
add_action('init', 'dprx_crondemo_setoptions');
function dprx_crondemo_setoptions() {
	if(!empty($_POST['dprx_crondemo_stop'])) {
		$timestamp = wp_next_scheduled('dprx_crondemo_hook', array("mail_to" => get_option("dprx_crondemo_mail")));
		/* This is where the event gets unscheduled */
		wp_unschedule_event($timestamp, "dprx_crondemo_hook", array("mail_to" => get_option("dprx_crondemo_mail")));
	}
	if(!empty($_POST['dprx_crondemo_mail'])) {
		update_option("dprx_crondemo_mail",$_POST['dprx_crondemo_mail']);
	}
	if(!empty($_POST['dprx_crondemo_inseconds']) && empty($_POST['dprx_crondemo_recc'])) {
		update_option("dprx_crondemo_inseconds",$_POST['dprx_crondemo_inseconds']);
		/* This is where the actual single event is scheduled */
		if (!wp_next_scheduled('dprx_crondemo_hook', array("mail_to" => get_option("dprx_crondemo_mail")))) {
			wp_schedule_single_event(time()+$_POST['dprx_crondemo_inseconds'], "dprx_crondemo_hook", array("mail_to" => get_option("dprx_crondemo_mail")));
		}
	}
	if(!empty($_POST['dprx_crondemo_inseconds']) && !empty($_POST['dprx_crondemo_recc'])) {
		update_option("dprx_crondemo_inseconds",$_POST['dprx_crondemo_inseconds']);
		/* This is where the actual recurring event is scheduled */
		if (!wp_next_scheduled('dprx_crondemo_hook', array("mail_to" => get_option("dprx_crondemo_mail")))) {
			wp_schedule_event(time()+$_POST['dprx_crondemo_inseconds'], "dprx_crondemo_recc", "dprx_crondemo_hook", array("mail_to" => get_option("dprx_crondemo_mail")));
		}
	}
}

/* a reccurence has to be added to the cron_schedules array */
add_filter('cron_schedules', 'dprx_crondemo_more_reccurences');
function dprx_crondemo_more_reccurences($recc) {
	$recc['dprx_crondemo_recc'] = array('interval' => get_option("dprx_crondemo_inseconds"), 'display' => 'Cron Demo Schedule');
	return $recc;
}
	
/* This is the scheduling hook for our plugin that is triggered by cron */
add_action('dprx_crondemo_hook','dprx_crondemo_trigger_schedule');
function dprx_crondemo_trigger_schedule($mail_to) {
	extract($mail_to);
	if(mail($mail_to,__('WordPress Cron Demo','dprx_crondemo'),__('Cron Demo message sent at: ','dprx_crondemo').date(get_option('date_format'))." ".date("H:i:s")."\n\n")) {
		update_option("dprx_crondemo_triggercount",get_option("dprx_crondemo_triggercount")+1);
	}
}

/* The options page display */
function dprx_crondemo_options_page() {
	?>
	<div class=wrap>
		<h2><?php _e('Cron Demo','dprx_crondemo') ?></h2>
		<p><?php _e('This WordPress plugin is just a demo for using the built in WordPress 
		pseudo-cron scheduling feature. Note that this WordPress feature depends on hits, 
		your WordPress website receives. So playing with this plugin within an offline environment 
		(MAMP, XAMPP, WAMP etc.) without anyone or anything triggering the scheduling by 
		sending requests to the WordPress page won\'t produce any results if you do not 
		trigger it by yourself.','dprx_crondemo') ?></p>
		<p><?php _e('The code of this plugin has the sole purpose of demonstrating the cron feature
		for WordPress plugin developers. It sends a scheduled triggered email containing the timestamp of the action to a specified email adress.','dprx_crondemo') ?></p>
		<div style="padding: 10px; border: 1px solid #cccccc;">
		<?php
		if (wp_next_scheduled('dprx_crondemo_hook', array("mail_to" => get_option("dprx_crondemo_mail")))) {
			?>
			<p><b><?php _e('Cron Demo is scheduled!','dprx_crondemo') ?></b></p>
			<pre><?php
			$crons = _get_cron_array();
			 foreach ( $crons as $timestamp => $cron ) {
				 if ( isset( $cron['dprx_crondemo_hook'] ) ) {
					echo __('Time now:','dprx_crondemo')." \t\t\t".date(get_option('date_format'))." ".date("H:i:s")."<br />";
					echo __('Schedule will be triggered:','dprx_crondemo')." \t".date(get_option('date_format'),$timestamp)." ".date("H:i:s",$timestamp)."<br />";
				 }
			 } 
			?><a href="<?php bloginfo('wpurl') ?>/wp-admin/options-general.php?page=cron-demo.php"><?php _e('refresh','dprx_crondemo') ?></a><br />
			</pre>
			<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
			<input type="submit" name="dprx_crondemo_stop" id="dprx_crondemo_stop" value="<?php _e('To turn off demo schedules','dprx_crondemo') ?>" />
			</form>
			<?php
			if(get_option("dprx_crondemo_triggercount") > 0) {
			?>
			<p><?php _e('Cron Demo schedule was triggered','dprx_crondemo') ?>
			<?php
			echo get_option("dprx_crondemo_triggercount");
			?> <?php _e('times.','dprx_crondemo') ?></p>
			<?php
			}
		} else {
			?>
			<p><?php _e('Cron Demo is NOT scheduled!','dprx_crondemo') ?></p>
			<?php
		}
		
		?>
		</div>
		<?php
		if (!wp_next_scheduled('dprx_crondemo_hook', array("mail_to" => get_option("dprx_crondemo_mail")))) {
		?>
		<br />
		<form style="padding: 10px; border: 1px solid #cccccc;" method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
		<p><?php _e('Send an Email testing the cron feature:','dprx_crondemo') ?></p><br />
		<?php _e('Email address','dprx_crondemo') ?> <input type="text" name="dprx_crondemo_mail" value="<?php echo get_option("dprx_crondemo_mail"); ?>" /><br />
		<?php _e('Seconds from now until this schedule should be triggered','dprx_crondemo') ?>:<br />
		<input type="text" name="dprx_crondemo_inseconds" value="<?php echo get_option("dprx_crondemo_inseconds"); ?>" /><?php _e('seconds','dprx_crondemo') ?><br />
		<?php _e('Check to set this as a reccurring event','dprx_crondemo') ?> <input type="checkbox" name="dprx_crondemo_recc" value="1" 
		<?php if (get_option("dprx_crondemo_recc") == 1) { ?>checked <?php } ?> /><br />
		<input type="submit" name="dprx_crondemo_submit" id="dprx_crondemo_submit" value="<?php _e('Set Demo Schedule','dprx_crondemo') ?>" />
		</form>
		<?php
		}
		?>
	</div>
	<!-- Thanks! -->
	<div class="wrap">
		<p>
		<?php _e("Questions? Ideas?",'dprx_crondemo'); ?>
		<a href="http://wordpress.designpraxis.at/">
		<?php _e("Drop me a line",'dprx_crondemo'); ?> &raquo;
		</a>
		</p>
		<div style="display: block; height:30px;">
			<div style="float:left; font-size: 16px; padding:5px 5px 5px 0;">
			<?php _e("Do you like this Plugin?",'dprx_crondemo'); ?>
			<?php _e("Consider to",'dprx_crondemo'); ?>
			</div>
			<div style="float:left;">
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_xclick">
			<input type="hidden" name="business" value="rol@rm-r.at">
			<input type="hidden" name="no_shipping" value="0">
			<input type="hidden" name="no_note" value="1">
			<input type="hidden" name="currency_code" value="EUR">
			<input type="hidden" name="tax" value="0">
			<input type="hidden" name="lc" value="AT">
			<input type="hidden" name="bn" value="PP-DonationsBF">
			<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but21.gif" border="0" name="submit" alt="Please donate via PayPal!">
			<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
			</form>
			</div>
		</div>
	</div>
	<?php
}
?>
