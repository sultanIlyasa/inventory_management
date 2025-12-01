<template>
    <div class="">
        <h1 class="flex text-lg justify-center font-bold mt-1" :class="variant === 'COMPACT' ? 'mb-1' : 'mb-7'">
            Status Overdue Monitor
        </h1>

        <SearchBar v-model:searchTerm="localFilters.search" @clear="handleClear" :variant="variant" />

        <!-- Counters and Filters -->
        <div :class="['flex gap-4', counterScale]">
            <div class="bg-red-100 text-red-700 px-4 py-2 rounded shadow font-bold">
                SHORTAGE: {{ statusCounts.SHORTAGE }}
            </div>
            <div class="bg-orange-500 text-yellow-100 px-4 py-2 rounded shadow font-bold">
                CAUTION: {{ statusCounts.CAUTION }}
            </div>
            <div class="bg-blue-100 text-blue-700 px-4 py-2 rounded shadow font-bold">
                OVERFLOW: {{ statusCounts.OVERFLOW }}
            </div>

            <PICSwitch v-model:selectedPIC="localFilters.pic" :picOptions="filterOptions.pics"
                v-model:selectedLocation="localFilters.location" :locationOptions="filterOptions.locations"
                v-model:selectedUsage="localFilters.usage" :usageOptions="filterOptions.usages"
                v-model:selectedGentani="localFilters.gentani" :gentaniOptions="gentaniItems" />
        </div>

        <!-- Sort Info Display -->
        <div v-if="variant !== 'COMPACT'" class="my-2 text-sm text-gray-600">
            <span class="font-medium">Sorting by:</span>
            <span class="capitalize">{{ localFilters.sortField }}</span>
            <span class="ml-1">({{ localFilters.sortDirection === 'desc' ? 'High to Low' : 'Low to High' }})</span>
        </div>

        <div class="relative">
            <div v-if="isLoading" class="absolute inset-0 bg-white/70 flex items-center justify-center z-10">
                <div class="text-center">
                    <div class="inline-block animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mb-3"></div>
                    <p class="text-sm text-gray-600 font-medium">Loading data...</p>
                </div>
            </div>

            <!-- Table -->
            <ReportsStatusTrackerTable :items="reports" :variant="variant" :sort-field="localFilters.sortField"
                :sort-direction="localFilters.sortDirection" @sort-change="handleSortChange" />
        </div>

        <!-- Pagination -->
        <Pagination v-if="totalPages > 1" v-model:currentPage="currentPage" :totalPages="totalPages"
            :startItem="startItem" :endItem="endItem" :totalItems="totalReports" :visiblePages="visiblePages"
            @update:currentPage="handlePageChange" />
    </div>
</template>

<script setup>
import { computed, ref, watch, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import Pagination from '@/Components/Pagination.vue'
import SearchBar from './SearchBar.vue'
import PICSwitch from '@/Components/PICSwitch.vue'
import ReportsStatusTrackerTable from './ReportsStatusTrackerTable.vue'

const props = defineProps({
    overdueReports: {
        type: Object,
        required: true
    },
    statistics: {
        type: Object,
        default: () => ({})
    },
    filterOptions: {
        type: Object,
        default: () => ({})
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    variant: {
        type: String,
        default: 'FULL'
    }
})

const defaultPagination = () => ({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
})

const sanitizeFilters = (incoming = {}) => ({
    search: incoming.search ?? '',
    pic: incoming.pic ?? '',
    location: incoming.location ?? '',
    usage: incoming.usage ?? '',
    gentani: incoming.gentani ?? '',
    status: incoming.status ?? '',
    sortField: incoming.sortField ?? 'status',
    sortDirection: incoming.sortDirection ?? 'desc',
    month: incoming.month ?? ''
})

const reports = ref(props.overdueReports.data || [])
const pagination = ref(props.overdueReports.pagination || defaultPagination())
const statistics = ref(props.statistics || {})
const filterOptions = ref({
    pics: props.filterOptions.pics || [],
    locations: props.filterOptions.locations || [],
    usages: props.filterOptions.usages || [],
    gentani: props.filterOptions.gentani || []
})

const localFilters = ref(sanitizeFilters(props.filters))
const currentPage = ref(pagination.value.current_page || 1)
const isLoading = ref(false)
const isSyncingFromServer = ref(false)
let searchDebounce = null

const counterScale = computed(() => props.variant === 'COMPACT' ? 'hidden' : 'scale-100 my-4')

const statusCounts = computed(() => ({
    SHORTAGE: statistics.value.SHORTAGE || 0,
    CAUTION: statistics.value.CAUTION || 0,
    OVERFLOW: statistics.value.OVERFLOW || 0,
    OK: statistics.value.OK || 0,
    UNCHECKED: statistics.value.UNCHECKED || 0
}))

const gentaniItems = computed(() => filterOptions.value.gentani && filterOptions.value.gentani.length
    ? filterOptions.value.gentani
    : ['GENTAN-I', 'NON_GENTAN-I'])

const totalPages = computed(() => pagination.value.last_page || 1)
const totalReports = computed(() => pagination.value.total || 0)
const startItem = computed(() => totalReports.value === 0 ? 0 : (currentPage.value - 1) * pagination.value.per_page + 1)
const endItem = computed(() => Math.min(currentPage.value * pagination.value.per_page, totalReports.value))
const visiblePages = computed(() => {
    const pages = []
    const maxVisible = 5
    let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2))
    let end = Math.min(totalPages.value, start + maxVisible - 1)

    if (end - start + 1 < maxVisible) {
        start = Math.max(1, end - maxVisible + 1)
    }

    for (let i = start; i <= end; i++) {
        pages.push(i)
    }

    return pages
})

