<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('GSPB_GreenShift_Settings')) {

	class GSPB_GreenShift_Settings
	{
		private $allowed_font_ext = [
			'woff2',
			'woff',
			'tiff',
			'ttf',
		];

		public function __construct()
		{
			add_action('admin_menu', array($this, 'greenshift_admin_page'));
			add_action( 'wp_ajax_gspb_generate_stylebook', array($this, 'gspb_generate_stylebook') );
			add_action('admin_init', array($this, 'gspb_stylebook_redirect'));
			if (!defined('REHUB_ADMIN_DIR')) {
				//Show Reusable blocks column
				add_action('registered_post_type', array($this, 'gspb_template_menu_display'), 10, 2);
				add_filter('manage_wp_block_posts_columns', array($this, 'gspb_template_screen_add_column'));
				add_action('manage_wp_block_posts_custom_column', array($this, 'gspb_template_screen_fill_column'), 1000, 2);
				// Force Block editor for Reusable Blocks even when Classic editor plugin is activated
				add_filter('use_block_editor_for_post', array($this, 'gspb_template_gutenberg_post'), 1000, 2);
				add_filter('use_block_editor_for_post_type', array($this, 'gspb_template_gutenberg_post_type'), 1000, 2);
				//Shortcode output for reusable blocks
				add_shortcode('wp_reusable_render', array($this, 'gspb_template_shortcode_function'));
				//Ajax render action
				add_action('wp_ajax_gspb_el_reusable_load', array($this, 'gspb_el_reusable_load'));
				add_action('wp_ajax_nopriv_gspb_el_reusable_load', array($this, 'gspb_el_reusable_load'));
				//settings fonts actions
				add_action('wp_ajax_gspb_settings_add_font', array($this, 'gspb_settings_add_font'));
			}
		}

		public function greenshift_admin_page()
		{

			$parent_slug = 'greenshift_dashboard';

			add_menu_page(
				'GreenShift',
				'GreenShift',
				'manage_options',
				$parent_slug,
				array($this, 'welcome_page'),
				plugin_dir_url(__FILE__) . 'libs/gspbLogo.svg',
				70
			);

			add_submenu_page(
				$parent_slug,
				esc_html__('Settings', 'greenshift-animation-and-page-builder-blocks'),
				esc_html__('Settings', 'greenshift-animation-and-page-builder-blocks'),
				'manage_options',
				'greenshift',
				array($this, 'settings_page')
			);

			add_submenu_page(
				$parent_slug,
				esc_html__('Addons', 'greenshift-animation-and-page-builder-blocks'),
				esc_html__('Addons', 'greenshift-animation-and-page-builder-blocks'),
				'manage_options',
				'greenshift_dashboard-addons',
				array($this, 'addons_page')
			);

			add_submenu_page(
				$parent_slug,
				esc_html__('Upgrade', 'greenshift-animation-and-page-builder-blocks'),
				esc_html__('Upgrade', 'greenshift-animation-and-page-builder-blocks'),
				'manage_options',
				'greenshift_upgrade',
				array($this, 'upgrade_page')
			);

			add_submenu_page(
				$parent_slug,
				esc_html__('Contact Us', 'greenshift-animation-and-page-builder-blocks'),
				esc_html__('Contact Us', 'greenshift-animation-and-page-builder-blocks'),
				'manage_options',
				'greenshift_contact',
				array($this, 'contact_page')
			);
			add_submenu_page(
				$parent_slug,
				esc_html__('Import/Export', 'greenshift-animation-and-page-builder-blocks'),
				esc_html__('Import/Export', 'greenshift-animation-and-page-builder-blocks'),
				'manage_options',
				'greenshift_import',
				array($this, 'import_page')
			);
			add_submenu_page(
				$parent_slug,
				esc_html__('Demo Import', 'greenshift-animation-and-page-builder-blocks'),
				esc_html__('Demo Import', 'greenshift-animation-and-page-builder-blocks'),
				'manage_options',
				'greenshift_demo',
				array($this, 'import_demo')
			);

			$stylebook_post_id = get_option( 'gspb_stylebook_id' );

			if( $stylebook_post_id ){
		
				$editposturl = get_edit_post_link( $stylebook_post_id );
		
				add_submenu_page(
					$parent_slug,
					esc_html__('Stylebook', 'greenshift-animation-and-page-builder-blocks'),
					esc_html__('Stylebook', 'greenshift-animation-and-page-builder-blocks'),
					'manage_options',
					$editposturl
				);
		
			} else {
		
				add_submenu_page(
					$parent_slug,
					esc_html__('Stylebook', 'greenshift-animation-and-page-builder-blocks'),
					esc_html__('Stylebook', 'greenshift-animation-and-page-builder-blocks'),
					'manage_options',
					'greenshift_stylebook',
					array($this, 'stylebook_page_callback')
				);
			}
		}

		public function welcome_page()
		{
			require_once GREENSHIFT_DIR_PATH . 'templates/admin/welcome-page.php';
		}

		public function contact_page()
		{
			require_once GREENSHIFT_DIR_PATH . 'templates/admin/contact-page.php';
		}

		public function import_page()
		{
			require_once GREENSHIFT_DIR_PATH . 'templates/admin/import-page.php';
		}

		public function import_demo()
		{
			require_once GREENSHIFT_DIR_PATH . 'templates/admin/import-demo.php';
		}

		public function addons_page()
		{
			require_once GREENSHIFT_DIR_PATH . 'templates/admin/addon-page.php';
		}

		public function upgrade_page()
		{
			require_once GREENSHIFT_DIR_PATH . 'templates/admin/upgrade-page.php';
		}

		public function stylebook_page_callback() { 
			?>
				<style>
					#greenshift_stylebook_btn{margin-top: 30px;width: 220px;height: 40px;font-size: 1rem;line-height: 1rem;}.stylbook_btn_wrapper{position: relative;display: flex;align-items: center;}
				</style>
				<script>
					jQuery(document).ready(function ($) {
						// Stylebook ajax request
						$( "#stylebook_generation" ).submit(function( e ) {
							e.preventDefault();

							$('#ajax-response').html('');

							const targetForm = e.target;
							const payload = new FormData( targetForm );
							payload.append( "action", "gspb_generate_stylebook" )
							$.ajax({
								method: "POST",
								url: ajaxurl,
								processData: false, 
								contentType: false, 
								data: payload,
								beforeSend: function( response ) {
									$(".spinner").addClass("is-active");    
								}
							})    
							.success(function ( response ) {
								$(".spinner").removeClass("is-active");  
								if( response.data.status == "success" ) {
									const post_link = response?.data?.post_link;
									if( post_link ){
										const postEditLink = post_link.replace(/&amp;/g, '&');
										window.location.href = postEditLink;
									}
								}
								if( response.data.status == "error" ) {
									$('#ajax-response').append('<div class="notice notice-error"><p>' + response.data.msg + '</p></div>');   
								}  
							})
							.error(function ( response ) {
								$(".spinner").removeClass("is-active");   
								$('#ajax-response').append('<div class="notice notice-error"><p>Something went wrong.</p></div>');   
							});

						});
					});
				</script>
				<div class="stylebook_form_wrap">
					<h1><?php _e( 'GreenShift Stylebook', 'greenshift-animation-and-page-builder-blocks' ); ?></h1>
					<div id="ajax-response"> </div>
					<form id="stylebook_generation" method="post" class="stylbook_btn_wrapper">
						<?php wp_nonce_field('gspb_generate_stylebook', 'gspb_stylebook'); ?>
						<div class="stylbook_btn_wrapper">
							<input type="submit" id="greenshift_stylebook_btn" name="greenshift_stylebook_btn" value=<?php _e( 'Create Stylebook', 'greenshift-animation-and-page-builder-blocks' ); ?> class="button button-primary button-large">
							<span class="spinner"></span>
						</div>			
					</form>
				</div>	
			<?php
		}

		public function gspb_stylebook_redirect() {
			if (isset($_GET['post_type']) && $_GET['post_type'] === 'gspbstylebook') {
				wp_safe_redirect( admin_url('?page=greenshift_dashboard') );
				exit;
			}
		}

		public function gspb_generate_stylebook(){

			$response = array();
			
			if ( ! isset( $_POST['gspb_stylebook'] )  || ! wp_verify_nonce( $_POST['gspb_stylebook'], 'gspb_generate_stylebook' )){
		
				$response =  array(
					'status' => 'error',
					'msg' => 'User Security not varified.'
				);
		
			} else {
				$stylebook_post_id = get_option( 'gspb_stylebook_id' );
		
				if( ! $stylebook_post_id ){
			
					// Create post object
					$stylebook_post = array(
						'post_title'    => 'Greenshift Stylebook',
						'post_type'	   	=> 'gspbstylebook',
						'post_name'		=>	'greenshift-stylebook',
						'post_content'	=>	'<!-- wp:greenshift-blocks/stylebook /-->',
						'post_status'   => 'publish',
					);
					
					// Insert the post into the database
					$stylebook_post_id = wp_insert_post( $stylebook_post );
			
					if( ! is_wp_error( $stylebook_post_id ) ){
			
							$response =  array(
								'status' => 'success',
								'post_link' => get_edit_post_link( $stylebook_post_id )
							);
			
							update_option( 'gspb_stylebook_id', $stylebook_post_id );
			
							// we need to flush this because post is created programatically.
							flush_rewrite_rules();
			
						} else {
			
							$response =  array(
								'status' => 'error',
								'msg' => $stylebook_post_id->get_error_message()
							);
			
							return $response;
						}
			
					} else {
					
					$response =  array(
						'status' => 'success',
						'post_link' => get_edit_post_link( $stylebook_post_id )
					);
			
				}
			}
		
			wp_send_json_success( $response );
		
		}

		public function settings_page()
		{

			if (!current_user_can('manage_options')) {
				wp_die('Unauthorized user');
			}

			// Get the active tab from the $_GET param
			$default_tab = null;
			$tab         = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : $default_tab;

		?>
			<div class="wrap">
				<style>
					#wpcontent {
						background: #f8fafc;
						padding: 0;
					}

					.wrap {
						margin: 0 auto;
					}

					.wrap .notice,
					.wrap .error {
						display: none
					}

					.wrap h2 {
						font-size: 1.4em;
						margin-bottom: 1.5em;
						margin-top: 0;
						font-weight: bold;
					}

					.greenshift_form {
						padding: 15px 25px 25px 25px;
						background: #fff;
						margin: 15px 0 35px 0;
						box-shadow: 0 0 3px 0 rgb(0 0 0 / 10%), 0 1px 2px -1px rgb(0 0 0 / 10%);
						overflow: hidden;
					}

					.greenshift_form .form-table {
						margin-top: 0
					}

					.greenshift_form .form-table textarea{
						border-color:#ccc
					}

					.gs-introtext {
						font-size: 14px;
						max-width: 500px;
						margin: 0 auto 50px auto
					}

					.gs-intro-video iframe {
						box-shadow: 10px 10px 20px rgb(0 0 0 / 15%);
					}

					.gs-intro-video {
						margin-bottom: 40px
					}

					.wrap h1 {
						text-align: left;
						padding: 15px 20px;
						margin: -1px -1px 60px -1px;
						font-size: 13px;
						font-weight: bold;
						text-transform: uppercase;
						box-shadow: 0 3px 8px rgb(0 0 0 / 5%);
					}

					.gs-padd {
						padding: 25px;
						text-align: left;
						margin: 1.5em auto;
						max-width: 900px;
					}

					.rtl .gs-padd {
						text-align: right
					}

					.wp-core-ui .button-primary {
						background-color: #2184f9
					}

					.nav-tab {
						font-size: 16px;
						border: none;
						padding: 10px 16px;
						background: none;
						border-bottom: 2px solid transparent;
					}

					.nav-tab-active,
					.nav-tab-active:focus,
					.nav-tab-active:focus:active,
					.nav-tab:hover {
						border-bottom: 2px solid #2184f9;
						background: #fff;
						color: #2184f9;
					}

					.nav-tab-wrapper {
						padding-left: 20px;
						background: white;
						border-bottom: 1px solid #edeff5;
					}

					.nav-tab-wrapper>div {
						margin: 0 auto;
						max-width: 950px
					}

					.wrap .fs-notice {
						margin: 0 25px 35px 25px !important
					}

					.wrap .fs-plugin-title {
						display: none !important
					}

					.mb30 {
						margin-bottom: 30px
					}
				</style>

				<!-- Here are our tabs -->
				<nav class="nav-tab-wrapper">
					<div>
						<a href="?page=greenshift" class="nav-tab <?php if ($tab === null) : ?>nav-tab-active<?php endif; ?>">
							<?php esc_html_e("General", 'greenshift-animation-and-page-builder-blocks'); ?>
						</a>
						<a href="?page=greenshift&tab=save_css" class="nav-tab <?php if ($tab === 'save_css') : ?>nav-tab-active<?php endif; ?>">
							<?php esc_html_e("CSS Management", 'greenshift-animation-and-page-builder-blocks'); ?>
						</a>
						<a href="?page=greenshift&tab=breakpoints" class="nav-tab <?php if ($tab === 'breakpoints') : ?>nav-tab-active<?php endif; ?>">
							<?php esc_html_e("Breakpoints", 'greenshift-animation-and-page-builder-blocks'); ?>
						</a>
						<a href="?page=greenshift&tab=scripts" class="nav-tab <?php if ($tab === 'scripts') : ?>nav-tab-active<?php endif; ?>">
							<?php esc_html_e("Script Management", 'greenshift-animation-and-page-builder-blocks'); ?>
						</a>
						<a href="?page=greenshift&tab=keys" class="nav-tab <?php if ($tab === 'keys') : ?>nav-tab-active<?php endif; ?>">
							<?php esc_html_e("API Keys", 'greenshift-animation-and-page-builder-blocks'); ?>
						</a>
					</div>
				</nav>

				<div class="tab-content gs-padd">
					<?php
					switch ($tab):
						case 'save_css':

							if (isset($_POST['gspb_save_settings'])) {
								if (!wp_verify_nonce($_POST['gspb_settings_field'], 'gspb_settings_page_action')) {
									esc_html_e("Sorry, your nonce did not verify.", 'greenshift-animation-and-page-builder-blocks');
									return;
								}
								update_option('gspb_css_save', sanitize_text_field($_POST['gspb_settings_option']));

								$default_settings = get_option('gspb_global_settings');

								$sanitised = array();
								if (!empty($_POST['reusablestyles'])) {
									foreach ($_POST['reusablestyles'] as $key => $value) {
										$sanitised['reusablestyles'][$key] = array(
											"style" => sanitize_textarea_field($value['style']),
											"blockname" =>  sanitize_text_field($value['blockname']),
										);
									}
									$newargs = wp_parse_args($sanitised, $default_settings);
									update_option('gspb_global_settings', $newargs);
								}
								if( isset( $_POST['cf_utility_on'] ) ){
									$newargs = wp_parse_args(array('cf_utility_on'=> true), $default_settings);
									update_option('gspb_global_settings', $newargs);
								} else {
									$newargs = wp_parse_args(array('cf_utility_on'=> false), $default_settings);
									update_option('gspb_global_settings', $newargs);
								}
							}

							$global_settings = get_option('gspb_global_settings');
							$css_tsyle_option = get_option('gspb_css_save');
							$cf_utility_on = !empty($global_settings['cf_utility_on']) ? $global_settings['cf_utility_on'] : '';
					?>
							<div class="gspb_settings_form">
								<form method="POST">
									<h2><?php esc_html_e("Save Css", 'greenshift-animation-and-page-builder-blocks'); ?></h2>
									<div class="greenshift_form">
										<?php wp_nonce_field('gspb_settings_page_action', 'gspb_settings_field'); ?>
										<table class="form-table">
											<tr>
												<th> <label for="css_system"><?php esc_html_e("Css location", 'greenshift-animation-and-page-builder-blocks'); ?></label> </th>
												<td>
													<select name="gspb_settings_option">
														<option value="inline" <?php selected($css_tsyle_option, 'inline'); ?>><?php esc_html_e("Inline in Head", 'greenshift-animation-and-page-builder-blocks'); ?> </option>
														<option value="inlineblock" <?php selected($css_tsyle_option, 'inlineblock'); ?>> <?php esc_html_e("Inline in block", 'greenshift-animation-and-page-builder-blocks'); ?> </option>
													</select>
												</td>
											</tr>
											<tr>
												<th> <label for="css_system_cf"><?php esc_html_e("Support for Core Framework Utility classes", 'greenshift-animation-and-page-builder-blocks'); ?></label> </th>
												<td>
												<input type="checkbox" name="cf_utility_on" id="cf_utility_on" <?php echo $cf_utility_on == true ? 'checked' : ''; ?> />
												</td>
											</tr>
										</table>
										<div style="margin-bottom:15px"><?php esc_html_e("Use Inline in block only if you have some issues with not updating styles of blocks or cache. Once saved as inline in block, styles can be overwritten only when you update post with blocks", 'greenshift-animation-and-page-builder-blocks'); ?></div>

										<input type="submit" name="gspb_save_settings" value="<?php esc_html_e("Save settings"); ?>" class="button button-primary button-large">
									</div>

									<h2 style="margin-top:40px"><?php esc_html_e("Global Reusable CSS", 'greenshift-animation-and-page-builder-blocks'); ?></h2>

									<div class="greenshift_form">
										<?php
										$gs_reusable_css = (!empty($global_settings['reusablestyles'])) ? $global_settings['reusablestyles'] : '';
										if ($gs_reusable_css) {
											foreach ($gs_reusable_css as $key => $value) {
												echo '<div style="margin-bottom:5px;    color: #999;">';
												esc_html_e("Class: ", 'greenshift-animation-and-page-builder-blocks');
												echo esc_attr($key);
												echo ', ';
												esc_html_e("Block Type: ", 'greenshift-animation-and-page-builder-blocks');
												echo esc_attr($value['blockname']);
												echo '</div>';
												echo '<textarea style="width:100%; border-color:#ddd" rows="5" name="reusablestyles[' . $key . '][style]" id="key_' . $key . '">' . $value['style'] . '</textarea>';
												echo '<input type="hidden" name="reusablestyles[' . $key . '][blockname]" value="' . $value['blockname'] . '" /><br/><br/>';
											}
											echo '<input type="submit" name="gspb_save_settings" value="' . esc_html__("Save settings") . '" class="button button-primary button-large">';
										}

										?>
									</div>

								</form>
							</div>
						<?php
							break;
						case 'breakpoints':
							$global_settings = get_option('gspb_global_settings');
							$gsbp_breakpoints = apply_filters('greenshift_responsive_breakpoints', array(
								'mobile' 	=> 576,
								'tablet' 	=> 768,
								'desktop' =>  992
							));

							if (isset($_POST['gspb_save_settings']) && isset($_POST['gspb_settings_field']) && wp_verify_nonce($_POST['gspb_settings_field'], 'gspb_settings_page_action')) {
								$breakpoints = array(
									"mobile" =>  sanitize_text_field($_POST['mobile']),
									"tablet" =>  sanitize_text_field($_POST['tablet']),
									"desktop" =>  sanitize_text_field($_POST['desktop']),
									"row" =>  sanitize_text_field($_POST['row']),
								);
								$global_settings['breakpoints'] = $breakpoints;
								update_option('gspb_global_settings', $global_settings);
							}
						?>
							<form method="POST" class="greenshift_form">
								<?php wp_nonce_field('gspb_settings_page_action', 'gspb_settings_field'); ?>
								<table class="form-table">

									<tr>
										<td> <?php esc_html_e("Mobile", 'greenshift-animation-and-page-builder-blocks'); ?> </td>
										<td>
											<input name="mobile" type="text" value="<?php if (isset($global_settings['breakpoints']['mobile'])) {echo esc_attr($global_settings['breakpoints']['mobile']);}  ?>" placeholder="<?php echo (int)$gsbp_breakpoints['mobile'];?>" />
										</td>
									</tr>
									<tr>
										<td> <?php esc_html_e("Tablet", 'greenshift-animation-and-page-builder-blocks'); ?> </td>
										<td>
											<input name="tablet" type="text" value="<?php if (isset($global_settings['breakpoints']['tablet'])) {
												echo esc_attr($global_settings['breakpoints']['tablet']);
											} ?>" placeholder="<?php echo (int)$gsbp_breakpoints['tablet'];?>" />
										</td>
									</tr>
									<tr>
										<td> <?php esc_html_e("Desktop", 'greenshift-animation-and-page-builder-blocks'); ?> </td>
										<td>
											<input name="desktop" type="text" value="<?php if (isset($global_settings['breakpoints']['desktop'])) {
												echo esc_attr($global_settings['breakpoints']['desktop']);
											} ?>" placeholder="<?php echo (int)$gsbp_breakpoints['desktop'];?>" />
										</td>
									</tr>
									<tr>
										<td> <?php esc_html_e("Default Row Content Width", 'greenshift-animation-and-page-builder-blocks'); ?> </td>
										<td>
											<input name="row" type="text" value="<?php if (isset($global_settings['breakpoints']['row'])) {
												echo esc_attr($global_settings['breakpoints']['row']);
											} ?>" placeholder="<?php echo apply_filters('gspb_default_row_width_px', 1200);?>" />
										</td>
									</tr>
								</table>
								<input type="submit" name="gspb_save_settings" value="Save" class="button button-primary button-large">
							</form>
						<?php
							break;
						case 'scripts':
							wp_enqueue_style('gsadminsettings');
							wp_enqueue_script('gsadminsettings');
							$global_settings = get_option('gspb_global_settings');
							if (isset($_POST['gspb_save_settings'])) { // Delay script saving
								if (!wp_verify_nonce($_POST['gspb_settings_field'], 'gspb_settings_page_action')) {
									esc_html_e("Sorry, your nonce did not verify.", 'greenshift-animation-and-page-builder-blocks');
									return;
								}

								$is_dealyjson = isset($_POST['delay_js_on']) && $_POST['delay_js_on'] == "on" ? 1 : 0;
								$jsdelay = array(
									"delay_js_on" => $is_dealyjson,
									"delay_js_page_on" =>  sanitize_text_field($_POST['delay_js_page_on']),
									"delay_js_page_list" =>  sanitize_text_field($_POST['delay_js_page_list']),
								);
								$global_settings['jsdelay'] = $jsdelay;

								update_option('gspb_global_settings', $global_settings);
							}
							//Form for delay script saving
							$delay_js_on = !empty($global_settings['jsdelay']['delay_js_on']) ? $global_settings['jsdelay']['delay_js_on'] : '';
							$delay_js_page_on = !empty($global_settings['jsdelay']['delay_js_page_on']) ? $global_settings['jsdelay']['delay_js_page_on'] : '';
							$delay_js_page_list = !empty($global_settings['jsdelay']['delay_js_page_list']) ? $global_settings['jsdelay']['delay_js_page_list'] : '';

							$show_page_option = $delay_js_on && ($delay_js_page_on == "includefor" || $delay_js_page_on  == "excludefor") ? true : false;

						?>
							<div class="gspb_settings_form">
								<form method="POST">
									<h2><?php esc_html_e("Javascript Files Delay", 'greenshift-animation-and-page-builder-blocks'); ?></h2>
									<div class="greenshift_form">
										<div><?php esc_html_e("Attention! This is experimental feature", "greenshift-animation-and-page-builder-blocks"); ?></div>
										<?php wp_nonce_field('gspb_settings_page_action', 'gspb_settings_field'); ?>
										<table class="form-table">
											<tr>
												<td colspan="2">
													<input type="checkbox" name="delay_js_on" id="delay_js_on" <?php echo $delay_js_on == true ? 'checked' : ''; ?> />
													<label for="delay_js_on"><?php esc_html_e("Enable script delay for Greenshift's scripts", 'greenshift-animation-and-page-builder-blocks'); ?></label>
												</td>
											</tr>
											<tr class="delay_js_optionsrow" <?php echo $delay_js_on == true ? 'style="display: table-row;"' : ''; ?>>
												<th> <label for="css_system"><?php esc_html_e("Javascript delay options", 'greenshift-animation-and-page-builder-blocks'); ?></label> </th>
												<td>
													<select id="delay_js_page_on" name="delay_js_page_on">
														<option value="all" <?php selected($delay_js_page_on, 'all'); ?>><?php esc_html_e("Enable on whole site", 'greenshift-animation-and-page-builder-blocks'); ?> </option>
														<option value="includefor" <?php selected($delay_js_page_on, 'includefor'); ?>> <?php esc_html_e("Enable only on selected pages", 'greenshift-animation-and-page-builder-blocks'); ?> </option>
														<option value="excludefor" <?php selected($delay_js_page_on, 'excludefor'); ?>> <?php esc_html_e("Enable on whole site except selected pages", 'greenshift-animation-and-page-builder-blocks'); ?> </option>
													</select>
												</td>
											</tr>
											<tr class="delay_js_pagerow" <?php echo $show_page_option == true ? 'style="display: table-row;"' : ''; ?>>
												<th>
													<label for="delay_js_page_list"><?php esc_html_e("Page Urls (one per line).", 'greenshift-animation-and-page-builder-blocks'); ?></label>
												</th>
												<td>
													<textarea style="width:100%; min-height:100px;" id="delay_js_page_list" name="delay_js_page_list"><?php echo esc_html($delay_js_page_list); ?></textarea>
													<div style="margin-bottom:15px"><?php esc_html_e("Specify URLs of pages (one per line).", 'greenshift-animation-and-page-builder-blocks'); ?></div>
												</td>
											</tr>
										</table>


										<input type="submit" name="gspb_save_settings" value="<?php esc_html_e("Save settings"); ?>" class="button button-primary button-large javascript_delay_submit">
									</div>
								</form>
							</div>
						<?php
							//End Form for delay script saving
							break;

						case 'keys':

							if (isset($_POST['gspb_save_settings'])) { // Delay script saving
								if (!wp_verify_nonce($_POST['gspb_settings_field'], 'gspb_settings_page_action')) {
									esc_html_e("Sorry, your nonce did not verify.", 'greenshift-animation-and-page-builder-blocks');
									return;
								}
								$sanitised = array();

								if (isset($_POST['googleapi']) || isset($_POST['openaiapi']) || isset($_POST['openaiapimodel'])) {
									$global_settings = get_option('gspb_global_settings');
									if (isset($_POST['googleapi'])) {
										$sanitised['googleapi'] = sanitize_text_field($_POST['googleapi']);
									}
									if (isset($_POST['openaiapi'])) {
										$sanitised['openaiapi'] = sanitize_text_field($_POST['openaiapi']);
									}
									if (isset($_POST['openaiapimodel'])) {
										$sanitised['openaiapimodel'] = sanitize_text_field($_POST['openaiapimodel']);
									}
									$newargs = wp_parse_args($sanitised, $global_settings);
									update_option('gspb_global_settings', $newargs);
								}
							}
							$global_settings = get_option('gspb_global_settings');
							//Form for delay script saving
							$googleapi = !empty($global_settings['googleapi']) ? $global_settings['googleapi'] : '';
							$openaiapi = !empty($global_settings['openaiapi']) ? $global_settings['openaiapi'] : '';
							$openaiapimodel = !empty($global_settings['openaiapimodel']) ? $global_settings['openaiapimodel'] : '';

							?>
							<div class="gspb_settings_form">
								<form method="POST">
									<h2><?php esc_html_e("Google Map API", 'greenshift-animation-and-page-builder-blocks'); ?></h2>
									<p class="mb30"><?php esc_html_e("Add key if you plan to use Google Map block", 'greenshift-animation-and-page-builder-blocks'); ?></p>
									<div class="greenshift_form">
										<?php wp_nonce_field('gspb_settings_page_action', 'gspb_settings_field'); ?>
										<table class="form-table">
											<tbody>
												<tr class="googleapikey">
													<th>
														<label for="googleapi"><?php esc_html_e("Google API Key", 'greenshift-animation-and-page-builder-blocks'); ?></label>
													</th>
													<td>
														<textarea style="width:100%; min-height:50px;border-color:#ddd" id="googleapi" name="googleapi"><?php echo esc_html($googleapi); ?></textarea>
														<div style="margin-bottom:15px"><a href="https://developers.google.com/maps/documentation/javascript/get-api-key"><?php esc_html_e("Get an API Key", 'greenshift-animation-and-page-builder-blocks'); ?></a></div>
													</td>
												</tr>
												<tr class="openaiapikey">
													<th>
														<label for="openaiapi"><?php esc_html_e("Open AI API Key", 'greenshift-animation-and-page-builder-blocks'); ?></label>
													</th>
													<td>
														<textarea style="width:100%; min-height:50px;border-color:#ddd" id="openaiapi" name="openaiapi"><?php echo esc_html($openaiapi); ?></textarea>
														<div style="margin-bottom:15px"><a href="https://platform.openai.com/account/api-keys"><?php esc_html_e("Get an API Key", 'greenshift-animation-and-page-builder-blocks'); ?></a></div>
													</td>
												</tr>
												<tr class="openaiapimodel">
													<th>
														<label for="openaiapimodel"><?php esc_html_e("Open AI Model", 'greenshift-animation-and-page-builder-blocks'); ?></label>
													</th>
													<td>
														<select name="openaiapimodel">
															<option value="gpt-3.5-turbo" <?php selected($openaiapimodel, 'gpt-3.5-turbo'); ?>><?php esc_html_e("gpt-3.5-turbo", 'greenshift-animation-and-page-builder-blocks'); ?> </option>
															<option value="gpt-4" <?php selected($openaiapimodel, 'gpt-4'); ?>> <?php esc_html_e("gpt-4", 'greenshift-animation-and-page-builder-blocks'); ?> </option>
															<option value="gpt-4-32k" <?php selected($openaiapimodel, 'gpt-4-32k'); ?>> <?php esc_html_e("gpt-4-32k", 'greenshift-animation-and-page-builder-blocks'); ?> </option>
														</select>
													</td>
												</tr>
											</tbody>
										</table>


										<input type="submit" name="gspb_save_settings" value="<?php esc_html_e("Save settings"); ?>" class="button button-primary button-large">
									</div>
								</form>
							</div>
						<?php
							//End Form for delay script saving
							break;
						default:
							wp_enqueue_style('gsadminsettings');
							wp_enqueue_script('gsadminsettings');
							if (isset($_POST['gspb_save_settings_general']) && isset($_POST['gspb_settings_field']) && wp_verify_nonce($_POST['gspb_settings_field'], 'gspb_settings_page_action')) { // local font saving
								$this->gspb_save_general_form($_POST, $_FILES);
							}

						?>
							<h2><?php esc_html_e("General Settings", 'greenshift-animation-and-page-builder-blocks'); ?></h2>
							<p class="mb30"><?php esc_html_e("You can assign global presets and other settings in Post edit area when you click on G button in header toolbar", 'greenshift-animation-and-page-builder-blocks'); ?></p>
							<h2><?php esc_html_e("Local Font Loader", 'greenshift-animation-and-page-builder-blocks'); ?></h2>
							<p class="mb30"><?php esc_html_e("Attention! Local font is global option and it can reduce performance in some cases, please, check", 'greenshift-animation-and-page-builder-blocks'); ?> <a href="https://greenshiftwp.com/how-to-use-local-fonts-in-greenshift-for-gdpr/" target="_blank"><?php esc_html_e("Documentation", 'greenshift-animation-and-page-builder-blocks'); ?></a></p>
							<?php
							$allowed_font_ext = $this->allowed_font_ext;
							require_once GREENSHIFT_DIR_PATH . 'templates/admin/settings_general_form.php'; ?>
							<h2><?php esc_html_e("Footer and Head Hooks", 'greenshift-animation-and-page-builder-blocks'); ?></h2>
							<div class="greenshift_form">
								<?php
								$theme_settings = get_option('greenshift_theme_options');
								$custom_code_in_head = !empty($theme_settings['custom_code_in_head']) ? wp_unslash($theme_settings['custom_code_in_head']) : '';
								$custom_code_before_closed_body = !empty($theme_settings['custom_code_before_closed_body']) ? wp_unslash($theme_settings['custom_code_before_closed_body']) : '';
								?>
								<form method="POST">
									<?php wp_nonce_field('gspb_settings_page_action', 'gspb_settings_field'); ?>
									<table class="form-table">
										<tr>
											<th> <label for="custom_code_in_head"><?php esc_html_e("Custom code in head section", 'greenshift'); ?></label> </th>
											<td>
												<textarea name="custom_code_in_head" id="" cols="30" rows="10" style="width: 100%"><?php echo $custom_code_in_head ?></textarea>
											</td>
										</tr>
										<tr>
											<th> <label for="custom_code_before_closed_body"><?php esc_html_e("Custom code before closed Body", 'greenshift'); ?></label> </th>
											<td>
												<textarea name="custom_code_before_closed_body" id="" cols="30" rows="10" style="width: 100%"><?php echo $custom_code_before_closed_body ?></textarea>
											</td>
										</tr>
									</table>
									<input type="submit" name="gspb_save_settings_general" value="<?php esc_html_e('Save', 'greenshift-animation-and-page-builder-blocks')?>" class="button button-primary button-large">
								</form>
							</div>
					<?php
							break;
					endswitch;
					?>
				</div>
			</div>
		<?php
		}

		// settings fonts
		public function gspb_settings_add_font()
		{
			$i = $_POST['i'];
			$allowed_font_ext = $this->allowed_font_ext;
			ob_start();
			require_once GREENSHIFT_DIR_PATH . 'templates/admin/settings_general_font_item.php';
			$html = ob_get_contents();
			ob_get_clean();
			wp_send_json(['html' => $html]);
		}

		public function gspb_save_general_form($data, $files)
		{
			$global_settings = get_option('gspb_global_settings');

			$fonts_urls = $this->gspb_save_files($files);
			$arr = [];
			for ($i = 0; (int)$data['fonts_count'] > $i; $i++) {
				//$item_arr = ['label' => sanitize_text_field($data['font_specific_style_name'][$i])];
				foreach ($this->allowed_font_ext as $ext) {
					$item_arr[$ext] = !empty($fonts_urls[$i][$ext]) ? $fonts_urls[$i][$ext] : sanitize_text_field($data[$ext][$i]);
				}
				$arr[sanitize_text_field($data['font_family_name'][$i])] = $item_arr;
			}
			$new_localfont = json_encode($arr);
			$global_settings['localfont'] = $new_localfont;

			$localfontcss = '';
			if (!empty($arr)) {
				foreach ($arr as $i => $value) {
					$localfontcss .= '@font-face {';
					$localfontcss .= 'font-family: "' . $i . '";';
					$localfontcss .= 'src: ';
					if (!empty($value['woff2'])) {
						$localfontcss .= 'url(' . $value["woff2"] . ') format("woff2"), ';
					}
					if (!empty($value['woff'])) {
						$localfontcss .= 'url(' . $value["woff"] . ') format("woff"), ';
					}
					if (!empty($value['ttf'])) {
						$localfontcss .= 'url(' . $value["ttf"] . ') format("truetype"), ';
					}
					if (!empty($value['tiff'])) {
						$localfontcss .= 'url(' . $value["tiff"] . ') format("tiff"), ';
					}
					$localfontcss .= ';';
					$localfontcss .= 'font-display: swap;}';
				}
				$localfontcss = str_replace(', ;', ';', $localfontcss);
				$global_settings['localfontcss'] = $localfontcss;

				$upload_dir = wp_upload_dir();
				require_once ABSPATH . 'wp-admin/includes/file.php';
				global $wp_filesystem;
				$dir = trailingslashit($upload_dir['basedir']) . 'GreenShift/'; // Set storage directory path

				WP_Filesystem(); // WP file system

				if (!$wp_filesystem->is_dir($dir)) {
					$wp_filesystem->mkdir($dir);
				}

				$gspb_json_filename = 'settings_backup.json';
				$gspb_backup_data = json_encode( $global_settings, JSON_PRETTY_PRINT );
		
				if (!$wp_filesystem->put_contents($dir . $gspb_json_filename, $gspb_backup_data)) {
					throw new Exception(__('JSON is not saved due the permission!!!', 'greenshift-animation-and-page-builder-blocks'));
				}
			}
			update_option('gspb_global_settings', $global_settings);

			$theme_settings = get_option('greenshift_theme_options');
			if(!empty($data['custom_code_in_head'])) {
				$theme_settings['custom_code_in_head'] = wp_kses(wp_unslash($data['custom_code_in_head']), [
					'meta' => [
						'charset' => [],
						'content' => [],
						'http-equiv' => [],
						'name' => [],
						'property' => []
					],
					'style' => [
						'media' => [],
						'type' => []
					],
					'script' => [
						'async' => [],
						'charset' => [],
						'defer' => [],
						'src' => [],
						'type' => []
					],
					'link' => [
						'href' => [],
						'rel' => [],
						'type' => []
					]
				]);
			}
			if(!empty($data['custom_code_before_closed_body'])){
				$theme_settings['custom_code_before_closed_body'] = wp_kses(wp_unslash($data['custom_code_before_closed_body']), [
					'meta' => [
						'charset' => [],
						'content' => [],
						'http-equiv' => [],
						'name' => [],
						'property' => []
					],
					'style' => [
						'media' => [],
						'type' => []
					],
					'script' => [
						'async' => [],
						'charset' => [],
						'defer' => [],
						'src' => [],
						'type' => []
					],
					'link' => [
						'href' => [],
						'rel' => [],
						'type' => []
					]
				]);
			}
			if(!empty($data['custom_code_in_head']) || !empty($data['custom_code_before_closed_body'])){
				update_option('greenshift_theme_options', $theme_settings);
			} 
		}

		public function gspb_save_files($files)
		{
			$result = [];
			$upload = wp_upload_dir();
			$upload_dir = $upload['basedir'] . '/GreenShift/fonts';
			$upload_url = $upload['baseurl'] . '/GreenShift/fonts';

			foreach (array_keys($files) as $filename) {
				foreach ($files[$filename]["error"] as $key => $error) {
					if ($error == UPLOAD_ERR_OK) {
						$tmp_name = $files[$filename]["tmp_name"][$key];
						$name = basename($files[$filename]["name"][$key]);
						$ext = pathinfo($name, PATHINFO_EXTENSION);
						$font_dir = $upload_dir . '/font_' . ($key + 1) . '/' . $ext;

						$this->gspb_rm_rec($font_dir); //clean up dir before download

						if (!wp_mkdir_p($font_dir)) {
							return false;
						}

						if (move_uploaded_file($tmp_name, "$font_dir/$name")) {
							$result[$key][$ext] = $upload_url . '/font_' . ($key + 1) . '/' . $ext . '/' . $name;
						}
					}
				}
			}

			return $result;
		}

		public function gspb_rm_rec($path)
		{
			if (is_file($path)) return unlink($path);
			if (is_dir($path)) {
				foreach (scandir($path) as $p) if (($p != '.') && ($p != '..'))
					$this->gspb_rm_rec($path . '/' . $p);
				return rmdir($path);
			}
			return false;
		}

		//Function to display Reusable section in menu
		function gspb_template_menu_display($type, $args)
		{
			if ('wp_block' !== $type) {
				return;
			}
			$args->show_in_menu = true;
			$args->_builtin = false;
			$args->labels->name = esc_html__('Block template', 'greenshift-animation-and-page-builder-blocks');
			$args->labels->menu_name = esc_html__('Reusable templates', 'greenshift-animation-and-page-builder-blocks');
			$args->menu_icon = 'dashicons-screenoptions';
			$args->menu_position = 58;
		}

		//Columns in Reusable section
		function gspb_template_screen_add_column($columns)
		{
			$newcols = array(
				'cb' => '<input type="checkbox" />',
				'title' => esc_html__('Block title', 'greenshift-animation-and-page-builder-blocks'),
				'gs-reusable-preview' => esc_html__('Usage', 'greenshift-animation-and-page-builder-blocks'),
			);
			return apply_filters('greenshift_reusable_blocks_list', array_merge($columns, $newcols));
		}

		//Render function for Columns in Reusable Sections
		function gspb_template_screen_fill_column($column, $ID)
		{
			global $post;
			switch ($column) {

				case 'gs-reusable-preview':

					echo '<p><input type="text" style="width:350px" value="[wp_reusable_render id=\'' . $ID . '\']" readonly=""></p>';
					echo '<p>' . esc_html__('If you use template inside other dynamic ajax blocks', 'greenshift-animation-and-page-builder-blocks') . '<br><input type="text" style="width:350px" value="[wp_reusable_render inlinestyle=1 id=\'' . $ID . '\']" readonly="">';
					echo '<p>' . esc_html__('Shortcode for Ajax render:', 'greenshift-animation-and-page-builder-blocks') . '<br><input type="text" style="width:350px" value="[wp_reusable_render ajax=1 height=100px id=\'' . $ID . '\']" readonly="">';
					echo '<p>' . esc_html__('Hover trigger:', 'greenshift-animation-and-page-builder-blocks') . ' <code>gs-el-onhover load-block-' . $ID . '</code>';
					echo '<p>' . esc_html__('Click trigger:', 'greenshift-animation-and-page-builder-blocks') . ' <code>gs-el-onclick load-block-' . $ID . '</code>';
					echo '<p>' . esc_html__('On view trigger:', 'greenshift-animation-and-page-builder-blocks') . ' <code>gs-el-onview load-block-' . $ID . '</code>';
					break;

				default:
					break;
			}
		}

		//Render shortcode function
		function gspb_template_shortcode_function($atts)
		{
			extract(shortcode_atts(
				array(
					'id' => '',
					'ajax' => '',
					'height' => '',
					'inlinestyle' => ''
				),
				$atts
			));
			if (!isset($id) || empty($id)) {
				return '';
			}
			if (!is_numeric($id)) {
				$postget = get_page_by_path($id, OBJECT, array('wp_block'));
				if (is_object($postget)) {
					$id = $postget->ID;
				} else {
					return;
				}
			}
			if (!empty($ajax)) {
				wp_enqueue_style('wp-block-library');
				wp_enqueue_style('gspreloadercss');
				wp_enqueue_script('gselajaxloader');
				$scriptvars = array(
					'reusablenonce' => wp_create_nonce('gsreusable'),
					'ajax_url' => admin_url('admin-ajax.php', 'relative'),
				);
				wp_localize_script('gselajaxloader', 'gsreusablevars', $scriptvars);
				$content = '<div class="gs-ajax-load-block gs-ajax-load-block-' . (int)$id . '"></div>';

				$content_post = get_post($id);
				if (!is_object($content_post)) return false;
				$contentpost = $content_post->post_content;
				$style = '';
				if (has_blocks($contentpost)) {
					$blocks = parse_blocks($contentpost);
					$style .= '<style scoped>';
					$style .= gspb_get_inline_styles_blocks($blocks);
					$style .= '</style>';
				}
				if (!empty($height)) {
					$content = '<div style="min-height:' . esc_attr($height) . '">' . $content . $style . '</div>';
				} else {
					$content = '<div>' . $content . $style . '</div>';
				}
			} else {
				$content_post = get_post($id);
				if (!is_object($content_post)) return false;
				$content = $content_post->post_content;
				$style = '';
				if ($inlinestyle) {
					if (has_blocks($content)) {
						$blocks = parse_blocks($content);
						$style .= '<style scoped>';
						$style .= gspb_get_inline_styles_blocks($blocks);
						$style .= '</style>';
					}
				}
				$content = do_blocks($content);
				$content = do_shortcode($content);
				$content = preg_replace('%<p>&nbsp;\s*</p>%', '', $content);
				$content = preg_replace('/^(?:<br\s*\/?>\s*)+/', '', $content);
				$content = $content . $style;
			}
			return $content;
		}

		//Load reusable Ajax function
		function gspb_el_reusable_load()
		{
			check_ajax_referer('gsreusable', 'security');
			$post_id = intval($_POST['post_id']);
			$content_post = get_post($post_id);
			$content = $content_post->post_content;
			$content = apply_filters('the_content', $content);
			$content = str_replace('strokewidth', 'stroke-width', $content);
			$content = str_replace('strokedasharray', 'stroke-dasharray', $content);
			$content = str_replace('stopcolor', 'stop-color', $content);
			$content = str_replace('loading="lazy"', '', $content);
			if ($content) {
				wp_send_json_success($content);
			} else {
				wp_send_json_success('fail');
			}
			wp_die();
		}

		//Show gutenberg editor on reusable section even if Classic editor plugins enabled
		function gspb_template_gutenberg_post($use_block_editor, $post)
		{
			if (empty($post->ID)) return $use_block_editor;
			if ('wp_block' === get_post_type($post->ID)) return true;
			return $use_block_editor;
		}
		function gspb_template_gutenberg_post_type($use_block_editor, $post_type)
		{
			if ('wp_block' === $post_type) return true;
			return $use_block_editor;
		}
	}
}

