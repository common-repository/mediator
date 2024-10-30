<?php
/*
Plugin Name: Mediator
Plugin URI:  http://mediator.tech/
Description: Detects adblock visitors and converts them to MailChimp subscribers, Facebook likes. Replace ad with custom html if AdBlock is enabled. Block content to adblockers and notify them with custom or default antiadblock alert. Full statistics and analytics. 
Version:     1.0
Author:      Mediator
Author URI:  http://mediator.tech
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: mediator_tech
Domain Path: /languages
*/

/*
error_reporting(E_ALL);
ini_set('display_errors', 1);
*/

register_activation_hook( __FILE__, 'mediator_tech_activation' );

function mediator_tech_activation() {
	
	$options = get_option('mediator_tech_settings');
	
	/* if options are empty, save the default ones */
	
	if (empty($options)) {
		
		$options['enabled'] = 'true';
		$options['adsense_code'] = '';
		$options['alt_content'] = 'custom_html';
		$options['mailchimp_key'] = '';
		$options['mailchimp_list'] = '';
		$options['facebook_layout'] = 'button';
		$options['custom_html'] = '';
		$options['blur_continue'] = '';
		$options['blur_image_type'] = 'default';
		$options['blur_count'] = 3;
		
		update_option('mediator_tech_settings', $options, true);
		
	}
	
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'mediator_tech_stats';
	
	$charset_collate = $wpdb->get_charset_collate();
	
	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		date date DEFAULT '0000-00-00' NOT NULL,
		hash tinytext NOT NULL,
		facebook tinytext DEFAULT '' NOT NULL,
		mailchimp tinytext DEFAULT '' NOT NULL,
		unblocked tinytext DEFAULT '' NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
	dbDelta( $sql );
	
}

require_once('MailChimp.php');

use \DrewM\MailChimp\MailChimp;

add_action('plugins_loaded', 'mediator_tech_init');

function mediator_tech_init() {
	
	$plugin_dir = basename(dirname(__FILE__));
	
	load_plugin_textdomain('mediator_tech', false, $plugin_dir . '/languages');
	
}


	
add_action('admin_menu', 'mediator_tech_settings_menu');

function mediator_tech_settings_menu() {
	
	add_menu_page(
		'Mediator1',
		'Mediator',
		'manage_options',
		'mediator-tech',
		false
	);
	
	add_submenu_page(
		'mediator-tech',
		'Mediator Stats',
		__('Statistics', 'mediator_tech'),
		'manage_options',
		'mediator-tech',
		'mediator_tech_stats'
	);
	
	add_submenu_page(
		'mediator-tech',
		'Mediator Settings',
		__('Settings', 'mediator_tech'),
		'manage_options',
		'mediator-tech-settings',
		'mediator_tech_settings'
	);
	
}

add_action('init', 'mediator_tech_process_settings');

function mediator_tech_process_settings() {
	
	if (isset($_POST['mediator_tech_settings_saved']) && $_POST['mediator_tech_settings_saved'] == 'true') {
	
		check_admin_referer('mediator-tech-save-settings');
		
		$new_option_values = $_POST['mediator_tech_settings'];
		
		if (isset($_FILES) && empty($_FILES['mediator_tech_settings_blur_image_file']) == false) {
			
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			
			$attachment_id = media_handle_upload('mediator_tech_settings_blur_image_file', 0);
			
			if (is_int($attachment_id)) {
				
				$new_option_values['blur_image_file'] = $attachment_id;
				
			}
			
		}
		
		update_option('mediator_tech_settings', $new_option_values, 'yes');
	
	}
	
}

