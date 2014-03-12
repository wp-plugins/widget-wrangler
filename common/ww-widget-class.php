<?php

/*
 * Widget Wrangler custom_post_type class for widget post type
 */
class Widget_Wrangler {
  // public meta fields
  var $meta_fields = array("ww-adv-enabled","ww-parse","ww-wpautop","ww-adv-template");
  var $settings = array();
  var $capability_type;
  var $widget_type;
  var $post_id;
  var $widget_meta = array();
	
  /*
   * Constructor, build the new post type
   */
  function Widget_Wrangler()
  {
    // Editing specific widget
		if (isset($_GET['post'])){
			$pid = $_GET['post'];
		}
		else if (isset($_POST['post_ID'])){
			$pid = $_POST['post_ID'];
		}
		// get the type
		if (isset($pid)){
			$post_type = get_post_type($pid);
		}
    
		// load some extra data if we are on a WW widget post_type
		if (isset($post_type) && $post_type == 'widget')
		{
			// this widget has been saved before, we now $pid exists
			$this->post_id = $pid;
      $this->widget_meta = get_post_meta($pid);
      $widget_type = get_post_meta($pid, 'ww-widget-type', true);
			$this->widget_type = ($widget_type) ? $widget_type : "standard";
      
			// handle editing page for all widgets
			$supports = array(
				'title',
				'excerpt',
        'custom-fields'
			);
			
			// Clones do not need an editor
			if($this->widget_type == 'standard'){
				$supports[] = 'editor';
			}
    }
    // this is a new widget
    else
    {
      $supports = array(
        'title',
        'excerpt',
        'editor',
        'custom-fields'
      );
    }
    
		$settings = ww_get_settings();
    // allow for custom capability type
    $capability_type = ($settings['capabilities'] == "advanced" && isset($settings['advanced'])) ? $settings['advanced'] : "post";
    
		// custom post type labels
		$labels = array(
      'name' => _x('Widget Wrangler', 'post type general name'),
      'all_items' => __('All Widgets'),
      'singular_name' => _x('Widget', 'post type singular name'),
      'add_new' => _x('Add New Widget', 'widget'),
      'add_new_item' => __('Add New Widget'),
      'edit_item' => __('Edit Widget'),
      'new_item' => __('New Widget'),
      'view_item' => __('View Widget'),
      'search_items' => __('Search Widgets'),
      'menu_icon' => WW_PLUGIN_DIR.'/admin/images/icon-wrangler.png',
      'not_found' =>  __('No widgets found'),
      'not_found_in_trash' => __('No widgets found in Trash'), 
      'parent_item_colon' => '',
    );
		
    // Register custom post types
    register_post_type('widget', array(
      'labels' =>$labels,
      'public' => true,
			//'publicly_queryable' => true,?
      'exclude_from_search' => (isset($settings['exclude_from_search']) && $settings['exclude_from_search'] == 0) ? false : true, 
      'show_in_menu' => true,
      'show_ui' => true, // UI in admin panel
      '_builtin' => false, // It's a custom post type, not built in
      '_edit_link' => 'post.php?post=%d',
      'capability_type' => $capability_type,
      'hierarchical' => false,
      'rewrite' => array("slug" => "widget"), // Permalinks
      'query_var' => "widget", // This goes to the WP_Query schema
      'supports' => $supports,
      'menu_icon' => WW_PLUGIN_URL.'/images/wrangler_icon.png'
    ));

    add_filter("manage_edit-widget_columns", array(&$this, "edit_columns"));
    add_action("manage_posts_custom_column", array(&$this, "custom_columns"));

    // Admin interface init
    add_action("admin_init", array(&$this, "admin_init"));
    //add_action("template_redirect", array(&$this, 'template_redirect'));

    // Insert post hook
    add_action("wp_insert_post", array(&$this, "wp_insert_post"), 10, 2);
  }
  /*
   * Custom columns for the main Widgets management page
   */
  function edit_columns($columns)
  {
    $columns = array(
      "cb" => "<input type=\"checkbox\" />",
      "title" => "Widget Title",
      "ww_type" => "Type",
      "ww_description" => "Description",
      "ww_rewrite_output" => "Rewrite Output",
			"ww_shortcode" => "Shortcode",
    );

    return $columns;
  }
  /*
   * Handler for custom columns
   */
  function custom_columns($column)
  {
    global $post;
    
    switch ($column){
      case "ww_type":
        $widget_type = get_post_meta($post->ID, 'ww-widget-type', true);
        print ($widget_type) ? $widget_type : "standard";
        break;
      case "ww_description":
        the_excerpt();
        break;
      case "ww_rewrite_output":
        $rewrite = get_post_meta($post->ID, 'ww-adv-enabled', true);
        print ($rewrite) ? $rewrite : "&nbsp;";
        break;
			case "ww_shortcode":
				print "[ww_widget  id=".$post->ID."]";
				break;
    }
  }

