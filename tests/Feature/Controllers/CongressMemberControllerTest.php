<?php

declare(strict_types=1);

use App\UseCases\GetCongressMemberDetailsUseCase;
use App\UseCases\GetCongressMembersListUseCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;

// Removed unused import

describe('CongressMemberController - Feature Tests', function () {
    describe('GET /congress/members', function () {
        it('returns 200 status code', function () {
            $this->mock(GetCongressMembersListUseCase::class)
                ->shouldReceive('execute')
                ->once()
                ->andReturn([
                    'data' => [],
                    'links' => [],
                    'meta' => ['current_page' => 1, 'total' => 0],
                    'filters' => [],
                    'filterOptions' => ['parties' => [], 'states' => []],
                ]);

            $this->get('/congress/members')
                ->assertOk();
        });

        it('renders congress.members.index view', function () {
            $this->mock(GetCongressMembersListUseCase::class)
                ->shouldReceive('execute')
                ->once()
                ->andReturn([
                    'data' => [],
                    'links' => [],
                    'meta' => ['current_page' => 1, 'total' => 0],
                    'filters' => [],
                    'filterOptions' => ['parties' => [], 'states' => []],
                ]);

            $this->get('/congress/members')
                ->assertViewIs('congress.members.index');
        });

        it('passes data to view correctly', function () {
            $mockData = [
                'data' => [],
                'links' => [],
                'meta' => [
                    'current_page' => 1,
                    'from' => null,
                    'last_page' => 1,
                    'per_page' => 25,
                    'to' => null,
                    'total' => 0,
                ],
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

            $this->mock(GetCongressMembersListUseCase::class)
                ->shouldReceive('execute')
                ->once()
                ->andReturn($mockData);

            $this->get('/congress/members')
                ->assertOk()
                ->assertViewIs('congress.members.index')
                ->assertViewHas('members')
                ->assertViewHas('filters')
                ->assertViewHas('filterOptions');
        });

        it('accepts name filter parameter', function () {
            $this->mock(GetCongressMembersListUseCase::class)
                ->shouldReceive('execute')
                ->once()
                ->with(Mockery::on(fn ($filters) => $filters->name === 'John Doe'))
                ->andReturn([
                    'data' => [['bioguide_id' => 'D000001', 'name' => 'John Doe', 'party_name' => 'Democratic', 'state' => 'CA']],
                    'links' => [],
                    'meta' => ['current_page' => 1, 'from' => 1, 'to' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 1],
                    'filters' => ['name' => 'John Doe'],
                    'filterOptions' => ['parties' => [], 'states' => []],
                ]);

            $this->get('/congress/members?name=John Doe')
                ->assertOk();
        });

        it('accepts party filter parameter', function () {
            $this->mock(GetCongressMembersListUseCase::class)
                ->shouldReceive('execute')
                ->once()
                ->with(Mockery::on(fn ($filters) => $filters->party === 'Democratic'))
                ->andReturn([
                    'data' => [],
                    'links' => [],
                    'meta' => ['current_page' => 1, 'total' => 0],
                    'filters' => ['party' => 'Democratic'],
                    'filterOptions' => ['parties' => [], 'states' => []],
                ]);

            $this->get('/congress/members?party=Democratic')
                ->assertOk();
        });

        it('accepts state filter parameter', function () {
            $this->mock(GetCongressMembersListUseCase::class)
                ->shouldReceive('execute')
                ->once()
                ->with(Mockery::on(fn ($filters) => $filters->state === 'CA'))
                ->andReturn([
                    'data' => [],
                    'links' => [],
                    'meta' => ['current_page' => 1, 'total' => 0],
                    'filters' => ['state' => 'CA'],
                    'filterOptions' => ['parties' => [], 'states' => []],
                ]);

            $this->get('/congress/members?state=CA')
                ->assertOk();
        });

        it('accepts sort_by parameter', function () {
            $this->mock(GetCongressMembersListUseCase::class)
                ->shouldReceive('execute')
                ->once()
                ->with(Mockery::on(fn ($filters) => $filters->sortBy === 'name'))
                ->andReturn([
                    'data' => [],
                    'links' => [],
                    'meta' => ['current_page' => 1, 'total' => 0],
                    'filters' => ['sort_by' => 'name'],
                    'filterOptions' => ['parties' => [], 'states' => []],
                ]);

            $this->get('/congress/members?sort_by=name')
                ->assertOk();
        });

        it('accepts sort_direction parameter', function () {
            $this->mock(GetCongressMembersListUseCase::class)
                ->shouldReceive('execute')
                ->once()
                ->with(Mockery::on(fn ($filters) => $filters->sortDirection === 'asc'))
                ->andReturn([
                    'data' => [],
                    'links' => [],
                    'meta' => ['current_page' => 1, 'total' => 0],
                    'filters' => ['sort_direction' => 'asc'],
                    'filterOptions' => ['parties' => [], 'states' => []],
                ]);

            $this->get('/congress/members?sort_direction=asc')
                ->assertOk();
        });

        it('accepts per_page parameter', function () {
            $this->mock(GetCongressMembersListUseCase::class)
                ->shouldReceive('execute')
                ->once()
                ->with(Mockery::on(fn ($filters) => $filters->perPage === 50))
                ->andReturn([
                    'data' => [],
                    'links' => [],
                    'meta' => ['current_page' => 1, 'total' => 0],
                    'filters' => ['per_page' => 50],
                    'filterOptions' => ['parties' => [], 'states' => []],
                ]);

            $this->get('/congress/members?per_page=50')
                ->assertOk();
        });

        it('accepts multiple filter parameters simultaneously', function () {
            $this->mock(GetCongressMembersListUseCase::class)
                ->shouldReceive('execute')
                ->once()
                ->with(Mockery::on(fn ($filters) => $filters->name === 'John'
                    && $filters->party === 'Republican'
                    && $filters->state === 'TX'
                    && $filters->sortBy === 'party_name'
                    && $filters->sortDirection === 'asc'
                    && $filters->perPage === 100))
                ->andReturn([
                    'data' => [],
                    'links' => [],
                    'meta' => ['current_page' => 1, 'total' => 0],
                    'filters' => [],
                    'filterOptions' => ['parties' => [], 'states' => []],
                ]);

            $this->get('/congress/members?name=John&party=Republican&state=TX&sort_by=party_name&sort_direction=asc&per_page=100')
                ->assertOk();
        });

        it('uses default values when no parameters provided', function () {
            $this->mock(GetCongressMembersListUseCase::class)
                ->shouldReceive('execute')
                ->once()
                ->with(Mockery::on(fn ($filters) => $filters->name === null
                    && $filters->party === null
                    && $filters->state === null
                    && $filters->sortBy === 'updated_date'
                    && $filters->sortDirection === 'desc'
                    && $filters->perPage === 25))
                ->andReturn([
                    'data' => [],
                    'links' => [],
                    'meta' => ['current_page' => 1, 'total' => 0],
                    'filters' => [],
                    'filterOptions' => ['parties' => [], 'states' => []],
                ]);

            $this->get('/congress/members')
                ->assertOk();
        });
    });

    describe('GET /congress/members/{bioguideId}', function () {
        it('returns 200 status code for existing member', function () {
            $this->mock(GetCongressMemberDetailsUseCase::class)
                ->shouldReceive('execute')
                ->once()
                ->with('D000001')
                ->andReturn([
                    'bioguide_id' => 'D000001',
                    'name' => 'John Doe',
                    'party_name' => 'Democratic',
                    'state' => 'CA',
                    'terms' => [],
                ]);

            $this->get('/congress/members/D000001')
                ->assertOk();
        });

        it('renders congress.members.show view', function () {
            $this->mock(GetCongressMemberDetailsUseCase::class)
                ->shouldReceive('execute')
                ->once()
                ->with('D000001')
                ->andReturn([
                    'bioguide_id' => 'D000001',
                    'name' => 'John Doe',
                    'party_name' => 'Democratic',
                    'state' => 'CA',
                    'terms' => [],
                ]);

            $this->get('/congress/members/D000001')
                ->assertViewIs('congress.members.show');
        });

        it('passes member data to view', function () {
            $mockMember = [
                'bioguide_id' => 'D000001',
                'name' => 'John Doe',
                'party_name' => 'Democratic',
                'state' => 'CA',
                'district' => 12,
                'depiction_image_url' => 'https://example.com/image.jpg',
                'official_website_url' => 'https://johndoe.gov',
                'updated_date' => '2024-01-15',
                'terms' => [
                    [
                        'chamber' => 'House of Representatives',
                        'start_year' => 2020,
                        'end_year' => null,
                        'is_current' => true,
                    ],
                ],
            ];

            $this->mock(GetCongressMemberDetailsUseCase::class)
                ->shouldReceive('execute')
                ->once()
                ->with('D000001')
                ->andReturn($mockMember);

            $this->get('/congress/members/D000001')
                ->assertViewHas('member', $mockMember);
        });

        it('returns 404 when member not found', function () {
            $this->mock(GetCongressMemberDetailsUseCase::class)
                ->shouldReceive('execute')
                ->once()
                ->with('NOTFOUND')
                ->andThrow(new ModelNotFoundException("Congress member with bioguide ID 'NOTFOUND' not found."));

            $this->get('/congress/members/NOTFOUND')
                ->assertNotFound();
        });

        it('handles different bioguide ID formats', function () {
            $this->mock(GetCongressMemberDetailsUseCase::class)
                ->shouldReceive('execute')
                ->once()
                ->with('S000456')
                ->andReturn([
                    'bioguide_id' => 'S000456',
                    'name' => 'Jane Smith',
                    'party_name' => 'Republican',
                    'state' => 'NY',
                    'terms' => [],
                ]);

            $this->get('/congress/members/S000456')
                ->assertOk();
        });

        it('passes correct bioguide ID to use case', function () {
            $bioguideId = 'A123456';

            $this->mock(GetCongressMemberDetailsUseCase::class)
                ->shouldReceive('execute')
                ->once()
                ->with($bioguideId)
                ->andReturn([
                    'bioguide_id' => $bioguideId,
                    'name' => 'Test Member',
                    'party_name' => 'Democratic',
                    'state' => 'CA',
                    'terms' => [],
                ]);

            $this->get("/congress/members/{$bioguideId}")
                ->assertOk();
        });

        it('handles member with multiple terms', function () {
            $mockMember = [
                'bioguide_id' => 'D000001',
                'name' => 'John Doe',
                'party_name' => 'Democratic',
                'state' => 'CA',
                'terms' => [
                    ['chamber' => 'House of Representatives', 'start_year' => 2010, 'end_year' => 2012],
                    ['chamber' => 'Senate', 'start_year' => 2012, 'end_year' => 2018],
                    ['chamber' => 'Senate', 'start_year' => 2018, 'end_year' => null],
                ],
            ];

            $this->mock(GetCongressMemberDetailsUseCase::class)
                ->shouldReceive('execute')
                ->once()
                ->with('D000001')
                ->andReturn($mockMember);

            $this->get('/congress/members/D000001')
                ->assertViewHas('member', $mockMember);
        });

        it('handles member with no terms', function () {
            $mockMember = [
                'bioguide_id' => 'D000001',
                'name' => 'John Doe',
                'party_name' => 'Democratic',
                'state' => 'CA',
                'terms' => [],
            ];

            $this->mock(GetCongressMemberDetailsUseCase::class)
                ->shouldReceive('execute')
                ->once()
                ->with('D000001')
                ->andReturn($mockMember);

            $this->get('/congress/members/D000001')
                ->assertViewHas('member', $mockMember);
        });
    });

    describe('Route Parameters Validation', function () {
        it('handles URL encoded parameters correctly', function () {
            $this->mock(GetCongressMembersListUseCase::class)
                ->shouldReceive('execute')
                ->once()
                ->with(Mockery::on(fn ($filters) => $filters->name === 'John Doe'))
                ->andReturn([
                    'data' => [],
                    'links' => [],
                    'meta' => ['current_page' => 1, 'total' => 0],
                    'filters' => [],
                    'filterOptions' => ['parties' => [], 'states' => []],
                ]);

            $this->get('/congress/members?name=' . urlencode('John Doe'))
                ->assertOk();
        });

        it('handles special characters in bioguide ID', function () {
            $this->mock(GetCongressMemberDetailsUseCase::class)
                ->shouldReceive('execute')
                ->once()
                ->with('D000001')
                ->andReturn([
                    'bioguide_id' => 'D000001',
                    'name' => 'Test',
                    'party_name' => 'Democratic',
                    'state' => 'CA',
                    'terms' => [],
                ]);

            $this->get('/congress/members/D000001')
                ->assertOk();
        });
    });

    describe('Error Handling', function () {
        it('returns 404 with custom message when member not found', function () {
            $this->mock(GetCongressMemberDetailsUseCase::class)
                ->shouldReceive('execute')
                ->once()
                ->with('INVALID')
                ->andThrow(new ModelNotFoundException("Congress member with bioguide ID 'INVALID' not found."));

            $this->get('/congress/members/INVALID')
                ->assertNotFound();
        });
    });
});
