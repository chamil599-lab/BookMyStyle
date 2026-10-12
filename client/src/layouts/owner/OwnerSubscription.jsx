import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../utils/api';

const PACKAGE_BG   = ['bg-ds-tertiary-fixed', 'bg-ds-secondary-container', 'bg-ds-primary-fixed/40', 'bg-[#e1e3e4]', 'bg-ds-error-container'];
const PACKAGE_ICON = ['diamond', 'star', 'workspace_premium', 'bolt', 'local_fire_department'];
const PACKAGE_IC   = ['text-ds-tertiary', 'text-ds-secondary', 'text-ds-primary', 'text-ds-on-surface-variant', 'text-ds-error'];

const FEATURES = [
  { key: 'max_salons',             icon: 'store',          fmt: v => `${v ?? '∞'} salon${v !== 1 ? 's' : ''}` },
  { key: 'max_staff',              icon: 'group',          fmt: v => `${v ?? '∞'} staff` },
  { key: 'max_services',           icon: 'content_cut',    fmt: v => `${v ?? '∞'} services` },
  { key: 'max_bookings_per_month', icon: 'calendar_month', fmt: v => `${v ?? '∞'} bookings / mo` },
  { key: 'analytics',              icon: 'bar_chart',      fmt: () => 'Analytics',       bool: true },
  { key: 'priority_support',       icon: 'support_agent',  fmt: () => 'Priority support', bool: true },
];

const STATUS_BADGE = {
  active:   'bg-ds-primary/10 text-ds-primary',
  pending:  'bg-ds-tertiary-fixed text-ds-tertiary',
  expired:  'bg-ds-error-container text-ds-error',
  canceled: 'bg-[#e1e3e4] text-ds-outline',
};

function SubscribeModal({ pkg, current, onClose, onSuccess }) {
  const [method, setMethod] = useState('bank_transfer');
  const [txId, setTxId]     = useState('');
  const [error, setError]   = useState('');

  const isChanging = current && current.package_id !== pkg.id;

  const mutation = useMutation({
    mutationFn: () => api.post(`/owner/subscription/subscribe/${pkg.id}`, {
      payment_method: method,
      transaction_id: method === 'bank_transfer' ? txId : undefined,
    }),
    onSuccess: () => { onSuccess(); onClose(); },
    onError: (e) => setError(e.response?.data?.message || 'Failed to submit request.'),
  });

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4">
      <div className="bg-white rounded-lg w-full max-w-md shadow-2xl">
        {/* Header */}
        <div className="p-6 border-b border-[#e1e3e4]">
          <div className="flex items-start justify-between">
            <div>
              <p className="text-xs font-bold text-ds-outline uppercase tracking-widest mb-1">
                {isChanging ? 'Change Plan' : 'Subscribe'}
              </p>
              <h3 className="font-headline text-xl font-bold text-[#191c1d]">{pkg.name}</h3>
              <p className="font-headline text-2xl font-extrabold text-ds-primary mt-1">
                LKR {Number(pkg.price).toLocaleString()}
                <span className="text-sm font-normal text-ds-outline"> / {pkg.duration_days} days</span>
              </p>
            </div>
            <button onClick={onClose} className="p-1.5 rounded-lg hover:bg-ds-surface-container-low text-ds-outline transition-colors">
              <span className="material-symbols-outlined">close</span>
            </button>
          </div>
          {isChanging && (
            <div className="mt-3 flex items-center gap-2 text-xs bg-ds-tertiary-fixed text-ds-tertiary px-3 py-2 rounded-lg">
              <span className="material-symbols-outlined text-sm">info</span>
              Your current plan will be replaced after admin approval.
            </div>
          )}
        </div>

        {/* Payment method */}
        <div className="p-6 space-y-4">
          <p className="text-sm font-semibold text-ds-on-surface-variant">Payment Method</p>

          <div className="grid grid-cols-2 gap-3">
            {[
              { value: 'bank_transfer', label: 'Bank Transfer', icon: 'account_balance' },
              { value: 'one_pay',       label: 'OnePay',        icon: 'credit_card' },
            ].map(opt => (
              <button
                key={opt.value}
                onClick={() => { setMethod(opt.value); setTxId(''); setError(''); }}
                className={`flex flex-col items-center gap-2 p-4 rounded-lg border-2 transition-all ${
                  method === opt.value
                    ? 'border-ds-primary bg-ds-primary/5'
                    : 'border-[#e1e3e4] hover:border-ds-outline-variant'
                }`}
              >
                <span className={`material-symbols-outlined text-2xl ${method === opt.value ? 'text-ds-primary' : 'text-ds-outline'}`}>
                  {opt.icon}
                </span>
                <span className={`text-sm font-semibold ${method === opt.value ? 'text-ds-primary' : 'text-ds-on-surface-variant'}`}>
                  {opt.label}
                </span>
              </button>
            ))}
          </div>

          {method === 'bank_transfer' && (
            <div className="space-y-3">
              <div className="bg-ds-surface-container-low rounded-lg p-4 text-sm space-y-1.5">
                <p className="font-bold text-[#191c1d]">Bank Transfer Details</p>
                <p className="text-ds-on-surface-variant">Bank: <span className="font-semibold">Commercial Bank of Ceylon</span></p>
                <p className="text-ds-on-surface-variant">Account: <span className="font-semibold">1234567890</span></p>
                <p className="text-ds-on-surface-variant">Name: <span className="font-semibold">BookMyStyle Pvt Ltd</span></p>
                <p className="text-ds-on-surface-variant">Amount: <span className="font-extrabold text-ds-primary">LKR {Number(pkg.price).toLocaleString()}</span></p>
              </div>
              <div>
                <label className="text-xs font-bold text-ds-outline uppercase tracking-widest block mb-1.5">
                  Transaction / Reference ID <span className="text-ds-error">*</span>
                </label>
                <input
                  value={txId}
                  onChange={e => { setTxId(e.target.value); setError(''); }}
                  placeholder="e.g. TRN20240417123456"
                  className="w-full bg-ds-surface-container-low border-none rounded-lg py-2.5 px-4 text-sm focus:outline-hidden focus:ring-2 focus:ring-ds-primary/20"
                />
              </div>
            </div>
          )}

          {method === 'one_pay' && (
            <div className="bg-ds-surface-container-low rounded-lg p-4 text-sm">
              <p className="font-bold text-[#191c1d] mb-1">OnePay Instructions</p>
              <p className="text-ds-outline">Complete payment via OnePay and submit. Admin will verify and activate your plan.</p>
            </div>
          )}

          {error && (
            <div className="flex items-start gap-2 bg-ds-error-container text-ds-error px-4 py-3 rounded-lg text-sm">
              <span className="material-symbols-outlined text-base shrink-0 mt-0.5">error</span>
              {error}
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="p-6 pt-0 flex gap-3">
          <button onClick={onClose}
            className="flex-1 py-3 rounded-lg bg-ds-surface-container-low text-ds-on-surface-variant text-sm font-semibold hover:bg-[#e1e3e4] transition-colors">
            Cancel
          </button>
          <button
            onClick={() => mutation.mutate()}
            disabled={mutation.isPending || (method === 'bank_transfer' && !txId.trim())}
            className="flex-1 py-3 rounded-lg bg-ds-primary text-white text-sm font-semibold hover:bg-ds-primary-container transition-colors disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            {mutation.isPending && <span className="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />}
            {isChanging ? 'Submit Change Request' : 'Submit Request'}
          </button>
        </div>
      </div>
    </div>
  );
}

