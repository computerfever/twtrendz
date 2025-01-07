<?php

namespace Acelle\Http\Middleware;

use Closure;
use Acelle\Model\User;

class Frontend
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $user = $request->user();

        // If user have no frontend access but has backend access
        if (isset($user) && !$user->can("customer_access", User::class) && $user->can("admin_access", User::class)) {
            return redirect()->action('Admin\HomeController@index');
        }

        // check if user not authorized for customer access
        if (!$user->can("customer_access", User::class)) {
            return redirect()->action('Controller@notAuthorized');
        }

        // Site offline
        if (\Acelle\Model\Setting::get('site_online') == 'no') {
            return redirect()->action('Controller@offline');
        }

        // User does not have customer account
        if (!$user->customer) {
            return redirect()->action('Controller@somethingWentWrong', [
                'message' => "User {$user->email} does not have a customer account!",
            ]);
        }

        // Customer account is not active & user is not admin
        if (!$user->customer->isActive()) {
            return redirect()->action('Controller@somethingWentWrong', [
                'message' => "Customer account {$user->customer->name} is not active!",
            ]);
        }

        // Language
        if ($user->customer->language) {
            \App::setLocale($user->customer->language->code);
            \Carbon\Carbon::setLocale($user->customer->language->code);
        }

        // DB here
        if (config('sharding.enabled') && $user->customer->hasLocalDb()) {
            $user->customer->setUserDbConnection();
        }

        // Wordpress db by user
        /*
        if (isset($user) && isset($user->customer)) {
            config([
                'database.connections.wordpress.database' => config('wordpress.'.$user->customer->id.'.db_name'),
                'database.connections.wordpress.prefix' => config('wordpress.'.$user->customer->id.'.db_prefix'),
                'wordpress.url' => config('wordpress.'.$user->customer->id.'.url'),
            ]);

            if (config('wordpress.'.$user->customer->id.'.db_host', false)) {
                config([
                    'database.connections.wordpress.host' => config('wordpress.'.$user->customer->id.'.db_host'),
                ]);
            }

            if (config('wordpress.'.$user->customer->id.'.db_port', false)) {
                config([
                    'database.connections.wordpress.port' => config('wordpress.'.$user->customer->id.'.db_port'),
                ]);
            }

            if (config('wordpress.'.$user->customer->id.'.db_user', false)) {
                config([
                    'database.connections.wordpress.username' => config('wordpress.'.$user->customer->id.'.db_user'),
                ]);
            }

            if (config('wordpress.'.$user->customer->id.'.db_password', false)) {
                config([
                    'database.connections.wordpress.password' => config('wordpress.'.$user->customer->id.'.db_password'),
                ]);
            }
        }
        */

        return $next($request);
    }
}
