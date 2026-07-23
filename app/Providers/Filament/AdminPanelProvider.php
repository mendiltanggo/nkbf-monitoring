<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('NKBF')
            ->brandLogo(fn () => new \Illuminate\Support\HtmlString('
                <div style="display: flex; align-items: center; gap: 12px;">
                    <img src="' . secure_asset('images/logo.png') . '" alt="Logo NKBF" style="height: 3rem;">
                    <span style="font-weight: 800; font-size: 1.5rem; letter-spacing: 1px;">NKBF</span>
                </div>
            '))
            //->brandLogo(asset('images/logo.png'))
            //->brandLogoHeight('3.5rem')
            ->favicon(secure_asset('images/logo.png'))
             // Pastikan baris ini ada di paling atas file jika belum ada

// ... kode di dalam fungsi panel(Panel $panel)

            ->colors([
                // UNGU ELEGAN (Deep Violet): Digunakan untuk tombol utama dan interaksi aktif
                'primary' => Color::hex('#6D28D9'), 

                // BIRU PREMIUM (Royal Blue): Digunakan untuk badge, link, atau informasi
                'info'    => Color::hex('#2563EB'), 

                // ABU-ABU KEBIRUAN (Slate): Rahasia agar background putih tidak terlihat kusam
                'gray'    => Color::Slate, 
                
                // Warna pendukung lainnya (menggunakan warna redup yang elegan)
                'danger'  => Color::Rose,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->font('Poppins') // atau bisa juga pakai 'Inter'
            ->sidebarFullyCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                //Widgets\AccountWidget::class,
                //Widgets\FilamentInfoWidget::class,
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
