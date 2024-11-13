<?php

namespace Gilsonreis\LaravelCrudGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class GenerateCrudMenuChoices extends Command
{
    protected $signature = 'crud:menu';
    protected $description = 'Menu interativo para geração de CRUDs';

    public function handle()
    {
        while (true) {
            $choice = $this->choice(
                "\n======================= GERADOR DE CRUDS - LARAVEL =======================\n" .
                'Selecione uma opção abaixo:',
                [
                    'Gerar CRUD completo',
                    'Gerar Action',
                    'Gerar UseCase',
                    'Gerar Repository',
                    'Gerar Model',
                    'Gerar Rotas',
                    'Sobre',
                    'Sair',
                ]
            );

            switch ($choice) {
                case 'Gerar CRUD completo':
                    $this->generateFullCrud();
                    break;
                case 'Gerar Action':
                    $this->generateAction();
                    break;
                case 'Gerar UseCase':
                    $this->generateUseCase();
                    break;
                case 'Gerar Repository':
                    $this->generateRepository();
                    break;
                case 'Gerar Model':
                    $this->generateModel();
                    break;
                case 'Gerar Rotas':
                    $this->generateRoutes();
                    break;
                case 'Sobre':
                    $this->displayAbout();
                    break;
                case 'Sair':
                    $this->info('Saindo...');
                    return;
            }

            $this->waitForKeyPress();
            $this->clearScreen();
        }
    }

    private function generateModel()
    {
        $tableName = $this->ask('Informe o nome da tabela:');
        $label = $this->ask('Informe o label (singular) do Model:');
        $pluralLabel = $this->ask('Informe o label (plural) do Model:');
        $addObserver = $this->confirm('Deseja adicionar um Observer para o Model?', false);

        $commandOptions = [
            '--table' => $tableName,
            '--label' => $label,
            '--plural-label' => $pluralLabel,
        ];

        if ($addObserver) {
            $commandOptions['--observer'] = true;
        }

        Artisan::call('make:crud-model', $commandOptions);

        $this->info("Model {$label} gerado com sucesso!");
    }

    private function generateRepository()
    {
        $repositoryName = $this->ask('Informe o nome do Repository:');
        $model = $this->ask('Informe o nome do Model (opcional) para incluir operações CRUD:');

        $commandOptions = [
            'repositoryName' => $repositoryName,
        ];

        if (!empty($model)) {
            $commandOptions['--model'] = $model;
        }

        Artisan::call('make:crud-repository', $commandOptions);

        $this->info("Repository {$repositoryName} gerado com sucesso!");
    }

    private function generateUseCase()
    {
        $useCaseType = $this->choice('Deseja gerar um UseCase para um model específico (CRUD) ou um UseCase em branco?', [
            'CRUD para Model',
            'UseCase em Branco'
        ]);

        if ($useCaseType === 'CRUD para Model') {
            $model = $this->ask('Informe o nome do Model para o CRUD:');
            Artisan::call('make:crud-use-case', ['--model' => $model]);
            $this->info("UseCases para CRUD do model {$model} gerados com sucesso!");

        } else {
            $name = $this->ask('Informe o nome do UseCase em branco:');
            $directory = $this->ask('Informe o diretório para o UseCase em branco:');
            Artisan::call('make:crud-use-case', [
                '--name' => $name,
                '--directory' => $directory,
            ]);
            $this->info("UseCase em branco {$name} criado no diretório {$directory} com sucesso!");
        }
    }

    private function generateAction()
    {
        $actionType = $this->choice('Deseja gerar Actions para um CRUD de model específico ou uma Action em branco?', [
            'CRUD para Model',
            'Action em Branco'
        ]);

        if ($actionType === 'CRUD para Model') {
            $model = $this->ask('Informe o nome do Model para o CRUD:');
            Artisan::call('make:crud-actions', ['--model' => $model]);
            $this->info("Actions para CRUD do model {$model} geradas com sucesso!");

        } else {
            $name = $this->ask('Informe o nome da Action em branco:');
            $directory = $this->ask('Informe o diretório para a Action em branco:');
            Artisan::call('make:crud-actions', [
                '--name' => $name,
                '--directory' => $directory,
            ]);
            $this->info("Action em branco {$name} criada no diretório {$directory} com sucesso!");
        }
    }

    private function generateRoutes()
    {
        $routeType = $this->choice('Deseja gerar rotas para um CRUD de model específico ou um arquivo de rotas em branco?', [
            'CRUD para Model',
            'Arquivo de Rotas em Branco',
        ]);

        if ($routeType === 'CRUD para Model') {
            $model = $this->ask('Informe o nome do Model para gerar as rotas CRUD:');
            Artisan::call('make:crud-routes', ['--model' => $model]);
            $this->info("Rotas CRUD para o Model {$model} geradas com sucesso!");
        } else {
            $name = $this->ask('Informe o nome do arquivo de rota em branco:');
            Artisan::call('make:crud-routes', ['--name' => $name]);
            $this->info("Arquivo de rotas em branco {$name} gerado com sucesso!");
        }
    }

    private function generateFullCrud()
    {
        $model = $this->ask('Informe o nome do Model para o CRUD completo:');
        $tableName = $this->ask('Informe o nome da tabela:');
        $label = $this->ask('Informe o label (singular) do Model:');
        $pluralLabel = $this->ask('Informe o label (plural) do Model:');
        $addObserver = $this->confirm('Deseja adicionar um Observer para o Model?', false);

        // Gerar Model
        $this->info('Gerando Model...');
        Artisan::call('make:crud-model', [
            '--table' => $tableName,
            '--label' => $label,
            '--plural-label' => $pluralLabel,
            '--observer' => $addObserver,
        ]);

        // Gerar Repository
        $this->info('Gerando Repository...');
        Artisan::call('make:crud-repository', [
            'repositoryName' => "{$model}Repository",
            '--model' => $model,
        ]);

        // Gerar UseCases
        $this->info('Gerando UseCases...');
        Artisan::call('make:crud-use-case', ['--model' => $model]);

        $this->info('Gerando Rotas...');
        Artisan::call('make:crud-routes', ['--model' => $model]);

        // Gerar Actions
        $this->info('Gerando Actions...');
        Artisan::call('make:crud-actions', ['--model' => $model]);

        $this->info("CRUD completo para o model {$model} gerado com sucesso!");
    }

    private function displayAbout()
    {
        $this->info("\n============================ SOBRE O GERADOR ============================");
        $this->info('Este é um gerador de CRUDs para Laravel, criado para automatizar a geração de ');
        $this->info('actions, use cases, repositories e models, e facilitar a criação de APIs RESTful.');
        $this->info("==========================================================================\n");
    }

    private function clearScreen()
    {
        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            system('cls');
        } else {
            system('clear');
        }
    }
    private function waitForKeyPress()
    {
        $this->info("\nPressione qualquer tecla para continuar...");
        readline();
    }
}
