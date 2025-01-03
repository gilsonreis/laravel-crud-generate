<?php

namespace Gilsonreis\LaravelCrudGenerator\Commands;

use Gilsonreis\LaravelCrudGenerator\Support\Helpers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateCrudJWTLogin extends Command
{
    protected $signature = 'make:crud-auth-jwt';
    protected $description = 'Gera componentes de autenticação como Action, UseCase, Repository e FormRequest em JWT';

    public function handle()
    {

        if (!Helpers::isFirebaseJwtInstalled()) {
            $this->error('Laravel firebase/php-jwt não está instalado.');
            $this->info('Esse sistema de login utiliza o JWT para autenticar as rotas, então é necessário tê-lo instalado.');
            $this->line(str_repeat('-', 50));
            $this->warn('* Para instalar o JWT, copie e execute os comandos abaixo:');
            $this->line('    > composer require firebase/php-jwt');
            $this->line('    > php artisan install:api');
            $this->line(str_repeat('-', 50));
            $this->line('Estes comandos irá instalar o JWT Firebase e fazer todas as configurações necessárias.');
            $this->line('Após instalar o JWT Firebase, execute novamente o comando para gerar o login.');
            return;
        }

        if (!Helpers::isJwtConfigured()) {
            $this->error("Configurações do JWT incompletas");
            $this->line(str_repeat('-', 50));
            $this->warn('* Para configurar o JWT voce precisa:');
            $this->info('    > criar uma chave no seu env RSA e uma PUBLIC com a chave privada e publica de assinatura do JWT (veja Online RSA Key Generator)');
            $this->info('    > chave JWT_EXPIRE, para definir o tempo de expiração do token em segundos, padrão é 86400 (1 dia)');
            $this->line(str_repeat('-', 50));
            $this->line('Após configurar o JWT, execute novamente o comando para gerar o login.');
            return;
        };

        $this->addHasApiTokensTrait();

        $this->generateFormRequest();
        $this->generateAction();
        $this->generateUseCase();
        $this->generateRepository();
        $this->generateSanctumAuthService();
        $this->generateAuthRepository();
        $this->createAuthRoutes();
        $this->ensureAuthRouteRequire();
        $this->registerBinds();

        $this->info('Componentes de autenticação gerados com sucesso!');
    }

    private function addHasApiTokensTrait()
    {
        $modelPath = app_path('Models/User.php');
        if (!File::exists($modelPath)) {
            $this->erro('O arquivo User.php não foi encontrado em app/Models.');
            return;
        }

        $content = File::get($modelPath);

        // Class name to match and replace
        $newClassName = 'Authenticatable';
        $requiredUses = [
            'Illuminate\Foundation\Auth\User as Authenticatable',
            'Gilsonreis\LaravelCrudGenerator\Traits\FilteredModel',
            'Illuminate\Database\Eloquent\Factories\HasFactory'
        ];
        if (preg_match('/namespace\s+[\w\\\]+;/', $content, $matches)) {
            // Namespace declaration found
            $namespaceDeclaration = $matches[0];
            foreach ($requiredUses as $useStatement) {
                $useLine = "use $useStatement;";
                if (strpos($content, $useLine) === false) {
                    // Add the `use` statement below the namespace if it doesn't exist
                    $content = preg_replace(
                        '/' . preg_quote($namespaceDeclaration, '/') . '/',
                        $namespaceDeclaration . "\n" . $useLine,
                        $content,
                        1
                    );
                }
            }
        }

// Replace the class definition
        $content = preg_replace(
            '/class\s+\w+\s+extends\s+\w+\s*{/', // Match `class ClassName extends Something {`
            "class User extends $newClassName {", // Replace with the new class definition
            $content
        );

        File::put($modelPath, $content);

        // Verifica se o `HasApiTokens` já está importado
        if (!str_contains($content, 'use Laravel\\Sanctum\\HasApiTokens;')) {
            // Insere a importação logo após o namespace
            $content = preg_replace(
                '/^namespace\s+App\\\Models;\n/m',
                "namespace App\\Models;\nuse Laravel\\Sanctum\\HasApiTokens;\n",
                $content
            );
        }

        // Verifica se a trait HasApiTokens já está presente dentro da classe
        if (preg_match('/class\s+\w+\s+extends\s+\w+\s*{[^}]*\buse\s+HasApiTokens\b/s', $content)) {
            return;
        }

        // Localiza o início do bloco da classe
        if (preg_match('/class\s+\w+\s+extends\s+\w+\s*{/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $classBodyStart = $matches[0][1] + strlen($matches[0][0]);
            // Localiza o último "use" dentro do escopo da classe
            if (preg_match('/\buse\s+[\w\\\]+;.*$/m', $content, $useMatches, PREG_OFFSET_CAPTURE, $classBodyStart)) {
                // Adiciona `HasApiTokens` após o último "use"
                $position = $useMatches[0][1] + strlen($useMatches[0][0]);
                $updatedContent = substr_replace($content,  "\n\t" . 'use HasApiTokens;' . "\n\t" . 'use HasFactory;' . "\n\t" . 'use FilteredModel;', $position, 0);
            } else {
                // Caso não exista nenhum "use", adiciona logo após a abertura da classe
                $updatedContent = substr_replace($content,  "\n\t" . 'use HasApiTokens;' . "\n\t" . 'use HasFactory;' . "\n\t" .  'use FilteredModel;', $classBodyStart, 0);
            }

            // Salva o conteúdo atualizado no arquivo
            File::put($modelPath, $updatedContent);
        } else {
            $this->error('Não foi possível localizar a classe User no arquivo User.php.');
        }
    }

    private function generateFormRequest()
    {
        $formRequestPath = app_path('Http/Requests/Auth/LoginRequest.php');

        if (!File::exists(app_path('Http/Requests/Auth'))) {
            File::ensureDirectoryExists(app_path('Http/Requests/Auth'));
        }

        $formRequestContent = "<?php

namespace App\Http\Requests\Auth;

use Gilsonreis\LaravelCrudGenerator\Requests\BaseRequest;

class LoginRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'e-mail',
            'password' => 'senha',
        ];
    }
}";

        File::put($formRequestPath, $formRequestContent);
    }

    private function generateAction()
    {
        $actionPath = app_path('Http/Actions/Auth/LoginAction.php');

        if (!File::exists(app_path('Http/Actions/Auth'))) {
            File::ensureDirectoryExists(app_path('Http/Actions/Auth'));
        }

        $actionContent = "<?php

namespace App\Http\Actions\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Gilsonreis\LaravelCrudGenerator\Traits\ApiResponser;
use App\UseCases\Auth\LoginUseCase;

class LoginAction extends Controller
{
    use ApiResponser;

    public function __construct(
        private readonly LoginUseCase \$useCase
    ) {}

    public function __invoke(LoginRequest \$request)
    {
        try {
            \$result = \$this->useCase->handle(\$request->validated());
            return \$this->successResponse(\$result);
        } catch (\Exception \$e) {
            return \$this->errorResponse(\$e->getMessage());
        }
    }
}";

        File::put($actionPath, $actionContent);

    }

    private function generateUseCase()
    {
        $useCasePath = app_path('UseCases/Auth/LoginUseCase.php');

        if (!File::exists(app_path('UseCases/Auth'))) {
            File::ensureDirectoryExists(app_path('UseCases/Auth'));
        }

        $useCaseContent = "<?php

namespace App\UseCases\Auth;

use App\Repositories\Auth\LoginRepositoryInterface;

class LoginUseCase
{
    public function __construct(
        private readonly LoginRepositoryInterface \$repository
    ) {}

    public function handle(array \$data)
    {
        return \$this->repository->authenticate(\$data);
    }
}";

        File::put($useCasePath, $useCaseContent);
    }

    private function generateRepository()
    {
        $repositoryInterfacePath = app_path('Repositories/Auth/LoginRepositoryInterface.php');
        $repositoryPath = app_path('Repositories/Auth/LoginRepository.php');

        if (!File::exists(app_path('Repositories/Auth'))) {
            File::ensureDirectoryExists(app_path('Repositories/Auth'));
        }

        $repositoryInterfaceContent = "<?php

namespace App\Repositories\Auth;

interface LoginRepositoryInterface
{
    public function authenticate(array \$data);
}";

        $repositoryContent = "<?php

namespace App\Repositories\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use \Gilsonreis\LaravelCrudGenerator\Traits\JwtHelper;
class LoginRepository implements LoginRepositoryInterface
{
    use JwtHelper;
    public function authenticate(array \$data)
    {
        if (!Auth::attempt(['email' => \$data['email'], 'password' => \$data['password']])) {
            throw new \Exception('Credenciais inválidas.');
        }

        \$user = Auth::user();
         \$returnData = ['user' => \$user->toArray()];
         return \$this->generateJwt(\$returnData, env('JWT_EXPIRE',86400));

    }
}";

        File::put($repositoryInterfacePath, $repositoryInterfaceContent);
        File::put($repositoryPath, $repositoryContent);
    }

    private function isFirebaseJwtInstalled(): bool
    {
        return File::exists(base_path('vendor/firebase/php-jwt'));
    }

    private function isConfigured()
    {
        $privateKey = env('RSA', null);
        return $privateKey != null;
    }

    private function createAuthRoutes()
    {
        $routeFilePath = app_path('Routes/AuthRoutes.php');

        if (File::exists($routeFilePath)) {
            return;
        }

        File::ensureDirectoryExists(app_path('Routes'));

        $routeContent = "<?php

use Illuminate\Support\Facades\Route;
use App\Http\Actions\Auth\LoginAction;
use App\Http\Actions\Auth\LogoutAction;

Route::prefix('auth')
    ->name('auth.')
    ->group(function () {
        Route::post('/login', LoginAction::class)->name('login');
    });
";

        File::put($routeFilePath, $routeContent);
    }



    private function registerBinds()
    {
        $appServiceProviderPath = app_path('Providers/AppServiceProvider.php');

        if (!File::exists($appServiceProviderPath)) {
            $this->error('O arquivo AppServiceProvider.php não foi encontrado.');
            return;
        }

        $content = File::get($appServiceProviderPath);

        // Localizando a função `register`
        if (preg_match('/public function register\(\): void\s*{/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $registerStart = $matches[0][1] + strlen($matches[0][0]);

            $bindings = "\n        // Bind da interface para a implementação\n" .
                "        \$this->app->bind(\App\Services\Auth\AuthServiceInterface::class, \App\Services\Auth\SanctumAuthService::class);\n" .
                "        \$this->app->bind(\App\Repositories\Auth\LoginRepositoryInterface::class, \App\Repositories\Auth\LoginRepository::class);\n";

            // Inserindo os bindings dentro do método `register`
            $updatedContent = substr_replace($content, $bindings, $registerStart, 0);

            File::put($appServiceProviderPath, $updatedContent);

        } else {
            $this->error('Não foi possível localizar a função register no AppServiceProvider.php.');
        }
    }

    private function generateSanctumAuthService()
    {
        $servicePath = app_path('Services/Auth/SanctumAuthService.php');
        $interfacePath = app_path('Services/Auth/AuthServiceInterface.php');

        File::ensureDirectoryExists(app_path('Services/Auth'));

        // Criando a Interface AuthServiceInterface
        if (!File::exists($interfacePath)) {
            $interfaceContent = "<?php

namespace App\Services\Auth;

interface AuthServiceInterface
{
    public function authenticate(array \$data): array;

    public function logout(string \$token): bool;
}";
            File::put($interfacePath, $interfaceContent);
        }

        // Criando o SanctumAuthService
        if (!File::exists($servicePath)) {
            $serviceContent = "<?php

namespace App\Services\Auth;

use Laravel\\Sanctum\\PersonalAccessToken;
use Illuminate\Support\Facades\Auth;

class SanctumAuthService implements AuthServiceInterface
{
    public function authenticate(array \$data): array
    {
        if (!Auth::attempt(\$data)) {
            throw new \\Exception('Credenciais inválidas.');
        }

        \$user = Auth::user();
        return [
            'token' => \$user->createToken('API Token')->plainTextToken,
            'user' => \$user,
        ];
    }

    public function logout(string \$token): bool
    {
        \$accessToken = PersonalAccessToken::findToken(\$token);

        if (\$accessToken) {
            \$accessToken->delete();
            return true;
        }

        return false;
    }
}";
            File::put($servicePath, $serviceContent);
        }
    }

    private function generateAuthRepository()
    {
        $repositoryInterfacePath = app_path('Repositories/Auth/AuthRepositoryInterface.php');
        $repositoryPath = app_path('Repositories/Auth/AuthRepository.php');

        File::ensureDirectoryExists(app_path('Repositories/Auth'));

        // Criando a Interface AuthRepositoryInterface
        if (!File::exists($repositoryInterfacePath)) {
            $interfaceContent = "<?php

namespace App\Repositories\Auth;

interface AuthRepositoryInterface
{
    public function getPersonalAccessToken(string \$token);
}";
            File::put($repositoryInterfacePath, $interfaceContent);
        }

        // Criando o AuthRepository
        if (!File::exists($repositoryPath)) {
            $repositoryContent = "<?php

namespace App\Repositories\Auth;

use Laravel\\Sanctum\\PersonalAccessToken;

class AuthRepository implements AuthRepositoryInterface
{
    public function getPersonalAccessToken(string \$token)
    {
        return PersonalAccessToken::findToken(\$token);
    }
}";
            File::put($repositoryPath, $repositoryContent);
        }
    }

    private function ensureAuthRouteRequire()
    {
        $apiRouteFile = base_path('routes/api.php');
        $authRouteRequire = "\nrequire app_path('Routes/AuthRoutes.php');\n";

        // Carrega o conteúdo existente do arquivo
        $existingContent = File::get($apiRouteFile);

        // Verifica se o require já está presente
        if (strpos($existingContent, "require app_path('Routes/AuthRoutes.php')") === false) {
            File::append($apiRouteFile, $authRouteRequire);
        }
    }
}
