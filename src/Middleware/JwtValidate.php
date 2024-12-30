<?php

namespace Gilsonreis\LaravelCrudGenerator\Middleware;

use Closure;
use Exception;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Gilsonreis\LaravelCrudGenerator\Traits\ApiResponser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Gilsonreis\LaravelCrudGenerator\Exception\JwtException;

class JwtValidate
{
    use ApiResponser;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->hasHeader('Authorization')) {
            return $this->errorResponse('Token inválido', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $bearerToken = $this->extractBearerToken($request);

            $decodedToken = $this->decodeToken($bearerToken);

            $this->validateTokenExpiration($decodedToken);

            $this->mergeScopeIntoRequest($request, $decodedToken);
        } catch (JwtException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        } catch (Exception $e) {
            return $this->errorResponse('Falha no token', Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }

    /**
     * Extracts the Bearer token from the Authorization header.
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    private function extractBearerToken(Request $request): string
    {
        $authorizationHeader = $request->header('Authorization');
        return trim(str_replace('Bearer', '', $authorizationHeader));
    }

    /**
     * Decodes the JWT token using the public key.
     *
     * @param string $token
     * @return object
     */
    private function decodeToken(string $token): object
    {
        $publicKey = env('PUBLIC');

        if (empty($publicKey)) {
            throw new JwtException('Chave pública não configurada', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return JWT::decode($token, new Key($publicKey, 'RS256'));
    }

    /**
     * Validates the expiration of the token.
     *
     * @param object $decodedToken
     * @throws JwtException
     */
    private function validateTokenExpiration(object $decodedToken): void
    {
        if (time() > $decodedToken->exp) {
            throw new JwtException('Token expirado', Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Merges the token's scope data into the request.
     *
     * @param \Illuminate\Http\Request $request
     * @param object $decodedToken
     */
    private function mergeScopeIntoRequest(Request $request, object $decodedToken): void
    {
        if (!empty($decodedToken->scope)) {
            $mergedData = [];
            foreach ($decodedToken->scope as $key => $value) {
                $mergedData[$key] = (array) $value;
            }
            $request->merge($mergedData);
        }
    }
}
