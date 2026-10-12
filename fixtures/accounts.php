<?php

return array(
	array(
		'login'        => 'system-messages',
		'password'     => 'system-messages',
		'email'        => 'system-messages@example.invalid',
		'display_name' => 'LUG System Messages',
		'role'         => 'subscriber',
		'internal'     => true,
		'description'  => 'System sender account used to demonstrate welcome messages and member notifications.',
	),
	array(
		'login'        => 'lug-demo-member',
		'password'     => 'lug-demo-member',
		'email'        => 'lug-demo-member@example.invalid',
		'display_name' => 'Demo Member',
		'role'         => 'subscriber',
		'description'  => 'Member account for exploring the demo site.',
	),
	array(
		'login'        => 'demo-super',
		'password'     => 'demo-super',
		'email'        => 'demo-super@example.invalid',
		'display_name' => 'Demo LUG Super User',
		'role'         => 'editor',
		'super'        => true,
		'description'  => 'LUG workflow manager for trying inventory tools and the lug-core member editor. WordPress site settings remain unavailable.',
	),
	array(
		'login'        => 'lug-demo-organizer',
		'password'     => 'lug-demo-organizer',
		'email'        => 'lug-demo-organizer@example.invalid',
		'display_name' => 'Demo Organizer',
		'role'         => 'editor',
		'description'  => 'Organizer account for exploring event and content workflows.',
	),
);
