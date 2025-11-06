<template>
    <!-- PIC Filter -->
    <select :value="selectedPIC" @change="$emit('update:selectedPIC', $event.target.value)"
        class="flex-1 sm:flex-none px-3 py-2 text-sm rounded border focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white min-w-[150px]">
        <option value="">All PICs</option>
        <option v-for="pic in picOptions" :key="pic" :value="pic">{{ pic }}</option>
    </select>
    <select :value="selectedLocation" @change="$emit('update:selectedLocation', $event.target.value)"
        class="flex-1 sm:flex-none px-3 py-2 text-sm rounded border focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white min-w-[150px]">
        <option value="">All Location</option>
        <option v-for="location in locationOptions" :key="location" :value="location">{{ location }}</option>
    </select>
    <select :value="selectedUsage" @change="$emit('update:selectedUsage', $event.target.value)"
        class="flex-1 sm:flex-none px-3 py-2 text-sm rounded border focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white min-w-[150px]">
        <option value="">All Usage</option>
        <option v-for="usage in usageOptions" :key="usage" :value="usage">{{ usage }}</option>
    </select>


</template>

<script setup>
defineProps({

    selectedPIC: {
        type: String,
        required: true
    },
    selectedUsage: {
        type: String,
        required: true
    },
    selectedLocation: {
        type: String,
        required: true
    },
    picOptions: {
        type: Array,
        default: () => []
    },
    usageOptions: {
        type: Array,
        default: () => []
    },
    locationOptions: {
        type: Array,
        default: () => []
    }
})

defineEmits([
    'update:selectedPIC',
    'update:selectedLocation',
    'update:selectedUsage'
])
</script>
