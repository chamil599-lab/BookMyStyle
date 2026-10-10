<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPackage;
use Illuminate\Http\Request;

class OwnerSubscriptionController extends Controller
{
    /** All active packages available to subscribe to */
    public function packages()
    {
        $packages = SubscriptionPackage::active()->ordered()->get();

        $user = auth()->user();
        $current = $user->activeSubscription()->with('package')->first();
        $pending = $user->subscriptions()->where('status', 'pending')->with('package')->latest()->first();

        return response()->json([
            'packages' => $packages,
            'current' => $current ? [
                'id' => $current->id,
                'package_id' => $current->package_id,
                'package_name' => $current->package?->name,
                'status' => $current->status,
                'ends_at' => $current->ends_at?->format('Y-m-d'),
                'days_remaining' => max(0, $current->daysRemaining()),
            ] : null,
            'pending' => $pending ? [
                'package_id' => $pending->package_id,
                'package_name' => $pending->package?->name,
                'submitted_at' => $pending->created_at->format('Y-m-d'),
            ] : null,
        ]);
    }

    /** Submit a subscribe / change-plan request */
    public function subscribe(Request $request, SubscriptionPackage $package)
    {
        $user = auth()->user();

        // Block if there is already a pending subscription
        $pendingSub = $user->subscriptions()->where('status', 'pending')->first();
        if ($pendingSub) {
            return response()->json([
                'message' => 'You already have a pending subscription request. Please wait for admin approval before changing plans.',
            ], 422);
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:bank_transfer,one_pay',
            'transaction_id' => 'required_if:payment_method,bank_transfer|nullable|string|max:255',
        ]);

        $activeSubscription = $user->activeSubscription()->first();
        app(\App\Services\SubscriptionService::class)->request($user, $package, $request->only(['payment_method', 'transaction_id']), $user);
        $message = 'Your subscription request has been submitted. Please wait for admin approval.';

        return response()->json(['message' => $message]);
    }

    /** History of all owner's subscriptions */
    public function history()
    {
        $history = auth()->user()
            ->subscriptions()
            ->with('package:id,name,price')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'package_name' => $s->package?->name ?? 'Unknown',
                'amount_paid' => $s->amount_paid,
                'status' => $s->status,
                'payment_method' => $s->payment_method,
                'transaction_id' => $s->transaction_id,
                'starts_at' => $s->starts_at?->format('Y-m-d'),
                'ends_at' => $s->ends_at?->format('Y-m-d'),
                'created_at' => $s->created_at->format('Y-m-d'),
            ]);

        return response()->json($history);
    }
}
