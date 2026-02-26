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

                <!-- Global Filters -->
                <div v-if="showMobileFilters" class="mt-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap gap-4 items-end">
                        <!-- Location -->
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Location</label>
                            <div class="flex items-center gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1">
                                <button v-for="l in (['all', 'SUNTER_1', 'SUNTER_2'] as const)" :key="l"
                                    @click="globalFilter.location = l"
                                    :class="globalFilter.location === l ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                    class="rounded-md px-2.5 py-1 text-xs font-semibold transition-colors">
                                    {{ l === 'all' ? 'All' : l === 'SUNTER_1' ? 'Sunter 1' : 'Sunter 2' }}
                                </button>
                            </div>
                        </div>
                        <!-- Month -->
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Month</label>
                            <input type="month" :value="globalFilter.month ?? ''"
                                @change="globalFilter.month = ($event.target as HTMLInputElement).value || null"
                                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-300" />
                        </div>
                        <!-- Gentani -->
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Gentani</label>
                            <div class="flex items-center gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1">
                                <button v-for="g in (['all', 'GENTAN-I', 'NON_GENTAN-I'] as const)" :key="g"
                                    @click="globalFilter.gentani = g"
                                    :class="globalFilter.gentani === g ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                    class="rounded-md px-2.5 py-1 text-xs font-semibold transition-colors">
                                    {{ g === 'all' ? 'All' : g }}
                                </button>
                            </div>
                        </div>
                        <!-- Reset -->
                        <button
                            @click="() => { globalFilter.location = 'all'; globalFilter.month = null; globalFilter.gentani = 'all' }"
                            class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50">
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Main grid -->
                <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-12 lg:gap-6">
                    <!-- LEFT -->
                    <section class="lg:col-span-8 space-y-4 lg:space-y-6">
                        <!-- Problematic Materials -->
                        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div
                                class="flex flex-col gap-3 border-b border-gray-100 p-4 sm:flex-row sm:items-center sm:justify-between">
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
                                    <div
                                        class="flex items-center gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1">
                                        <button v-for="f in (['all', 'DAILY', 'WEEKLY', 'MONTHLY'] as const)" :key="f"
                                            @click="setUsageFilter(f)" :class="globalFilter.usage === f ? f === 'DAILY' ? 'bg-red-500 text-white' : f === 'WEEKLY' ? 'bg-amber-500 text-white' : f === 'MONTHLY' ? 'bg-emerald-500 text-white' : 'bg-white text-gray-800 shadow-sm'
                                                : 'text-gray-500 hover:text-gray-700'"
                                            class="rounded-md px-2.5 py-1 text-xs font-semibold transition-colors">
                                            {{ f === 'all' ? 'All' : f }}
                                        </button>
                                    </div>
                                    <!-- Status filter pills -->
                                    <div
                                        class="flex items-center gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1">
                                        <button v-for="f in (['all', 'SHORTAGE', 'CAUTION'] as const)" :key="f"
                                            @click="setStatusFilter(f)" :class="statusFilter === f
                                                ? f === 'SHORTAGE' ? 'bg-red-500 text-white' : f === 'CAUTION' ? 'bg-amber-500 text-white' : 'bg-white text-gray-800 shadow-sm'
                                                : 'text-gray-500 hover:text-gray-700'"
                                            class="rounded-md px-2.5 py-1 text-xs font-semibold transition-colors">
                                            {{ f === 'all' ? 'All' : f }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Unified responsive table (drag to pan) -->
                            <div ref="tableScrollRef" class="overflow-x-auto cursor-grab active:cursor-grabbing"
                                @mousedown="onPanStart" @mousemove="onPanMove" @mouseup="onPanEnd"
                                @mouseleave="onPanEnd">
                                <table class="min-w-full">
                                    <thead class="bg-gray-50">
                                        <tr
                                            class="text-left text-[10px] font-semibold uppercase tracking-wide text-gray-400">
                                            <th class="px-3 py-2 w-8">#</th>
                                            <th class="px-3 py-2">Material</th>
                                            <th class="px-3 py-2 hidden sm:table-cell">Status</th>
                                            <th class="px-3 py-2 hidden sm:table-cell">Severity</th>
                                            <th class="px-3 py-2 hidden md:table-cell whitespace-nowrap">Durability</th>
                                            <th class="px-3 py-2 whitespace-nowrap">Streak</th>
                                            <th class="px-3 py-2 hidden md:table-cell whitespace-nowrap">Last Updated
                                            </th>
                                            <th class="px-3 py-2 whitespace-nowrap">Est. GR</th>
                                            <th class="px-3 py-2 w-6"></th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-gray-100">
                                        <!-- Skeleton -->
                                        <template v-if="isLoadingMaterials">
                                            <tr v-for="n in 5" :key="'skel-' + n" class="animate-pulse">
                                                <td class="px-3 py-2.5">
                                                    <div class="h-3 w-5 rounded bg-gray-200"></div>
                                                </td>
                                                <td class="px-3 py-2.5">
                                                    <div class="h-3 w-36 rounded bg-gray-200"></div>
                                                    <div class="mt-1 h-2.5 w-20 rounded bg-gray-100"></div>
                                                </td>
                                                <td class="px-3 py-2.5 hidden sm:table-cell">
                                                    <div class="h-4 w-16 rounded-full bg-gray-200"></div>
                                                </td>
                                                <td class="px-3 py-2.5 hidden sm:table-cell">
                                                    <div class="h-4 w-20 rounded bg-gray-200"></div>
                                                </td>
                                                <td class="px-3 py-2.5 hidden md:table-cell">
                                                    <div class="h-3 w-14 rounded bg-gray-200"></div>
                                                </td>
                                                <td class="px-3 py-2.5">
                                                    <div class="h-3 w-10 rounded bg-gray-200"></div>
                                                </td>
                                                <td class="px-3 py-2.5 hidden md:table-cell">
                                                    <div class="h-3 w-18 rounded bg-gray-200"></div>
                                                </td>
                                                <td class="px-3 py-2.5">
                                                    <div class="h-6 w-28 rounded bg-gray-200"></div>
                                                </td>
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
                                            <td class="px-3  max-w-[160px] sm:max-w-none">
                                                <div class="truncate text-xs font-semibold text-gray-900">{{
                                                    m.description }}</div>
                                                <div class="text-[10px] font-mono text-gray-400">{{ m.material_number }}
                                                </div>
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
                                                <span v-if="m.coverage_shifts !== null"
                                                    class="text-xs font-semibold text-gray-800">
                                                    {{ m.coverage_shifts.toFixed(1) }}<span
                                                        class="text-[10px] font-normal text-gray-400"> sh</span>
                                                </span>
                                                <span v-else class="text-xs text-gray-300">—</span>
                                            </td>

                                            <!-- Streak -->
                                            <td class="px-3 py-2.5">
                                                <span class="text-xs font-semibold text-red-600">{{ m.streak_days
                                                }}d</span>
                                            </td>

                                            <!-- Last Updated (md+) -->
                                            <td class="px-3 py-2.5 hidden md:table-cell">
                                                <span class="text-xs text-gray-600">{{ formatDate(m.last_updated)
                                                }}</span>
                                            </td>

                                            <!-- Est. GR -->
                                            <td class="px-3 py-2.5" @click.stop>
                                                <div class="flex items-center gap-1">
                                                    <input type="text"
                                                        :ref="el => mountDatePicker(el as HTMLInputElement, m.id)"
                                                        :value="m.estimated_gr ?? ''" placeholder="dd/mm/yyyy"
                                                        class="w-28 rounded border border-gray-200 px-1.5 py-1 text-xs text-gray-700 focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-300" />
                                                    <button v-if="m.estimated_gr" @click="clearTableDate(m)"
                                                        class="text-gray-300 hover:text-red-400 transition-colors"
                                                        title="Clear date">
                                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none">
                                                            <path d="M6 6l12 12M18 6L6 18" stroke="currentColor"
                                                                stroke-width="2.5" stroke-linecap="round" />
                                                        </svg>
                                                    </button>
                                                    <span v-if="grSaveState[m.id] === 'saving'"
                                                        class="text-[10px] text-gray-400">…</span>
                                                    <span v-else-if="grSaveState[m.id] === 'saved'"
                                                        class="text-[10px] text-emerald-600">✓</span>
                                                    <span v-else-if="grSaveState[m.id] === 'error'"
                                                        class="text-[10px] text-red-500">!</span>
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
                                        {{ (pagination.current_page - 1) * pagination.per_page + 1 }}–{{
                                            Math.min(pagination.current_page *
                                                pagination.per_page, pagination.total) }}
                                    </span>
                                    <span class="hidden sm:inline"> of {{ pagination.total }}</span>
                                </p>
                                <div class="flex items-center gap-1">
                                    <button @click="goToPage(pagination.current_page - 1)"
                                        :disabled="pagination.current_page === 1"
                                        class="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                                        ‹
                                    </button>
                                    <template v-for="p in pageWindow" :key="String(p)">
                                        <span v-if="p === '…'" class="px-1 text-xs text-gray-400">…</span>
                                        <button v-else @click="goToPage(p as number)"
                                            :class="p === pagination.current_page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                                            class="rounded-lg border px-2.5 py-1.5 text-xs font-semibold">
                                            {{ p }}
                                        </button>
                                    </template>
                                    <button @click="goToPage(pagination.current_page + 1)"
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
                                <!-- <OverdueTrendChart :data="dashboardData.barChart" :filters="globalFilter" /> -->
                                <RecoveryTrendChart :data="(dashboardData.recovery as any)?.trendData"
                                    :filters="globalFilter" />
                            </div>

                            <!-- Recommendation -->
                            <div class="lg:col-span-5">
                                <SystemRecommendationPanel :shortage="(dashboardData as any).shortage"
                                    :caution="(dashboardData as any).caution" :updated-at="updatedAt"
                                    @view-material="onViewMaterialFromPanel" />
                            </div>
                        </div>
                    </section>

                    <!-- RIGHT -->
                    <aside class="lg:col-span-4 space-y-4 lg:space-y-6">
                        <DistributionDonutChart :data="(dashboardData as any).barChart" :filters="globalFilter" />
                        <StatusChangeContent :filters="globalFilter" size="compact" :hide-refresh="true" />
                        <FastestToCriticalChart :data="(dashboardData as any).shortage?.leaderboard"
                            :filters="globalFilter" />
                    </aside>
                </div>

                <!-- Leaderboards (Below main view) -->
                <section class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-12 lg:gap-6">
                    <div class="lg:col-span-6 rounded-xl border border-gray-200 overflow-hidden">
                        <ShortageOverdueLeaderboard :filters="globalFilter" :hide-refresh="true" />
                    </div>
                    <div class="lg:col-span-6 rounded-xl border border-gray-200 overflow-hidden">
                        <CautionOverdueLeaderboard :filters="globalFilter" :hide-refresh="true" />
                    </div>
                </section>

                <!-- Material Inventory Overview -->
                <MaterialInventoryOverview :filters="globalFilter" />

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
                                        {{ selectedMaterial?.severity === 'Line-Stop' ? 'Escalate' : 'Monitor' }}
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
                                        <input v-if="selectedMaterial" type="text"
                                            :ref="el => mountModalDatePicker(el as HTMLInputElement)"
                                            :value="selectedMaterial.estimated_gr ?? ''" placeholder="dd/mm/yyyy"
                                            class="rounded border border-gray-200 px-2 py-1 text-sm text-gray-700 focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-300" />
                                        <button v-if="selectedMaterial?.estimated_gr" @click="clearModalDate()"
                                            class="text-gray-300 hover:text-red-400 transition-colors"
                                            title="Clear date">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                                <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.5"
                                                    stroke-linecap="round" />
                                            </svg>
                                        </button>
                                        <span v-if="selectedMaterial && grSaveState[selectedMaterial.id] === 'saving'"
                                            class="text-xs text-gray-400">Saving…</span>
                                        <span
                                            v-else-if="selectedMaterial && grSaveState[selectedMaterial.id] === 'saved'"
                                            class="text-xs text-emerald-600">Saved ✓</span>
                                        <span
                                            v-else-if="selectedMaterial && grSaveState[selectedMaterial.id] === 'error'"
                                            class="text-xs text-red-500">Error !</span>
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
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.min.css'
import { buildFilterParams } from '@/utils/filterParams'
import OverdueTrendChart from '@/Components/OverdueTrendChart.vue'
import RecoveryTrendChart from '@/Components/RecoveryTrendChart.vue'
import DistributionDonutChart from '@/Components/DistributionDonutChart.vue'
import FastestToCriticalChart from '@/Components/FastestToCriticalChart.vue'
import SystemRecommendationPanel from '@/Components/SystemRecommendationPanel.vue'
import CautionOverdueLeaderboard from '@/Components/CautionOverdueLeaderboard.vue'
import ShortageOverdueLeaderboard from '@/Components/ShortageOverdueLeaderboard.vue'
import StatusChangeContent from '@/Components/StatusChangeContent.vue'
import MaterialInventoryOverview from '@/Components/MaterialInventoryOverview.vue'

import {
    Chart,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Tooltip,
    Legend,
    LineController,
    Filler,
} from 'chart.js'

Chart.register(CategoryScale, LinearScale, PointElement, LineElement, LineController, Tooltip, Legend, Filler)

declare const window: Window & { axios: import('axios').AxiosInstance }

const error = ref<string | null>(null)
const showMobileFilters = ref(false)
const updatedAt = ref('-')
const isLoadingMaterials = ref(false)

type Status = 'CAUTION' | 'SHORTAGE'
type Severity = 'Low' | 'Medium' | 'High' | 'Line-Stop' | 'Critical'

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
        const { data } = await window.axios.patch(`/warehouse-monitoring/api/problematic/${id}`, {
            estimated_gr: date || null,
        })
        const mat = materials.value.find(m => m.id === id)
        if (mat) mat.estimated_gr = data.estimated_gr ?? null
        grSaveState.value[id] = 'saved'
        setTimeout(() => { grSaveState.value[id] = 'idle' }, 2000)
    } catch {
        grSaveState.value[id] = 'error'
        setTimeout(() => { grSaveState.value[id] = 'idle' }, 3000)
    }
}

