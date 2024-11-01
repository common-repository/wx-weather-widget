=== WX Weather Widget ===
Contributors: squaredesign
Tags: widget, weather
Donate link: none
Requires at least: 2.7
Tested up to: 4.0
Stable tag: trunk

WX Weather is a WordPress widget designed to display data from a Personal Weather Station (PWS) in your blog sidebar.

== Description ==

WX Weather displays data from a Personal Weather Station, retrieved from the website [Weather Underground](www.wunderground.com).

to use this for your own Weather Station, you need to submit your data to Weather Underground, and you know your Weather Station code. to find out more about how to do this, visit [Weather Underground's Personal Weather Station Page](http://www.wunderground.com/weatherstation/about.asp).

data is cached locally in your WordPress install, so it only makes one request to Weather Underground every five minutes.

== Installation ==

1. upload the contents of the archive to your /wp-content/plugins/ directory
1. activate the plugin
1. go to Appearance, Widgets and Add the WX Weather Widget to your dynamic sidebar
1. Edit the Widget on the right to add a title, and specify your Weather Station code

== Frequently Asked Questions ==

= theme compatibility =

i have seen a few reports of this plugin throwing errors when certain themes are used. please try a different theme before reporting a problem - if the problem clears up, let me know what theme it was causing an error with and i will try to investigate the conflict. please also note that this plugin requires PHP 5 - if you're using PHP4, the functions used to parse the return data from Wunderground don't exist.

== Screenshots ==

1. add the WX Weather Widget to your sidebar
2. enter a title, and your Weather Station code. choose your unit preference.
3. how the Widget looks in the sidebar - presentation can be altered using the CSS of your theme

== changelog ==

= 0.91 =

* fixed a bug introduced in 0.9 that output the full F+C temp string after the C string (sorry!)
* corrected some formatting to be in line with the wordpress php coding standards: https://make.wordpress.org/core/handbook/coding-standards/php/
* changed some of my string concats to be more readable

= 0.9 =

* updating some of the URLs for wunderground
* starting work on abstracting out the choice to display alternate units or not

= 0.8 =

* accidentally checked in some debug code - fixed

= 0.7 =

* changes at wunderground meant this plugin displayed XML errors when api.wunderground is overloaded; updated to check not only the HTTP status code, but examine the payload itself for valid XML before processing (otherwise it continues to use your cached data)

= 0.6 =

* added the ability to choose Metric or English unit preference
* fixed the display of rainfall data (if it's raining)
* updated readme and plugin info page to reflect PHP5 requirement

= 0.5 =

* added a message to report the use of cached data if timeout has not expired

= 0.4 =

* suppression of php errors when using WP "snoopy" function to retrieve data
* addition of title/mouseover error|success reporting (hover over update time to see status)

== Upgrade Notice ==

= 0.6 =
this version fixes the display of rainfall data, gives you the choice of preferring English or Metric units