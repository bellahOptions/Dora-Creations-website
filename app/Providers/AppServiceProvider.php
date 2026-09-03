<?php

namespace App\Providers;

use App\Services\ActivityLogger;
use App\Support\Cloudinary\CloudinaryAdapter;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Filesystem\FilesystemAdapter as LaravelFilesystemAdapter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem as Flysystem;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Some MySQL/MariaDB hosts (older InnoDB row format defaults) can't index
        // a full varchar(255) under the utf8mb4 charset — cap it so migrations don't
        // exceed the key-length limit on unique/indexed string columns.
        Schema::defaultStringLength(191);

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Livewire's shared update endpoint handles every component action
        // (cart, checkout, admin tables, etc.), so the limit is generous —
        // this is a backstop against scripted abuse, not normal usage.
        Livewire::setUpdateRoute(fn ($handle) => Route::post('/livewire/update', $handle)
            ->middleware(['web', 'throttle:300,1']));

        Storage::extend('cloudinary', function ($app, array $config) {
            $adapter = new CloudinaryAdapter($config);

            return new LaravelFilesystemAdapter(new Flysystem($adapter, $config), $adapter, $config);
        });

        // Single hook for every login, storefront or admin panel, since both
        // share the same guard and this event fires from within it either way.
        Event::listen(Login::class, function (Login $event): void {
            if ($event->user->is_admin) {
                ActivityLogger::admin("{$event->user->name} logged in.");
            } else {
                ActivityLogger::visitor("{$event->user->name} logged in.", $event->user);
            }
        });

        Event::listen(Registered::class, function (Registered $event): void {
            ActivityLogger::visitor("{$event->user->name} created an account.", $event->user);
        });
    }
}
