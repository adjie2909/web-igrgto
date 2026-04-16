<?php

namespace App\Providers;

use App\Support\NoRenameFilesystem;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->forgetInstance('files');
        $this->app->singleton('files', fn () => new NoRenameFilesystem());
        $this->app->alias('files', Filesystem::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();
    }
}
