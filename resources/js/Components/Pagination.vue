<template>
    <div class="flex flex-col sm:flex-row items-center justify-between mt-4 gap-3">
        <!-- Entry Counter -->
        <div class="text-xs sm:text-sm text-gray-600 order-2 sm:order-1">
            Showing {{ startItem }} to {{ endItem }} of {{ totalItems }} entries
        </div>

        <!-- Pagination Buttons -->
        <div class="flex gap-1 sm:gap-2 order-1 sm:order-2 flex-wrap justify-center">
            <!-- First Button -->
            <button @click="changePage(1)" :disabled="currentPage === 1"
                class="px-2 sm:px-3 py-1 text-xs sm:text-sm border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                {{ firstButtonText }}
            </button>

            <!-- Previous Button -->
            <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1"
                class="px-2 sm:px-3 py-1 text-xs sm:text-sm border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                {{ previousButtonText }}
            </button>

            <!-- Page Number Buttons -->
            <div class="flex gap-1">
                <button v-for="page in visiblePages" :key="page" @click="changePage(page)" :class="[
                    'px-2 sm:px-3 py-1 text-xs sm:text-sm border rounded transition',
                    currentPage === page
                        ? 'bg-blue-500 text-white'
                        : 'hover:bg-gray-100'
                ]">
                    {{ page }}
                </button>
            </div>

            <!-- Next Button -->
            <button @click="changePage(currentPage + 1)" :disabled="currentPage === totalPages"
                class="px-2 sm:px-3 py-1 text-xs sm:text-sm border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                {{ nextButtonText }}
            </button>

            <!-- Last Button -->
            <button @click="changePage(totalPages)" :disabled="currentPage === totalPages"
                class="px-2 sm:px-3 py-1 text-xs sm:text-sm border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                {{ lastButtonText }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({
    currentPage: {
        type: Number,
        required: true
    },
    totalPages: {
        type: Number,
        required: true
    },
    startItem: {
        type: Number,
        required: true
    },
    endItem: {
        type: Number,
        required: true
    },
    totalItems: {
        type: Number,
        required: true
    },
    visiblePages: {
        type: Array,
        required: true
    },
    firstButtonText: {
        type: String,
        default: 'First'
    },
    previousButtonText: {
        type: String,
        default: 'Prev'
    },
    nextButtonText: {
        type: String,
        default: 'Next'
    },
    lastButtonText: {
        type: String,
        default: 'Last'
    },

})

const emit = defineEmits(['update:currentPage'])

const changePage = (page) => {
    if (page >= 1 && page <= props.totalPages) {
        emit('update:currentPage', page)
    }
}
</script>
