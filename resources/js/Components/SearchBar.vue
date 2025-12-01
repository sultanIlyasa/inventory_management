<template>
    <div class="flex flex-col gap-2 mb-2
     ">
        <div class='flex flex-col sm:flex-row gap-2'>
            <!-- Search Input -->
            <input :value="searchTerm" @input="$emit('update:searchTerm', $event.target.value)" type="search"
                placeholder="Search material, description or PIC..."
                class="flex-1 px-3 py-2 text-sm rounded border focus:outline-none focus:ring-2 focus:ring-blue-500" />
            <button @click="$emit('clear')"
                class="px-3 py-2 bg-gray-100 rounded border text-sm hover:bg-gray-200 transition">
                Clear
            </button>
            <span
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition disabled:opacity-50 text-xs sm:text-sm ">{{
                    formattedDate }}</span>

        </div>
        <!-- Clear Button -->


    </div>
</template>
compuet

<script setup>
import { computed } from 'vue'

const currentDate = new Date();
const formattedDate = currentDate.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
});

const props = defineProps({
    searchTerm: {
        type: String,
        required: true,
        default: "null"
    },

})

const searchBarScaling = computed(() => props.variant === 'COMPACT' ? 'hidden' : 'scale-100')

defineEmits([
    'update:searchTerm',
    'clear'
])
</script>
