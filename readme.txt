=== Streakfire ===
Contributors: merkushin
Tags: writing, streak, habit, productivity, motivation
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Track your writing streak: see how many days in a row you have published a post, right above your Posts list.

== Description ==

Streakfire turns publishing into a habit. Open **Posts** in your dashboard and a panel shows:

* **Current run**: how many days in a row you have published at least one post.
* **Last published**: the date of your latest post.
* **Next milestone**: 3, 7, 14, 30, 50 and 100 days, then every 25 days, with a progress bar.
* Whether you have already published today, or still need to post to keep the streak going.

A streak stays alive until the end of the day after your last post, so you always have a full day to keep it going. Days follow your site's timezone, and several posts on the same day count as one day.

There is nothing to configure. The panel only appears on the Posts list screen, each user can hide it under **Screen Options**, and the plugin loads nothing on the front end of your site.

= Translations =

Streakfire ships with translations for Chinese (Simplified), Dutch, French, German, Italian, Japanese, Polish, Portuguese (Brazil), Portuguese (Portugal), Russian, Spanish, Turkish and Ukrainian. You can help translate it on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/streakfire/).

= Privacy =

Streakfire does not collect, store or send any personal data. It reads the dates of your published posts and caches the result in a transient, which is removed when you delete the plugin. If you hide the panel, that choice is saved in WordPress' own per-user screen settings.

= Source code =

Development happens on [GitHub](https://github.com/merkushin/wpstreak), including the uncompiled CSS and JavaScript and the build instructions. Bug reports and pull requests are welcome.

== Installation ==

1. In your dashboard, go to **Plugins → Add New Plugin** and search for "Streakfire", or upload the plugin zip.
2. Activate the plugin.
3. Go to **Posts** to see your streak.

== Frequently Asked Questions ==

= What counts as a day? =

A calendar day in your site's timezone (**Settings → General → Timezone**) on which at least one post was published.

= Do scheduled, draft or private posts count? =

No. Only published posts count. A scheduled post counts on the day it is published.

= Do pages or custom post types count? =

No, only regular posts.

= I published yesterday but not today. Is my streak lost? =

No. The streak stays alive until the end of today. Publish a post today to extend it.

= Who can see the panel? =

Anyone who can open the Posts list in the dashboard.

= Can I hide the panel? =

Yes. On the Posts screen, open **Screen Options** at the top right and uncheck **Writing streak panel**. The choice is saved for your user only; check the box again to bring the panel back.

== Screenshots ==

1. The writing streak panel above the Posts list.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
