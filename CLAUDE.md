# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WP Launch Check is a WP-CLI extension that performs performance and security checks for WordPress sites. Originally designed for Pantheon.io customers but usable anywhere. It registers the `wp launchcheck <subcommand>` command.

## Build & Test Commands

```bash
# Install dependencies
composer install

# Build the phar file
curl -LSs https://box-project.github.io/box2/installer.php | php
php -dphar.readonly=0 box.phar build -v

# Run all Behat tests
vendor/bin/behat --ansi

# Run a specific feature file
vendor/bin/behat features/cron.feature
```

**Note:** Tests require MySQL running locally. The CI uses database credentials `pantheon/pantheon/pantheon` on `127.0.0.1:3306`.

## Release Process

Releases are automated via GitHub Actions:
1. Merge PR to `main` → `tag-release.yml` runs `action-autotag` to create a semver tag and draft release
2. Release created → `release.yml` builds the phar and attaches it to the release

## Architecture

### Core Framework

The check framework has two execution patterns:

1. **`\Pantheon\Checker`** (`php/pantheon/checker.php`) - Runs checks against data/state. Iterates registered checks calling `init()`, `run()`, `message()`.

2. **`\Pantheon\Filesearcher`** (`php/pantheon/filesearcher.php`) - Extends Checker but first scans directories with Symfony Finder, then passes each PHP file to the check's `run($file)` method.

### Check Implementation

All checks extend `\Pantheon\Checkimplementation` and implement `\Pantheon\Checkinterface`:
- `init()` - Set default state
- `run()` - Execute the check (receives `$file` parameter for Filesearcher checks)
- `message(Messenger $messenger)` - Format output

Checks live in `php/pantheon/checks/`:
- `config.php` - wp-config validation (runs before WP loads)
- `cron.php` - Cron status and scheduled tasks
- `database.php` - InnoDB engine, options table size, autoloaded options, transients
- `general.php` - WP_DEBUG, debug-bar, plugin count, URL settings, caching plugins
- `objectcache.php` - Object cache and Redis detection
- `plugins.php` / `themes.php` - Update availability
- `sessions.php` - Detects session_start() usage (uses Filesearcher)

### Output System

`\Pantheon\Messenger` collects check results and emits them in `raw` (CLI formatted) or `json` format. Check properties include:
- `$score`: 0=ok (green), 1=warning (orange), 2=error (red)
- `$alerts`: Array of `['code', 'class', 'message']`

### WP-CLI Integration

`php/commands/launchcheck.php` registers the `launchcheck` command and includes the autoloader. Subcommands: `all`, `config`, `cron`, `database`, `general`, `object_cache`, `plugins`, `sessions`, `themes`.

The `@when before_wp_load` annotation on some methods allows checks to run before WordPress fully loads.

## Tests

Behat feature tests in `features/`. Each `.feature` file tests a subcommand by installing WordPress in a temp directory and running `wp launchcheck` commands against it. Test fixtures use `Given a WP install` which sets up a fresh WordPress installation with the test database.