  /*
   * When a post is inserted or updated
   */
  function wp_insert_post($post_id, $post = null)
  {
    // Check if this call results from another event, like the "Quick Edit" option
    // http://wordpress.org/support/topic/quickedit-deletes-code-in-advanced-parsing?replies=1
		if (isset($_REQUEST['_inline_edit'])) { return; }

    if ($post->post_type == "widget")
    {
      $widget_type = get_post_meta($post_id, 'ww-widget-type', true);
      
      // Loop through the public meta fields ($this->meta_fields) for $_POST data
      foreach ($this->meta_fields as $key)
      {
        $value = @$_POST[$key];
        if (empty($value))
        {
         delete_post_meta($post_id, $key);
         continue;
        }

        // If value is a string it should be unique
        if (!is_array($value))
        {
          // Update meta
          update_post_meta($post_id, $key, $value);
        }
        else
        {
          // If passed along is an array, we should remove all previous data
          delete_post_meta($post_id, $key);

          // Loop through the array adding new values to the post meta as different entries with the same name
          foreach ($value as $entry){
            add_post_meta($post_id, $key, $entry);
          }
        }
      }
      
      // update clone instance
      if ($widget_type == "clone"){
        $instance = ww_make_clone_instance($_POST);
        $old_instance = get_post_meta($post_id, 'ww-clone-instance', true);
        
        // let the widget update itself
        $classname = $_POST['ww-data']['clone']['clone-class'];
        if (class_exists($classname)) {
          $wp_widget = new $classname;
          $instance = $wp_widget->update($instance, $old_instance);
        }
        
        $instance['ID'] = $post_id;
        if (isset($_POST['ww-data']['clone']['hide_title'])){
          $instance['hide_title'] = $_POST['ww-data']['clone']['hide_title'];
        }
        
        delete_post_meta($post_id, 'ww-clone-instance');
        add_post_meta($post_id, 'ww-clone-instance', $instance);
      }
    }
  }
  /*
   * Add meta box to widget posts
   */
  function admin_init()
  {
    // Clone Instance
    if ($this->widget_type == "clone") {
      add_meta_box("ww-clone-instance", "Widget Form", array(&$this, "meta_clone_instance"), "widget", "normal", "high");
    }
    else {
      // Custom meta boxes for the edit widget screen
      add_meta_box("ww-parse", "Options", array(&$this, "meta_parse"), "widget", "normal", "high");
    }
    add_meta_box("ww-adv-help", "Advanced Help", array(&$this, "meta_advanced_help"), "widget", "normal", "high");
    add_meta_box("ww-widget-preview", "Widget Preview", array(&$this, "meta_widget_preview"), "widget", "side", "default");
    
  }
	
  /*
	 * Clone Instance
	 */ 
  function meta_clone_instance()
  {
    // get widget factory and post data
    global $wp_widget_factory;

    $wp_widget_classname = get_post_meta($this->post_id,'ww-clone-classname', true);
    $wp_widget_instance = get_post_meta($this->post_id, 'ww-clone-instance', true);
    
    if($wp_widget_classname)
    {
      // create instance form
      ob_start();
        $wp_widget = new $wp_widget_classname;
        $wp_widget->form($wp_widget_instance);
      $instance_form = ob_get_clean();
        
      $hide_title_checked = (isset($wp_widget_instance['hide_title'])) ? 'checked="checked"' : '';
      ?>
        <label>
          <input type="checkbox" name="ww-data[clone][hide_title]" <?php print $hide_title_checked; ?> /> - Hide the Widget's title on display
        </label>
        <div class="ww-clone-form">
          <?php print $instance_form; ?>
        </div>
				<input type="hidden" name="ww-data[clone][clone-class]" value="<?php print $wp_widget_classname; ?>" />
        <input type="hidden" name="ww-data[clone][clone-instance]" value="Placeholder" />
      <?php
    }
  }
	
  // Admin preview box
  function meta_widget_preview(){
    if ($this->post_id)
    {
      $widget = ww_get_single_widget($this->post_id);
			$preview = ww_theme_single_widget($widget);
			$preview_balance = balanceTags($preview, true);
      ?>
        <div id="ww-preview">
          <p><em>This preview does not include your theme's CSS stylesheet.</em></p>
          <?php	print $preview_balance; ?>
        </div>
				<?php if ($preview != $preview_balance) { ?>
					<div style="border-top: 1px solid #bbb; margin-top: 12px; padding-top: 8px;">
						<span style="color: red; font-style: italic;">Your widget may contain some broken or malformed html.</span> Wordpress balanced the tags in this preview in an attempt to prevent the page from breaking, but it will not do so on normal widget display.
					</div>
				<?php } ?>
      <?php
    }
  }

