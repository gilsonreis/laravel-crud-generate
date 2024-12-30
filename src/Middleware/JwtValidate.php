<?php

namespace Gilsonreis\LaravelCrudGenerator\Middleware;

use App\Exceptions\JwtException;
use Closure;
use Exception;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Gilsonreis\LaravelCrudGenerator\Traits\ApiResponser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


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

        // Check if the request has the Authorization header
        if (!$request->hasHeader('Authorization')) {
            return $this->errorResponse('Token inválido', Response::HTTP_UNAUTHORIZED);
        }

        try {
            // Get the token from the Authorization header
            $bearer = trim(str_replace('Bearer', '', $request->header('Authorization')));

            // Decode the token using the public key
            $decoded = JWT::decode($bearer, new Key(env('PUBLIC'), 'RS256'));
            // Check if the token is expired
            if (time() > $decoded->exp) {
                throw new \Gilsonreis\LaravelCrudGenerator\Exception\JwtException('Token expirado',401);
            }
        } catch (ExpiredException $e) {
            throw new JwtException('Token expirado',401);
        } catch (Exception $e) {
            throw new \Gilsonreis\LaravelCrudGenerator\Exception\JwtException($e->getMessage(),401); // $this->errorResponse('Falha no token', Response::HTTP_FORBIDDEN);
        }
        return $next($request);
    }
}
