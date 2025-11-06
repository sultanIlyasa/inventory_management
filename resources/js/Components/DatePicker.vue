<template>
    <div class="flex items-center space-x-2">
        <flat-pickr v-model="internalDate" :config="config"
            class="flex-1 sm:flex-none px-3 py-2 text-sm rounded border focus:outline-none focus:ring-2 focus:ring-blue-500 w-full"
            placeholder="Select a date" />
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import FlatPickr from 'vue-flatpickr-component'
import 'flatpickr/dist/flatpickr.css'


// Props and Emits
const props = defineProps({
    selectedDate: String
})
const emit = defineEmits(['update:selectedDate'])

// Local state
const internalDate = ref(props.selectedDate)

// Watcher to sync parent and local state
watch(internalDate, (val) => {
    emit('update:selectedDate', val)
})

// Disable weekends using flatpickr config
const config = {
    dateFormat: 'Y-m-d',
    disable: [
        function (date) {
            // Disable Saturday (6) and Sunday (0)
            return date.getDay() === 0 || date.getDay() === 6
        }
    ]
}
</script>
