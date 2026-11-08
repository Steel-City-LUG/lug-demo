# Demo guest access 1.0.0

Install `lug-demo-guest-access.php` directly into the tools site's
`wp-content/mu-plugins/` directory. It only admits visitors when the configured
home host is exactly `lugtools.steelcitylug.com`; Test and Production stay closed.
Keep the retired-account guard installed.

The welcome-page account directory, page footer and WordPress login footer offer
a POST button that starts an independent two-hour subscriber session. Passwords
are generated randomly and never displayed. The endpoint accepts no target user,
role or redirect. Existing signed-in users retain their identity.

Guests may use member tools and normal forum participation. Site administration,
uploads, publishing WordPress posts, user management, password login/reset and
application passwords are denied. Changing a guest's role or adding capabilities
invalidates its session. Expired sessions are denied immediately; expired guest
accounts are removed hourly and when a new session starts. Cleanup removes their
WordPress content; companion-plugin data follows each plugin's user-deletion hooks.

Admissions are serialized and limited to six per originating IP and sixty sitewide
per hour. The origin IP comes from REMOTE_ADDR, never a client-controlled header.
Networks behind the same proxy share this limit. This is a public demo, so visitors
must not enter personal or sensitive information. The site must contain demo data.

Run `tests/guest-access.php` with the local WordPress PHP configuration to verify
member capabilities, privilege escalation rejection, expiry and cleanup. HTTP
checks should also verify POST + nonce, two independent guest identities, valid
cookie authentication, and rejection of the retired passwordless endpoint.
