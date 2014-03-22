<?php

/*
 * Handles settings page
 */
function ww_settings_page_handler()
{
  if (isset($_GET['ww-settings-action'])){
    switch($_GET['ww-settings-action']){
      case "save":
        ww_settings_save($_POST);
        break;
      case "reset":
        ww_settings_reset_widgets();
        break;
    }
    wp_redirect(get_bloginfo('wpurl').'/wp-admin/edit.php?post_type=widget&page=ww-settings');
  }
  else{
    ww_settings_form();
  }
}

/*
 * TODO:
 *   Access Control
 */
function ww_settings_form()
{
  $settings = ww_get_settings();
  // handle checkboxes
  $simple_checked = ($settings['capabilities'] == 'simple') ? "checked" : ""; 
  $adv_checked = ($settings['capabilities'] == 'advanced') ? "checked" : ""; 
  $advanced_capability = isset($settings['advanced']) ? $settings['advanced'] : "";
  $exclude_from_search_checked = (isset($settings['exclude_from_search']) && $settings['exclude_from_search'] == 1) ? "checked='checked'" : "";
  $theme_compat_checked = (isset($settings['theme_compat']) && $settings['theme_compat'] == 1) ? "checked='checked'" : "";
  
  // Get all extra post types
  $args = array('public' => true, '_builtin' => false);
  $post_types = get_post_types($args,'names','and');
  // Add standard types
  $post_types['post'] = 'post';
  $post_types['page'] = 'page';
  unset($post_types['widget']);
  ksort($post_types);
  
  ?>
    <div class='wrap'>
      <h2>Widget Wrangler Settings</h2>
      <div class="ww-clear-gone">&nbsp;</div>
      <form action="edit.php?post_type=widget&page=ww-settings&ww-settings-action=save&noheader=true" method="post">
        <div class="ww-setting-column">
          <h1>System Settings</h1>
          <p class="description">
            System settings control how Widget Wrangler works
          </p>
          <h2 class="ww-setting-title">Post Types</h2>
          <div class="ww-setting-content">
            <p>
              Type the names of all post types you would like to enable Widget Wrangler on.  Separate each post type with a comma. By default Widget Wrangler is enabled for Pages and Posts (eg. page,post).<br />
              You may not allow Widget Wrangler on widget posts.
            </p>
              <div class="ww-checkboxes">
                <?php
                  // loop through post types
                  foreach ($post_types as $post_type )
                  {
                    $post_type_checked = (in_array($post_type, $settings['post_types'])) ? 'checked="checked"' : '';
                    ?>
                    <label class="ww-checkbox"><input type="checkbox" name="settings[post_types][<?php print $post_type; ?>]" value="<?php print $post_type; ?>" <?php print $post_type_checked; ?> /> - <?php print ucfirst($post_type); ?> </label>
                    <?php
                  }
                ?>
                <div class="ww-clear-gone">&nbsp;</div>
              </div>  
          </div>
          <h2 class="ww-setting-title">Theme Compatibility</h2>
          <div class="ww-setting-content">
            <p>
              <label class="ww-checkbox">
                <input name="settings[theme_compat]" type="checkbox" <?php print $theme_compat_checked; ?> /> - If checked, widgets will include Wordpress sidebar settings for the registered sidebar.  ie, $before_widget, $before_title, $after_title, $after_widget.
              </label>
            </p>
          </div>          
        </div>
        
        <div class="ww-setting-column">
          <h1>Widget post type settings</h1>
          <p class="description">
            Post type settings control the "widget" post type registered by this plugin.
          </p>
          <h2 class="ww-setting-title">Capabilities </h2>
          <div class="ww-setting-content">
            <p> 
              <label>
                <input name="settings[capabilities]" type="radio" value="simple" <?php print $simple_checked; ?> />
                <strong>Simple</strong>:  Widgets can be Created and Edited by anyone who can edit Posts.  Anyone who can edit a Page can change the Widgets displayed on that Page.
              </label>
            </p>
            <hr />
            <p>
              <label>
                <input name="settings[capabilities]" type="radio" value="advanced" <?php print $adv_checked; ?> />
                <strong>Advanced</strong>:  Change the capability_type for this post_type.
              </label>
              This is primarily for incorporating third party permission systems. <br />
              A simple use of this setting would be to change the Capability Type to 'page'.  This would make it so that only users who can create and edit pages may create and edit widgets.
              <br />
            </p>
            <label><input name="settings[advanced]" type="text" size="20" value="<?php print $advanced_capability; ?>"/> Capability Type</label> 
            <br />
          </div>
          <h2 class="ww-setting-title">Exclude from search</h2>
          <div class="ww-setting-content">
            <p>
              <label class="ww-checkbox">
                <input name="settings[exclude_from_search]" type="checkbox" <?php print $exclude_from_search_checked; ?> /> - If checked, widgets will be excluded from search results.
              </label>
            </p>
          </div>          
        </div>
        <p>
          <input class=" button button-primary button-large" type="submit" value="Save Settings" />
        </p>
        
      </form>
      
      <div id="ww-mass-reset">
        <form action="edit.php?post_type=widget&page=ww-settings&ww-settings-action=reset&noheader=true" method="post">
          <h2 class="ww-setting-title">Mass Reset</h3>
          <div class="ww-setting-content">
            <p>
              <span style="color: red;">WARNING!</span>  If you click this button, all pages will lose their widget sidebar and order settings and will fall back on the default settings.</p>
              <input class="button ww-setting-button-bad" type="submit" value="Reset All Widgets to Default" onclick="return confirm('Are you Really sure you want to Reset widget settings on all pages?');" />
            </p>
          </div>
        </form>
      </div>
    </div>
  <?php
}
/*
 * Reset all pages to use the default widget settings
 */
function ww_settings_reset_widgets()
{
  global $wpdb;
  $query = "DELETE FROM `".$wpdb->prefix."postmeta` WHERE `meta_key` = 'ww_post_widgets'";
  $wpdb->query($query);
}
/*
 * Save the Widget Wrangler Settings page
 */
function ww_settings_save($post)
{
	$post['settings']['exclude_from_search'] = (isset($post['settings']['exclude_from_search'])) ? 1 : 0;
	$post['settings']['theme_compat'] = (isset($post['settings']['theme_compat'])) ? 1 : 0;

  // save to wordpress options
  $settings = serialize($post['settings']);
  update_option("ww_settings", $settings);
}
