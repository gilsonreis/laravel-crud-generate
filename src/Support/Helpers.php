<?php
namespace Gilsonreis\LaravelCrudGenerator\Support;

use Illuminate\Support\Facades\File;

final class Helpers{

    public static function isFirebaseJwtInstalled(): bool
    {
        return File::exists(base_path('vendor/firebase/php-jwt'));
    }

    public static  function isJwtConfigured(){
        $privateKey = env('RSA',null);
        return $privateKey != null;
        }


        public static function isSanctumInstalled(): bool
        {
            return File::exists(base_path('vendor/laravel/sanctum'));
        }


}
