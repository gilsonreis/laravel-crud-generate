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
        $this->ensureApiResponserTraitExists();

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

use Gilsonreis\LaravelCrudGenerator\Traits\ApiResponser;use Illuminate\Http\Request;

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

use ;use Gilsonreis\LaravelCrudGenerator\Support\Filter;use Gilsonreis\LaravelCrudGenerator\Support\Pagination;use Gilsonreis\LaravelCrudGenerator\Traits\ApiResponser;use Illuminate\Http\Request;
{$useCasesNamespace}\\{$useCase};

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

use ;use Gilsonreis\LaravelCrudGenerator\Traits\ApiResponser;use Illuminate\Http\Request;
{$useCasesNamespace}\\{$useCase};

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

use ;use ;use Gilsonreis\LaravelCrudGenerator\Traits\ApiResponser;
{$useCasesNamespace}\\{$useCase};
{$formRequest};

class {$actionName} extends Controller
{
    use ApiResponser;

    public function __invoke({$formRequest} \$request, {$useCase} \$useCase)
    {
        try {
            \$result = \$useCase->handle(\$request->validated());
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

use ;use ;use Gilsonreis\LaravelCrudGenerator\Traits\ApiResponser;
{$useCasesNamespace}\\{$useCase};
{$formRequest};

class {$actionName} extends Controller
{
    use ApiResponser;

    public function __invoke({$formRequest} \$request, {$useCase} \$useCase, int \$id)
    {
        try {
            \$result = \$useCase->handle(\$id, \$request->validated());
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

use ;use Gilsonreis\LaravelCrudGenerator\Traits\ApiResponser;use Illuminate\Http\Request;
{$useCasesNamespace}\\{$useCase};

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

    private function ensureApiResponserTraitExists()
    {
        $traitsDirectory = app_path('Traits');
        $traitPath = "{$traitsDirectory}/ApiResponser.php";

        // Verifica e cria o diretório Traits, se necessário
        File::ensureDirectoryExists($traitsDirectory);

        if (!File::exists($traitPath)) {
            $traitContent = "<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\{JsonResponse, Response};

trait ApiResponser
{

    /**
     * Build Success Response
     * @param array|string \$data
     * @param int \$code
     * @param array \$headers
     * @param int \$options
     * @return JsonResponse
     */
    public function successResponse(array|string \$data, int \$code = Response::HTTP_OK, array \$headers = [], int \$options = 0): JsonResponse
    {
        if (is_string(\$data)) {
            return response()->json([
                'status' => 'success',
                'code' => \$code,
                'data' => [
                    'message' => \$data
                ]
            ], \$code, \$headers, \$options);
        }

        if (is_array(\$data) && array_key_exists('data', \$data)) {
            return response()->json([
                'status' => 'success',
                'code' => \$code,
                'data' => [
                    'results' => \$data['data'] ?? [],
                    'links' => [
                        'first' => \$data['first_page_url'],
                        'last' => \$data['last_page_url'],
                        'next' => \$data['next_page_url'],
                        'prev' => \$data['prev_page_url'],
                    ],
                    'meta' => [
                        'current_page' => \$data['current_page'],
                        'last_page' => \$data['last_page'],
                        'from' => \$data['from'],
                        'path' => \$data['path'],
                        'per_page' => \$data['per_page'],
                        'to' => \$data['to'],
                        'total' => \$data['total'],
                        'links' => \$data['links']
                    ]
                ]
            ], \$code, \$headers, \$options);
        }

        return response()->json([
            'status' => 'success',
            'code' => \$code,
            'data' => [
                'results' => \$data ?? [],
            ]
        ], \$code, \$headers, \$options);
    }


    /**
     * Build Error Response
     * @param string \$message
     * @param int \$code
     * @param array \$headers
     * @param int \$options
     * @return JsonResponse
     * @throws \\Exception
     */
    public function errorResponse(string \$message, int \$code = Response::HTTP_INTERNAL_SERVER_ERROR, array \$headers = [], int \$options = 0): JsonResponse
    {
        \$status = match (\$code) {
            400, 401, 403, 409, 422 => 'fail',
            404, 500, 501, 502 => 'error',
            default => 'error'
        };

        return response()->json([
            'status' => \$status,
            'code' => \$code,
            'data' => [
                'message' => \$message
            ]
        ], \$code, \$headers, \$options);
    }
}";

            File::put($traitPath, $traitContent);
            $this->info('Trait ApiResponser criada com sucesso.');
        }
    }
}
