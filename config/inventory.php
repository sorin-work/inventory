<?php

return [

    /*
    | Create demo@example.com / demo-password with a sample shop when the
    | database is seeded. The docker image seeds on start, so this is what
    | turns the demo on or off there.
    */
    'seed_demo' => (bool) env('SEED_DEMO', false),

    'demo' => [
        'email' => 'demo@example.com',
        'password' => 'demo-password',
    ],

];
