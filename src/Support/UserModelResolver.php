<?php

namespace Arseno25\ExceptionLogger\Support;

class UserModelResolver
{
    public static function resolve(): string
    {
        $defaultGuard = config('auth.defaults.guard');

        if ($defaultGuard) {
            $provider = config("auth.guards.{$defaultGuard}.provider");

            if ($provider) {
                $model = config("auth.providers.{$provider}.model");

                if (is_string($model) && $model !== '') {
                    return $model;
                }
            }
        }

        $usersProviderModel = config('auth.providers.users.model');

        if (is_string($usersProviderModel) && $usersProviderModel !== '') {
            return $usersProviderModel;
        }

        foreach (config('auth.providers', []) as $provider) {
            $model = $provider['model'] ?? null;

            if (is_string($model) && $model !== '') {
                return $model;
            }
        }

        $fallbackModel = config('auth.model');

        if (is_string($fallbackModel) && $fallbackModel !== '') {
            return $fallbackModel;
        }

        return \Illuminate\Foundation\Auth\User::class;
    }
}

