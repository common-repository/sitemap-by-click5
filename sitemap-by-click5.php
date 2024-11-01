<?php
/**
* Plugin Name: Sitemap by click5
* Plugin URI: https://www.click5interactive.com/wordpress-sitemap-plugin/
* Description: Generate and customize HTML & XML sitemaps for your website.
* Version: 1.0.42
* Author: click5 Interactive
* Author URI: https://www.click5interactive.com/?utm_source=sitemap-plugin&utm_medium=plugin-list&utm_campaign=wp-plugins
* Text Domain: sitemap-by-click5
* Domain Path: /languages
**/

define('CLICK5_SITEMAP_VERSION', '1.0.42');
define('CLICK5_SITEMAP_DEV_MODE', true);
if (!function_exists('is_plugin_active')) {
  include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}
require('api.php');

function click5_check_nonce($nonce){
  return wp_verify_nonce($nonce, 'click5_sitemap_nonce');
}

add_filter( 'wp_sitemaps_enabled', '__return_false' );
add_action('admin_menu', 'click5_sitemap_create_menu');

add_action( 'init', 'click5_sitemap_set_global_var' );
function click5_sitemap_set_global_var()
{
  global $global_yoast_xml;
  global $global_aio_seo_xml;
  global $global_file_xml;
  $global_yoast_xml = false;
  $global_aio_seo_xml = false;
  $global_file_xml = false;
  $yoast = false;
  $aio_seo = false;
  if(function_exists('is_plugin_active')) {
    $yoast = is_plugin_active("wordpress-seo/wp-seo.php");
    if($yoast) {
      if( class_exists('WPSEO_Options') ) {
        $global_yoast_xml = WPSEO_Options::get('enable_xml_sitemap');
      }
    }
    $aio_seo = is_plugin_active("all-in-one-seo-pack/all_in_one_seo_pack.php");
    if($aio_seo) {
      $aio_seo_data = get_option("aioseo_options");
      if (function_exists('str_contains'))
      {
        if (str_contains($aio_seo_data, 'sitemap":{"general":{"enable":true')) { 
          $global_aio_seo_xml = true;
        }
        if (str_contains($aio_seo_data, 'sitemap":{"general":{"enable":false')) { 
          $global_aio_seo_xml = false;
        }
      }
      else
      {
        if (strpos($aio_seo_data, 'sitemap":{"general":{"enable":true') !== false) { 
          $global_aio_seo_xml = true;
        }
        if (strpos($aio_seo_data, 'sitemap":{"general":{"enable":false') !== false) { 
          $global_aio_seo_xml = false;
        }
      }
    }
  }
  if (function_exists('glob')){
    $files_xml = glob(ABSPATH . "*.xml");
    $detected_files = 0;
    foreach($files_xml as $xml_file) {
      $xml_data = file_get_contents($xml_file);
      if (function_exists('str_contains'))
      {
        if(!str_contains($xml_data, 'sitemap-by-click5') && str_contains($xml_file,"sitemap")) {
          $detected_files++;
        }
      }
      else
      {
        if(strpos($xml_data, 'sitemap-by-click5') === false && strpos($xml_file,"sitemap") !== false) {
          $detected_files++;
        }
      }
      
    }
    if($detected_files > 0) {
      $global_file_xml = true;
    }
  }

  global $global_list;
  $global_list = array();
  global $wpdb;
  $robot_exist = $wpdb->query( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . "options WHERE option_name ='%s' LIMIT 1", 'click5_sitemap_robots_exist' ));
  if(!$robot_exist){
    add_option('click5_sitemap_robots_exist', "1");
    if (!file_exists(ABSPATH.'/robots.txt'))
    {
      //click5_sitemap_generate_robots_txt();
    }
  }
 

  if(file_exists(ABSPATH.'/r.txt'))
  {
    unlink(ABSPATH.'/r.txt');
  }

  if(isset($_POST['update_txt'])){ 
 
    $nonce = sanitize_text_field($_POST['click5_sitemap_nonce']);  
  
    if(click5_check_nonce($nonce))
    {
      $robots_txt = ABSPATH.'/robots.txt';
      if(is_writable($robots_txt)) {
        if(isset($_POST['file_text'])){
          $input = sanitize_textarea_field($_POST['file_text']);
        }
        file_put_contents($robots_txt, $input);
      }
    }
  }
  
  if(isset($_POST['revert_backup_txt'])){ 
    $nonce = '';
    if(isset($_POST['click5_sitemap_nonce']))
      $nonce = sanitize_text_field($_POST['click5_sitemap_nonce']);
  
    if(click5_check_nonce($nonce))
    {
      update_option('click5_sitemap_seo_robots_txt', false);
      unlink(ABSPATH.'/robots.txt');
      $robots_backup_txt = ABSPATH.'/robots-click5-backup.txt';
      if(file_exists($robots_backup_txt)) {
        rename($robots_backup_txt,  ABSPATH.'/robots.txt');
      }
    }
  }
  
  if(isset($_POST['delete_backup_txt'])){
    
    $nonce = sanitize_text_field($_POST['click5_sitemap_nonce']);
  
    if(click5_check_nonce($nonce))
    {
      update_option('click5_sitemap_seo_robots_txt', false); 
      unlink(ABSPATH.'/robots.txt');
    }
  }
  
  if(isset($_POST['create_file_txt'])){ 
  
    $nonce = sanitize_text_field($_POST['create_file_txt']);
    if(click5_check_nonce($nonce))
    {
      update_option('click5_sitemap_seo_robots_txt', true);
      if (file_exists(ABSPATH.'/robots.txt') && !file_exists(ABSPATH.'/robots-click5-backup.txt')) {
        rename(ABSPATH.'/robots.txt', ABSPATH.'/robots-click5-backup.txt');
        
      }
      $content = '';
      if (boolval(esc_attr(get_option('click5_sitemap_seo_include_sitemap_xml')))) {
        $content = 'Sitemap: '.esc_url(site_url().'/'.basename('sitemap.xml')).PHP_EOL;
      }
      $content .= 'User-agent: *'.PHP_EOL;
      $blacklistSeoIDArray = json_decode(get_option('click5_sitemap_seo_blacklisted_array'));
      $blacklistSeoID = array_column($blacklistSeoIDArray ? $blacklistSeoIDArray : array(), 'ID');
  
      $robots_txt = ABSPATH.'/robots.txt';
  
      file_put_contents($robots_txt, $content);
  
      $robots_txt = ABSPATH.'/robots.txt';
      $robots_backup_txt = ABSPATH.'/robots-click5-backup.txt';
      if(file_exists($robots_backup_txt)) {
        $backup_content = "";
        $handle_backup = fopen($robots_backup_txt, "r");
        if ($handle_backup) {
          while (($line = fgets($handle_backup)) !== false) {
            $empty_line = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "#####", $line);       
            if(!empty($line) && "#####" != $empty_line) {
              if($line[0] != "#") {
                $single_line = "# " . $line;
              } else {
                $single_line = $line;
              }
              $backup_content .= $single_line;
            }  
          }
          fclose($handle_backup);
        }
        $robots_txt_data = file_get_contents($robots_txt);
        $robots_txt_data .= $backup_content;
        file_put_contents($robots_txt, preg_replace('/^[ \t]*[\r\n]+/m', '', $robots_txt_data));
      }
    }
  }

  global $backupExist;
  $robots_backup_txt_chcek = ABSPATH.'/robots-click5-backup.txt';
  if(file_exists($robots_backup_txt_chcek)) {
    $backupExist = true; 
  } else {
    $backupExist = false;
  }
  global $gTxtData;
  $string_lines = "";
  $robots_txt = ABSPATH.'/robots.txt';
  if(file_exists($robots_txt)) {
    $counter = 0;
    $string_file_txt = file_get_contents($robots_txt);
    $res = explode("\n", $string_file_txt);
    foreach($res as $res_item) {
      if($counter > 1 && !empty($res_item)) {
        if($counter == 2) {
          $string_lines .= $res_item;
        } else {
          $string_lines .= "\n" . $res_item;
        }
      }
      $counter = $counter + 1;
    }
    $gTxtData = $string_lines;
  }

  if(get_option("click5_sitemap_html_blog_sort_by") === false){
    update_option("click5_sitemap_html_blog_sort_by","alphabetically");
  }

  if(get_option("click5_sitemap_html_blog_order_by") === false){
    update_option("click5_sitemap_html_blog_order_by","ASC");
  }

  if(get_option("click5_sitemap_custom_sorting") === false){
    update_option("click5_sitemap_custom_sorting",1);
  }

  if(get_option("click5_sitemap_blacklisted_array") === false){
    add_option("click5_sitemap_blacklisted_array",'[]');
  }

  if(get_option("click5_sitemap_seo_blacklisted_array") === false){
    add_option("click5_sitemap_seo_blacklisted_array",'[]');
  }

  if(esc_attr(get_option("click5_sitemap_seo_cron")) === "1"){
    if (! wp_next_scheduled ( 'click5_sitemap_seo_cron' )) {
      wp_schedule_event(time(), 'daily', 'click5_sitemap_seo_cron');
    }
  }

}

add_action("click5_sitemap_seo_cron","click5_sitemap_regenerate_xml");
function click5_sitemap_regenerate_xml(){
  click5_sitemap_reset_sitemap_order_inline();
  click5_sitemap_generate_sitemap_XML_DoWork();
}

function click5_sitemap_create_menu() {

	//create new top-level menu
	add_menu_page('Sitemap Settings', 'Sitemap', 'administrator', __FILE__, 'click5_sitemap_settings_page' , 'dashicons-editor-ul' );

	//call register settings function
	add_action( 'admin_init', 'click5_sitemap_settings' );
}

function click5_sitemap_activation_redirect( $plugin ) {
  if( $plugin == plugin_basename( __FILE__ ) ) {
    $robots_txt = ABSPATH.'/robots.txt';
    $robots_backup_txt = ABSPATH.'/robots-click5-backup.txt';
    exit( wp_redirect( admin_url( 'admin.php?page=sitemap-by-click5%2Fsitemap-by-click5.php' ) ) );
  }
}
add_action( 'activated_plugin', 'click5_sitemap_activation_redirect' );

//left sidebar link
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'click5_sitemap_add_plugin_page_settings_link');
function click5_sitemap_add_plugin_page_settings_link( $links ) {
	$links[] = '<a href="' .
		admin_url( 'options-general.php?page=sitemap-by-click5%2Fsitemap-by-click5.php' ) .
		'">' . __('Settings') . '</a>';
	//$links[] = '<a target="_blank" rel="nofollow" href="https://www.click5interactive.com/wordpress-sitemap-plugin">' . __('About plugin') . '</a>';
	return $links;
}

add_filter( 'plugin_row_meta', 'click5_sitemap_plugin_meta', 10, 2 );
function click5_sitemap_plugin_meta( $links, $file ) { // add some links to plugin meta row
	if ( strpos( $file, 'sitemap-by-click5.php' ) !== false ) {
    //$links = array_merge( $links,  );

    array_splice( $links, 2, 0, array( '<a href="https://www.click5interactive.com/wordpress-sitemap-plugin/?utm_source=sitemap-plugin&utm_medium=plugin-list&utm_campaign=wp-plugins" target="_blank" rel="nofollow">About plugin</a>' ) );
	}
	return $links;
}

// Activation
function click5_sitemap_activation(){
    do_action( 'click5_sitemap_default_options' );
}
register_activation_hook( __FILE__, 'click5_sitemap_activation' );



function click5_sitemap_upgrade_completed( $upgrader_object, $options ) {
  $our_plugin = plugin_basename( __FILE__ );
  if( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
   foreach( $options['plugins'] as $plugin ) {
    if( $plugin == $our_plugin ) {

      if (file_exists(ABSPATH.'/robots.txt') && !file_exists(ABSPATH.'/robots-click5-backup.txt')) {
        //rename(ABSPATH.'/robots.txt', ABSPATH.'/robots-click5-backup.txt');
        $robots_txt = ABSPATH.'/robots.txt';
        copy($robots_txt,  ABSPATH.'/robots-click5-backup.txt');
      }
      
      if (file_exists(ABSPATH.'/robots-click5-backup.txt')) {
          update_option('click5_sitemap_seo_robots_backup', true);
      }

      //click5_sitemap_generate_robots_txt();

    }
   }
  }
}
add_action( 'upgrader_process_complete', 'click5_sitemap_upgrade_completed', 10, 2 );


