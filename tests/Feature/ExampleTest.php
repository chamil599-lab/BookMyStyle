<?php

it('keeps the public homepage on React during the staged migration', function () {
    $response = $this->get('/');

    $response->assertRedirect(rtrim((string) env('FRONTEND_URL', 'https://bookmystyle.limach.lk'), '/'));
});