function mediator_tech_stats() {
	
	$options = get_option('mediator_tech_settings');
	
	global $wpdb;
	
	$month = isset($_POST['mediator_tech_stats']['month']) ? $_POST['mediator_tech_stats']['month'] : date('m');
	
	$year = isset($_POST['mediator_tech_stats']['year']) ? $_POST['mediator_tech_stats']['year'] : date('Y');
	
	$days_in_month = cal_days_in_month(CAL_GREGORIAN, intval($month), intval($year));
	
	$start_date = $year . '-' . $month . '-01';
	
	$end_date = $year . '-' . $month . '-' . $days_in_month;
	
	//var_dump(memory_get_usage());
	
	$connection = mysqli_connect(str_replace(':', '', DB_HOST), DB_USER, DB_PASSWORD, DB_NAME);
	
	$result = mysqli_query($connection, 'SELECT * FROM ' . $wpdb->prefix . 'mediator_tech_stats' . ' WHERE date BETWEEN "' . $start_date . '" AND "' . $end_date . '"');
	
	//var_dump(mysqli_num_rows($result));
	
	//var_dump(memory_get_usage()); 
	
	//$results = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'mediator_tech_stats' . ' WHERE date BETWEEN "' . $start_date . '" AND "' . $end_date . '"');
	
	$all_adblockers = array();
	
	$fb_adblockers = array();
	
	$mc_adblockers = array();
	
	$unblockers = array();
	
	$days_list = array();
	
	for ($i = 1; $i <= $days_in_month; $i++) {
		
		$all_adblockers[$i] = 0;
		
		$fb_adblockers[$i] = 0;
		
		$mc_adblockers[$i] = 0;
		
		$unblockers[$i] = 0;
		
		array_push($days_list, $i);
		
	}
	
	while ($result_single = mysqli_fetch_assoc($result)) {
		
		$day = intval(substr($result_single['date'], -2));
		
		if ($result_single['mailchimp'] == '1') {
			
			$azaza = $mc_adblockers[$day];
			
			$azaza++;
			
			$mc_adblockers[$day] = $azaza;	
			
		}
		
		if ($result_single['facebook'] == '1') {
			
			$azaza = $fb_adblockers[$day];
			
			$azaza++;
			
			$fb_adblockers[$day] = $azaza;	
			
		}
		
		if ($result_single['unblocked'] == '1') {
			
			$azaza = $unblockers[$day];
			
			$azaza++;
			
			$unblockers[$day] = $azaza;	
			
		}
		
		$azaza = $all_adblockers[$day];
			
		$azaza++;
			
		$all_adblockers[$day] = $azaza;
		
		unset($day);
		
		unset($azaza);
		
		unset($result_single);
        
    }
    
    //var_dump(memory_get_usage()); 

	$all_list = array();
	
	foreach ($all_adblockers as $all_adblockers_single) {
		
		array_push($all_list, $all_adblockers_single);
		
	}
	
	$fb_list = array();
	
	foreach ($fb_adblockers as $fb_adblockers_single) {
		
		array_push($fb_list, $fb_adblockers_single);
		
	}
	
	$mc_list = array();
	
	foreach ($mc_adblockers as $mc_adblockers_single) {
		
		array_push($mc_list, $mc_adblockers_single);
		
	}
	
	$unblockers_list = array();
	
	foreach ($unblockers as $unblockers_single) {
		
		array_push($unblockers_list, $unblockers_single);
		
	}

	$total_adblockers = array_sum($all_list);

	$total_fb = array_sum($fb_list);

	$total_mc = array_sum($mc_list);
	
	$total_unblockers = array_sum($unblockers_list);
	
	if ($total_adblockers < 1) {
		
		$conversion_fb = 0;
		
		$conversion_mc = 0;
		
		$conversion_unblock = 0;
		
	} else {
		
		$conversion_fb = round((($total_fb / $total_adblockers) * 100), 2);
	
		$conversion_mc = round((($total_mc / $total_adblockers) * 100), 2);	
		
		$conversion_unblock = round((($total_unblockers / $total_adblockers) * 100), 2);
		
	}
	
	mysqli_close($connection);
	
	//var_dump(memory_get_usage()); 
	
	?>
	
	<style>
		
		.vd_adv_stats_select {
			text-align: center;
			width: 700px;
		}
		
		.vd_adv_stats_select form {
			display: inline-block;
			margin: 6px;
		}
		
	</style>
	
	<script>
		
		jQuery(document).ready(function(){
			
			var days_list = JSON.parse('<?php echo json_encode($days_list); ?>');
			
			var all_list = JSON.parse('<?php echo json_encode($all_list); ?>');
			
			var fb_list = JSON.parse('<?php echo json_encode($fb_list); ?>');
			
			var mc_list = JSON.parse('<?php echo json_encode($mc_list); ?>');
			
			var unblockers_list = JSON.parse('<?php echo json_encode($unblockers_list); ?>');;
			
			var ctx = document.getElementById("mediator_tech_chart");
			
			var myChart = new Chart(ctx, {
			    type: 'bar',
			    data: {
			        labels: days_list,
			        datasets: [{
			            label: '<?php _e('All visitor with adblock', 'mediator_tech'); ?>',
			            data: all_list,
			            backgroundColor: 'rgba(153, 102, 255, 0.2)',
			            borderColor: 'rgba(75, 192, 192, 1)',
			            borderWidth: 1
			        },
			        {
			            label: '<?php _e('Facebook likes', 'mediator_tech'); ?>',
			            data: fb_list,
			            backgroundColor: 'rgba(54, 162, 235, 0.2)',
			            borderColor: 'rgba(54, 162, 235, 1)',
			            borderWidth: 1
			        },
			        {
			            label: '<?php _e('MailChimp subscriptions', 'mediator_tech'); ?>',
			            data: mc_list,
			            backgroundColor: 'rgba(255, 99, 132, 0.2)',
			            borderColor: 'rgba(255,99,132,1)',
			            borderWidth: 1
			        },
			        {
			            label: '<?php _e("Users that have disabled adblock", 'mediator_tech'); ?>',
			            data: unblockers_list,
			            backgroundColor: 'rgba(155, 99, 132, 0.2)',
			            borderColor: 'rgba(155,99,132,1)',
			            borderWidth: 1
			        }]
			    },
			    options: {
			        scales: {
			            yAxes: [{
			                ticks: {
			                    beginAtZero:true
			                }
			            }]
			        },
					responsive: false
			    }
			});	
			
		});
	
	</script>
	
	<div class="vd_adv_stats">
		
		<h1><?php _e('Mediator Statistics', 'mediator_tech'); ?></h1>
		
		<div class="vd_adv_stats_select">
		<form method="post" class="">
			
			<label><?php _e('Month', 'mediator_tech'); ?></label>
			<select name="mediator_tech_stats[month]">
				<option value="01" <?php selected('01', $month, true); ?>><?php _e('January', 'mediator_tech'); ?></option>
				<option value="02" <?php selected('02', $month, true); ?>><?php _e('February', 'mediator_tech'); ?></option>
				<option value="03" <?php selected('03', $month, true); ?>><?php _e('March', 'mediator_tech'); ?></option>
				<option value="04" <?php selected('04', $month, true); ?>><?php _e('April', 'mediator_tech'); ?></option>
				<option value="05" <?php selected('05', $month, true); ?>><?php _e('May', 'mediator_tech'); ?></option>
				<option value="06" <?php selected('06', $month, true); ?>><?php _e('June', 'mediator_tech'); ?></option>
				<option value="07" <?php selected('07', $month, true); ?>><?php _e('July', 'mediator_tech'); ?></option>
				<option value="08" <?php selected('08', $month, true); ?>><?php _e('August', 'mediator_tech'); ?></option>
				<option value="09" <?php selected('09', $month, true); ?>><?php _e('September', 'mediator_tech'); ?></option>
				<option value="10" <?php selected('10', $month, true); ?>><?php _e('October', 'mediator_tech'); ?></option>
				<option value="11" <?php selected('11', $month, true); ?>><?php _e('November', 'mediator_tech'); ?></option>
				<option value="12" <?php selected('12', $month, true); ?>><?php _e('December', 'mediator_tech'); ?></option>
			</select>
			
			<label><?php _e('Year', 'mediator_tech'); ?></label>
			<select name="mediator_tech_stats[year]">
				<option value="2015" <?php selected('2015', $year, true); ?>>2015</option>
				<option value="2016" <?php selected('2016', $year, true); ?>>2016</option>
				<option value="2017" <?php selected('2017', $year, true); ?>>2017</option>
				<option value="2017" <?php selected('2018', $year, true); ?>>2018</option>
			</select>
			
			<input type="submit" value="<?php _e('Select', 'mediator_tech'); ?>">
			
		</form>
		</div>

		<div class="vd_adv_stats_conversion">

			<p><b>
			<?php

			if ($month == date('m') && $year == date('Y') && date('d') < $days_in_month) {

				_e('Conversion rate this month (so far):', 'mediator_tech');

			} else {

				_e('Conversion rate this month:', 'mediator_tech');

			}

			?>
			</b></p>

			<ul>
				<li><?php _e('Facebook likes', 'mediator_tech'); ?>: <?php echo $conversion_fb; ?>%</li>
				<li><?php _e('MailChimp subscriptions', 'mediator_tech'); ?>: <?php echo $conversion_mc; ?>%</li>
				<li><?php _e('Users that have disabled adblock', 'mediator_tech'); ?>: <?php echo $conversion_unblock; ?>%</li>
			</ul>

		</div>

		<canvas id="mediator_tech_chart" height="250" width="700"></canvas>
		
	</div>	
	
	<?php
	
}

