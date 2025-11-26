<template>
    <div :class="[
        'bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden',
        fullWidth ? 'col-span-full' : ''
    ]">
        <!-- Card Header -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-4 py-3 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">{{ icon }}</span>
                    <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
                </div>
                <div v-if="loading" class="flex items-center gap-2 text-sm text-gray-600">
                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span>Loading...</span>
                </div>
            </div>
        </div>

        <!-- Card Body -->
        <div class="relative min-h-[200px]">
            <!-- Loading Skeleton -->
            <div v-if="loading" class="p-6">
                <div class="animate-pulse space-y-4">
                    <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                    <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                    <div class="h-4 bg-gray-200 rounded w-5/6"></div>
                    <div class="grid grid-cols-3 gap-4 mt-6">
                        <div class="h-20 bg-gray-200 rounded"></div>
                        <div class="h-20 bg-gray-200 rounded"></div>
                        <div class="h-20 bg-gray-200 rounded"></div>
                    </div>
                </div>
            </div>

            <!-- Actual Content -->
            <div v-else>
                <slot></slot>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        required: true
    },
    icon: {
        type: String,
        default: '📊'
    },
    loading: {
        type: Boolean,
        default: false
    },
    fullWidth: {
        type: Boolean,
        default: false
    }
})
</script>
