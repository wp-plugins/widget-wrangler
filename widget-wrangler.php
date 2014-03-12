<?php
/*
Plugin Name: Widget Wrangler
Plugin URI: http://www.widgetwrangler.com
Description: Widget Wrangler gives the wordpress admin a clean interface for managing widgets on a page by page basis. It also provides widgets as a post type, the ability to clone existing wordpress widgets, and granular control over widgets' templates.
Author: Jonathan Daggerhart
Version: 1.5.3

Author URI: http://www.websmiths.co
License: GPL2
*/
/*  Copyright 2010  Jonathan Daggerhart  (email : jonathan@daggerhart.com)
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
define('WW_PLUGIN_DIR', dirname(__FILE__));
define('WW_PLUGIN_URL', get_bloginfo('wpurl')."/wp-content/plugins/widget-wrangler");

// versioning for now
define('WW_VERSION', 1.5);

// template wrangler 2+
include WW_PLUGIN_DIR.'/template-wrangler.inc';

// widget display functions: templating, ww_the_widget, etc
include WW_PLUGIN_DIR.'/common/widget-display.inc';

// add the widget post type class
include WW_PLUGIN_DIR.'/common/ww-widget-class.php';

// include WP standard widgets for sidebars
include WW_PLUGIN_DIR.'/common/ww-sidebars-widget.php';

// Initiate the plugin
add_action( 'init', 'Widget_Wrangler_Init');
add_action( 'admin_menu', 'ww_menu');

// HOOK IT UP TO WORDPRESS
add_action( 'admin_init', 'ww_admin_init' );
add_shortcode('ww_widget','ww_single_widget_shortcode');

/*
 * Function for initializing the plugin
 */
function Widget_Wrangler_Init() {
  global $ww;
  $ww = new Widget_Wrangler();
}

/*
 * Display the plugin on admin screen
 */
function ww_admin_init()
{
  // include all admin functions
  include WW_PLUGIN_DIR.'/admin/widget-wrangler.admin.php';
  include WW_PLUGIN_DIR.'/admin/ww-clone.php';
  //include WW_PLUGIN_DIR.'/ww-corrals.php';
  include WW_PLUGIN_DIR.'/admin/ww-settings.php';
  include WW_PLUGIN_DIR."/admin/ww-defaults.php";
  include WW_PLUGIN_DIR.'/admin/ww-postspage.php';
  include WW_PLUGIN_DIR.'/admin/ww-sidebars.php';
  
  // handle display of sortable widgets admin panel
  ww_display_admin_panel();
  
  add_action( 'save_post', 'ww_save_post' );
  add_action( 'admin_head', 'ww_adjust_css');
  add_action( 'admin_head', 'ww_admin_css');
}

/*
 * All my hook_menu implementations
 */
function ww_menu()
{
  $clone    = add_submenu_page( 'edit.php?post_type=widget', 'Clone WP Widget', 'Copy WP Widget',  'manage_options', 'ww-clone',    'ww_clone_page_handler');
  $defaults = add_submenu_page( 'edit.php?post_type=widget', 'Default Widgets', 'Default Widgets', 'manage_options', 'ww-defaults', 'ww_defaults_page_handler');
  // only show postspage widget setting if post page is the front page
  if(get_option('show_on_front') == 'posts'){
    $postspage= add_submenu_page( 'edit.php?post_type=widget', 'Posts Page', 'Posts Page Widgets', 'manage_options', 'ww-postspage','ww_postspage_page_handler');
  }
  $sidebars = add_submenu_page( 'edit.php?post_type=widget', 'Widget Sidebars', 'Corrals (Sidebars)', 'manage_options', 'ww-sidebars', 'ww_sidebars_page_handler');
  $settings = add_submenu_page( 'edit.php?post_type=widget', 'Settings', 'Settings', 'manage_options', 'ww-settings', 'ww_settings_page_handler');
  //$debug    = add_submenu_page( 'edit.php?post_type=widget', 'Debug Widgets', 'Debug', 'manage_options', 'ww-debug', 'ww_debug_page');
  add_action( "admin_print_scripts-$sidebars", 'ww_sidebar_js' );
}

/*
 * for whatever.
 */
function ww_debug_page(){
  /*/global $wp_registered_widgets, $wp_registered_widget_controls, $wp_registered_widget_updates, $_wp_deprecated_widgets_callbacks;
  //global $wp_widget_factory,$wp_registered_widgets, $wpdb;
  //global $wp_filter;
  // */
}
/* end page handling */

/*
 * Returns all published widgets
 * @return array of all widget objects
 */
function ww_get_all_widgets()
{
  global $wpdb;
  $query = "SELECT `ID` FROM
              ".$wpdb->prefix."posts
            WHERE
              post_type = 'widget' AND
              post_status = 'publish'";
  $results = $wpdb->get_results($query);
  
  $widgets = array();
  $i=0;
  $total = count($results);
  while($i < $total)
  {
    $widgets[$i] = ww_get_single_widget($results[$i]->ID);
    $i++;
  }
  return $widgets;
}
/*
 * Retrieve and return a single widget by its ID
 * @return widget object
 */
