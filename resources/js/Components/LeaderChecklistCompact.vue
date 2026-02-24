<template>
    <div class="bg-white rounded-lg shadow-lg p-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-gray-800">📋 Today's Checklist</h2>
            <a :href="route('warehouse-monitoring.leader-checklist')"
                class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                View All →
            </a>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-3 gap-2 mb-4">
            <div class="bg-green-50 p-2 rounded text-center">
                <div class="text-xl font-bold text-green-700">{{ statistics.DAILY || 0 }}</div>
                <div class="text-xs text-green-600">Daily</div>
            </div>
            <div class="bg-blue-50 p-2 rounded text-center">
                <div class="text-xl font-bold text-blue-700">{{ statistics.WEEKLY || 0 }}</div>
                <div class="text-xs text-blue-600">Weekly</div>
            </div>
            <div class="bg-purple-50 p-2 rounded text-center">
                <div class="text-xl font-bold text-purple-700">{{ statistics.MONTHLY || 0 }}</div>
                <div class="text-xs text-purple-600">Monthly</div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="text-sm text-gray-600 mt-2">Loading...</p>
        </div>

        <!-- Compact Table -->
        <div v-else class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 text-left">Material</th>
                        <th class="p-2 text-center">Type</th>
                        <th class="p-2 text-center">Days</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in topItems" :key="item.key" class="border-b hover:bg-gray-50">
                        <td class="p-2">
                            <div class="font-semibold text-gray-800">{{ item.material_number }}</div>
                            <div class="text-gray-600 truncate" style="max-width: 200px;">{{ item.description }}</div>
                        </td>
                        <td class="p-2 text-center">
                            <span :class="getUsageBadgeClass(item.usage)"
                                class="px-2 py-1 rounded text-xs font-bold inline-block">
                                {{ item.usage }}
                            </span>
                        </td>
                        <td class="p-2 text-center">
                            <span :class="getDaysClass(item.days_since_check)" class="font-bold">
                                <span v-if="item.days_since_check === 999">Never</span>
                                <span v-else>{{ item.days_since_check }}d</span>
                            </span>
                        </td>
                    </tr>

                    <!-- Empty State -->
                    <tr v-if="!topItems || topItems.length === 0">
                        <td colspan="3" class="p-6 text-center text-gray-500">
                            <div class="text-2xl mb-1">✅</div>
                            <div class="text-xs">All up to date!</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Show More Link -->
        <div v-if="topItems && topItems.length > 0" class="mt-3 text-center">
            <a :href="route('warehouse-monitoring.leader-checklist')"
                class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                View all {{ statistics.TOTAL || 0 }} items →
            </a>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { route } from 'ziggy-js'

const props = defineProps({
    complianceData: {
        type: Array,
        default: () => []
    },
    statistics: {
        type: Object,
        default: () => ({ DAILY: 0, WEEKLY: 0, MONTHLY: 0, TOTAL: 0 })
    },
    isLoading: {
        type: Boolean,
        default: false
    },
    limit: {
        type: Number,
        default: 5
    }
})

const topItems = computed(() => props.complianceData.slice(0, props.limit))

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
</script>
