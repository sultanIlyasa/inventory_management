<template>
    <div class="w-full px-2 sm:px-4">
        <FilterBar v-model:searchTerm="searchTerm" v-model:selectedDate="selectedDate" v-model:selectedPIC="selectedPIC"
            :picOptions="uniquePICs" :uncheckedCount="uncheckedCount" @clear="clearFilters" />
        <!-- Critical, Overflow, OK, Shortage Counter -->
        <div class="flex flex-wrap gap-4 my-4">

            <div class="bg-red-100 text-red-700 px-4 py-2 rounded shadow">
                SHORTAGE: {{allReports.filter(item => item.status === 'SHORTAGE').length}}
            </div>
            <div class="bg-orange-500 text-yellow-100 px-4 py-2 rounded shadow">
                CRITICAL: {{allReports.filter(item => item.status === 'CRITICAL').length}}
            </div>
            <div class="bg-green-100 text-green-700 px-4 py-2 rounded shadow">
                OK: {{allReports.filter(item => item.status === 'OK').length}}
            </div>
            <div class="bg-blue-100 text-blue-700 px-4 py-2 rounded shadow">
                OVERFLOW: {{allReports.filter(item => item.status === 'OVERFLOW').length}}
            </div>
        </div>
        <ReportsTable :items="paginatedItems" />

        <Pagination v-if="totalPages > 1" v-model:currentPage="currentPage" :totalPages="totalPages"
            :startItem="startItem" :endItem="endItem" :totalItems="totalReports" :visiblePages="visiblePages" />

    </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';

import { useDailyInput } from '@/Composeables/useDailyInput';
import FilterBar from './FilterBar.vue';
import ReportsTable from './ReportsTable.vue';
import { useReport } from '@/Composeables/useReport';
import Pagination from '@/Components/Pagination.vue'


const {
    uniquePICs,
    uncheckedCount,
    clearFilters
} = useDailyInput();
const {
    selectedPIC,
    fetchDataCurrent,
    searchTerm,
    selectedDate,
    totalPages,
    startItem,
    endItem,
    totalReports,
    visiblePages,
    paginatedItems,
    currentPage,
    allReports
} = useReport();
let intervalId = null

onMounted(() => {
    fetchDataCurrent()
    intervalId = setInterval(fetchDataCurrent, 10000)
})

onUnmounted(() => {
    if (intervalId) clearInterval(intervalId)
})

</script>