function mediator_tech_settings() {
	
	$options = get_option('mediator_tech_settings');
	
	?>
	
	<style>
		
		.vd_adv_settings_text {
			padding: 10px 0px;
			width: 100%;
			max-width: 700px;
			float: left;
		}
		
		.vd_adv_settings_form {
			width: 100%;
			max-width: 700px;
			float: left;
		}
		
		.vd_adv_settings_block {
			width: 100%;
			float: left;
			padding: 10px 0px;
		}
		
		.vd_adv_settings_block.alt_content_type {
			display: none;
		}
		
		.vd_adv_settings_form textarea {
			width: 100%;
			min-height: 100px;
		}
		
		.vd_adv_settings_fb_layout {
			width: 100%;
			float: left;
			height: 55px;
			line-height: 55px;
			margin: 2px 0px;
		}
		
		.vd_adv_settings_fb_layout img {
			float: left;
			margin-right: 10px;
		}
		
		.vd_adv_settings_mailchimp_api_key_status {
			display: inline-block;
			width: 25px;
			height: 25px;
			vertical-align: bottom;
			border-radius: 50%;
		}
		
		.vd_adv_settings_mailchimp_api_key_status.true {
			background-color: green;
		}
		
		.vd_adv_settings_mailchimp_api_key_status.false {
			background-color: red;
		}
		
		#mediator_tech_set_mc_key {
			display: none;
		}

		.vd_adv_settings_file {
			float: left;
			width: 100%;
			height: 50px;
			line-height: 50px;
		}

		.file_thumbnail {
			width: 50px;
			height: 50px;
			float: left;
			margin-right: 20px;
		}

		.file_thumbnail img {
			max-width: 100%;
			height: auto;
		}

		.file_name {
			padding: 6px 16px;
			background-color: gray;
			color: white;
			border-radius: 25px;
		}

		.file_delete {
			cursor: pointer;
			margin-left: 20px;
			display: inline-block;
			vertical-align: -9px;
			width: 27px;
			height: 27px;
			border-radius: 100%;
			background-image: url('<?php echo plugin_dir_url( __FILE__ ) . '/img/icon-delete.svg'; ?>');
			background-position: center;
			background-repeat: no-repeat;
			background-size: cover;
		}
		
	</style>
	
	<h1><?php _e('Mediator Settings', 'mediator_tech'); ?></h1>
	
	<div class="vd_adv_settings_text">
		<?php _e("In this settings section you can control the way how Mediator Plugin works on your website. Firstly, you need to set your AdSense code and, secondly, selecting the type of alternative content. The alternative content will be displayed if the visitor has active adblock software in the browser. After that you can add this AdSense to your website using widget or shortcode. If you already use other plugin to add AdSense, please use shortcode [mediator_tech] in there. For advanced users: if you want to use plugin in php template, use echo do_shortcode ('[mediator_tech]');", 'mediator_tech'); ?> 
	</div>
	
	<form class="vd_adv_settings_form" method="post" enctype="multipart/form-data">
		
		<input type="hidden" name="mediator_tech_settings_saved" value="true" />
		
		<div class="vd_adv_settings_block">
			<label><?php _e('AdSense Code', 'mediator_tech'); ?></label>
			<textarea name="mediator_tech_settings[adsense_code]" placeholder="<?php _e('Your AdSense advertising code', 'mediator_tech'); ?>"><?php echo stripslashes($options['adsense_code']); ?></textarea>
		</div>
		
		<div class="vd_adv_settings_block">
			<label><?php _e('Type of alternative content', 'mediator_tech'); ?></label>
			<select name="mediator_tech_settings[alt_content]" >
				<option value="custom_html" <?php selected($options['alt_content'], 'custom_html', true); ?>><?php _e('Custom HTML content', 'mediator_tech'); ?></option>
				<option value="mailchimp_form" <?php selected($options['alt_content'], 'mailchimp_form', true); ?>><?php _e('MailChimp subscribtion form', 'mediator_tech'); ?></option>
				<option value="facebook_like" <?php selected($options['alt_content'], 'facebook_like', true); ?>><?php _e('Facebook like button', 'mediator_tech'); ?></option>
				<option value="blur_content" <?php selected($options['alt_content'], 'blur_content', true); ?>><?php _e('Blur content', 'mediator_tech'); ?></option>
			</select>
		</div>
		
		<div class="vd_adv_settings_block alt_content_type mailchimp_form" <?php echo $options['alt_content'] == 'mailchimp_form' ? 'style="display: block;"' : ''; ?>>
				<p><?php _e('To find your MailChimp API key, look here for simple instructions', 'mediator_tech'); ?>: <a href="http://kb.mailchimp.com/integrations/api-integrations/about-api-keys#Find-or-Generate-Your-API-Key" target="_blank">http://kb.mailchimp.com/integrations/api-integrations/about-api-keys#Find-or-Generate-Your-API-Key</a></p>
				<?php
					
				$api_key = $options['mailchimp_key'];
				
				$list_id = $options['mailchimp_list'];
				
				$api = '';
				
				/* initialize the new MailChimp API */
				
				try {
					
					$api = new MailChimp($api_key);
					
					$api_status = true;
					
				} catch (Exception $e) {
					
					if (strpos($e->getMessage(), 'Invalid MailChimp API key') !== false) {
						
						$api_status = false;
						
					}
					
				}
					
				?>
			
			<p>
			<label><?php _e('MailChimp API key', 'mediator_tech'); ?></label>
			<input type="text" name="mediator_tech_settings[mailchimp_key]" value="<?php echo $options['mailchimp_key'] ?>" <?php if (empty($options['mailchimp_key']) == false) { echo 'readonly'; } ?>/>
			<span class="vd_adv_settings_mailchimp_api_key_status <?php echo $api_status ? 'true' : 'false' ; ?>"></span>
			<button id="mediator_tech_change_mc_key" <?php echo empty($options['mailchimp_key']) ? 'style="display: none"' : ''; ?>><?php _e('Change API key', 'mediator_tech'); ?></button>
			<button id="mediator_tech_set_mc_key" <?php echo empty($options['mailchimp_key']) ? 'style="display: inline-block"' : ''; ?>><?php _e('Connect to MailChimp', 'mediator_tech'); ?></button>
			</p>
			
			<p>			
			<?php
			
			if ($api_status == true) {
				
				echo '<p>';
				
				echo '<label>';
				
				_e('MailChimp List ID', 'mediator_tech');
				
				echo '</label>';
				
				$lists_request = $api->get('lists');
				
				echo '<select name="mediator_tech_settings[mailchimp_list]">';
				
				if (count($lists_request['lists']) > 0) {
					
					foreach ($lists_request['lists'] as $single_list) {
						
						echo '<option value="' . $single_list['id'] . '" ' . selected($single_list['id'], $list_id, false) . '  >' . $single_list['name'] . '</option>';
						
					}
					
				} else {
					
					echo '<option value="none">' . __('No lists available in MailChimp', 'mediator_tech') . '</option>';
					
				}
				
				echo '</select>';
				
				echo '</p>';
				
			} else {
				
				echo '<input type="hidden" name="mediator_tech_settings[mailchimp_list]" value="none" />';
				
			}
			
			?>
			</p>
			
		</div>
		
		<script>
			
			jQuery(document).on('click', '#mediator_tech_change_mc_key', function(e) {
				
				e.preventDefault();
				
				jQuery('#mediator_tech_set_mc_key').show();

				jQuery('#mediator_tech_change_mc_key').hide();
				
				jQuery('.vd_adv_settings_block.mailchimp_form input').attr('readonly', false);
				
			});
			
			jQuery(document).on('click', '#mediator_tech_set_mc_key', function(e) {
				
				e.preventDefault();
				
				jQuery('.vd_adv_settings_block.mailchimp_form input').attr('readonly', true);
				
				jQuery.ajax({
					url: '<?php bloginfo('url'); ?>/wp-admin/admin-ajax.php',
		            type: 'post',
		            dataType: 'JSON',
		            data: {
			            action: 'vd_adv_settings_mailchimp_update',
			            api_key: jQuery('.vd_adv_settings_block.mailchimp_form input').val()
			        },
		            success: function(response) {

						console.log(response.status);
			            
			            if (response.status == true) {
				            
				            jQuery.ajax({
					            url: window.location.href,
					            success: function(response) {
						            
									jQuery('.vd_adv_settings_block.mailchimp_form').replaceWith(jQuery('.vd_adv_settings_block.mailchimp_form', response));

									jQuery('.vd_adv_settings_block.mailchimp_form').show();
									
									jQuery('#mediator_tech_set_mc_key').hide();
						            
					            }
				            });
				            
			            }
			            
		            }
				});
				
			});
			
			jQuery(document).on('change', 'select[name="mediator_tech_settings[alt_content]"]', function(e) {
				
				var alt_content = jQuery(this).val();
				
				jQuery('.vd_adv_settings_block.alt_content_type').hide();
				
				jQuery('.vd_adv_settings_block.alt_content_type.' + alt_content).show();
				
			});

			jQuery(document).on('click', '.file_delete', function(e) {
				
				jQuery.ajax({
					url: '<?php bloginfo('url'); ?>/wp-admin/admin-ajax.php',
		            type: 'post',
		            dataType: 'JSON',
		            data: {
			            action: 'vd_adv_settings_blur_notice_image_remove'
			        },
		            success: function(response) {
			            
			            if (response.status == true) {
				            
				            jQuery.ajax({
					            url: window.location.href,
					            success: function(response) {
						            
									jQuery('.vd_adv_settings_block.blur_content').replaceWith(jQuery('.vd_adv_settings_block.blur_content', response));

									jQuery('.vd_adv_settings_block.blur_content').show();
						            
					            }
				            });
				            
			            }
			            
		            }
				});

			});
			
		</script>
		
		<div class="vd_adv_settings_block alt_content_type facebook_like" <?php echo $options['alt_content'] == 'facebook_like' ? 'style="display: block;"' : ''; ?>>
			
			<label><?php _e('Type of Facebook like button', 'mediator_tech'); ?></label><br />

				<label><div class="vd_adv_settings_fb_layout"><img src="<?php echo plugin_dir_url( __FILE__ ) . '/img/fb_like_button.png'; ?>" /><input type="radio" name="mediator_tech_settings[facebook_layout]" value="button" <?php checked($options['facebook_layout'], 'button', true); ?> /><?php _e('Button', 'mediator_tech'); ?></div></label>
				<label><div class="vd_adv_settings_fb_layout"><img src="<?php echo plugin_dir_url( __FILE__ ) . '/img/fb_like_button_count.png'; ?>" /><input type="radio" name="mediator_tech_settings[facebook_layout]" value="button_count" <?php checked($options['facebook_layout'], 'button_count', true); ?> /><?php _e('Button count', 'mediator_tech'); ?></div></label>
				<label><div class="vd_adv_settings_fb_layout"><img src="<?php echo plugin_dir_url( __FILE__ ) . '/img/fb_like_box_count.png'; ?>" /><input type="radio" name="mediator_tech_settings[facebook_layout]" value="box_count" <?php checked($options['facebook_layout'], 'box_count', true); ?> /><?php _e('Box count', 'mediator_tech'); ?></div></label>
				<label><div class="vd_adv_settings_fb_layout"><img src="<?php echo plugin_dir_url( __FILE__ ) . '/img/fb_like_standard.png'; ?>" /><input type="radio" name="mediator_tech_settings[facebook_layout]" value="standard" <?php checked($options['facebook_layout'], 'standard', true); ?> /><?php _e('Standard', 'mediator_tech'); ?></div></label>

		</div>
		
		<div class="vd_adv_settings_block alt_content_type custom_html" <?php echo $options['alt_content'] == 'custom_html' ? 'style="display: block;"' : ''; ?>>
			<label><?php _e('Custom HTML content', 'mediator_tech'); ?></label>
			<textarea name="mediator_tech_settings[custom_html]" placeholder="<?php _e('Your alternative custom HTML content', 'mediator_tech'); ?>"><?php echo stripslashes($options['custom_html']); ?></textarea>
		</div>
		
		<div class="vd_adv_settings_block alt_content_type blur_content" <?php echo $options['alt_content'] == 'blur_content' ? 'style="display: block;"' : ''; ?>>
			<p><?php _e('This option allows you to gradually blur the content and ask user to disable adblock or whitelist your website. After first pageview visitor with AdBlock will see blurred text.', 'mediator_tech'); ?></p>
			<p>
				<label><?php _e("Show alert image after following number of page views", 'mediator_tech'); ?></label>
				<input type="number" name="mediator_tech_settings[blur_count]" value="<?php echo $options['blur_count']; ?>" />
			</p>
			
			<p>
				<label><?php _e("Continue to show image every following page view", 'mediator_tech'); ?></label>
				<input type="checkbox" name="mediator_tech_settings[blur_continue]" value="true" <?php checked('true', $options['blur_continue'], true); ?> />
			</p>
			
			<p>
				<label><?php _e("Choose alert image. It is recommended to use 640x480px image. You can preview default image here: ", 'mediator_tech'); ?><a href="/wp-content/plugins/mediator_tech_1-0/img/default-notice.png">here</a></label><br />
				<input type="radio" name="mediator_tech_settings[blur_image_type]" value="default" <?php checked('default', $options['blur_image_type'], true); ?> /><?php _e("Default image", 'mediator_tech'); ?><br />
				<input type="radio" name="mediator_tech_settings[blur_image_type]" value="custom" <?php checked('custom', $options['blur_image_type'], true); ?> /><?php _e("Custom image", 'mediator_tech'); ?>
			</p>
			
			<p>
				<label><?php _e("Upload custom image", 'mediator_tech'); ?></label>
				<?php
				
				if (empty($options['blur_image_file']) == false) {
					
					$blur_image_file_name = basename(get_attached_file($options['blur_image_file']));
					
					$blur_image_file_thumb = wp_get_attachment_thumb_url($options['blur_image_file']);
					
				?>

				<input type="hidden" name="mediator_tech_settings[blur_image_file]" value="<?php echo $options['blur_image_file']; ?>" />
				
				<div class="vd_adv_settings_file"><span class="file_thumbnail"><img src="<?php echo $blur_image_file_thumb; ?>" /></span><span class="file_name"><?php echo $blur_image_file_name; ?></span><span class="file_delete"></span></div>
				
				<?php
					
				} else {	
					
				?>
				
				<input type="file" name="mediator_tech_settings_blur_image_file" value="" />
				
				<?php
					
				}	
					
				?>
			</p>
			
		</div>
		
		<div class="vd_adv_settings_block">
			<label><?php _e('Enabled', 'mediator_tech'); ?></label>
			<input type="checkbox" name="mediator_tech_settings[enabled]" value="true" <?php checked('true', $options['enabled'], true); ?>/>
		</div>
		
		<?php
		
			wp_nonce_field('mediator-tech-save-settings');	
			
		?>
		
		<div class="vd_adv_settings_block">
			<input type="submit" value="<?php _e('Save settings', 'mediator_tech'); ?>" />
		</div>
		
	</form>
	
	
	
	<?php
	
}