// Flatpickr instances keyed by material id (table rows only)
const fpInstances: Record<number, flatpickr.Instance> = {}

const fpConfig = (onChange: (iso: string | null) => void): flatpickr.Options.Options => ({
    dateFormat: 'Y-m-d',
    altInput: true,
    altFormat: 'd/m/Y',
    allowInput: true,
    onChange: ([date]) => onChange(date ? date.toISOString().slice(0, 10) : null),
})

function mountDatePicker(el: HTMLInputElement | null, id: number) {
    if (!el) {
        fpInstances[id]?.destroy()
        delete fpInstances[id]
        return
    }
    if (fpInstances[id]) return
    fpInstances[id] = flatpickr(el, fpConfig(iso => saveEstimatedGR(id, iso)))
    if (el.value) fpInstances[id].setDate(el.value, false)
}

let modalFpInstance: flatpickr.Instance | null = null

function mountModalDatePicker(el: HTMLInputElement | null) {
    if (!el) {
        modalFpInstance?.destroy()
        modalFpInstance = null
        return
    }
    if (modalFpInstance) return
    modalFpInstance = flatpickr(el, fpConfig(iso => {
        if (selectedMaterial.value) saveEstimatedGR(selectedMaterial.value.id, iso)
    }))
    if (el.value) modalFpInstance.setDate(el.value, false)
}

