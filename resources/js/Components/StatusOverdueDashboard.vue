<template>
    <div class=" border rounded-lg shadow-lg p-4 max-h-108">
        <h1 class="flex text-lg justify-center font-bold mt-1 " :class="variant === 'COMPACT' ? 'mb-1 ' : 'mb-7'">
            Status
            Overdue Monitor</h1>
        <SearchBar v-model:searchTerm="searchTerm" :variant="variant" />
        <!-- Caution, Overflow, Shortage Counter -->
        <div :class="['flex  gap-4', counterScale]">
            <div class="bg-red-100 text-red-700 px-4 py-2 rounded shadow font-bold">
                SHORTAGE: {{allReports.filter(item => item.status === 'SHORTAGE').length}}
            </div>
            <div class="bg-orange-500 text-yellow-100 px-4 py-2 rounded shadow font-bold">
                CAUTION: {{allReports.filter(item => item.status === 'CAUTION').length}}
            </div>
            <div class="bg-blue-100 text-blue-700 px-4 py-2 rounded shadow font-bold">
                OVERFLOW: {{allReports.filter(item => item.status === 'OVERFLOW').length}}
            </div>

            <PICSwitch v-model:selectedPIC="selectedPIC" :picOptions="uniquePICs" />
        </div>
        <div :class="[, variant === 'COMPACT' ? 'hidden' : 'unhidden']">
            <button class="px-2 py-1">
                More Information
            </button>
        </div>
        <ReportsStatusTrackerTable :items="paginatedItems" :variant="variant" />
        <Pagination v-if="totalPages > 1" v-model:currentPage="currentPage" :totalPages="totalPages"
            :startItem="startItem" :endItem="endItem" :totalItems="totalReports" :visiblePages="visiblePages"
            :variant="variant" />
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, computed } from 'vue';
import { useStatusOverdue } from '@/Composeables/useStatusOverdue';
import Pagination from '@/Components/Pagination.vue'
import SearchBar from './SearchBar.vue';
import PICSwitch from '@/Components/PICSwitch.vue';
import ReportsStatusTrackerTable from './ReportsStatusTrackerTable.vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'FULL'
    }
})


const {
    allReports,
    totalReports,
    searchTerm,
    selectedPIC,
    currentPage,
    uniquePICs,
    paginatedItems,
    totalPages,
    startItem,
    endItem,
    visiblePages,
    fetchDataCurrent,
} = useStatusOverdue();
let intervalId = null

const counterScale = computed(() => props.variant === 'COMPACT' ? 'hidden' : 'scale-100 my-4')


onMounted(() => {
    fetchDataCurrent()
    intervalId = setInterval(fetchDataCurrent, 10000)
})

onUnmounted(() => {
    if (intervalId) clearInterval(intervalId)
})

</script>
