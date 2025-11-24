<?php

declare(strict_types=1);

namespace App\UseCases;

use App\Contracts\MemberRepositoryInterface;
use App\DTO\MemberListFiltersData;
use App\Http\Resources\MemberResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetCongressMembersListUseCase
{
    public function __construct(
        private readonly MemberRepositoryInterface $memberRepository
    ) {}

    /**
     * Execute the use case to get a filtered and paginated list of Congress members.
     *
     * @return array{
     *     data: array,
     *     links: array,
     *     meta: array,
     *     filters: array,
     *     filterOptions: array
     * }
     */
    public function execute(MemberListFiltersData $filters): array
    {
        $paginatedMembers = $this->getPaginatedMembers($filters);

        $filterOptions = $this->getFilterOptions();

        return [
            'data' => $this->transformMembersData($paginatedMembers),
            'links' => $paginatedMembers->linkCollection()->toArray(),
            'meta' => $this->buildMetadata($paginatedMembers),
            'filters' => $filters->toArray(),
            'filterOptions' => $filterOptions,
        ];
    }

    private function getPaginatedMembers(MemberListFiltersData $filters): LengthAwarePaginator
    {
        return $this->memberRepository->getFilteredMembers($filters);
    }

    private function transformMembersData(LengthAwarePaginator $paginator): array
    {
        return MemberResource::collection($paginator->items())->resolve();
    }

    private function buildMetadata(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'from' => $paginator->firstItem(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
        ];
    }

    private function getFilterOptions(): array
    {
        return [
            'parties' => $this->memberRepository->getDistinctParties(),
            'states' => $this->memberRepository->getDistinctStates(),
        ];
    }
}
