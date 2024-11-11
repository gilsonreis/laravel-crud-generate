<?php

namespace App\Console\Commands\CrudGenerate;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GenerateCrudModel extends Command
{
    protected $signature = 'make:crud-model {--table=} {--label=} {--plural-label=} {--observer} {--factory}';
    protected $description = 'Gera o Model com opções de Observer, Factory, relações automáticas, casts de data e json';

    public function handle()
    {
        $tableName = $this->option('table');
        $label = $this->option('label');
        $pluralLabel = $this->option('plural-label');
        $observer = $this->option('observer');
        $factory = $this->option('factory');

        $baseModelPath = app_path('Models/BaseModel.php');

        if (!File::exists($baseModelPath)) {
            $this->createBaseModel($baseModelPath);
        }

        if (!$tableName || !$label || !$pluralLabel) {
            $this->error('Os parâmetros --table, --label e --plural-label são obrigatórios.');
            return;
        }

        $modelName = Str::studly(Str::singular($tableName));
        $this->info("Gerando o Model $modelName para a tabela $tableName...");

        $this->generateModel($modelName, $tableName, $factory);

        if ($observer) {
            $this->generateObserver($modelName);
        }

        $this->info("Model $modelName criado com sucesso!");
    }

    private function generateModel($modelName, $tableName, $factory)
    {
        if (!Schema::hasTable($tableName)) {
            $this->error("A tabela '$tableName' não foi encontrada.");
            return;
        }

        $modelPath = app_path("Models/{$modelName}.php");

        if (file_exists($modelPath)) {
            if (!$this->confirm("O arquivo $modelPath já existe. Deseja sobrescrevê-lo?", false)) {
                $this->info('A criação do Model foi cancelada.');
                return;
            }
        }

        $columns = Schema::getColumnListing($tableName);
        $foreignKeys = $this->getForeignKeys($tableName);
        $hasManyRelations = $this->detectHasManyRelations(Str::snake($modelName));
        $belongsToManyRelations = $this->detectBelongsToManyRelations(Str::snake($modelName));
        $casts = $this->getCasts($tableName);

        $modelContent = $this->generateModelContent($modelName, $columns, $foreignKeys, $hasManyRelations, $belongsToManyRelations, $casts, $factory);

        file_put_contents($modelPath, $modelContent);

        if ($factory) {
            $this->generateFactory($modelName);
        }
    }

    private function getForeignKeys($tableName)
    {
        $columns = Schema::getColumnListing($tableName);
        $foreignKeys = [];

        foreach ($columns as $column) {
            if (Str::endsWith($column, '_id')) {
                $relatedTable = Str::plural(Str::before($column, '_id'));
                $foreignKeys[$column] = $relatedTable;
            }
        }

        return $foreignKeys;
    }

    private function detectHasManyRelations($tableName)
    {
        $hasManyRelations = [];

        // Obter todas as tabelas do banco de dados
        $tables = DB::select('SHOW TABLES');

        foreach ($tables as $table) {
            // Extrair o nome da tabela como o primeiro valor do objeto
            $otherTable = array_values((array)$table)[0];

            // Verifica se a tabela tem uma coluna que aponta para o model principal
            if (Schema::hasColumn($otherTable, "{$tableName}_id")) {
                $hasManyRelations[] = $otherTable;
            }
        }

        return $hasManyRelations;
    }

    private function detectBelongsToManyRelations($tableName)
    {
        $belongsToManyRelations = [];

        // Obter todas as tabelas do banco de dados
        $tables = DB::select('SHOW TABLES');

        foreach ($tables as $table) {
            // Extrair o nome da tabela como o primeiro valor do objeto
            $otherTable = array_values((array)$table)[0];

            // Verifica se a tabela é uma tabela pivot com as colunas de foreign key
            if (Str::contains($otherTable, [$tableName . '_', '_' . $tableName])) {
                $tables = explode('_', $otherTable);
                if (count($tables) == 2 && Schema::hasColumn($otherTable, "{$tables[0]}_id") && Schema::hasColumn($otherTable, "{$tables[1]}_id")) {
                    $belongsToManyRelations[] = $otherTable;
                }
            }
        }

        return $belongsToManyRelations;
    }

