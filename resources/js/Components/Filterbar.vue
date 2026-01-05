<template>
    <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-1 sm:p-2 shadow-sm border border-gray-200">
        <!-- Header with Toggle Button -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                <h3 class="text-sm sm:text-base font-semibold text-gray-700">Filters</h3>

                <!-- Active Filters Count Badge -->
                <span v-if="activeFiltersCount > 0"
                    class="px-2 py-0.5 bg-blue-600 text-white text-xs font-bold rounded-full">
                    {{ activeFiltersCount }}
                </span>
            </div>

            <!-- Toggle Button -->
            <button @click="isExpanded = !isExpanded"
                class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-white rounded-lg transition-all duration-200"
                :aria-expanded="isExpanded" aria-label="Toggle filters">
                <span class="hidden sm:inline">{{ isExpanded ? 'Collapse' : 'Expand' }}</span>
                <svg class="w-5 h-5 transition-transform duration-300" :class="{ 'rotate-180': isExpanded }" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
        </div>

        <!-- Collapsible Filter Content -->
        <transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-[800px]" leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="opacity-100 max-h-[800px]" leave-to-class="opacity-0 max-h-0">
            <div v-show="isExpanded" class="overflow-hidden">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
                    <!-- PIC Filter -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-medium text-gray-600 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            PIC
                        </label>
                        <select :value="selectedPIC" @change="$emit('update:selectedPIC', $event.target.value)"
                            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all hover:border-gray-400">
                            <option value="">All PICs</option>
                            <option v-for="pic in picOptions" :key="pic" :value="pic">{{ pic }}</option>
                        </select>
                    </div>

                    <!-- LOCATION Filter -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-medium text-gray-600 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Location
                        </label>
                        <select :value="selectedLocation"
                            @change="$emit('update:selectedLocation', $event.target.value)"
                            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all hover:border-gray-400">
                            <option value="">All Locations</option>
                            <option value="SUNTER_1">Sunter 1</option>
                            <option value="SUNTER_2">Sunter 2</option>
                        </select>
                    </div>

                    <!-- USAGE Filter -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-medium text-gray-600 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Usage Period
                        </label>
                        <select :value="selectedUsage" @change="$emit('update:selectedUsage', $event.target.value)"
                            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all hover:border-gray-400">
                            <option value="">All Usage</option>
                            <option value="DAILY">Daily</option>
                            <option value="WEEKLY">Weekly</option>
                            <option value="MONTHLY">Monthly</option>
                        </select>
                    </div>

                    <!-- GENTANI Filter -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-medium text-gray-600 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            Gentan-I
                        </label>
                        <select :value="selectedGentani" @change="$emit('update:selectedGentani', $event.target.value)"
                            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all hover:border-gray-400">
                            <option value="">All</option>
                            <option value="GENTAN-I">Gentan-I</option>
                            <option value="NON_GENTAN-I">Non Gentan-I</option>
                        </select>
                    </div>

                    <!-- DATE PICKER -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-medium text-gray-600 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Date
                        </label>

                        <!-- DAILY -->
                        <div v-if="selectedUsage === 'DAILY' || selectedUsage === ''">
                            <FlatPickr :model-value="selectedDate"
                                @change="$emit('update:selectedDate', $event.target.value)"
                                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all hover:border-gray-400" />
                        </div>

                        <!-- WEEKLY -->
                        <div v-else-if="selectedUsage === 'WEEKLY'" class="space-y-2">
                            <div class="flex gap-2">
                                <button @click="setThisWeek"
                                    class="flex-1 px-2 py-1.5 bg-blue-500 text-white rounded-lg text-xs font-medium hover:bg-blue-600 active:bg-blue-700 transition-colors shadow-sm">
                                    This Week
                                </button>
                                <button @click="setLastWeek"
                                    class="flex-1 px-2 py-1.5 bg-blue-500 text-white rounded-lg text-xs font-medium hover:bg-blue-600 active:bg-blue-700 transition-colors shadow-sm">
                                    Last Week
                                </button>
                            </div>
                            <FlatPickr :model-value="selectedDate"
                                @change="$emit('update:selectedDate', $event.target.value)"
                                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all hover:border-gray-400" />
                            <p v-if="weekLabel" class="text-xs text-gray-600 italic bg-blue-50 px-2 py-1 rounded">{{
                                weekLabel }}</p>
                        </div>

                        <!-- MONTHLY -->
                        <div v-else-if="selectedUsage === 'MONTHLY'" class="space-y-2">
                            <FlatPickr :model-value="selectedDate"
                                @change="$emit('update:selectedDate', $event.target.value)"
                                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all hover:border-gray-400" />
                            <p v-if="monthLabel" class="text-xs text-gray-600 italic bg-blue-50 px-2 py-1 rounded">{{
                                monthLabel }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { computed, ref } from "vue";
import FlatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import monthSelectPlugin from "flatpickr/dist/plugins/monthSelect";
import "flatpickr/dist/plugins/monthSelect/style.css";

const props = defineProps({
    selectedDate: String,
    selectedUsage: String,
    selectedPIC: String,
    selectedLocation: String,
    selectedGentani: String,
    picOptions: Array,
    usageOptions: Array,
});

const emit = defineEmits([
    "update:selectedPIC",
    "update:selectedLocation",
    "update:selectedUsage",
    "update:selectedGentani",
    "update:selectedDate",
]);

// Collapsible state - starts expanded by default
const isExpanded = ref(true);

// Count active filters
const activeFiltersCount = computed(() => {
    let count = 0;
    if (props.selectedPIC) count++;
    if (props.selectedLocation) count++;
    if (props.selectedUsage) count++;
    if (props.selectedGentani) count++;
    return count;
});

const setThisWeek = () => emit("update:selectedDate", new Date());
const setLastWeek = () => {
    const d = new Date();
    d.setDate(d.getDate() - 7);
    emit("update:selectedDate", d);
};

const weekLabel = computed(() => {
    if (!props.selectedDate) return "";
    const date = new Date(props.selectedDate);

    const start = new Date(date);
    start.setDate(date.getDate() - date.getDay() + 1);

    const end = new Date(start);
    end.setDate(start.getDate() + 6);

    return `Week (${start.toLocaleDateString("id-ID")} - ${end.toLocaleDateString("id-ID")})`;
});

const monthLabel = computed(() => {
    if (!props.selectedDate) return "";
    return new Date(props.selectedDate).toLocaleDateString("id-ID", {
        month: "long",
        year: "numeric",
    });
});

const monthConfig = {
    plugins: [
        monthSelectPlugin({
            shorthand: true,
            dateFormat: "Y-m-d",
            altFormat: "F Y",
        })
    ]
};
</script>
