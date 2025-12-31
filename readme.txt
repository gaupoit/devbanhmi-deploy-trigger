=== DevBanhMi Deploy Trigger ===
Contributors: devbanhmi
Tags: deploy, github, webhook, astro, static site
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later

Triggers GitHub Actions to rebuild your static blog when posts are published.

== Description ==

This plugin automatically triggers a GitHub Actions workflow when you publish, update, or trash a post. Perfect for headless WordPress setups with static site generators like Astro.

Features:

* Automatic deploy trigger on post publish/update/trash
* 60-second debounce to prevent multiple triggers
* Manual trigger button in settings
* Encrypted GitHub token storage
* Webhook attempt logging

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin
3. Go to Settings → Deploy Trigger
4. Enter your GitHub repository (e.g., `owner/repo`)
5. Create a GitHub token with `repo` scope and enter it
6. Enable auto-deploy

== Changelog ==

= 1.0.0 =
* Initial release