add_action('widgets_init', function() {
	register_widget( 'MEDITOR_Display_Widget' );
});

class MEDITOR_Display_Widget extends WP_Widget {

	public function __construct() {
		
		$widget_ops = array( 
			'classname' => 'mediator_tech_widget',
			'description' => __('Mediator Plugin Widget', 'mediator_tech'),
		);
		
		parent::__construct( 'mediator_tech_widget', 'Mediator Advertising Display Widget', $widget_ops);
		
	}

	public function widget($args, $instance) {
		
		$options = get_option('mediator_tech_settings');
		
		if ($options == false || $options['enabled'] != 'true') {
			
			return false;
			
		} else {
			
			echo mediator_tech_output();
			
		}
		
	}

}

add_shortcode('mediator_tech', 'mediator_tech_shortcode');

function mediator_tech_shortcode($atts) {
	
	$options = get_option('mediator_tech_settings');
		
	if ($options == false || $options['enabled'] != 'true') {
		
		return false;
		
	} else {
		
		return mediator_tech_output();
		
	}
	
}


function mediator_tech_output() {
	
	$options = get_option('mediator_tech_settings');
	
	if ($options['adsense_code'] != '') {
		
		$random_id1 = mediator_tech_random_id();
		
		$random_id2 = mediator_tech_random_id();
		
		$output = '<div id="' . $random_id1 . '">';
		
		$output .= stripslashes($options['adsense_code']);
		
		$output .= '</div>';
		
		if ($options['alt_content'] == 'custom_html' && $options['custom_html'] != '') {
			
			$output .= '<div id="' . $random_id2 . '" style="display: none;">';
			
			$output .= stripslashes($options['custom_html']);
			
			$output .= '</div>';
			
		} elseif ($options['alt_content'] == 'mailchimp_form' && $options['mailchimp_key'] != '' && $options['mailchimp_list'] != '') {
			
			$output .= '<div id="' . $random_id2 . '" style="display: none;">';
			
			$actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			
			$output .= '<form method="post" class="mediator_tech_mailchimp">';
				
				$output .= '<input type="hidden" name="mediator_tech_mailchimp_form" value="true" />';
				
				$output .= '<input type="hidden" name="mediator_tech_mailchimp_page" value="' . $actual_link . '" />';
				
				$output .= '<input type="text" name="mediator_tech_mailchimp_email" value="" />';
				
				$output .= '<input type="submit" value="' . __('Subscribe', 'mediator_tech') . '" ?>';
				
			$output .= '</form>';
			
			$output .= '</div>';	
			
		} elseif ($options['alt_content'] == 'facebook_like') {
			
			$actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			
			$facebook_layout = $options['facebook_layout'];
			
			echo '<div id="' . $random_id2 . '" style="display: none;">';
			
			?>
			
			<div id="fb-root"></div>
			<script>(function(d, s, id) {
			  var js, fjs = d.getElementsByTagName(s)[0];
			  if (d.getElementById(id)) return;
			  js = d.createElement(s); js.id = id;
			  js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.8";
			  fjs.parentNode.insertBefore(js, fjs);
			}(document, 'script', 'facebook-jssdk'));</script>

			<div class="mediator_tech_fb_wrapper" style="padding: 8px 10px;"><div class="fb-like" data-href="<?php echo $actual_link; ?>" data-layout="<?php echo $facebook_layout; ?>" data-action="like" data-show-faces="false" data-share="false"></div></div>	
			
			<?php
				
			echo '</div>';	
			
		}
		
		ob_start();
		
		?>
		
		<style>
			
			#mediator_tech_overlay {
				
				height: 100%;
				opacity : 0.8;
				position: fixed;
				top: 0;
				left: 0;
				background-color: black;
				width: 100%;
				height: 100%;
				z-index: 5000;
				
			}
			
			#mediator_tech_overlay_notice {
				
				height: 480px;
				width: 640px;
				position: fixed;
				top: 50%;
				left: 50%;
				transform: translate3d(-50%, -50%, 0);
				background-size: cover;
				background-repeat: no-repeat;
				background-position: center;
				z-index: 6000;
				
			}
			
			.mediator_tech_overlay_close {
				
				text-shadow: none;
				color: blue;
				position: absolute;
				top: 4px;
				left: 4px;
				line-height: 1.4;
				
			}
			
			.mediator_tech_overlay_close:hover {
				cursor: pointer;
				text-decoration: underline;
			}
			
		</style>
		
		<script>
			
			jQuery(document).on('submit', 'form.mediator_tech_mailchimp', function(e) {
				
				e.preventDefault();
				
				jQuery.ajax({
					url: '<?php bloginfo('url'); ?>/wp-admin/admin-ajax.php',
		            type: 'post',
		            dataType: 'JSON',
		            data: {
			            action: 'vd_mailchimp_subscribe',
			            mediator_tech_mailchimp_form: true,
			            mediator_tech_mailchimp_page: jQuery('form.mediator_tech_mailchimp input[name="mediator_tech_mailchimp_page"]').val(),
			            mediator_tech_mailchimp_email: jQuery('form.mediator_tech_mailchimp input[name="mediator_tech_mailchimp_email"]').val(),
			        },
		            success: function(response) {
			            
			            console.log(response);
			            
			            if (response.status == true) {
				            
				            alert(response.message);
				            
				            jQuery('form.mediator_tech_mailchimp input[name="mediator_tech_mailchimp_email"]').val('');
				            
			            } else if (response.status == false) {
				            
				            alert(response.message);
				            
			            }
			            
		            }
				});
				
			});
			
			function mediator_tech_blur_content() {
				
				var blur_size = parseInt(Cookies.get('azaza'));
				
				if (blur_size >= 0 != true) {
					
					blur_size = 0;
					
				}
				
				blur_size++;
				
				Cookies.set('azaza', blur_size);

				if (blur_size > 1) {
				
					jQuery('body').css({
						'color': 'transparent',
						'text-shadow': 'rgb(0, 0, 0) 0px 0px ' + blur_size + 'px'
					});

				}

				if (blur_size <?php echo (isset($options['blur_continue']) && $options['blur_continue'] == 'true') ? '>=' : '=='; ?> parseInt('<?php echo $options['blur_count']; ?>')) {

					mediator_tech_blur_notice();

				}
				
			}

			function mediator_tech_blur_notice() {

				var notice_image_url = '<?php echo $options['blur_image_type'] == 'custom' ? wp_get_attachment_url($options['blur_image_file']) : plugin_dir_url( __FILE__ ) . '/img/default-notice.png'; ?>';

				jQuery("body").append("<div id='mediator_tech_overlay'></div>");

				jQuery("body").append("<div id='mediator_tech_overlay_notice'><span class='mediator_tech_overlay_close'><?php _e('[Close]', 'mediator_tech'); ?></span></div>");

				jQuery("#mediator_tech_overlay_notice")
				.height(480)
				.width(640)
				.css({
					'background-image': 'url(' + notice_image_url + ')'
				});

			}
			
			function mediator_tech_ping(blocked) {
				
				jQuery.ajax({
					url: '<?php bloginfo('url'); ?>/wp-admin/admin-ajax.php',
		            type: 'post',
		            dataType: 'JSON',
		            data: {
			            action: 'mediator_tech_ping',
			            blocked: blocked
			        },
		            success: function(response) {
			            
			            console.log(response);
			            
		            }
				});
				
			}
		
			jQuery(document).ready(function() {
				
				var main_item = jQuery("#<?php echo $random_id1; ?>")[0];
				
				window.setTimeout(function() {
					
					if ((main_item.offsetHeight * main_item.offsetWidth) < 10000) {
						
						<?php 
							
						if ($options['alt_content'] == 'blur_content') {
							
						?>
							
							mediator_tech_blur_content();
							
						<?php
							
						} else {
							
						?>
							
							jQuery("#<?php echo $random_id2; ?>").show();
							
						<?php
							
						}
						
						?>
						
						mediator_tech_ping(true);
						
						var monitor = setInterval(function() {
							
						    var elem = document.activeElement;
						    
						    if (elem && elem.tagName == 'IFRAME' && elem == jQuery('.fb-like iframe')[0]) {
							    
						        clearInterval(monitor);
						        
								jQuery.ajax({
									url: '<?php bloginfo('url'); ?>/wp-admin/admin-ajax.php',
						            type: 'post',
						            dataType: 'JSON',
						            data: {
							            action: 'mediator_tech_ping_fb'
							        },
						            success: function(response) {
							            
							            console.log(response);
							            
						            }
								});
						        
						    }
						    
						}, 100);
						
						
					} else {
						
						mediator_tech_ping(false);
						
					}
					
				}, 100);
				
			});
			
			jQuery(document).on('click', '.mediator_tech_overlay_close', function(e) {
				jQuery('#mediator_tech_overlay_notice').remove();
				jQuery('#mediator_tech_overlay').remove();
			});
			
		</script>
		
		<?php
			
		$output .= ob_get_clean();
		
		return $output;
		
	}
	
}

