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
                            <span class="ml-auto text-[10px] md:text-xs text-gray-500">Total: {{ statistics.totalItems
                            }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3 md:gap-4">
                            <div class="bg-blue-50 p-2 md:p-3 rounded-lg shadow-sm border border-blue-100">
                                <span class="block text-xs md:text-sm text-blue-600 font-bold mb-1">Discrepancy Items
                                    (+)</span>
                                <div class="flex flex-col sm:flex-row sm:items-baseline gap-1 sm:gap-2">
                                    <span class="text-base md:text-lg font-bold text-blue-800">{{
                                        statistics.surplusCount }}</span>
                                    <span
                                        class="text-[10px] font-semibold text-blue-600 bg-blue-100 px-1.5 py-0.5 rounded w-fit">
                                        {{ statistics.surplusCountPercent }}%
                                    </span>
                                </div>
                            </div>
                            <div class="bg-red-50 p-2 md:p-3 rounded-lg shadow-sm border border-red-100">
                                <span class="block text-xs md:text-sm text-red-600 font-bold mb-1">Discrepancy Items
                                    (-)</span>
                                <div class="flex flex-col sm:flex-row sm:items-baseline gap-1 sm:gap-2">
                                    <span class="text-base md:text-lg font-bold text-red-800">{{
                                        statistics.discrepancyCount }}</span>
                                    <span
                                        class="text-[10px] font-semibold text-red-600 bg-red-100 px-1.5 py-0.5 rounded w-fit">
                                        {{ statistics.discrepancyCountPercent }}%
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
                            <span class="ml-auto text-[10px] md:text-xs text-gray-500">Match: {{
                                statistics.matchCountPercent }}%</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3 md:gap-4">
                            <div class="bg-white p-2 md:p-3 rounded-lg shadow-sm border border-blue-100">
                                <span class="block text-xs md:text-sm text-blue-600 font-bold mb-1">Discrepancy Amount
                                    (+) </span>
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
                                <span class="block text-xs md:text-sm text-red-600 font-bold mb-1">Discrepancy Amount
                                    (-) </span>
                                <div class="flex flex-col xl:flex-row xl:items-baseline gap-1">
                                    <span class="text-sm md:text-lg font-bold text-gray-800 break-all">{{
                                        formatCurrency(statistics.discrepancyAmount) }}
                                    </span>
                                    <span
                                        class="text-[10px] font-semibold text-red-600 bg-red-100 px-1.5 py-0.5 rounded w-fit">
                                        {{ statistics.discrepancyAmountPercent }}%
                                    </span>
                                </div>
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
                                <option value="shortage">Shortage</option>
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
                            <span class="text-sm font-bold" :class="getGapColor(getFinalDiscrepancy(item))">
                                {{ formatNumber(getFinalDiscrepancy(item)) }} Qty
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-bold"
                                :class="getGapColor(getFinalDiscrepancy(item) * (item.price || 0))">
                                {{ formatCurrency(getFinalDiscrepancy(item) * (item.price || 0)) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hidden md:block bg-white shadow-lg rounded-lg border border-gray-200 overflow-x-auto pb-4">
                <table class="w-full text-sm text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-gray-50/80 text-xs text-gray-600 uppercase font-semibold border-b border-gray-200 leading-tight">
                            <th class="px-4 py-3 min-w-[120px] border-r border-gray-100">Material No.</th>
                            <th class="px-4 py-3 min-w-[200px] border-r border-gray-100">Material Name</th>
                            <th class="px-4 py-3 border-r border-gray-100">Rack</th>
                            <th class="px-4 py-3 text-center border-r border-gray-200">Price</th>

                            <th class="px-4 py-3 w-28 bg-green-50/30 text-green-700 border-r border-green-100">
                                SOH<br><span class="text-[10px]">(Editable)</span>
                            </th>
                            <th class="px-4 py-3 text-center bg-blue-50/30 border-r border-blue-100">
                                Actual<br><span class="text-[10px] opacity-70">(Count)</span>
                            </th>
                            <th
                                class="px-4 py-3 text-center font-bold text-gray-700 bg-blue-50/30 border-r border-blue-100">
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

                            <th class="px-4 py-3 text-center bg-gray-50 border-l border-gray-200">Final Qty</th>
                            <th class="px-4 py-3 text-center bg-gray-50 border-l border-gray-200">Final Amount</th>
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

                            <td class="px-4 py-4 font-medium text-gray-900 border-r border-gray-100">
                                {{ item.material_number }}
                            </td>
                            <td class="px-4 py-3 border-r border-gray-100">
                                <div class="text-gray-900 text-sm">{{ item.description }}</div>
                                <div class="text-xs text-gray-500 mt-0.5 inline-block bg-gray-100 px-1.5 rounded-sm">
                                    {{ item.unit_of_measure }}
                                </div>
                            </td>
                            <td class="px-2 py-4 text-sm text-gray-600 text-center border-r border-gray-100">
                                {{ item.rack_address || '-' }}
                            </td>
                            <td class="px-1 py-1 text-right text-gray-700 font-medium border-r border-gray-200">
                                {{ formatCurrency(item.price) }}
                            </td>

                            <td
                                class="px-3 py-3 bg-green-50/10 border-r border-green-100 group-hover:bg-green-50/30 transition-colors">
                                <input type="number" v-model.number="item.soh" @input="markDirty(item)"
                                    class="w-full border-gray-300 rounded-md text-right font-medium text-sm focus:ring-2 focus:ring-green-400 focus:border-green-400 shadow-sm px-2 py-1.5 transition-all"
                                    placeholder="0" />
                            </td>

                            <td
                                class="px-1 text-center border-r border-blue-100 group-hover:bg-blue-50/30 transition-colors">
                                <div class="text-sm font-bold text-gray-800">{{ formatNumber(item.actual_qty) }}</div>
                                <div v-if="item.counted_at"
                                    class="text-[11px] text-gray-400 font-medium mt-1 whitespace-nowrap">
                                    {{ formatCompactTimestamp(item.counted_at) }}
                                </div>
                            </td>

                            <td class="px-4 py-4 text-center font-mono text-sm font-bold border-r border-blue-100 group-hover:bg-blue-50/30 transition-colors"
                                :class="getGapColor(getInitialGap(item))">
                                {{ getInitialGap(item) > 0 ? '+' : '' }}{{ formatNumber(getInitialGap(item)) }}
                            </td>

                            <td
                                class="px-3 py-3 bg-yellow-50/10 border-r border-yellow-100 group-hover:bg-yellow-50/30 transition-colors">
                                <input type="number" min="0" v-model.number="item.outstanding_gr"
                                    @input="markDirty(item)"
                                    class="w-full border-gray-300 rounded-md text-right font-medium text-sm focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 shadow-sm px-2 py-1.5 transition-all"
                                    placeholder="0" />
                            </td>

                            <td
                                class="px-3 py-3 bg-yellow-50/10 border-r border-yellow-100 group-hover:bg-yellow-50/30 transition-colors">
                                <input type="number" :value="item.outstanding_gi"
                                    @input="item.outstanding_gi = -Math.abs($event.target.value); markDirty(item)"
                                    class="w-full border-gray-300 rounded-md text-right font-medium text-sm focus:ring-2 focus:ring-red-400 focus:border-red-400 shadow-sm px-2 py-1.5 transition-all text-red-600"
                                    placeholder="0" />
                            </td>

                            <td
                                class="px-3 py-3 bg-yellow-50/10 border-r border-yellow-100 group-hover:bg-yellow-50/30 transition-colors">
                                <input type="number" v-model.number="item.error_movement" @input="markDirty(item)"
                                    class="w-full border-gray-300 rounded-md text-right font-medium text-sm focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 shadow-sm px-2 py-1.5 transition-all"
                                    placeholder="0" />
                            </td>

                            <td class="px-4 py-4 text-center bg-white border-l border-gray-200 group-hover:bg-gray-50">
                                <div class="inline-block px-3 py-1 rounded-md bg-gray-50 border"
                                    :class="[getGapColor(getFinalDiscrepancy(item)), getFinalDiscrepancy(item) === 0 ? 'border-gray-200' : 'border-current opacity-80']">
                                    <div class="text-sm font-bold">
                                        {{ formatNumber(getFinalDiscrepancy(item)) }}
                                    </div>
                                </div>
                                <div v-if="getFinalDiscrepancy(item) === 0"
                                    class="text-[10px] font-bold text-green-600 uppercase tracking-wider mt-1">Matched
                                </div>
                                <div v-else class="text-[10px] font-bold text-red-500 uppercase tracking-wider mt-1">
                                    Variance</div>
                            </td>

                            <td class="px-4 py-4 text-right bg-white border-l border-gray-200 group-hover:bg-gray-50 font-medium"
                                :class="getGapColor(getFinalDiscrepancy(item) * (item.price || 0))">
                                {{ formatCurrency(getFinalDiscrepancy(item) * (item.price || 0)) }}
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
    </MainAppLayout>
</template>

<script setup>
import MainAppLayout from '@/Layouts/MainAppLayout.vue';
import { ref, computed, onMounted, watch } from 'vue';
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
    Upload
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
    surplusCount: 0,
    discrepancyCount: 0,
    matchCount: 0,
    surplusAmount: 0,
    discrepancyAmount: 0,
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
const searchQuery = ref('');
const loading = ref(false);
const saving = ref(false);
const uploading = ref(false);
const error = ref(null);
const successMessage = ref(null);
const locations = ref([]);

let searchTimeout = null;

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
            await fetchData(1);
        }
    } catch (err) {
        error.value = 'Failed to upload Excel: ' + (err.response?.data?.message || err.message);
    } finally {
        uploading.value = false;
        event.target.value = '';
    }
};