add_filter('block_editor_settings_all', 'gspb_generate_anchor_headings', 10, 2);

function gspb_generate_anchor_headings($settings, $block_editor_context)
{
	$settings['generateAnchors'] = true;
	return $settings;
}

function gspb_get_inline_styles_blocks($blocks)
{
	$inlinestyle = '';
	foreach ($blocks as $block) {
		if (!empty($block['attrs']['inlineCssStyles'])) {
			$dynamic_style = $block['attrs']['inlineCssStyles'];
			$dynamic_style = gspb_get_final_css($dynamic_style);
			$dynamic_style = gspb_quick_minify_css($dynamic_style);
			$dynamic_style = htmlspecialchars_decode($dynamic_style);
			$inlinestyle .= $dynamic_style;
		}
		gspb_greenShift_block_script_assets('', $block);
		if (function_exists('greenShiftGsap_block_script_assets')) {
			greenShiftGsap_block_script_assets('', $block);
		}
		if (!empty($block['innerBlocks'])) {
			$blocks = $block['innerBlocks'];
			$inlinestyle .= gspb_get_inline_styles_blocks($blocks);
		}
	}
	return $inlinestyle;
}

//////////////////////////////////////////////////////////////////
// File Manager
//////////////////////////////////////////////////////////////////

if (!function_exists('greenshift_download_file_localy')) {
	function greenshift_download_file_localy($file_uri, $save_dir, $file_name, $file_ext = null, $check_type = '')
	{
		$file_path = trailingslashit($save_dir) . $file_name;
		if (file_exists($file_path)) {
			return $file_name;
		}
		$args = array(
			'timeout' => 30,
			'httpversion' => '1.1',
			'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.101 Safari/537.36',
			'sslverify'   => true,
		);

		$response = wp_remote_get($file_uri, $args);

		if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
			return false;
		}

		if ($file_ext === null) {
			$headers = wp_remote_retrieve_headers($response);
			if (empty($headers['content-type'])) return false;

			$types = array_search($headers['content-type'], wp_get_mime_types());

			if (!$types) return false;

			$exts = explode('|', $types);
			$file_ext = $exts[0];
			$file_name .= '.' . $file_ext;
		}

		$file_name = wp_unique_filename($save_dir, $file_name);

		if ($check_type) {
			$filetype = wp_check_filetype($file_name, null);
			if (substr($filetype['type'], 0, 5) != $check_type)
				return false;
		}

		$image_string = wp_remote_retrieve_body($response);
		if (!file_put_contents($file_path, $image_string))
			return false;

		return $file_name;
	}
}

