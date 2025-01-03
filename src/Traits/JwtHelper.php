<?php

namespace Gilsonreis\LaravelCrudGenerator\Traits;
use Exception;
use Firebase\JWT\JWT;
use Ramsey\Uuid\Uuid;




trait JwtHelper
{
    public  function generateJwt(?array $dados,  $ttl = null): array
    {
        $ttl ??= env('JWT_EXPIRE', 3600 * 48);
        $expiration = time() + $ttl;

        $payload = [
            'iss' => env('APP_URL', ''),
            'exp' => $expiration,
            'uid' => (string) Uuid::uuid4(),

        ];
        if(!empty($dados)){
            $payload['scope'] = $dados;
        }

        $privateKey = env('RSA');

        if(empty($privateKey)){
            throw new Exception("Chave privada não encontrada. É necessário criar uma chave RSA válida no seu env.");

        }
        $jwt = JWT::encode($payload, $privateKey, 'RS256');

        $returnJwt = [
            'token_type' => 'bearer',
            'access_token' => $jwt,
            'expires_in' => $expiration - time(),
        ];

        return $returnJwt;
    }
}
