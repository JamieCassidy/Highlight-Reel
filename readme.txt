=== Plugin Name ===
Contributors: JamieCassidy
Tags: dribbble
Requires at least: 3.0
Tested up to: 3.5.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Highlight Reel is a simple Wordpress plugin that enables you to easily display your latest Dribbble shots on your website.

== Installation ==

Installing and using Highlight Reel couldn't be simpler. Just follow these steps:

1. Upload the `highlight-reel` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Once activated, you will see a notice to enter your username. Enter your username in the Plugin settings area and hit update.

Once your plugin is installed you can load your Dribbble feed either by using the Highlight Reel shortcode or by using the built in template tag.

Shortcode -
Using the Highlight reel shortcode is the easiest method of loading your feed to your website. Simply create a new page for your feed or use an existing one and type the following: `[highlight-reel]`

Hit publish/update and visit your page. You should now see your Dribbble feed.

Template Tag - 
Alternatively, you can embed your Dribbble feed right into your theme files using the provided Template Tag. Simply call the below function wherever you wish to display your Dribbble feed.

`<?php highlight_reel(); ?>`