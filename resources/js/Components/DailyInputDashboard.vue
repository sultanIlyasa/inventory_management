<template>
    <div class="w-full px-2 sm:px-4">
        <SearchBar v-model:searchTerm="searchTerm" @clear="clearFilters" />
        <div class="flex flex-col sm:flex-row gap-3">
            <DatePicker v-model:selectedDate="selectedDate" />
            <PICSwitch v-model:selectedPIC="selectedPIC" :picOptions="uniquePICs"
                v-model:selectedLocation="selectedLocation" :locationOptions="locations"
                v-model:selectedUsage="selectedUsage" :usageOptions="usages" />
        </div>
        <DailyInputTable :items="paginatedItems" :uncheckedCount="uncheckedCount" v-model:sortOrder="sortOrder"
            @submit="submitDailyStock" @delete="deleteInput" />
        <Pagination v-if="totalPages > 1" v-model:currentPage="currentPage" :totalPages="totalPages"
            :startItem="startItem" :endItem="endItem" :totalItems="totalItems" :visiblePages="visiblePages" />
    </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'
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
} = useDailyInput()

let intervalId = null

// In DailyInputDashboard.vue, add this in onMounted
// In DailyInputDashboard.vue
onMounted(async () => {
    await fetchData()  // Wait for data to load

    // Now log after data is loaded
    console.log('locations:', locations.value)
    console.log('usages:', usages.value)
    console.log('uniquePICs:', uniquePICs.value)
    console.log('allItems:', filteerd.value)

    intervalId = setInterval(fetchData, 10000)
})
onUnmounted(() => {
    if (intervalId) clearInterval(intervalId)
})
</script>
