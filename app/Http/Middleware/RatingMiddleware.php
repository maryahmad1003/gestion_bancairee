<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RatingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    use ApiResponseTrait;
        protected int $limit = 10;

        protected int $decaySeconds = 600;
    public function handle(Request $request, Closure $next): Response
    {
      $user = Auth::user();

      if(!$user){
        return $next($request);
        
      }

      $key = "rating_limit_user_{$user->id}";

      $requests = cache()->get($key, 0);

        if($requests >= $this->limit){
            DB::table('rating_logs')->insert([
            'user_id' => $user->id,
            'limit' => $this->limit,
            'blocked_at' => now(),
        ]);

            return $this->errorResponse("Vous avez atteint la limite de {$this->limiit} requêtes toutes les {$this->decaySeconds} secondes.", 429);
        }

        cache()->put($key, $requests + 1, now()->addSeconds($this->decaySeconds));
        return $next($request);

    }
}
