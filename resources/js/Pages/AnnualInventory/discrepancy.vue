<template>
    <MainAppLayout title="Annual Inventory Discrepancy" subtitle="Manage discrepancy for annual inventory">
        <div class="min-h-screen bg-gray-100 p-2 md:p-8 font-sans">

            <div v-if="error"
                class="mb-4 md:mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex justify-between items-center text-sm md:text-base">
                <span>{{ error }}</span>
                <button @click="error = null" class="text-red-600 hover:text-red-800 font-bold">×</button>
            </div>

            <div v-if="successMessage"
                class="mb-4 md:mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex justify-between items-center text-sm md:text-base">
                <span>{{ successMessage }}</span>
                <button @click="successMessage = null" class="text-green-600 hover:text-green-800 font-bold">×</button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border-b border-gray-200 mb-4 md:mb-6">
                <div
                    class="p-4 md:p-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div class="flex items-center gap-3 md:gap-4 w-full md:w-auto">
                        <button @click="goBack"
                            class="p-2 hover:bg-gray-100 rounded-lg border border-gray-200 transition-colors flex-shrink-0">
                            <ArrowLeft class="w-5 h-5 text-gray-600" />
                        </button>
                        <div class="overflow-hidden">
                            <h1 class="text-lg md:text-2xl font-bold text-gray-800 truncate">Inventory Discrepancy</h1>
                            <p class="text-gray-500 text-xs md:text-sm mt-1 truncate">
                                PID: {{ pidData?.pid || 'All' }} • {{ pidData?.location || 'All Locations' }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:flex gap-2 w-full md:w-auto">
                        <button @click="downloadTemplate"
                            class="px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center justify-center gap-2 text-xs md:text-sm">
                            <Download class="w-4 h-4" />
                            <span class="hidden sm:inline">Template</span>
                            <span class="sm:hidden">Templ.</span>
                        </button>
                        <button @click="downloadExcel"
                            class="px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors flex items-center justify-center gap-2 text-xs md:text-sm">
                            <Download class="w-4 h-4" />
                            <span class="hidden sm:inline">Download</span>
                            <span class="sm:hidden">Excel</span>
                        </button>
                        <label
                            class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 cursor-pointer transition-colors flex items-center justify-center gap-2 text-xs md:text-sm"
                            :class="{ 'opacity-50 cursor-not-allowed': uploading }">
                            <Upload class="w-4 h-4" />
                            <input type="file" @change="handleFileUpload" accept=".xlsx,.xls" class="hidden"
                                :disabled="uploading" />
                            {{ uploading ? '...' : 'Upload' }}
                        </label>
                        <button @click="saveAllChanges" :disabled="!hasChanges || saving"
                            class="col-span-2 sm:col-span-1 px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 text-xs md:text-sm">
                            <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
                            <Save v-else class="w-4 h-4" />
                            {{ saving ? 'Saving...' : 'Save Changes' }}
                        </button>
                        <button @click="fetchData" :disabled="loading"
                            class="col-span-2 sm:col-span-1 px-3 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 text-xs md:text-sm">
                            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
                            <span class="hidden sm:inline">Refresh</span>
                        </button>
                        <button @click="recalculateDiscrepancy" :disabled="isRecalculating"
                            class="col-span-2 sm:col-span-1 px-3 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 text-xs md:text-sm">
                            <Loader2 v-if="isRecalculating" class="w-4 h-4 animate-spin" />
                            <IterationCcw v-else class="w-4 h-4" />
                            <span class="hidden sm:inline">{{ isRecalculating ? 'Recalculating...' : 'Recalculate' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Counting Progress Bar -->
                <div class="px-4 md:px-6 py-3 border-b border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <h3 class="text-xs md:text-sm font-bold text-gray-700 uppercase tracking-wide">Counting
                                Progress</h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs md:text-sm font-bold"
                                :class="statistics.countedPercent === 100 ? 'text-green-600' : 'text-blue-600'">
                                {{ statistics.countedItems }} / {{ statistics.progressTotal }}
                            </span>
                            <span class="text-xs font-bold px-1.5 py-0.5 rounded"
                                :class="statistics.countedPercent === 100 ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'">
                                {{ statistics.countedPercent }}%
                            </span>
                        </div>
                    </div>
                    <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500 ease-out"
                            :class="statistics.countedPercent === 100 ? 'bg-green-500' : 'bg-blue-500'"
                            :style="{ width: `${statistics.countedPercent}%` }">
                        </div>
                    </div>
                    <div class="flex justify-between mt-1.5 text-[10px] md:text-xs text-gray-500">
                        <span>Counted: <span class="font-semibold text-gray-700">{{ statistics.countedItems
                        }}</span></span>
                        <span>Pending: <span class="font-semibold text-gray-700">{{ statistics.pendingItems
                        }}</span></span>
                    </div>
                </div>

                <div
                    class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-200 bg-gray-50/50 rounded-b-xl">
                    <div class="p-4">
                        <div class="mb-3 flex items-center gap-2">
                            <div class="p-1.5 bg-indigo-100 rounded-md text-indigo-600">
                                <Package class="w-4 h-4" />
                            </div>
                            <h3 class="text-xs md:text-sm font-bold text-gray-700 uppercase tracking-wide">Operational
                                Impact</h3>
                            <div class="relative group">
                                <button type="button"
                                    class="w-4 h-4 rounded-full border border-gray-300 text-gray-500 flex items-center justify-center text-[10px]
                                    hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    aria-label="Operational impact info">
                                    ?
                                </button>
                                <div class="pointer-events-none absolute left-1/2 top-full z-50 mt-2 w-64 -translate-x-1/2
                                    rounded-lg bg-gray-900 px-3 py-2 text-xs text-white shadow-lg
                                    opacity-0 group-hover:opacity-100 transition-opacity">
                                    Shows <span class="font-semibold">counted-only</span> items. Counts how many items
                                    have Final Gap &gt; 0 (surplus) or &lt; 0 (shortage).
                                    <div class="absolute left-1/2 top-0 -translate-x-1/2 -translate-y-1/2
                                        h-2 w-2 rotate-45 bg-gray-900"></div>
                                </div>
                            </div>
                            <span class="ml-auto text-[10px] md:text-xs text-gray-500">Total:
                                {{ statistics.totalItems }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3 md:gap-4">
                            <div class="bg-blue-50 p-2 md:p-3 rounded-lg shadow-sm border border-blue-100">
                                <div class="flex items-center gap-1 mb-1">
                                    <span class="text-xs md:text-sm text-blue-600 font-bold">Discrepancy Items
                                        (+)</span>
                                    <div class="relative group">
                                        <button type="button" class="w-3.5 h-3.5 rounded-full border border-blue-300 text-blue-500 flex items-center justify-center text-[9px]
                                            hover:bg-blue-100 hover:text-blue-700 focus:outline-none"
                                            aria-label="Surplus items info">
                                            ?
                                        </button>
                                        <div class="pointer-events-none absolute left-1/2 top-full z-50 mt-2 w-56 -translate-x-1/2
                                            rounded-lg bg-gray-900 px-3 py-2 text-xs text-white shadow-lg
                                            opacity-0 group-hover:opacity-100 transition-opacity">
                                            Count of items where <span class="font-semibold">Final Gap &gt;
                                                0</span>.<br>
                                            Percentage = Surplus Count / Total Items.
                                            <div class="absolute left-1/2 top-0 -translate-x-1/2 -translate-y-1/2
                                                h-2 w-2 rotate-45 bg-gray-900"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-baseline gap-1 sm:gap-2">
                                    <span class="text-base md:text-lg font-bold text-blue-800">
                                        {{ statistics.surplusCount }}</span>
                                    <span
                                        class="text-[10px] font-semibold text-blue-600 bg-blue-100 px-1.5 py-0.5 rounded w-fit">
                                        {{ statistics.surplusCountPercent }}%
                                    </span>
                                </div>
                            </div>
                            <div class="bg-red-50 p-2 md:p-3 rounded-lg shadow-sm border border-red-100">
                                <div class="flex items-center gap-1 mb-1">
                                    <span class="text-xs md:text-sm text-red-600 font-bold">Discrepancy Items (-)</span>
                                    <div class="relative group">
                                        <button type="button" class="w-3.5 h-3.5 rounded-full border border-red-300 text-red-500 flex items-center justify-center text-[9px]
                                            hover:bg-red-100 hover:text-red-700 focus:outline-none"
                                            aria-label="Shortage items info">
                                            ?
                                        </button>
                                        <div class="pointer-events-none absolute left-1/2 top-full z-50 mt-2 w-56 -translate-x-1/2
                                            rounded-lg bg-gray-900 px-3 py-2 text-xs text-white shadow-lg
                                            opacity-0 group-hover:opacity-100 transition-opacity">
                                            Count of items where <span class="font-semibold">Final Gap &lt;
                                                0</span>.<br>
                                            Percentage = Shortage Count / Total Items.
                                            <div class="absolute left-1/2 top-0 -translate-x-1/2 -translate-y-1/2
                                                h-2 w-2 rotate-45 bg-gray-900"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-baseline gap-1 sm:gap-2">
                                    <span class="text-base md:text-lg font-bold text-red-800">{{
                                        statistics.shortageCount }}</span>
                                    <span
                                        class="text-[10px] font-semibold text-red-600 bg-red-100 px-1.5 py-0.5 rounded w-fit">
                                        {{ statistics.shortageCountPercent }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4">
                        <div class="mb-3 flex items-center gap-2">
                            <div class="p-1.5 bg-blue-100 rounded-md text-blue-600">
                                <DollarSign class="w-4 h-4" />
                            </div>
                            <h3 class="text-xs md:text-sm font-bold text-gray-700 uppercase tracking-wide">Financial
                                Impact</h3>
                            <div class="relative group">
                                <button type="button"
                                    class="w-4 h-4 rounded-full border border-gray-300 text-gray-500 flex items-center justify-center text-[10px]
                                    hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    aria-label="Financial impact info">
                                    ?
                                </button>
                                <div class="pointer-events-none absolute left-1/2 top-full z-50 mt-2 w-64 -translate-x-1/2
                                    rounded-lg bg-gray-900 px-3 py-2 text-xs text-white shadow-lg
                                    opacity-0 group-hover:opacity-100 transition-opacity">
                                    Shows <span class="font-semibold">counted-only</span> items.<br>
                                    Amounts = Final Gap x Price per item, summed by surplus/shortage.
                                    <div class="absolute left-1/2 top-0 -translate-x-1/2 -translate-y-1/2
                                        h-2 w-2 rotate-45 bg-gray-900"></div>
                                </div>
                            </div>
                            <span class="ml-auto text-[10px] md:text-xs text-gray-500">Match: {{
                                statistics.matchCountPercent }}%</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3 md:gap-4">
                            <!-- Financial Impact -->
                            <div class="bg-white p-2 md:p-3 rounded-lg shadow-sm border border-blue-100">
                                <div class="flex items-center gap-1 mb-1">
                                    <span class="text-xs md:text-sm text-blue-600 font-bold">Discrepancy Amount
                                        (+)</span>
                                    <div class="relative group">
                                        <button type="button" class="w-3.5 h-3.5 rounded-full border border-blue-300 text-blue-500 flex items-center justify-center text-[9px]
                                            hover:bg-blue-100 hover:text-blue-700 focus:outline-none"
                                            aria-label="Surplus amount info">
                                            ?
                                        </button>
                                        <div class="pointer-events-none absolute left-1/2 top-full z-50 mt-2 w-64 -translate-x-1/2
                                            rounded-lg bg-gray-900 px-3 py-2 text-xs text-white shadow-lg
                                            opacity-0 group-hover:opacity-100 transition-opacity">
                                            <span class="font-semibold">Sum of (Final Gap x Price)</span> for items
                                            where Final Gap &gt; 0.<br>
                                            Percentage = Surplus Amount / System Amount.
                                            <div class="absolute left-1/2 top-0 -translate-x-1/2 -translate-y-1/2
                                                h-2 w-2 rotate-45 bg-gray-900"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col xl:flex-row xl:items-baseline gap-1">
                                    <span class="text-sm md:text-lg font-bold text-gray-800 break-all">{{
                                        formatCurrency(statistics.surplusAmount) }}
                                    </span>
                                    <span
                                        class="text-[10px] font-semibold text-blue-600 bg-blue-100 px-1.5 py-0.5 rounded w-fit">
                                        {{ statistics.surplusAmountPercent }}%
                                    </span>
                                </div>
                            </div>
                            <div class="bg-white p-2 md:p-3 rounded-lg shadow-sm border border-red-100">
                                <div class="flex items-center gap-1 mb-1">
                                    <span class="text-xs md:text-sm text-red-600 font-bold">Discrepancy Amount
                                        (-)</span>
                                    <div class="relative group">
                                        <button type="button" class="w-3.5 h-3.5 rounded-full border border-red-300 text-red-500 flex items-center justify-center text-[9px]
                                            hover:bg-red-100 hover:text-red-700 focus:outline-none"
                                            aria-label="Shortage amount info">
                                            ?
                                        </button>
                                        <div class="pointer-events-none absolute left-1/2 top-full z-50 mt-2 w-64 -translate-x-1/2
                                            rounded-lg bg-gray-900 px-3 py-2 text-xs text-white shadow-lg
                                            opacity-0 group-hover:opacity-100 transition-opacity">
                                            <span class="font-semibold">Sum of |Final Gap x Price|</span> for items
                                            where Final Gap &lt; 0.<br>
                                            Percentage = Shortage Amount / System Amount.
                                            <div class="absolute left-1/2 top-0 -translate-x-1/2 -translate-y-1/2
                                                h-2 w-2 rotate-45 bg-gray-900"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col xl:flex-row xl:items-baseline gap-1">
                                    <span class="text-sm md:text-lg font-bold text-gray-800 break-all">{{
                                        formatCurrency(statistics.shortageAmount) }}
                                    </span>
                                    <span
                                        class="text-[10px] font-semibold text-red-600 bg-red-100 px-1.5 py-0.5 rounded w-fit">
                                        {{ statistics.shortageAmountPercent }}%
                                    </span>
                                </div>
                            </div>
                            <div class="bg-white p-2 md:p-3 rounded-lg shadow-sm border"
                                :class="statistics.nettDiscrepancyAmount >= 0 ? 'border-blue-100' : 'border-red-100'">
                                <div class="flex items-center gap-1 mb-1">
                                    <span class="text-xs md:text-sm text-gray-600 font-bold">Nett Discrepancy</span>
                                    <div class="relative group">
                                        <button type="button" class="w-3.5 h-3.5 rounded-full border border-gray-300 text-gray-500 flex items-center justify-center text-[9px]
                                            hover:bg-gray-100 hover:text-gray-700 focus:outline-none"
                                            aria-label="Nett discrepancy info">
                                            ?
                                        </button>
                                        <div class="pointer-events-none absolute left-1/2 top-full z-50 mt-2 w-64 -translate-x-1/2
                                            rounded-lg bg-gray-900 px-3 py-2 text-xs text-white shadow-lg
                                            opacity-0 group-hover:opacity-100 transition-opacity">
                                            <span class="font-semibold">Sum of all (Final Gap x Price)</span>,
                                            signed.<br>
                                            Positive = net surplus, Negative = net shortage.
                                            <div class="absolute left-1/2 top-0 -translate-x-1/2 -translate-y-1/2
                                                h-2 w-2 rotate-45 bg-gray-900"></div>
                                        </div>
                                    </div>
                                </div>
                                <span class="text-sm md:text-lg font-bold break-all"
                                    :class="statistics.nettDiscrepancyAmount > 0 ? 'text-blue-700' : statistics.nettDiscrepancyAmount < 0 ? 'text-red-700' : 'text-gray-800'">
                                    {{ formatCurrency(statistics.nettDiscrepancyAmount) }}
                                </span>
                                <span class="text-[10px] font-semibold  px-1.5 py-0.5 rounded w-fit"
                                    :class="statistics.overallDiscrepancyImpactPercent === 100 ? 'bg-green-100 text-green-700' : 'bg-gray-100 '">
                                    {{ statistics.overallDiscrepancyImpactPercent }}%
                                </span>
                            </div>
                            <div class="bg-white p-2 md:p-3 rounded-lg shadow-sm border border-gray-200">
                                <div class="flex items-center gap-1 mb-1">
                                    <span class="text-xs md:text-sm text-gray-600 font-bold">System Amount</span>
                                    <div class="relative group">
                                        <button type="button" class="w-3.5 h-3.5 rounded-full border border-gray-300 text-gray-500 flex items-center justify-center text-[9px]
                                            hover:bg-gray-100 hover:text-gray-700 focus:outline-none"
                                            aria-label="System amount info">
                                            ?
                                        </button>
                                        <div class="pointer-events-none absolute left-1/2 top-full z-50 mt-2 w-56 -translate-x-1/2
                                            rounded-lg bg-gray-900 px-3 py-2 text-xs text-white shadow-lg
                                            opacity-0 group-hover:opacity-100 transition-opacity">
                                            <span class="font-semibold">Sum of (SOH x Price)</span> across all counted
                                            items.
                                            <div class="absolute left-1/2 top-0 -translate-x-1/2 -translate-y-1/2
                                                h-2 w-2 rotate-45 bg-gray-900"></div>
                                        </div>
                                    </div>
                                </div>
                                <span class="text-sm md:text-lg font-bold text-gray-800 break-all">
                                    {{ formatCurrency(statistics.systemAmount) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="relative w-full lg:max-w-md my-1">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input v-model="searchQuery" type="text" placeholder="Search material number or description..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm" />
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-4 p-4">
                <div class="flex flex-col lg:flex-row items-start lg:items-center gap-4">
                    <div class="grid grid-cols-2 md:flex md:flex-wrap items-center gap-3 w-full lg:w-auto">
                        <div class="flex flex-col md:flex-row md:items-center gap-1 md:gap-2 col-span-1">
                            <label class="text-xs md:text-sm font-semibold text-gray-700">PID:</label>
                            <select v-model="selectedPID" @change="handleFilterChange"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 px-3 py-2 text-sm">
                                <option value="">All</option>
                                <option v-for="pid in pids" :key="pid.id" :value="pid.id">{{ pid.pid }}</option>
                            </select>
                        </div>

                        <div class="flex flex-col md:flex-row md:items-center gap-1 md:gap-2 col-span-1">
                            <label class="text-xs md:text-sm font-semibold text-gray-700">Type:</label>
                            <select v-model="selectedDiscrepancy" @change="handleFilterChange"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 px-3 py-2 text-sm">
                                <option value="">All</option>
                                <option value="surplus">Surplus</option>
                                <option value="deficit">Deficit</option>
                                <option value="match">Match</option>
                            </select>
                        </div>
                        <div class="flex flex-col md:flex-row md:items-center gap-1 md:gap-2 col-span-2 sm:col-span-1">
                            <label class="text-xs md:text-sm font-semibold text-gray-700">Loc:</label>
                            <select v-model="selectedLocation" @change="handleFilterChange"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 px-3 py-2 text-sm">
                                <option value="">All</option>
                                <option v-for="loc in locations" :key="loc" :value="loc">{{ loc }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="hidden lg:block ml-auto text-sm text-gray-600">
                        Showing {{ items.length }} of {{ pagination.total }} items
                    </div>
                </div>
            </div>

            <div class="md:hidden space-y-4">
                <div v-if="loading" class="text-center py-8 bg-white rounded-lg shadow">
                    <Loader2 class="w-8 h-8 mx-auto text-blue-500 animate-spin mb-2" />
                    <p class="text-gray-500 text-sm">Loading data...</p>
                </div>
                <div v-else-if="items.length === 0"
                    class="text-center py-8 bg-white rounded-lg shadow text-gray-500 text-sm">
                    No items found.
                </div>

                <div v-else v-for="item in items" :key="item.id"
                    class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden"
                    :class="{ 'ring-2 ring-yellow-400': item._dirty }">

                    <div class="bg-gray-50 p-3 border-b border-gray-100 flex justify-between items-start">
                        <div>
                            <div class="font-bold text-gray-900 text-sm">{{ item.material_number }}</div>
                            <div class="text-xs text-gray-600 mt-1 line-clamp-2">{{ item.description }}</div>
                        </div>
                        <div class="flex flex-col items-end">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-200 text-gray-700 mb-1">
                                {{ item.rack_address || 'No Rack' }}
                            </span>
                            <span class="text-xs font-medium text-gray-500">{{ item.unit_of_measure }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 divide-x divide-gray-100 border-b border-gray-100 bg-white">
                        <div class="p-2 text-center">
                            <div class="text-[10px] text-gray-400 uppercase">Price</div>
                            <div class="text-xs font-semibold">{{ formatCurrency(item.price) }}</div>
                        </div>
                        <div class="p-2 text-center bg-blue-50/20">
                            <div class="text-[10px] text-gray-400 uppercase">Actual</div>
                            <div class="text-xs font-bold text-blue-700">{{ formatNumber(item.actual_qty) }}</div>
                        </div>
                        <div class="p-2 text-center bg-blue-50/20">
                            <div class="text-[10px] text-gray-400 uppercase">Init Gap</div>
                            <div class="text-xs font-bold" :class="getGapColor(getInitialGap(item))">
                                {{ getInitialGap(item) > 0 ? '+' : '' }}{{ formatNumber(getInitialGap(item)) }}
                            </div>
                        </div>
                    </div>

                    <div class="p-3 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-green-700 uppercase">SOH (Editable)</label>
                                <input type="number" v-model.number="item.soh" @input="markDirty(item)"
                                    class="w-full border-green-200 bg-green-50/20 rounded-md text-center font-bold text-sm focus:ring-2 focus:ring-green-400 focus:border-green-400 py-2"
                                    placeholder="0" />
                                <div v-if="item.soh_updated_at" class="text-[9px] text-green-500 text-center">
                                    {{ formatCompactTimestamp(item.soh_updated_at) }}
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-yellow-700 uppercase">Error Mvmt</label>
                                <input type="number" v-model.number="item.error_movement" @input="markDirty(item)"
                                    class="w-full border-yellow-200 bg-yellow-50/20 rounded-md text-center font-medium text-sm focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 py-2"
                                    placeholder="0" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-500 uppercase">O/S GR (+)</label>
                                <input type="number" min="0" v-model.number="item.outstanding_gr"
                                    @input="markDirty(item)"
                                    class="w-full border-gray-300 rounded-md text-center text-sm focus:ring-2 focus:ring-blue-400 py-2"
                                    placeholder="0" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-500 uppercase">O/S GI (-)</label>
                                <input type="number" :value="item.outstanding_gi"
                                    @input="item.outstanding_gi = -Math.abs($event.target.value); markDirty(item)"
                                    class="w-full border-gray-300 rounded-md text-center text-sm text-red-600 focus:ring-2 focus:ring-red-400 py-2"
                                    placeholder="0" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-3 border-t border-gray-200 flex justify-between items-center">
                        <div class="flex flex-col">
                            <span class="text-[10px] text-gray-500 uppercase font-bold">Final Variance</span>
                            <span class="text-sm font-bold" :class="getGapColor(getFinalDiscrepancy(item).val)">
                                {{ formatNumber(getFinalDiscrepancy(item).val) }} Qty
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-bold"
                                :class="getGapColor(getFinalDiscrepancy(item).val * (item.price || 0))">
                                {{ formatCurrency(getFinalDiscrepancy(item).val * (item.price || 0)) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div ref="tableScrollRef"
                class="hidden md:block bg-white shadow-lg rounded-lg border border-gray-200 overflow-x-auto pb-4"
                :class="{ 'cursor-grabbing': isDragging, 'cursor-grab': !isDragging }" @mousedown="onDragStart"
                @mousemove="onDragMove" @mouseup="onDragEnd" @mouseleave="onDragEnd">
                <table class="w-full text-sm text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-gray-50/80 text-xs text-gray-600 uppercase font-semibold border-b border-gray-200 leading-tight">
                            <th class="px-2 py-1 min-w-[120px] border-r border-gray-100">PID.</th>
                            <th class="px-2 py-1 min-w-[120px] border-r border-gray-100">Material No.</th>
                            <th class="px-2 py-1 min-w-[200px] border-r border-gray-100">Material Name</th>
                            <th class="px-2 py-1 border-r border-gray-100">Rack</th>
                            <th class="px-2 py-1 text-center border-r border-gray-200">Price</th>

                            <th class="px-2 py-1 w-28 bg-green-50/30 text-green-700 border-r border-green-100">
                                SOH<br><span class="text-[10px]">(Editable)</span>
                            </th>
                            <th class="px-2 py-1 text-center bg-blue-50/30 border-r border-blue-100">
                                Actual<br><span class="text-[10px] opacity-70">(Count)</span>
                            </th>
                            <th
                                class="px-2 py-1 text-center font-bold text-gray-700 bg-blue-50/30 border-r border-blue-100">
                                Initial<br>Gap
                            </th>

                            <th class="px-4 py-3 w-28 bg-yellow-50/30 text-yellow-700 border-r border-yellow-100">
                                O/S GR<br><span class="text-[10px]">(+)</span>
                            </th>
                            <th class="px-4 py-3 w-28 bg-yellow-50/30 text-yellow-700 border-r border-yellow-100">
                                O/S GI<br><span class="text-[10px]">(-)</span>
                            </th>
                            <th class="px-4 py-3 w-28 bg-yellow-50/30 text-yellow-700 border-r border-yellow-100">
                                Error<br><span class="text-[10px]">(Mvmt)</span>
                            </th>

                            <th class="px-4 py-3 text-center bg-gray-50 border-l border-gray-200">Final Gap</th>
                            <th class="px-2 py-1 text-xs text-center bg-gray-50 border-l border-gray-200">Final Counted
                                Qty</th>
                            <th class="px-4 py-3 text-center  bg-gray-50 shadow-sm border-l border-gray-200 align-bottom cursor-pointer hover:bg-gray-100 transition-colors select-none"
                                @click="handleSort('final_discrepancy_amount')">
                                <div class="flex items-center justify-center gap-1">
                                    <span>Final Amount</span>
                                    <div class="flex flex-col">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3"
                                            :class="sortBy === 'final_discrepancy_amount' && sortOrder === 'asc' ? 'text-blue-600' : 'text-gray-400'"
                                            fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M7 14l5-5 5 5z" />
                                        </svg>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 -mt-1"
                                            :class="sortBy === 'final_discrepancy_amount' && sortOrder === 'desc' ? 'text-blue-600' : 'text-gray-400'"
                                            fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M7 10l5 5 5-5z" />
                                        </svg>
                                    </div>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="loading" class="hover:bg-gray-50">
                            <td colspan="13" class="px-4 py-8 text-center text-gray-500">
                                <div class="flex justify-center items-center">
                                    <Loader2 class="w-5 h-5 mr-3 text-blue-500 animate-spin" />
                                    Loading data...
                                </div>
                            </td>
                        </tr>
                        <tr v-else-if="items.length === 0" class="hover:bg-gray-50">
                            <td colspan="13" class="px-4 py-8 text-center text-gray-500">
                                No items found. Make sure items have been counted first.
                            </td>
                        </tr>
                        <tr v-else v-for="item in items" :key="item.id"
                            class="group hover:bg-gray-50 transition-colors relative"
                            :class="{ 'bg-yellow-50': item._dirty }">

                            <td class="px-1 py-1 text-xs font-medium text-gray-900 border-r border-gray-100">
                                {{ item.pid }}
                            </td>
                            <td class="px-1 py-1 text-xs font-medium text-gray-900 border-r border-gray-100">
                                {{ item.material_number }}
                            </td>
                            <td class="px-1 py-1 border-r border-gray-100">
                                <div class="text-gray-900 text-xs">{{ item.description }}</div>
                                <div class="text-xs text-gray-500 mt-0.5 inline-block bg-gray-100 px-1.5 rounded-sm">
                                    {{ item.unit_of_measure }}
                                </div>
                            </td>
                            <td class="px-2 py-4 text-xs text-gray-600 text-center border-r border-gray-100">
                                {{ item.rack_address || '-' }}
                            </td>
                            <td class="px-1 py-1 text-xs text-right text-gray-700 font-medium border-r border-gray-200">
                                {{ formatCurrency(item.price) }}
                            </td>

                            <td
                                class="px-3 py-2 bg-green-50/10 border-r border-green-100 group-hover:bg-green-50/30 transition-colors">
                                <input type="number" v-model.number="item.soh" @input="markDirty(item)" class="w-full min-w-[90px] border-gray-300 rounded-md text-right font-medium text-sm tabular-nums whitespace-nowrap
           focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 shadow-sm px-2 py-1.5 transition-all"
                                    placeholder="0" />
                                <div v-if="item.soh_updated_at" class="text-[10px] text-green-500 text-right mt-1">
                                    {{ formatCompactTimestamp(item.soh_updated_at) }}
                                </div>
                            </td>

                            <td
                                class="px-1 text-center border-r border-blue-100 group-hover:bg-blue-50/30 transition-colors">
                                <div class="text-sm font-bold text-gray-800">{{ formatNumber(item.actual_qty) }}</div>
                                <div v-if="item.counted_at"
                                    class="text-[11px] text-gray-400 font-medium mt-1 whitespace-nowrap">
                                    {{ formatCompactTimestamp(item.counted_at) }}
                                </div>
                                <button @click="openEditActualQtyModal(item)"
                                    class="mt-1 p-1 text-blue-600 hover:bg-blue-100 rounded transition-colors"
                                    title="Edit Actual Qty">
                                    <Pencil class="w-3.5 h-3.5" />
                                </button>
                            </td>

                            <td class="px-4 py-4 text-center font-mono text-sm font-bold border-r border-blue-100 group-hover:bg-blue-50/30 transition-colors"
                                :class="getGapColor(getInitialGap(item))">
                                {{ getInitialGap(item) > 0 ? '+' : '' }}{{ formatNumber(getInitialGap(item)) }}
                            </td>

                            <td
                                class="px-2 py-2 bg-yellow-50/10 border-r border-yellow-100 group-hover:bg-yellow-50/30 transition-colors min-w-[110px]">
                                <input type="number" min="0" v-model.number="item.outstanding_gr"
                                    @input="markDirty(item)" class="w-full min-w-[90px] border-gray-300 rounded-md text-right font-medium text-sm tabular-nums whitespace-nowrap
           focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 shadow-sm px-2 py-1.5 transition-all"
                                    placeholder="0" />
                            </td>

                            <td
                                class="px-2 py-2 bg-yellow-50/10 border-r border-yellow-100 group-hover:bg-yellow-50/30 transition-colors min-w-[110px]">
                                <input type="number" :value="item.outstanding_gi"
                                    @input="item.outstanding_gi = -Math.abs($event.target.value); markDirty(item)"
                                    class="w-full min-w-[90px] border-gray-300 rounded-md text-right font-medium text-sm tabular-nums whitespace-nowrap text-red-600
           focus:ring-2 focus:ring-red-400 focus:border-red-400 shadow-sm px-2 py-1.5 transition-all"
                                    placeholder="0" />
                            </td>

                            <td
                                class="px-2 py-2 bg-yellow-50/10 border-r border-yellow-100 group-hover:bg-yellow-50/30 transition-colors min-w-[110px]">
                                <input type="number" v-model.number="item.error_movement" @input="markDirty(item)"
                                    class="w-full min-w-[90px] border-gray-300 rounded-md text-right font-medium text-sm tabular-nums whitespace-nowrap
           focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 shadow-sm px-2 py-1.5 transition-all"
                                    placeholder="0" />
                            </td>


                            <td class="px-4 py-4 text-center bg-white border-l border-gray-200 group-hover:bg-gray-50">
                                <div class="inline-block px-3 py-1 rounded-md bg-gray-50 border"
                                    :class="[getGapColor(getFinalDiscrepancy(item).val), getFinalDiscrepancy(item).val === 0 ? 'border-gray-200' : 'border-current opacity-80']">
                                    <div class="text-sm font-bold">
                                        {{ formatNumber(getFinalDiscrepancy(item).val) }}
                                    </div>
                                </div>
                                <div v-if="getFinalDiscrepancy(item).val === 0"
                                    class="text-[10px] font-bold text-green-600 uppercase tracking-wider mt-1">Matched
                                </div>
                                <div v-else class="text-[10px] font-bold text-red-500 uppercase tracking-wider mt-1">
                                    Variance</div>

                            </td>
                            <td>
                                <div
                                    class="px-1 py-1 text-center bg-white border-l border-gray-200 group-hover:bg-gray-50">
                                    <span class="inline-block text-xs px-3 py-1 rounded-md bg-gray-50 border">
                                        {{ getFinalDiscrepancy(item).predictedSOH }}</span>
                                </div>
                            </td>

                            <td class="px-4 py-4 text-xs text-right bg-white border-l border-gray-200 group-hover:bg-gray-50 font-medium whitespace-nowrap tabular-nums"
                                :class="getGapColor(getFinalDiscrepancy(item).val * (item.price || 0))">
                                {{ formatCurrency(getFinalDiscrepancy(item).val * (item.price || 0)) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="pagination.last_page > 1" class="bg-white rounded-lg shadow-sm border border-gray-200 mt-4 p-4">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="text-sm text-gray-600 order-2 md:order-1">
                        <span class="md:hidden">Page {{ pagination.current_page }} / {{ pagination.last_page }}</span>
                        <span class="hidden md:inline">Page {{ pagination.current_page }} of {{ pagination.last_page
                        }}</span>
                    </div>

                    <div class="flex gap-2 order-1 md:order-2">
                        <button @click="goToPage(1)" :disabled="pagination.current_page === 1"
                            class="hidden md:block px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed text-sm">
                            First
                        </button>
                        <button @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                            class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed text-sm flex items-center">
                            <ChevronLeft class="w-4 h-4 md:hidden" />
                            <span class="hidden md:inline">Previous</span>
                        </button>

                        <div class="hidden md:flex gap-1">
                            <template v-for="page in getPageNumbers()" :key="page">
                                <button v-if="page !== '...'" @click="goToPage(page)"
                                    class="px-3 py-1 border rounded-md text-sm"
                                    :class="pagination.current_page === page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50'">
                                    {{ page }}
                                </button>
                                <span v-else class="px-2 py-1 text-sm">...</span>
                            </template>
                        </div>

                        <button @click="goToPage(pagination.current_page + 1)"
                            :disabled="pagination.current_page === pagination.last_page"
                            class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed text-sm flex items-center">
                            <ChevronRight class="w-4 h-4 md:hidden" />
                            <span class="hidden md:inline">Next</span>
                        </button>
                        <button @click="goToPage(pagination.last_page)"
                            :disabled="pagination.current_page === pagination.last_page"
                            class="hidden md:block px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed text-sm">
                            Last
                        </button>
                    </div>

                    <div class="hidden md:block text-sm text-gray-600 order-3">
                        Total: {{ pagination.total }} items
                    </div>
                </div>
            </div>
        </div>
        <!-- Submit Confirmation Modal -->
        <Modal :show="showSubmitModal" @close="closeSubmitModal" max-width="md">
            <div class="p-4 sm:p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div
                        class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                        <Check class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600" />
                    </div>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900">Confirm Stock Count</h2>
                </div>

                <div class="bg-gray-50 rounded-lg p-3 sm:p-4 mb-4 sm:mb-6 space-y-2">
                    <div class="flex flex-col sm:flex-row sm:justify-between gap-1">
                        <span class="text-xs sm:text-sm text-gray-600">Material Number:</span>
                        <span class="text-xs sm:text-sm font-semibold text-gray-900">{{ itemToSubmit?.material_number
                        }}</span>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:justify-between gap-1">
                        <span class="text-xs sm:text-sm text-gray-600">Description:</span>
                        <span class="text-xs sm:text-sm font-medium text-gray-900">{{ itemToSubmit?.description
                            }}</span>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:justify-between items-center gap-1 pt-2">
                        <label class="text-xs sm:text-sm text-gray-600 font-medium">Actual Count:</label>
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <input v-model="editingQty" type="number" min="0"
                                class="w-full sm:w-32 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 font-bold text-lg text-blue-600 text-center" />
                            <span class="text-sm font-medium text-gray-500">{{ itemToSubmit?.unit_of_measure }}</span>
                        </div>
                    </div>
                </div>

                <p class="text-xs sm:text-sm text-gray-600 mb-4 sm:mb-6">
                    Are you sure you want to submit this count?
                </p>

                <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3">
                    <button @click="closeSubmitModal"
                        class="w-full sm:w-auto px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300 transition order-2 sm:order-1">
                        Cancel
                    </button>
                    <button @click="confirmSubmit" :disabled="isSaving"
                        class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 disabled:opacity-50 transition order-1 sm:order-2">
                        {{ isSaving ? 'Saving...' : 'Confirm Submit' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Edit Actual Qty Modal -->
        <Modal :show="showEditActualQtyModal" @close="closeEditActualQtyModal" max-width="lg">
            <div class="p-4 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <Pencil class="w-5 h-5 text-blue-600" />
                        </div>
                        <h2 class="text-base sm:text-lg font-semibold text-gray-900">Edit Actual Quantity</h2>
                    </div>
                    <button @click="closeEditActualQtyModal" class="p-1 hover:bg-gray-100 rounded-lg transition-colors">
                        <X class="w-5 h-5 text-gray-500" />
                    </button>
                </div>

                <!-- Item Info -->
                <div class="bg-gray-50 rounded-lg p-3 sm:p-4 mb-4 space-y-2">
                    <div class="flex flex-col sm:flex-row sm:justify-between gap-1">
                        <span class="text-xs sm:text-sm text-gray-600">Material Number:</span>
                        <span class="text-xs sm:text-sm font-semibold text-gray-900">{{ editingItem?.material_number
                        }}</span>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:justify-between gap-1">
                        <span class="text-xs sm:text-sm text-gray-600">Description:</span>
                        <span class="text-xs sm:text-sm font-medium text-gray-900 text-right">{{
                            editingItem?.description
                        }}</span>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:justify-between gap-1">
                        <span class="text-xs sm:text-sm text-gray-600">Current Actual Qty:</span>
                        <span class="text-xs sm:text-sm font-bold text-blue-600">{{
                            formatNumber(editingItem?.actual_qty) }} {{
                                editingItem?.unit_of_measure }}</span>
                    </div>
                </div>

                <!-- History Section -->
                <div class="mb-4">
                    <div class="flex items-center gap-2 mb-3">
                        <History class="w-4 h-4 text-gray-500" />
                        <h3 class="text-sm font-semibold text-gray-700">Quantity History</h3>
                    </div>
                    <div v-if="editingItem?.actual_qty_history?.length > 0"
                        class="max-h-48 overflow-y-auto border border-gray-200 rounded-lg">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">#</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600">Qty</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Date/Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(history, index) in editingItem.actual_qty_history" :key="index"
                                    class="hover:bg-gray-50"
                                    :class="{ 'bg-blue-50': index === editingItem.actual_qty_history.length - 1 }">
                                    <td class="px-3 py-2 text-gray-500">{{ index + 1 }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-gray-800">{{
                                        formatNumber(history.actual_qty) }}</td>
                                    <td class="px-3 py-2 text-gray-500 text-xs">{{ history.counted_at }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else
                        class="text-center py-4 text-gray-500 text-sm border border-gray-200 rounded-lg bg-gray-50">
                        No history available
                    </div>
                </div>

                <!-- New Quantity Input -->
                <div class="bg-blue-50 rounded-lg p-4 mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">New Actual Quantity</label>
                    <div class="flex items-center gap-3">
                        <input v-model.number="newActualQty" type="number" min="0" step="1"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-bold text-center"
                            placeholder="Enter new quantity" />
                        <span class="text-sm font-medium text-gray-500">{{ editingItem?.unit_of_measure }}</span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3">
                    <button @click="closeEditActualQtyModal"
                        class="w-full sm:w-auto px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300 transition order-2 sm:order-1">
                        Cancel
                    </button>
                    <button @click="saveActualQty"
                        :disabled="savingActualQty || newActualQty === null || newActualQty === editingItem?.actual_qty"
                        class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition order-1 sm:order-2 flex items-center justify-center gap-2">
                        <Loader2 v-if="savingActualQty" class="w-4 h-4 animate-spin" />
                        {{ savingActualQty ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </div>
        </Modal>
    </MainAppLayout>


</template>

<script setup>
import MainAppLayout from '@/Layouts/MainAppLayout.vue';
import Modal from '@/Components/Modal.vue';
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Save,
    RefreshCw,
    Search,
    Package,
    DollarSign,
    Loader2,
    Download,
    Upload,
    Pencil,
    History,
    X,
    Check,
    ChevronLeft,
    ChevronRight,
    IterationCcw,
} from 'lucide-vue-next';
import axios from 'axios';

// Props
const props = defineProps({
    pid_id: {
        type: [Number, String],
        default: null
    }
});

// State
const items = ref([]);
const pids = ref([]);
const pidData = ref(null);
const statistics = ref({
    totalItems: 0,
    progressTotal: 0,
    countedItems: 0,
    pendingItems: 0,
    countedPercent: 0,
    surplusCount: 0,
    discrepancyCount: 0,
    matchCount: 0,
    surplusAmount: 0,
    discrepancyAmount: 0,
    nettDiscrepancyAmount: 0,
    systemAmount: 0,
    surplusCountPercent: 0,
    discrepancyCountPercent: 0,
    matchCountPercent: 0,
    surplusAmountPercent: 0,
    discrepancyAmountPercent: 0,
});
const pagination = ref({
    current_page: 1,
    per_page: 50,
    total: 0,
    last_page: 1,
});

const selectedPID = ref(props.pid_id || '');
const selectedStatus = ref('');
const selectedDiscrepancy = ref('');
const selectedLocation = ref('');
const sortBy = ref(null);
const sortOrder = ref('asc');
const searchQuery = ref('');
const loading = ref(false);
const saving = ref(false);
const uploading = ref(false);
const isRecalculating = ref(false);
const error = ref(null);
const successMessage = ref(null);
const locations = ref([]);

let searchTimeout = null;

const showSubmitModal = ref(false);
const itemToSubmit = ref(null);
const editingQty = ref(null);     // ✅ must be number/null, not false
const isSaving = ref(false);      // ✅ used in template

const showEditActualQtyModal = ref(false);
const editingItem = ref(null);        // selected item snapshot for modal
const editingItemId = ref(null);      // keep id stable
const newActualQty = ref(null);
const savingActualQty = ref(false);
const loadingActualQtyHistory = ref(false);
// Computed
const hasChanges = computed(() => items.value.some(item => item._dirty));

// Fetch PIDs list for dropdown
const fetchPIDs = async () => {
    try {
        const response = await axios.get('/api/annual-inventory/pids-dropdown');
        if (response.data.success) {
            pids.value = response.data.data;
        }
    } catch (err) {
        console.error('Failed to fetch PIDs:', err);
    }
};

// Fetch locations for filter
const fetchLocations = async () => {
    try {
        const response = await axios.get('/api/annual-inventory/locations');
        if (response.data.success) {
            locations.value = response.data.data;
        }
    } catch (err) {
        console.error('Failed to fetch locations:', err);
    }
};

// Download Excel template
const downloadTemplate = () => {
    window.location.href = '/api/annual-inventory/discrepancy/template';
};

// Download Excel with current filters
const downloadExcel = () => {
    const params = new URLSearchParams();

    if (selectedPID.value) {
        params.append('pid_id', selectedPID.value);
    }
    if (selectedDiscrepancy.value) {
        params.append('discrepancy_type', selectedDiscrepancy.value);
    }
    if (selectedLocation.value) {
        params.append('location', selectedLocation.value);
    }
    if (searchQuery.value) {
        params.append('search', searchQuery.value);
    }

    const queryString = params.toString();
    window.location.href = '/api/annual-inventory/discrepancy/export' + (queryString ? '?' + queryString : '');
};

// Handle Excel file upload
// Recalculate all discrepancy values
const recalculateDiscrepancy = async () => {
    isRecalculating.value = true;
    error.value = null;
    try {
        const response = await axios.post('/api/annual-inventory/recalculate-discrepancy');
        if (response.data.success) {
            successMessage.value = `Recalculated ${response.data.updated} items`;
            setTimeout(() => successMessage.value = null, 3000);
            await fetchData(pagination.value.current_page, true);
        }
    } catch (err) {
        error.value = 'Recalculation failed: ' + (err.response?.data?.message || err.message);
    } finally {
        isRecalculating.value = false;
    }
};

const handleFileUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    uploading.value = true;
    error.value = null;

    const formData = new FormData();
    formData.append('file', file);

    try {
        const response = await axios.post('/api/annual-inventory/discrepancy/import', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        if (response.data.success) {
            successMessage.value = `Excel imported successfully! Updated: ${response.data.updated} items`;
            setTimeout(() => successMessage.value = null, 5000);
            await fetchData(1, true); // skipConfirm - just imported
        }
    } catch (err) {
        error.value = 'Failed to upload Excel: ' + (err.response?.data?.message || err.message);
    } finally {
        uploading.value = false;
        event.target.value = '';
    }
};

//handle sort
const handleSort = (column) => {
    if (sortBy.value === column) {
        // Toggle sort order if clicking the same column
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        // Set new column and default to ascending
        sortBy.value = column;
        sortOrder.value = 'asc';
    }
    fetchData(1); // Reset to page 1 when sorting changes
};


// Check for unsaved changes before action
const confirmIfUnsaved = (message = 'You have unsaved changes. Are you sure you want to continue?') => {
    if (hasChanges.value) {
        return confirm(message);
    }
    return true;
};

// Fetch discrepancy data
const fetchData = async (page = 1, skipConfirm = false) => {
    if (!skipConfirm && !confirmIfUnsaved('You have unsaved changes. Refreshing will discard them. Continue?')) {
        return;
    }
    loading.value = true;
    error.value = null;

    try {
        const params = {
            per_page: pagination.value.per_page,
            page: page,
        };

        if (selectedPID.value) {
            params.pid_id = selectedPID.value;
        }

        if (selectedStatus.value) {
            params.status = selectedStatus.value;
        }

        if (selectedDiscrepancy.value) {
            params.discrepancy_type = selectedDiscrepancy.value;
        }

        if (selectedLocation.value) {
            params.location = selectedLocation.value;
        }

        if (searchQuery.value) {
            params.search = searchQuery.value;
        }

        if (sortBy.value) {
            params.sort_by = sortBy.value;
            params.sort_order = sortOrder.value;
        }


        const response = await axios.get('/api/annual-inventory/discrepancy', { params });

        if (response.data.success) {
            items.value = response.data.data.items.map(item => ({
                ...item,
                _dirty: false,
                _original: {
                    actual_qty: item.actual_qty,
                    soh: item.soh,
                    outstanding_gr: item.outstanding_gr,
                    outstanding_gi: item.outstanding_gi,
                    error_movement: item.error_movement,
                }
            }));
            statistics.value = response.data.data.statistics;
            pagination.value = response.data.data.pagination;

            // Get PID data if filtered
            if (selectedPID.value && response.data.data.pid) {
                pidData.value = response.data.data.pid;
            } else {
                pidData.value = null;
            }
        }
    } catch (err) {
        error.value = 'Failed to fetch data: ' + (err.response?.data?.message || err.message);
        console.error('Fetch error:', err);
    } finally {
        loading.value = false;
    }
};

// Mark item as dirty
const markDirty = (item) => {
    item._dirty = true;
};

// Save all changes
const saveAllChanges = async () => {
    const dirtyItems = items.value.filter(item => item._dirty);
    if (dirtyItems.length === 0) return;

    saving.value = true;
    error.value = null;

    try {
        const payload = {
            items: dirtyItems.map(item => ({
                id: item.id,
                soh: item.soh || 0,
                outstanding_gr: item.outstanding_gr || 0,
                outstanding_gi: item.outstanding_gi || 0,
                error_movement: item.error_movement || 0,
            }))
        };

        const response = await axios.post('/api/annual-inventory/discrepancy/bulk-update', payload);

        if (response.data.success) {
            successMessage.value = `Successfully updated ${response.data.updated} items`;
            setTimeout(() => successMessage.value = null, 3000);

            // Reset dirty flags
            dirtyItems.forEach(item => {
                item._dirty = false;
                item._original = {
                    actual_qty: item.actual_qty,
                    soh: item.soh,
                    outstanding_gr: item.outstanding_gr,
                    outstanding_gi: item.outstanding_gi,
                    error_movement: item.error_movement,
                };
            });

            // Refresh to get updated calculations
            await fetchData(pagination.value.current_page, true); // skipConfirm - just saved
        }
    } catch (err) {
        error.value = 'Failed to save: ' + (err.response?.data?.message || err.message);
    } finally {
        saving.value = false;
    }
};

// Filter handlers
const handleFilterChange = () => {
    if (!confirmIfUnsaved('You have unsaved changes. Changing filter will discard them. Continue?')) {
        return;
    }
    fetchData(1, true); // skipConfirm since we already confirmed
};

// Search with debounce
watch(searchQuery, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        if (!confirmIfUnsaved('You have unsaved changes. Searching will discard them. Continue?')) {
            return;
        }
        fetchData(1, true); // skipConfirm since we already confirmed
    }, 500);
});

// Pagination
const goToPage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        if (!confirmIfUnsaved('You have unsaved changes. Changing page will discard them. Continue?')) {
            return;
        }
        fetchData(page, true); // skipConfirm since we already confirmed
    }
};

const getPageNumbers = () => {
    const pages = [];
    const current = pagination.value.current_page;
    const last = pagination.value.last_page;

    if (last <= 7) {
        for (let i = 1; i <= last; i++) pages.push(i);
    } else {
        pages.push(1);
        if (current > 3) pages.push('...');

        const start = Math.max(2, current - 1);
        const end = Math.min(last - 1, current + 1);
        for (let i = start; i <= end; i++) pages.push(i);

        if (current < last - 2) pages.push('...');
        pages.push(last);
    }

    return pages;
};

// Navigation
const goBack = () => {
    router.visit('/annual-inventory');
};

// Calculations
const getInitialGap = (item) => {
    const actual = item.actual_qty || 0;
    const soh = item.soh || 0;
    return actual - soh;
};

const getFinalDiscrepancy = (item) => {
    const initialGap = getInitialGap(item);
    const gr = Number(item.outstanding_gr) || 0;
    const gi = Number(item.outstanding_gi) || 0;
    const err = Number(item.error_movement) || 0;
    const explainedSystem = gr + gi + err;
    const finalGap = initialGap - explainedSystem;
    const predictedSOH = item.soh + finalGap

    return {
        val: finalGap,
        predictedSOH: predictedSOH
    };
};

// Formatting
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value || 0);
};

const formatNumber = (value) => {
    if (value === null || value === undefined) return '0';
    return new Intl.NumberFormat('en-US').format(value);
};

const getGapColor = (val) => {
    if (val === 0) return 'text-gray-400';
    if (val > 0) return 'text-blue-600';
    return 'text-red-600';
};

const formatCompactTimestamp = (timestampStr) => {
    if (!timestampStr) return '';
    const date = new Date(timestampStr);
    if (isNaN(date.getTime())) return '';
    return new Intl.DateTimeFormat('en-GB', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    }).format(date).replace(',', '');
};


const closeSubmitModal = () => {
    showSubmitModal.value = false;
    itemToSubmit.value = null;
    editingQty.value = null;
}

// Edit Actual Qty Modal Functions
const openEditActualQtyModal = async (item) => {
    // ✅ keep stable id + snapshot for modal
    editingItemId.value = item.id;
    editingItem.value = { ...item }; // snapshot (prevents reactive weirdness)
    newActualQty.value = item.actual_qty ?? 0;
    showEditActualQtyModal.value = true;

    // ✅ fetch history if not present (optional but recommended)
    // If your backend already includes history in list response, this will just skip.
    if (!Array.isArray(item.actual_qty_history)) {
        loadingActualQtyHistory.value = true;
        try {
            // Adjust endpoint if yours differs:
            const res = await axios.get(`/api/annual-inventory/items/${item.id}`);
            if (res.data?.success) {
                editingItem.value = {
                    ...editingItem.value,
                    ...res.data.data,
                    actual_qty_history: res.data.data.actual_qty_history || [],
                };
            }
        } catch (e) {
            // don't hard-fail modal, just show "No history"
            editingItem.value.actual_qty_history = [];
        } finally {
            loadingActualQtyHistory.value = false;
        }
    }
};

const closeEditActualQtyModal = () => {
    showEditActualQtyModal.value = false;
    editingItem.value = null;
    editingItemId.value = null;
    newActualQty.value = null;
    loadingActualQtyHistory.value = false;
};

const saveActualQty = async () => {
    const id = editingItemId.value;
    if (!id) return;

    const nextQty = Number(newActualQty.value);
    const currentQty = Number(editingItem.value?.actual_qty ?? 0);

    if (!Number.isFinite(nextQty) || nextQty < 0) return;
    if (nextQty === currentQty) return;

    savingActualQty.value = true;
    error.value = null;

    try {
        const response = await axios.put(`/api/annual-inventory/items/${id}`, {
            actual_qty: nextQty,
        });

        if (response.data.success) {
            // ✅ update the row in current table without relying on editingItem ref
            const idx = items.value.findIndex((i) => i.id === id);
            if (idx !== -1) {
                items.value[idx].actual_qty = nextQty;
                items.value[idx].actual_qty_history = response.data.data?.actual_qty_history ?? items.value[idx].actual_qty_history ?? [];
                items.value[idx].counted_at = response.data.data?.counted_at ?? items.value[idx].counted_at;
            }

            successMessage.value = 'Actual quantity updated successfully';
            setTimeout(() => (successMessage.value = null), 3000);

            closeEditActualQtyModal();

            // ✅ refresh to recalc stats/gaps
            await fetchData(pagination.value.current_page, true);
        }
    } catch (err) {
        error.value = 'Failed to update: ' + (err.response?.data?.message || err.message);
    } finally {
        savingActualQty.value = false;
    }
};


// --- DRAG TO SCROLL ---
const tableScrollRef = ref(null);
const isDragging = ref(false);
const dragStartX = ref(0);
const scrollStartX = ref(0);
const isPointerDown = ref(false);
const DRAG_THRESHOLD = 5; // px - movement below this allows text selection

const onDragStart = (e) => {
    if (e.target.closest('input, button, select, a, label')) return;
    isPointerDown.value = true;
    isDragging.value = false;
    dragStartX.value = e.clientX;
    scrollStartX.value = tableScrollRef.value.scrollLeft;
};

const onDragMove = (e) => {
    if (!isPointerDown.value) return;
    const dx = e.clientX - dragStartX.value;

    // Only start dragging after exceeding the threshold
    if (!isDragging.value && Math.abs(dx) >= DRAG_THRESHOLD) {
        isDragging.value = true;
        // Clear any accidental text selection when drag starts
        window.getSelection()?.removeAllRanges();
    }

    if (isDragging.value) {
        e.preventDefault();
        tableScrollRef.value.scrollLeft = scrollStartX.value - dx;
    }
};

const onDragEnd = () => {
    isPointerDown.value = false;
    isDragging.value = false;
};

// Lifecycle
const handleBeforeUnload = (e) => {
    if (hasChanges.value) {
        e.preventDefault();
        e.returnValue = '';
        return '';
    }
};

let removeInertiaListener = null;

onMounted(() => {
    fetchPIDs();
    fetchLocations();
    fetchData(1, true); // skipConfirm - initial load

    // Browser navigation (refresh, close tab, URL change)
    window.addEventListener('beforeunload', handleBeforeUnload);

    // Inertia SPA navigation
    removeInertiaListener = router.on('before', (event) => {
        if (hasChanges.value) {
            if (!confirm('You have unsaved changes. Are you sure you want to leave this page?')) {
                event.preventDefault();
            }
        }
    });
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', handleBeforeUnload);
    if (removeInertiaListener) {
        removeInertiaListener();
    }
});

// --- HELPERS ---
const showSaveMessage = (message, type) => {
    saveMessage.value = { message, type };
    setTimeout(() => {
        saveMessage.value = null;
    }, 3000);
};

</script>

<style scoped>
.cursor-grabbing {
    user-select: none;
    cursor: grabbing;
}

.cursor-grab {
    cursor: grab;
}
</style>