  // Admin post meta contents
  function meta_parse()
  {
    // post custom data
    $custom = get_post_custom($this->post_id);
    $parse = isset($custom["ww-parse"]) ? $custom["ww-parse"][0] : NULL;
    $adv_enabled = isset($custom["ww-adv-enabled"]) ? $custom["ww-adv-enabled"][0] : NULL;
    $adv_template = isset($custom["ww-adv-template"]) ? $custom["ww-adv-template"][0] : NULL;
    $wpautop = isset($custom["ww-wpautop"]) ? $custom["ww-wpautop"][0] : NULL;

    // default to checked upon creation
    $adv_checked = (isset($adv_enabled)) ? 'checked="checked"' : '';
    $adv_template_checked = (isset($adv_template)) ? 'checked="checked"' : '';
    // checked on new widget creation
    $wpautop_checked = (isset($wpautop) || (!isset($_GET['action']) && (isset($_GET['post_type']) && $_GET['post_type'] == 'widget'))) ? 'checked="checked"' : '';

    ?><div id="ww-template">
        <input type="hidden" name="quickeditfix" value="true" />
        <div class="ww-widget-postid">Post ID<br/><span><?php print $this->post_id;?></span></div>
        <div>
          <label><input type="checkbox" name="ww-wpautop" <?php print $wpautop_checked; ?> /> Automatically add Paragraphs to this Widget's Content</label>
        </div>
        <div>
          <h4>Advanced Parsing</h4>
          <div id="ww-advanced-field">
            <label><input id="ww-adv-parse-toggle" type="checkbox" name="ww-adv-enabled" <?php print $adv_checked; ?> /> Enable Advanced Parsing</label>
            <div id="ww-advanced-template">
              <label><input id="ww-adv-template-toggle" type="checkbox" name="ww-adv-template" <?php print $adv_template_checked; ?> /> Template the Advanced Parsing Area</label> <em>(Do not use with Cloned Widgets.  Details below)</em>
            </div>
          </div>
          <textarea name="ww-parse" cols="40" rows="16" style="width: 100%;"><?php print htmlentities($parse); ?></textarea>
        </div>      
      </div>
    <?php
  }
	
	/*
	 * Advanced Help
	 */
	function meta_advanced_help()
	{
		if ($widget = ww_get_single_widget($this->post_id)){
      $args = array(
        'widget' => $widget, // needed in final template
        'widget_id' => $widget->ID,
        'post_name' => $widget->post_name,
        'widget_type' => $widget->widget_type,
        //'corral_id' => (isset($widget->widget_data) && isset($widget->widget_data['preview-corral-id'])) ? $widget->widget_data['preview-corral-id'] : 0,
        'tw_action'  => 'find_only',
      );
      
      $a = theme('ww_widget', $args);
    
      $suggestions = "";
      if (isset($a['suggestions'])){
        foreach($a['suggestions'] as $i => $suggestion) {
          // we can't detect corral here
          //$suggestion = str_replace("corral_0", "corral_[corral_id]", $suggestion);
          $class = ($suggestion == $a['found_suggestion']) ? "ww-template-suggestion found" : "ww-template-suggestion";
          $suggestions.= "<li class='$class'>".$suggestion."</li>";
        }
      }
    }
			?>
      <div class="adv-parse-description">
        <div id="ww-advanced-help">
          <div class="ww-advanced-help-description adv-parse-description">
            <?php
              //  only show adv parsing help on standard
              if ($this->widget_type != "clone"){ ?>
                <h4>In the Advanced Parsing area you can:</h4>
                <ul>
                  <li>Use PHP tags ( &lt;?php and ?&gt; are required )</li>
                  <li>Use {{title}} or $widget->post_title to insert the widget's title</li>
                  <li>Use {{content}} or $widget->post_content to insert the widget's content</li>
                  <li>Access the $widget object for more widget data (see provided template for examples)</li>
                  <li>Access the $post object for data concerning the page being displayed (see provided template for examples)</li>
                </ul>
                <h4>Templating Advanced Parsed Widgets</h4>
                <ul>
                  <li>To template an advanced parsed widget you must return an associative array with a title and content string.</li>
                  <li>Example: &lt;?php return array("title" => "The Widget's Title", "content" => "The Widget's Content"); ?&gt;</li>
                  <li><strong>All Cloned widgets are already templated so this setting should not be used for them.</strong></li>
                  <li><strong>If you are unclear on what this means, it is highly recommended that you avoid this option.</strong></li>
                </ul>
                <?php
              }
              
              if (isset($suggestions)) { ?>
                <h4>Template Suggestions</h4>
                <!--<p class="description">Corral specific templates will not be detected here unless you set the "Preview Corral Context" in the preview pane.</p>-->
                <ul><?php print $suggestions; ?></ul>
              <?php
                if (isset($a['found_path']) && $a['found_path'])
                { ?>
                    <h4>Found template location</h4>
                    <div class='ww-found-template-location'><?php print str_replace(ABSPATH, "/", $a['found_path']); ?></div>
                  <?php
                }
                
                // provide some clone instance debugging
                if ($this->widget_type == "clone")
                {
                  $clone_instance = unserialize($this->widget_meta['ww-clone-instance'][0]);
                  ?>
                  <div>
                    <h4>WP_Widget $instance</h4>
                    <div><pre><?php print htmlentities(print_r($clone_instance,1)); ?></pre></div>
                  </div>
                  <?php
                }
              }
            ?>
          </div>
        </div>
      </div>
		<?php
	}  
}
// end widget class
