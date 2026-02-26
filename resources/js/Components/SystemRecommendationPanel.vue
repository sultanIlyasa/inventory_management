<template>
    <div class="rounded-xl border border-blue-200 bg-blue-50 shadow-sm">
        <div class="flex items-center justify-between gap-3 border-b border-blue-100 p-4">
            <div class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-blue-600">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2l9 4-9 4-9-4 9-4z" stroke="currentColor" stroke-width="2" />
                        <path d="M3 10l9 4 9-4" stroke="currentColor" stroke-width="2" opacity=".4" />
                        <path d="M3 14l9 4 9-4" stroke="currentColor" stroke-width="2" opacity=".25" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-blue-900">System Recommendation Action</p>
                    <p class="text-xs text-blue-700">What should I do right now?</p>
                </div>
            </div>
            <span class="text-xs font-medium text-blue-700">{{ updatedAt }}</span>
        </div>

        <div class="p-4 space-y-3">
            <!-- Immediate Action -->
            <div class="rounded-xl border border-red-200 bg-white p-3">
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-red-500"></span>
                    <p class="text-xs font-semibold uppercase tracking-wide text-red-700">Immediate Action</p>
                </div>
                <div class="mt-2">
                    <p class="text-sm font-semibold text-gray-900">{{ immediateAction.material }}</p>
                    <ul class="mt-2 list-disc pl-5 text-xs text-gray-700 space-y-1">
                        <li v-for="(a, idx) in immediateAction.actions" :key="'im-' + idx">{{ a }}</li>
                    </ul>
                </div>
            </div>

            <!-- Urgent -->
            <div class="rounded-xl border border-amber-200 bg-white p-3">
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">
                        Urgent <span class="font-medium normal-case text-amber-700/80">(Next 2 shifts)</span>
                    </p>
                </div>
                <div class="mt-2">
                    <p class="text-sm font-semibold text-gray-900">{{ urgentAction.summary }}</p>
                    <ul class="mt-2 list-disc pl-5 text-xs text-gray-700 space-y-1">
                        <li v-for="(a, idx) in urgentAction.actions" :key="'ur-' + idx">{{ a }}</li>
                    </ul>
                </div>
            </div>

            <button
                class="w-full rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 active:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="!topShortage"
                @click="topShortage && emit('view-material', topShortage)">
                {{ topShortage ? 'View Material Analysis →' : 'No shortage materials' }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

// Leaderboard item shape from LeaderboardService
type LeaderboardItem = {
    material_number: string
    description: string
    pic_name: string
    usage: string
    days: number          // distinct days in shortage/caution
    current_stock: number
}

type LBStats = {
    total: number
    average_days: number
    max_days: number
    min_days: number
}

const props = defineProps<{
    shortage?: { leaderboard?: LeaderboardItem[]; statistics?: LBStats } | null
    caution?:  { leaderboard?: LeaderboardItem[]; statistics?: LBStats } | null
    updatedAt?: string
}>()

const emit = defineEmits<{
    'view-material': [material: LeaderboardItem]
}>()

const topShortage  = computed(() => props.shortage?.leaderboard?.[0] ?? null)
const shortageStats = computed(() => props.shortage?.statistics ?? null)
const cautionStats  = computed(() => props.caution?.statistics ?? null)

const immediateAction = computed(() => {
    const top = topShortage.value
    const stats = shortageStats.value
    if (!top) {
        return {
            material: 'No shortage materials',
            actions: ['All materials are within safe stock levels'],
        }
    }
    const actions = [
        `Contact PIC: ${top.pic_name} immediately`,
        `${top.days} day${top.days !== 1 ? 's' : ''} in shortage — current stock: ${top.current_stock}`,
    ]
    if (stats && stats.total > 1) {
        actions.push(`${stats.total - 1} other shortage material${stats.total - 1 > 1 ? 's' : ''} also need attention`)
    }
    actions.push('Request emergency PO or find alternative source')
    return { material: top.description, actions }
})

const urgentAction = computed(() => {
    const stats = cautionStats.value
    const count = stats?.total ?? props.caution?.leaderboard?.length ?? 0
    if (count === 0) {
        return {
            summary: 'No caution materials',
            actions: ['Continue monitoring stock levels'],
        }
    }
    const actions: string[] = []
    if (stats?.average_days) {
        actions.push(`Average ${stats.average_days} days in caution — act before they hit shortage`)
    }
    if (stats?.max_days) {
        actions.push(`Worst case: ${stats.max_days} days — follow up on pending GR`)
    }
    actions.push('Monitor consumption rates and prepare escalation docs')
    return {
        summary: `${count} caution material${count > 1 ? 's' : ''} need attention`,
        actions,
    }
})
</script>
