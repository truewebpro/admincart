<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticateCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $token = JWTAuth::parseToken();
            $customer = auth('customer')->authenticate($token);
            if(!$customer){
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            auth()->guard('customer')->setUser($customer);
            $request->setUserResolver(fn () => $customer);
            $request->merge(['customer' => $customer]);

        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token expired'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'Token invalid'], 401);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token missing or unreadable'], 401);
        }
        catch (Exception $e){
            return response()->json(['error' => 'Token invalid or expired'], 401);
        }
        return $next($request);
    }
}
