<?php

namespace App\Console\Commands\CrudGenerate;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GenerateCrudRepository extends Command
{
    protected $signature = 'make:crud-repository
    {repositoryName}
    {--model= : Nome do Model opcional}
    {--filters= : Campos para filtros com orWhere e LIKE}';
    protected $description = 'Gera um Repository com interface e métodos básicos de CRUD, incluindo paginação para listagem';

    public function handle()
    {
        $repositoryName = $this->argument('repositoryName');
        $modelName = $this->option('model');
        $filters = $this->option('filters') ?? [];


        if (!empty($filters)) {
            $filters = explode(',', $filters);
        }

        $directory = $modelName ? Str::studly($modelName) : Str::studly($repositoryName);

        $interfacePath = app_path("Repositories/{$directory}/{$repositoryName}Interface.php");
        $repositoryPath = app_path("Repositories/{$directory}/{$repositoryName}.php");

        if (!File::exists(app_path("Repositories/{$directory}"))) {
            File::makeDirectory(app_path("Repositories/{$directory}"), 0755, true);
        }

        if ($modelName) {
            $this->generateFilter($modelName, $filters);
            $this->generateInterfaceWithCrud($repositoryName, $modelName, $interfacePath);
            $this->generateRepositoryWithCrud($repositoryName, $modelName, $repositoryPath, $filters);
        } else {
            $this->generateEmptyInterface($repositoryName, $interfacePath);
            $this->generateEmptyRepository($repositoryName, $repositoryPath);
        }

        $this->updateAppServiceProvider($repositoryName, $directory);

        $this->info("Repository $repositoryName criado com sucesso!");
    }

    private function generateEmptyInterface($repositoryName, $interfacePath)
    {
        $interfaceContent = "<?php

namespace App\Repositories\\" . Str::studly($repositoryName) . ";

interface {$repositoryName}Interface
{

}";

        File::put($interfacePath, $interfaceContent);
    }

    private function generateInterfaceWithCrud($repositoryName, $modelName, $interfacePath)
    {
        $interfaceContent = "<?php

namespace App\Repositories\\" . Str::studly($modelName) . ";

use Illuminate\\Pagination\\LengthAwarePaginator;
use App\\Types\\Pagination;
use App\\Filters\\" . Str::studly($modelName) . "Filter;

interface {$repositoryName}Interface
{
    public function getAll(Pagination \$pagination, {$modelName}Filter \$filter): LengthAwarePaginator|array;

    public function find(int \$id);

    public function create(array \$data);

    public function update(int \$id, array \$data): bool;

    public function delete(int \$id): bool;
}";

        File::put($interfacePath, $interfaceContent);
    }

