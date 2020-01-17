<?php

namespace Vvk\Upyun\FileSystem;

use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Storage;

class VvkUpyunServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('upyun', function ($app, $config) {
            $adapter = new UpyunAdapter($config);
            return new Filesystem($adapter);
        });
    }
}
