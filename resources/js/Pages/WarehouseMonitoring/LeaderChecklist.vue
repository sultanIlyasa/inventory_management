<template>
    <MainAppLayout title="Leader Checklist" subtitle="Materials that need checking today">
        <div class="max-w-7xl mx-auto p-4">
            <!-- Header Info -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Today's Checklist:</strong> These materials need to be checked based on their
                            schedule
                            (Daily materials need daily checks, Weekly materials need checks once per week, Monthly
                            materials need checks once per month).
                        </p>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="mb-6">
                <button
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm mb-4"
                    @click="showFilters = !showFilters">
                    <span>{{ showFilters ? 'Hide Filters' : 'Show Filters' }}</span>
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4h18M4 8h16M6 12h12m-8 4h4m-6 4h8" />
                    </svg>
                </button>

                <div v-show="showFilters" class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Search -->
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Search</label>
                            <input v-model="localFilters.search" type="text"
                                placeholder="Material number or description"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200" />
                        </div>

                        <!-- PIC -->
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">PIC</label>
                            <select v-model="localFilters.pic"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                                <option value="">All PICs</option>
                                <option v-for="pic in filterOptions.pics" :key="pic.pic_name" :value="pic.pic_name">
                                    {{ pic.pic_name }}
                                </option>
                            </select>
                        </div>

                        <!-- Location -->
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Location</label>
                            <select v-model="localFilters.location"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                                <option value="">All Locations</option>
                                <option value="SUNTER_1">Sunter 1</option>
                                <option value="SUNTER_2">Sunter 2</option>
                            </select>
                        </div>

                        <!-- Usage -->
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Usage</label>
                            <select v-model="localFilters.usage"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                                <option value="">All Usage</option>
                                <option value="DAILY">Daily</option>
                                <option value="WEEKLY">Weekly</option>
                                <option value="MONTHLY">Monthly</option>
                            </select>
                        </div>

                        <!-- Gentani -->
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Gentani</label>
                            <select v-model="localFilters.gentani"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                                <option value="">All</option>
                                <option value="GENTAN-I">Gentan-I</option>
                                <option value="NON_GENTAN-I">Non Gentan-I</option>
                            </select>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-end gap-3 sm:col-span-2">
                            <button @click="applyFilters" :disabled="isLoading"
                                class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-md transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                                {{ isLoading ? 'Loading...' : 'Apply' }}
                            </button>
                            <button @click="clearFilters" :disabled="isLoading"
                                class="flex-1 rounded-lg border border-gray-200 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-60">
                                Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-green-100 text-green-700 px-4 py-3 rounded-lg shadow font-bold text-center">
                    <div class="text-2xl">{{ statistics.DAILY || 0 }}</div>
                    <div class="text-sm">DAILY Need Check</div>
                </div>
                <div class="bg-blue-100 text-blue-700 px-4 py-3 rounded-lg shadow font-bold text-center">
                    <div class="text-2xl">{{ statistics.WEEKLY || 0 }}</div>
                    <div class="text-sm">WEEKLY Need Check</div>
                </div>
                <div class="bg-purple-100 text-purple-700 px-4 py-3 rounded-lg shadow font-bold text-center">
                    <div class="text-2xl">{{ statistics.MONTHLY || 0 }}</div>
                    <div class="text-sm">MONTHLY Need Check</div>
                </div>
                <div class="bg-orange-100 text-orange-700 px-4 py-3 rounded-lg shadow font-bold text-center">
                    <div class="text-2xl">{{ statistics.TOTAL || 0 }}</div>
                    <div class="text-sm">TOTAL Need Check</div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden relative">
                <!-- Loading Overlay -->
                <div v-if="isLoading"
                    class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10">
                    <div class="text-center">
                        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-3">
                        </div>
                        <p class="text-sm text-gray-600 font-medium">Loading data...</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="p-3 border text-sm font-semibold text-left">Material Number</th>
                                <th class="p-3 border text-sm font-semibold text-left">Description</th>
                                <th class="p-3 border text-sm font-semibold text-left">PIC</th>
                                <th class="p-3 border text-sm font-semibold text-center">Usage</th>
                                <th class="p-3 border text-sm font-semibold text-center">Stock Range</th>
                                <th class="p-3 border text-sm font-semibold text-center">Last Check Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in complianceReports.data" :key="item.key" class="hover:bg-gray-50">
                                <td class="border p-2 bg-yellow-200 text-sm font-bold">{{ item.material_number }}</td>
                                <td class="border p-2 bg-yellow-100 text-sm">{{ item.description }}</td>
                                <td class="border p-2 bg-red-100 text-sm">{{ item.pic_name }}</td>
                                <td class="border p-2 text-center">
                                    <span :class="getUsageBadgeClass(item.usage)"
                                        class="px-3 py-1 rounded text-xs font-bold">
                                        {{ item.usage }}
                                    </span>
                                </td>
                                <td class="border p-2 text-sm text-center">{{ item.stock_min }} - {{ item.stock_max }}
                                </td>
                                <td class="border p-2 text-center" :class="getDaysClass(item.days_since_check)">
                                    <div v-if="item.days_since_check === 999" class="font-bold">
                                        <div class="text-sm">Never Checked</div>
                                        <div class="text-xs text-red-500 mt-1">⚠️ No history</div>
                                    </div>
                                    <div v-else-if="item.last_check_date">
                                        <div class="text-sm font-semibold">{{ formatDate(item.last_check_date) }}</div>
                                        <div class="text-xs mt-1">
                                            <span class="font-bold">({{ item.days_since_check }} days ago)</span>
                                        </div>
                                    </div>
                                    <div v-else class="font-bold text-sm">
                                        Due Today
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!complianceReports.data || complianceReports.data.length === 0">
                                <td colspan="6" class="border p-8 text-center text-gray-500">
                                    <div class="text-3xl mb-2">✅</div>
                                    <div class="text-sm font-semibold">All materials are up to date!</div>
                                    <div class="text-xs text-gray-400 mt-1">No materials need checking right now</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="complianceReports.pagination && complianceReports.pagination.last_page > 1"
                    class="border-t p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-sm text-gray-600">
                        Showing {{ (complianceReports.pagination.current_page - 1) *
                            complianceReports.pagination.per_page + 1 }}
                        to {{ Math.min(complianceReports.pagination.current_page *
                            complianceReports.pagination.per_page,
                            complianceReports.pagination.total) }}
                        of {{ complianceReports.pagination.total }} results
                    </div>
                    <div class="flex items-center gap-2 flex-wrap justify-center">
                        <button @click="changePage(complianceReports.pagination.current_page - 1)"
                            :disabled="complianceReports.pagination.current_page === 1 || isLoading"
                            class="px-3 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                            Previous
                        </button>
                        <span class="px-3 py-1 text-sm">
                            Page {{ complianceReports.pagination.current_page }} of {{
                                complianceReports.pagination.last_page }}
                        </span>
                        <button @click="changePage(complianceReports.pagination.current_page + 1)"
                            :disabled="complianceReports.pagination.current_page === complianceReports.pagination.last_page || isLoading"
                            class="px-3 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                            Next
                        </button>
                        <div class="flex items-center gap-2 ml-4">
                            <label for="jumpToPage" class="text-sm text-gray-600">Jump to:</label>
                            <input id="jumpToPage" v-model="jumpToPageInput" type="number" min="1"
                                :max="complianceReports.pagination.last_page" @keyup.enter="jumpToPage"
                                class="w-16 px-2 py-1 border rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            <button @click="jumpToPage" :disabled="isLoading"
                                class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                Go
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainAppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import MainAppLayout from '@/Layouts/MainAppLayout.vue'

