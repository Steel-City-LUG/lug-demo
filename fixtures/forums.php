<?php

return array(
	array(
		'title'   => 'Getting started with the hobby',
		'content' => 'Questions from new builders and friendly advice from experienced members.',
		'topics'  => array(
			array(
				'title'   => 'What should I bring to my first meetup?',
				'content' => 'I am new to the group. Is there anything I should bring or prepare before attending?',
				'replies' => array(
					array( 'content' => 'Just bring yourself. A small project is welcome, but there is no expectation that you need to bring anything.' ),
					array( 'content' => 'The Getting Started page has a useful overview, and the monthly meetup is a great first event.' ),
				),
			),
		),
	),
	array(
		'title'   => 'Builds, displays, and techniques',
		'content' => 'Share projects, ask for feedback, and trade building ideas.',
		'topics'  => array(
			array(
				'title'   => 'Show and tell: neighborhood landmarks',
				'content' => 'I am putting together a small local-landmark display and would love ideas for making the streetscape feel lived in.',
				'replies' => array(
					array( 'content' => 'Try varying the building heights and adding small details at street level. Those details make a big difference.' ),
					array( 'content' => 'This would be a fun project to bring to the next meetup for feedback.' ),
				),
			),
		),
	),
	array(
		'title'   => 'Demo tour: events and signups',
		'content' => 'Walk through event creation, shifts, capacities, participation, and the organizer view.',
		'topics'  => array(
			array(
				'title'   => 'Try it: join an event shift',
				'content' => 'Open the next meetup, review the available shifts, and sign up as a demo member. Then log in as the organizer and inspect the participant count. Try joining a full-capacity shift and see how the site responds.',
				'replies' => array(
					array( 'content' => 'This is a safe place to try both joining and withdrawing. Resetting the demo clears the seeded participation.' ),
					array( 'content' => 'Organizers can use the shift view to see capacity, open spots, and the people who volunteered.' ),
				),
			),
			array(
				'title'   => 'Try it: plan a public display',
				'content' => 'Open the summer display planning event and explore how a LUG can coordinate volunteers, locations, and setup responsibilities.',
				'replies' => array(
					array( 'content' => 'Try different shift capacities and compare the member experience with the organizer experience.' ),
				),
			),
		),
	),
	array(
		'title'   => 'Demo tour: I-Spy, brick-by-numbers, and QR builds',
		'content' => 'Follow the complete public-build path: discover a challenge, scan it on a phone, build along, and record progress.',
		'topics'  => array(
			array(
				'title'   => 'Step by step: build a brick-by-numbers challenge',
				'content' => '1. Open the seeded event and find its I-Spy/build-along link. 2. Open the challenge on a laptop first so you can see the organizer and public views. 3. Use the numbered instructions to identify the next part or section. 4. On your phone, scan the QR code shown by the public I-Spy page. 5. Build the next step from the phone view while keeping the physical model in front of you. 6. Mark progress, return to the event, and try the next step. Resetting the demo restores the starting challenge and event data.',
				'replies' => array(
					array( 'content' => 'The important test is the handoff from a shared event link to a phone. Try opening the QR destination on both iOS and Android, then rotate the phone and adjust text size.' ),
					array( 'content' => 'For a real public display, print the QR code beside the model. Visitors can scan it without logging in and build one numbered step at a time.' ),
				),
			),
			array(
				'title'   => 'Try it: share an I-Spy event from the public view',
				'content' => 'Open the event as a visitor, inspect the I-Spy progress and public sharing controls, copy the public link, and use the QR action to create a phone-friendly handoff. Then log in as demo1 and compare the organizer/edit controls. The public route should be useful even when the visitor is not signed in.',
				'replies' => array(
					array( 'content' => 'Use the current event for the live walkthrough and the recent event to compare what a completed or closed event looks like.' ),
					array( 'content' => 'The demo site is deliberately assembled with the complete plugin family. If one of these surfaces is missing, treat that as a deployment/setup issue and restore the showcase plugin set before evaluating the workflow.' ),
				),
			),
		),
	),
	array(
		'title'   => 'Demo tour: members and locations',
		'content' => 'Explore member profiles, interests, ZIP codes, and the geographic view of a local group.',
		'topics'  => array(
			array(
				'title'   => 'Try it: explore the member map',
				'content' => 'Browse the member directory and location map. Compare the different demo personas, then try editing a profile or adding a new test member.',
				'replies' => array(
					array( 'content' => 'The locations are fictional Pittsburgh-area examples. They are included to demonstrate how a group can see where members are coming from.' ),
				),
			),
		),
	),
	array(
		'title'   => 'Demo tour: officers and elections',
		'content' => 'See how a LUG can define officer roles, run elections, record results, and show current leadership.',
		'topics'  => array(
			array(
				'title'   => 'Try it: explore officer roles',
				'content' => 'Open the officers area, review the available roles, and inspect the election workflow. If the demo installation has elections enabled, try moving through nominations, voting, and results.',
				'replies' => array(
					array( 'content' => 'The demo is intended to make the workflow understandable even when you are not familiar with LUG administration.' ),
				),
			),
		),
	),
	array(
		'title'   => 'Demo tour: community connections',
		'content' => 'Explore announcements, private welcome messages, Discord connections, and the activity stream.',
		'topics'  => array(
			array(
				'title'   => 'Try it: follow the member welcome experience',
				'content' => 'Use the demo member account, read the welcome information, post an activity update, and reply to a forum topic. Then switch to the organizer account to see the community from another perspective.',
				'replies' => array(
					array( 'content' => 'Try writing a welcome message or announcement as though you were inviting someone to their first meetup.' ),
				),
			),
			array(
				'title'   => 'Try it: test integrations without fear',
				'content' => 'The Discord, layout, display, and other integrations are here to be explored. Follow their local plugin documentation, try the controls, and report anything confusing.',
				'replies' => array(
					array( 'content' => 'If an experiment leaves the site looking odd, ask the administrator to rebuild the demo. Resetting is an expected part of the tour.' ),
				),
			),
		),
	),
);
