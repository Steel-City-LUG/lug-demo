<?php

$demo_now = function_exists( 'current_time' ) ? current_time( 'timestamp' ) : time();
$past_meeting = wp_date( 'Y-m-d', strtotime( '-28 days', $demo_now ) );
$current_meeting = wp_date( 'Y-m-d', strtotime( '-12 hours', $demo_now ) );
$future_meeting = wp_date( 'Y-m-d', strtotime( '+21 days', $demo_now ) );

return array(
	array(
		'name'                => 'Steel City LUG monthly meetup (recent)',
		'creator_login'       => 'lug-demo-organizer',
		'start_date'          => $past_meeting . ' 18:30:00',
		'end_date'            => $past_meeting . ' 20:30:00',
		'description'         => 'A welcoming monthly meetup for builders, collectors, families, and anyone curious about the hobby. Bring a project, ask questions, or just come say hello.',
		'attendees'           => 'All ages and experience levels are welcome.',
		'classification'      => 'Meeting',
		'url'                 => '',
		'event_location'      => 'Community room at the Steel City library',
		'social_links'        => '',
		'entrance_fee'        => 'Free',
		'community_enjoyment' => 'Bring-and-build tables, show-and-tell, and an open help desk.',
	),
	array(
		'name'                => 'Beginner workshop: build your first display (in progress)',
		'creator_login'       => 'lug-demo-organizer',
		'start_date'          => $current_meeting . ' 00:00:00',
		'end_date'            => wp_date( 'Y-m-d H:i:s', strtotime( '+24 hours', $demo_now ) ),
		'description'         => 'A hands-on workshop covering planning, building techniques, and how to make a small display look great. Parts and examples are provided.',
		'attendees'           => 'Ideal for new builders and families.',
		'classification'      => 'Workshop',
		'url'                 => '',
		'event_location'      => 'Steel City LUG workshop space',
		'social_links'        => '',
		'entrance_fee'        => 'Free; registration requested',
		'community_enjoyment' => 'A guided build followed by peer feedback and ideas for next projects.',
	),
	array(
		'name'                => 'Summer public display planning night (upcoming)',
		'creator_login'       => 'lug-demo-organizer',
		'start_date'          => $future_meeting . ' 19:00:00',
		'end_date'            => $future_meeting . ' 21:00:00',
		'description'         => 'Help plan the group display for the summer community festival. We will assign tables, review the theme, and coordinate transport and volunteer shifts.',
		'attendees'           => 'Members who want to exhibit or volunteer.',
		'classification'      => 'Planning',
		'url'                 => '',
		'event_location'      => 'Online planning room and Steel City LUG clubhouse',
		'social_links'        => '',
		'entrance_fee'        => 'Free',
		'community_enjoyment' => 'Collaborative planning with clear next steps for every volunteer.',
	),
);
