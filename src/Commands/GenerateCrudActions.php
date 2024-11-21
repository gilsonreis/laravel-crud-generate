<?php

namespace Gilsonreis\LaravelCrudGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class GenerateCrudActions extends Command
{
    protected $signature = 'make:crud-actions
                            {--model= : Nome do Model para gerar Actions CRUD}
                            {--name= : Nome da Action em branco}
                            {--directory= : Diretório para a Action em branco}';
    protected $description = 'Gera Actions para um CRUD com base no Model ou uma Action em branco';

    public function handle()
    {
        $modelName = $this->option('model');
        $name = $this->option('name');
        $directory = $this->option('directory');

        if ($modelName) {
            $modelName = Str::studly($modelName);

            if (!class_exists("App\\Models\\{$modelName}")) {
                $this->error("O Model {$modelName} não foi encontrado.");
                return;
            }

            $useCasesNamespace = "App\\UseCases\\{$modelName}";
            $formRequest = "{$modelName}Request";

            $this->generateGetAllAction($modelName, $useCasesNamespace);
            $this->generateShowAction($modelName, $useCasesNamespace);
            $this->generateCreateAction($modelName, $useCasesNamespace, $formRequest);
            $this->generateUpdateAction($modelName, $useCasesNamespace, $formRequest);
            $this->generateDeleteAction($modelName, $useCasesNamespace);

        } elseif ($name) {
            if (!$directory) {
                $this->error('O parâmetro --directory é obrigatório para Actions em branco.');
                return;
            }

            $this->generateEmptyAction($name, $directory);
        } else {
            $this->error('É necessário fornecer --model ou --name e --directory.');
        }
    }

    private function generateEmptyAction($name, $directory)
    {
        $actionName = Str::studly($name);
        $actionPath = app_path("Http/Actions/{$directory}/{$actionName}.php");

        File::ensureDirectoryExists(app_path("Http/Actions/{$directory}"));

        $actionContent = "<?php

namespace App\Http\Actions\\{$directory};

use App\Http\Controllers\Controller;
use Gilsonreis\LaravelCrudGenerator\Traits\ApiResponser;
use Illuminate\Http\Request;

class {$actionName} extends Controller
{
    use ApiResponser;

    public function __invoke(Request \$request)
    {
        try {
            // Implementação da Action
            return \$this->successResponse([]);
        } catch (\Exception \$e) {
            return \$this->errorResponse(\$e->getMessage());
        }
    }
}";

        File::put($actionPath, $actionContent);
        $this->info("Action em branco {$actionName} criada com sucesso.");
    }

    private function generateGetAllAction($modelName, $useCasesNamespace)
    {
        $actionName = "{$modelName}GetAllAction";
        $useCase = "GetAll{$modelName}UseCase";
        $actionPath = app_path("Http/Actions/{$modelName}/{$actionName}.php");

        File::ensureDirectoryExists(app_path("Http/Actions/{$modelName}"));

        $actionContent = "<?php

namespace App\Http\Actions\\{$modelName};

use App\Http\Controllers\Controller;
use Gilsonreis\LaravelCrudGenerator\Support\Filter;
use Gilsonreis\LaravelCrudGenerator\Support\Pagination;
use Gilsonreis\LaravelCrudGenerator\Traits\ApiResponser;
use Illuminate\Http\Request;

use {$useCasesNamespace}\\{$useCase};

class {$actionName} extends Controller
{
    use ApiResponser;

    public function __construct(
        private readonly {$useCase} \$useCase
    ) {}

    public function __invoke(Request \$request)
    {
        try {
            \$columns = \$request->get('columns') ? explode(',', \$request->get('columns')) : ['*'];
            \$orderColumn = \$request->get('orderColumn', 'created_at');
            \$orderDirection = \$request->get('orderDirection', 'asc');
            \$modelFilters = \$request->get('{$modelName}', []);

            \$filter = new Filter(
                columns: \$columns,
                orderColumn: \$orderColumn,
                orderDirection: \$orderDirection,
                filters: \$modelFilters
            );

            \$pagination = new Pagination(
                page: \$request->get('page', 1),
                perPage: \$request->get('perPage', 10)
            );

            \$result = \$this->useCase->handle(\$pagination, \$filter);

            return \$this->successResponse(\$result);
        } catch (\Exception \$e) {
            return \$this->errorResponse(\$e->getMessage());
        }
    }
}";

        File::put($actionPath, $actionContent);
        $this->info("Action {$actionName} criada com sucesso.");
    }

