<?php

namespace App\Http;

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureUserIsApproved;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middlewareAliases = [
        'approved' => EnsureUserIsApproved::class,
        'subscription' => CheckSubscription::class,
    ];
}