// Fetch discrepancy data
const fetchData = async (page = 1) => {
    loading.value = true;
    error.value = null;

    try {
        const params = {
            per_page: pagination.value.per_page,
            page: page,
            counted_only: true, // Only show items that have been counted
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

        const response = await axios.get('/api/annual-inventory/discrepancy', { params });

        if (response.data.success) {
            items.value = response.data.data.items.map(item => ({
                ...item,
                _dirty: false,
                _original: {
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
                    soh: item.soh,
                    outstanding_gr: item.outstanding_gr,
                    outstanding_gi: item.outstanding_gi,
                    error_movement: item.error_movement,
                };
            });

            // Refresh to get updated calculations
            await fetchData(pagination.value.current_page);
        }
    } catch (err) {
        error.value = 'Failed to save: ' + (err.response?.data?.message || err.message);
    } finally {
        saving.value = false;
    }
};

// Filter handlers
const handleFilterChange = () => {
    fetchData(1);
};

// Search with debounce
watch(searchQuery, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchData(1);
    }, 500);
});

// Pagination
const goToPage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        fetchData(page);
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

    // Final = Initial Gap + GR + GI + Error
    // GI is typically negative, GR is positive
    return initialGap + gr + gi + err;
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

// Lifecycle
onMounted(() => {
    fetchPIDs();
    fetchLocations();
    fetchData();
});
</script>
