<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
    }

    public function boot()
    {
        Relation::morphMap([
            'items'      => 'App\Models\Item',
            'checklists' => 'App\Models\Checklist',
        ]);
    }
}