    private function getCasts($tableName)
    {
        $columns = Schema::getColumnListing($tableName);
        $casts = [];

        foreach ($columns as $column) {
            $type = Schema::getColumnType($tableName, $column);

            if (in_array($type, ['date', 'datetime', 'timestamp'])) {
                $casts[$column] = 'datetime';
            } elseif ($type === 'json') {
                $casts[$column] = 'array';
            }
        }

        return $casts;
    }

    private function generateObserver($modelName)
    {
        $observerName = "{$modelName}Observer";

        // Cria o observer usando Artisan::call
        Artisan::call('make:observer', [
            'name' => "{$observerName}",
            '--model' => "App\\Models\\{$modelName}"
        ]);

        $this->info("Observer $observerName criado e registrado com sucesso.");
    }

    private function generateFactory($modelName)
    {
        // Caminho da factory
        $factoryPath = database_path("factories/{$modelName}Factory.php");

        // Verifica se o arquivo já existe
        if (file_exists($factoryPath)) {
            $this->warn("A Factory para {$modelName} já existe.");
            return;
        }

        // Obtém os campos e tipos da tabela
        $tableName = Str::snake(Str::pluralStudly($modelName));
        $columns = Schema::getColumnListing($tableName);
        $factoryFields = $this->getFactoryFields($tableName, $columns);

        // Conteúdo da factory com base nos tipos de dados
        $factoryContent = <<<EOD
<?php

namespace Database\Factories;

use App\Models\\{$modelName};
use Illuminate\Database\Eloquent\Factories\Factory;

class {$modelName}Factory extends Factory
{
    protected \$model = {$modelName}::class;

    public function definition()
    {
        return [
$factoryFields
        ];
    }
}
EOD;

        file_put_contents($factoryPath, $factoryContent);
        $this->info("Factory {$modelName}Factory criada com sucesso.");
    }

    private function getFactoryFields($tableName, $columns)
    {
        $fields = '';

        foreach ($columns as $column) {
            if ($column === 'id' || $column === 'created_at' || $column === 'updated_at' || $column === 'deleted_at') {
                continue; // Ignora campos de ID e timestamps
            }

            $type = Schema::getColumnType($tableName, $column);

            $fakerType = match ($type) {
                'string' => "\$this->faker->word(mt_rand(2, 5), true)",
                'text' => "\$this->faker->paragraph",
                'integer', 'int', 'bigint' => "\$this->faker->numberBetween(1, 100)",
                'boolean' => "\$this->faker->boolean",
                'date' => "\$this->faker->date",
                'datetime' => "\$this->faker->dateTime",
                'decimal' => "\$this->faker->randomFloat(2, 0, 1000)",
                'json', 'array' => "\$this->faker->words(mt_rand(2, 5))",
                default => "\$this->faker->words(3, true)",
            };

            $fields .= "            '$column' => $fakerType,\n";
        }

        return $fields;
    }