function clearModalDate() {
    if (selectedMaterial.value) saveEstimatedGR(selectedMaterial.value.id, null)
    modalFpInstance?.clear()
}

function clearTableDate(m: MaterialRow) {
    saveEstimatedGR(m.id, null)
    fpInstances[m.id]?.clear()
}

// --- Global filter ---
type UsageFilter = 'all' | 'DAILY' | 'WEEKLY' | 'MONTHLY'
type StatusFilter = 'all' | 'SHORTAGE' | 'CAUTION'

const globalFilter = reactive({
    usage: 'all' as UsageFilter,
    location: 'all' as 'all' | 'SUNTER_1' | 'SUNTER_2',
    month: null as string | null,
    gentani: 'all' as 'all' | 'GENTAN-I' | 'NON_GENTAN-I',
})

const statusFilter = ref<StatusFilter>('all')
const pagination = ref({ current_page: 1, per_page: 10, total: 0, last_page: 1 })

// --- Dashboard data (fetched on filter change) ---
const dashboardData = ref<Record<string, unknown>>({})

async function fetchDashboardData() {
    try {
        const params = buildFilterParams(globalFilter as unknown as Record<string, unknown>)
        const res = await fetch(`/warehouse-monitoring/api/dashboard?${params}`)
        if (!res.ok) throw new Error(`HTTP ${res.status}`)
        const json = await res.json()
        // API wraps payload in { success, data: {...}, filters, timestamp }
        Object.assign(dashboardData.value, json.data ?? json)
    } catch {
        // dashboard is non-critical
    }
}

