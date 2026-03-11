<?php

test('the health endpoint reports the database', function () {
    $this->getJson('/healthz')
        ->assertOk()
        ->assertExactJson(['status' => 'ok', 'database' => 'ok']);
});

test('the framework health route is up', function () {
    $this->get('/up')->assertOk();
});

test('guests see the landing page and are sent to log in for the app', function () {
    $this->get('/')->assertOk()->assertSee('Create a shop');
    $this->get('/dashboard')->assertRedirect('/login');
    $this->get('/products')->assertRedirect('/login');
});