function ww_get_single_widget($post_id, $widget_status = false){
  global $wpdb;
  
  $status = $widget_status ? ("`post_status` = '".$widget_status."' AND") : "";
  
  $query = "SELECT
              `ID`,`post_name`,`post_title`,`post_content`
            FROM
              `".$wpdb->prefix."posts`
            WHERE
              `post_type` = 'widget' AND
              ". $status ."
              `ID` = ".$post_id." LIMIT 1";
  if ($widget = $wpdb->get_row($query)) {

    $widget->widget_meta  = get_post_meta($widget->ID);
    $widget->adv_enabled  = get_post_meta($widget->ID,'ww-adv-enabled',TRUE);
    $widget->adv_template = get_post_meta($widget->ID,'ww-adv-template',TRUE);
    $widget->parse        = get_post_meta($widget->ID,'ww-parse', TRUE);
    $widget->wpautop      = get_post_meta($widget->ID,'ww-wpautop', TRUE);
    $widget->widget_type  = get_post_meta($widget->ID,'ww-widget-type', TRUE);
    if (empty($widget->widget_type)){
      $widget->widget_type = "standard";
    }
    $widget->clone_classname = get_post_meta($widget->ID,'ww-clone-classname', TRUE);
    $widget->clone_instance = get_post_meta($widget->ID,'ww-clone-instance', TRUE);
    
    return $widget;
  }
  return false;
}

/*
 * Retrieve list of sidebars
 * @return array of sidebars
 */
function ww_get_all_sidebars()
{
  if ($sidebars_string = get_option('ww_sidebars')){
    $sidebars_array = unserialize($sidebars_string);
  }
  else{
    $sidebars_array = array('No Corrals Defined');
  }
  return $sidebars_array;
}
/*
 * Output a sidebar
 */
function ww_dynamic_sidebar($sidebar_slug = 'default', $wp_widget_args = array())
{
  // get the post and sidebars
  global $post;
  $sidebars = ww_get_all_sidebars();
  $ww_settings = ww_get_settings();
  $output = '';

  // see if this is the Posts (blog) page
  if(is_home() && (get_option('show_on_front') == 'posts') && $postspage_string = get_option('ww_postspage_widgets')){
    $widgets_array = unserialize($postspage_string);
  }
  // look for post meta data
  else if ($widgets_string = get_post_meta($post->ID,'ww_post_widgets', TRUE)){
    $widgets_array = unserialize($widgets_string);
  }
  // try defaults instead
  else if ($defaults_string = get_option('ww_default_widgets')){
    $widgets_array = unserialize($defaults_string);
  }
  // no widgets in post and no defaults
  else{
    return;
  }
  if (isset($widgets_array[$sidebar_slug]) && is_array($widgets_array[$sidebar_slug]))
  {
    $i = 0;
    $total = count($widgets_array[$sidebar_slug]);

    // custom sorting with callback
    usort($widgets_array[$sidebar_slug],'ww_cmp');
    $sorted_widgets = array_reverse($widgets_array[$sidebar_slug]);
    while($i < $total)
    {
      if($widget = ww_get_single_widget($widgets_array[$sidebar_slug][$i]['id'], 'publish'))
      {
        // include theme compatibility data
        $widget->theme_compat = 0;
        $widget->wp_widget_args = array();
        
        if ($ww_settings['theme_compat']) {
          $widget->theme_compat = 1;
          $widget->wp_widget_args = $wp_widget_args;
      
          // handle output widget classes
          $search = 'widget-wrangler-widget-classname';
          $replace = 'ww_widget-'.$widget->post_name.' ww_widget-'.$widget->ID;
          $widget->wp_widget_args['before_widget'] = str_replace($search, $replace, $widget->wp_widget_args['before_widget']);          
        }
        $output.= ww_theme_single_widget($widget);
      }
      $i++;
    }
  }

  print $output;
}
/*
 * Shortcode support for all widgets
 */
function ww_single_widget_shortcode($atts) {
  $short_array = shortcode_atts(array('id' => ''), $atts);
  extract($short_array);
  if ($widget = ww_get_single_widget($id, 'publish')){
    return ww_theme_single_widget($widget);
  }
  return '';
}

/*
 * Get the Widget Wrangler Settings
 * @return settings array
 */
function ww_get_settings()
{
  if ($settings = get_option("ww_settings")){
    $settings = unserialize($settings);
  }
  else{
    ww_settings_set_default();
    $settings = ww_get_settings();
  }

  return $settings;
}
/*
 * Default settings
 */
function ww_settings_set_default()
{
  $settings["capabilities"] = "simple";
  $settings["post_types"]["page"] = "page";
  $settings["post_types"]["post"] = "post";
  $settings["exclude_from_search"] = 1;
  $settings["theme_compat"] = 1;
  
  update_option("ww_settings", serialize($settings));
}


/*
 * Activation hook
 */
function ww_plugin_activation(){
  // check version
  if (!get_option('ww_version')){
    // first time install
    ww_settings_set_default();
    add_option('ww_version', WW_VERSION);
  }
  else if (get_option('ww_version') < WW_VERSION){
    //ww_upgrade_core();
    // upgrade
    update_option('ww_version', WW_VERSION);
  }
}
register_activation_hook(__FILE__, 'ww_plugin_activation');

/*
 * Upgrade
 */
function ww_upgrade_core(){}

/* ==================================== HELPER FUNCTIONS ===== */
/*
 * Helper function for making sidebar slugs
 */
function ww_make_slug($string){
  return stripcslashes(preg_replace('/[\s_\'\"]/','_', strtolower(strip_tags($string))));
}
/*
 * usort callback. I likely stole this from somewhere.. like php.net
 */
function ww_cmp($a,$b) {
  if ($a['weight'] == $b['weight']) return 0;
  return ($a['weight'] < $b['weight'])? -1 : 1;
}
// recursive array search
function ww_array_searchRecursive( $needle, $haystack, $strict=false, $path=array() )
{
  if( !is_array($haystack) ) {
    return false;
  }
  foreach( $haystack as $key => $val ) {
    if( is_array($val) && $subPath = ww_array_searchRecursive($needle, $val, $strict, $path) ) {
        $path = array_merge($path, array($key), $subPath);
        return $path;
    } elseif( (!$strict && $val == $needle) || ($strict && $val === $needle) ) {
        $path[] = $key;
        return $path;
    }
  }
  return false;
}