    private function generateModelContent($modelName, $columns, $foreignKeys, $hasManyRelations, $belongsToManyRelations, $casts, $factory)
    {
        $fillableColumns = array_filter($columns, fn($column) => $column !== 'id');
        $fillableArray = "['" . implode("', '", $fillableColumns) . "']";
        $castsArray = empty($casts) ? '[]' : "[\n        '" . implode("',\n        '", array_map(fn($key, $value) => "$key' => '$value", array_keys($casts), $casts)) . "'\n    ]";

        $modelTemplate = "<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class $modelName extends BaseModel
{
";

        // Inclui HasFactory se o parâmetro --factory foi passado
        if ($factory) {
            $modelTemplate .= "    use HasFactory, SoftDeletes;\n\n";
        } else {
            $modelTemplate .= "    use SoftDeletes;\n\n";
        }

        $modelTemplate .= "    protected \$fillable = $fillableArray;\n\n";
        $modelTemplate .= "    protected \$casts = $castsArray;\n\n";

        // Configurações para slug e relacionamentos
        $modelTemplate .= '
    public static function boot()
    {
        parent::boot();
';

        // Identifica campos que terminam em "_slug" e gera automaticamente
        foreach ($columns as $column) {
            if (Str::endsWith($column, '_slug')) {
                // Extrai o campo de origem a partir do prefixo
                $sourceField = Str::replaceLast('_slug', '', $column);

                // Verifica se o campo de origem existe antes de definir a lógica do slug
                if (in_array($sourceField, $columns)) {
                    $modelTemplate .= "
        static::saving(function (\$model) {
            if (empty(\$model->$column)) {
                \$model->$column = Str::slug(\$model->$sourceField);
            }
        });
";
                }
            }
        }

        $modelTemplate .= "    }\n";

        // Relacionamentos BelongsTo
        foreach ($foreignKeys as $foreignKey => $relatedTable) {
            $relationName = Str::camel(Str::singular($relatedTable));
            $relatedModel = Str::studly(Str::singular($relatedTable));
            $modelTemplate .= "
    public function $relationName()
    {
        return \$this->belongsTo($relatedModel::class, '$foreignKey');
    }
";
        }

        // Relacionamentos HasMany
        foreach ($hasManyRelations as $relatedTable) {
            $relationName = Str::camel(Str::plural($relatedTable));
            $relatedModel = Str::studly(Str::singular($relatedTable));
            $modelTemplate .= "
    public function $relationName()
    {
        return \$this->hasMany($relatedModel::class, '" . Str::snake($modelName) . "_id');
    }
";
        }

        // Relacionamentos BelongsToMany
        foreach ($belongsToManyRelations as $pivotTable) {
            $relatedTable = Str::replaceLast('_' . Str::snake($modelName), '', $pivotTable);
            $relationName = Str::camel(Str::plural($relatedTable));
            $relatedModel = Str::studly(Str::singular($relatedTable));
            $modelTemplate .= "
    public function $relationName()
    {
        return \$this->belongsToMany($relatedModel::class, '$pivotTable');
    }
";
        }

        $modelTemplate .= '
}';

        return $modelTemplate;
    }

    protected function createBaseModel()
    {
        $baseModelPath = app_path('Models/BaseModel.php');

        // Verifica se o BaseModel já existe para evitar sobrescrita
        if (file_exists($baseModelPath)) {
            $this->info("O arquivo BaseModel.php já existe em: $baseModelPath");
            return;
        }

        // Conteúdo do BaseModel
        $baseModelContent = <<<'EOD'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class BaseModel extends Model
{
    /**
     * Aplica filtros à consulta com base nos parâmetros fornecidos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApplyFilters($query, array $filters)
    {
        // Verifica se o nome do modelo está presente como chave
        $modelFilters = $filters[static::class] ?? [];

        foreach ($modelFilters as $field => $value) {
            if (Str::startsWith($field, '_or')) {
                // Filtro OR - espera um array de condições
                $query->where(function ($q) use ($value) {
                    foreach ($value as $orCondition) {
                        foreach ($orCondition as $orField => $orValue) {
                            $this->applyOperator($q, $orField, $orValue, 'orWhere');
                        }
                    }
                });
            } else {
                // Condição padrão para AND e operadores de comparação
                $this->applyOperator($query, $field, $value, 'where');
            }
        }

        return $query;
    }

    /**
     * Aplica o operador apropriado ao campo e valor fornecidos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param mixed $value
     * @param string $method
     */
    protected function applyOperator($query, $field, $value, $method = 'where')
    {
        $operators = [
            '_like'      => ['operator' => 'LIKE', 'value' => '%' . $value . '%'],
            '_gt'        => ['operator' => '>', 'value' => $value],
            '_lt'        => ['operator' => '<', 'value' => $value],
            '_gte'       => ['operator' => '>=', 'value' => $value],
            '_lte'       => ['operator' => '<=', 'value' => $value],
            '_in'        => ['method' => $method . 'In', 'value' => explode(',', $value)],
            '_not_in'    => ['method' => $method . 'NotIn', 'value' => explode(',', $value)],
            '_null'      => ['operator' => '=', 'value' => null],
            '_not_null'  => ['operator' => '!=', 'value' => null],
        ];

        foreach ($operators as $suffix => $operation) {
            if (Str::endsWith($field, $suffix)) {
                $actualField = Str::before($field, $suffix);
                $methodToApply = $operation['method'] ?? $method;
                $operator = $operation['operator'] ?? null;
                $query->$methodToApply($actualField, $operator, $operation['value']);
                return;
            }
        }

        if (Str::endsWith($field, '_between')) {
            $actualField = Str::before($field, '_between');
            $range = explode(',', $value);
            if (count($range) === 2) {
                $query->$method . 'Between'($actualField, [$range[0], $range[1]]);
            }
            return;
        }

        $query->$method($field, '=', $value);
    }
}
EOD;

        // Cria o arquivo BaseModel.php com o conteúdo definido
        file_put_contents($baseModelPath, $baseModelContent);
        $this->info("BaseModel.php criado com sucesso em: $baseModelPath");
    }
}
