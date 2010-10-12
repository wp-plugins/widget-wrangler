<?php
/*
 * TODO:
 *   Access Control
 *   Auto Paragraph?
 *   Maintain Line Breaks?
 */
function ww_edit_settings_page()
{
  //print_r($wp_roles);
  $settings = ww_get_settings();
  //print_r($settings);
  if($settings['capabilities'] == 'simple') { $simple_checked =  "checked"; }
  if($settings['capabilities'] == 'advanced') { $adv_checked = "checked"; } 
  
  /*
  global $wp_roles;
  if($settings['capabilities'] == 'roles') {
    $role_checked =  "checked";
    foreach($settings['roles'] as $role => $value)
    {
      if ($value == "on")
      {
        // make a variable as the role name and st it to a value
        $var = $role."_checked";
        $$var = "checked";
      }
    }
  }
  */
  ?>
    <div class='wrap'>
      <h2>Widget Wrangler Settings</h2>
      <form action="edit.php?post_type=widget&page=ww-settings&ww-settings-action=save&noheader=true" method="post">
        <h3>Capabilities </h3>
        <div class="ww-settings-cap">
          <p> 
            <label>
              <input name="settings[capabilities]" type="radio" value="simple" <?php print $simple_checked; ?> />
              <strong>Simple</strong>:  Widgets can be Created and Edited by anyone who can edit Posts.  Anyone who can edit a Page can change the Widgets displayed on that Page.
            </label>
          </p>
          <hr />
          
          <!-- p>
            <label>
              <input name="settings[capabilities]" type="radio" value="roles" <?php print $role_checked; ?> />
              <strong>Per Role</strong>: Check which roles should be able to edit Widget placement on pages.</label>
              Only roles checked below can access widget placement, sidebars defaults, cloning, and settings.
              <br />
              <em>Unchecked Roles who can create a Post will still be able to create and edit their own Widgets, but they will not be able to control widget placement on Pages.</em>
          </p>
          <ul class="ww-settings-roles">
            <?php
              foreach ($wp_roles->role_names as $key => $name )
              {
                // dynamically create variable name for ${role}_checked value
                $this_checked = $key."_checked";
                ?>
                  <li>
                    <label>
                      <input name="settings[roles][<?php print $key; ?>]" type="checkbox" <?php print $$this_checked; ?> /> <?php print $name; ?>
                    </label>
                  </li>    
                <?php
              }
            ?>
          </ul>
          <hr / -->
          
          <p>
            <label>
              <input name="settings[capabilities]" type="radio" value="advanced" <?php print $adv_checked; ?> />
              <strong>Advanced</strong>:  Change the capability_type for this post_type.
            </label>
            This is primarily for incorporating third party permission systems. <br />
            A simple use of this setting would be to change the Capability Type to 'page'.  This would make it so that only users who can create and edit pages may create and edit widgets.
            <br />
          </p>
          <label><input name="settings[advanced]" type="text" size="20" value="<?php print $settings['advanced']; ?>"/> Capability Type</label> 
          <br />
        </div>  
        <input type="submit" value="Save" />
      </form>
    </div>
  <?php
}


function ww_save_settings($post)
{
  //print_r($post['settings']);
  $settings = serialize($post['settings']);
 
  // adjust the roles
  /*
  global $wp_roles;
  if ($post['settings']['capabilities'] == 'roles')
  { 
    foreach($wp_roles->role_names as $role => $name)
    {
      $role_object = get_role($role);
      
      if ($post['settings']['roles'][$role] == "on")
      {
        $role_object->add_cap('manage_widgets');
      }
      else
      {
        $role_object->remove_cap('manage_widgets');
      }
    }
  }
  */
  
  update_option("ww_settings", $settings);
}
