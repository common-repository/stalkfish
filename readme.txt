=== Stalkfish - Error Monitoring and Activity Log Monitoring ===

Contributors:      mauryaratan, lushkant
Tested up to:      6.3
Requires at least: 5.6
Requires PHP:      7.1
Stable tag:        1.2.1
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html
Tags:              activity log, monitoring, user log, user activity, audit log, tracking, alerts, security

Stalkfish actively tracks error, crashes, and activity log on your WordPress site and sends them to your Stalkfish dashboard.

== Description =

Stalkfish is a no setup plugin that will monitor and log all the site activity, error and crashes without bloating your database. All the errors and activity logs are stored in your Stalfish account, and can be accessed via the Stalkfish dashboard without compromising your site privacy.

Unlike alternative plugins, Stalkfish does not require any configuration, and is compatible with most recent WordPress versions. We provide a dedicated monitoring system for your site, and a dashboard to view and manage activity for all your WordPress sites, in once place.

== Why use an activity logging and monitoring plugin? ==

Don't you hate it when you have no clue when something on your site changed, and you have no idea why? Stalkfish helps you keep track of all your site activity, and you can see what's going on in a single place. This helps you locate when a particular action was performed on your site.

== Benefits of Stalkfish ==
* **Track any activity:** Stalkfish will monitor all the activities on your site, and log it to Stalkfish server with contextual data that will help you locate what has changed.
* **Track errors:** Stalkfish would automatically track all errors on your site and alert you as needed.
* **Share event data:** Easily creating public links that can be shared with others. You may set a range of expiry dates for the link, and the link will be automatically deleted after the expiry date.
* **Get alerts for important events:** Create contextual alerts and get notified when something specific happens on your site.
* **Unlimited sites:** Stalkfish gives you unlimited sites to monitor in a decent dedicated dashboard.

=== Get Started with Stalkfish ===
[Stalkfish](https://stalkfish.com) is a SaaS product, and we offer a free plan for personal use. You can record upto 500 events per month for free, ideal if you're trying the plugin or using it on small sites and still feel secure.

==== What activities are tracked? ====
- **Posts, Pages and Custom Post Types** - Log all the activities/events associated including metadata and custom fields
- **Taxonomies** - Log any and all changes from creation to attachment in posts and CPTs.
- **Comments** - Log all the comment updates from status changes to spamming.
- **Users** - Log all user logins to any profile changes.
- **Media & Library** - Log all the media uploads to attachment in posts and CPTs.
- **Settings** - Log all setting updates from general to privacy.
- **Customizer** - Log any and all WordPress Customizer changes including theme modifications.
- **Plugins and Themes** - Log all theme and plugin installations to uninstalls.
- **Widgets** - Log any and all sidebar changes to widget updates.
- **Classic Editor and Block (Gutenberg) Editor** - Log all the editor updates from post creation to deletion.
- **WordPress Core Updates** - Log Wordpress core updates from manual to auto.
- **Multisite** (Coming Soon) - Log network wide site updates from adding them to removal.

That's not it! We're working around the clock to improve your experience and add more useful activity logging.

= Privacy =
Stalkfish is a SaaS (software as a service) connector plugin that uses a custom API to log all the site activity. We only store event data on Stalkfish server which do not include any sensitive data, and we do not share your data with anyone. We do record user's IP who performs an activity to give you a better insight of action's origin. Respecting your privacy is our top-most priority.

== Installation ==

From your WordPress dashboard

1. **Visit** Plugins > Add New
2. **Search** for "Stalkfish"
3. **Install and Activate** Stalkfish from your Plugins page
4. **Click** on **Stalkfish** in the "Settings" menu item, to setup plugin and start logging.

== Frequently Asked Questions  ==

Thank you for being an early adopter of Stalkfish! If you find any issues, please reach out by visiting the [support forum](https://wordpress.org/support/plugin/stalkfish/) to ask any questions or file feature requests.

= What support is provided? =

Limited free support is provided through the WordPress.org support forums. Paying customers can get priority support from their Stalkfish account.

== Screenshots ==

1. Sites Dashboard screen
2. Site Activity log screen
3. Single event screen
4. Site Error log screen
5. Single error stacktrace screen
6. Single error overview screen
7. Configurable alerts screen
8. Edit Alert screen
9. Edit Alert conditions screen
10. Site settings screen
11. Add a site screen
12. Team members settings screen
13. Team integrations screen
14. User security settings screen

== Changelog ==

= 1.2.1 =
* Fix: Password reset page error
* Fix: Input filter class throwing error in some cases
* Improvements: Compatible upto WordPress v6.3.0

= 1.2.0 =
* New: Custom authentication flow replacing usage of WP REST API and Application Passwords
* New: Update ActionScheduler lib to v3.4.2
* Fix: Plugin load order update could cause unexpected plugin deactivations
* Fix: Scheduled logs possibly triggering errors when data exceeded 8k or more characters
* Fix: Send sample data button failing to report status
* Fix: Comment insert log error when username is empty or anonymous
* Improvements: Compatible upto WordPress v6.0.1

= 1.1.2 =
* Fix: Remove file captures from error stacktrace
* Improvement: Add settings page link to plugin action links

= 1.1.1 =
* Fix: Plugin order action triggering undefined offset on first install
* Fix: All plugins getting deactivated when deactivating a single plugin if order is wrong due to undefined offset
* Fix: User count event showing context as network
* Improve: Ignore Scoper config from svn files

= 1.1.0 =
* New: Introducing Error Tracker, track all erros and crashes on your WordPress site
* Improvement: Autoload plugin files
* Improvement: Added setting for enabling/disabling Activity and Error log
* Improvement: Remove media delete event action links
* Fix: Scheduled post error due to non-string `$post_id` concatenated in string var
* Fix: Guest comments error due to undefined `$user` var

= 1.0.7 =
* Fix: Incorrect onboarding page url

= 1.0.6 =
* Fix: plugins/themes log handling for failed and downgrade events
* Fix: incorrect previous version at Installer pipe logs

= 1.0.5 =
* Improved Settings activity tracking
* Improved Themes and Plugins activity tracking
* Disable Multisite features temporarily
* Other minor changes

= 1.0.4 =
* Add plugin onboarding
* Other minor changes

= 1.0.3 =
* Use as_enqueue_async_action for asap async events
* Only re-schedule events for 426, 500, and empty response codes
* Remove obsolete try/catch at Logpipe API

= 1.0.2 =
* Fixes an error due to a multi-dimensional array in request data
* Update translation .pot file

= 1.0.1 =
* Add ActionScheduler lib for request background processing
* Add option to select immediate/async event logging at Settings
* Add Excludes filter tab at Settings
* Add actionable links in event data
* Improve custom CPT events to show better insights
* Improve "Send Sample Data" button at Settings
* Improve event required data by adding a fallback filter
* Fix user logout action message
* Removes duplicate values from auth events
* Remove blank values from comments meta
* Drop admin session_tokens event from log

= 1.0.0 =
* Initial release
