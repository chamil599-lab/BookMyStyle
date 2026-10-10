<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPackage;
use Illuminate\Http\Request;

class SubscriptionRequiredController extends Controller
{
    public function index()
    {
        // Get all active packages
        $packages = SubscriptionPackage::active()
            ->ordered()
            ->get();

        return view('subscription.required', compact('packages'));
    }
}