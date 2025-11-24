@extends('layouts.app')

@section('title', $member['name'])

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold text-gray-900">{{ $member['name'] }}</h1>
        </div>
    </header>

    <main>
        <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8 px-4">
            <!-- Back Button -->
            <div class="mb-6">
                <a href="{{ route('congress.members.index') }}"
                   class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Members List
                </a>
            </div>

            <!-- Member Information Card -->
            <div class="mb-6 overflow-hidden rounded-lg bg-white shadow">
                <div class="p-6">
                    <div class="flex flex-col gap-6 sm:flex-row">
                        <!-- Image Section -->
                        @if (!empty($member['depiction_image_url']))
                            <div class="shrink-0">
                                <img src="{{ $member['depiction_image_url'] }}"
                                     alt="{{ $member['name'] }}"
                                     class="h-32 w-32 rounded-lg object-cover sm:h-40 sm:w-40">
                                @if (!empty($member['depiction_attribution']))
                                    <p class="mt-2 text-xs text-gray-500">{{ $member['depiction_attribution'] }}</p>
                                @endif
                            </div>
                        @endif

                        <!-- Information Section -->
                        <div class="flex-1 space-y-4">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">{{ $member['name'] }}</h2>
                                <p class="mt-1 text-sm text-gray-500">Bioguide ID: {{ $member['bioguide_id'] }}</p>
                            </div>

                            <dl class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Party</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold
                                            @if($member['party_name'] === 'Democratic') bg-blue-100 text-blue-800
                                            @elseif($member['party_name'] === 'Republican') bg-red-100 text-red-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ $member['party_name'] ?? 'N/A' }}
                                        </span>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500">State</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $member['state'] ?? 'N/A' }}</dd>
                                </div>

                                @if (!empty($member['district']))
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">District</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $member['district'] }}</dd>
                                    </div>
                                @endif

                                @if (!empty($member['current_chamber']))
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Current Chamber</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($member['current_chamber']) }}</dd>
                                    </div>
                                @endif

                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $member['updated_date_human'] ?? 'N/A' }}</dd>
                                </div>
                            </dl>

                            @if (!empty($member['url']))
                                <div>
                                    <a href="{{ $member['url'] }}"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-800 transition">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                        Official Profile
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Terms Section -->
            <div class="rounded-lg bg-white p-6 shadow">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900">Congressional Terms</h2>
                    <span class="text-sm text-gray-500">
                        {{ $member['terms_count'] ?? 0 }} term{{ ($member['terms_count'] ?? 0) !== 1 ? 's' : '' }}
                    </span>
                </div>

                @if (!empty($member['terms']) && count($member['terms']) > 0)
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($member['terms'] as $term)
                            <div class="rounded-lg border border-gray-200 p-4 hover:border-gray-300 transition">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold
                                        @if($term['chamber'] === 'house') bg-green-100 text-green-800
                                        @elseif($term['chamber'] === 'senate') bg-purple-100 text-purple-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst($term['chamber']) }}
                                    </span>
                                    @if (empty($term['end_year']))
                                        <span class="text-xs font-semibold text-blue-600">Current</span>
                                    @endif
                                </div>

                                <div class="space-y-1">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $term['start_year'] }} - {{ $term['end_year'] ?? 'Present' }}
                                    </p>

                                    @if (!empty($term['state_name']))
                                        <p class="text-xs text-gray-600">{{ $term['state_name'] }}</p>
                                    @endif

                                    @if (!empty($term['member_type']))
                                        <p class="text-xs text-gray-600">{{ $term['member_type'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-500">No terms recorded for this member.</p>
                    </div>
                @endif
            </div>

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
