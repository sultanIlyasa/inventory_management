<template>
    <div class="flex flex-col gap-2 mb-3">
        <div class="flex flex-col sm:flex-row gap-2">
            <!-- Search Input -->
            <input :value="searchTerm" @input="$emit('update:searchTerm', $event.target.value)" type="search"
                placeholder="Search material, description or PIC..."
                class="flex-1 px-3 py-2 text-sm rounded border focus:outline-none focus:ring-2 focus:ring-blue-500" />

            <div class="flex gap-2">
                <!-- Date Input -->
                <input :value="selectedDate" @change="$emit('update:selectedDate', $event.target.value)" type="date"
                    class="flex-1 sm:flex-none px-3 py-2 text-sm rounded border focus:outline-none focus:ring-2 focus:ring-blue-500" />

                <!-- Clear Button -->
                <button @click="$emit('clear')"
                    class="px-3 py-2 bg-gray-100 rounded border text-sm hover:bg-gray-200 transition">
                    Clear
                </button>
            </div>

            <!-- PIC Filter -->
            <select :value="selectedPIC" @change="$emit('update:selectedPIC', $event.target.value)"
                class="flex-1 sm:flex-none px-3 py-2 text-sm rounded border focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white min-w-[150px]">
                <option value="">All PICs</option>
                <option v-for="pic in picOptions" :key="pic" :value="pic">{{ pic }}</option>
            </select>
        </div>


    </div>
</template>

<script setup>
defineProps({
    searchTerm: {
        type: String,
        required: true
    },
    selectedDate: {
        type: String,
        required: true
    },
    selectedPIC: {
        type: String,
        required: true
    },
    picOptions: {
        type: Array,
        default: () => []
    },
    uncheckedCount: {
        type: Number,
        default: 0
    },
    showUncheckedButton: {
        type: Boolean,
        default: true
    }
})

defineEmits([
    'update:searchTerm',
    'update:selectedDate',
    'update:selectedPIC',
    'clear',
    'openMissing'
])
</script>
