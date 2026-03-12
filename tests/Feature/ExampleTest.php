<?php

test('returns a successful response', function () {
    $response = $this->get('/');

    // Guests are redirected to login
    $response->assertRedirect();
});