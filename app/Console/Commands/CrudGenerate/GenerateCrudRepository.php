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

        $this->generateFilter();
        $this->generatePagination();

        if ($modelName) {
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
use App\\Support\\Pagination;
use App\\Support\\Filter;

interface {$repositoryName}Interface
{
    public function getAll(Pagination \$pagination, Filter \$filter): LengthAwarePaginator|array;

    public function find(int \$id);

    public function create(array \$data);

    public function update(int \$id, array \$data): bool;

    public function delete(int \$id): bool;
}";

        File::put($interfacePath, $interfaceContent);
    }

    private function generateRepositoryWithCrud($repositoryName, $modelName, $repositoryPath, $filters)
    {
        // Ignorar a criação do filtro específico do modelo
        $repositoryContent = "<?php

namespace App\Repositories\\" . Str::studly($modelName) . ";

use App\Models\\$modelName;
use Illuminate\\Pagination\\LengthAwarePaginator;
use App\\Support\\Pagination;
use App\\Support\\Filter;

class {$repositoryName} implements {$repositoryName}Interface
{
    public function getAll(Pagination \$pagination, Filter \$filter): LengthAwarePaginator|array
    {
        \$query = {$modelName}::query();

        \$query->applyFilters(\$filter->getFilters());

        \$query->orderBy(\$filter->getOrderColumn(), \$filter->getOrderDirection());

        if (\$pagination->hasPaginate()) {
            return \$query->paginate(
                perPage: \$pagination->getPerPage(),
                columns: \$filter->getColumns(),
                page: \$pagination->getPage()
            )->toArray();
        }

        return \$query->get(\$filter->getColumns())->toArray();
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

            $interfaceImport = "use App\\Repositories\\{$directory}\\{$repositoryName}Interface;";
            $repositoryImport = "use App\\Repositories\\{$directory}\\{$repositoryName};";

            $interfacePattern = "/^use\s+App\\\\Repositories\\\\{$directory}\\\\{$repositoryName}Interface;/m";
            $repositoryPattern = "/^use\s+App\\\\Repositories\\\\{$directory}\\\\{$repositoryName};/m";

            if (!preg_match($interfacePattern, $providerContent) || !preg_match($repositoryPattern, $providerContent)) {
                $providerContent = preg_replace(
                    '/^namespace\s+[^\n]+;\n/m',
                    "$0\n" . (!preg_match($interfacePattern, $providerContent) ? "$interfaceImport\n" : '') . (!preg_match($repositoryPattern, $providerContent) ? "$repositoryImport" : ''),
                    $providerContent,
                    1
                );
            }

            $bindStatement = "\$this->app->bind({$repositoryName}Interface::class, {$repositoryName}::class);";

            $pattern = "/bind\(\s*{$repositoryName}Interface::class\s*,\s*{$repositoryName}::class\s*\)/";

            if (!preg_match($pattern, $providerContent)) {
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

    private function generateFilter()
    {
        $directoryPath = app_path('Support');
        File::ensureDirectoryExists($directoryPath);

        $filterPath = "{$directoryPath}/Filter.php";

        if (File::exists($filterPath)) {
            $this->info('O arquivo Filter.php já existe.');
            return;
        }

        $filterContent = "<?php

namespace App\Support;

class Filter
{
    public function __construct(
        private ?array \$columns = ['*'],
        private ?string \$orderColumn = 'created_at',
        private ?string \$orderDirection = 'asc',
        private array \$filters = []
    ) {}

    public function getColumns(): ?array
    {
        return \$this->columns;
    }

    public function setColumns(array \$columns): self
    {
        \$this->columns = \$columns;
        return \$this;
    }

    public function getOrderColumn(): ?string
    {
        return \$this->orderColumn;
    }

    public function setOrderColumn(string \$orderColumn): self
    {
        \$this->orderColumn = \$orderColumn;
        return \$this;
    }

    public function getOrderDirection(): ?string
    {
        return \$this->orderDirection;
    }

    public function setOrderDirection(string \$orderDirection): self
    {
        if (!in_array(\$orderDirection, ['asc', 'desc'])) {
            throw new \\DomainException('OrderDirection precisa ser \"asc\" ou \"desc\"');
        }
        \$this->orderDirection = \$orderDirection;
        return \$this;
    }

    public function getFilters(): array
    {
        return \$this->filters;
    }

    public function setFilters(array \$filters): self
    {
        \$this->filters = \$filters;
        return \$this;
    }
}";

        File::put($filterPath, $filterContent);
        $this->info('Filter.php criado com sucesso.');
    }

    private function generatePagination()
    {
        $directoryPath = app_path('Support');
        File::ensureDirectoryExists($directoryPath);

        $paginationPath = "{$directoryPath}/Pagination.php";

        if (File::exists($paginationPath)) {
            $this->info('O arquivo Pagination.php já existe.');
            return;
        }

        $paginationContent = "<?php

namespace App\Support;

class Pagination
{
    public function __construct(private int \$page = 1, private int \$perPage = 15, private ?bool \$paginate = true)
    {
    }

    public function getPage(): int
    {
        return \$this->page;
    }

    public function setPage(int \$page): Pagination
    {
        \$this->page = \$page;
        return \$this;
    }

    public function getPerPage(): int
    {
        return \$this->perPage;
    }

    public function setPerPage(int \$perPage): Pagination
    {
        \$this->perPage = \$perPage;
        return \$this;
    }

    public function setPaginate(bool \$setPaginate): Pagination
    {
        \$this->paginate = \$setPaginate;
        return \$this;
    }

    public function hasPaginate(): bool
    {
        return \$this->paginate;
    }

    public function toArray(): array
    {
        return [
            'page' => \$this->getPage(),
            'per_page' => \$this->getPerPage(),
            'paginate' => \$this->hasPaginate(),
        ];
    }
}";

        File::put($paginationPath, $paginationContent);
        $this->info('Pagination.php criado com sucesso.');
    }

}