    private function generateShowAction($modelName, $useCasesNamespace)
    {
        $actionName = "{$modelName}ShowAction";
        $useCase = "Show{$modelName}UseCase";
        $actionPath = app_path("Http/Actions/{$modelName}/{$actionName}.php");

        File::ensureDirectoryExists(app_path("Http/Actions/{$modelName}"));

        $actionContent = "<?php

namespace App\Http\Actions\\{$modelName};

use App\Http\Controllers\Controller;
use Gilsonreis\LaravelCrudGenerator\Traits\ApiResponser;
use Illuminate\Http\Request;
use {$useCasesNamespace}\\{$useCase};

class {$actionName} extends Controller
{
    use ApiResponser;

    public function __invoke(Request \$request, {$useCase} \$useCase, int \$id)
    {
        try {
            \$result = \$useCase->handle(\$id);
            return \$this->successResponse(\$result);
        } catch (\Exception \$e) {
            return \$this->errorResponse(\$e->getMessage());
        }
    }
}";

        File::put($actionPath, $actionContent);
        $this->info("Action {$actionName} criada com sucesso.");
    }

    private function generateCreateAction($modelName, $useCasesNamespace, $formRequest)
    {
        $actionName = "{$modelName}CreateAction";
        $useCase = "Create{$modelName}UseCase";
        $actionPath = app_path("Http/Actions/{$modelName}/{$actionName}.php");

        File::ensureDirectoryExists(app_path("Http/Actions/{$modelName}"));

        if (!class_exists("App\\Http\\Requests\\{$formRequest}")) {
            if ($this->confirm("O FormRequest {$formRequest} não existe. Deseja criá-lo?", true)) {
                Artisan::call('make:crud-form-request', ['--model' => $modelName]);
            }
        }

        $actionContent = "<?php

namespace App\Http\Actions\\{$modelName};

use App\Http\Controllers\Controller;
use Gilsonreis\LaravelCrudGenerator\Traits\ApiResponser;
use {$useCasesNamespace}\\{$useCase};
use App\Http\Requests\{$formRequest};

class {$actionName} extends Controller
{
    use ApiResponser;

    public function __invoke({$formRequest} \$request, {$useCase} \$useCase)
    {
        try {
            \$result = \$useCase->handle(\$request->all());
            return \$this->successResponse(\$result);
        } catch (\Exception \$e) {
            return \$this->errorResponse(\$e->getMessage());
        }
    }
}";

        File::put($actionPath, $actionContent);
        $this->info("Action {$actionName} criada com sucesso.");
    }

    private function generateUpdateAction($modelName, $useCasesNamespace, $formRequest)
    {
        $actionName = "{$modelName}UpdateAction";
        $useCase = "Update{$modelName}UseCase";
        $actionPath = app_path("Http/Actions/{$modelName}/{$actionName}.php");

        File::ensureDirectoryExists(app_path("Http/Actions/{$modelName}"));

        if (!class_exists("App\\Http\\Requests\\{$formRequest}")) {
            if ($this->confirm("O FormRequest {$formRequest} não existe. Deseja criá-lo?", true)) {
                Artisan::call('make:crud-form-request', ['--model' => $modelName]);
            }
        }

        $actionContent = "<?php

namespace App\Http\Actions\\{$modelName};

use App\Http\Controllers\Controller;
use Gilsonreis\LaravelCrudGenerator\Traits\ApiResponser;
use {$useCasesNamespace}\\{$useCase};
use App\Http\Requests\{$formRequest};

class {$actionName} extends Controller
{
    use ApiResponser;

    public function __invoke({$formRequest} \$request, {$useCase} \$useCase, int \$id)
    {
        try {
            \$result = \$useCase->handle(\$id, \$request->all());
            return \$this->successResponse(\$result);
        } catch (\Exception \$e) {
            return \$this->errorResponse(\$e->getMessage());
        }
    }
}";

        File::put($actionPath, $actionContent);
        $this->info("Action {$actionName} criada com sucesso.");
    }

    private function generateDeleteAction($modelName, $useCasesNamespace)
    {
        $actionName = "{$modelName}DeleteAction";
        $useCase = "Delete{$modelName}UseCase";
        $actionPath = app_path("Http/Actions/{$modelName}/{$actionName}.php");

        File::ensureDirectoryExists(app_path("Http/Actions/{$modelName}"));

        $actionContent = "<?php

namespace App\Http\Actions\\{$modelName};

use App\Http\Controllers\Controller;
use Gilsonreis\LaravelCrudGenerator\Traits\ApiResponser;
use Illuminate\Http\Request;
use {$useCasesNamespace}\\{$useCase};

class {$actionName} extends Controller
{
    use ApiResponser;

    public function __invoke(Request \$request, {$useCase} \$useCase, int \$id)
    {
        try {
            \$result = \$useCase->handle(\$id);
            return \$this->successResponse(\$result);
        } catch (\Exception \$e) {
            return \$this->errorResponse(\$e->getMessage());
        }
    }
}";

        File::put($actionPath, $actionContent);
        $this->info("Action {$actionName} criada com sucesso.");
    }
}
