<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    use ApiResponseTrait;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->current_user ?? Auth::guard('api')->user();

        if (!$user) {
            return $this->errorResponse('Utilisateur non authentifié.', 401);
        }

        // Vérifier si l'utilisateur a l'un des rôles requis
        if (!in_array($user->role, $roles)) {
            return $this->errorResponse(
                'Accès refusé. Rôle requis : ' . implode(' ou ', $roles),
                403
            );
        }

        // Vérifier les permissions spécifiques si nécessaire
        if ($request->has('required_permissions')) {
            $requiredPermissions = $request->required_permissions;
            $userPermissions = $user->permissions ?? [];

            $hasPermission = false;
            foreach ($requiredPermissions as $permission) {
                if (in_array($permission, $userPermissions)) {
                    $hasPermission = true;
                    break;
                }
            }

            if (!$hasPermission) {
                return $this->errorResponse(
                    'Permission insuffisante pour cette action.',
                    403
                );
            }
        }

        return $next($request);
    }
}
