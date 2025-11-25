<?php

declare(strict_types=1);

use App\Jobs\FetchCongressMembersPageJob;
use App\UseCases\FetchAndStoreCongressMembersUseCase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config(['congress.api.chunk_size' => 250]);
    config(['congress.queues.fetch' => 'congress-fetch']);

    $this->useCase = new FetchAndStoreCongressMembersUseCase();
});

test('executes with no limit and dispatches job to fetch all members', function () {
    Queue::fake();

    Log::shouldReceive('info')
        ->once()
        ->with('Starting Congress members fetch', [
            'limit' => 'unlimited',
            'chunk_size' => 250,
        ]);

    Log::shouldReceive('info')
        ->once()
        ->with('Initial fetch job dispatched');

    $this->useCase->execute(null);

    Queue::assertPushed(FetchCongressMembersPageJob::class, function ($job) {
        return $job->queue === 'congress-fetch';
    });
});

test('executes with limit and dispatches job', function () {
    Queue::fake();

    Log::shouldReceive('info')
        ->once()
        ->with('Starting Congress members fetch', [
            'limit' => 100,
            'chunk_size' => 250,
        ]);

    Log::shouldReceive('info')
        ->once()
        ->with('Initial fetch job dispatched');

    $this->useCase->execute(100);

    Queue::assertPushed(FetchCongressMembersPageJob::class);
});

test('dispatches fetch job when using configured chunk size', function () {
    config(['congress.api.chunk_size' => 500]);

    Queue::fake();

    Log::shouldReceive('info')->twice();

    $this->useCase->execute(null);

    Queue::assertPushed(FetchCongressMembersPageJob::class);
});

test('dispatches job to correct queue from config', function () {
    config(['congress.queues.fetch' => 'custom-queue']);

    Queue::fake();

    Log::shouldReceive('info')->twice();

    $this->useCase->execute(null);

    Queue::assertPushed(FetchCongressMembersPageJob::class, function ($job) {
        return $job->queue === 'custom-queue';
    });
});

test('dispatches job successfully', function () {
    Queue::fake();

    Log::shouldReceive('info')->twice();

    $this->useCase->execute(null);

    Queue::assertPushed(FetchCongressMembersPageJob::class);
});

test('dispatches job on each execution', function () {
    Queue::fake();

    Log::shouldReceive('info')->twice();

    $this->useCase->execute(null);

    Queue::assertPushed(FetchCongressMembersPageJob::class);
});

test('logs unlimited when limit is null', function () {
    Queue::fake();

    Log::shouldReceive('info')
        ->once()
        ->with('Starting Congress members fetch', [
            'limit' => 'unlimited',
            'chunk_size' => 250,
        ]);

    Log::shouldReceive('info')->once();

    $this->useCase->execute(null);
});

test('logs specific limit when provided', function () {
    Queue::fake();

    Log::shouldReceive('info')
        ->once()
        ->with('Starting Congress members fetch', [
            'limit' => 500,
            'chunk_size' => 250,
        ]);

    Log::shouldReceive('info')->once();

    $this->useCase->execute(500);
});

test('logs job dispatched after dispatch', function () {
    Queue::fake();

    Log::shouldReceive('info')->once();

    Log::shouldReceive('info')
        ->once()
        ->with('Initial fetch job dispatched');

    $this->useCase->execute(null);
});

test('dispatches exactly one job per execution', function () {
    Queue::fake();

    Log::shouldReceive('info')->twice();

    $this->useCase->execute(null);

    Queue::assertPushed(FetchCongressMembersPageJob::class, 1);
});

test('handles limit of zero', function () {
    Queue::fake();

    Log::shouldReceive('info')
        ->once()
        ->with('Starting Congress members fetch', [
            'limit' => 0,
            'chunk_size' => 250,
        ]);

    Log::shouldReceive('info')->once();

    $this->useCase->execute(0);

    Queue::assertPushed(FetchCongressMembersPageJob::class);
});

test('handles large limit values', function () {
    Queue::fake();

    Log::shouldReceive('info')
        ->once()
        ->with('Starting Congress members fetch', [
            'limit' => 10000,
            'chunk_size' => 250,
        ]);

    Log::shouldReceive('info')->once();

    $this->useCase->execute(10000);

    Queue::assertPushed(FetchCongressMembersPageJob::class);
});
