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
7. **Sobre**
8. **Sair**

Cada opção solicita os parâmetros necessários e executa os comandos correspondentes.

---

## Gerar Partes Individualmente

Você também pode usar comandos específicos para criar componentes individuais.

### 1. Gerar um Model

Gera um model com fillables automáticos, casts de data e json, relacionamentos e trait `SoftDeletes` (se aplicável):

```bash
php artisan make:crud-model --table=products --label=Produto --plural-label=Produtos --observer
```

### Parâmetros Disponíveis
- `--table`: Nome da tabela no banco de dados.
- `--label`: Nome do model (singular).
- `--plural-label`: Nome plural do model.
- `--observer`: Adiciona um observer ao model (opcional).

### 2. Gerar um Repository

Gera um repository baseado em um model especificado:

```bash
php artisan make:crud-repository RepositoryName --model=Product
```

### Parâmetros Disponíveis
- `repositoryName`: Nome do repository.
- `--model`: Nome do model associado ao repository (opcional).

### 3. Gerar UseCases

Gera os UseCases de um CRUD completo ou um UseCase em branco:

```bash
php artisan make:crud-use-case --model=Product
```

Para um UseCase em branco:

```bash
php artisan make:crud-use-case --name=ExampleUseCase --directory=Example
```

### Parâmetros Disponíveis
- `--model`: Nome do model para gerar o CRUD completo.
- `--name`: Nome do UseCase em branco.
- `--directory`: Diretório onde o UseCase em branco será criado.

### 4. Gerar Actions

Gera as actions de um CRUD ou uma action em branco:

```bash
php artisan make:crud-actions --model=Product
```

Para uma action em branco:

```bash
php artisan make:crud-actions --name=ExampleAction --directory=Example
```

### Parâmetros Disponíveis
- `--model`: Nome do model para gerar o CRUD.
- `--name`: Nome da action em branco.
- `--directory`: Diretório onde a action em branco será criada.

### 5. Gerar Rotas

Gera um arquivo de rotas para um CRUD completo ou cria um arquivo de rota em branco:

```bash
php artisan make:crud-routes --model=Product
```

Para um arquivo de rota em branco:

```bash
php artisan make:crud-routes --name=example
```

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

