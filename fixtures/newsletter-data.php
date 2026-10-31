<?php
/** Fictional, reserved-domain examples. Never addresses for delivery. */
return [
    'drafts' => [
        ['title'=>'Sample monthly club news','subject'=>'Build together this month','intro'=>'Welcome, builders! Explore our next club build and share your latest creation.','items'=>[
            ['title'=>'Community build afternoon','section'=>'upcoming','date_offset'=>14,'description'=>'Bring a small model and help build a shared town display.','location'=>'Example community hall','path'=>'/getting-started/'],
            ['title'=>'Member build spotlight','section'=>'recap','description'=>'A colourful brick-built garden inspired our latest group challenge.','path'=>'/plugin-tour/'],
        ]],
        ['title'=>'Sample volunteer announcement','kind'=>'adhoc','subject'=>'Help at our next display','intro'=>'Here is a short example announcement for club volunteers.','items'=>[
            ['title'=>'Choose a volunteer shift','section'=>'attention','description'=>'Review the example event and see how members can help with setup and visitor questions.','path'=>'/getting-started/'],
        ]],
        ['title'=>'Sample next edition planning','subject'=>'Ideas for the next newsletter','intro'=>'This unfinished example stays a draft until an editor reviews it.','items'=>[
            ['title'=>'Share an idea for the next club challenge','section'=>'attention','description'=>'Suggest a theme, a build technique or a small collaborative display.','path'=>'/plugin-tour/'],
        ]],
    ],
    'contacts' => [
        ['name'=>'Alex Builder','email'=>'alex.newsletter-demo@example.org','status'=>'subscribed'],
        ['name'=>'Morgan Builder','email'=>'morgan.newsletter-demo@example.org','status'=>'subscribed'],
        ['name'=>'Casey Builder','email'=>'casey.newsletter-demo@example.org','status'=>'subscribed'],
        ['name'=>'Jamie Builder','email'=>'jamie.newsletter-demo@example.org','status'=>'unsubscribed'],
        ['name'=>'Taylor Builder','email'=>'taylor.newsletter-demo@example.org','status'=>'pending'],
    ],
];
