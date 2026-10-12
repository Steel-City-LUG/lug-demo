# LUG Demo

Demo-only orchestration plugin for `lugtools.steelcitylug.com`.

This repository is intentionally separate from `lug-platform`. It should not be included in the downloadable platform connector or installed on partner LUG sites.

## Current scope

- Admin screen for seed/rebuild and reset.
- First activation/version update automatically seeds the demo and makes the welcome page the site front page for the administrator who completes the activation flow.
- A welcoming account directory with one-click demo login links.
- A sandbox for all demo accounts: WordPress dashboard, settings, user administration, plugin/theme installation, updates, and site configuration are blocked. Only explicitly allowlisted LUG plugin screens and `lug_` plugin actions remain available.
- Three public demo identities: `lug-demo-member` (member), `lug-demo-organizer` (organizer/event owner), and `demo-super` (inventory/user-editor manager). The internal `system-messages` account is not shown as a persona to try.
- Existing non-demo users are never overwritten or deleted if their login collides with a fixture account.
- A dedicated `demo-super` account for trying lug-core inventory management and its member editor. It is still sandboxed: WordPress settings, plugin/theme/core management, and unrelated admin screens remain blocked.
- A `system-messages` account configured as lug-core's default sender for welcome messages and member notifications.
- Seeded WordPress pages and a generated demo navigation menu.
- Seeded events through the installed `lug-core` event provider.
- Seeded events are explicitly owned by `lug-demo-organizer` so the organizer account can open an existing event and try its edit, shift, signup, sharing, and management workflows without first completing the event form.
- Seeded event shifts with realistic capacities and member signups.
- Seeded bbPress forums, topics, and replies.
- Seeded BuddyPress activity updates.
- Seeded member profile metadata and Pittsburgh-area ZIP codes for the location tools.
- Assigns demo members to the installed officer roles and restores previous assignments on reset.
- Reports the availability of the surrounding LUG tools from the demo admin screen, including officers, ZIP map, welcome messages, Discord, and layout tools.
- Showcase guidance for the complete Steel City LUG plugin family. The target demo site is expected to have the companion plugins installed; local smoke tests may still skip integrations that are not present in the development checkout.
- Quick-login URLs after seeding:
  - `?lug_demo_login=lug-demo-member`
  - `?lug_demo_login=lug-demo-organizer`
  - `?lug_demo_login=demo-super`

The plugin is intentionally private to `lugtools.steelcitylug.com`. Its fixtures are designed to make that site feel like an established local LUG where visitors can explore the complete toolset. It is not intended for distribution to other LUG sites.

## Seed and reset contract

Use **LUG Demo → Seed / rebuild demo** after enabling or updating the installed plugins. The seed operation clears the previous batch-owned records before recreating them. Reset removes only records tracked by this plugin, including demo users, pages, navigation, events, forums, topics, replies, and BuddyPress activity.

Each operation records a report in the admin screen. On lugtools.steelcitylug.com, a missing companion plugin is a deployment/setup defect to fix, not an alternate demo mode. If an adapter returns an error, the operation remains visible as a warning instead of appearing silently successful.

The showcase covers lug-core events, inventory, and I-Spy; brick-by-numbers; officers; member ZIP/location tools; welcome messages; BuddyPress Messages; layout designer; rotating displays; AFOL badges; BuddyPress; bbPress; Discord connections; and the supporting member plugins.

## Local smoke test

When the local web runtime is available, run `tests/smoke.php` with the same PHP configuration used by the WordPress server. The test seeds the demo, checks the created records, resets them, and verifies that the original front-page settings are restored:

```text
php -c "C:\path\to\runtime\php.ini" tests/smoke.php
```

## Adapter contract

Installed demo-capable plugins should register with `lug_demo_seed_adapters`:

```php
$adapters['events'] = array(
    'label' => 'Events demo data',
    'run'   => array( $this, 'seed_demo' ),
    'reset' => array( $this, 'reset_demo' ),
);
```

Adapters must create only records marked with `steel-city-lug-demo-v1` and must remove only those records during reset.