if (!function_exists('greenshift_save_file_localy')) {
	function greenshift_save_file_localy($file_uri, $img_title = '', $check_type = '')
	{
		$newfilename = basename($file_uri);
		$ext = pathinfo(basename($file_uri), PATHINFO_EXTENSION);

		$ext = ($ext) ? $ext : null;

		if (empty($newfilename)) {
			$newfilename = preg_replace('/[^a-zA-Z0-9\-]/', '', $newfilename);
			$newfilename = strtolower($newfilename);
		}

		$uploads = wp_upload_dir();

		if ($newfilename = greenshift_download_file_localy($file_uri, $uploads['path'], $newfilename, $ext, $check_type)) {
			return $newfilename;
		} else {
			return false;
		}
	}
}

if (!function_exists('greenshift_replace_ext_images')) {
	function greenshift_replace_ext_images($content, $format = 'json')
	{
		$pattern = '#https?://[^/\s]+/\S+\.(jpg|png|gif|webp|svg|jpeg|json)#';
		if ($format == 'json') {
			$content = json_decode($content, true);
		}
		$result = preg_replace_callback($pattern, function ($match) {
			if (is_array($match)) {
				$url = $match[0];
				if (strpos($url, get_bloginfo('url')) !== 0) {
					$urlnew = greenshift_save_file_localy($url);
					$uploads = wp_upload_dir();
					$image = trailingslashit($uploads['url']) . $urlnew;
					return $image;
				} else {
					return $url;
				}
			}
		}, $content);
		if ($format == 'json') {
			$result = json_encode($result);
		}
		return $result;
	}
}

?>