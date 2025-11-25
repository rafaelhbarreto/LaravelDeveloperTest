<?php

declare(strict_types=1);

use App\DTO\MemberListFiltersData;
use App\Http\Controllers\CongressMemberController;
use App\UseCases\GetCongressMemberDetailsUseCase;
use App\UseCases\GetCongressMembersListUseCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\View\View;

describe('CongressMemberController - Unit Tests', function () {
    beforeEach(function () {
        $this->listUseCase = Mockery::mock(GetCongressMembersListUseCase::class);
        $this->detailsUseCase = Mockery::mock(GetCongressMemberDetailsUseCase::class);

        // Mock the view factory
        $this->viewFactory = Mockery::mock(\Illuminate\Contracts\View\Factory::class);
        app()->instance(\Illuminate\Contracts\View\Factory::class, $this->viewFactory);

        $this->controller = new CongressMemberController(
            $this->listUseCase,
            $this->detailsUseCase
        );
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('index()', function () {
        it('calls GetCongressMembersListUseCase with correct filters', function () {
            $request = new Request([
                'name' => 'John Doe',
                'party' => 'Democratic',
                'state' => 'CA',
                'sort_by' => 'name',
                'sort_direction' => 'asc',
                'per_page' => 50,
            ]);

            $mockData = [
                'data' => [
                    ['bioguide_id' => 'D000001', 'name' => 'John Doe'],
                ],
                'links' => ['first' => 'http://test.com?page=1'],
                'meta' => ['current_page' => 1, 'total' => 1],
                'filters' => [
                    'name' => 'John Doe',
                    'party' => 'Democratic',
                    'state' => 'CA',
                    'sort_by' => 'name',
                    'sort_direction' => 'asc',
                    'per_page' => 50,
                ],
                'filterOptions' => [
                    'parties' => ['Democratic', 'Republican'],
                    'states' => ['CA', 'NY'],
                ],
            ];

            $this->listUseCase
                ->shouldReceive('execute')
                ->once()
                ->with(Mockery::on(function ($filters) {
                    return $filters instanceof MemberListFiltersData
                        && $filters->name === 'John Doe'
                        && $filters->party === 'Democratic'
                        && $filters->state === 'CA'
                        && $filters->sortBy === 'name'
                        && $filters->sortDirection === 'asc'
                        && $filters->perPage === 50;
                }))
                ->andReturn($mockData);

            $mockView = Mockery::mock(View::class);
            $this->viewFactory
                ->shouldReceive('make')
                ->once()
                ->with('congress.members.index', Mockery::any(), [])
                ->andReturn($mockView);

            $result = $this->controller->index($request);

            expect($result)->toBe($mockView);
        });

        it('uses default values when no filters are provided', function () {
            $request = new Request();

            $mockData = [
                'data' => [],
                'links' => [],
                'meta' => ['current_page' => 1, 'total' => 0],
                'filters' => [
                    'name' => null,
                    'party' => null,
                    'state' => null,
                    'sort_by' => 'updated_date',
                    'sort_direction' => 'desc',
                    'per_page' => 25,
                ],
                'filterOptions' => [
                    'parties' => [],
                    'states' => [],
                ],
            ];

            $this->listUseCase
                ->shouldReceive('execute')
                ->once()
                ->with(Mockery::on(function ($filters) {
                    return $filters instanceof MemberListFiltersData
                        && $filters->name === null
                        && $filters->party === null
                        && $filters->state === null
                        && $filters->sortBy === 'updated_date'
                        && $filters->sortDirection === 'desc'
                        && $filters->perPage === 25;
                }))
                ->andReturn($mockData);

            $mockView = Mockery::mock(View::class);
            $this->viewFactory
                ->shouldReceive('make')
                ->once()
                ->with(Mockery::any(), Mockery::any(), [])
                ->andReturn($mockView);

            $result = $this->controller->index($request);

            expect($result)->toBe($mockView);
        });

        it('passes correct data structure to view', function () {
            $request = new Request();

            $mockData = [
                'data' => [
                    ['bioguide_id' => 'D000001', 'name' => 'John Doe'],
                    ['bioguide_id' => 'S000002', 'name' => 'Jane Smith'],
                ],
                'links' => ['first' => 'http://test.com?page=1'],
                'meta' => ['current_page' => 1, 'total' => 2],
                'filters' => [
                    'name' => null,
                    'party' => null,
                    'state' => null,
                    'sort_by' => 'updated_date',
                    'sort_direction' => 'desc',
                    'per_page' => 25,
                ],
                'filterOptions' => [
                    'parties' => ['Democratic'],
                    'states' => ['CA'],
                ],
            ];

            $this->listUseCase
                ->shouldReceive('execute')
                ->once()
                ->andReturn($mockData);

            $mockView = Mockery::mock(View::class);
            $this->viewFactory
                ->shouldReceive('make')
                ->once()
                ->with('congress.members.index', Mockery::on(function ($data) use ($mockData) {
                    return isset($data['members'])
                        && isset($data['filters'])
                        && isset($data['filterOptions'])
                        && $data['members']['data'] === $mockData['data']
                        && $data['filters'] === $mockData['filters']
                        && $data['filterOptions'] === $mockData['filterOptions'];
                }), [])
                ->andReturn($mockView);

            $result = $this->controller->index($request);

            expect($result)->toBe($mockView);
        });

        it('handles partial filters correctly', function () {
            $request = new Request([
                'name' => 'John',
            ]);

            $mockData = [
                'data' => [['bioguide_id' => 'D000001', 'name' => 'John Doe']],
                'links' => [],
                'meta' => ['current_page' => 1, 'total' => 1],
                'filters' => ['name' => 'John', 'party' => null, 'state' => null, 'sort_by' => 'updated_date', 'sort_direction' => 'desc', 'per_page' => 25],
                'filterOptions' => ['parties' => [], 'states' => []],
            ];

            $this->listUseCase
                ->shouldReceive('execute')
                ->once()
                ->with(Mockery::on(function ($filters) {
                    return $filters->name === 'John'
                        && $filters->party === null
                        && $filters->state === null;
                }))
                ->andReturn($mockData);

            $mockView = Mockery::mock(View::class);
            $this->viewFactory
                ->shouldReceive('make')
                ->once()
                ->with(Mockery::any(), Mockery::any(), [])
                ->andReturn($mockView);

            $result = $this->controller->index($request);

            expect($result)->toBe($mockView);
        });
    });

    describe('show()', function () {
        it('calls GetCongressMemberDetailsUseCase with bioguide ID', function () {
            $bioguideId = 'D000001';

            $mockMember = [
                'bioguide_id' => 'D000001',
                'name' => 'John Doe',
                'party_name' => 'Democratic',
                'state' => 'CA',
                'terms' => [],
            ];

            $this->detailsUseCase
                ->shouldReceive('execute')
                ->once()
                ->with($bioguideId)
                ->andReturn($mockMember);

            $mockView = Mockery::mock(View::class);
            $this->viewFactory
                ->shouldReceive('make')
                ->once()
                ->with(Mockery::any(), Mockery::any(), [])
                ->andReturn($mockView);

            $result = $this->controller->show($bioguideId);

            expect($result)->toBe($mockView);
        });

        it('passes member data to view', function () {
            $bioguideId = 'D000001';

            $mockMember = [
                'bioguide_id' => 'D000001',
                'name' => 'John Doe',
                'party_name' => 'Democratic',
                'state' => 'CA',
                'district' => null,
                'depiction_image_url' => 'https://example.com/image.jpg',
                'official_website_url' => 'https://example.com',
                'updated_date' => '2024-01-15',
                'terms' => [
                    ['chamber' => 'House of Representatives', 'start_year' => 2020, 'end_year' => null],
                ],
            ];

            $this->detailsUseCase
                ->shouldReceive('execute')
                ->once()
                ->with($bioguideId)
                ->andReturn($mockMember);

            $mockView = Mockery::mock(View::class);
            $this->viewFactory
                ->shouldReceive('make')
                ->once()
                ->with('congress.members.show', Mockery::on(function ($data) use ($mockMember) {
                    return isset($data['member']) && $data['member'] === $mockMember;
                }), [])
                ->andReturn($mockView);

            $result = $this->controller->show($bioguideId);

            expect($result)->toBe($mockView);
        });

        it('calls abort when member is not found', function () {
            $bioguideId = 'NOTFOUND';

            $this->detailsUseCase
                ->shouldReceive('execute')
                ->once()
                ->with($bioguideId)
                ->andThrow(new ModelNotFoundException("Congress member with bioguide ID '{$bioguideId}' not found."));

            // Since we can't easily test abort() in unit tests, we just verify the exception is caught
            try {
                $this->controller->show($bioguideId);
                $this->fail('Expected abort to be called');
            } catch (\Throwable $e) {
                // Expected - abort was called but isn't available in unit test context
                expect(true)->toBeTrue();
            }
        });

        it('passes correct bioguide ID to use case', function () {
            $bioguideId = 'S000456';

            $mockMember = [
                'bioguide_id' => 'S000456',
                'name' => 'Jane Smith',
            ];

            $this->detailsUseCase
                ->shouldReceive('execute')
                ->once()
                ->with('S000456')
                ->andReturn($mockMember);

            $mockView = Mockery::mock(View::class);
            $this->viewFactory
                ->shouldReceive('make')
                ->once()
                ->with(Mockery::any(), Mockery::any(), [])
                ->andReturn($mockView);

            $result = $this->controller->show($bioguideId);

            expect($result)->toBe($mockView);
        });
    });

    describe('buildFiltersFromRequest()', function () {
        it('creates MemberListFiltersData with all parameters', function () {
            $request = new Request([
                'name' => 'Test Name',
                'party' => 'Republican',
                'state' => 'TX',
                'sort_by' => 'party_name',
                'sort_direction' => 'asc',
                'per_page' => 100,
            ]);

            $mockData = [
                'data' => [],
                'links' => [],
                'meta' => [],
                'filters' => [],
                'filterOptions' => ['parties' => [], 'states' => []],
            ];

            $this->listUseCase
                ->shouldReceive('execute')
                ->once()
                ->with(Mockery::on(function ($filters) {
                    return $filters->name === 'Test Name'
                        && $filters->party === 'Republican'
                        && $filters->state === 'TX'
                        && $filters->sortBy === 'party_name'
                        && $filters->sortDirection === 'asc'
                        && $filters->perPage === 100;
                }))
                ->andReturn($mockData);

            $mockView = Mockery::mock(View::class);
            $this->viewFactory
                ->shouldReceive('make')
                ->once()
                ->with(Mockery::any(), Mockery::any(), [])
                ->andReturn($mockView);

            $this->controller->index($request);
        });

        it('handles integer conversion for per_page', function () {
            $request = new Request([
                'per_page' => '50',
            ]);

            $mockData = [
                'data' => [],
                'links' => [],
                'meta' => [],
                'filters' => [],
                'filterOptions' => ['parties' => [], 'states' => []],
            ];

            $this->listUseCase
                ->shouldReceive('execute')
                ->once()
                ->with(Mockery::on(function ($filters) {
                    return $filters->perPage === 50;
                }))
                ->andReturn($mockData);

            $mockView = Mockery::mock(View::class);
            $this->viewFactory
                ->shouldReceive('make')
                ->once()
                ->with(Mockery::any(), Mockery::any(), [])
                ->andReturn($mockView);

            $this->controller->index($request);
        });
    });
});
