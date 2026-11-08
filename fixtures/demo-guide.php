<?php
/** Public task guide. No privileged controls or credentials belong here. */
$start = '<p>Try the tools with fictional club records. No personal account or password is needed.</p>[lug_demo_accounts]';
$limits = '<p>Your demo member session lasts two hours. Use fictional details and non-sensitive images. Personal layouts are private; shared layouts, administrator access, real email and external integrations are blocked.</p>';
$tools = array(
	array( 'Events and shifts', '/events/', 'Open a sample event, inspect the dates and location, sign up for a shift, then cancel your signup. Create your own event to explore the organizer form.', 'Member participation and your own events are live. Sample-event management and club-wide settings remain restricted.', 'lug-core' ),
	array( 'Inventory', '/inventory/', 'Browse sample equipment, tables and baseplates. Open an item, add it to a checkout request, and inspect your cart.', 'Member browsing and request controls are live. Approval, credit adjustments and manager actions stay restricted.', 'lug-core' ),
	array( 'Layout Designer', '/layout/', 'Create a personal layout, place and move a table or baseplate, save, reopen, then try print/export.', 'Private, owner-only layouts. Sharing, joining, invitations and editing another visitor\'s layout are blocked.', 'layout-designer' ),
	array( 'Brick to Numbers', '/bbn/create/', 'Choose a small image, generate a mosaic, change the size or palette, save your project, and inspect the build instructions.', 'The creator is live. Mosaic Session organizer and public participant workflows are covered in training. I-Spy is a separate checklist tool.', 'brick-by-numbers' ),
	array( 'GEoBrick', '/geobrick/', 'Choose a shape, change its size and construction settings, inspect the layers, save a design and reopen it from your library.', 'Member design and your own saved designs are live.', 'geobrick' ),
	array( 'Rotating Displays', '/rotating-displays/', 'Open the sample display, choose free dates, reserve a slot, then cancel your reservation.', 'Member reservations are live. Display and location administration stay restricted.', '' ),
	array( 'AFOL Badges', '/my-badges/', 'Search for an interest, add a self-selectable badge, inspect its details, then remove it.', 'Interest badges are live. Organizer-awarded badges cannot be self-awarded.', '' ),
	array( 'I-Spy', '/ispy/', 'Check off the sample landmarks, complete the checklist, then restart it.', 'The public checklist is live. Authoring and visitor analytics are organizer functions.', 'lug-core' ),
	array( 'Officers', '/officer-roster/', 'Inspect the fictional officer roster and position descriptions.', 'Roster preview. Elections, nominations and appointments are administrative workflows; guests cannot change them.', '' ),
	array( 'Member ZIP Map', '/member-map/', 'Explore the fictional member ZIP clusters and map controls.', 'Map preview. Do not enter a home address or personal location.', '' ),
	array( 'Welcome messages and notifications', '/member-notifications/', 'Read the example welcome message and see where event notifications fit the member journey.', 'Workflow preview. No real email is delivered; guest sessions are temporary.', '' ),
	array( 'Newsletter', '/newsletter/', 'Inspect sample monthly drafts, the fictional audience and simulated reports, then follow the training series.', 'Read-only samples. Editing, real audience imports, sending and scheduling are blocked.', 'lug-newsletter' ),
	array( 'Discord bridge, Discord Connect and LUG Platform', '/integrations/', 'Review how forum announcements, membership connections and site linking work together.', 'Setup preview only. Real OAuth, webhooks, role syncing and cross-site connections are disabled.', '' ),
	array( 'Bricks Cubed', '/training/?tool=bricks-cubed', 'Follow the training series to see the editor and building workflow.', 'Video-only: Bricks Cubed is not installed here. This is not a live editor demo.', '' ),
);
$catalog = $start . '<p>Pick a plugin below. Each has a first task and an explicit access level.</p>';
foreach ( $tools as $tool ) {
	$catalog .= '<section><h2>' . esc_html( $tool[0] ) . '</h2><p>' . esc_html( $tool[2] ) . '</p><p><strong>Access:</strong> ' . esc_html( $tool[3] ) . '</p><p><a href="' . esc_url( home_url( $tool[1] ) ) . '">Open ' . esc_html( $tool[0] ) . '</a>';
	if ( $tool[4] ) { $catalog .= ' &middot; <a href="' . esc_url( home_url( '/training/?tool=' . $tool[4] ) ) . '">Training videos</a>'; }
	$catalog .= '</p></section>';
}
$catalog .= $limits;
$first_tasks = '<ol><li><a href="/layout/">Create a personal layout</a>, add a table, save it and reopen it.</li><li><a href="/bbn/create/">Create a mosaic</a> from a small, non-sensitive image.</li><li><a href="/geobrick/">Design and save a GEoBrick shape</a>.</li><li><a href="/events/">Open a sample event</a> and try a volunteer signup.</li><li><a href="/rotating-displays/">Reserve and cancel a demo display slot</a>, or <a href="/my-badges/">choose an interest badge</a>.</li></ol>';
$more = '<p><a href="/active-tools/">Every plugin: live tasks, previews and limits</a> &middot; <a href="/training/">Training videos</a></p>';
return array(
	'welcome-to-the-demo-lug' => $start . '<h2>Your first five minutes</h2>' . $first_tasks . $more . $limits,
	'getting-started' => $start . '<h2>Choose a first task</h2>' . $first_tasks . '<p>For inventory, try the checkout request cart. For I-Spy, finish and restart the public checklist. Administrative workflows and external integrations have explicitly labeled previews.</p>' . $more . $limits,
	'plugin-tour' => $catalog,
	'active-tools' => $catalog,
	'about-this-demo' => $start . '<p>The club records, people, locations and examples shown here are demonstration content. This site illustrates member workflows; it does not grant access to a real club or a persistent member account.</p>' . $limits . '<p>Newsletter, officer administration and integrations are preview-only. Bricks Cubed currently has training only.</p>' . $more,
);
