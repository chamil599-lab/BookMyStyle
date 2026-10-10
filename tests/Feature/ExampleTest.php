<?php

it('serves the public homepage through Blade', function () {
    $response = $this->get('/');

    $response->assertOk()->assertSee('BOOK')->assertSee('Find your salon');
});
