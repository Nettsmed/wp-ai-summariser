# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-03-15

### Changed
- Summary generation is now handled in a background WP-Cron job (`aibs_background_generate`) — save post returns instantly instead of blocking for 3–8 seconds while waiting for the API response

### Fixed
- `wp_next_scheduled` guard prevents duplicate background jobs being queued for the same post

## [1.0.0] - 2026-03-14

### Added
- Initial release
- Auto-generate summaries on first publish or every update via `save_post` hook
- Anthropic Claude API integration (`/v1/messages`) with configurable model, language, max words, and custom prompt
- Admin settings page at Settings → AI Summariser
- AI Summary meta box in post editor with editable textarea and "Regenerate Summary" button (AJAX)
- Writes summary to both `_ai_summary` and `article_summary` post meta for broad compatibility with Elementor and other page builders
- Shortcode `[ai_summary]` for use in templates
- Supports any public post type
- Norwegian Bokmål language support (uses `explode`-based word count instead of `str_word_count`)
- Security: nonce verification, capability checks, full input sanitization and output escaping
