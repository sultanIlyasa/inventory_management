<template>
    <div v-if="isLoading" class="absolute inset-0 bg-white/70 flex items-center justify-center z-10 h-full">
        <div class="text-center">
            <div class="inline-block animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mb-3"></div>
            <p class="text-sm text-gray-600 font-medium">Loading data...</p>
        </div>
    </div>
    <div class="">
        <SearchBar v-model:searchTerm="searchTerm" @clear="clearFilters" />
        <div class="flex flex-col sm:flex-row gap-3">
            <Filterbar v-model:selectedPIC="selectedPIC" :picOptions="uniquePICs"
                v-model:selectedLocation="selectedLocation" v-model:selectedUsage="selectedUsage" :usageOptions="usages"
                v-model:selectedGentani="selectedGentani" v-model:selectedDate="selectedDate" />
            <button class="px-4 py-2 border bg-blue-500 rounded-lg text-white" @click="handleRefresh">
                Refresh data
            </button>
        </div>
        <DailyInputTable :items="paginatedItems" :uncheckedCount="uncheckedCount" v-model:sortOrder="sortOrder"
            @submit="submitDailyStock" @delete="deleteInput" :startItem="startItem" />
        <Pagination v-if="totalPages > 1" v-model:currentPage="currentPage" :totalPages="totalPages"
            :startItem="startItem" :endItem="endItem" :totalItems="totalItems" :visiblePages="visiblePages" />
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

onMounted(async () => {
    fetchDailyData()  // Wait for data to load

})
onUnmounted(() => {
    if (intervalId) clearInterval(intervalId)
})
</script>
