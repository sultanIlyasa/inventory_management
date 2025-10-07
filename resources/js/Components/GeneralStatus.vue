<template>
    <div class="bg-white rounded-xl shadow-md p-4">
        <h2 class="text-lg font-semibold mb-2 text-center">General Status</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2 border">PIC</th>
                        <th class="p-2 border bg-yellow-300">NOT CHECKED</th>
                        <th class="p-2 border bg-yellow-200">ROP</th>
                        <th class="p-2 border bg-red-300 text-white">SHORTAGE</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in statusData" :key="item.pic_name" class="text-center">
                        <td class="border p-2 bg-gray-100 font-semibold">{{ item.pic_name }}</td>
                        <td class="border p-2 bg-yellow-100">{{ item.not_checked }}</td>
                        <td class="border p-2 bg-yellow-50">{{ item.rop }}</td>
                        <td class="border p-2 bg-red-100">{{ item.shortage }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const statusData = ref([])

onMounted(async () => {
    try {
        const res = await axios.get('/api/daily-input/status')
        statusData.value = res.data.data || []
    } catch (err) {
        console.error(err)
    }
})
</script>
