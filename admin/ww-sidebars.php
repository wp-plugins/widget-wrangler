<?php

/*
 * Sidebar page handler
 */
function ww_sidebars_page_handler()
{
  if(isset($_GET['ww-sidebar-action'])){
    switch($_GET['ww-sidebar-action']){
      case 'insert':
        $new_corral_id = ww_corral_insert($_POST);
        break;
      case 'delete':
        ww_corral_delete($_POST);
        break;
      case 'update':
        ww_corral_update($_POST);
        break;
      case 'sort':
        ww_corral_sort($_POST);
        break;
    }
    wp_redirect(get_bloginfo('wpurl').'/wp-admin/edit.php?post_type=widget&page=ww-sidebars');
  }
  // show sidebars page
  ww_corrals_form();
}

/*
 * Build the form 
 */
function ww_corrals_form()
{
  $sidebars = unserialize(get_option('ww_sidebars'));
  $sorting_items = '';
  ?>
  <div class='wrap'>
    <h2>Widget Corrals</h2>
    <p>
      A <strong>Corral</strong> is an arbitrary group of widgets. Wordpress and preview Widget Wrangler versions call them '<em>sidebars</em>', but they are ultimately not limited by that terminology.
    </p>                           
  <div id='ww-corral-page'>
    <div class="ww-setting-column">
      <h2 class="ww-setting-title">Edit existing Corrals</h2>
      <div class="ww-setting-content">
        <div class='description' style='color:darkred;'>
          Warning! If you change a corral's 'slug', widgets currently assigned to that corral will need to be reassigned.
        </div>
        <ul id='ww-corrals-list'>
        <?php
          //  no corrals
          if (!is_array($sidebars))
          { ?>
            <li>No Corrals defined</li>
            <?php
          }
          // corrals
          else {
            // loop through each sidebar and build edit form
            $i = 1;
            foreach($sidebars as $slug => $sidebar)
            { ?><li class='ww-corral-item'>
                  <div class='widget'>
                    <div class='widget-top'>
                      <div class='widget-title-action'>
                        <div class='widget-action'></div>
                      </div>
                      <h4><?php print $sidebar; ?> (<?php print $slug; ?>)</h4>
                    </div>
                    <div class='widget-inside'>
                      <form action='edit.php?post_type=widget&page=ww-sidebars&ww-sidebar-action=update&noheader=true' method='post'>
                        <p class="ww-top-right-save">
                          <input class='ww-corral-update-submit button button-primary button-large' name='ww-update-submit' type='submit' value='Update' />
                        </p>
                        <p>
                          <label>Name: </label>
                          <input class='ww-text' name='ww-update-sidebar' type='text' value='<?php print $sidebar; ?>' />
                        </p>
                        <p>
                          <label>Slug: </label>
                          <input class='ww-text' name='ww-update-slug' type='text' value='<?php print $slug; ?>' />
                        </p>
                        <input name='ww-update-old-slug' type='hidden' value='<?php print $slug; ?>' />
                      </form>
                      <hr />
                      <form class='ww-delete-sidebar' action='edit.php?post_type=widget&page=ww-sidebars&ww-sidebar-action=delete&noheader=true' method='post'>
                        <input name='ww-delete-slug' type='hidden' value='<?php print $slug; ?>' />
                        <p>
                          <input class='ww-setting-button-bad button button-small' name='ww-delete-submit' type='submit' value='Delete' />
                        </p>
                      </form>
                    </div>
                  </div>
                </li>
                <?php
              // sortable list
              $sorting_items.= "<li class='ww-corral-sort-item'>
                                  <strong>".$sidebar." (".$slug.")</strong>
                                  <input type='hidden' class='ww-corral-weight' name='weight[".$i."]' value='".$slug."' />
                                </li>";
              $i++;
            }
            
          }
        ?>
        </ul>
      </div>
    </div>
    <div class="ww-setting-column">
      <h2 class="ww-setting-title">Create New Corral</h2>
      <div class="ww-setting-content">
        <form action='edit.php?post_type=widget&page=ww-sidebars&ww-sidebar-action=insert&noheader=true' method='post'>
          <p>
            Corral Name: <input name='ww-new-sidebar' type='text' value='' />
          </p>
          <input class='button button-primary button-large' type='submit' value='Create Corral' />
        </form>
      </div>
      <h2 class="ww-setting-title">Sort Corrals</h2>
      <div class="ww-setting-content">
        <form action='edit.php?post_type=widget&page=ww-sidebars&ww-sidebar-action=sort&noheader=true' method='post'>
          <ul id='ww-corrals-sort-list'>
            <?php print $sorting_items; ?>
          </ul>
          <input class='ww-sidebar-sort-submit button button-primary button-large' type='submit' name='ww-sidebars-save' value='Save Order' />
        </form>
      </div>
    </div>
  </div>
  </div>
  <?php
}
/*
 * Handle sorting of sidebars
 */
function ww_corral_sort($posted = array())
{
  $all_sidebars = ww_get_all_sidebars();
  $new_order_array = array();
  $new_order_string = '';
  
  if (is_array($posted['weight']))
  {
    $i = 1;
    $total = count($posted['weight']);
    while($i <= $total)
    {
      $new_order_array[$posted['weight'][$i]] = $all_sidebars[$posted['weight'][$i]];
      $i++;
    }
    $new_order_string = serialize($new_order_array);
    
    update_option('ww_sidebars',$new_order_string);
  }
}
/*
 * Add a new sidebar
 */
function ww_corral_insert($posted = array())
{
  // just in case
  $new_sidebar = strip_tags($posted['ww-new-sidebar']);
  // clean name
  $slug_name = ww_make_slug($new_sidebar);
  
  if ($sidebars_string = get_option('ww_sidebars'))
  {
    $sidebars_array = unserialize($sidebars_string);
  }
  // add new sidebar
  $sidebars_array[$slug_name] = $new_sidebar;
  // encode
  $new_option = serialize($sidebars_array);
  // save
  update_option('ww_sidebars',$new_option);
}
/*
 * Delete a sidebar
 */
function ww_corral_delete($posted = array())
{
  $old_slug = $posted['ww-delete-slug'];
  
  if ($sidebars_string = get_option('ww_sidebars'))
  {
    $sidebars_array = unserialize($sidebars_string);
    unset($sidebars_array[$old_slug]);
    $new_option = serialize($sidebars_array);
  }
  else
  {
    $new_option = '';
  }
  update_option('ww_sidebars', $new_option);
}
/*
 * Update/Edit a sidebar
 */
function ww_corral_update($posted = array())
{
  $update_sidebar = strip_tags($posted['ww-update-sidebar']);
  $update_slug = ww_make_slug($posted['ww-update-slug']);
  $old_slug = $posted['ww-update-old-slug'];
  
  if ($sidebars_string = get_option('ww_sidebars'))
  {
    $sidebars_array = unserialize($sidebars_string);
    // delete old one
    unset($sidebars_array[$old_slug]);
    // add new one
    $sidebars_array[$update_slug] = $update_sidebar;
    // serialize
    $new_option = serialize($sidebars_array);
  }
  else
  {
    $new_option = '';
  }
  update_option('ww_sidebars', $new_option);
}
