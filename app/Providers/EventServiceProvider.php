<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use App\Listeners\EnsureUserPersonalFolder;
use App\Listeners\AutoLinkFolderToCompanies;
use App\Models\Folder;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Login::class => [
            EnsureUserPersonalFolder::class,
        ],
        'eloquent.created: App\Models\Folder' => [
            AutoLinkFolderToCompanies::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