function mediator_tech_get_real_ip() {
	
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    
    $remote  = $_SERVER['REMOTE_ADDR'];

    if(filter_var($client, FILTER_VALIDATE_IP)) {
	    
        $ip = $client;
        
    } elseif(filter_var($forward, FILTER_VALIDATE_IP)) {
	    
        $ip = $forward;
        
    } else {
	    
        $ip = $remote;
        
    }

    return $ip;
    
}

/* settings js update back-end */

add_action('wp_ajax_vd_adv_settings_blur_notice_image_remove', 'vd_adv_settings_blur_notice_image_remove');

function vd_adv_settings_blur_notice_image_remove() {

	if (current_user_can('administrator')) {

		$options = get_option('mediator_tech_settings');

		$options['blur_image_file'] = '';
		$options['blur_image_type'] = 'default';

		update_option('mediator_tech_settings', $options, 'yes');		

		echo json_encode(array(
			'status' => true,
		));

	} else {

		echo json_encode(array(
			'status' => false,
		));

	}

	die;

}

add_action('wp_ajax_vd_adv_settings_mailchimp_update', 'vd_adv_settings_mailchimp_update');

function vd_adv_settings_mailchimp_update() {
	
	if (current_user_can('administrator')) {
		
		if (isset($_POST['api_key'])) {
			
			$options = get_option('mediator_tech_settings');
			
			$options['mailchimp_key'] = trim($_POST['api_key']);
			
			update_option('mediator_tech_settings', $options, 'yes');
			
			echo json_encode(array(
				'status' => true,
			));
			
		} else {
			
			echo json_encode(array(
				'status' => false,
			));
			
		}
		
	}
	
	die;
	
}


