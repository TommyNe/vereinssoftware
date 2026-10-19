<?php

test('the home page redirects to the software login', function (): void {
    $response = $this->get('/');

    $response->assertRedirectToRoute('filament.admin.auth.login');
});