    private function generateFilter($modelName, $filters = null)
    {
        $filterName = "{$modelName}Filter";
        $filterPath = app_path("Filters/{$filterName}.php");

        if (!File::exists(app_path('Filters'))) {
            File::makeDirectory(app_path('Filters'), 0755, true);
        }

        $baseFilterPath = app_path('Filters/BaseFilter.php');
        if (!File::exists($baseFilterPath)) {
            $this->generateBaseFilter();
        }

        $filters = $this->getFilters($modelName, $filters);

        $constructorParams = '';
        $gettersSetters = '';

        foreach ($filters as $filter) {
            $studlyFilter = Str::studly($filter);

            $constructorParams .= "        private ?string \$$filter,\n";

            // Define getter e setter
            $gettersSetters .= "
    public function get{$studlyFilter}(): ?string
    {
        return \$this->$filter;
    }

    public function set{$studlyFilter}(?string \$$filter): self
    {
        \$this->$filter = \$$filter;
        return \$this;
    }\n";
        }

        // Define a estrutura da classe de filtro estendendo BaseFilter
        $filterContent = "<?php

namespace App\Filters;

class {$filterName} extends BaseFilter
{
    public function __construct(
$constructorParams
    ) {
        parent::__construct();

    }

$gettersSetters

    public function toArray(): array
    {
        return [
            ...parent::toArray(),
" . implode('', array_map(fn($filter) => "            '$filter' => \$this->get" . Str::studly($filter) . "(),\n", $filters)) . '        ];
    }
}';

        File::put($filterPath, $filterContent);
    }

    private function generateRepositoryWithCrud($repositoryName, $modelName, $repositoryPath, $filters)
    {
        $filters = $this->getFilters($modelName, $filters);

        $filterConditions = '';
        foreach ($filters as $filter) {
            $studlyFilter = Str::studly($filter);
            $filterConditions .= "
        if (\$filter?->get{$studlyFilter}()) {
            \$query->orWhere('$filter', 'like', '%' . \$filter->get{$studlyFilter}() . '%');
        }";
        }

        $repositoryContent = "<?php

namespace App\Repositories\\" . Str::studly($modelName) . ";

use App\Models\\$modelName;
use Illuminate\\Pagination\\LengthAwarePaginator;
use App\\Types\\Pagination;
use App\\Filters\\{$modelName}Filter;

class {$repositoryName} implements {$repositoryName}Interface
{
    public function getAll(Pagination \$pagination, {$modelName}Filter \$filter): LengthAwarePaginator|array
    {
        \$query = {$modelName}::query();

        $filterConditions

        \$query->orderBy(\$filter->getOrderColumn(), \$filter->getOrderDirection());

        if (\$pagination->hasPaginate()) {
            return \$query->paginate(
                perPage: \$pagination->getPerPage(),
                columns: \$filter->getColumns(),
                page: \$pagination->getPage()
            );
        }

        return \$query->get(\$filter->getColumns())?->toArray() ?? [];
    }

    public function find(int \$id): ?$modelName
    {
        return {$modelName}::query()->find(\$id)?->first();
    }

    public function create(array \$data): $modelName
    {
        return {$modelName}::query()->create(\$data);
    }

    public function update(int \$id, array \$data): bool
    {
        return {$modelName}::query()->find(\$id)?->update(\$data) ?? false;
    }

    public function delete(int \$id): bool
    {
        return {$modelName}::query()->find(\$id)?->delete() ?? false;
    }
}";

        File::put($repositoryPath, $repositoryContent);
    }

    private function generateEmptyRepository($repositoryName, $repositoryPath)
    {
        $repositoryContent = "<?php

namespace App\Repositories\\" . Str::studly($repositoryName) . ";

class {$repositoryName} implements {$repositoryName}Interface
{
    //
}";

        File::put($repositoryPath, $repositoryContent);
    }

    private function updateAppServiceProvider($repositoryName, $directory)
    {
        $serviceProviderPath = app_path('Providers/AppServiceProvider.php');

        if (File::exists($serviceProviderPath)) {
            $providerContent = File::get($serviceProviderPath);

            // Define as linhas de importação que queremos adicionar
            $interfaceImport = "use App\\Repositories\\{$directory}\\{$repositoryName}Interface;";
            $repositoryImport = "use App\\Repositories\\{$directory}\\{$repositoryName};";

            // Expressão regular para verificar a existência dos imports
            $interfacePattern = "/^use\s+App\\\\Repositories\\\\{$directory}\\\\{$repositoryName}Interface;/m";
            $repositoryPattern = "/^use\s+App\\\\Repositories\\\\{$directory}\\\\{$repositoryName};/m";

            // Adiciona as linhas de importação logo após o namespace, caso não existam
            if (!preg_match($interfacePattern, $providerContent) || !preg_match($repositoryPattern, $providerContent)) {
                $providerContent = preg_replace(
                    '/^namespace\s+[^\n]+;\n/m',
                    "$0\n" . (!preg_match($interfacePattern, $providerContent) ? "$interfaceImport\n" : '') . (!preg_match($repositoryPattern, $providerContent) ? "$repositoryImport" : ''),
                    $providerContent,
                    1
                );
            }

            // Define o bind statement sem o namespace completo
            $bindStatement = "\$this->app->bind({$repositoryName}Interface::class, {$repositoryName}::class);";

            // Expressão regular para verificar o bind
            $pattern = "/bind\(\s*{$repositoryName}Interface::class\s*,\s*{$repositoryName}::class\s*\)/";

            if (!preg_match($pattern, $providerContent)) {
                // Insere o bind no método register
                $providerContent = str_replace(
                    "public function register(): void\n    {\n",
                    "public function register(): void\n    {\n        $bindStatement \n",
                    $providerContent
                );

                File::put($serviceProviderPath, $providerContent);
                $this->info('Bind e importações adicionados ao AppServiceProvider.');
            } else {
                $this->info('Bind já existe no AppServiceProvider.');
            }
        }
    }

    /**
     * @param $modelName
     * @param $filters
     * @return array|string[]
     */
    private function getFilters($modelName, $filters): array
    {
        $tableName = Str::plural(Str::snake($modelName));

        if (empty($filters)) {
            $filters = array_filter(Schema::getColumnListing(Str::snake($tableName)), function ($column) use ($tableName) {
                $columnType = Schema::getColumnType(Str::snake($tableName), $column);
                return in_array($columnType, ['char', 'varchar', 'text', 'mediumtext', 'longtext']);
            });
        }

        return $filters;
    }

    private function generateBaseFilter()
    {
        $baseFilterContent = "<?php

namespace App\Filters;

abstract class BaseFilter
{
    public function __construct(
        private ?array \$columns = ['*'],
        private ?string \$orderColumn = 'created_at',
        private ?string \$orderDirection = 'asc',
    ) {
    }

    public function getColumns(): ?array
    {
        return \$this->columns;
    }

    public function setColumns(?array \$columns): BaseFilter
    {
        \$this->columns = \$columns;
        return \$this;
    }

    public function getOrderColumn(): ?string
    {
        return \$this->orderColumn;
    }

    public function setOrderColumn(?string \$orderColumn): BaseFilter
    {
        \$this->orderColumn = \$orderColumn;
        return \$this;
    }

    public function getOrderDirection(): ?string
    {
        return \$this->orderDirection;
    }

    public function setOrderDirection(?string \$orderDirection): BaseFilter
    {
        if (!in_array(\$orderDirection, ['asc', 'desc'])) {
            throw new \DomainException('OrderDirection precisa ser \"asc\" ou \"desc\"', 422);
        }

        \$this->orderDirection = \$orderDirection;
        return \$this;
    }

    public function toArray(): array
    {
        return [
            'columns' => \$this->getColumns(),
            'orderColumn' => \$this->getOrderColumn(),
            'orderDirection' => \$this->getOrderDirection()
        ];
    }
}";

        File::put(app_path('Filters/BaseFilter.php'), $baseFilterContent);
        $this->info('BaseFilter criado com sucesso.');
    }

}
