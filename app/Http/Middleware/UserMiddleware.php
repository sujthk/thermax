<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
class UserMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        
        if(Auth::user()->user_type =='ADMIN')
        {
             return $next($request);
        }
        else
        {
            $user_calculators = Auth::user()->calculators->pluck('route')->merge(['dashboard','logout','profile'])->toArray();
           
            $route = $request->route()->getName();
           
            if(in_array($route,$user_calculators))
                return $next($request);
            else
               return redirect('/dashboard');    
        }

       
    }
}
