<?php

it('serves the public homepage through Blade', function () {
    $response = $this->get('/');

    $response->assertOk()->assertSee('BookMyStyle')->assertSee('Find your salon');
});