export default function OwnerSubscription() {
  const qc = useQueryClient();
  const [selectedPkg, setSelectedPkg] = useState(null);
  const [tab, setTab] = useState('plans'); // 'plans' | 'history'
  const [success, setSuccess] = useState('');

  const { data, isLoading } = useQuery({
    queryKey: ['owner-subscription-packages'],
    queryFn: () => api.get('/owner/subscription/packages').then(r => r.data),
  });

  const { data: history = [], isLoading: histLoading } = useQuery({
    queryKey: ['owner-subscription-history'],
    queryFn: () => api.get('/owner/subscription/history').then(r => r.data),
    enabled: tab === 'history',
  });

  const packages = data?.packages ?? [];
  const current  = data?.current;
  const pending  = data?.pending;

  const handleSuccess = () => {
    qc.invalidateQueries(['owner-subscription-packages']);
    qc.invalidateQueries(['owner-subscription-history']);
    qc.invalidateQueries(['owner-stats']);
    setSuccess('Your subscription request has been submitted. Admin will review and activate your plan.');
    setTimeout(() => setSuccess(''), 6000);
  };

  return (
    <div className="space-y-6">
      {selectedPkg && (
        <SubscribeModal
          pkg={selectedPkg}
          current={current}
          onClose={() => setSelectedPkg(null)}
          onSuccess={handleSuccess}
        />
      )}

      <h1 className="font-headline text-2xl font-bold text-ds-primary">Subscription</h1>

      {/* Success banner */}
      {success && (
        <div className="flex items-center gap-3 bg-ds-primary/10 border border-ds-primary/20 text-ds-primary px-5 py-4 rounded-lg text-sm font-semibold">
          <span className="material-symbols-outlined text-xl">check_circle</span>
          {success}
        </div>
      )}

      {/* Inactive notice — no current active subscription and no pending request */}
      {!current && !pending && (
        <div className="flex items-start gap-3 bg-ds-error-container text-ds-error px-5 py-4 rounded-lg text-sm">
          <span className="material-symbols-outlined text-xl shrink-0 mt-0.5">lock</span>
          <div>
            <p className="font-bold">Your subscription is not active</p>
            <p className="mt-0.5">Reactivate your previous plan or pick a new one below to regain access to your dashboard.</p>
          </div>
        </div>
      )}

      {/* Pending notice */}
      {pending && (
        <div className="flex items-start gap-3 bg-ds-tertiary-fixed text-ds-tertiary px-5 py-4 rounded-lg text-sm">
          <span className="material-symbols-outlined text-xl shrink-0 mt-0.5">hourglass_top</span>
          <div>
            <p className="font-bold">Pending approval</p>
            <p className="mt-0.5">Your request for <span className="font-bold">{pending.package_name}</span> submitted on {pending.submitted_at} is awaiting admin approval.</p>
          </div>
        </div>
      )}

      {/* Current plan card */}
      {current && (
        <div className={`rounded-lg p-5 flex flex-wrap items-center justify-between gap-4 ${
          current.days_remaining <= 7 ? 'bg-ds-error-container border border-ds-error/20' : 'bg-ds-primary/5 border border-ds-primary/10'
        }`}>
          <div className="flex items-center gap-4">
            <div className="w-12 h-12 rounded-full bg-ds-primary-container flex items-center justify-center shrink-0">
              <span className="material-symbols-outlined text-ds-on-primary-container text-xl">card_membership</span>
            </div>
            <div>
              <p className="text-xs font-bold text-ds-outline uppercase tracking-widest">Current Plan</p>
              <p className="font-headline text-lg font-extrabold text-ds-primary">{current.package_name}</p>
            </div>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <span className={`px-2.5 py-0.5 rounded-full text-xs font-bold capitalize ${STATUS_BADGE[current.status] ?? ''}`}>
              {current.status}
            </span>
            {current.ends_at && (
              <span className={`text-sm font-semibold ${current.days_remaining <= 7 ? 'text-ds-error' : 'text-ds-outline'}`}>
                {current.days_remaining} day{current.days_remaining !== 1 ? 's' : ''} remaining · expires {current.ends_at}
              </span>
            )}
          </div>
        </div>
      )}

      {/* Tabs */}
      <div className="flex gap-1 bg-ds-surface-container-low p-1 rounded-full w-fit">
        {[{ key: 'plans', label: 'Available Plans' }, { key: 'history', label: 'History' }].map(t => (
          <button key={t.key} onClick={() => setTab(t.key)}
            className={`px-5 py-2 rounded-full text-sm font-semibold transition-all ${
              tab === t.key ? 'bg-white text-ds-primary shadow-xs' : 'text-ds-outline hover:text-ds-on-surface-variant'
            }`}>{t.label}</button>
        ))}
      </div>

      {/* Plans grid */}
      {tab === 'plans' && (
        isLoading ? (
          <div className="flex items-center justify-center h-48">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-ds-primary" />
          </div>
        ) : (
          <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
            {packages.map((pkg, i) => {
              const isCurrent = current?.package_id === pkg.id;
              const isPending = pending?.package_id === pkg.id;

              return (
                <div key={pkg.id} className={`bg-white rounded-lg overflow-hidden flex flex-col transition-all ${
                  isCurrent ? 'ring-2 ring-ds-primary' : 'hover:shadow-md'
                } ${pkg.is_popular ? 'relative' : ''}`}>
                  {/* Popular badge */}
                  {pkg.is_popular && (
                    <div className="absolute top-4 right-4 z-10">
                      <span className="text-[10px] bg-ds-primary text-white px-2 py-0.5 rounded-full font-bold uppercase tracking-widest">
                        Popular
                      </span>
                    </div>
                  )}

                  {/* Top accent bar */}
                  <div className={`h-1 w-full ${isCurrent ? 'bg-ds-primary' : 'bg-[#e1e3e4]'}`} />

                  <div className="p-6 flex flex-col flex-1">
                    {/* Icon + name */}
                    <div className={`w-14 h-14 rounded-xl ${PACKAGE_BG[i % PACKAGE_BG.length]} flex items-center justify-center mb-4`}>
                      <span className={`material-symbols-outlined text-2xl ${PACKAGE_IC[i % PACKAGE_IC.length]}`}>
                        {PACKAGE_ICON[i % PACKAGE_ICON.length]}
                      </span>
                    </div>

                    <h3 className="font-headline text-lg font-bold text-[#191c1d]">{pkg.name}</h3>

                    <div className="mt-2 mb-5">
                      <span className="font-headline text-3xl font-extrabold text-ds-primary">
                        LKR {Number(pkg.price).toLocaleString()}
                      </span>
                      <span className="text-sm text-ds-outline"> / {pkg.duration_days} days</span>
                    </div>

                    {/* Features */}
                    <ul className="space-y-2.5 flex-1">
                      {FEATURES.map(feat => {
                        const val = pkg[feat.key];
                        if (feat.bool && !val) return null;
                        return (
                          <li key={feat.key} className="flex items-center gap-2.5 text-sm text-ds-on-surface-variant">
                            <span className={`material-symbols-outlined text-base shrink-0 ${
                              feat.bool ? 'text-ds-primary' : 'text-ds-outline'
                            }`}>{feat.icon}</span>
                            {feat.fmt(val)}
                          </li>
                        );
                      })}
                    </ul>

                    {/* CTA */}
                    <div className="mt-6">
                      {isCurrent ? (
                        <button
                          onClick={() => !pending && setSelectedPkg(pkg)}
                          disabled={!!pending}
                          className={`w-full py-2.5 rounded-lg text-sm font-bold transition-all flex items-center justify-center gap-2 ${
                            pending
                              ? 'bg-[#e1e3e4] text-ds-outline cursor-not-allowed'
                              : 'bg-ds-primary/10 text-ds-primary hover:bg-ds-primary hover:text-white'
                          }`}
                        >
                          <span className="material-symbols-outlined text-base">refresh</span>
                          Reactivate Plan
                        </button>
                      ) : isPending ? (
                        <div className="w-full py-2.5 rounded-lg bg-ds-tertiary-fixed text-ds-tertiary text-sm font-bold text-center flex items-center justify-center gap-2">
                          <span className="material-symbols-outlined text-base">hourglass_top</span>
                          Pending Approval
                        </div>
                      ) : (
                        <button
                          onClick={() => !pending && setSelectedPkg(pkg)}
                          disabled={!!pending}
                          className={`w-full py-2.5 rounded-lg text-sm font-bold transition-all ${
                            pending
                              ? 'bg-[#e1e3e4] text-ds-outline cursor-not-allowed'
                              : current
                              ? 'bg-ds-surface-container-low text-ds-primary hover:bg-ds-primary/10 border border-ds-primary/20'
                              : 'bg-ds-primary text-white hover:bg-ds-primary-container'
                          }`}
                        >
                          {current ? 'Switch to this Plan' : 'Subscribe'}
                        </button>
                      )}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        )
      )}

      {/* History tab */}
      {tab === 'history' && (
        <div className="bg-white rounded-lg overflow-hidden">
          {histLoading ? (
            <div className="flex items-center justify-center h-48">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-ds-primary" />
            </div>
          ) : history.length === 0 ? (
            <div className="text-center py-16 text-ds-outline">
              <span className="material-symbols-outlined text-4xl block mb-2">receipt_long</span>
              No subscription history
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-[#e1e3e4] bg-ds-surface-container-low">
                    {['Package', 'Amount Paid', 'Status', 'Payment', 'Ref / Transaction ID', 'Started', 'Expires'].map(h => (
                      <th key={h} className="text-left px-5 py-3 text-xs font-bold text-ds-outline uppercase tracking-widest whitespace-nowrap">{h}</th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {history.map(h => (
                    <tr key={h.id} className="border-b border-ds-surface-container-low hover:bg-[#f8f9fa] transition-colors">
                      <td className="px-5 py-4 font-semibold text-[#191c1d]">{h.package_name}</td>
                      <td className="px-5 py-4 font-bold text-ds-primary">
                        LKR {Number(h.amount_paid ?? 0).toLocaleString()}
                      </td>
                      <td className="px-5 py-4">
                        <span className={`inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold capitalize ${STATUS_BADGE[h.status] ?? 'bg-[#e1e3e4] text-ds-outline'}`}>
                          {h.status}
                        </span>
                      </td>
                      <td className="px-5 py-4 capitalize text-ds-on-surface-variant">
                        {h.payment_method?.replace('_', ' ') ?? '—'}
                      </td>
                      <td className="px-5 py-4 text-xs text-ds-outline font-mono">{h.transaction_id ?? '—'}</td>
                      <td className="px-5 py-4 text-xs text-ds-outline whitespace-nowrap">{h.starts_at ?? '—'}</td>
                      <td className="px-5 py-4 text-xs whitespace-nowrap">
                        {h.ends_at ? (
                          <span className={new Date(h.ends_at) < new Date() && h.status === 'active' ? 'text-ds-error font-semibold' : 'text-ds-on-surface-variant'}>
                            {h.ends_at}
                          </span>
                        ) : '—'}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
