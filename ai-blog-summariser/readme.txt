=== AI Blog Summariser ===
Contributors: nettsmed
Tags: ai, summary, blog, anthropic, claude
Requires at least: 5.9
Tested up to: 6.7
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically generates AI-powered summaries for blog posts using the Anthropic Claude API.

== Description ==

AI Blog Summariser uses the Anthropic Claude API to automatically generate concise summaries of your blog posts. Summaries are stored as post meta and can be displayed anywhere using the `[ai_summary]` shortcode.

**Features:**
* Auto-generate summaries on publish or update
* Manual regeneration via meta box button
* Configurable language, word count, and AI model
* Custom prompt templates with placeholders
* Works with any public post type

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/ai-blog-summariser/`
2. Activate the plugin via the Plugins screen
3. Go to Settings → AI Summariser and enter your Anthropic API key

== Frequently Asked Questions ==

= Where do I get an Anthropic API key? =

Sign up at https://console.anthropic.com/

= What shortcode can I use to display the summary? =

Use `[ai_summary]` anywhere in a post or page. To display another post's summary, use `[ai_summary post_id="123"]`.

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release.
