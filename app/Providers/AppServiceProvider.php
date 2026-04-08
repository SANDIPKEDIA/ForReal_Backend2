<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use App\Observers\AuditableObserver;
use App\User;
use App\Models\DeviceToken;
use App\Models\Products\Products;
use App\Models\Rent\Rent;
use App\Models\Wishlist\Wishlist;
use App\Models\ProductUserReview;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $railwayDomain = env( 'RAILWAY_PUBLIC_DOMAIN' );
        if ( ! env( 'APP_URL' ) && $railwayDomain ) {
            URL::forceRootUrl( 'https://'.$railwayDomain );
        }
        if ( getenv( 'RAILWAY_ENVIRONMENT' ) !== false ) {
            URL::forceScheme( 'https' );
        }

        $observer = AuditableObserver::class;

        User::observe($observer);
        Products::observe($observer);
        Rent::observe($observer);
        Wishlist::observe($observer);
        DeviceToken::observe($observer);
        ProductUserReview::observe($observer);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