function click5_sitemap_save_default_order_list() {
  $styleSett = esc_attr( get_option('click5_sitemap_display_style') );
  $_style = !empty($styleSett) ? $styleSett : 'group';

  $newOrder = (array)(json_decode(html_entity_decode(json_encode(click5_sitemap_get_order_list($_style), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE))));

  $orderItems = click5_sitemap_order_list_setup_order_values($newOrder);

  $data_string = json_encode($orderItems);
  $byte_size = mb_strlen($data_string, '8bit');
  update_option('click5_sitemap_order_list2', '');
  update_option('click5_sitemap_order_list3', '');
  update_option('click5_sitemap_order_list4', '');
  update_option('click5_sitemap_order_list', json_encode($orderItems));
}

function click5_sitemap_forceDefaultSettings() {
  $robots_txt = ABSPATH.'/robots.txt';
  if (file_exists($robots_txt)) {
    copy($robots_txt,  ABSPATH.'/robots-click5-backup.txt');
  }
  $post_types = click5_sitemap_get_post_types();

  update_option('click5_sitemap_first_load', true);
  
  //update_option('click5_sitemap_seo_xml_categories', false);
  //update_option('click5_sitemap_seo_xml_tags', false);
  //update_option('click5_sitemap_seo_xml_images', false);
  //update_option('click5_sitemap_seo_xml_docs', false);
 // update_option('click5_sitemap_seo_xml_videos', false);
 // update_option('click5_sitemap_seo_xml_authors', false);

  $users = get_users();

  if (file_exists(ABSPATH.'/robots-click5-backup.txt')) {
    update_option('click5_sitemap_seo_robots_backup', true);
  }

  /*
  update_option('click5_sitemap_seo_sitemap_xml', false);
  update_option('click5_sitemap_seo_robots_txt', false);
  update_option('click5_sitemap_seo_include_sitemap_xml', false);
  update_option('click5_sitemap_seo_sitemap_type', 'splitted');
  update_option('click5_sitemap_seo_auto', true);
  update_option('click5_sitemap_html_pagination_items_per_page', 100);
  update_option('click5_sitemap_display_columns', 1);
*/
  //click5_sitemap_generate_sitemap_XML_DoWork();
  //click5_sitemap_generate_robots_txt();

  update_option('click5_sitemap_seo_auto', true);
  update_option('click5_sitemap_seo_cron', false);
  //save default order list
  click5_sitemap_save_default_order_list();
}

add_action( 'click5_sitemap_default_options', 'click5_sitemap_forceDefaultSettings' );


function click5_sitemap_settings() {  
  
  //force auto-update - only once on version .30
  if(get_option('click5_sitemap_first_load'))
  {
    global $wpdb;

    $click5_option_value = "a:1:{i:1;s:39:\"sitemap-by-click5/sitemap-by-click5.php\";}";

    $click5_select_wp_option = $wpdb->get_results( "SELECT option_value FROM {$wpdb->prefix}options WHERE option_name='auto_update_plugins'") ; 


    if (!empty($click5_select_wp_option))
    {
      if(strpos($click5_select_wp_option[0]->option_value, "sitemap-by-click5.php")=== false)
      {      
            if($click5_select_wp_option[0]->option_value == "a:0:{}" or $click5_select_wp_option[0]->option_value == " ")
            {
              $res = $wpdb->delete( 
                'wp_options',
                array( 'option_name' => 'auto_update_plugins' )
              );
          
              $wpdb->insert( 
                'wp_options', 
                array( 
                  'option_value' => $click5_option_value,
                  'option_name' => 'auto_update_plugins'
                )
              );
            }
            else
            {
              $click5_select_wp_option1 =  $click5_select_wp_option[0]->option_value;
              $click5_select_wp_option1 = str_replace(';}', ';i:91;s:39:"sitemap-by-click5/sitemap-by-click5.php";}', $click5_select_wp_option1);
              $plugins_count = substr_count($click5_select_wp_option1, ';')/2;
              $plugins_count_before_sitemap = $plugins_count - 1;
              $click5_select_wp_option1 = str_replace('a:'.$plugins_count_before_sitemap,'a:'.$plugins_count , $click5_select_wp_option1);
              $wpdb->update( 
                'wp_options', 
                array( 
                  'option_value' => $click5_select_wp_option1
                ),
                array( 'option_name' => 'auto_update_plugins' )
              );
              
            }
      }
    }
    else
    {
      $res = $wpdb->delete( 
        'wp_options',
        array( 'option_name' => 'auto_update_plugins' )
      );
  
      $wpdb->insert( 
        'wp_options', 
        array( 
          'option_value' => $click5_option_value,
          'option_name' => 'auto_update_plugins'
        )
      );
    }

    update_option('click5_sitemap_first_load', false);
    
   
  }

  //register our settings
	register_setting( 'click5_sitemap', 'click5_sitemap_post_template_HTML' );
  register_setting( 'click5_sitemap', 'click5_sitemap_is_multiple_time' );
  register_setting( 'click5_sitemap', 'click5_sitemap_first_load' );
  


  $post_types = click5_sitemap_get_post_types();
  foreach($post_types as $single_type) {
    $single_type = get_post_type_object($single_type);
    $option_name = 'click5_sitemap_display_'.$single_type->name;
    $option_names = array('click5_sitemap_use_custom_name_'.$single_type->name, 'click5_sitemap_custom_name_text_'.$single_type->name);

    register_setting('click5_sitemap', $option_name);
    register_setting('click5_sitemap', $option_names[0]);
    register_setting('click5_sitemap', $option_names[1]);
    //seo
    register_setting('click5_sitemap_seo', 'click5_sitemap_seo_post_type_'.$single_type->name);
  }

  register_setting('click5_sitemap_seo', 'click5_sitemap_seo_xml_categories');
  register_setting('click5_sitemap_seo', 'click5_sitemap_seo_xml_tags');
  register_setting('click5_sitemap_seo', 'click5_sitemap_seo_xml_authors');

    register_setting('click5_sitemap_seo', 'click5_sitemap_seo_xml_images');
    register_setting('click5_sitemap_seo', 'click5_sitemap_seo_xml_docs');
    register_setting('click5_sitemap_seo', 'click5_sitemap_seo_xml_videos');

  $cpt_args = array('public'   => true, '_builtin' => false);
  $cpt_output = 'names';
  $cpt_operator = 'and';
  $cpt_types = get_taxonomies( $cpt_args, $cpt_output, $cpt_operator ); 
  foreach ( $cpt_types  as $cpt_type ) { 
    register_setting('click5_sitemap_seo', 'click5_sitemap_seo_xml_'.$cpt_type.'_tax');
  }

  $getCustomCat = click5_sitemap_getCustomCategoriesCustomUrlsHTML();                       
  foreach($getCustomCat as $item) {
    register_setting('click5_sitemap_seo', 'click5_sitemap_seo_xml_'.str_replace(' ', '_', $item).'_custom');
  }
  
  register_setting('click5_sitemap', 'click5_sitemap_display_style');

  register_setting('click5_sitemap', 'click5_sitemap_url_target_blanc');

  register_setting('click5_sitemap_blacklist', 'click5_sitemap_blacklisted_array');
  register_setting('click5_sitemap_blacklist_seo', 'click5_sitemap_seo_blacklisted_array');


  //seo

  register_setting('click5_sitemap_seo', 'click5_sitemap_seo_sitemap_xml');

  register_setting('click5_sitemap_seo', 'click5_sitemap_seo_robots_txt');

  register_setting('click5_sitemap_seo', 'click5_sitemap_seo_include_sitemap_xml');

  register_setting('click5_sitemap_seo', 'click5_sitemap_seo_sitemap_type');
  register_setting('click5_sitemap_seo', 'click5_sitemap_seo_auto');
  register_setting('click5_sitemap_seo', 'click5_sitemap_seo_cron');


  //order list
  register_setting('click5_sitemap_html', 'click5_sitemap_order_list');
  register_setting('click5_sitemap_html', 'click5_sitemap_order_list2');
  update_option('click5_sitemap_order_list2', ' ');
  register_setting('click5_sitemap_html', 'click5_sitemap_order_list3');
  update_option('click5_sitemap_order_list3', ' ');
  register_setting('click5_sitemap_html', 'click5_sitemap_order_list4');
  update_option('click5_sitemap_order_list4', ' ');
  register_setting('click5_sitemap_html', 'click5_sitemap_order_list_nested');
  register_setting('click5_sitemap_html', 'click5_sitemap_html_pagination_items_per_page');



  register_setting('click5_sitemap_urls', 'click5_sitemap_urls_list');

  //for authentication purposes
  $current_user = wp_get_current_user();
  register_setting('click5_sitemap_authentication', 'click5_sitemap_authentication_token_'.wp_get_current_user()->user_login);
}

