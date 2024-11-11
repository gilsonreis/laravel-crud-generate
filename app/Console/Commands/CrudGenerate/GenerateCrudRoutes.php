<?php

namespace App\Console\Commands\CrudGenerate;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class GenerateCrudRoutes extends Command
{
    protected $signature = 'make:crud-routes
                            {--model= : Nome do Model para gerar rotas CRUD}
                            {--name= : Nome para criar um arquivo de rota em branco com rota GET padrão}';
    protected $description = 'Gera um arquivo de rotas CRUD para um Model específico ou uma rota em branco';

    public function handle()
    {
        $model = $this->option('model');
        $name = $this->option('name');

        if ($model) {
            $this->generateCrudRoutes($model);
        } elseif ($name) {
            $this->generateEmptyRoute($name);
        } else {
            $this->error('É necessário fornecer --model ou --name.');
        }

        // Garante que o autoload das rotas seja adicionado ao routes/api.php
        $this->ensureRouteAutoloadInApi();
    }

    private function generateCrudRoutes($model)
    {
        $modelName = Str::studly($model);
        $modelKebab = Str::kebab($model);
        $modelPluralKebab = Str::plural($modelKebab);

        $routeFilePath = app_path("Routes/{$modelName}Routes.php");

        File::ensureDirectoryExists(app_path('Routes'));

        $routeContent = "<?php

use Illuminate\\Support\\Facades\\Route;
use App\\Http\\Actions\\{$modelName}\\{$modelName}GetAllAction;
use App\\Http\\Actions\\{$modelName}\\{$modelName}ShowAction;
use App\\Http\\Actions\\{$modelName}\\{$modelName}CreateAction;
use App\\Http\\Actions\\{$modelName}\\{$modelName}UpdateAction;
use App\\Http\\Actions\\{$modelName}\\{$modelName}DeleteAction;

Route::prefix('$modelPluralKebab')
    ->name('{$modelKebab}.')
    ->group(function () {
        Route::get('/', {$modelName}GetAllAction::class)->name('index');
        Route::get('/{id}', {$modelName}ShowAction::class)->name('show');
        Route::post('/', {$modelName}CreateAction::class)->name('store');
        Route::put('/{id}', {$modelName}UpdateAction::class)->name('update');
        Route::delete('/{id}', {$modelName}DeleteAction::class)->name('destroy');
    });
";

        if (File::exists($routeFilePath)) {
            $this->warn("As rotas para o Model {$modelName} já existem em {$routeFilePath}.");
            return;
        }

        File::put($routeFilePath, $routeContent);
        $this->info("Arquivo de rotas CRUD para {$modelName} criado com sucesso em app/Routes.");
    }

    private function generateEmptyRoute($name)
    {
        $routeName = Str::studly($name);
        $routeKebab = Str::kebab($name);

        $routeFilePath = app_path("Routes/{$routeName}Routes.php");

        File::ensureDirectoryExists(app_path('Routes'));

        $routeContent = "<?php

use Illuminate\\Support\\Facades\\Route;

Route::prefix('$routeKebab')
    ->name('{$routeKebab}.')
    ->group(function () {
        Route::get('/', fn() => response()->json(['message' => 'Rota {$routeName}']))->name('index');
    });
";

        if (File::exists($routeFilePath)) {
            $this->warn("O arquivo de rotas para {$routeName} já existe em {$routeFilePath}.");
            return;
        }

        File::put($routeFilePath, $routeContent);
        $this->info("Arquivo de rota em branco para {$routeName} criado com sucesso em app/Routes.");
    }

    private function ensureRouteAutoloadInApi()
    {
        $apiRouteFile = base_path('routes/api.php');
        $autoloadStatement = "\n// Carrega automaticamente todas as rotas CRUD em app/Routes\nforeach (glob(app_path('Routes/*.php')) as \$routeFile) {\n    require \$routeFile;\n}\n";

        // Verifica se o arquivo routes/api.php existe; se não, cria com `php artisan install:api`
        if (!File::exists($apiRouteFile)) {
            $this->info('O arquivo routes/api.php não foi encontrado. Criando com `php artisan install:api`...');
            Artisan::call('install:api');
        }

        // Recarrega o conteúdo do arquivo após a criação
        $existingContent = File::get($apiRouteFile);

        // Adiciona a instrução de autoload se ainda não estiver presente
        if (strpos($existingContent, "foreach (glob(app_path('Routes/*.php'))") === false) {
            File::append($apiRouteFile, $autoloadStatement);
            $this->info('Autoload de rotas adicionado ao arquivo routes/api.php.');
        } else {
            $this->info('O autoload de rotas já existe no arquivo routes/api.php.');
        }
    }
}
