<?php

namespace Gilsonreis\LaravelCrudGenerator\Providers;

use Illuminate\Support\ServiceProvider;

class CrudGeneratorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            \Gilsonreis\LaravelCrudGenerator\Commands\GenerateCrudModel::class,
            \Gilsonreis\LaravelCrudGenerator\Commands\GenerateCrudRepository::class,
            \Gilsonreis\LaravelCrudGenerator\Commands\GenerateCrudUseCase::class,
            \Gilsonreis\LaravelCrudGenerator\Commands\GenerateCrudActions::class,
            \Gilsonreis\LaravelCrudGenerator\Commands\GenerateFormRequest::class,
            \Gilsonreis\LaravelCrudGenerator\Commands\GenerateCrudMenuChoices::class,
        ]);
    }

    public function boot()
    {
    }
}