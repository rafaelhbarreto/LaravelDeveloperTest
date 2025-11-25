<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\CongressApiClientInterface;
use App\Contracts\MemberRepositoryInterface;
use App\Repositories\MemberRepository;
use App\Services\CongressApiClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CongressApiClientInterface::class, CongressApiClient::class);
        $this->app->singleton(MemberRepositoryInterface::class, MemberRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
