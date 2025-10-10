<template>
    <div class="w-full px-2 sm:px-4">
        <FilterBar v-model:searchTerm="searchTerm" v-model:selectedDate="selectedDate" v-model:selectedPIC="selectedPIC"
            :picOptions="uniquePICs" @clear="clearFilters" />


        <DataTable :items="paginatedItems" :uncheckedCount="uncheckedCount" @submit="submitDailyStock"
            @delete="deleteInput" />

        <Pagination v-if="totalPages > 1" v-model:currentPage="currentPage" :totalPages="totalPages"
            :startItem="startItem" :endItem="endItem" :totalItems="totalItems" :visiblePages="visiblePages" />
    </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'
import FilterBar from '@/Components/FilterBar.vue'
import DataTable from '@/Components/DailyInputTable.vue'
import Pagination from '@/Components/Pagination.vue'
import { useDailyInput, } from '@/Composeables/useDailyInput'

const {
    searchTerm,
    selectedDate,
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
    deleteInput
} = useDailyInput()

let intervalId = null

onMounted(() => {
    fetchData()
    intervalId = setInterval(fetchData, 10000)
})

onUnmounted(() => {
    if (intervalId) clearInterval(intervalId)
})
</script>