async function fetchProblematicMaterials(page = 1) {
    isLoadingMaterials.value = true
    error.value = null
    try {
        const params = new URLSearchParams({ page: String(page), per_page: String(pagination.value.per_page) })
        if (statusFilter.value !== 'all') params.set('status', statusFilter.value)
        if (globalFilter.usage !== 'all') params.set('usage', globalFilter.usage)
        if (globalFilter.location !== 'all') params.set('location', globalFilter.location)
        if (globalFilter.month) params.set('month', globalFilter.month)
        if (globalFilter.gentani !== 'all') params.set('gentani', globalFilter.gentani)
        const res = await fetch(`/warehouse-monitoring/api/problematic?${params}`)
        if (!res.ok) throw new Error(`HTTP ${res.status}`)
        const json = await res.json()
        materials.value = json.data ?? []
        if (json.pagination) pagination.value = json.pagination
        if (materials.value.length > 0 && materials.value[0].last_updated) {
            updatedAt.value = formatDate(materials.value[0].last_updated)
        }
    } catch {
        error.value = 'Failed to load problematic materials. Please refresh the page.'
    } finally {
        isLoadingMaterials.value = false
    }
}

function setUsageFilter(f: UsageFilter) {
    globalFilter.usage = f
}

function setStatusFilter(f: StatusFilter) {
    statusFilter.value = f
    fetchProblematicMaterials(1)
}

