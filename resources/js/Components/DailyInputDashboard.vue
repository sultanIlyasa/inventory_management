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
            <DatePicker v-model:selectedDate="selectedDate" />
            <PICSwitch v-model:selectedPIC="selectedPIC" :picOptions="uniquePICs"
                v-model:selectedLocation="selectedLocation" :locationOptions="locations"
                v-model:selectedUsage="selectedUsage" :usageOptions="usages" v-model:selectedGentani="selectedGentani"
                :gentaniOptions="gentaniItems" />
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
import DatePicker from '@/Components/DatePicker.vue'
import Pagination from '@/Components/Pagination.vue'
import { useDailyInput, } from '@/Composeables/useDailyInput'
import PICSwitch from '@/Components/PICSwitch.vue'
import DailyInputTable from '@/Components/DailyInputTable.vue'
import SearchBar from '@/Components/SearchBar.vue'

const {
    searchTerm,
    selectedDate,
    selectedUsage,
    usages,
    selectedLocation,
    locations,
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
    fetchData,
    clearFilters,
    submitDailyStock,
    deleteInput,
    sortOrder,
    gentaniItems,
    selectedGentani
} = useDailyInput()

let intervalId = null

const isLoading = ref(false)
const handleRefresh = async () => {
    isLoading.value = true
    try {
        await fetchData()
    } catch (error) {
        console.log(error)
    } finally {
        isLoading.value = false
    }
}

onMounted(async () => {
    fetchData()  // Wait for data to load

})
onUnmounted(() => {
    if (intervalId) clearInterval(intervalId)
})
</script>