/* stats logic */

add_action('wp_ajax_mediator_tech_ping', 'mediator_tech_ping');
add_action('wp_ajax_nopriv_mediator_tech_ping', 'mediator_tech_ping');

function mediator_tech_ping() {
	
	$user_agent = $_SERVER['HTTP_USER_AGENT'];
	
	$blocked = $_POST['blocked'];
	
	$ip = mediator_tech_get_real_ip();
	
	$string = $ip . ' ' . $user_agent;
	
	$hash = md5($string);
	
	global $wpdb;
	
	/*
	for ($i = 1; $i < 30; $i++) {
		
		if ($i < 10) {
			
			$day = '0' . $i;
			
		} else {
			
			$day = $i;
			
		}
		
		$min = 50;
		
		$max = rand($min, 500);
		
		for ($x = $min; $x < $max; $x++) {
			
		$wpdb->insert(
			$wpdb->prefix . 'mediator_tech_stats',
			array(
				'date' => date('Y-m-') . $day,
				'hash' => md5(rand(0, 1000)),
				'mailchimp' => rand(0, 1),
				'facebook' => rand(0, 1)
			)
		);	
			
		}
		
		
		
	}
	*/
	
	if ($blocked == 'true') {
	
		$check = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . 'mediator_tech_stats' . " WHERE hash = '" . $hash . "' AND date = '" . date('Y-m-d') . "'");
		
		if ($check == null) {
			
			$wpdb->insert(
				$wpdb->prefix . 'mediator_tech_stats',
				array(
					'date' => date('Y-m-d'),
					'hash' => $hash
				)
			);	
			
		}
		
	} elseif ($blocked == 'false') {
		
		$check = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . 'mediator_tech_stats' . " WHERE hash = '" . $hash . "' AND date = '" . date('Y-m-d') . "' AND unblocked != '1'");
		
		if ($check != null) {
			
			$wpdb->update(
				$wpdb->prefix . 'mediator_tech_stats',
				array(
					'unblocked' => 1
				),
				array(
					'hash' => $hash,
					'date' => date('Y-m-d')
				)
			);
			
		}
		
	}	
	
}

