<?php

namespace Gilsonreis\LaravelCrudGenerator\Providers;

use Gilsonreis\LaravelCrudGenerator\Commands\GenerateCrudActions;
use Gilsonreis\LaravelCrudGenerator\Commands\GenerateCrudMenuChoices;
use Gilsonreis\LaravelCrudGenerator\Commands\GenerateCrudModel;
use Gilsonreis\LaravelCrudGenerator\Commands\GenerateCrudRbac;
use Gilsonreis\LaravelCrudGenerator\Commands\GenerateCrudRepository;
use Gilsonreis\LaravelCrudGenerator\Commands\GenerateCrudRoutes;
use Gilsonreis\LaravelCrudGenerator\Commands\GenerateCrudSanctumLogin;
use Gilsonreis\LaravelCrudGenerator\Commands\GenerateCrudUseCase;
use Gilsonreis\LaravelCrudGenerator\Commands\GenerateFormRequest;
use Illuminate\Support\ServiceProvider;

class CrudGeneratorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            GenerateCrudModel::class,
            GenerateCrudRepository::class,
            GenerateCrudUseCase::class,
            GenerateCrudActions::class,
            GenerateFormRequest::class,
            GenerateCrudMenuChoices::class,
            GenerateCrudRoutes::class,
            GenerateCrudSanctumLogin::class,
            GenerateCrudRbac::class
        ]);
    }

    public function boot()
    {
    }
}