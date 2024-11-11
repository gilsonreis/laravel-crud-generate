<?php

namespace App\Console\Commands\CrudGenerate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GenerateFormRequest extends Command
{
    protected $signature = 'make:crud-form-request
                            {--model= : Nome do Model para validação automática}
                            {--name= : Nome do FormRequest em branco}';
    protected $description = 'Gera um FormRequest com validações automáticas ou em branco';

    public function handle()
    {
        $modelName = $this->option('model');
        $name = $this->option('name');

        // Verifica e cria o BaseRequest se não existir
        $this->ensureBaseRequestExists();

        if ($modelName) {
            $modelName = Str::studly($modelName);

            if (!class_exists("App\\Models\\{$modelName}")) {
                $this->error("O Model {$modelName} não foi encontrado.");
                return;
            }

            $this->generateModelFormRequest($modelName);
        } elseif ($name) {
            $this->generateBlankFormRequest($name);
        } else {
            $this->error('É necessário fornecer --model ou --name.');
        }
    }

    private function ensureBaseRequestExists()
    {
        $directoryPath = app_path('Http/Requests');
        File::ensureDirectoryExists($directoryPath);

        $baseRequestPath = "{$directoryPath}/BaseRequest.php";

        if (!File::exists($baseRequestPath)) {
            $baseRequestContent = "<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

abstract class BaseRequest extends FormRequest
{
    protected \$stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator \$validator)
    {
        \$response = new Response([
            'status' => 'fail',
            'code' => 400,
            'data' => [
                'message' => \$validator->errors()->first()
            ]
        ], 422);
        throw new ValidationException(\$validator, \$response);
    }

    abstract public function rules(): array;
}";
            File::put($baseRequestPath, $baseRequestContent);
            $this->info('BaseRequest criado com sucesso.');
        }
    }

    private function generateModelFormRequest($modelName)
    {
        $requestName = "{$modelName}Request";
        $requestPath = app_path("Http/Requests/{$requestName}.php");

        $directoryPath = app_path('Http/Requests');
        File::ensureDirectoryExists($directoryPath);

        $tableName = Str::snake(Str::plural($modelName));
        $columns = Schema::getColumnListing($tableName);
        $rules = [];
        $attributesContent = '';

        foreach ($columns as $column) {
            // Ignora o campo de chave primária, assumindo que é 'id'
            if ($column === 'id') {
                continue;
            }

            $type = Schema::getColumnType($tableName, $column);
            $rule = ['sometimes', 'required'];

            // Define regras de validação com base no tipo de dado
            if ($type === 'string') {
                $rule[] = 'string';
                $rule[] = 'max:255';
            } elseif ($type === 'integer' || $type === 'bigint') {
                $rule[] = 'integer';
            } elseif ($type === 'float' || $type === 'decimal') {
                $rule[] = 'numeric';
            } elseif ($type === 'boolean') {
                $rule[] = 'boolean';
            } elseif ($type === 'date' || $type === 'datetime') {
                $rule[] = 'date';
            }

            $rules[$column] = implode('|', $rule);
            $attributesContent .= "            '$column' => '$column',\n";
        }

        $rulesContent = '';
        foreach ($rules as $field => $rule) {
            $rulesContent .= "            '$field' => '$rule',\n";
        }

        $requestContent = "<?php

namespace App\Http\Requests;

class {$requestName} extends BaseRequest
{
    public function rules(): array
    {
        return [
$rulesContent
        ];
    }

    public function attributes(): array
    {
        return [
$attributesContent
        ];
    }
}";

        File::put($requestPath, $requestContent);
        $this->info("FormRequest {$requestName} criado com sucesso.");
    }

    private function generateBlankFormRequest($name)
    {
        $requestName = Str::studly($name);
        $requestPath = app_path("Http/Requests/{$requestName}.php");

        $directoryPath = app_path('Http/Requests');
        File::ensureDirectoryExists($directoryPath);

        $requestContent = "<?php

namespace App\Http\Requests;

class {$requestName} extends BaseRequest
{
    public function rules(): array
    {
        return [
            // Adicione suas regras de validação aqui
        ];
    }

    public function attributes(): array
    {
        return [
            // Defina os nomes dos campos aqui
        ];
    }
}";

        File::put($requestPath, $requestContent);
        $this->info("FormRequest em branco {$requestName} criado com sucesso.");
    }
}
