=== Comment SPAM Wiper ===
Contributors: intermod
Donate link: http://www.spamwipe.com
Tags: csw, comments, spam, wiper, comment, block, blog
Requires at least: 3.0
Tested up to: 3.4
Stable tag: 1.2.1
License: GPLv2 or later

Comment SPAM Wiper is a distributed solution for fighting comment SPAM (aka blog SPAM) that automatically filters the SPAM comments.

== Description ==

Comment SPAM Wiper checks your comments against the CSW web service to see if they are SPAM or HAM.
If a SPAM comment is detected it is automatically moved to SPAM folder. If a SPAM comment is not detected and you manually
mark it as SPAM, Comment SPAM Wiper will learn and in the future it will detect it as SPAM. CSW learns and improves detecting blog SPAM as it goes.

You will need an API Key (http://www.spamwipe.com/signup.html) to use it.  The API Keys are FREE of charge.

== Installation ==

1. Download plugin and unzip.
2. Upload the plugin file to your WordPress plugins directory inside of wp-content.
3. Activate it from the plugins menu inside of WordPress.
4. Enter your API Key on the plugin settings page (http://www.spamwipe.com/signup.html).

== Frequently Asked Questions ==

= After I signed-up and entered my API Key, a message appear saying "Invalid API Key". What am I doing wrong? =

Make sure you enter the Site URL in the format: http://www.site.com. The "http://" part in the URL is mandatory.

Another thing to keep in mind: each API Key is issued for a specific domain. If you try to use the API Key for a different domain that the one specified in the sign-up form it won't work. So, make sure that you use the API Key on the website you specified.

= Where can I send bugs? =

You can send us bugs at bugs@spamwipe.com . We will do our best to fix them as soon as possible.

== License and Warranty ==

The Comment SPAM Wiper plugin for WordPress is open source and 
is licensed under the GPLv2.

This program is distributed in the hope that it will be
useful, but WITHOUT ANY WARRANTY; without even the implied
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE. See either the GNU General Public License for more 
details.

== Changelog ==

= 1.2.1 =
* Fixed auto spam protection

= 1.2.0 =
* Statistics authentication fixed
* Introduced paid plans notifications

= 1.1.0 =
* Added extra protection for automated spam posting

= 1.0.0 =
* Fixed CURL problem in PHP 5.2
* Added per site statistics

= 0.9.7 =
* Added reminder for API Keys

= 0.9.6 =
* Added latest news in dashboard
* Pass comment type parameter to API
* Add protected copyright in footer

= 0.9.5 =
* Fixed SDK to work without PHP CURL extension

= 0.9.4 =
* Fixed SDK connection problem
* Modified readme.txt

= 0.9.3 =
* Fixed SPAM recheck

