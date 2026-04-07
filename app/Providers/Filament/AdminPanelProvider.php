<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use AlizHarb\ActivityLog\ActivityLogPlugin;
use App\Filament\Widgets\SalesChartWidget;
use App\Filament\Widgets\RecentOrdersWidget;
use App\Filament\Widgets\DashboardOverview;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use App\Filament\Pages\PosTerminal;
use Filament\Actions\Action;
use App\Models\PosSession;
use App\Helpers\SettingsHelper;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName(fn () => SettingsHelper::siteName())
            ->colors([
                'primary' => Color::Violet,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                // Sales & Operations (Most Important - Always Expanded)
                NavigationGroup::make()
                    ->label(fn () => __('navigation.groups.sales'))
                    ->icon('heroicon-o-shopping-cart')
                    ->collapsed(false)
                    ->collapsible(false),

                // Inventory Management
                NavigationGroup::make()
                    ->label(fn () => __('navigation.groups.inventory'))
                    ->icon('heroicon-o-cube')
                    ->collapsed(false),

                // Reports & Analytics
                NavigationGroup::make()
                    ->label(fn () => __('navigation.groups.reports'))
                    ->icon('heroicon-o-chart-bar')
                    ->collapsed(true),

                // System & Configuration
                NavigationGroup::make()
                    ->label(fn () => __('navigation.groups.system'))
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(true),
            ])
            ->navigationItems([
                // POS Terminal - Highest Priority
                NavigationItem::make('pos_terminal')
                    ->label(fn () => __('pos.common.navigation_label'))
                    ->url(fn (): string => PosTerminal::getUrl())
                    ->icon('heroicon-o-computer-desktop')
                    ->group(fn () => __('navigation.groups.sales'))
                    ->sort(0)
                    ->badge(fn () => PosSession::where('status', 'open')->count())
                    ->openUrlInNewTab(),
            ])
            ->userMenuItems([
                Action::make('pos_terminal')
                    ->label(fn () => __('pos.common.navigation_label'))
                    ->url(fn (): string => PosTerminal::getUrl())
                    ->icon('heroicon-o-computer-desktop')
                    ->openUrlInNewTab(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                DashboardOverview::class,
                SalesChartWidget::class,
                RecentOrdersWidget::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup(fn () => __('navigation.groups.system'))
                    ->navigationSort(3)
                    ->navigationIcon('heroicon-o-shield-check'),
                
                ActivityLogPlugin::make()
                    ->label('Activity Log')
                    ->pluralLabel('Activity Logs')
                    ->navigationGroup(fn () => __('navigation.groups.system'))
                    ->navigationIcon('heroicon-o-clipboard-document-list')
                    ->navigationSort(99),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