function click5_sitemap_load_plugin_textdomain() {
  load_plugin_textdomain( 'sitemap-by-click5', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'click5_sitemap_load_plugin_textdomain' );


function click5_sitemap_settings_page() {
?>
<div class="wrap">
  <h1 class="click5_sitemap_heading"><?php _e('click5 Sitemap Settings', 'sitemap-by-click5'); ?>&nbsp;<span class="version">v<?php echo CLICK5_SITEMAP_VERSION; ?></span></h1>
</div>
<?php if( isset($_GET["settings-updated"]) ) { ?>
<div id="message" class="updated">
<p><strong><?php _e("Settings have been saved."); ?></strong></p>
</div>
<?php } ?>
<?php
            if( isset( $_GET[ 'tab' ] ) ) {
                $active_tab = sanitize_key($_GET[ 'tab' ]);
            } else {
              $active_tab = 'html';
            }
?>

<h2 class="nav-tab-wrapper">
    <a href="?page=sitemap-by-click5/sitemap-by-click5.php&tab=html" class="nav-tab <?php echo $active_tab == 'html' ? 'nav-tab-active' : ''; ?>"><?php _e('HTML Sitemap', 'sitemap-by-click5'); ?></a>
    <a href="?page=sitemap-by-click5/sitemap-by-click5.php&tab=seo" class="nav-tab <?php echo $active_tab == 'seo' ? 'nav-tab-active' : ''; ?>"><?php _e('XML Sitemap', 'sitemap-by-click5'); ?></a>
    <a href="?page=sitemap-by-click5/sitemap-by-click5.php&tab=urls" class="nav-tab <?php echo $active_tab == 'urls' ? 'nav-tab-active' : ''; ?>"><?php _e('Custom URLs', 'sitemap-by-click5'); ?></a>
</h2>
<div class="wrap click5_sitemap_wrapper_content_settings">
<div class="content-left" style="margin-right: 20px">
<?php
      $verification_token = md5(uniqid(rand(), true));
      $cur_user_id = wp_get_current_user()->user_login;
      update_option('click5_sitemap_authentication_token_'.$cur_user_id, $verification_token);
?>
<input type="hidden" id="verification_token" value="<?php echo esc_attr($verification_token); ?>" />
<input type="hidden" id="user_identificator" value="<?php echo esc_attr($cur_user_id); ?>" />
<input type="hidden" id="escape_id" value=""/>

<form method="post" action="options.php" id="myForm" >
    <?php if ($active_tab == 'html'): ?>
    <?php settings_fields( 'click5_sitemap' ); ?>
    <?php do_settings_sections( 'click5_sitemap' ); ?>
    <div id="poststuff">
      <div id="post-body-content">
      <div class="error"<?php  global $global_yoast_xml; if(!$global_yoast_xml) {echo "style='display:none'";} else { echo 'style="margin-top: 0px!important;"';} ?>>
          <p>
            <b><?php _e('Warning: ', 'sitemap-by-click5'); ?></b>
            <?php _e('We have detected an XML Sitemap generated by a different plugin. We recommend to deactivate the XML sitemap generated by the YOAST SEO plugin in order to prevent any conflicts or SEO errors on your website. These may be reported in Google Search Console and other tools.', 'sitemap-by-click5'); ?>
          </p>
          <button type="button" onclick="redirectToYoast()" style="margin-bottom: 8px;" class="button button-primary">Disable Now</button>
        </div>
        <div class="error" <?php  global $global_aio_seo_xml; if(!$global_aio_seo_xml) {echo "style='display:none'";} else { echo 'style="margin-top: 0px!important;"';} ?>>
          <p>
            <b><?php _e('Warning: ', 'sitemap-by-click5'); ?></b>
            <?php _e('We have detected an XML Sitemap generated by a different plugin. We recommend to deactivate the XML sitemap generated by the All In One SEO plugin in order to prevent any conflicts or SEO errors on your website. These may be reported in Google Search Console and other tools.', 'sitemap-by-click5'); ?>
          </p>
          <button type="button" onclick="redirectToAioSeo()" style="margin-bottom: 8px;" class="button button-primary">Disable Now</button>
        </div>
        <div class="error" <?php  global $global_file_xml; if(!$global_file_xml) {echo "style='display:none'";} else { echo 'style="margin-top: 0px!important;"';} ?>>
          <p>
            <b><?php _e('Warning: ', 'sitemap-by-click5'); ?></b>
            <?php _e('We have detected an XML Sitemap generated by a different plugin. We recommend to deactivate the other sitemap in order to prevent any conflicts or SEO errors on your website. These may be reported in Google Search Console and other tools.', 'sitemap-by-click5'); ?>
          </p>
        </div>
        <div class="postbox">
          <h3 class="hndle"><span><?php _e('HTML Sitemap Shortcode', 'sitemap-by-click5'); ?></span></h3>
          <div class="inside">
            <p><?php _e('In order to display HTML Sitemap on your website, place this shortcode on any page or post.', 'sitemap-by-click5'); ?></p>
            <blockquote id="copy-me" style="margin-left: 0;"><strong style="font-family: monospace; font-weight: 300; font-size: 1.5em;">[click5_sitemap]</strong></blockquote>
          </div>
        </div>
        <div class="postbox" id="enableSitemap">
          <h3 class="hndle"><span><?php _e('General HTML Sitemap Settings', 'sitemap-by-click5'); ?></span></h3>
          <div class="inside setting_block">
            <p style="margin-bottom: 0px;"><?php _e('Select post types which you want to include in your HTML Sitemap:', 'sitemap-by-click5'); ?></p>
            <table class="form-table">
              <tbody>
              <?php
                $post_types = click5_sitemap_get_post_types();
                foreach($post_types as $single_type) {
                  $single_type = get_post_type_object($single_type);
              ?>
              <tr class="click5_sitemap_post_type_item">
                <td>
                    <?php $option_name = 'click5_sitemap_display_'.$single_type->name; ?>
                    <label for="<?php echo esc_attr($option_name); ?>">
                    <input type="checkbox" name="<?php echo esc_attr($option_name); ?>" id="<?php echo esc_attr($option_name); ?>" value="1" <?php echo (intval(esc_attr( get_option($option_name) )) == 1 ? 'checked' : ''); ?>>
                    <strong><?php echo esc_attr($single_type->label); ?></strong></label>
                </td>
                <td>
                  <?php
                    $option_names = array('click5_sitemap_use_custom_name_'.$single_type->name, 'click5_sitemap_custom_name_text_'.$single_type->name);
                  ?>
                  <label><?php _e('Section Heading:', 'sitemap-by-click5'); ?>&nbsp;</label>
                  <label><input type="checkbox" name="<?php echo esc_attr($option_names[0]); ?>" id="<?php echo esc_attr($option_names[0]); ?>" value="1" <?php echo (intval(esc_attr( get_option($option_names[0]) )) == 1 ? 'checked' : ''); ?>/></label>
                  <input type="text" placeholder="<?php echo esc_attr($single_type->label); ?>" name="<?php echo esc_attr($option_names[1]); ?>" id="<?php echo esc_attr($option_names[1]); ?>" value="<?php echo esc_attr( get_option($option_names[1]) ); ?>"/>
                </td>
              </tr>
              <?php } ?>
              </tbody>
            </table>

            <p style="padding:10px 0 0 0; margin-bottom: 5px;"><?php _e('Select taxonomies which you want to include in your HTML Sitemap:', 'sitemap-by-click5'); ?></p>
            <table class="form-table">
              <tbody>

              <tr class="click5_sitemap_post_type_item">
                <td>
                    <?php $cat_tax_option_name = 'click5_sitemap_display_cat_tax'; ?>
                    <label for="<?php echo esc_attr($cat_tax_option_name); ?>">
                    <input type="checkbox" name="<?php echo esc_attr($cat_tax_option_name); ?>" id="<?php echo esc_attr($cat_tax_option_name); ?>" value="1" <?php echo (intval(esc_attr( get_option($cat_tax_option_name) )) == 1 ? 'checked' : ''); ?>>
                    <strong>Categories</strong></label>
                </td>
                <td>
                  <?php
                    $cat_tax_option_names = array('click5_sitemap_use_custom_name_cat_tax', 'click5_sitemap_custom_name_text_cat_tax');
                  ?>
                  <label><?php _e('Section Heading:', 'sitemap-by-click5'); ?>&nbsp;</label>
                  <label style="display:none"><input type="checkbox" name="<?php echo esc_attr($cat_tax_option_names[0]); ?>" id="<?php echo esc_attr($cat_tax_option_names[0]); ?>" value="1" <?php echo (intval(esc_attr( get_option($cat_tax_option_names[0]) )) == 1 ? 'checked' : ''); ?>/></label>
                  <input type="text" placeholder="Categories" name="<?php echo esc_attr($cat_tax_option_names[1]); ?>" id="<?php echo esc_attr($cat_tax_option_names[1]); ?>" value="<?php echo esc_attr( get_option($cat_tax_option_names[1]) ); ?>"/>
                </td>
              </tr>

              <tr class="click5_sitemap_post_type_item">
                <td>
                    <?php $authors_tax_option_name = 'click5_sitemap_display_authors_tax'; ?>
                    <label for="<?php echo esc_attr($authors_tax_option_name); ?>">
                    <input type="checkbox" name="<?php echo esc_attr($authors_tax_option_name); ?>" id="<?php echo esc_attr($authors_tax_option_name); ?>" value="1" <?php echo (intval(esc_attr( get_option($authors_tax_option_name) )) == 1 ? 'checked' : ''); ?>>
                    <strong>Authors</strong></label>
                </td>
                <td>
                  <?php
                    $authors_tax_option_names = array('click5_sitemap_use_custom_name_authors_tax', 'click5_sitemap_custom_name_text_authors_tax');
                  ?>
                  <label><?php _e('Section Heading:', 'sitemap-by-click5'); ?>&nbsp;</label>
                  <label style="display:none"><input type="checkbox" name="<?php echo esc_attr($authors_tax_option_names[0]); ?>" id="<?php echo esc_attr($authors_tax_option_names[0]); ?>" value="1" <?php echo (intval(esc_attr( get_option($authors_tax_option_names[0]) )) == 1 ? 'checked' : ''); ?>/></label>
                  <input type="text" placeholder="Authors" name="<?php echo esc_attr($authors_tax_option_names[1]); ?>" id="<?php echo esc_attr($authors_tax_option_names[1]); ?>" value="<?php echo esc_attr( get_option($authors_tax_option_names[1]) ); ?>"/>
                </td>
              </tr>

              <tr class="click5_sitemap_post_type_item">
                <td>
                    <?php $tag_tax_option_name = 'click5_sitemap_display_tag_tax'; ?>
                    <label for="<?php echo esc_attr($tag_tax_option_name); ?>">
                    <input type="checkbox" name="<?php echo esc_attr($tag_tax_option_name); ?>" id="<?php echo esc_attr($tag_tax_option_name); ?>" value="1" <?php echo (intval(esc_attr( get_option($tag_tax_option_name) )) == 1 ? 'checked' : ''); ?>>
                    <strong>Tags</strong></label>
                </td>
                <td>
                  <?php
                    $tag_tax_option_names = array('click5_sitemap_use_custom_name_tag_tax', 'click5_sitemap_custom_name_text_tag_tax');
                  ?>
                  <label><?php _e('Section Heading:', 'sitemap-by-click5'); ?>&nbsp;</label>
                  <label style="display:none"><input type="checkbox" name="<?php echo esc_attr($tag_tax_option_names[0]); ?>" id="<?php echo esc_attr($tag_tax_option_names[0]); ?>" value="1" <?php echo (intval(esc_attr( get_option($tag_tax_option_names[0]) )) == 1 ? 'checked' : ''); ?>/></label>
                  <input type="text" placeholder="Tags" name="<?php echo esc_attr($tag_tax_option_names[1]); ?>" id="<?php echo esc_attr($tag_tax_option_names[1]); ?>" value="<?php echo esc_attr( get_option($tag_tax_option_names[1]) ); ?>"/>
                </td>
              </tr>

              <?php

                $tax_args=array(
                  'public'   => true,
                  '_builtin' => false
                );
                $output = 'objects';
                $operator = 'and'; 
                $taxonomies=get_taxonomies($tax_args,$output,$operator); 

                if  ($taxonomies) {
                  foreach ($taxonomies  as $taxonomy ) {


              ?>
              <tr class="click5_sitemap_post_type_item">
                <td>
                    <?php $tax_option_name = 'click5_sitemap_display_'.$taxonomy->name; ?>
                    <label for="<?php echo esc_attr($tax_option_name); ?>">
                    <input type="checkbox" name="<?php echo esc_attr($tax_option_name); ?>" id="<?php echo esc_attr($tax_option_name); ?>" value="1" <?php echo (intval(esc_attr( get_option($tax_option_name) )) == 1 ? 'checked' : ''); ?>>
                    <strong><?php echo ucwords(esc_attr($taxonomy->labels->name)); ?></strong></label>
                </td>
                <td>
                  <?php
                    $tax_option_names = array('click5_sitemap_use_custom_name_'.$taxonomy->name, 'click5_sitemap_custom_name_text_'.$taxonomy->name);
                  ?>
                  <label><?php _e('Section Heading:', 'sitemap-by-click5'); ?>&nbsp;</label>
                  <label style="display:none"><input type="checkbox" name="<?php echo esc_attr($tax_option_names[0]); ?>" id="<?php echo esc_attr($tax_option_names[0]); ?>" value="1" <?php echo (intval(esc_attr( get_option($tax_option_names[0]) )) == 1 ? 'checked' : ''); ?>/></label>
                  <input type="text" placeholder="<?php echo ucwords(esc_attr($taxonomy->labels->name)); ?>" name="<?php echo esc_attr($tax_option_names[1]); ?>" id="<?php echo esc_attr($tax_option_names[1]); ?>" value="<?php echo esc_attr( get_option($tax_option_names[1]) ); ?>"/>
                </td>
              </tr>
              <?php } } ?>
              </tbody>
            </table>

            <p><strong><?php _e('Select grouping type:', 'sitemap-by-click5'); ?></strong></p>
            <table class="form-table">
              <tbody>
                <tr>
                  <select id="click5_sitemap_display_style" name="click5_sitemap_display_style" style="margin-left: 15px;">
                    <option value="group" <?php echo (esc_attr( get_option('click5_sitemap_display_style') ) == 'group' ? 'selected' : ''); ?>><?php _e('Split and group by post types', 'sitemap-by-click5'); ?></option>
                    <option value="merge" <?php echo (esc_attr( get_option('click5_sitemap_display_style') ) == 'merge' ? 'selected' : ''); ?>><?php _e('Merge into one list', 'sitemap-by-click5'); ?></option>
                  </select>
                </tr>
              </tbody>
            </table>

            <p><strong><?php _e('Display in Columns:', 'sitemap-by-click5'); ?></strong></p>
            <table class="form-table">
              <tbody>
                <tr>
                  <select id="click5_sitemap_display_columns" name="click5_sitemap_display_columns" style="margin-left: 15px;width: 224px;">
                    <option value="1" <?php echo (esc_attr( get_option('click5_sitemap_display_columns') ) == '1' ? 'selected' : ''); ?>>1 Column</option>
                    <option value="2" <?php echo (esc_attr( get_option('click5_sitemap_display_columns') ) == '2' ? 'selected' : ''); ?>>2 Columns</option>
                    <option value="3" <?php echo (esc_attr( get_option('click5_sitemap_display_columns') ) == '3' ? 'selected' : ''); ?>>3 Columns</option>
                    <option value="4" <?php echo (esc_attr( get_option('click5_sitemap_display_columns') ) == '4' ? 'selected' : ''); ?>>4 Columns</option>
                  </select>
                </tr>
              </tbody>
            </table>

            <p><strong><?php _e('Pagination:', 'sitemap-by-click5'); ?></strong></p>
            <table class="form-table">
              <tbody>
                <tr>
                  <label style="padding-left: 15px;"><?php _e('Items per page:', 'sitemap-by-click5'); ?>&nbsp;</label>
                  <input type="number" min="0" name="click5_sitemap_html_pagination_items_per_page" id="click5_sitemap_html_pagination_items_per_page" value="<?php echo intval(esc_attr( get_option('click5_sitemap_html_pagination_items_per_page') )); ?>"/>
                  <span style="width: auto; padding-left: 35px; color: #999; font-style: italic;"></span>
                  <br/><div id="items_1000_error" style="padding-left: 15px; color: red; display: none">You can set up to 1000 items per page</div>
                </tr>
              </tbody>
            </table>
            <p><strong><?php _e('URLs options:', 'sitemap-by-click5'); ?></strong></p>
            <table class="form-table">
              <tbody>
                <tr>
                  <label for="click5_sitemap_url_target_blanc" style="margin-left: 15px;">
                    <input type="checkbox" name="click5_sitemap_url_target_blanc" id="click5_sitemap_url_target_blanc" value="1" <?php echo (intval(esc_attr( get_option('click5_sitemap_url_target_blanc') )) == 1 ? 'checked' : ''); ?>><?php _e('Open links in new tab', 'sitemap-by-click5'); ?></label>
                </tr>
              </tbody>
            </table>
            <p><strong><?php _e('Grouping Options:', 'sitemap-by-click5'); ?></strong></p>
            <table class="form-table">
              <tbody>
                <tr>
                  <p><?php _e('Enable Grouping for Post Type:', 'sitemap-by-click5'); ?></p>
                  <div class="disable_wrapper">
                  <?php
                    foreach($post_types as $single_type) {
                    $single_type = get_post_type_object($single_type);
                    $option_name_treat = 'click5_sitemap_html_blog_treat_'.$single_type->name;
                      ?>
                      <p style="padding-left: 15px;"><label><input type="checkbox" class="not-reset" name="<?php echo esc_attr($option_name_treat); ?>" id="<?php echo esc_attr($option_name_treat); ?>" value="1" <?php echo (intval(esc_attr( get_option($option_name_treat) )) == 1 ? 'checked' : ''); ?>/><?php echo esc_attr($single_type->label); ?></label></p>
                      <?php
                    }
                  ?>
                  </div>
                </tr>
                <tr>
                  <p><?php _e('Group by:', 'sitemap-by-click5'); ?></p>
                  <div class="disable_wrapper">
                  <select id="click5_sitemap_html_blog_group_by" class="not-reset" name="click5_sitemap_html_blog_group_by" style="margin-left: 15px;">
                    <option value="disabled" <?php echo (esc_attr( get_option('click5_sitemap_html_blog_group_by') ) == 'disabled' ? 'selected' : ''); ?>><?php _e('Disabled', 'sitemap-by-click5'); ?></option>
                    <option value="archives" <?php echo (esc_attr( get_option('click5_sitemap_html_blog_group_by') ) == 'archives' ? 'selected' : ''); ?>><?php _e('Archives', 'sitemap-by-click5'); ?></option>
                    <option value="categories" <?php echo (esc_attr( get_option('click5_sitemap_html_blog_group_by') ) == 'categories' ? 'selected' : ''); ?>><?php _e('Categories', 'sitemap-by-click5'); ?></option>
                    <option value="tags" <?php echo (esc_attr( get_option('click5_sitemap_html_blog_group_by') ) == 'tags' ? 'selected' : ''); ?>><?php _e('Tags', 'sitemap-by-click5'); ?></option>
                  </select>
                  </div>
                </tr>
              </tbody>
            </table>

            <p style="margin-bottom: 0;"><strong><?php _e('Sorting Options:', 'sitemap-by-click5'); ?></strong></p>
            <?php 
              $customSorting = "enabled";
              if(esc_attr(get_option("click5_sitemap_custom_sorting")) == 1)
                    $customSorting = "enabled";
              else
                    $customSorting = "disabled";
            ?>
            <p style="margin-bottom: 0; margin-top: -5px;" id="sorting_notification" class="<?php echo $customSorting; ?>"><small><em>The following settings will be ignored, because you have custom sorting enabled in the 'HTML Sitemap Order' section below. In order to use these sorting options, please click the 'Reset Order' button below.</em></small></p>
            <table class="form-table">
              <tbody>
                <tr>
                  <p><?php _e('Sort by:', 'sitemap-by-click5'); ?></p>
                  <div class="disable_wrapper">
                  <select id="click5_sitemap_html_blog_sort_by" class="not-reset" name="click5_sitemap_html_blog_sort_by" style="margin-left: 15px;">
                    <option value="alphabetically" <?php echo (esc_attr( get_option('click5_sitemap_html_blog_sort_by') ) == 'alphabetically' ? 'selected' : ''); ?>><?php _e('Alphabetically', 'sitemap-by-click5'); ?></option>
                    <option value="date" <?php echo (esc_attr( get_option('click5_sitemap_html_blog_sort_by') ) == 'date' ? 'selected' : ''); ?>><?php _e('Date', 'sitemap-by-click5'); ?></option>
                  </select>
                  </div>
                </tr>
  
                <tr>
                  <p><?php _e('Order by:', 'sitemap-by-click5'); ?></p>
                  <div class="disable_wrapper">
                  <select id="click5_sitemap_html_blog_order_by" class="not-reset" name="click5_sitemap_html_blog_order_by" style="margin-left: 15px;">
                    <option value="ASC" <?php echo (esc_attr( get_option('click5_sitemap_html_blog_order_by') ) == 'ASC' ? 'selected' : ''); ?>><?php _e('Ascending', 'sitemap-by-click5'); ?></option>
                    <option value="DESC" <?php echo (esc_attr( get_option('click5_sitemap_html_blog_order_by') ) == 'DESC' ? 'selected' : ''); ?>><?php _e('Descending', 'sitemap-by-click5'); ?></option>
                  </select>
                  </div>
                </tr>

              </tbody>
            </table>
            <br/>
            <button style="margin-left: 10px; margin-bottom: 20px" type="button" onClick="updateGeneralHtmlOptions()" class="button button-primary">Save Changes</button>
          </div>
        </div>
        <div class="postbox" id="order-sitemap">
          <h3 class="hndle"><span><?php _e('HTML Sitemap Order', 'sitemap-by-click5'); ?></span></h3>
          
          <p style="padding-left: 12px; padding-right: 12px;"><?php _e('Grab & drop elements listed below to setup HTML Sitemap display order.', 'sitemap-by-click5'); ?></p>
          <div class="inside">
            <button id="btnSaveOrder" class="button button-primary"><?php _e('Save Order', 'sitemap-by-click5'); ?></button>
            <button id="btnResetOrder" class="button button-secondary" style="margin-left: 10px;"><?php _e('Reset Order', 'sitemap-by-click5'); ?></button>
          </div>
        </div>
        <div class="postbox">
          <h3 class="hndle"><span><?php _e('HTML Sitemap Blacklist', 'sitemap-by-click5'); ?></span></h3>
          <div class="inside" id="click5_sitemap_blacklist_container" class="html">
            <div id="loader_blacklisted" class="loader-container"><div class="sbl-circ-ripple"></div></div>
            <ul id="click5_sitemap_already_blacklisted" class="results">
            </ul>
            <div class="ClearBlacklist_sec">
              <a href="#" id="btnClearBlacklist" class="click5_sitemap_clear_blacklist"><?php _e('Clear entire Blacklist', 'sitemap-by-click5'); ?></a>
            </div>
            <hr/>
            <table class="form-table">
              <tbody>
              <tr>
                <th scope="row">
                <?php _e('Search for posts/pages', 'sitemap-by-click5'); ?></th>
                <td>
                    <input type="text" id="page_search" placeholder="Search" />
                    <label>&nbsp;<?php _e('Type:', 'sitemap-by-click5'); ?>&nbsp;</label>
                    <select id="page_type" disabled>
                      <option value="all" selected><?php _e('All', 'sitemap-by-click5'); ?></option>
                      <option value="category"><?php _e('Category', 'sitemap-by-click5'); ?></option>
                      <option value="authors"><?php _e('Authors', 'sitemap-by-click5'); ?></option>
                      <option value="tag"><?php _e('Tags', 'sitemap-by-click5'); ?></option>
                      <?php 
                        $cpt_args = array(
                          'public'   => true,
                          '_builtin' => false,
                        );
                        $cpt_output = 'names';
                        $cpt_operator = 'and';
                        $cpt_types = get_taxonomies( $cpt_args, $cpt_output, $cpt_operator ); 
                        $taxonomies_array = array();
                        foreach($cpt_types as $cpt_item) {
                          array_push($taxonomies_array, $cpt_item);
                        }

                        foreach($taxonomies_array as $taxonomies_item) {
                          if(boolval(esc_attr(get_option('click5_sitemap_display_' . $taxonomies_item)))) {
                            $tax = get_taxonomy($taxonomies_item);
                            ?>
                              <option value="<?php echo esc_attr($taxonomies_item); ?>"><?php echo esc_attr(ucfirst($tax->labels->name)); ?></option>
                            <?php
                          }
                        }
                      
                      ?>


                      <?php
                        foreach($post_types as $single_type) {
                          $single_type = get_post_type_object($single_type);
                      ?>
                        <option value="<?php echo esc_attr($single_type->name); ?>"><?php echo esc_attr($single_type->label); ?></option>
                      <?php } ?>
                    </select>
                    <input type="hidden" id="all_types" value="<?php echo esc_attr(implode(',', $post_types)); ?>" />
                </td>
              </tr>
              </tbody>
            </table>
            <hr/>
            <div id="loader_results" class="loader-container"><div class="sbl-circ-ripple"></div></div>
            <ul id="results" class="results"></ul>
          </div>
        </div>
      </div>
    </div>
    <?php elseif( $active_tab == 'seo' ): ?>
    <?php settings_fields( 'click5_sitemap_seo' ); ?>
    <?php do_settings_sections( 'click5_sitemap_seo' ); ?>
    <div id="poststuff">
      <div id="post-body-content">
        <div class="error"<?php  global $global_yoast_xml; if(!$global_yoast_xml) {echo "style='display:none'";} else { echo 'style="margin-top: 0px!important;"';} ?>>
          <p>
            <b><?php _e('Warning: ', 'sitemap-by-click5'); ?></b>
            <?php _e('We have detected an XML Sitemap generated by a different plugin. We recommend to deactivate the XML sitemap generated by the YOAST SEO plugin in order to prevent any conflicts or SEO errors on your website. These may be reported in Google Search Console and other tools.', 'sitemap-by-click5'); ?>
          </p>
          <button type="button" onclick="redirectToYoast()" style="margin-bottom: 8px;" class="button button-primary">Disable Now</button>
        </div>
        <div class="error" <?php  global $global_aio_seo_xml; if(!$global_aio_seo_xml) {echo "style='display:none'";} else { echo 'style="margin-top: 0px!important;"';} ?>>
          <p>
            <b><?php _e('Warning: ', 'sitemap-by-click5'); ?></b>
            <?php _e('We have detected an XML Sitemap generated by a different plugin. We recommend to deactivate the XML sitemap generated by the All In One SEO plugin in order to prevent any conflicts or SEO errors on your website. These may be reported in Google Search Console and other tools.', 'sitemap-by-click5'); ?>
          </p>
          <button type="button" onclick="redirectToAioSeo()" style="margin-bottom: 8px;" class="button button-primary">Disable Now</button>
        </div>
        <div class="error" <?php  global $global_file_xml; if(!$global_file_xml) {echo "style='display:none'";} else { echo 'style="margin-top: 0px!important;"';} ?>>
          <p>
            <b><?php _e('Warning: ', 'sitemap-by-click5'); ?></b>
            <?php _e('We have detected an XML Sitemap generated by a different plugin. We recommend to deactivate the other sitemap in order to prevent any conflicts or SEO errors on your website. These may be reported in Google Search Console and other tools.', 'sitemap-by-click5'); ?>
          </p>
        </div>
        <div class="postbox">
          <h3 class="hndle"><span><?php _e('XML Sitemap & Robots.txt', 'sitemap-by-click5'); ?></span></h3>
          <?php  wp_nonce_field( 'click5_sitemap_nonce','click5_sitemap_nonce' ); ?>
          <div class="inside">
              <table class="form-table">
                <tbody>
                <tr>
                      <td class="click5_sitemap_options_wrapper">
                        <p style="width: 100%; margin-bottom: 10px;"><strong><?php _e('Your sitemap.xml:', 'sitemap-by-click5'); ?></strong></p>
                        <div id="loader_status_sitemap" class="loader-container"><div class="sbl-circ-ripple"></div></div>
                        <div id="click5_sitemap_url_container" style="width: 100%; display: none;">
                        <?php $sitemaps = glob(ABSPATH.'/*sitemap*.xml'); ?>
                        <?php if (count($sitemaps)): ?>
                        <?php
                          $resultArray = array();
                          foreach( $sitemaps as $sitemap ) {
                            if (strpos($sitemap, 'index.xml') !== false && strpos($sitemap, 'video') == false && strpos($sitemap, 'image') == false && strpos($sitemap, 'author') == false && strpos($sitemap, 'category') == false) {
                              array_unshift($resultArray, $sitemap);
                            } else {
                                $resultArray[] = $sitemap;
                            }
                          }
                          $sitemaps = $resultArray;
                        ?>
                        <?php foreach( $sitemaps as $sitemap ) { 
                          if (strpos($sitemap, 'index.xml') !== false && strpos($sitemap, 'video') == false && strpos($sitemap, 'image') == false && strpos($sitemap, 'author') == false && strpos($sitemap, 'category') == false){
                          ?>
                        <a style="display: block; width: max-content;" target="_blank" class="click5_sitemap_urls" href="<?php echo esc_url(site_url().'/'.str_replace('-index', '', basename($sitemap))); ?>"><?php echo esc_url(site_url().'/'.str_replace('-index', '', basename($sitemap))); ?></a>
                        <?php } else {?>
                        <a style="display: block; width: max-content;" target="_blank" class="click5_sitemap_urls" href="<?php echo esc_url(site_url().'/'. basename($sitemap)); ?>"><?php echo esc_url(site_url().'/'. basename($sitemap)); ?></a>
                        <?php }} ?>
                        <?php else: ?>
                        <p class="sitemap_not_gen" style="width: 100%;"><?php _e('sitemap.xml not generated yet.', 'sitemap-by-click5'); ?></p>
                        <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                    <tr>
                    
                      <td class="click5_sitemap_options_wrapper">
                        <label><input type="checkbox" value="1" onClick=<?php global $backupExist;  if ($backupExist) { echo "openModalBackup(this.value)"; } else { echo "openModalNoBackup(this.value)"; } ?> name="click5_sitemap_seo_robots_txt" id="click5_sitemap_seo_robots_txt" <?php echo (intval(esc_attr( get_option('click5_sitemap_seo_robots_txt') )) == 1 ? 'checked' : ''); ?> /><?php _e('Generate robots.txt', 'sitemap-by-click5'); ?></label>
                        <br/>
                        <p style="width: 100%; margin-bottom: 10px;"><strong><?php _e('Your robots.txt:', 'sitemap-by-click5'); ?></strong></p>
                        <div id="loader_status_robots" class="loader-container"><div class="sbl-circ-ripple"></div></div>
                        <div id="click5_sitemap_robots_txt_container" style="width: 100%; display: flex; flex-direction: column;">
                        <?php click5_sitemap_print_robots_txt(); ?>
                        <div name="robots_error" style="color:#d80707; display:none">"robots.txt" has failed to update<br/>Unable to update directory <?php echo ABSPATH; ?> Is this directory writeble by the server?</div>
                        </div>
                      </td>
                  </tr>
                </tbody>
              </table>
          </div>
        </div>
        <div class="postbox" id="general_seo">
          <h3 class="hndle"><span><?php _e('General XML Sitemap Settings', 'sitemap-by-click5'); ?></span></h3>
          <div class="inside" id="ajaxable">
              <table class="form-table">
                <tbody>
                    <tr>
                      <td class="click5_sitemap_options_wrapper">
                        <div>
                          <p style="width: 100%; margin-bottom: 10px;"><strong><?php _e('Generate SEO files:', 'sitemap-by-click5'); ?></strong></p>
                            <div style="display: flex; flex-direction: column;">
                                <label><input type="checkbox" value="1" name="click5_sitemap_seo_sitemap_xml" id="click5_sitemap_seo_sitemap_xml" <?php echo (intval(esc_attr( get_option('click5_sitemap_seo_sitemap_xml') )) == 1 ? 'checked' : ''); ?>/><?php _e('Generate sitemap.xml', 'sitemap-by-click5'); ?></label>
                                <!--<label><input type="checkbox" value="1" onClick=<?php global $backupExist; if ($backupExist) { echo "openModalBackup(this.value)"; } else { echo "openModalNoBackup(this.value)"; } ?> name="click5_sitemap_seo_robots_txt" id="click5_sitemap_seo_robots_txt" <?php echo (intval(esc_attr( get_option('click5_sitemap_seo_robots_txt') )) == 1 ? 'checked' : ''); ?> /><?php _e('Generate robots.txt', 'sitemap-by-click5'); ?></label>-->
                                <label><input type="checkbox" value="1" name="click5_sitemap_seo_include_sitemap_xml" id="click5_sitemap_seo_include_sitemap_xml" <?php echo (intval(esc_attr( get_option('click5_sitemap_seo_include_sitemap_xml') )) == 1 ? 'checked' : ''); ?> /><?php _e('Include sitemap.xml URL in robots.txt', 'sitemap-by-click5'); ?></label>
                            </div>
                        </div>
                      </td>
                      <td class="click5_sitemap_options_wrapper">
                        <div>
                          <p style="width: 100%; margin-bottom: 10px;"><strong><?php _e('Include post types:', 'sitemap-by-click5'); ?></strong></p>
                          <div style="display: flex; flex-direction: column;">
                          <?php
                            $post_types = click5_sitemap_get_post_types();
                            foreach($post_types as $single_type) {
                            $single_type = get_post_type_object($single_type);
                            $option_name = 'click5_sitemap_seo_post_type_'.$single_type->name;
                          ?>
                                <label for="<?php echo esc_attr($option_name); ?>"><input type="checkbox" name="<?php echo esc_attr($option_name); ?>" id="<?php echo esc_attr($option_name); ?>" value="1" <?php echo (intval(esc_attr( get_option($option_name) )) == 1 ? 'checked' : ''); ?>><?php echo esc_attr($single_type->label); ?></label>
                          <?php } ?>
                          </div>
                        </div>
                      </td>

                      <td class="click5_sitemap_options_wrapper">
                        <div>
                          <p style="width: 100%; margin-bottom: 10px;"><strong><?php _e('Include taxonomy sitemaps:', 'sitemap-by-click5'); ?></strong></p>
                          <div style="display: flex; flex-direction: column;">
                                <label for="<?php echo esc_attr('click5_sitemap_seo_xml_categories'); ?>"><input type="checkbox" name="<?php echo esc_attr('click5_sitemap_seo_xml_categories'); ?>" id="<?php echo esc_attr('click5_sitemap_seo_xml_categories'); ?>" value="1" <?php echo (intval(esc_attr( get_option('click5_sitemap_seo_xml_categories') )) == 1 ? 'checked' : ''); ?>><?php _e('Categories', 'sitemap-by-click5'); ?></label>
                                <?php
                                if(!get_tags()){
                                    update_option('click5_sitemap_seo_xml_tags', false);                                   
                                }
                                ?>
                              <input type="hidden" id="tags_exist" name="tags_exist" value="<?php echo (get_tags() ? '1' : '0'); ?>">
                              <label <?php echo (get_tags() ? '' : 'style="color:#D3D3D3;"'); ?>  for="<?php echo esc_attr('click5_sitemap_seo_xml_tags'); ?>"><input type="checkbox" name="<?php echo esc_attr('click5_sitemap_seo_xml_tags'); ?>" id="<?php echo esc_attr('click5_sitemap_seo_xml_tags'); ?>" value="1" <?php echo (get_tags() ? '' : 'disabled'); ?> <?php echo (intval(esc_attr( get_option('click5_sitemap_seo_xml_tags') )) == 1 ? 'checked' : ''); ?>><?php _e('Tags', 'sitemap-by-click5'); ?></label>


                                <?php

                                $cpt_args = array(
                                  'public'   => true,
                                  '_builtin' => false,
                                );
                                $cpt_output = 'objects';
                                $cpt_operator = 'and';
                                $cpt_types = get_taxonomies( $cpt_args, $cpt_output, $cpt_operator ); 

                                foreach ( $cpt_types  as $cpt_type ) {  

                                $option_name = 'click5_sitemap_seo_xml_'.$cpt_type->name.'_tax';

                                ?>
                                      <label for="<?php echo esc_attr($option_name); ?>"><input type="checkbox" name="<?php echo esc_attr($option_name); ?>" id="<?php echo esc_attr($option_name); ?>" value="1" <?php echo (intval(esc_attr( get_option($option_name) )) == 1 ? 'checked' : ''); ?>><?php echo ucwords(esc_attr($cpt_type->labels->name)); ?></label>

                                <?php } ?>

                                <label for="<?php echo esc_attr('click5_sitemap_seo_xml_authors'); ?>"><input type="checkbox" name="<?php echo esc_attr('click5_sitemap_seo_xml_authors'); ?>" id="<?php echo esc_attr('click5_sitemap_seo_xml_authors'); ?>" value="1" <?php echo (intval(esc_attr( get_option('click5_sitemap_seo_xml_authors') )) == 1 ? 'checked' : ''); ?>><?php _e('Authors', 'sitemap-by-click5'); ?></label>

                                <?php 
                                  $getCustomCat = click5_sitemap_getCustomCategoriesCustomUrlsHTML();
                              
                                  foreach($getCustomCat as $item) {
                                    
                                    $cust_option_name = 'click5_sitemap_seo_xml_'.str_replace(' ', '_', $item).'_custom';

                                    ?>

                                    <label for="<?php echo esc_attr($cust_option_name); ?>"><input type="checkbox" name="<?php echo esc_attr($cust_option_name); ?>" id="<?php echo esc_attr($cust_option_name); ?>" value="1" <?php echo (intval(esc_attr( get_option($cust_option_name) )) == 1 ? 'checked' : ''); ?>><?php echo ucwords(esc_attr($item)); ?></label>

                                    <?php
                                  }
                                ?>
                          </div>
                        </div>
                      </td>
                        <td class="click5_sitemap_options_wrapper">
                            <div>
                                <p style="width: 100%; margin-bottom: 10px;"><strong><?php _e('Include media sitemaps:', 'sitemap-by-click5'); ?></strong></p>
                                <div style="display: flex; flex-direction: column;">
                                    <label for="<?php echo esc_attr('click5_sitemap_seo_xml_images'); ?>"><input type="checkbox" name="<?php echo esc_attr('click5_sitemap_seo_xml_images'); ?>" id="<?php echo esc_attr('click5_sitemap_seo_xml_images'); ?>" value="1" <?php echo (intval(esc_attr( get_option('click5_sitemap_seo_xml_images') )) == 1 ? 'checked' : ''); ?>><?php _e('Images', 'sitemap-by-click5'); ?></label>
                                    <label for="<?php echo esc_attr('click5_sitemap_seo_xml_docs'); ?>"><input type="checkbox" name="<?php echo esc_attr('click5_sitemap_seo_xml_docs'); ?>" id="<?php echo esc_attr('click5_sitemap_seo_xml_docs'); ?>" value="1" <?php echo (intval(esc_attr( get_option('click5_sitemap_seo_xml_docs') )) == 1 ? 'checked' : ''); ?>><?php _e('Documents', 'sitemap-by-click5'); ?></label>
                                    <label for="<?php echo esc_attr('click5_sitemap_seo_xml_videos'); ?>"><input type="checkbox" name="<?php echo esc_attr('click5_sitemap_seo_xml_videos'); ?>" id="<?php echo esc_attr('click5_sitemap_seo_xml_videos'); ?>" value="1" <?php echo (intval(esc_attr( get_option('click5_sitemap_seo_xml_videos') )) == 1 ? 'checked' : ''); ?>><?php _e('Videos', 'sitemap-by-click5'); ?></label>
                                </div>
                            </div>
                        </td>


                    </tr>
                    <tr>
                      <td class="click5_sitemap_options_wrapper">
                        <p style="width: 100%; margin-bottom: 10px;"><strong><?php _e('XML Sitemap type:', 'sitemap-by-click5'); ?></strong></p>
                              <select name="click5_sitemap_seo_sitemap_type" id="click5_sitemap_seo_sitemap_type">
                                <option value="splitted" <?php echo (esc_attr( get_option('click5_sitemap_seo_sitemap_type') ) == 'splitted' ? 'selected' : ''); ?>><?php _e('Split and group by post types', 'sitemap-by-click5'); ?></option>
                                <option value="one_file" <?php echo (esc_attr( get_option('click5_sitemap_seo_sitemap_type') ) == 'one_file' ? 'selected' : ''); ?>><?php _e('Merge into one list', 'sitemap-by-click5'); ?></option>
                              </select>
                              <p style="width: 100%;">* <?php _e('<strong>Splitted</strong> sitemap type is recommended for websites with many pages/posts.', 'sitemap-by-click5'); ?></p>
                      </td>
                    </tr>
                    <tr>
                      <td class="click5_sitemap_options_wrapper">
                      <p style="width: 100%; margin-bottom: 10px;"><strong><?php _e('Automation:', 'sitemap-by-click5'); ?></strong></p>
                          <label style="width: 100%;"><input type="checkbox" name="click5_sitemap_seo_auto" id="click5_sitemap_seo_auto" value="1" <?php echo (intval(esc_attr( get_option('click5_sitemap_seo_auto') )) == 1 ? 'checked' : ''); ?>></input> <?php _e('Re-generate Sitemap files on each website structure change (ex. new page have been added or its content modified)', 'sitemap-by-click5'); ?></label>
                          <label style="width: 100%;"><input type="checkbox" name="click5_sitemap_seo_cron" id="click5_sitemap_seo_cron" value="1" <?php echo (intval(esc_attr( get_option('click5_sitemap_seo_cron') )) == 1 ? 'checked' : ''); ?>></input> <?php _e('Re-generate Sitemap via a cron job (recomended for websites with many pages/posts)', 'sitemap-by-click5'); ?></label>       
                        </td>
                    </tr>
                    <tr>
                      <td>
                        <button type="button" onClick="reGenerateButton()" class="button button-primary" id="seoSave">Save Changes</button>
                        <button id="generate_btn" class="button button-secondary"><?php _e('Re-generate XML Sitemap', 'sitemap-by-click5'); ?></button><div id="click5-ajax-loader" class="sbl-circ"></div>           
                      </td>
                    </tr>
                </tbody>
              </table>
              <div name="xml_error" style="color:#d80707; display:none">Cannot create XML Sitemaps<br/>Unable to update directory <?php echo ABSPATH; ?> Is this directory writeble by the server?</div>
          </div>
        </div>
        <div class="postbox">
          <h3 class="hndle"><span><?php _e('XML Sitemap Blacklist', 'sitemap-by-click5'); ?></span></h3>
          <div class="inside" id="click5_sitemap_blacklist_container" class="seo">
            <div id="loader_blacklisted" class="loader-container"><div class="sbl-circ-ripple"></div></div>
            <ul id="click5_sitemap_already_blacklisted" class="results">
            </ul>
            <div class="ClearBlacklist_sec">
              <a href="#" id="btnClearBlacklist" class="click5_sitemap_clear_blacklist"><?php _e('Clear entire Blacklist', 'sitemap-by-click5'); ?></a>
            </div>
            <hr/>
            <table class="form-table">
              <tbody>
              <tr>
                <th scope="row">
                <?php _e('Search for posts/pages', 'sitemap-by-click5'); ?></th>
                <td>
                    <input type="text" id="page_search" placeholder="Search" />
                    <label>&nbsp;<?php _e('Type:', 'sitemap-by-click5'); ?>&nbsp;</label>
                    <select id="page_type" disabled>
                      <option value="all" selected><?php _e('All', 'sitemap-by-click5'); ?></option>
                      <option value="category"><?php _e('Category', 'sitemap-by-click5'); ?></option>
                      <option value="authors"><?php _e('Authors', 'sitemap-by-click5'); ?></option>
                      <option value="tag"><?php _e('Tags', 'sitemap-by-click5'); ?></option>
                      <?php 
                        $cpt_args = array(
                          'public'   => true,
                          '_builtin' => false,
                        );
                        $cpt_output = 'names';
                        $cpt_operator = 'and';
                        $cpt_types = get_taxonomies( $cpt_args, $cpt_output, $cpt_operator ); 
                        $taxonomies_array = array();
                        foreach($cpt_types as $cpt_item) {
                          array_push($taxonomies_array, $cpt_item);
                        }

                        foreach($taxonomies_array as $taxonomies_item) {
                          if(boolval(esc_attr(get_option('click5_sitemap_seo_xml_' . $taxonomies_item."_tax")))) {
                            $tax = get_taxonomy($taxonomies_item);
                            ?>
                              <option value="<?php echo esc_attr($taxonomies_item); ?>"><?php echo esc_attr(ucfirst($tax->labels->name)); ?></option>
                            <?php
                          }
                        }
                      
                      ?>


                      <?php
                        foreach($post_types as $single_type) {
                          $single_type = get_post_type_object($single_type);
                      ?>
                        <option value="<?php echo esc_attr($single_type->name); ?>"><?php echo esc_attr($single_type->label); ?></option>
                      <?php } ?>
                    </select>
                    <input type="hidden" id="all_types" value="<?php echo esc_attr(implode(',', $post_types)); ?>" />
                </td>
              </tr>
              </tbody>
            </table>
            <hr/>
            <div id="loader_results" class="loader-container"><div class="sbl-circ-ripple"></div></div>
            <ul id="results" class="results"></ul>
          </div>
        </div>
      </div>
    </div>
    <?php elseif( $active_tab == 'urls' ): ?>
    <?php settings_fields( 'click5_sitemap_urls' ); ?>
    <?php do_settings_sections( 'click5_sitemap_urls' ); ?>
    <div id="poststuff" class="custom_urls">
      <div id="post-body-content">
      <div class="error"<?php  global $global_yoast_xml; if(!$global_yoast_xml) {echo "style='display:none'";} else { echo 'style="margin-top: 0px!important;"';} ?>>
          <p>
            <b><?php _e('Warning: ', 'sitemap-by-click5'); ?></b>
            <?php _e('We have detected an XML Sitemap generated by a different plugin. We recommend to deactivate the XML sitemap generated by the YOAST SEO plugin in order to prevent any conflicts or SEO errors on your website. These may be reported in Google Search Console and other tools.', 'sitemap-by-click5'); ?>
          </p>
          <button type="button" onclick="redirectToYoast()" style="margin-bottom: 8px;" class="button button-primary">Disable Now</button>
        </div>
        <div class="error" <?php  global $global_aio_seo_xml; if(!$global_aio_seo_xml) {echo "style='display:none'";} else { echo 'style="margin-top: 0px!important;"';} ?>>
          <p>
            <b><?php _e('Warning: ', 'sitemap-by-click5'); ?></b>
            <?php _e('We have detected an XML Sitemap generated by a different plugin. We recommend to deactivate the XML sitemap generated by the All In One SEO plugin in order to prevent any conflicts or SEO errors on your website. These may be reported in Google Search Console and other tools.', 'sitemap-by-click5'); ?>
          </p>
          <button type="button" onclick="redirectToAioSeo()" style="margin-bottom: 8px;" class="button button-primary">Disable Now</button>
        </div>
        <div class="error" <?php  global $global_file_xml; if(!$global_file_xml) {echo "style='display:none'";} else { echo 'style="margin-top: 0px!important;"';} ?>>
          <p>
            <b><?php _e('Warning: ', 'sitemap-by-click5'); ?></b>
            <?php _e('We have detected an XML Sitemap generated by a different plugin. We recommend to deactivate the other sitemap in order to prevent any conflicts or SEO errors on your website. These may be reported in Google Search Console and other tools.', 'sitemap-by-click5'); ?>
          </p>
        </div>
        <div class="postbox">
          <h3 class="hndle"><span><?php _e('Existing Custom URLs', 'sitemap-by-click5'); ?></span></h3>
          <div class="inside">
            <table class="form-table">
              <tbody>
              <tr valign="top">
                <td>
                  <div id="loader_custom_urls_list" class="loader-container"><div class="sbl-circ-ripple"></div></div>
                  <p id="click5_no_records_found" style="display: none;"><?php _e('No custom URLs saved yet.', 'sitemap-by-click5'); ?></p>
                  <ul id="custom_urls_list" class="results"></ul>
                  <a href="#" id="click5_clear_custom_list" style="display: none;" disabled><?php _e('Clear list', 'sitemap-by-click5'); ?></a>
                </td>
              </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="postbox" id="edit_custom_url">
          <h3 class="hndle"><span><?php _e('Edit URL', 'sitemap-by-click5'); ?></span></h3>
          <div class="inside">
            <table class="form-table">
              <tbody>
              <tr valign="top">
                <th scope="row"><?php _e('URL Title', 'sitemap-by-click5'); ?>&nbsp;<span style="color:#d80707">*</span></th>
                <td>
                  <input type="text" name="edit_url_title" id="edit_url_title" placeholder="Enter your custom URL Title" style="width: 100%;" />
                </td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('URL', 'sitemap-by-click5'); ?>&nbsp;<span style="color:#d80707">*</span></th>
                <td>
                  <input type="text" name="edit_url_url" id="edit_url_url" placeholder="Enter your custom URL" style="width: 100%;" />
                  <p class="description"><?php _e('For ex.', 'sitemap-by-click5'); ?> <strong>/postname/</strong> or <strong>https://www.mywebsite.com/</strong></p>
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label for="click5_sitemap_heading_text"><?php _e('URL Category', 'sitemap-by-click5'); ?>&nbsp;<span style="color:#d80707">*</span></label>
                </th>
                <td>
                  <?php
                    $post_types = click5_sitemap_get_post_types();
                    $getCustomCategories = click5_sitemap_getCustomCategoriesCustomUrlsHTML(); 
                  ?>
                  <select id="edit_url_category_select">
                          <option value="custom" data-custom="true"><?php _e('Custom', 'sitemap-by-click5'); ?></option>
                          <?php
                            foreach($post_types as $post_type) {
                              $post_type = get_post_type_object($post_type);
                              echo '<option value="'.esc_attr($post_type->name).'">'.esc_attr($post_type->label).'</option>';
                            }
                            foreach($getCustomCategories as $item) {
                              echo '<option value="'.esc_attr($item).'" data-custom="true">'.esc_attr($item).'</option>';
                            }
                          ?>
                  </select>
                  <input type="hidden" id="edit_url_category_use_custom" name="edit_url_category_use_custom" value="true" />
                  <input type="text" name="edit_url_category_text" id="edit_url_category_text" placeholder="ex. `External URLs`" style="display: none;" />
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label for="click5_sitemap_heading_text"><?php _e('Open link in new tab', 'sitemap-by-click5'); ?></label>
                </th>
                <td>
                  <label><input type="checkbox" name="edit_url_open_new_tab" id="edit_url_open_new_tab" value="1"><?php _e('Yes', 'sitemap-by-click5'); ?></label>
                  <p class="description"><?php _e('Used for the HTML Sitemap.', 'sitemap-by-click5'); ?></p>
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label for="click5_sitemap_heading_text"><?php _e('Last modification date', 'sitemap-by-click5'); ?></label>
                </th>
                <td>
                    <input type="text" name="edit_url_last_mod_date" id="edit_url_last_mod_date" />
                    <p class="description"><?php _e('Used for the XML Sitemap (you can leave it empty).', 'sitemap-by-click5'); ?></p>
                </td>
              </tr>
              <tr>
                <td>
                  <button id="saveURLbtn" class="button button-primary"><?php _e('Save Changes', 'sitemap-by-click5'); ?></button>
                  <button id="cancelURLbtn" class="button button-secondary"><?php _e('Cancel', 'sitemap-by-click5'); ?></button>
                </td>
              </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="postbox" id="add_custom_url">
          <h3 class="hndle"><span><?php _e('Add New URL', 'sitemap-by-click5'); ?></span></h3>
          <div class="inside">
            <table class="form-table">
              <tbody>
              <tr valign="top">
                <th scope="row"><?php _e('URL Title', 'sitemap-by-click5'); ?>&nbsp;<span style="color:#d80707">*</span></th>
                <td>
                  <input type="text" name="add_url_title" id="add_url_title" placeholder="Enter your custom URL Title" style="width: 100%;" />
                </td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('URL', 'sitemap-by-click5'); ?>&nbsp;<span style="color:#d80707">*</span></th>
                <td>
                  <input type="text" name="add_url_url" id="add_url_url" placeholder="Enter your custom URL" style="width: 100%;" />
                  <p class="description"><?php _e('For ex.', 'sitemap-by-click5'); ?> <strong>/postname/</strong> or <strong>https://www.mywebsite.com/</strong></p>
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label for="click5_sitemap_heading_text"><?php _e('URL Category', 'sitemap-by-click5'); ?>&nbsp;<span style="color:#d80707">*</span></label>
                </th>
                <td>
                  <?php
                    $post_types = click5_sitemap_get_post_types(); 
                    $getCustomCategories = click5_sitemap_getCustomCategoriesCustomUrlsHTML(); 
                  ?>
                  <select id="add_url_category_select" style="float: left; margin-right: 3px;">
                          <option value="custom" data-custom="true"><?php _e('Custom', 'sitemap-by-click5'); ?></option>
                          <?php
                            foreach($post_types as $post_type) {
                              $post_type = get_post_type_object($post_type);
                              echo '<option value="'.esc_attr($post_type->name).'">'.esc_attr($post_type->label).'</option>';
                            }
                            foreach($getCustomCategories as $item) {
                              $item = str_replace("Custom Url", "Custom URLs", $item);
                              echo '<option value="'.esc_attr($item).'" data-custom="true">'.esc_attr($item).'</option>';
                            }
                          ?>
                  </select>
                  <input type="hidden" id="add_url_category_use_custom" name="add_url_category_use_custom" value="true" />
                  <input type="text" name="add_url_category_text" id="add_url_category_text" placeholder="ex. `External URLs`" style="display: none; float: left;" />
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label for="click5_sitemap_heading_text"><?php _e('Open link in new tab', 'sitemap-by-click5'); ?></label>
                </th>
                <td>
                  <label><input type="checkbox" name="add_url_open_new_tab" id="add_url_open_new_tab" value="1"><?php _e('Yes', 'sitemap-by-click5'); ?></label>
                  <p class="description"><?php _e('Used for the HTML Sitemap.', 'sitemap-by-click5'); ?></p>
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label for="click5_sitemap_heading_text"><?php _e('Last modification date', 'sitemap-by-click5'); ?></label>
                </th>
                <td>
                  <?php
                  
                  $currentDate = new DateTime(); 
                  $currentDate->setTimezone(new DateTimeZone(wp_timezone_string()));
                  $currentDate = $currentDate->format('m/d/Y');
                  
                  ?>
                  <input type="text" class="ll-skin-nigran" name="add_url_last_mod_date" id="add_url_last_mod_date" date-currentData="<?php echo $currentDate ?>" placeholder="<?php echo $currentDate ?>" />
                  <p class="description"><?php _e('Used for the XML Sitemap (you can leave it empty).', 'sitemap-by-click5'); ?></p>
                </td>
              </tr>
              <tr>
                <td>
                  <button id="addNewURLbtn" class="button button-primary"><?php _e('Add URL', 'sitemap-by-click5'); ?></button>
                </td>
              </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="postbox" id="order-sitemap" style="display:none">
          <div class="inside"></div>
        </div>

      </div>
    </div>
    <?php endif; ?>

    <?php 
      if($active_tab != 'urls'){
        if($active_tab === "seo")
          submit_button(null,'primary','save_changes',true);
        else{
          echo '<input type="button" name="submit" id="submit" class="button button-primary" value="Save Changes" onclick="saveChanges()">';
        }
      }
     
    ?>
<div id="c5ConfirmModalBackup" class="c5modal">
  <!-- Modal content -->
    <div class="c5modal-content">
    <span onclick="closeModal()" class="c5close">&times;</span>  
      <p style="margin-left: 15px">Revert to the previous version of the robots.txt file?</p>
      <br/>
      <br/>
      <input value="Yes" style="margin-left: 15px" type="button" onclick="markRobotsToDeleteRevert()" class="button button-primary" name="revert_backup_txt"/>
      <input value="No" style="margin-left: 15px" type="button" onclick="markRobotsToDelete()" class="button button-outline-primary" name="delete_backup_txt"/>
      <input value="Cancel" onclick="closeModal()"style="margin-left: 15px, width: 50px" class="button button-outline-primary c5cancle" name="cancel_button_backup" />
    </div>
</div>
<div id="c5ConfirmModal" class="c5modal">
  <!-- Modal content -->
 
  <div class="c5modal-content">
  <span onclick="closeModal()" class="c5close">&times;</span>
      <p style="margin-left: 15px">Are you sure you want to delete the robots.txt file?</p>
      <br/>
      <br/>
      <button value="Yes" style="margin-left: 15px" onclick="markRobotsToDelete()" type="button" class="button button-primary" name="delete_backup_txt">Yes</button>
      <input  width="48" value="No" onclick="closeModal()" style="margin-left: 15px" class="button button-outline-primary c5no" name="cancel_button_no_backup"/>
    </div>
 
</div> 
</form>
</div>
<div class="content-right content-right-width">
      <div id="poststuff">
        <div id="post-body-content">
            <div class="postbox">
              <h3 class="hndle"><span>Plugin Support</span></h3>
              <div class="inside">
                <p>Visit our <a href="http://wordpress.org/support/plugin/sitemap-by-click5" target="_blank" rel="nofollow">community forum</a> to find answers to common issues, ask questions, submit bug reports, feature suggestions and other tips about our plugin.</p>
                <p>Please consider supporting our plugin by <a href="https://wordpress.org/support/plugin/sitemap-by-click5/reviews/?filter=5" target="_blank" rel="nofollow">leaving a review</a>. Thank You!</p>
              </div>
            </div>
            <?php
              $currentMonth = new DateTime(); 
              $currentMonth->setTimezone(new DateTimeZone(wp_timezone_string()));
              $currentMonth = $currentMonth->format('m');
 
              $monthSwitch = intval($currentMonth%2);
 
              $c5HistoryLog_Pathpluginurl = WP_PLUGIN_DIR . '/history-log-by-click5/history-log-by-click5.php';
              $c5HistoryLog_IsInstalled = file_exists( $c5HistoryLog_Pathpluginurl );
 
              $c5DisableComments_Pathpluginurl = WP_PLUGIN_DIR . '/disable-comments-by-click5/disable-comments-by-click5.php';
              $c5DisableComments_IsInstalled = file_exists( $c5DisableComments_Pathpluginurl );
              $OtherWordpressPlugins = false;
              $AdImageURL = "assets/banner-300x515-sitemap-plugin.png";
              $AdImageSiteURL = "https://click5crm.com/?utm_source=comments-plugin&utm_medium=sidebar&utm_campaign=wp-plugins";
              $AdBoxSiteURL = "https://wordpress.org/plugins/history-log-by-click5/";
              
              switch($monthSwitch){
                case 0:
                if($c5HistoryLog_IsInstalled === false){
                    $AdImageURL = "assets/baner-v01c.png";
                    $AdImageSiteURL = "https://wordpress.org/plugins/history-log-by-click5/";
                  }else if($c5DisableComments_IsInstalled === false){
                    $OtherWordpressPlugins = "disablecomments";
                  }
                 
                  break;
                  
                case 1:
                  if($c5DisableComments_IsInstalled === false && $c5HistoryLog_IsInstalled === false){
                    $OtherWordpressPlugins = "historylog";
                  }else if($c5DisableComments_IsInstalled === false){
                    $OtherWordpressPlugins = "disablecomments";
                  }
                  else{
                    if($c5HistoryLog_IsInstalled === false){
                      $OtherWordpressPlugins = "historylog";
                    }
                  }
                  break;
              }
            ?>
            <div class="postbox with-image">
              <a href="<?php echo esc_url($AdImageSiteURL) ?>" target="_blank" rel="nofollow">
                <img src="<?php echo plugin_dir_url( __FILE__ ).$AdImageURL; ?>" alt="click5historylog">
              </a>
            </div>
            <?php if($OtherWordpressPlugins === "disablecomments"){ ?>
            <div class="postbox">
              <h3 class="hndle"><span>Our Other WordPress Plugins</span></h3>
              <div class="inside">
                <p><strong><a href="https://wordpress.org/plugins/disable-comments-by-click5/" target="_blank" rel="nofollow">Disable Comments by click5</a></strong></p>
                <p>Disable comments on your website and protect yourself from spammers.<br> As always 100% FREE!</p>
              </div>
            </div>
            <?php } else if($OtherWordpressPlugins === "historylog"){ ?>
            <div class="postbox">
              <h3 class="hndle"><span>Our Other WordPress Plugins</span></h3>
              <div class="inside">
                <p><strong><a href="https://wordpress.org/plugins/history-log-by-click5/" target="_blank" rel="nofollow">History Log by click5</a></strong></p>
                <p>Best plugin to track user activity and log changes on your WordPress website.</p>
                <p><strong><a  href="https://wordpress.org/plugins/search/click5/" target="_blank" rel="nofollow">and more...</a</strong></p>
              </div>
            </div>
            <?php } ?>
        </div>
      </div>
</div>   
</div>

<?php }

function click5_sitemap_init_admin_scripts() {
  //libraries
  $screen = get_current_screen();
  $version = CLICK5_SITEMAP_DEV_MODE ? time() : CLICK5_SITEMAP_VERSION;

  if(strpos($screen->base, 'sitemap-by-click5') !== false) {

    wp_enqueue_script( 'click5_sitemap_js_sortable', plugins_url('/js/admin/jquery-sortable.js', __FILE__), array(), $version, true);
  
    wp_enqueue_script( 'click5_sitemap_js_datepicker', plugins_url('/js/admin/datepicker.min.js', __FILE__), array(), $version);
    wp_enqueue_script( 'click5_sitemap_js_admin', plugins_url('/js/admin/index.js', __FILE__), array(), $version);
    wp_enqueue_script( 'click5_sitemap_js_admin_html', plugins_url('/js/admin/html.js', __FILE__), array(), $version);
    wp_enqueue_script( 'click5_sitemap_js_admin_seo', plugins_url('/js/admin/seo.js', __FILE__), array(), $version);
    wp_enqueue_script( 'click5_sitemap_js_admin_urls', plugins_url('/js/admin/urls.js', __FILE__), array(), $version);
    wp_enqueue_style( 'click5_sitemap_css_datepicker', plugins_url('/css/admin/datepicker.min.css', __FILE__), array(), $version);
    wp_enqueue_style( 'click5_sitemap_css_admin', plugins_url('/css/admin/index.css', __FILE__), array(), $version);
    wp_enqueue_style( 'click5_sitemap_css_sortable', plugins_url('/css/admin/sortable.css', __FILE__), array(), $version);
    

  }
  
  wp_localize_script( 'jquery', 'c5resturl', array('wpjson' => get_rest_url()) );
  wp_localize_script( 'jquery', 'c5homeurl', array('home' => get_home_url()) );

}

add_action('admin_enqueue_scripts','click5_sitemap_init_admin_scripts');



remove_action( 'template_redirect', array('WPSEO_Sitemaps_Router', 'template_redirect'), 0 );
function click5_sitemap_sitemap_xml_redirection() {

  if(boolval(esc_attr( get_option('click5_sitemap_seo_sitemap_xml')))){
  
    $current_url = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

    $b_should_redirect = false;

    

    if(preg_match('/\/(.*)?sitemap((?!-.+).)*\.(htm|html|xml)(\.gz)?/', $current_url)) {
      $b_should_redirect = true;
    }
    if(preg_match('/(.+|)sitemap_index.xml/', $current_url)) {
      $b_should_redirect = true;
    }

    if ($b_should_redirect) {
      wp_safe_redirect( home_url( '/sitemap-index.xml' ), 301, 'click5 Sitemap XML' );
      exit;
    }

  }
}
add_action( 'template_redirect', 'click5_sitemap_sitemap_xml_redirection', 0);


delete_option('click5_sitemap_seo_xml_custom_taxonomies');
//unregister_setting('click5_sitemap_seo', 'click5_sitemap_seo_xml_custom_taxonomies');

// uninstall hook

function click5_sitemap_uninstallFunction() { 
  //delete_option('click5_sitemap_url_target_blanc');
  //delete_option('click5_sitemap_blacklisted_array');
  //delete_option('click5_sitemap_urls_list');
  //delete_option('click5_sitemap_display_style');
  //delete_option('click5_sitemap_display_columns');
  //delete_option('click5_sitemap_seo_blacklisted_array');
  //delete_option('click5_sitemap_seo_sitemap_type');
  //delete_option('click5_sitemap_seo_robots_txt');
  //delete_option('click5_sitemap_seo_include_sitemap_xml');
  //delete_option('click5_sitemap_seo_auto');
  //delete_option('click5_sitemap_order_list');
  //delete_option('click5_sitemap_order_list2');
  //delete_option('click5_sitemap_order_list3');
  //delete_option('click5_sitemap_order_list4');
  //delete_option('click5_sitemap_order_list_nested');

  //delete_option('click5_sitemap_seo_xml_categories');
  //delete_option('click5_sitemap_seo_xml_tags');
  //delete_option('click5_sitemap_seo_xml_authors');

    //delete_option('click5_sitemap_seo_xml_images');
   // delete_option('click5_sitemap_seo_xml_videos');
   // delete_option('click5_sitemap_seo_xml_docs');

  //delete_option('click5_sitemap_seo_robots_backup');
/*
  $cpt_args = array('public'   => true, '_builtin' => false);
  $cpt_output = 'names';
  $cpt_operator = 'and';
  $cpt_types = get_taxonomies( $cpt_args, $cpt_output, $cpt_operator ); 
  foreach ( $cpt_types  as $cpt_type ) { 
    delete_option('click5_sitemap_seo_xml_'.$cpt_type.'_tax');
  }

  $getCustomCat = click5_sitemap_getCustomCategoriesCustomUrlsHTML();                       
  foreach($getCustomCat as $item) {
    delete_option('click5_sitemap_seo_xml_'.str_replace(' ', '_', $item).'_custom');
  }
  */
  //$post_types = click5_sitemap_get_post_types();
  /*foreach($post_types as $single_type) {
    $single_type = get_post_type_object($single_type);
    delete_option('click5_sitemap_display_'.$single_type->name);
    delete_option('click5_sitemap_use_custom_name_'.$single_type->name);
    delete_option('click5_sitemap_custom_name_text_'.$single_type->name);
    delete_option('click5_sitemap_seo_post_type_'.$single_type->name);
  }*/

  $current_user = wp_get_current_user();
  delete_option('click5_sitemap_authentication_token_'.wp_get_current_user()->user_login);

  //unregister

	//unregister_setting( 'click5_sitemap', 'click5_sitemap_post_template_HTML' );
  //unregister_setting( 'click5_sitemap', 'click5_sitemap_is_multiple_time' );

  //$post_types = click5_sitemap_get_post_types();
  /*foreach($post_types as $single_type) {
    $single_type = get_post_type_object($single_type);
    $option_name = 'click5_sitemap_display_'.$single_type->name;
    $option_names = array('click5_sitemap_use_custom_name_'.$single_type->name, 'click5_sitemap_custom_name_text_'.$single_type->name);

    unregister_setting('click5_sitemap', $option_name);
    unregister_setting('click5_sitemap', $option_names[0]);
    unregister_setting('click5_sitemap', $option_names[1]);
    //seo
    unregister_setting('click5_sitemap_seo', 'click5_sitemap_seo_post_type_'.$single_type->name);
  }*/

  //unregister_setting('click5_sitemap_seo', 'click5_sitemap_seo_xml_categories');
  //unregister_setting('click5_sitemap_seo', 'click5_sitemap_seo_xml_tags');
  //unregister_setting('click5_sitemap_seo', 'click5_sitemap_seo_xml_authors');

  //  unregister_setting('click5_sitemap_seo', 'click5_sitemap_seo_xml_images');
   // unregister_setting('click5_sitemap_seo', 'click5_sitemap_seo_xml_docs');
   // unregister_setting('click5_sitemap_seo', 'click5_sitemap_seo_xml_videos');
/*
  $cpt_args = array('public'   => true, '_builtin' => false);
  $cpt_output = 'names';
  $cpt_operator = 'and';
  $cpt_types = get_taxonomies( $cpt_args, $cpt_output, $cpt_operator ); 
  foreach ( $cpt_types  as $cpt_type ) { 
    unregister_setting('click5_sitemap_seo', 'click5_sitemap_seo_xml_'.$cpt_type.'_tax');
  }

  $getCustomCat = click5_sitemap_getCustomCategoriesCustomUrlsHTML();                       
  foreach($getCustomCat as $item) {
    unregister_setting('click5_sitemap_seo', 'click5_sitemap_seo_xml_'.str_replace(' ', '_', $item).'_custom');
  }

  unregister_setting('click5_sitemap', 'click5_sitemap_display_style');
  unregister_setting('click5_sitemap', 'click5_sitemap_display_columns');

  unregister_setting('click5_sitemap', 'click5_sitemap_url_target_blanc');

  unregister_setting('click5_sitemap_blacklist', 'click5_sitemap_blacklisted_array');
  unregister_setting('click5_sitemap_blacklist_seo', 'click5_sitemap_seo_blacklisted_array');


  //seo

  unregister_setting('click5_sitemap_seo', 'click5_sitemap_seo_sitemap_xml');
  unregister_setting('click5_sitemap_seo', 'click5_sitemap_seo_robots_txt');
  unregister_setting('click5_sitemap_seo', 'click5_sitemap_seo_include_sitemap_xml');
  unregister_setting('click5_sitemap_seo', 'click5_sitemap_seo_sitemap_type');
  unregister_setting('click5_sitemap_seo', 'click5_sitemap_seo_auto');
*/

  //order list
  //unregister_setting('click5_sitemap_html', 'click5_sitemap_order_list');
  //unregister_setting('click5_sitemap_html', 'click5_sitemap_order_list2');
  //unregister_setting('click5_sitemap_html', 'click5_sitemap_order_list3');
  //unregister_setting('click5_sitemap_html', 'click5_sitemap_order_list4');
  //unregister_setting('click5_sitemap_html', 'click5_sitemap_order_list_nested');
  //unregister_setting('click5_sitemap_html', 'click5_sitemap_html_pagination_items_per_page');

  //unregister_setting('click5_sitemap_urls', 'click5_sitemap_urls_list');

  //for authentication purposes
  //unregister_setting('click5_sitemap_authentication', 'click5_sitemap_authentication_token_'.wp_get_current_user()->user_login);

  //unregister_setting('click5_sitemap_first_load', 'click5_sitemap_urls_list');
}

//register_uninstall_hook(__FILE__, 'click5_sitemap_uninstallFunction');

add_filter( 'site_status_tests', function($tests){
  if(array_key_exists("direct",$tests)){
    $tests['direct']['test_c5_sitemap'] = array("label" => "Sitemap by Click5", "test"=>"click5_sitemap_healthcheck");
  }
  
  return $tests;
});


function click5_sitemap_healthcheck(){
  $result = array(
    'label'       => 'Sitemap by click5',
    'status'      => 'good',
    'badge'       => array(
        'label' => __( 'Sitemap' ),
        'color' => 'blue',
    ),
    'description' => sprintf(
        '<p>%s</p>',
        __( 'We didn’t find any issues with your XML Sitemap.' )
    ),
    'actions'     => '',
    'test'        => 'click5_sitemap_healthcheck',
);
global $global_yoast_xml;
global $global_aio_seo_xml;
global $global_file_xml;

if($global_yoast_xml){
  $result['label'] = "We have detected an XML Sitemap generated by a different plugin";
  $result['status'] = "critical";
  $result['description'] = sprintf(
    '<p>%s</p><p>%s</p>',
    __( 'We recommend deactivating the XML Sitemap generated by the YOAST SEO plugin in order to prevent any conflicts or SEO errors on your website. These may be reported in Google Search Console and other tools.','sitemap-by-click5'),
    __('<a href="'.admin_url('admin.php?page=wpseo_dashboard#top#features').'">Disable Now</a>')
  );
}else if($global_aio_seo_xml){
  $result['label'] = "We have detected an XML Sitemap generated by a different plugin";
  $result['status'] = "critical";
  $result['description'] = sprintf(
    '<p>%s</p><p>%s</p>',
    __( 'We recommend deactivating the XML Sitemap generated by the All In One SEO plugin in order to prevent any conflicts or SEO errors on your website. These may be reported in Google Search Console and other tools.','sitemap-by-click5'),
    __('<a href="'.admin_url('admin.php?page=aioseo-sitemaps#/general-sitemap').'">Disable Now</a>')
  );
}else if($global_file_xml){
  $result['label'] = "We have detected an XML Sitemap generated by a different plugin";
  $result['status'] = "critical";
  $result['description'] = sprintf(
    '<p>%s</p>',
    __( 'We have detected an XML Sitemap generated by a different plugin. We recommend deactivating the other sitemap in order to prevent any conflicts or SEO errors on your website. These may be reported in Google Search Console and other tools.','sitemap-by-click5')
  );
}


return $result;
}



function click5_sitemap_plugin_deactivated()
{
  
  $robots_txt = ABSPATH.'/robots.txt';
  $robots_backup_txt = ABSPATH.'/robots-click5-backup.txt';
  if(file_exists($robots_backup_txt)) {
    rename($robots_backup_txt,  ABSPATH.'/robots.txt');
  }
}
register_deactivation_hook( __FILE__, "click5_sitemap_plugin_deactivated");


















