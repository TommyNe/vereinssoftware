<?php

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\URL;

it('forces https scheme in production environment', function () {
    app()->detectEnvironment(fn () => 'production');

    (new AppServiceProvider(app()))->boot();

    expect(URL::to('/test'))->toStartWith('https://');
});
