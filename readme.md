
# Laravel CRUD Generator

O **Laravel CRUD Generator** é uma biblioteca projetada para automatizar a criação de componentes essenciais de um CRUD, como Models, Repositories, UseCases, Actions e Rotas. Esta documentação fornece um guia passo a passo para usar a biblioteca de forma eficiente.

## Instalação

Certifique-se de ter o PHP 8.1 ou superior instalado e um projeto Laravel configurado.

1. Instale a biblioteca via Composer:

```bash
composer require gilsonreis/laravel-crud-generator
```

2. Publique a configuração (se aplicável):

```bash
php artisan vendor:publish --provider="Gilsonreis\LaravelCrudGenerator\LaravelCrudGeneratorServiceProvider"
```

3. Atualize o autoload do Composer:

```bash
composer dump-autoload
```

4. Certifique-se de que o namespace das classes está registrado corretamente no arquivo `app/Console/Kernel.php` ou no `ServiceProvider` do pacote.

---

## Menu Interativo

A biblioteca oferece um menu interativo para facilitar o uso. Para acessá-lo, execute:

```bash
php artisan crud:menu
```

### Opções Disponíveis

1. **Gerar CRUD completo**
2. **Gerar Action**
3. **Gerar UseCase**
4. **Gerar Repository**
5. **Gerar Model**
6. **Gerar Rotas**
7. **Gerar Login (Utiliza Sanctum)**
8. **Gerar Login (Utiliza JWT)**
9. **Sobre**
10. **Sair**

Cada opção solicita os parâmetros necessários e executa os comandos correspondentes.

---

## Gerar Partes Individualmente

Você também pode usar comandos específicos para criar componentes individuais.

### 1. Gerar um Model

Gera um model com fillables automáticos, casts de data e json, relacionamentos e trait `SoftDeletes` (se aplicável):

```bash
php artisan make:crud-model --table=products --label=Produto --plural-label=Produtos --observer
```

### 2. Gerar um Repository

Gera um repository baseado em um model especificado:

```bash
php artisan make:crud-repository RepositoryName --model=Product
```

### 3. Gerar UseCases

Gera os UseCases de um CRUD completo ou um UseCase em branco:

```bash
php artisan make:crud-use-case --model=Product
```

### 4. Gerar Actions

Gera as actions de um CRUD ou uma action em branco:

```bash
php artisan make:crud-actions --model=Product
```

### 5. Gerar Rotas

Gera um arquivo de rotas para um CRUD completo ou cria um arquivo de rota em branco:

```bash
php artisan make:crud-routes --model=Product
```

---

## Gerar o Login

O sistema de autenticação utiliza o Laravel Sanctum e pode ser gerado com o comando:

```bash
php artisan make:crud-auth
```

## Gerar o Login (JWT)

O sistema de autenticação para uisar JWT, pode ser egrado com o seguinte comando:

```bash
php artisan make:crud-auth-jwt
```

### Componentes Gerados

1. **Rotas**: Um arquivo de rotas em `app/Routes/AuthRoutes.php`, contendo rotas para login e logout.
2. **Actions**: Classes para `LoginAction` e `LogoutAction`.
3. **UseCases**: Classes para `LoginUseCase` e `LogoutUseCase`.
4. **FormRequest**: Validação para login em `LoginRequest`.
5. **Services**: Serviço de autenticação usando `SanctumAuthService`.
6. **Repositories**: Classes `LoginRepository` e `AuthRepository`.

### Exemplo de Rotas Geradas

```php
Route::prefix('auth')
    ->name('auth.')
    ->group(function () {
        Route::post('/login', LoginAction::class)->name('login');
        Route::delete('/logout', LogoutAction::class)
            ->name('logout')
            ->middleware('auth:sanctum');
    });
```

### Caso tenha escolhido JWT 
```php
Route::prefix('auth')
    ->name('auth.')
    ->group(function () {
        Route::post('/login', LoginAction::class)->name('login');
    });
```

### Testando o Login

#### Login

Endpoint:
- **URL**: `/api/auth/login`
- **Método**: `POST`
- **Corpo**:
  ```json
  {
    "email": "usuario@example.com",
    "password": "senha"
  }
  ```

#### Logout (somente SANCTUM)

Endpoint:
- **URL**: `/api/auth/logout`
- **Método**: `DELETE`
- **Headers**:
    - `Authorization: Bearer {token}`

---

## Gerar o CRUD Completo

Para gerar todas as partes de um CRUD (Model, Repository, UseCases, Actions e Rotas):

```bash
php artisan crud:menu
```

Selecione a opção **Gerar CRUD completo** e preencha os parâmetros solicitados.

---

## Utilizando o Filtro Genérico

A biblioteca inclui um sistema de filtros dinâmicos inspirado no Strapi. Você pode utilizar filtros na query string para manipular os resultados das consultas.

### Exemplo de Query String

```plaintext
Product[name]=Laptop&Product[price_between]=1000,2000&page=2&perPage=15
```

### Tipos de Filtros Suportados

- **`campo=value`**: Filtra pelo valor exato.
- **`campo_like=value`**: Filtra usando `LIKE`.
- **`campo_between=value1,value2`**: Filtra por intervalo.
- **`campo_in=value1,value2`**: Filtra pelos valores especificados.
- **`campo_not_in=value1,value2`**: Exclui os valores especificados.
- **`campo_greater_than=value`**: Filtra valores maiores que o especificado.
- **`campo_less_than=value`**: Filtra valores menores que o especificado.
- **`campo_is_null`**: Filtra valores nulos.
- **`campo_not_null`**: Filtra valores não nulos.


---

## Contribuições

Contribuições são bem-vindas! Por favor, envie um pull request ou abra uma issue no repositório oficial no GitHub.

## Licença

Este projeto está licenciado sob a [Licença MIT](LICENSE).