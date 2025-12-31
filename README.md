# DevBanhMi Deploy Trigger

WordPress plugin that triggers GitHub Actions workflows when posts are published, updated, or trashed. Built for headless WordPress setups with static site generators like Astro, Next.js, or Hugo.

## How It Works

```
WordPress Post Published/Updated/Trashed
    ↓
Plugin sends repository_dispatch to GitHub
    ↓
GitHub Actions workflow runs
    ↓
Static site rebuilds
```

## Installation

1. Download the [latest release](https://github.com/gaupoit/devbanhmi-deploy-trigger/releases) or clone this repo
2. Upload to `/wp-content/plugins/devbanhmi-deploy-trigger/`
3. Activate the plugin in WordPress admin
4. Go to **Settings → Deploy Trigger**

## Configuration

### GitHub Token

Create a Personal Access Token at [github.com/settings/tokens](https://github.com/settings/tokens):

**Classic Token:**
- Select `repo` scope

**Fine-Grained Token (recommended):**
- Repository access: Select your blog repo only
- Permissions: Contents (Read and write)

### GitHub Actions Workflow

Add this workflow to your static site repository at `.github/workflows/rebuild.yml`:

```yaml
name: Rebuild Blog

on:
  repository_dispatch:
    types: [wordpress_publish]

permissions:
  contents: write

jobs:
  rebuild:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Trigger rebuild
        run: |
          git config user.name "GitHub Actions"
          git config user.email "actions@github.com"
          git commit --allow-empty -m "Rebuild: ${{ github.event.client_payload.action }} at ${{ github.event.client_payload.time }}"
          git push
```

## Features

| Feature | Description |
|---------|-------------|
| Auto-deploy | Triggers on post publish, update, or trash |
| Debounce | 60-second cooldown prevents duplicate triggers |
| Manual trigger | Button in settings for on-demand deploys |
| Encrypted storage | GitHub token encrypted with `AUTH_KEY` |
| Logging | Last 10 webhook attempts with status |

## Settings

| Setting | Description |
|---------|-------------|
| Enable Auto-Deploy | Toggle automatic triggers on/off |
| GitHub Repository | Format: `owner/repo` |
| GitHub Token | Personal Access Token with repo access |

## Webhook Payload

The plugin sends this payload to GitHub:

```json
{
  "event_type": "wordpress_publish",
  "client_payload": {
    "action": "publish|edit|trash|manual",
    "time": "2025-01-01 12:00:00"
  }
}
```

## Requirements

- WordPress 5.0+
- PHP 7.4+
- GitHub repository with Actions enabled

## License

GPL v2 or later
