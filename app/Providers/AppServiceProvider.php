<?php

namespace App\Providers;

use App\Models\HardwareRequest;
use App\Models\SoftwareRequest;
use App\Models\User;
use App\Policies\RequestPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
        Vite::prefetch(concurrency: 3);

        // RequestPolicy dipakai bareng buat dua model ini karena struktur
        // approval-nya sama persis. Nggak bisa kepakai lewat auto-discovery
        // Laravel (nama class-nya nggak match konvensi {Model}Policy),
        // jadi harus didaftarin manual di sini.
        Gate::policy(HardwareRequest::class, RequestPolicy::class);
        Gate::policy(SoftwareRequest::class, RequestPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}