add_action('wp_ajax_mediator_tech_ping_fb', 'mediator_tech_ping_fb');
add_action('wp_ajax_nopriv_mediator_tech_ping_fb', 'mediator_tech_ping_fb');

function mediator_tech_ping_fb() {
	
	$user_agent = $_SERVER['HTTP_USER_AGENT'];
	
	$ip = mediator_tech_get_real_ip();
	
	$string = $ip . ' ' . $user_agent;
	
	$hash = md5($string);
	
	global $wpdb;
	
	$wpdb->update(
		$wpdb->prefix . 'mediator_tech_stats',
		array(
			'facebook' => 1
		),
		array(
			'hash' => $hash,
			'date' => date('Y-m-d')
		)
	);
	
}

function mediator_tech_stats_mailchimp() {
	
	$user_agent = $_SERVER['HTTP_USER_AGENT'];
	
	$ip = mediator_tech_get_real_ip();
	
	$string = $ip . ' ' . $user_agent;
	
	$hash = md5($string);
	
	global $wpdb;
	
	$wpdb->update(
		$wpdb->prefix . 'mediator_tech_stats',
		array(
			'mailchimp' => 1
		),
		array(
			'hash' => $hash,
			'date' => date('Y-m-d')
		)
	);
	
}

/* mailchimp logic */

add_action('wp_ajax_vd_mailchimp_subscribe', 'mediator_tech_mailchimp_subscribe');
add_action('wp_ajax_nopriv_vd_mailchimp_subscribe', 'mediator_tech_mailchimp_subscribe');