function goToPage(page: number) {
    if (page < 1 || page > pagination.value.last_page) return
    fetchProblematicMaterials(page)
}

const pageWindow = computed(() => {
    const { current_page, last_page } = pagination.value
    const delta = 2
    const pages: (number | '…')[] = []
    const start = Math.max(1, current_page - delta)
    const end = Math.min(last_page, current_page + delta)
    if (start > 1) { pages.push(1); if (start > 2) pages.push('…') }
    for (let p = start; p <= end; p++) pages.push(p)
    if (end < last_page) { if (end < last_page - 1) pages.push('…'); pages.push(last_page) }
    return pages
})

// Deep watch: any globalFilter change triggers re-fetch of everything
watch(globalFilter, () => {
    fetchDashboardData()
    fetchProblematicMaterials(1)
}, { deep: true })

// Sort materials: Line-Stop Risk first, then by coverage_shifts asc, then streak_days desc
const severityRank = (s: Severity) => (s === 'Line-Stop' ? 3 : s === 'High' ? 2 : s === 'Medium' ? 1 : 0)

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

// --- Drag-to-pan ---
const tableScrollRef = ref<HTMLDivElement | null>(null)
let isPanning = false
let didPan = false
let panStartX = 0
let panStartScrollLeft = 0
const PAN_THRESHOLD = 5

function onPanStart(e: MouseEvent) {
    if (!tableScrollRef.value) return
    const tag = (e.target as HTMLElement).tagName
    if (tag === 'INPUT' || tag === 'BUTTON' || tag === 'SELECT' || tag === 'TEXTAREA') return
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
    setTimeout(() => { didPan = false }, 0)
}

// --- pills ---
const statusPill = (status: string) => {
    switch (status) {
        case 'CAUTION': return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'
        case 'SHORTAGE': return 'bg-red-50 text-red-700 ring-1 ring-red-200'
        default: return 'bg-gray-50 text-gray-700 ring-1 ring-gray-200'
    }
}

const severityPill = (severity: Severity) => {
    switch (severity) {
        case 'Line-Stop': return 'bg-red-50 text-red-700 ring-1 ring-red-200'
        case 'High': return 'bg-orange-50 text-orange-700 ring-1 ring-orange-200'
        case 'Medium': return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'
        case 'Critical': return 'bg-red-50 text-red-700 ring-1 ring-red-200'
        default: return 'bg-gray-50 text-gray-700 ring-1 ring-gray-200'
    }
}

// --- Modal ---
const showDetailModal = ref(false)
const selectedMaterial = ref<MaterialRow | null>(null)
let modalChart: Chart | null = null
const modalChartCanvas = ref<HTMLCanvasElement | null>(null)

function initModalChart() {
    if (!modalChartCanvas.value) return
    if (modalChart) { modalChart.destroy(); modalChart = null }

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

async function openDetail(m: MaterialRow) {
    selectedMaterial.value = m
    showDetailModal.value = true
    await nextTick()
    initModalChart()
}

function closeDetail() {
    showDetailModal.value = false
    selectedMaterial.value = null
    if (modalChart) { modalChart.destroy(); modalChart = null }
}

function onViewMaterialFromPanel(material: any) {
    if (!material) return
    const mat = materials.value.find(m => m.material_number === (material.material_number ?? material.material))
    if (mat) openDetail(mat)
}

watch(selectedMaterial, async () => {
    if (!showDetailModal.value) return
    await nextTick()
    initModalChart()
})

const formatDate = (dateString: string | null | undefined): string => {
    if (!dateString) return '—'
    const date = new Date(dateString)
    if (isNaN(date.getTime())) return '—'
    return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

onMounted(() => {
    fetchProblematicMaterials(1)
    fetchDashboardData()
})

onBeforeUnmount(() => {
    if (modalChart) { modalChart.destroy(); modalChart = null }
    Object.keys(fpInstances).forEach(id => { fpInstances[Number(id)]?.destroy() })
    modalFpInstance?.destroy()
})
</script>
