<template>
    <div v-if="isLoading" class="absolute inset-0 bg-white/70 flex items-center justify-center z-10 h-full">
        <div class="text-center">
            <div class="inline-block animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mb-3"></div>
            <p class="text-sm text-gray-600 font-medium">Loading data...</p>
        </div>
    </div>
    <div class="space-y-3 sm:space-y-4">
        <!-- Search Bar -->
        <SearchBar v-model:searchTerm="searchTerm" @clear="clearFilters" />

        <!-- Filters and Actions Container -->
        <div class="flex flex-col lg:flex-row gap-3 lg:gap-4">
            <!-- Filterbar takes full width on mobile, flex on desktop -->
            <div class="flex-1 w-full lg:w-auto">
                <Filterbar v-model:selectedPIC="selectedPIC" :picOptions="uniquePICs"
                    v-model:selectedLocation="selectedLocation" v-model:selectedUsage="selectedUsage" :usageOptions="usages"
                    v-model:selectedGentani="selectedGentani" v-model:selectedDate="selectedDate" />
            </div>

            <!-- Action Buttons - Stack on mobile, side by side on tablet+ -->
            <div class="flex flex-col sm:flex-row gap-2 lg:gap-3 w-full lg:w-auto lg:min-w-fit">
                <button
                    class="w-full sm:w-auto px-4 py-2.5 sm:py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 active:bg-blue-700 transition-colors font-medium text-sm sm:text-base shadow-sm hover:shadow-md"
                    @click="handleRefresh">
                    <span class="flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh Data
                    </span>
                </button>
                <button
                    class="w-full sm:w-auto px-4 py-2.5 sm:py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 active:bg-green-800 transition-colors font-medium text-sm sm:text-base shadow-sm hover:shadow-md"
                    @click="downloadExcel">
                    <span class="flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Download Data
                    </span>
                </button>
            </div>
        </div>

        <!-- Data Table -->
        <DailyInputTable :items="paginatedItems" :uncheckedCount="uncheckedCount" :stats="stats" v-model:sortOrder="sortOrder"
            @submit="submitDailyStock" @delete="deleteInput" :startItem="startItem" />

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="mt-4">
            <Pagination v-model:currentPage="currentPage" :totalPages="totalPages"
                :startItem="startItem" :endItem="endItem" :totalItems="totalItems" :visiblePages="visiblePages" />
        </div>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import Pagination from '@/Components/Pagination.vue'
import { useDailyInput, } from '@/Composeables/useDailyInput'
import DailyInputTable from '@/Components/DailyInputTable.vue'
import SearchBar from '@/Components/SearchBar.vue'
import Filterbar from './Filterbar.vue'

const {
    searchTerm,
    selectedDate,
    selectedUsage,
    usages,
    selectedLocation,
    selectedPIC,
    currentPage,
    uniquePICs,
    uncheckedCount,
    stats,
    paginatedItems,
    totalPages,
    startItem,
    endItem,
    totalItems,
    visiblePages,
    fetchDailyData,
    clearFilters,
    submitDailyStock,
    deleteInput,
    sortOrder,
    gentaniItems,
    selectedGentani,
    isLoading

} = useDailyInput()

let intervalId = null

const handleRefresh = async () => {
    isLoading.value = true
    try {
        await fetchDailyData()
    } finally {
        isLoading.value = false
    }
}

const downloadExcel = () => {
    const params = new URLSearchParams()

    if (selectedDate.value) {
        params.append('date', selectedDate.value)
    }
    if (selectedUsage.value) {
        params.append('usage', selectedUsage.value)
    }
    if (selectedLocation.value) {
        params.append('location', selectedLocation.value)
    }

    const url = `/api/daily-input/export?${params.toString()}`
    window.location.href = url
}

onMounted(async () => {
    fetchDailyData()  // Wait for data to load

})
onUnmounted(() => {
    if (intervalId) clearInterval(intervalId)
})
</script>
