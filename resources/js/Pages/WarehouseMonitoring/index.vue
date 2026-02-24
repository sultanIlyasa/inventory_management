<template>
    <MainAppLayout title="Warehouse Monitoring" subtitle="Warehouse GR Monitoring">
        <div class="min-h-screen bg-gray-50">
            <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <!-- Header -->
                <header class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">
                            Section Head Dashboard Overview
                        </p>
                        <h1 class="text-2xl font-semibold text-gray-900">Warehouse Performance Monitoring</h1>
                    </div>

                    <button
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 active:bg-gray-100"
                        @click="showMobileFilters = !showMobileFilters">
                        <span>{{ showMobileFilters ? 'Hide Filters' : 'Show Filters' }}</span>
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4h18M4 8h16M6 12h12m-8 4h4m-6 4h8" />
                        </svg>
                    </button>
                </header>

                <!-- Error -->
                <div v-if="error" class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    {{ error }}
                </div>

                <!-- Filters (dummy) -->
                <div v-if="showMobileFilters" class="mt-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label class="text-xs font-medium text-gray-600">Warehouse</label>
                            <select class="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm">
                                <option>All</option>
                                <option>Sunter 1</option>
                                <option>Sunter 2</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Shift</label>
                            <select class="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm">
                                <option>All</option>
                                <option>Shift 1</option>
                                <option>Shift 2</option>
                                <option>Shift 3</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Search</label>
                            <input class="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm"
                                placeholder="Material / Part No" />
                        </div>
                    </div>
                </div>

                <!-- Main grid -->
                <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-12 lg:gap-6">
                    <!-- LEFT -->
                    <section class="lg:col-span-8 space-y-4 lg:space-y-6">
                        <!-- Problematic Materials -->
                        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div class="flex flex-col gap-3 border-b border-gray-100 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                            <path d="M12 2l8 4.5v11L12 22l-8-4.5v-11L12 2z" stroke="currentColor"
                                                stroke-width="2" />
                                            <path d="M12 2v20" stroke="currentColor" stroke-width="2" opacity=".3" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">Problematic Materials</p>
                                        <p class="text-xs text-gray-500">
                                            {{ pagination.total }} items · Click a row to view details
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <!-- Status filter pills -->
                                    <div class="flex items-center gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1">
                                        <button
                                            v-for="f in (['all', 'SHORTAGE', 'CAUTION'] as const)"
                                            :key="f"
                                            @click="setStatusFilter(f)"
                                            :class="statusFilter === f
                                                ? f === 'SHORTAGE' ? 'bg-red-500 text-white' : f === 'CAUTION' ? 'bg-amber-500 text-white' : 'bg-white text-gray-800 shadow-sm'
                                                : 'text-gray-500 hover:text-gray-700'"
                                            class="rounded-md px-2.5 py-1 text-xs font-semibold transition-colors">
                                            {{ f === 'all' ? 'All' : f }}
                                        </button>
                                    </div>
                                    <span class="hidden text-xs text-gray-400 sm:inline">Updated: {{ updatedAt }}</span>
                                </div>
                            </div>

                            <!-- Unified responsive table (drag to pan) -->
                            <div ref="tableScrollRef"
                                class="overflow-x-auto cursor-grab active:cursor-grabbing"
                                @mousedown="onPanStart"
                                @mousemove="onPanMove"
                                @mouseup="onPanEnd"
                                @mouseleave="onPanEnd">
                                <table class="min-w-full">
                                    <thead class="bg-gray-50">
                                        <tr class="text-left text-[10px] font-semibold uppercase tracking-wide text-gray-400">
                                            <th class="px-3 py-2 w-8">#</th>
                                            <th class="px-3 py-2">Material</th>
                                            <th class="px-3 py-2 hidden sm:table-cell">Status</th>
                                            <th class="px-3 py-2 hidden sm:table-cell">Severity</th>
                                            <th class="px-3 py-2 hidden md:table-cell whitespace-nowrap">Durability</th>
                                            <th class="px-3 py-2 whitespace-nowrap">Streak</th>
                                            <th class="px-3 py-2 hidden md:table-cell whitespace-nowrap">Last Updated</th>
                                            <th class="px-3 py-2 whitespace-nowrap">Est. GR</th>
                                            <th class="px-3 py-2 w-6"></th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-gray-100">
                                        <!-- Skeleton -->
                                        <template v-if="isLoadingMaterials">
                                            <tr v-for="n in 5" :key="'skel-' + n" class="animate-pulse">
                                                <td class="px-3 py-2.5"><div class="h-3 w-5 rounded bg-gray-200"></div></td>
                                                <td class="px-3 py-2.5">
                                                    <div class="h-3 w-36 rounded bg-gray-200"></div>
                                                    <div class="mt-1 h-2.5 w-20 rounded bg-gray-100"></div>
                                                </td>
                                                <td class="px-3 py-2.5 hidden sm:table-cell"><div class="h-4 w-16 rounded-full bg-gray-200"></div></td>
                                                <td class="px-3 py-2.5 hidden sm:table-cell"><div class="h-4 w-20 rounded bg-gray-200"></div></td>
                                                <td class="px-3 py-2.5 hidden md:table-cell"><div class="h-3 w-14 rounded bg-gray-200"></div></td>
                                                <td class="px-3 py-2.5"><div class="h-3 w-10 rounded bg-gray-200"></div></td>
                                                <td class="px-3 py-2.5 hidden md:table-cell"><div class="h-3 w-18 rounded bg-gray-200"></div></td>
                                                <td class="px-3 py-2.5"><div class="h-6 w-28 rounded bg-gray-200"></div></td>
                                                <td class="px-3 py-2.5"></td>
                                            </tr>
                                        </template>

                                        <!-- Empty -->
                                        <tr v-else-if="sortedMaterials.length === 0">
                                            <td colspan="9" class="px-4 py-10 text-center text-xs text-gray-400">
                                                No problematic materials found.
                                            </td>
                                        </tr>

                                        <!-- Data rows -->
                                        <tr v-else v-for="(m, idx) in sortedMaterials" :key="m.id"
                                            class="cursor-pointer hover:bg-gray-50 active:bg-gray-100"
                                            @click="() => { if (!didPan) openDetail(m) }">

                                            <!-- # -->
                                            <td class="px-3 py-2.5 text-[10px] font-medium text-gray-400 tabular-nums">
                                                {{ (pagination.current_page - 1) * pagination.per_page + idx + 1 }}
                                            </td>

                                            <!-- Material — shows status+severity badges on xs when columns are hidden -->
                                            <td class="px-3 py-2.5 max-w-[160px] sm:max-w-none">
                                                <div class="truncate text-xs font-semibold text-gray-900">{{ m.description }}</div>
                                                <div class="text-[10px] font-mono text-gray-400">{{ m.material_number }}</div>
                                                <div class="mt-1 flex flex-wrap gap-1 sm:hidden">
                                                    <span :class="statusPill(m.status)"
                                                        class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold">
                                                        {{ m.status }}
                                                    </span>
                                                    <span :class="severityPill(m.severity)"
                                                        class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold">
                                                        {{ m.severity }}
                                                    </span>
                                                </div>
                                            </td>

                                            <!-- Status (sm+) -->
                                            <td class="px-3 py-2.5 hidden sm:table-cell">
                                                <span :class="statusPill(m.status)"
                                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold">
                                                    {{ m.status }}
                                                </span>
                                            </td>

                                            <!-- Severity (sm+) -->
                                            <td class="px-3 py-2.5 hidden sm:table-cell">
                                                <span :class="severityPill(m.severity)"
                                                    class="inline-flex items-center rounded px-2 py-0.5 text-[10px] font-semibold">
                                                    {{ m.severity }}
                                                </span>
                                            </td>

                                            <!-- Durability (md+) -->
                                            <td class="px-3 py-2.5 hidden md:table-cell">
                                                <span v-if="m.coverage_shifts !== null" class="text-xs font-semibold text-gray-800">
                                                    {{ m.coverage_shifts.toFixed(1) }}<span class="text-[10px] font-normal text-gray-400"> sh</span>
                                                </span>
                                                <span v-else class="text-xs text-gray-300">—</span>
                                            </td>

                                            <!-- Streak -->
                                            <td class="px-3 py-2.5">
                                                <span class="text-xs font-semibold text-red-600">{{ m.streak_days }}d</span>
                                            </td>

                                            <!-- Last Updated (md+) -->
                                            <td class="px-3 py-2.5 hidden md:table-cell">
                                                <span class="text-xs text-gray-600">{{ formatDate(m.last_updated) }}</span>
                                            </td>

                                            <!-- Est. GR -->
                                            <td class="px-3 py-2.5" @click.stop>
                                                <div class="flex items-center gap-1">
                                                    <input
                                                        type="date"
                                                        :value="m.estimated_gr ?? ''"
                                                        @change="saveEstimatedGR(m.id, ($event.target as HTMLInputElement).value)"
                                                        class="w-32 rounded border border-gray-200 px-1.5 py-1 text-xs text-gray-700 focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-300"
                                                    />
                                                    <span v-if="grSaveState[m.id] === 'saving'" class="text-[10px] text-gray-400">…</span>
                                                    <span v-else-if="grSaveState[m.id] === 'saved'" class="text-[10px] text-emerald-600">✓</span>
                                                    <span v-else-if="grSaveState[m.id] === 'error'" class="text-[10px] text-red-500">!</span>
                                                </div>
                                            </td>

                                            <!-- Chevron -->
                                            <td class="px-3 py-2.5 text-right">
                                                <svg class="h-3.5 w-3.5 text-gray-400" viewBox="0 0 24 24" fill="none">
                                                    <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination bar -->
                            <div v-if="pagination.last_page > 1"
                                class="flex items-center justify-between border-t border-gray-100 px-4 py-3">
                                <p class="text-xs text-gray-500">
                                    <span class="font-medium text-gray-700">
                                        {{ (pagination.current_page - 1) * pagination.per_page + 1 }}–{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}
                                    </span>
                                    <span class="hidden sm:inline"> of {{ pagination.total }}</span>
                                </p>
                                <div class="flex items-center gap-1">
                                    <button
                                        @click="goToPage(pagination.current_page - 1)"
                                        :disabled="pagination.current_page === 1"
                                        class="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                                        ‹
                                    </button>
                                    <template v-for="p in pageWindow" :key="String(p)">
                                        <span v-if="p === '…'" class="px-1 text-xs text-gray-400">…</span>
                                        <button v-else
                                            @click="goToPage(p as number)"
                                            :class="p === pagination.current_page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                                            class="rounded-lg border px-2.5 py-1.5 text-xs font-semibold">
                                            {{ p }}
                                        </button>
                                    </template>
                                    <button
                                        @click="goToPage(pagination.current_page + 1)"
                                        :disabled="pagination.current_page === pagination.last_page"
                                        class="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                                        ›
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Lower left: Trends + Recommendation -->
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                            <!-- Trends -->
                            <div class="lg:col-span-7 space-y-4">
                                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                                <path d="M12 3v10l7 4" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" />
                                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"
                                                    opacity=".3" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-900">Overdue Days Trend</p>
                                    </div>
                                    <div class="mt-3">
                                        <canvas ref="overdueCanvas" class="h-40 w-full"></canvas>
                                    </div>
                                </div>

                                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                                <path d="M4 14l5-5 4 4 7-7" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M20 7v6h-6" stroke="currentColor" stroke-width="2"
                                                    opacity=".3" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-900">Recovery Days Trend</p>
                                    </div>
                                    <div class="mt-3">
                                        <canvas ref="recoveryCanvas" class="h-40 w-full"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Recommendation -->
                            <div class="lg:col-span-5">
                                <div class="rounded-xl border border-blue-200 bg-blue-50 shadow-sm">
                                    <div class="flex items-center justify-between gap-3 border-b border-blue-100 p-4">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-blue-600">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                                    <path d="M12 2l9 4-9 4-9-4 9-4z" stroke="currentColor"
                                                        stroke-width="2" />
                                                    <path d="M3 10l9 4 9-4" stroke="currentColor" stroke-width="2"
                                                        opacity=".4" />
                                                    <path d="M3 14l9 4 9-4" stroke="currentColor" stroke-width="2"
                                                        opacity=".25" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-blue-900">System Recommendation
                                                    Action</p>
                                                <p class="text-xs text-blue-700">What should I do right now?</p>
                                            </div>
                                        </div>
                                        <span class="text-xs font-medium text-blue-700">{{ updatedAt }}</span>
                                    </div>

                                    <div class="p-4 space-y-3">
                                        <div class="rounded-xl border border-red-200 bg-white p-3">
                                            <div class="flex items-center gap-2">
                                                <span class="h-2 w-2 rounded-full bg-red-500"></span>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-red-700">
                                                    Immediate Action</p>
                                            </div>
                                            <div class="mt-2">
                                                <p class="text-sm font-semibold text-gray-900">{{
                                                    recommendation.immediate.material }}</p>
                                                <ul class="mt-2 list-disc pl-5 text-xs text-gray-700 space-y-1">
                                                    <li v-for="(a, idx) in recommendation.immediate.actions"
                                                        :key="'im-' + idx">{{ a }}</li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="rounded-xl border border-amber-200 bg-white p-3">
                                            <div class="flex items-center gap-2">
                                                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">
                                                    Urgent <span class="font-medium normal-case text-amber-700/80">(Next
                                                        2 shifts)</span>
                                                </p>
                                            </div>
                                            <div class="mt-2">
                                                <p class="text-sm font-semibold text-gray-900">{{
                                                    recommendation.urgent.summary }}</p>
                                                <ul class="mt-2 list-disc pl-5 text-xs text-gray-700 space-y-1">
                                                    <li v-for="(a, idx) in recommendation.urgent.actions"
                                                        :key="'ur-' + idx">{{ a }}</li>
                                                </ul>
                                            </div>
                                        </div>

                                        <button
                                            class="w-full rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 active:bg-blue-800"
                                            @click="openDetail(topPriorityMaterial)">
                                            View Material Analysis →
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- RIGHT -->
                    <aside class="lg:col-span-4 space-y-4 lg:space-y-6">
                        <!-- Distribution -->
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                        <path d="M4 19V5" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" />
                                        <path d="M4 19h16" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" />
                                        <path d="M8 16V8" stroke="currentColor" stroke-width="2" opacity=".5" />
                                        <path d="M12 16V6" stroke="currentColor" stroke-width="2" opacity=".6" />
                                        <path d="M16 16v-4" stroke="currentColor" stroke-width="2" opacity=".7" />
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-gray-900">Material Status Distribution</p>
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-3">
                                <div class="h-48">
                                    <canvas ref="distributionCanvas" class="h-full w-full"></canvas>
                                </div>

                                <div class="grid grid-cols-3 gap-2 text-center">
                                    <div class="rounded-lg border border-gray-200 p-2">
                                        <div class="text-xs text-gray-500">Ok</div>
                                        <div class="text-sm font-semibold text-gray-900">{{ distribution.ok }}</div>
                                    </div>
                                    <div class="rounded-lg border border-gray-200 p-2">
                                        <div class="text-xs text-gray-500">Caution</div>
                                        <div class="text-sm font-semibold text-gray-900">{{ distribution.caution }}
                                        </div>
                                    </div>
                                    <div class="rounded-lg border border-gray-200 p-2">
                                        <div class="text-xs text-gray-500">Shortage</div>
                                        <div class="text-sm font-semibold text-gray-900">{{ distribution.shortage }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Changes Avg -->
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                        <path d="M4 19V5" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" />
                                        <path d="M4 19h16" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" />
                                        <path d="M8 17V11" stroke="currentColor" stroke-width="2" />
                                        <path d="M12 17V7" stroke="currentColor" stroke-width="2" opacity=".7" />
                                        <path d="M16 17V9" stroke="currentColor" stroke-width="2" opacity=".5" />
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-gray-900">Status Changes Avg</p>
                            </div>
                            <div class="mt-3">
                                <canvas ref="statusChangeCanvas" class="h-44 w-full"></canvas>
                            </div>
                        </div>

                        <!-- Fastest to Critical -->
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex h-7 w-7 items-center justify-center rounded-lg bg-orange-50 text-orange-600">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 2l4 8-4 4-4-4 4-8z" stroke="currentColor" stroke-width="2" />
                                        <path d="M6 22h12" stroke="currentColor" stroke-width="2" opacity=".35" />
                                        <path d="M8 18h8" stroke="currentColor" stroke-width="2" opacity=".5" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Top 5 Fastest to Critical</p>
                                    <p class="text-xs text-gray-500">Mean time to critical materials (days)</p>
                                </div>
                            </div>

                            <div class="mt-3">
                                <canvas ref="fastestCanvas" class="h-56 w-full"></canvas>
                            </div>
                        </div>
                    </aside>
                </div>

                <p class="mt-6 text-xs text-gray-500">
                    Note: Dummy data for UI preview. Replace with API data when ready.
                </p>
                <!-- Leaderboards (Below main view) -->
                <section class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-12 lg:gap-6">
                    <!-- Shortage Leaderboard -->
                    <div class="lg:col-span-6 rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                        <div class="border-b border-gray-100 bg-red-50/40 p-4">
                            <div class="flex items-center gap-2">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                        <path d="M4 7h10M4 12h8M4 17h6" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" />
                                        <path d="M18 6l2 2-6 6-3 1 1-3 6-6z" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Shortage Leaderboard</p>
                                    <p class="text-xs text-gray-600">Top materials with 0 stock level</p>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        <th class="px-4 py-3 w-16">Rank</th>
                                        <th class="px-4 py-3">Material</th>
                                        <th class="px-4 py-3">Material Number</th>
                                        <th class="px-4 py-3">Streak Days</th>
                                        <th class="px-4 py-3">Severity</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="row in shortageLeaderboard" :key="row.rank" class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-center">
                                                <span
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-red-50 text-sm font-semibold text-red-700">
                                                    {{ row.rank }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-gray-900">{{ row.material }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-sm text-gray-700">{{ row.material_number }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-semibold text-red-600">{{ row.streakDays }} Days
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span :class="severityPill(row.severity)"
                                                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold">
                                                {{ row.severity }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Caution Leaderboard -->
                    <div class="lg:col-span-6 rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                        <div class="border-b border-gray-100 bg-amber-50/40 p-4">
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 3l10 18H2L12 3z" stroke="currentColor" stroke-width="2"
                                            stroke-linejoin="round" />
                                        <path d="M12 9v5" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" />
                                        <path d="M12 17h.01" stroke="currentColor" stroke-width="3"
                                            stroke-linecap="round" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Caution Leaderboard</p>
                                    <p class="text-xs text-gray-600">Top Materials below minimum stock level</p>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        <th class="px-4 py-3 w-16">Rank</th>
                                        <th class="px-4 py-3">Material</th>
                                        <th class="px-4 py-3">Material Number</th>
                                        <th class="px-4 py-3 min-w-[240px]">Stock Level</th>
                                        <th class="px-4 py-3">Days Left</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="row in cautionLeaderboard" :key="row.rank" class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-center">
                                                <span
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-amber-50 text-sm font-semibold text-amber-800">
                                                    {{ row.rank }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-gray-900">{{ row.material }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-sm text-gray-700">{{ row.material_number }}</div>
                                        </td>

                                        <!-- Stock Level: bar + value -->
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <div class="h-2 w-28 sm:w-36 rounded-full bg-gray-200 overflow-hidden">
                                                    <div class="h-full rounded-full bg-orange-500"
                                                        :style="{ width: `${row.stockPct}%` }"></div>
                                                </div>
                                                <div class="text-sm text-gray-700 whitespace-nowrap">
                                                    {{ row.current }} / {{ row.min }} <span class="text-gray-500">{{
                                                        row.unit }}</span>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-4 py-3">
                                            <div :class="daysLeftClass(row.daysLeft)" class="text-sm font-semibold">
                                                {{ row.daysLeft }} days
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
                <!-- Material Inventory Overview (Below leaderboards) -->
                <section class="mt-6 rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                    <!-- Header -->
                    <div class="p-4 sm:p-5 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-900">Material Inventory Overview</h2>
                        <p class="mt-1 text-sm text-gray-500">Complete tracking of warehouse materials and their status
                        </p>
                    </div>

                    <!-- Desktop Table -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-semibold text-gray-500">
                                    <th class="px-4 py-3">
                                        <div class="inline-flex items-center gap-2">
                                            Material
                                            <span class="text-gray-400">↑↓</span>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3">
                                        <div class="inline-flex items-center gap-2">
                                            Material Number
                                            <span class="text-gray-400">↑↓</span>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3">Category</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Overdue Days</th>
                                    <th class="px-4 py-3">Recovery Days</th>
                                    <th class="px-4 py-3 text-right">SoH</th>
                                    <th class="px-4 py-3 w-10"></th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="row in inventoryRows" :key="row.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-gray-900">{{ row.material }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-700">{{ row.sku }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-700">{{ row.category }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="inventoryStatusPill(row.status)"
                                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold">
                                            {{ row.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span v-if="row.overdueDays === null" class="text-sm text-gray-500">-</span>
                                        <span v-else class="text-sm font-semibold text-red-600">{{ row.overdueDays }}
                                            days</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span v-if="row.recoveryDays === null" class="text-sm text-gray-500">-</span>
                                        <span v-else class="text-sm font-semibold text-blue-600">{{ row.recoveryDays }}
                                            days</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-sm font-medium text-gray-900">{{ formatNumber(row.soh)
                                            }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button
                                            class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                                            title="More">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                                <path
                                                    d="M12 6.5a1.25 1.25 0 110-2.5 1.25 1.25 0 010 2.5zM12 13.25a1.25 1.25 0 110-2.5 1.25 1.25 0 010 2.5zM12 20a1.25 1.25 0 110-2.5 1.25 1.25 0 010 2.5z"
                                                    fill="currentColor" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="md:hidden p-4 space-y-3">
                        <div v-for="row in inventoryRows" :key="row.id"
                            class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">{{ row.material }}</div>
                                    <div class="mt-0.5 text-xs text-gray-500">{{ row.sku }} • {{ row.category }}</div>
                                </div>
                                <button class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                                    title="More">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                        <path
                                            d="M12 6.5a1.25 1.25 0 110-2.5 1.25 1.25 0 010 2.5zM12 13.25a1.25 1.25 0 110-2.5 1.25 1.25 0 010 2.5zM12 20a1.25 1.25 0 110-2.5 1.25 1.25 0 010 2.5z"
                                            fill="currentColor" />
                                    </svg>
                                </button>
                            </div>

                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span :class="inventoryStatusPill(row.status)"
                                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold">
                                    {{ row.status }}
                                </span>
                                <span class="text-xs text-gray-500">SoH:</span>
                                <span class="text-xs font-semibold text-gray-900">{{ formatNumber(row.soh) }}</span>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                <div class="rounded-lg border border-gray-200 p-2">
                                    <div class="text-gray-500">Overdue</div>
                                    <div v-if="row.overdueDays === null" class="font-semibold text-gray-700">-</div>
                                    <div v-else class="font-semibold text-red-600">{{ row.overdueDays }} days</div>
                                </div>
                                <div class="rounded-lg border border-gray-200 p-2">
                                    <div class="text-gray-500">Recovery</div>
                                    <div v-if="row.recoveryDays === null" class="font-semibold text-gray-700">-</div>
                                    <div v-else class="font-semibold text-blue-600">{{ row.recoveryDays }} days</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer / Pagination -->
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-t border-gray-100 p-4">
                        <p class="text-sm text-gray-500">
                            Showing <span class="font-medium text-gray-700">{{ inventoryRows.length }}</span> of
                            <span class="font-medium text-gray-700">{{ inventoryRows.length }}</span> materials
                        </p>

                        <div class="flex items-center gap-2">
                            <button
                                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Previous
                            </button>
                            <button
                                class="rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white">1</button>
                            <button
                                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Next
                            </button>
                        </div>
                    </div>
                </section>

            </div>



            <!-- Detail Modal -->
            <teleport to="body">
                <div v-if="showDetailModal" class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center"
                    @keydown.esc="closeDetail" tabindex="-1">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/40" @click="closeDetail"></div>

                    <!-- Panel -->
                    <div
                        class="relative w-full sm:max-w-2xl rounded-t-2xl sm:rounded-2xl bg-white shadow-xl border border-gray-200">
                        <div class="flex items-start justify-between gap-3 p-4 border-b border-gray-100">
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="text-base font-semibold text-gray-900">
                                        {{ selectedMaterial?.description }} <span class="text-gray-500 font-medium">({{
                                            selectedMaterial?.material_number }})</span>
                                    </p>
                                    <span v-if="selectedMaterial" :class="statusPill(selectedMaterial.status)"
                                        class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold">
                                        {{ selectedMaterial.status }}
                                    </span>
                                    <span v-if="selectedMaterial" :class="severityPill(selectedMaterial.severity)"
                                        class="inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold">
                                        {{ selectedMaterial.severity }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Updated: {{
                                    formatDate(selectedMaterial?.last_updated ?? null)
                                }}</p>
                            </div>

                            <button class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                                @click="closeDetail" aria-label="Close">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                    <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-4 space-y-4 max-h-[75vh] overflow-auto">
                            <!-- Key stats -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div class="rounded-xl border border-gray-200 p-3">
                                    <p class="text-xs text-gray-500">Durability Stock</p>
                                    <p class="text-sm font-semibold text-gray-900">
                                        <template v-if="selectedMaterial?.coverage_shifts !== null">
                                            {{ selectedMaterial?.coverage_shifts?.toFixed(1) }} shifts
                                        </template>
                                        <span v-else class="text-gray-400">No data</span>
                                    </p>
                                </div>
                                <div class="rounded-xl border border-gray-200 p-3">
                                    <p class="text-xs text-gray-500">Streak Days</p>
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ selectedMaterial?.streak_days }} days
                                    </p>
                                </div>
                                <div class="rounded-xl border border-gray-200 p-3">
                                    <p class="text-xs text-gray-500">Current Stock</p>
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ selectedMaterial?.instock }} {{ selectedMaterial?.usage }}
                                    </p>
                                </div>
                                <div class="rounded-xl border border-gray-200 p-3">
                                    <p class="text-xs text-gray-500">Action</p>
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ selectedMaterial?.severity === 'Line-Stop Risk' ? 'Escalate' : 'Monitor' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Material info -->
                            <div class="rounded-xl border border-gray-200 p-3">
                                <p class="text-sm font-semibold text-gray-900 mb-2">Material Info</p>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div><span class="text-gray-500">PIC:</span> <span class="font-medium">{{
                                        selectedMaterial?.pic_name }}</span></div>
                                    <div><span class="text-gray-500">Location:</span> <span class="font-medium">{{
                                        selectedMaterial?.location }}</span></div>
                                    <div><span class="text-gray-500">Usage:</span> <span class="font-medium">{{
                                        selectedMaterial?.usage }}</span></div>
                                    <div><span class="text-gray-500">Shift avg:</span> <span class="font-medium">{{
                                        selectedMaterial?.shift_avg ?? '—' }}</span></div>
                                    <div><span class="text-gray-500">Daily avg:</span> <span class="font-medium">{{
                                        selectedMaterial?.daily_avg ?? '—' }}</span></div>
                                    <div><span class="text-gray-500">Instock:</span> <span class="font-medium">{{
                                        selectedMaterial?.instock ?? '—' }}</span></div>
                                    <div class="col-span-2 flex items-center gap-2">
                                        <span class="text-gray-500">Est. GR:</span>
                                        <input
                                            v-if="selectedMaterial"
                                            type="date"
                                            :value="selectedMaterial.estimated_gr ?? ''"
                                            @change="saveEstimatedGR(selectedMaterial.id, ($event.target as HTMLInputElement).value)"
                                            class="rounded border border-gray-200 px-2 py-1 text-sm text-gray-700 focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-300"
                                        />
                                        <span v-if="selectedMaterial && grSaveState[selectedMaterial.id] === 'saving'" class="text-xs text-gray-400">Saving…</span>
                                        <span v-else-if="selectedMaterial && grSaveState[selectedMaterial.id] === 'saved'" class="text-xs text-emerald-600">Saved ✓</span>
                                        <span v-else-if="selectedMaterial && grSaveState[selectedMaterial.id] === 'error'" class="text-xs text-red-500">Error !</span>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="p-4 border-t border-gray-100 flex flex-col sm:flex-row gap-2 sm:justify-end">
                            <button
                                class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                                @click="closeDetail">
                                Close
                            </button>
                            <button
                                class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                Create Escalation Note
                            </button>
                        </div>
                    </div>
                </div>
            </teleport>
        </div>
    </MainAppLayout>
</template>

<script setup lang="ts">
import MainAppLayout from '@/Layouts/MainAppLayout.vue'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import {
    Chart,
    CategoryScale,
    LinearScale,
    ArcElement,
    PointElement,
    LineElement,
    BarElement,
    Tooltip,
    Legend,
    Title,
    Filler,
    // ✅ controllers (required)
    LineController,
    BarController,
    DoughnutController,
} from 'chart.js'

Chart.register(
    // scales
    CategoryScale,
    LinearScale,

    // elements
    ArcElement,
    PointElement,
    LineElement,
    BarElement,

    // controllers ✅
    LineController,
    BarController,
    DoughnutController,

    // plugins
    Tooltip,
    Legend,
    Title,
    Filler
)


const error = ref<string | null>(null)
const showMobileFilters = ref(false)
const updatedAt = ref('-')
const isLoadingMaterials = ref(false)

type Status = 'CAUTION' | 'SHORTAGE'
type Severity = 'Low' | 'Medium' | 'High' | 'Line-Stop Risk' | 'Critical'

type MaterialRow = {
    id: number
    material_number: string
    description: string
    pic_name: string
    status: Status
    severity: Severity
    coverage_shifts: number | null
    daily_avg: number | null
    shift_avg: number | null
    instock: number
    streak_days: number
    location: string
    usage: string
    last_updated: string | null
    estimated_gr: string | null
}

const materials = ref<MaterialRow[]>([])

type SaveState = 'idle' | 'saving' | 'saved' | 'error'
const grSaveState = ref<Record<number, SaveState>>({})

async function saveEstimatedGR(id: number, date: string | null) {
    grSaveState.value[id] = 'saving'
    try {
        const res = await fetch(`/warehouse-monitoring/api/problematic/${id}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '' },
            body: JSON.stringify({ estimated_gr: date || null }),
        })
        if (!res.ok) throw new Error(`HTTP ${res.status}`)
        const json = await res.json()
        const mat = materials.value.find(m => m.id === id)
        if (mat) mat.estimated_gr = json.estimated_gr ?? null
        grSaveState.value[id] = 'saved'
        setTimeout(() => { grSaveState.value[id] = 'idle' }, 2000)
    } catch {
        grSaveState.value[id] = 'error'
        setTimeout(() => { grSaveState.value[id] = 'idle' }, 3000)
    }
}

// --- Filter & Pagination state ---
type StatusFilter = 'all' | 'SHORTAGE' | 'CAUTION'
const statusFilter = ref<StatusFilter>('all')
const pagination = ref({ current_page: 1, per_page: 10, total: 0, last_page: 1 })

async function fetchProblematicMaterials(page = 1) {
    isLoadingMaterials.value = true
    error.value = null
    try {
        const params = new URLSearchParams({ page: String(page), per_page: String(pagination.value.per_page) })
        if (statusFilter.value !== 'all') params.set('status', statusFilter.value)
        const res = await fetch(`/warehouse-monitoring/api/problematic?${params}`)
        if (!res.ok) throw new Error(`HTTP ${res.status}`)
        const json = await res.json()
        materials.value = json.data ?? []
        if (json.pagination) pagination.value = json.pagination
        if (materials.value.length > 0 && materials.value[0].last_updated) {
            updatedAt.value = formatDate(materials.value[0].last_updated)
        }
    } catch (e) {
        error.value = 'Failed to load problematic materials. Please refresh the page.'
    } finally {
        isLoadingMaterials.value = false
    }
}

function setStatusFilter(f: StatusFilter) {
    statusFilter.value = f
    fetchProblematicMaterials(1)
}

function goToPage(page: number) {
    if (page < 1 || page > pagination.value.last_page) return
    fetchProblematicMaterials(page)
}

// Page window: show at most 5 page numbers around current
const pageWindow = computed(() => {
    const { current_page, last_page } = pagination.value
    const delta = 2
    const pages: (number | '…')[] = []
    const start = Math.max(1, current_page - delta)
    const end   = Math.min(last_page, current_page + delta)
    if (start > 1) { pages.push(1); if (start > 2) pages.push('…') }
    for (let p = start; p <= end; p++) pages.push(p)
    if (end < last_page) { if (end < last_page - 1) pages.push('…'); pages.push(last_page) }
    return pages
})

async function fetchAverageConsumption() {
    try {
        const res = await fetch('/warehouse-monitoring/api/consumption-averages')
        if (!res.ok) throw new Error(`HTTP ${res.status}`)
        const json = await res.json()
        console.log(json)
    } catch (e) {
        error.value = "failed"
    }
}

type SeverityLB = 'Critical' | 'High' | 'Medium'

const shortageLeaderboard = ref([
    { rank: 1, material: 'Steel Rebar #8', material_number: 'STL-RB-008', streakDays: 15, severity: 'Critical' as SeverityLB },
    { rank: 2, material: 'Concrete Mix Type II', material_number: 'CNC-MX-002', streakDays: 11, severity: 'Critical' as SeverityLB },
    { rank: 3, material: 'Plywood 18mm', material_number: 'PLY-18MM', streakDays: 9, severity: 'High' as SeverityLB },
    { rank: 4, material: 'PVC Pipe 4"', material_number: 'PVC-P-004', streakDays: 9, severity: 'High' as SeverityLB },
    { rank: 5, material: 'Electrical Wire 12AWG', material_number: 'ELC-W-012', streakDays: 3, severity: 'Medium' as SeverityLB },
])

const cautionLeaderboard = ref([
    { rank: 1, material: 'Cement Portland', material_number: 'CMT-PRT-001', current: 850, min: 1500, unit: 'bags', daysLeft: 4, stockPct: 38 },
    { rank: 2, material: 'Sand Fine Grade', material_number: 'SND-FN-001', current: 1200, min: 2000, unit: 'm³', daysLeft: 5, stockPct: 45 },
    { rank: 3, material: 'Paint Latex White', material_number: 'PNT-LTX-WH', current: 320, min: 500, unit: 'liters', daysLeft: 6, stockPct: 52 },
    { rank: 4, material: 'Nails 3" Common', material_number: 'NLS-3IN-CMN', current: 75, min: 150, unit: 'kg', daysLeft: 7, stockPct: 50 },
    { rank: 5, material: 'Insulation Foam', material_number: 'INS-FOM-001', current: 180, min: 300, unit: 'panels', daysLeft: 8, stockPct: 60 },
])



// color the "days left" text like the screenshot
const daysLeftClass = (days: number) => {
    if (days <= 4) return 'text-red-600'
    if (days <= 6) return 'text-orange-600'
    return 'text-amber-600'
}


const recommendation = ref({
    immediate: {
        material: 'Steel Bearing A-2045',
        actions: ['Escalate to procurement lead', 'Request emergency PO expedite', 'Check alternative suppliers'],
    },
    urgent: {
        summary: '2 materials need attention',
        actions: ['Follow up on pending GR', 'Monitor consumption rates', 'Prepare escalation docs'],
    },
})

const distribution = ref({ ok: 144, caution: 3, shortage: 3 })
const months6 = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']
const overdueSeries = [150, 165, 140, 125, 105, 90]
const recoverySeries = [90, 95, 110, 98, 105, 118]

const statusChangesMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug']
const statusChanges = [3, 5, 4, 7, 6, 4, 8, 6]

const fastestToCritical = ref([
    { name: 'Steel Bearing A-2045', days: 3 },
    { name: 'Hydraulic Seal HX-920', days: 4 },
    { name: 'Electric Motor EM-5500', days: 5 },
    { name: 'Filter Cartridge FC-220', days: 6 },
    { name: 'Gasket Set GS-110', days: 8 },
])

// Backend already returns rows sorted by status priority + streak days desc.
// Client re-sorts only by severity so Line-Stop Risk always floats to the top.
const severityRank = (s: Severity) => (s === 'Line-Stop Risk' ? 3 : s === 'High' ? 2 : s === 'Medium' ? 1 : 0)

const sortedMaterials = computed(() => {
    return [...materials.value].sort((a, b) => {
        const sr = severityRank(b.severity) - severityRank(a.severity)
        if (sr !== 0) return sr
        const aCov = a.coverage_shifts ?? Infinity
        const bCov = b.coverage_shifts ?? Infinity
        if (aCov !== bCov) return aCov - bCov
        return b.streak_days - a.streak_days
    })
})

const topPriorityMaterial = computed(() => sortedMaterials.value[0])

// --- Drag-to-pan (horizontal scroll) ---
const tableScrollRef = ref<HTMLDivElement | null>(null)
let isPanning = false
let didPan = false        // true if the mouse moved enough to count as a pan
let panStartX = 0
let panStartScrollLeft = 0
const PAN_THRESHOLD = 5  // px

function onPanStart(e: MouseEvent) {
    if (!tableScrollRef.value) return
    isPanning = true
    didPan = false
    panStartX = e.pageX - tableScrollRef.value.offsetLeft
    panStartScrollLeft = tableScrollRef.value.scrollLeft
    tableScrollRef.value.style.cursor = 'grabbing'
    tableScrollRef.value.style.userSelect = 'none'
}

function onPanMove(e: MouseEvent) {
    if (!isPanning || !tableScrollRef.value) return
    const x = e.pageX - tableScrollRef.value.offsetLeft
    const delta = x - panStartX
    if (Math.abs(delta) > PAN_THRESHOLD) {
        didPan = true
        e.preventDefault()
        tableScrollRef.value.scrollLeft = panStartScrollLeft - delta
    }
}

function onPanEnd() {
    if (!tableScrollRef.value) return
    isPanning = false
    tableScrollRef.value.style.cursor = 'grab'
    tableScrollRef.value.style.userSelect = ''
    // Reset didPan after the click event fires (click fires after mouseup)
    setTimeout(() => { didPan = false }, 0)
}

// --- pills ---
const statusPill = (status: string) => {
    switch (status) {
        case 'CAUTION':
            return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'
        case 'SHORTAGE':
            return 'bg-red-50 text-red-700 ring-1 ring-red-200'
        default:
            return 'bg-gray-50 text-gray-700 ring-1 ring-gray-200'
    }
}

const severityPill = (severity: Severity) => {
    switch (severity) {
        case 'Line-Stop Risk':
            return 'bg-red-50 text-red-700 ring-1 ring-red-200'
        case 'High':
            return 'bg-orange-50 text-orange-700 ring-1 ring-orange-200'
        case 'Medium':
            return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'
        case 'Critical':
            return 'bg-red-50 text-red-700 ring-1 ring-red-200'
        default:
            return 'bg-gray-50 text-gray-700 ring-1 ring-gray-200'
    }
}

// --- Chart refs ---
const overdueCanvas = ref<HTMLCanvasElement | null>(null)
const recoveryCanvas = ref<HTMLCanvasElement | null>(null)
const distributionCanvas = ref<HTMLCanvasElement | null>(null)
const statusChangeCanvas = ref<HTMLCanvasElement | null>(null)
const fastestCanvas = ref<HTMLCanvasElement | null>(null)
const modalChartCanvas = ref<HTMLCanvasElement | null>(null)

// --- Chart instances (to destroy properly) ---
let overdueChart: Chart | null = null
let recoveryChart: Chart | null = null
let distributionChart: Chart | null = null
let statusChangeChart: Chart | null = null
let fastestChart: Chart | null = null
let modalChart: Chart | null = null

const COLORS = {
    red: '#ef4444',
    orange: '#f97316',
    amber: '#f59e0b',
    green: '#10b981',
    blue: '#3b82f6',
    purple: '#8b5cf6',
    gray: '#9ca3af',
}

const alpha = (hex: string, a: number) => {
    // hex like "#RRGGBB" -> "rgba(r,g,b,a)"
    const h = hex.replace('#', '')
    const r = parseInt(h.substring(0, 2), 16)
    const g = parseInt(h.substring(2, 4), 16)
    const b = parseInt(h.substring(4, 6), 16)
    return `rgba(${r},${g},${b},${a})`
}

const baseOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: { enabled: true },
    },
    scales: {
        x: { grid: { display: false }, ticks: { color: '#6b7280', font: { size: 10 } } },
        y: { grid: { color: 'rgba(17,24,39,0.06)' }, ticks: { color: '#6b7280', font: { size: 10 } } },
    },
} as const

function safeDestroy(c: Chart | null) {
    if (c) c.destroy()
}

function initCharts() {
    // Overdue line
    if (overdueCanvas.value) {
        safeDestroy(overdueChart)
        overdueChart = new Chart(overdueCanvas.value, {
            type: 'line',
            data: {
                labels: months6,
                datasets: [
                    {
                        label: 'Overdue Days',
                        data: overdueSeries,
                        tension: 0.35,
                        pointRadius: 3,
                        borderWidth: 2,
                        borderColor: COLORS.red,
                        backgroundColor: alpha(COLORS.red, 0.15),
                        pointBackgroundColor: COLORS.red,
                        fill: true,
                    },
                ],

            },
            options: {
                ...baseOptions,
                plugins: {
                    ...baseOptions.plugins,
                    title: { display: false },
                },
            },
        })
    }

    // Recovery line
    if (recoveryCanvas.value) {
        safeDestroy(recoveryChart)
        recoveryChart = new Chart(recoveryCanvas.value, {
            type: 'line',
            data: {
                labels: months6,
                datasets: [
                    {
                        label: 'Recovery Days',
                        data: recoverySeries,
                        tension: 0.35,
                        pointRadius: 3,
                        borderWidth: 2,
                        borderColor: COLORS.green,
                        backgroundColor: alpha(COLORS.green, 0.15),
                        pointBackgroundColor: COLORS.green,
                        fill: true,
                    },
                ],

            },
            options: {
                ...baseOptions,
            },
        })
    }

    // Distribution doughnut
    if (distributionCanvas.value) {
        safeDestroy(distributionChart)
        distributionChart = new Chart(distributionCanvas.value, {
            type: 'doughnut',
            data: {
                labels: ['Ok', 'Caution', 'Shortage'],
                datasets: [
                    {
                        data: [distribution.value.ok, distribution.value.caution, distribution.value.shortage],
                        borderWidth: 0,
                        backgroundColor: [COLORS.green, COLORS.amber, COLORS.red],
                        hoverOffset: 6,
                    },
                ],

            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%', // ✅ HERE (options), not dataset
                plugins: { legend: { position: 'bottom' } },
            },
        })

    }

    // Status change bar
    if (statusChangeCanvas.value) {
        safeDestroy(statusChangeChart)
        statusChangeChart = new Chart(statusChangeCanvas.value, {
            type: 'bar',
            data: {
                labels: statusChangesMonths,
                datasets: [
                    {
                        label: 'Avg Status Changes',
                        data: statusChanges,
                        borderRadius: 6,
                        backgroundColor: alpha(COLORS.purple, 0.75),
                        borderColor: COLORS.purple,
                        borderWidth: 1,
                    },
                ],

            },
            options: {
                ...baseOptions,
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#6b7280', font: { size: 10 } } },
                    y: { grid: { color: 'rgba(17,24,39,0.06)' }, ticks: { color: '#6b7280', font: { size: 10 } }, beginAtZero: true },
                },
            },
        })
    }

    // Fastest to critical horizontal bar
    if (fastestCanvas.value) {
        safeDestroy(fastestChart)
        fastestChart = new Chart(fastestCanvas.value, {
            type: 'bar',
            data: {
                labels: fastestToCritical.value.map(x => x.name),
                datasets: [
                    {
                        label: 'Runway (days)',
                        data: fastestToCritical.value.map(x => x.days),
                        borderRadius: 8,
                        backgroundColor: alpha(COLORS.orange, 0.75),
                        borderColor: COLORS.orange,
                        borderWidth: 1,
                    },
                ],

            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: true },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: COLORS.gray } },
                    y: { grid: { color: alpha('#111827', 0.06) }, ticks: { color: COLORS.gray } },
                },

            },
        })
    }
}

function initModalChart() {
    if (!modalChartCanvas.value) return
    safeDestroy(modalChart)

    // dummy modal series (per material could be different if you want)
    const labels = ['D-5', 'D-4', 'D-3', 'D-2', 'D-1', 'Today']
    const consumption = [10, 12, 9, 14, 13, 15]
    const runway = [6, 5.5, 5, 4.2, 3.5, 3]

    modalChart = new Chart(modalChartCanvas.value, {
        type: 'line',
        data: {
            labels,
            datasets: [
                { label: 'Consumption', data: consumption, tension: 0.35, pointRadius: 3 },
                { label: 'Runway (days)', data: runway, tension: 0.35, pointRadius: 3 },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#6b7280', font: { size: 10 } } },
                y: { grid: { color: 'rgba(17,24,39,0.06)' }, ticks: { color: '#6b7280', font: { size: 10 } }, beginAtZero: true },
            },
        },
    })
}
type InventoryStatus = 'OK' | 'Caution' | 'Shortage'

type InventoryRow = {
    id: string
    material: string
    sku: string
    category: string
    status: InventoryStatus
    overdueDays: number | null
    recoveryDays: number | null
    soh: number
}

const inventoryRows = ref<InventoryRow[]>([
    { id: 'i1', material: 'Steel Beam I-Type', sku: 'STL-BM-I01', category: 'Structural', status: 'OK', overdueDays: null, recoveryDays: null, soh: 450 },
    { id: 'i2', material: 'Hydraulic Cement', sku: 'HYD-CMT-001', category: 'Concrete', status: 'Shortage', overdueDays: 23, recoveryDays: 15, soh: 120 },
    { id: 'i3', material: 'Ceramic Tiles 60x60', sku: 'CER-TL-6060', category: 'Finishing', status: 'Caution', overdueDays: 8, recoveryDays: 10, soh: 680 },
    { id: 'i4', material: 'Copper Wire 10mm', sku: 'CPR-WR-010', category: 'Electrical', status: 'OK', overdueDays: null, recoveryDays: null, soh: 2400 },
    { id: 'i5', material: 'Glass Panel Tempered', sku: 'GLS-PNL-TMP', category: 'Glazing', status: 'Shortage', overdueDays: 45, recoveryDays: 30, soh: 85 },
    { id: 'i6', material: 'Aluminum Profile', sku: 'ALM-PRF-001', category: 'Framework', status: 'Caution', overdueDays: 12, recoveryDays: 8, soh: 320 },
    { id: 'i7', material: 'Waterproofing Membrane', sku: 'WTR-PRF-MBR', category: 'Protection', status: 'OK', overdueDays: null, recoveryDays: null, soh: 1500 },
    { id: 'i8', material: 'Granite Slab Premium', sku: 'GRN-SLB-PRM', category: 'Stone', status: 'Caution', overdueDays: 5, recoveryDays: 12, soh: 240 },
])

const inventoryStatusPill = (status: InventoryStatus) => {
    switch (status) {
        case 'OK':
            return 'bg-emerald-50 text-emerald-700'
        case 'Caution':
            return 'bg-orange-50 text-orange-700'
        case 'Shortage':
            return 'bg-red-50 text-red-700'
        default:
            return 'bg-gray-50 text-gray-700'
    }
}

const formatNumber = (n: number) => new Intl.NumberFormat('en-US').format(n)

const formatDate = (dateString: string | null | undefined): string => {
    if (!dateString) return '—'
    const date = new Date(dateString)
    if (isNaN(date.getTime())) return '—'
    return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}


// --- Modal logic ---
const showDetailModal = ref(false)
const selectedMaterial = ref<MaterialRow | null>(null)

async function openDetail(m: MaterialRow) {
    selectedMaterial.value = m
    showDetailModal.value = true
    await nextTick()
    initModalChart()
}

function closeDetail() {
    showDetailModal.value = false
    selectedMaterial.value = null
    safeDestroy(modalChart)
    modalChart = null
}

// Re-init modal chart if you switch material while modal open
watch(selectedMaterial, async () => {
    if (!showDetailModal.value) return
    await nextTick()
    initModalChart()
})

// Init / cleanup
onMounted(() => {
    fetchProblematicMaterials(1)
    fetchAverageConsumption()
    initCharts()
})

onBeforeUnmount(() => {
    safeDestroy(overdueChart)
    safeDestroy(recoveryChart)
    safeDestroy(distributionChart)
    safeDestroy(statusChangeChart)
    safeDestroy(fastestChart)
    safeDestroy(modalChart)
})
</script>
