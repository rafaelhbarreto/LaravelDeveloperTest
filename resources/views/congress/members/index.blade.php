@extends('layouts.app')

@section('title', 'Congress Members')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold text-gray-900">Congress Members</h1>
        </div>
    </header>

    <main>
        <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8 px-4">
            <!-- Filters Section -->
            <div class="mb-6 rounded-lg bg-white p-6 shadow">
                <form method="GET" action="{{ route('congress.members.index') }}" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Name Filter -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Name
                            </label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ $filters['name'] ?? '' }}"
                                placeholder="Search by name..."
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            >
                        </div>

                        <!-- Party Filter -->
                        <div>
                            <label for="party" class="block text-sm font-medium text-gray-700 mb-1">
                                Party
                            </label>
                            <select
                                id="party"
                                name="party"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            >
                                <option value="">All Parties</option>
                                @foreach ($filterOptions['parties'] ?? [] as $party)
                                    <option value="{{ $party }}" {{ ($filters['party'] ?? '') === $party ? 'selected' : '' }}>
                                        {{ $party }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- State Filter -->
                        <div>
                            <label for="state" class="block text-sm font-medium text-gray-700 mb-1">
                                State
                            </label>
                            <select
                                id="state"
                                name="state"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            >
                                <option value="">All States</option>
                                @foreach ($filterOptions['states'] ?? [] as $state)
                                    <option value="{{ $state }}" {{ ($filters['state'] ?? '') === $state ? 'selected' : '' }}>
                                        {{ $state }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Per Page -->
                        <div>
                            <label for="per_page" class="block text-sm font-medium text-gray-700 mb-1">
                                Per Page
                            </label>
                            <select
                                id="per_page"
                                name="per_page"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            >
                                <option value="25" {{ ($filters['per_page'] ?? 25) == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ ($filters['per_page'] ?? 25) == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ ($filters['per_page'] ?? 25) == 100 ? 'selected' : '' }}>100</option>
                            </select>
                        </div>
                    </div>

                    <!-- Hidden sort fields -->
                    <input type="hidden" name="sort_by" value="{{ $filters['sort_by'] ?? 'updated_date' }}">
                    <input type="hidden" name="sort_direction" value="{{ $filters['sort_direction'] ?? 'desc' }}">

                    <!-- Action Buttons -->
                    <div class="flex gap-2">
                        <button
                            type="submit"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            Apply Filters
                        </button>
                        <a
                            href="{{ route('congress.members.index') }}"
                            class="rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500"
                        >
                            Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Data Table -->
            <div class="overflow-hidden rounded-lg bg-white shadow mb-6">
                <!-- Desktop Table -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                @php
                                    $columns = [
                                        'name' => 'Name',
                                        'party_name' => 'Party',
                                        'state' => 'State',
                                        'district' => 'District',
                                        'updated_date' => 'Last Update'
                                    ];
                                    $currentSort = $filters['sort_by'] ?? 'updated_date';
                                    $currentDirection = $filters['sort_direction'] ?? 'desc';
                                @endphp

                                @foreach ($columns as $column => $label)
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                        <a href="{{ route('congress.members.index', array_merge(request()->all(), [
                                            'sort_by' => $column,
                                            'sort_direction' => ($currentSort === $column && $currentDirection === 'asc') ? 'desc' : 'asc'
                                        ])) }}" class="flex items-center gap-2 hover:text-gray-900 {{ $currentSort === $column ? 'text-gray-900' : 'text-gray-700' }}">
                                            <span>{{ $label }}</span>
                                            <span class="text-gray-400">
                                                @if ($currentSort === $column)
                                                    {{ $currentDirection === 'asc' ? '↑' : '↓' }}
                                                @else
                                                    ↕
                                                @endif
                                            </span>
                                        </a>
                                    </th>
                                @endforeach
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">View</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($members['data'] ?? [] as $member)
                                <tr class="transition hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $member['name'] }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5
                                            @if($member['party_name'] === 'Democratic') bg-blue-100 text-blue-800
                                            @elseif($member['party_name'] === 'Republican') bg-red-100 text-red-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ $member['party_name'] ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                        {{ $member['state'] ?? 'N/A' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                        {{ $member['district'] ?? 'N/A' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {{ $member['updated_date_human'] ?? '' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        <a href="{{ route('congress.members.show', $member['bioguide_id']) }}"
                                           class="text-blue-600 hover:text-blue-900 transition">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">No members found</h3>
                                        <p class="mt-1 text-sm text-gray-500">Try adjusting your filters.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden divide-y divide-gray-200">
                    @forelse ($members['data'] ?? [] as $member)
                        <div class="p-4 hover:bg-gray-50 transition">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-gray-900">{{ $member['name'] }}</h3>
                                    <div class="mt-1 flex flex-wrap gap-2">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold
                                            @if($member['party_name'] === 'Democratic') bg-blue-100 text-blue-800
                                            @elseif($member['party_name'] === 'Republican') bg-red-100 text-red-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ $member['party_name'] ?? 'N/A' }}
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            {{ $member['state'] ?? 'N/A' }}
                                            @if (!empty($member['district']))
                                                - District {{ $member['district'] }}
                                            @endif
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Updated {{ $member['updated_date_human'] ?? '' }}
                                    </p>
                                </div>
                                <a href="{{ route('congress.members.show', $member['bioguide_id']) }}"
                                   class="ml-4 text-sm font-medium text-blue-600 hover:text-blue-900 transition">
                                    View
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No members found</h3>
                            <p class="mt-1 text-sm text-gray-500">Try adjusting your filters.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Pagination -->
            @if (!empty($members['meta']) && $members['meta']['total'] > 0)
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-white rounded-lg p-4 shadow">
                    <!-- Showing text -->
                    <div class="text-sm text-gray-700">
                        Showing {{ $members['meta']['from'] }} to {{ $members['meta']['to'] }} of {{ $members['meta']['total'] }} results
                    </div>

                    <!-- Pagination links -->
                    <nav class="flex items-center gap-1">
                        @foreach ($members['links'] ?? [] as $link)
                            @if ($link['label'] === '&laquo; Previous')
                                <a href="{{ $link['url'] ?? '#' }}"
                                   class="relative inline-flex items-center rounded-l-md px-3 py-2 text-sm font-medium transition
                                   {{ $link['url'] ? 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300' : 'bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200' }}"
                                   @if(!$link['url']) disabled @endif>
                                    <span class="sr-only">Previous</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            @elseif ($link['label'] === 'Next &raquo;')
                                <a href="{{ $link['url'] ?? '#' }}"
                                   class="relative inline-flex items-center rounded-r-md px-3 py-2 text-sm font-medium transition
                                   {{ $link['url'] ? 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300' : 'bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200' }}"
                                   @if(!$link['url']) disabled @endif>
                                    <span class="sr-only">Next</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            @else
                                <a href="{{ $link['url'] ?? '#' }}"
                                   class="relative inline-flex items-center px-4 py-2 text-sm font-medium transition border
                                   {{ $link['active'] ? 'z-10 bg-blue-600 text-white border-blue-600' : ($link['url'] ? 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300' : 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200') }}"
                                   @if(!$link['url']) disabled @endif>
                                    {!! $link['label'] !!}
                                </a>
                            @endif
                        @endforeach
                    </nav>
                </div>
            @endif
        </div>
    </main>

    <!-- Footer -->
    <footer class="mt-12 border-t border-gray-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <p class="text-center text-sm text-gray-500">
                Data from Congress.gov API
            </p>
        </div>
    </footer>
</div>
@endsection
