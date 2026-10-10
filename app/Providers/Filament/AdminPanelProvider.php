<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\Auth\Login;
use App\Filament\Admin\Pages\Auth\Register;
use App\Filament\Admin\Pages\PendingApproval;
use App\Http\Middleware\EnsureUserIsApproved;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('filament')
            ->homeUrl(fn () => route('dashboard'))
            ->colors([
                'primary' => Color::Green,
                'danger' => Color::Red,
                'gray' => Color::Slate,
                'info' => Color::Blue,
                'success' => Color::Green,
                'warning' => Color::Amber,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('20rem')
            ->brandLogo(null)
            ->brandName(fn (): string => auth()->check() ? auth()->user()->name.'\'s Dashboard' : 'Dashboard')
            ->favicon(asset('logo2.png'))
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                PendingApproval::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->widgets([])
            ->login(Login::class)
            ->registration(Register::class)
            ->passwordReset()
            ->emailVerification()
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                \App\Http\Middleware\PrivateAccountResponse::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugin(FilamentShieldPlugin::make())
            ->renderHook('panels::head.end', fn (): HtmlString => new HtmlString(<<<'HTML'
                <style>
                    /* Filament Sidebar Custom Theme - Start */
                    .fi-sidebar {
                        /* Slate 800 -> Slate 700 */
                        background: linear-gradient(180deg, #1e293b 0%, #334155 100%) !important;
                    }
                    /* Sidebar background layers */
                    .fi-sidebar-header {
                        /* Slate 800 -> Slate 700 */
                        background: linear-gradient(180deg, #1e293b 0%, #334155 100%) !important;
                        /* Slate 400 @ 20% */
                        border-color: #94A3B833 !important;
                    }
                    .fi-sidebar-nav,
                    .fi-sidebar-footer {
                        /* Slate 800 -> Slate 700 */
                        background: linear-gradient(180deg, #1e293b 0%, #334155 100%) !important;
                    }
                    .fi-sidebar .fi-logo,
                    .fi-sidebar .fi-sidebar-item-icon {
                        /* Slate 200 */
                        color: #e2e8f0 !important;
                    }
                    .fi-sidebar .fi-sidebar-group-label,
                    .fi-sidebar .fi-sidebar-item-label {
                        /* Slate 200 */
                        color: #e2e8f0 !important;
                    }
                    /* Sidebar hover and focus colors */
                    .fi-sidebar .fi-sidebar-item-btn:hover {
                        background-color: #ffffff !important;
                    }
                    .fi-sidebar .fi-sidebar-item-btn:hover .fi-sidebar-item-label,
                    .fi-sidebar .fi-sidebar-item:hover .fi-sidebar-item-label,
                    .fi-sidebar .fi-sidebar-item-btn:hover .fi-sidebar-item-icon,
                    .fi-sidebar .fi-sidebar-item:hover .fi-sidebar-item-icon,
                    .fi-sidebar .fi-sidebar-item-btn:hover .fi-sidebar-item-icon svg,
                    .fi-sidebar .fi-sidebar-item:hover .fi-sidebar-item-icon svg,
                    .fi-sidebar .fi-sidebar-item-btn:hover .fi-icon,
                    .fi-sidebar .fi-sidebar-item:hover .fi-icon,
                    .fi-sidebar .fi-sidebar-item-btn:focus-visible .fi-sidebar-item-label,
                    .fi-sidebar .fi-sidebar-item-btn:focus-visible .fi-sidebar-item-icon,
                    .fi-sidebar .fi-sidebar-item-btn:focus-visible .fi-sidebar-item-icon svg,
                    .fi-sidebar .fi-sidebar-item-btn:focus-visible .fi-icon {
                        /* Blue 600 */
                        color: #16a34a !important;
                    }
                    .fi-sidebar .fi-sidebar-item-btn:hover .fi-sidebar-item-icon *,
                    .fi-sidebar .fi-sidebar-item:hover .fi-sidebar-item-icon *,
                    .fi-sidebar .fi-sidebar-item-btn:hover .fi-icon *,
                    .fi-sidebar .fi-sidebar-item:hover .fi-icon *,
                    .fi-sidebar .fi-sidebar-item-btn:focus-visible .fi-sidebar-item-icon *,
                    .fi-sidebar .fi-sidebar-item-btn:focus-visible .fi-icon * {
                        /* Green 600 */
                        stroke: #16a34a !important;
                        color: #16a34a !important;
                    }
                    .fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-btn {
                        background-color: #ffffff !important;
                    }
                    .fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-label,
                    .fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-icon {
                        /* Green 600 */
                        color: #16a34a !important;
                    }
                    /* Dark mode parity for hover/active sidebar label + icon colors */
                    .dark .fi-sidebar .fi-sidebar-item-btn:hover .fi-sidebar-item-label,
                    .dark .fi-sidebar .fi-sidebar-item:hover .fi-sidebar-item-label,
                    .dark .fi-sidebar .fi-sidebar-item-btn:hover .fi-sidebar-item-icon,
                    .dark .fi-sidebar .fi-sidebar-item:hover .fi-sidebar-item-icon,
                    .dark .fi-sidebar .fi-sidebar-item-btn:hover .fi-icon,
                    .dark .fi-sidebar .fi-sidebar-item:hover .fi-icon,
                    .dark .fi-sidebar .fi-sidebar-item-btn:focus-visible .fi-sidebar-item-label,
                    .dark .fi-sidebar .fi-sidebar-item-btn:focus-visible .fi-sidebar-item-icon,
                    .dark .fi-sidebar .fi-sidebar-item-btn:focus-visible .fi-icon,
                    .dark .fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-label,
                    .dark .fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-icon {
                        /* Green 600 */
                        color: #16a34a !important;
                    }
                    .dark .fi-sidebar .fi-sidebar-item-btn:hover .fi-sidebar-item-icon *,
                    .dark .fi-sidebar .fi-sidebar-item:hover .fi-sidebar-item-icon *,
                    .dark .fi-sidebar .fi-sidebar-item-btn:hover .fi-icon *,
                    .dark .fi-sidebar .fi-sidebar-item:hover .fi-icon *,
                    .dark .fi-sidebar .fi-sidebar-item-btn:focus-visible .fi-sidebar-item-icon *,
                    .dark .fi-sidebar .fi-sidebar-item-btn:focus-visible .fi-icon * {
                        /* Green 600 */
                        stroke: #16a34a !important;
                        color: #16a34a !important;
                    }
                    .dark .fi-sidebar .fi-sidebar-item-btn:hover,
                    .dark .fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-btn,
                    .dark .fi-sidebar .fi-sidebar-nav .fi-sidebar-item-btn:hover,
                    .dark .fi-sidebar .fi-sidebar-nav .fi-sidebar-item.fi-active .fi-sidebar-item-btn,
                    .dark .fi-sidebar .fi-sidebar-item .fi-sidebar-item-btn:hover,
                    .dark .fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-btn,
                    [data-theme="dark"] .fi-sidebar .fi-sidebar-item-btn:hover,
                    [data-theme="dark"] .fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-btn,
                    html.dark .fi-sidebar .fi-sidebar-item-btn:hover,
                    html.dark .fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-btn {
                        background-color: #ffffff !important;
                    }
                    /* Sidebar navigation badge (number pill) */
                    .fi-sidebar .fi-badge {
                        background-color: #16a34a !important;
                        color: #ffffff !important;
                    }
                    /* Admin form/table label colors */
                    .fi-fo-field-wrp-label,
                    .fi-in-entry-wrp-label,
                    .fi-ta-header-cell-label {
                        /* Green 600 */
                        color: #16a34a !important;
                    }
                    /* Filament Sidebar Custom Theme - End */
                </style>
            HTML))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->authMiddleware([
                Authenticate::class,
                EnsureUserIsApproved::class,
            ], isPersistent: true);
    }
}
