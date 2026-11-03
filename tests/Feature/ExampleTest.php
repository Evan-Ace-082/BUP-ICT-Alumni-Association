<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('application homepage loads successfully', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
});
