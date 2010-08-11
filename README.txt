=== Widget Wrangler ===
Contributors: daggerhart
Tags: widget, admin, widgets, administration, sidebar
Requires at least: 3
Tested up to: 3.0.1
Stable tag: trunk

A way to manage widgets on a page by page basis. Widgets as post type.

== Description ==

Widget Wrangler gives the wordpress admin a clean interface for managing widgets on a page by page basis.
It also provides widgets as a post type, and the ability to clone existing wordpress widgets.
Has the ability to support multiple 'sidebars' (groups of widgets)

== Installation ==

1. Upload `widget-wrangler` to the `/wp-content/plugins/` directory
1. Activate the plugin through the `Plugins` menu in WordPress
1. Add a new sidebar under the Widgets menu
1. Begin adding widgets under the Widgets menu
1. Set default widgets on the defaults
1. Add '<?php ww_dynamic_sider('name_of_sidebar'); ?> to your template

== Frequently Asked Questions ==

= How do I display my widgets? =

You must edit your template files and find any instance of the dynamic_sidebar() function.  Replace these functions with ww_dynamic_sidebar('name_of_sidebar')

= How can I control the widget's template (HTML)? =

In the widget-wrangler directory there is a file named 'widget-template.php'.  Copy this file to your theme's root directory and rename it 'widget.php'.  You can edit the HTML in the file to have widgets defaultly appear as you want.

= Can I use existing wordpress widgets? = 

Some of them.  With this plugin I plan on focusing on the post-Wordpress-3.0 users.  Widgets programmed for older versions of wordpress will likely not work.

= What does it mean to Clone a widget? =

When you clone a wordpress widget, it creates a new widget post in the Widget Wrangler system with the settings for that widget pre-filled in the Advanced Parsing area of the new widget.


== Screenshots ==

1. Widget Page Manager
2. Individual Widget
3. Cloned Wordpress Widget

== Changelog ==
= 1.0beta =
Initial Release

== Upgrade Notice ==

= 1.0beta =
