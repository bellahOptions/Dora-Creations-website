<?php

namespace App\Providers;

use App\Support\Cloudinary\CloudinaryAdapter;
use Illuminate\Filesystem\FilesystemAdapter as LaravelFilesystemAdapter;
use Illuminate\Support\Facades\Route;
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
    }
}
