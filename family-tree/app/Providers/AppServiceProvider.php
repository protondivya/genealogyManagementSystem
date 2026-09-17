<?php

namespace App\Providers;

use App\Models\Citation;
use App\Models\Family;
use App\Models\FamilyInvitation;
use App\Models\LifeEvent;
use App\Models\MediaItem;
use App\Models\Person;
use App\Models\Relationship;
use App\Models\Source;
use Illuminate\Database\Eloquent\Relations\Relation;
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

        Relation::morphMap([
            'person' => Person::class,
            'relationship' => Relationship::class,
            'life_event' => LifeEvent::class,
            'family' => Family::class,
            'source' => Source::class,
            'citation' => Citation::class,
            'media_item' => MediaItem::class,
            'invitation' => FamilyInvitation::class,
        ]);
    }
}
