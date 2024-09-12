<?php

namespace Skn036\Gmail;

use Illuminate\Support\ServiceProvider;

class GmailServiceProvider extends ServiceProvider
{
    public function register() {
        $this->app->bind(\Skn036\Gmail\Gmail::class, function() {
            return new Gmail();
        });
    }
}