const props = defineProps({
    complianceReports: {
        type: Object,
        required: true
    },
    statistics: {
        type: Object,
        default: () => ({})
    },
    filterOptions: {
        type: Object,
        default: () => ({ pics: [], locations: [], usages: [], gentani: [] })
    },
    filters: {
        type: Object,
        default: () => ({})
    }
})

const showFilters = ref(false)
const isLoading = ref(false)
const jumpToPageInput = ref('')

const localFilters = ref({
    search: props.filters.search || '',
    pic: props.filters.pic || '',
    location: props.filters.location || '',
    usage: props.filters.usage || '',
    gentani: props.filters.gentani || ''
})

const applyFilters = () => {
    if (isLoading.value) return

    router.get(
        route('warehouse-monitoring.leader-checklist'),
        {
            ...localFilters.value,
            page: 1
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['complianceReports', 'statistics', 'filterOptions'],
            onStart: () => { isLoading.value = true },
            onFinish: () => { isLoading.value = false }
        }
    )
}

const clearFilters = () => {
    localFilters.value = {
        search: '',
        pic: '',
        location: '',
        usage: '',
        gentani: ''
    }
    applyFilters()
}

const changePage = (page) => {
    if (isLoading.value) return

    router.get(
        route('warehouse-monitoring.leader-checklist'),
        {
            ...localFilters.value,
            page: page
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['complianceReports'],
            onStart: () => { isLoading.value = true },
            onFinish: () => { isLoading.value = false }
        }
    )
}

const jumpToPage = () => {
    const pageNum = parseInt(jumpToPageInput.value)
    if (pageNum && pageNum >= 1 && pageNum <= props.complianceReports.pagination.last_page) {
        changePage(pageNum)
        jumpToPageInput.value = ''
    }
}

const getUsageBadgeClass = (usage) => {
    switch (usage) {
        case 'DAILY':
            return 'bg-green-500 text-white'
        case 'WEEKLY':
            return 'bg-blue-500 text-white'
        case 'MONTHLY':
            return 'bg-purple-500 text-white'
        default:
            return 'bg-gray-300 text-gray-700'
    }
}

const getDaysClass = (days) => {
    if (days === 999) return 'text-red-600'
    if (days >= 30) return 'text-red-600'
    if (days >= 14) return 'text-orange-600'
    if (days >= 7) return 'text-yellow-600'
    return 'text-gray-700'
}

const formatDate = (dateString) => {
    if (!dateString) return 'N/A'

    const date = new Date(dateString)
    const today = new Date()
    const yesterday = new Date(today)
    yesterday.setDate(yesterday.getDate() - 1)

    const dateOnly = new Date(date.getFullYear(), date.getMonth(), date.getDate())
    const todayOnly = new Date(today.getFullYear(), today.getMonth(), today.getDate())
    const yesterdayOnly = new Date(yesterday.getFullYear(), yesterday.getMonth(), yesterday.getDate())

    if (dateOnly.getTime() === todayOnly.getTime()) {
        return 'Today'
    } else if (dateOnly.getTime() === yesterdayOnly.getTime()) {
        return 'Yesterday'
    } else {
        const day = String(date.getDate()).padStart(2, '0')
        const month = String(date.getMonth() + 1).padStart(2, '0')
        const year = date.getFullYear()
        return `${day}/${month}/${year}`
    }
}
</script>
