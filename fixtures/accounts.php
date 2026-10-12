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
		'login'        => 'demo',
		'password'     => 'demo',
		'email'        => 'demo@example.invalid',
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
		'login'        => 'demo1',
		'password'     => 'demo1',
		'email'        => 'demo1@example.invalid',
		'display_name' => 'Demo Organizer',
		'role'         => 'editor',
		'description'  => 'Organizer account for exploring event and content workflows.',
	),
);
