export default function SubscriptionBanner({ subscription }) {
  if (!subscription) {
    return (
      <div className="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-yellow-800 text-sm">
        No active subscription. Please subscribe to a plan to unlock features.
      </div>
    );
  }

  const { package_name, status, days_remaining, ends_at, features } = subscription;

  const statusColors = {
    active:   'bg-green-100 text-green-700',
    pending:  'bg-yellow-100 text-yellow-700',
    expired:  'bg-red-100 text-red-700',
    canceled: 'bg-gray-100 text-gray-600',
  };

  const urgency = days_remaining <= 7 ? 'border-red-300 bg-red-50' : 'border-blue-200 bg-blue-50';

  return (
    <div className={`rounded-xl border p-5 ${urgency}`}>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p className="text-xs text-gray-500 uppercase tracking-wide font-medium">Current Plan</p>
          <p className="mt-0.5 text-lg font-bold text-gray-800">{package_name ?? 'Unknown'}</p>
        </div>
        <div className="flex items-center gap-3">
          <span className={`px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize ${statusColors[status] ?? statusColors.canceled}`}>
            {status}
          </span>
          {status === 'active' && (
            <span className={`text-sm font-medium ${days_remaining <= 7 ? 'text-red-600' : 'text-gray-600'}`}>
              {days_remaining} day{days_remaining !== 1 ? 's' : ''} remaining
            </span>
          )}
        </div>
      </div>

      {features && (
        <div className="mt-4 flex flex-wrap gap-3 text-xs text-gray-600">
          {features.max_salons != null && (
            <span className="bg-white border border-gray-200 rounded-md px-2 py-1">
              {features.max_salons} salon{features.max_salons !== 1 ? 's' : ''}
            </span>
          )}
          {features.max_staff != null && (
            <span className="bg-white border border-gray-200 rounded-md px-2 py-1">
              {features.max_staff} staff
            </span>
          )}
          {features.max_services != null && (
            <span className="bg-white border border-gray-200 rounded-md px-2 py-1">
              {features.max_services} services
            </span>
          )}
          {features.max_bookings_per_month != null && (
            <span className="bg-white border border-gray-200 rounded-md px-2 py-1">
              {features.max_bookings_per_month} bookings/mo
            </span>
          )}
          {features.analytics && (
            <span className="bg-white border border-gray-200 rounded-md px-2 py-1">Analytics</span>
          )}
          {features.priority_support && (
            <span className="bg-white border border-gray-200 rounded-md px-2 py-1">Priority support</span>
          )}
        </div>
      )}

      {ends_at && (
        <p className="mt-3 text-xs text-gray-500">Expires {ends_at}</p>
      )}
    </div>
  );
}
