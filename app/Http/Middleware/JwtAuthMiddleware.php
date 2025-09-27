<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JwtAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Get the JWT token from the Authorization header
            $authHeader = $request->header('Authorization');
            
            if (!$authHeader) {
                return $this->unauthorizedResponse('Authorization header is missing');
            }

            // Extract token from "Bearer <token>" format
            if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $this->unauthorizedResponse('Invalid authorization header format. Expected: Bearer <token>');
            }

            $token = $matches[1];

            if (empty($token)) {
                return $this->unauthorizedResponse('JWT token is missing');
            }

            // Set the token for JWT Auth to parse
            JWTAuth::setToken($token);

            // Attempt to authenticate the user
            $user = JWTAuth::authenticate();

            if (!$user) {
                return $this->unauthorizedResponse('User not found or token invalid');
            }

            // Add user information to request for use in controllers
            $request->merge([
                'jwt_user' => $user,
                'jwt_user_id' => $user->id,
                'jwt_claims' => JWTAuth::getPayload()->toArray()
            ]);

            return $next($request);

        } catch (TokenExpiredException $e) {
            return $this->unauthorizedResponse('Token has expired');
        } catch (TokenInvalidException $e) {
            return $this->unauthorizedResponse('Invalid token');
        } catch (JWTException $e) {
            \Log::error('JWT Authentication Error: ' . $e->getMessage());
            return $this->unauthorizedResponse('Token validation failed');
        } catch (\Exception $e) {
            \Log::error('JWT Authentication Error: ' . $e->getMessage());
            return $this->unauthorizedResponse('Authentication failed');
        }
    }

    /**
     * Return unauthorized response
     */
    private function unauthorizedResponse(string $message): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'error' => [
                'code' => -32001,
                'message' => 'Unauthorized',
                'data' => $message
            ]
        ], 401);
    }
}
