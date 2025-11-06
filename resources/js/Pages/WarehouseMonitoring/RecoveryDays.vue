<template>
    <MainAppLayout>
        <!-- Filters Section -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                    <input v-model="localFilters.month" type="month"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Usage</label>
                    <select v-model="localFilters.usage"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All</option>
                        <option value="DAILY">Daily</option>
                        <option value="WEEKLY">Weekly</option>
                        <option value="MONTHLY">Monthly</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                    <select v-model="localFilters.location"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All</option>
                        <option value="SUNTER_1">Sunter 1</option>
                        <option value="SUNTER_2">Sunter 2</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button @click="applyFilters"
                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                        Apply
                    </button>
                    <button @click="clearFilters"
                        class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm font-medium">
                        Clear
                    </button>
                </div>
            </div>
        </div>
        <RecoveryDaysReport :limit="4" :show-chart="true" />
    </MainAppLayout>

</template>


<script setup>
import MainAppLayout from '@/Layouts/MainAppLayout.vue';
import RecoveryDaysReport from '@/Components/RecoveryDaysReport.vue';

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({})
    }
})

const localFilters = ref({
    month: props.filters.month || '',
    usage: props.filters.usage || '',
    location: props.filters.location || ''
})
</script>
