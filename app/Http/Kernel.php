<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middlewareAliases = [
        'approved' => \App\Http\Middleware\EnsureUserIsApproved::class,
        'subscription' => \App\Http\Middleware\CheckSubscription::class,
    ];
}
