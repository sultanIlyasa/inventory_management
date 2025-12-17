<template>
    <!-- PIC -->
    <select :value="selectedPIC" @change="$emit('update:selectedPIC', $event.target.value)" class="filter">
        <option value="">All PICs</option>
        <option v-for="pic in picOptions" :key="pic" :value="pic">{{ pic }}</option>
    </select>

    <!-- LOCATION -->
    <select :value="selectedLocation" @change="$emit('update:selectedLocation', $event.target.value)" class="filter">
        <option value="">All Location</option>
        <option value="SUNTER_1">Sunter 1</option>
        <option value="SUNTER_2">Sunter 2</option>
    </select>

    <!-- USAGE -->
    <select :value="selectedUsage" @change="$emit('update:selectedUsage', $event.target.value)" class="filter">
        <option value="">All Usage</option>
        <option value="DAILY">Daily</option>
        <option value="WEEKLY">Weekly</option>
        <option value="MONTHLY">Monthly</option>
    </select>

    <!-- GENTANI -->
    <select :value="selectedGentani" @change="$emit('update:selectedGentani', $event.target.value)" class="filter">
        <option value="">All</option>
        <option value="GENTAN-I">Gentan-I</option>
        <option value="NON_GENTAN-I">Non Gentan-I</option>
    </select>

    <!-- DATE PICKERS -->
    <div class="space-y-2">

        <!-- DAILY -->
        <div v-if="selectedUsage === 'DAILY' || selectedUsage === ''">
            <FlatPickr :model-value="selectedDate" @change="$emit('update:selectedDate', $event.target.value)"
                class="picker" />
        </div>

        <!-- WEEKLY -->
        <div v-else-if="selectedUsage === 'WEEKLY'" class="space-y-2">
            <div class="flex gap-2">
                <button @click="setThisWeek" class="quick-btn px-2 py-1 bg-blue-400 text-white rounded-lg  ">This
                    Week</button>
                <button @click="setLastWeek" class="quick-btn px-2 py-1 bg-blue-400 text-white rounded-lg ">Last
                    Week</button>
            </div>

            <FlatPickr :model-value="selectedDate" @change="$emit('update:selectedDate', $event.target.value)"
                class="picker" />

            <p v-if="weekLabel" class="text-xs text-gray-500 italic">{{ weekLabel }}</p>
        </div>

        <!-- MONTHLY -->
        <div v-else-if="selectedUsage === 'MONTHLY'">
            <FlatPickr :model-value="selectedDate" @change="$emit('update:selectedDate', $event.target.value)"
                class="picker" />

            <p v-if="monthLabel" class="text-xs text-gray-500 italic">{{ monthLabel }}</p>
        </div>

    </div>
</template>

<script setup>
import { computed } from "vue";
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
