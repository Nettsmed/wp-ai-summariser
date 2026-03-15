# AI Blog Summariser

WordPress plugin that automatically generates AI-powered summaries for blog posts using the Anthropic Claude API.

Built by [Nettsmed](https://nettsmed.no). First deployed on [nettsmed.no](https://nettsmed.no) and [productivitytech.io](https://productivitytech.io).

## Features

- Auto-generates a concise summary when a post is published or updated
- Background processing — save post returns instantly, summary appears seconds later
- Configurable AI model (Claude Sonnet, Haiku, Opus)
- Language support: auto-detect, English, Norwegian Bokmål
- Manual override via meta box in the post editor
- "Regenerate Summary" button for one-click refresh
- Writes to both `_ai_summary` and `article_summary` meta keys for broad theme/page-builder compatibility
- Shortcode `[ai_summary]` for use in templates
- Supports any public post type

## Requirements

- WordPress 6.0+
- PHP 8.0+
- An [Anthropic API key](https://console.anthropic.com/)

## Installation

1. Upload the `ai-blog-summariser` folder to `wp-content/plugins/`
2. Activate the plugin in **Plugins → Installed Plugins**
3. Go to **Settings → AI Summariser** and enter your Anthropic API key
4. Publish or update a post — the summary will appear in the AI Summary meta box within seconds

## Configuration

| Setting | Default | Description |
|---|---|---|
| API Key | — | Your Anthropic API key |
| Model | `claude-sonnet-4-6` | AI model to use |
| Post Types | `post` | Which post types to generate summaries for |
| Auto Generate | On first publish | When to generate: on publish, on every update, or manual only |
| Language | Auto-detect | Language for the summary |
| Max Words | 50 | Maximum length of the summary (20–300) |
| Custom Prompt | Built-in | Full control over the prompt sent to Claude |

## Post Meta Keys

| Key | Description |
|---|---|
| `_ai_summary` | The generated summary (internal) |
| `article_summary` | Same value — for Elementor dynamic tags and other page builders |
| `_ai_summary_generated` | Unix timestamp of last generation |

## Cost Estimate

Using `claude-sonnet-4-6` (~$3/M input, ~$15/M output tokens):

- Typical blog post (~1 000 words) ≈ **~$0.005 per summary** (half a cent)
- 200 posts ≈ $1

## Shortcode

```
[ai_summary]
[ai_summary post_id="123"]
```

## Deployment

### productivitytech.io

```bash
rsync -avz ai-blog-summariser/ htz_productivitytech:/var/www/vhosts/productivitytech.io/httpdocs/wp-content/plugins/ai-blog-summariser/
ssh -o RemoteCommand=none htz_productivitytech "wp --path=/var/www/vhosts/productivitytech.io/httpdocs plugin activate ai-blog-summariser"
```

### nettsmed.no (Kinsta)

```bash
scp -r ai-blog-summariser nettsmed:/www/nettsmed_123/public/wp-content/plugins/
ssh nettsmed "cd /www/nettsmed_123/public && wp plugin activate ai-blog-summariser"
```

## License

GPL-2.0+
