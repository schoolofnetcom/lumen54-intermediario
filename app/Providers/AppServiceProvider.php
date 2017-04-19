<?php

namespace App\Providers;

use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->configure('mail');
        $this->app->alias('mailer',Mailer::class);
    }
}
