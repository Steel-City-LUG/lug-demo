# LUG Demo

Demo-only orchestration plugin for `lugtools.steelcitylug.com`.

This repository is intentionally separate from `lug-platform`. It should not be included in the downloadable platform connector or installed on partner LUG sites.

## Current demo access

Visitors use the password-free button to start an independent two-hour subscriber
session. No shared credentials are published. Retired QA, organizer, system and
super-user identities remain locked by the separate must-use account guard.

`mu-plugins/lug-demo-guest-access.php` is installed directly in the tools site's
must-use directory. Guest permissions are bounded, existing session cookies expire,
shared layouts and real external integrations are disabled, and guests cannot
manage WordPress. Personal layouts are private and owner-only.

The welcome page, Getting Started, Plugin Tour and All Tools use
`fixtures/demo-guide.php`. Every installed tool has a task and an explicit live or
preview-only access level. Bricks Cubed has a live member editor and a separate training series. No promise
of administrator access or physical structural safety is made.

`tools/provision-demo.php` is a CLI-only, demo-host-only, idempotent setup operation.
It creates missing legacy LUG Core tables from schema-only fixtures (never copied
member data), adds sample inventory, events/shifts and a rotating display, and
creates the missing public preview pages. It does not rebuild accounts or overwrite
unowned pages or existing tables. Run it from a reviewed release with WP-CLI.

## Public demo email policy

On the configured `lugtools.steelcitylug.com` site, LUG Demo blocks all WordPress email with a failed result and a second PHPMailer transport guard. Newsletter capabilities and every Newsletter REST route are denied for all users, including administrators; its queue worker and new queue schedules are disabled. Newsletter is presented through read-only samples, screenshots and training. The guard uses the configured site URL, not the request Host header, and does not apply to other LUG installations. Keep LUG Demo active on the public demo site.

Run `php tests/mail-safety.php` for isolated boundary checks.

### Newsletter training links (0.1.13)

The Newsletter showcase and `/training/?tool=lug-newsletter` share the generated training catalog. The training export includes Newsletter only after the playlist and episode visibility have been verified as public; private uploads and empty playlists stay out of public pages. Updating this catalog preserves existing demo records and the email/Newsletter editing blocks.

### Newsletter sample data (0.1.12)

After Newsletter is installed and active, LUG Tools seeds three private example drafts, five fictional contacts at reserved `example.org` addresses, and a clearly labeled simulated report. Every sample delivery row is already closed; the seed never calls mail, queues delivery or schedules a send. Existing contacts and their opt-outs are preserved. Repeated requests reuse the completed seed. Seed/reset owns only marked posts and source-tagged contacts; it does not reset other tools or change mail credentials. `/newsletter/` offers read-only draft details and subscription examples without send/edit controls.

Local integration check: `wp eval-file tests/newsletter-seed-local.php`. It uses temporary Newsletter tables, verifies idempotency, no mail/active jobs and preservation of existing consent, then removes its own records. It does not activate LUG Demo locally or touch the original Newsletter tables.

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

## 0.1.14

Simplify automatic header navigation to Getting Started, All Tools, and Training Videos.
Keep background links in the footer and remove Community forums and Member directory
from All Tools, including already-seeded pages. Existing demo data is not reseeded.
All six training series remain available (64 episodes).

## 0.1.15

Remove the training page screenshot section, including already-seeded pages.
The video tool picker and all episodes remain available without reseeding demo data.

## 0.1.16

Retire the mixed build-along walkthrough and Community Guidelines page from demo navigation.
Their old URLs redirect to Brick to Numbers training and About this demo respectively.
Correct the All Tools and Plugin tour copy to distinguish I-Spy checklists from mosaic building.
Existing demo data is not reseeded.

## 0.1.17

Replace the introductory pages with a task-based, hands-on plugin journey.
Remove normal-journey reset messaging and retired demo-account prompts.
Keep account access expectations clear and label the Newsletter showcase read-only.
Existing demo data is not reseeded.

## 0.1.21 / Guest Access 1.0.5

Replace outdated account instructions with a task-based catalog covering live and
preview-only workflows. Provision missing demo schema and sample content explicitly.
Keep introductory content updates separate from full seed/reset. Harden demo-only
outbound requests and cleanup; hide misleading WordPress toolbar links for guests.

Review coverage, changes and limits: [Demo review](DEMO-REVIEW-2026-11-08.md).

## 0.1.22

- Publish the six current Social Posts training lessons, including event featured photos, from the canonical training inventory.