function mediator_tech_mailchimp_subscribe() {
	
	if (isset($_POST['mediator_tech_mailchimp_form']) && $_POST['mediator_tech_mailchimp_form'] == 'true') {
		
		$options = get_option('mediator_tech_settings');
		
		$api_key = $options['mailchimp_key'];
		
		$list_id = $options['mailchimp_list'];
		
		/* initialize the new MailChimp API */
		
		$api = new MailChimp($api_key);
		
		/* get the list of all merge vars for the list */
		
		$list_of_merge_vars = $api->get('lists/' . $list_id . '/merge-fields');
		
		/* prepare an empty array for required merge tags */
		
		$required_marge_tags = array();
		
		/* if there are merge fields in the list, loop over them and check if there are any required ones */
		/* save all required merge field tags in the array with empty values */
		
		if (is_array($list_of_merge_vars['merge_fields'])) {
			
			foreach ($list_of_merge_vars['merge_fields'] as $single_merge_var) {
				
				if ($single_merge_var['required']) {
					
					$single_merge_var_tag = $single_merge_var['tag'];
					
					$required_marge_tags[$single_merge_var_tag] = '';
					
				}
				
			}
			
		}
		
		/* set the FROMURL merge tag with the URL of the page, from which the user is subscribing */
		
		$required_marge_tags['FROMURL'] = $_POST['mediator_tech_mailchimp_page'];

		$required_marge_tags['ADBLOCK'] = 'true';
		
		/* if there isn't any merge vars in the list, add a merge FROMURL tag to it */
		
		if (is_array($list_of_merge_vars['merge_fields']) != true) {
			
			$api->post('lists/' . $list_id . '/merge-fields', array(
				'tag' => 'FROMURL',
				'name' => 'Subscribed from',
				'type' => 'text'
			));

			$api->post('lists/' . $list_id . '/merge-fields', array(
				'tag' => 'ADBLOCK',
				'name' => 'User with adblock',
				'type' => 'text'
			));
			
		} else {
			
			/* if there are merge tags in the list, check if there's FROMURL already there */
			/* if not, add it to the list as an additional merge tag */
			
			$list_of_merge_tags = array_map(function ($ar) {return $ar['tag'];}, $list_of_merge_vars['merge_fields']);
			
			if (in_array('FROMURL', $list_of_merge_tags) != true) {
				
				$api->post('lists/' . $list_id . '/merge-fields', array(
					'tag' => 'FROMURL',
					'name' => 'Subscribed from',
					'type' => 'text'
				));
				
			} 

			if (in_array('ADBLOCK', $list_of_merge_tags) != true) {

				$api->post('lists/' . $list_id . '/merge-fields', array(
					'tag' => 'ADBLOCK',
					'name' => 'User with adblock',
					'type' => 'text'
				));

			}
			
		}
		
		/* subscribe the new user to the list and send the correct merge fields with him */
		
		$result = $api->post('lists/' . $list_id . '/members', array(
			'email_address' => $_POST['mediator_tech_mailchimp_email'],
            'status' => 'subscribed',
            'email_type' => 'html',
            'merge_fields' => $required_marge_tags
		));
		
		if ($api->success()) {
			
			mediator_tech_stats_mailchimp();
			
			echo json_encode(array(
				'status' => true,
				'message' => __("You've successfully subscribed", 'mediator_tech')
			));
			
		} else {
			
			$error = $api->getLastError();
			
			if (strpos($error, 'is already a list member') !== false) {
				
				$message = __('You have already subscribed', 'mediator_tech');
				
			} else {
				
				$message = $error;
				
			}
			
			echo json_encode(array(
				'status' => false,
				'message' => $message
			));
			
		}
		
		die;
		
	}
	
}

add_action("admin_print_footer_scripts", "mediator_tech_button_script");

function mediator_tech_button_script() {
	
    if (wp_script_is("quicktags")) {
	    
        ?>
            <script type="text/javascript">

                QTags.addButton(
                    "mediator_tech_shortcode", 
                    "Mediator", 
                    mediator_tech_shortcode_callback
                );

                function mediator_tech_shortcode_callback() {
                    
                    QTags.insertContent("[mediator_tech]");
                    
                }
                
            </script>
        <?php
	       
    }
    
}

add_action('init', 'mediator_tech_tinymce_button');

function mediator_tech_tinymce_button() {

	if (!current_user_can('edit_posts') && !current_user_can('edit_pages') && get_user_option('rich_editing') == 'true') {
	
		return;
	
	}

	add_filter("mce_external_plugins", "mediator_tech_register_tinymce_plugin"); 

	add_filter('mce_buttons', 'mediator_tech_add_tinymce_button');
	
}

function mediator_tech_register_tinymce_plugin($plugin_array) {
	
    $plugin_array['mediator_tech_button'] = plugin_dir_url(__FILE__) . 'js/tinymce_shortcode_plugin.js';
    
    return $plugin_array;
    
}

function mediator_tech_add_tinymce_button($buttons) {
	
    $buttons[] = "mediator_tech_button";
    
    return $buttons;
}

function mediator_tech_random_id($length = 10) {
	
    return substr(str_shuffle(str_repeat($x = 'abcdefghijklmnopqrstuvwxyz', ceil($length/strlen($x)))), 1, $length);
    
}

add_action('wp_enqueue_scripts', 'mediator_tech_scripts' );

function mediator_tech_scripts() {
	
    wp_enqueue_script('mediator_tech_js_cookie', plugins_url( 'js/js.cookie.js', __FILE__ ), array(), '1.0.0', true);
    
}

add_action('admin_enqueue_scripts', 'mediator_tech_admin_scripts' );

function mediator_tech_admin_scripts() {
	
	/* check the current screen and load the chart.js plugin only if we're at the stats page */
	
	$current_screen = get_current_screen();
	
	if ($current_screen->base == 'toplevel_page_mediator-tech') {
	
		wp_enqueue_script('mediator_tech_chartjs', plugins_url( 'js/Chart.min.js', __FILE__ ), array(), '2.3.0', true);
		
	}	
	
}

	
?>