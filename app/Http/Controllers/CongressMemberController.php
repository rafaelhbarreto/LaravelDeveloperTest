<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\MemberListFiltersData;
use App\UseCases\GetCongressMemberDetailsUseCase;
use App\UseCases\GetCongressMembersListUseCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CongressMemberController extends Controller
{
    public function __construct(
        private readonly GetCongressMembersListUseCase $getListUseCase,
        private readonly GetCongressMemberDetailsUseCase $getDetailsUseCase
    ) {}

    /**
     * Display a paginated and filtered list of Congress members.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $filters = $this->buildFiltersFromRequest($request);

        $data = $this->getListUseCase->execute($filters);

        return $this->renderIndexView($data);
    }

    /**
     * Display detailed information about a specific Congress member.
     *
     * @param string $bioguideId
     * @return View
     */
    public function show(string $bioguideId): View
    {
        try {
            $member = $this->getDetailsUseCase->execute($bioguideId);

            return $this->renderShowView($member);
        } catch (ModelNotFoundException $e) {
            abort(404, $e->getMessage());
        }
    }

    /**
     * Build filters DTO from HTTP request.
     *
     * @param Request $request
     * @return MemberListFiltersData
     */
    private function buildFiltersFromRequest(Request $request): MemberListFiltersData
    {
        return new MemberListFiltersData(
            name: $request->input('name'),
            party: $request->input('party'),
            state: $request->input('state'),
            sortBy: $request->input('sort_by', 'updated_date'),
            sortDirection: $request->input('sort_direction', 'desc'),
            perPage: $request->integer('per_page', 25),
        );
    }

    /**
     * Render the index view with members list data.
     *
     * @param array $data
     * @return View
     */
    private function renderIndexView(array $data): View
    {
        return view('congress.members.index', [
            'members' => [
                'data' => $data['data'],
                'links' => $data['links'],
                'meta' => $data['meta'],
            ],
            'filters' => $data['filters'],
            'filterOptions' => $data['filterOptions'],
        ]);
    }

    /**
     * Render the show view with member details data.
     *
     * @param array $member
     * @return View
     */
    private function renderShowView(array $member): View
    {
        return view('congress.members.show', [
            'member' => $member,
        ]);
    }
}
