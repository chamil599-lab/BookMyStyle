<x-storefront.layout title="Subscription request">
    <div class="shell py-12">
        <div class="max-w-2xl mx-auto">
            <a class="underline" href="{{ route('subscription.required') }}">Back to plans</a>
            <h1 class="section-title mt-4 mb-6">Subscription request</h1>
            <section class="panel p-6 mb-6">
                <h2 class="text-xl font-semibold">{{ $package->name }}</h2>
                <p class="mt-3">LKR {{ number_format($package->price, 2) }} for {{ $package->duration_days }} days</p>
                <p class="mt-3">Your current paid plan stays active while this request is reviewed. {{ $isChangingPlan ? 'An approved change to this plan begins immediately.' : 'If this is a renewal, the new period begins when your current period ends.' }}</p>
            </section>
            @if($errors->any())<div role="alert" class="panel p-4 mb-6">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
            @if(session('error'))<p role="alert" class="panel p-4 mb-6">{{ session('error') }}</p>@endif
            <form action="{{ route('subscription.subscribe', $package) }}" method="POST" class="panel p-6 space-y-6" x-data="{ method: @js(old('payment_method', 'bank_transfer')) }" data-subscription-request>
                @csrf
                <div>
                    <label for="payment-method" class="block mb-2">Payment method</label>
                    <select id="payment-method" name="payment_method" x-model="method" required class="field">
                        <option value="bank_transfer">Bank transfer</option>
                        <option value="one_pay">One Pay</option>
                    </select>
                </div>
                <div x-show="method === 'bank_transfer'">
                    <label for="transaction-id" class="block mb-2">Transaction reference</label>
                    <input id="transaction-id" name="transaction_id" value="{{ old('transaction_id') }}" maxlength="255" :required="method === 'bank_transfer'" class="field">
                </div>
                <p x-show="method === 'one_pay'">Automatic One Pay processing is not available. This records a request for administrator review.</p>
                <p>This form does not process or confirm a payment. Access changes after an administrator reviews your request and verifies the payment.</p>
                <button type="submit" class="button">Submit subscription request</button>
            </form>
        </div>
    </div>
</x-storefront.layout>