const syncFromPageProps = (pageProps) => {
    isSyncingFromServer.value = true
    reports.value = pageProps.overdueReports?.data || []
    pagination.value = pageProps.overdueReports?.pagination || defaultPagination()
    statistics.value = pageProps.statistics || {}
    filterOptions.value = {
        pics: pageProps.filterOptions?.pics || [],
        locations: pageProps.filterOptions?.locations || [],
        usages: pageProps.filterOptions?.usages || [],
        gentani: pageProps.filterOptions?.gentani || []
    }
    currentPage.value = pagination.value.current_page || 1
    localFilters.value = sanitizeFilters(pageProps.filters)
    nextTick(() => {
        isSyncingFromServer.value = false
    })
}

const fetchData = (page = 1) => {
    if (isLoading.value) return

    isLoading.value = true

    router.get(
        route('warehouse-monitoring.overdue-days'),
        {
            ...localFilters.value,
            page
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['overdueReports', 'statistics', 'filterOptions', 'filters'],
            onSuccess: (pageData) => {
                syncFromPageProps(pageData.props)
                isLoading.value = false
            },
            onError: () => {
                isLoading.value = false
            }
        }
    )
}

const applyFilters = () => {
    currentPage.value = 1
    fetchData(1)
}

const handlePageChange = (page) => {
    currentPage.value = page
    fetchData(page)
}

const handleSortChange = ({ field, direction }) => {
    localFilters.value.sortField = field
    localFilters.value.sortDirection = direction
    applyFilters()
}

const handleClear = () => {
    localFilters.value.search = ''
    localFilters.value.pic = ''
    localFilters.value.location = ''
    localFilters.value.usage = ''
    localFilters.value.gentani = ''
    localFilters.value.status = ''
    currentPage.value = 1
    fetchData(1)
}

watch(
    () => [
        localFilters.value.pic,
        localFilters.value.location,
        localFilters.value.usage,
        localFilters.value.gentani,
        localFilters.value.status
    ],
    () => {
        if (isSyncingFromServer.value) return
        currentPage.value = 1
        fetchData(1)
    }
)

watch(
    () => localFilters.value.search,
    () => {
        if (isSyncingFromServer.value) return
        clearTimeout(searchDebounce)
        searchDebounce = setTimeout(() => {
            currentPage.value = 1
            fetchData(1)
        }, 400)
    }
)

watch(
    () => props.overdueReports,
    (newVal) => {
        if (newVal) {
            reports.value = newVal.data || []
            pagination.value = newVal.pagination || defaultPagination()
            currentPage.value = pagination.value.current_page || 1
        }
    }
)

watch(
    () => props.statistics,
    (newVal) => {
        statistics.value = newVal || {}
    }
)

watch(
    () => props.filterOptions,
    (newVal) => {
        filterOptions.value = {
            pics: newVal?.pics || [],
            locations: newVal?.locations || [],
            usages: newVal?.usages || [],
            gentani: newVal?.gentani || []
        }
    }
)

watch(
    () => props.filters,
    (newVal) => {
        isSyncingFromServer.value = true
        localFilters.value = sanitizeFilters(newVal)
        nextTick(() => {
            isSyncingFromServer.value = false
        })
    }
)
</script>
