<?php

namespace Gilsonreis\LaravelCrudGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class GenerateCrudRbac extends Command
{
    protected $signature = 'make:crud-rbac';
    protected $description = 'Gera o sistema básico de RBAC (Roles e Permissões)';

    public function handle()
    {
        $this->info('Iniciando a geração do sistema de RBAC...');

        // 1. Gerar Migrations
        $this->generateMigrations();

        // 2. Gerar Models
//        $this->generateModels();
//
//        // 3. Adicionar Relações nos Models
//        $this->addRelationsToModels();
//
//        // 4. Criar Middleware
//        $this->generateMiddleware();
//
//        // 5. Criar Services
//        $this->generateServices();
//
//        // 6. Registrar Binds no AppServiceProvider
//        $this->registerBinds();
//
//        // 7. Gerar Rotas
//        $this->generateRoutes();

        // 8. Finalizar
        $this->info('Sistema de RBAC gerado com sucesso!');
    }

    private function generateMigrations()
    {
        $this->info('Gerando migrations...');
        Artisan::call('make:migration create_roles_table --create=roles');
        Artisan::call('make:migration create_permissions_table --create=permissions');
        Artisan::call('make:migration create_role_permission_table --create=role_permission');
        Artisan::call('make:migration create_user_role_table --create=user_role');

        // Adicionar campos às migrations
        $this->addFieldsToMigration('create_roles_table', [
            ['name' => 'name', 'type' => 'string', 'length' => 100],
            ['name' => 'description', 'type' => 'string', 'length' => 255, 'nullable' => true],
        ]);

        $this->addFieldsToMigration('create_permissions_table', [
            ['name' => 'name', 'type' => 'string', 'length' => 100],
            ['name' => 'description', 'type' => 'string', 'length' => 255, 'nullable' => true],
        ]);

        $this->addFieldsToMigration('create_role_permission_table', [
            ['name' => 'role_id', 'type' => 'unsignedBigInteger'],
            ['name' => 'permission_id', 'type' => 'unsignedBigInteger'],
        ]);

        $this->addFieldsToMigration('create_user_role_table', [
            ['name' => 'user_id', 'type' => 'unsignedBigInteger'],
            ['name' => 'role_id', 'type' => 'unsignedBigInteger'],
        ]);
    }

    private function generateModels()
    {
        $this->info('Gerando models...');
        Artisan::call('make:model Role');
        Artisan::call('make:model Permission');
        $this->info('Models gerados.');
    }

    private function addRelationsToModels()
    {
        $this->info('Adicionando relações nos models...');
        // Adicionar os relacionamentos necessários no Model Role, Permission e User.
        // Implementar a lógica de edição automática aqui.
        $this->info('Relações adicionadas.');
    }

    private function generateMiddleware()
    {
        $this->info('Gerando middleware...');
        Artisan::call('make:middleware CheckPermission');
        Artisan::call('make:middleware CheckRole');
        $this->info('Middleware gerados.');
    }

    private function generateServices()
    {
        $this->info('Gerando serviços...');
        // Criar serviços e interfaces para gerenciar roles e permissões.
        $this->info('Serviços gerados.');
    }

    private function registerBinds()
    {
        $this->info('Registrando binds no AppServiceProvider...');
        // Adicionar os bindings necessários no AppServiceProvider.
        $this->info('Binds registrados.');
    }

    private function generateRoutes()
    {
        $this->info('Gerando rotas...');
        // Criar arquivo de rotas para RBAC.
        $this->info('Rotas geradas.');
    }

    private function addFieldsToMigration(string $migrationName, array $fields)
    {
        $migrationFiles = glob(database_path("migrations/*_{$migrationName}.php"));

        if (empty($migrationFiles)) {
            $this->error("Migration {$migrationName} não encontrada.");
            return;
        }

        $filePath = $migrationFiles[0];
        $content = File::get($filePath);

        // Localiza o método up
        $upPattern = '/public function up\(\): void\s*{\s*Schema::create\(\)->create\(\'[a-z_]+\',\s*function\s*\([^\)]*\)\s*{/';
        if (preg_match($upPattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $position = $matches[0][1] + strlen($matches[0][0]);

            $fieldsCode = '';
            foreach ($fields as $field) {
                $nullable = isset($field['nullable']) && $field['nullable'] ? '->nullable()' : '';
                $length = isset($field['length']) ? "({$field['length']})" : '';
                $fieldsCode .= "\n                \$table->{$field['type']}('{$field['name']}'{$length}){$nullable};";
            }

            $updatedContent = substr_replace($content, $fieldsCode, $position, 0);
            File::put($filePath, $updatedContent);

            $this->info("Campos adicionados à migration {$migrationName}.");
        } else {
            $this->error("Não foi possível localizar o método up na migration {$migrationName}.");
        }
    }
}