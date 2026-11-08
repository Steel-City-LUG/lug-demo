# Demo review — November 8, 2026

Scope: `lugtools.steelcitylug.com`, its public guidance and temporary member
access. Test's private gate and the real club Production site were not changed.

## Findings fixed

- Introductory pages told visitors to use an account they did not have. Every
  introductory page now provides the password-free entry and explains expiry.
- All Tools omitted installed tools and linked the mosaic creator only to videos.
  The catalog now gives each tool a first task, direct link and explicit boundary.
- Core event and inventory base tables were missing. The reviewed CLI-only setup
  creates missing schema without copying member data or replacing existing tables.
  Three sample events with shifts, three inventory items and a rotating display
  now provide concrete things to try. Repeating setup creates no duplicate tables,
  events or inventory. New pages cover the roster, map and integration previews.
- The standalone mosaic creator was disabled. It is enabled at `/bbn/create/`.
  The officer roster was moved off the native plugin route to avoid a conflict.
- Bricks Cubed was absent. Installed release 0.3.0 from its clean GitHub main
  commit `351f1392ffc8b784f5276f981bf5762a434f1120`, built and packaged using its
  positive runtime allowlist. All 502 tests, version check and build passed.
- The guest sandbox blocked the member display-reservation capability. It now
  permits reservation and cancellation while retaining manager restrictions.
- Guest toolbar links led to unavailable WordPress administration. Hidden for
  guests. Session messaging now explains which access is active.
- Added a ten-personal-layout limit. Expired guests release private layouts,
  display reservations, event signups, inventory holds and pending requests.
- Public admissions remain capped at sixty per hour sitewide. The per-IP limit
  is thirty so a club group on one network is not stopped after six visitors.

## Security checks and changes

- WordPress's file editor was enabled on this demo. `DISALLOW_FILE_EDIT=true` is
  now set in its configuration.
- Core 7.1.4 initially had four checksum differences and an extra error log.
  All four differences were line endings only. Preserved evidence privately,
  restored the official files and moved the log out of the web root. Core
  verification subsequently passed.
- Eight targeted Git, log, environment and configuration-backup probes returned
  403/404 without sensitive contents. This is not an exhaustive filename scan.
- Real external server calls are blocked except the demo itself, WordPress update
  services and public training lookups. Discord delivery, OAuth configuration and
  role synchronization are disabled. WordPress mail returns failure; Newsletter
  API access, sending and scheduling remain blocked.
- Retired QA/system/shared credentials remain locked. Guest capabilities do not
  include WordPress settings, user management, plugin/theme management or uploads
  through the general WordPress media endpoint.
- Cross-LUG shared layouts remain disabled, including public token routes.
  Guests can only create private, owner-only local layouts. Other visitors cannot
  open them, and guests cannot add editors or turn on sharing.

## Workflow coverage

| Plugin or surface | Verified guest behavior | Deliberate boundary |
| --- | --- | --- |
| LUG Core events/shifts | Sample data, own signup and withdrawal; another member's signup denied | Existing-event and club-wide administration restricted; complete organizer form submission not exercised |
| LUG Core inventory | Sample browse, cart add/remove; approval denied | Approval, credits and fulfillment stay manager-only; no real-world checkout |
| Layout Designer | Private create/read/rename/delete; other-owner access, sharing and editor changes denied | No hosting, joining, invitations or shared editing |
| Brick to Numbers | Creator shell, member library, project save and reopen | Browser image processing, canvas interaction and organizer/participant session flows not visually exercised |
| GEoBrick | Designer shell, library, private save/reopen/delete | Rendering and physical outputs are not certified |
| Bricks Cubed | Editor/assets, save/reopen/delete, private object isolation and admin gallery denial | Software diagnostics do not establish structural safety; mobile/WebGL interaction not visually verified |
| Rotating Displays | Sample location/display, reservation and cancellation | Manager and location administration restricted |
| AFOL Badges | Self-selectable badge add/remove | Organizer-awarded and administrative actions restricted |
| I-Spy | Public checklist content and items load | JavaScript completion effects not visually exercised |
| Member ZIP Map | Member page, four demonstration ZIP markers | No home addresses or real member locations requested |
| Officers | Fictional roster preview | Elections, nominations and appointments are not a live guest demo |
| Welcome/notifications | Labeled sample message and workflow explanation | Preview; real email is not delivered |
| Newsletter | Draft/audience/report showcase and training page | Read-only; no editing, imports, sends or scheduling |
| Discord bridge/Connect and Platform | Labeled integration setup overview | No real external connection or synchronization |

Nineteen initial guest pages passed HTTP checks after fixes. Bricks Cubed's
additional editor and runtime assets also passed. Temporary review accounts,
designs, layouts, reservations, signups and inventory holds were removed.

## Validation and limits

Local PHP lint, visitor guide/schema safety checks, guest permission tests and
17 mail-safety assertions passed. Live authenticated HTTP checks verified the
actions listed above. Browser use is disabled in this app's settings, so there
was no screenshot, mobile, WebGL or end-to-end JavaScript interaction sign-off.

This is a review of the demo's access boundaries and representative workflows,
not a full audit of every plugin endpoint or the shared hosting account. The
earlier hosting compromise investigation remains a separate security concern.
No claim that the entire site or host is free of vulnerabilities is made.

The catalog is intentionally honest about previews. Administrative setup,
elections, outgoing communication and cross-site workflows require a separate
private environment to evaluate fully; public administrator access is not a
substitute for that environment.
