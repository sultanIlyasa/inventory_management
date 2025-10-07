<template>

    <!-- Controls: search and date -->
    <div class="flex flex-col gap-2 mb-3">
        <div class="flex flex-col sm:flex-row gap-2">
            <input v-model="searchTerm" type="search" placeholder="Search material, description or PIC..."
                class="flex-1 px-3 py-2 text-sm rounded border focus:outline-none focus:ring-2 focus:ring-blue-500" />
            <div class="flex gap-2">
                <input v-model="selectedDate" type="date"
                    class="flex-1 sm:flex-none px-3 py-2 text-sm rounded border focus:outline-none focus:ring-2 focus:ring-blue-500"
                    @change="onDateChange" />
                <button @click="clearSearch"
                    class="px-3 py-2 bg-gray-100 rounded border text-sm hover:bg-gray-200 transition">
                    Clear
                </button>
            </div>
            <select v-model="selectedPIC"
                class="flex-1 sm:flex-none px-3 py-2 text-sm rounded border focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white min-w-[150px]">
                <option value="">All PICs</option>
                <option v-for="pic in uniquePICs" :key="pic" :value="pic">{{ pic }}</option>
            </select>
        </div>
        <button @click="openMissingModal"
            class="w-full sm:w-auto px-3 py-2 bg-red-100 text-red-700 rounded border text-sm flex items-center justify-center gap-2 hover:bg-red-200 transition">
            <span>Unchecked Items:</span>
            <span class="font-semibold bg-red-600 text-white px-2 py-0.5 rounded">{{ uncheckedCount }}</span>
        </button>
    </div>

    <!-- Table View with responsive font sizes -->
    <div class="overflow-x-auto">
        <table class="w-full border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-200">
                    <th class="p-1 sm:p-2 border text-[10px] sm:text-xs md:text-sm">No</th>
                    <th class="p-1 sm:p-2 border text-[10px] sm:text-xs md:text-sm">Material Number</th>
                    <th class="p-1 sm:p-2 border bg-yellow-300 text-[10px] sm:text-xs md:text-sm">Material
                        Description</th>
                    <th class="p-1 sm:p-2 border bg-red-400 text-white text-[10px] sm:text-xs md:text-sm">PIC</th>
                    <th class="p-1 sm:p-2 border text-[10px] sm:text-xs md:text-sm">UoM</th>
                    <th class="p-1 sm:p-2 border text-[10px] sm:text-xs md:text-sm">Min Stock</th>
                    <th class="p-1 sm:p-2 border text-[10px] sm:text-xs md:text-sm">Max Stock</th>
                    <th class="p-1 sm:p-2 border text-[10px] sm:text-xs md:text-sm">Status</th>
                    <th class="p-1 sm:p-2 border text-[10px] sm:text-xs md:text-sm">Daily Stock</th>
                    <th class="p-1 sm:p-2 border text-[10px] sm:text-xs md:text-sm">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in paginatedItems" :key="item.key" class="text-center hover:bg-gray-50">
                    <td class="border p-1 sm:p-2 font-semibold bg-gray-100 text-[10px] sm:text-xs md:text-sm">{{
                        item.number }}</td>
                    <td class="border p-1 sm:p-2 bg-yellow-200 text-[10px] sm:text-xs md:text-sm">{{
                        item.material_number }}</td>
                    <td class="border p-1 sm:p-2 bg-yellow-100 text-[10px] sm:text-xs md:text-sm">{{
                        item.description }}</td>
                    <td class="border p-1 sm:p-2 bg-red-100 text-[10px] sm:text-xs md:text-sm">{{ item.pic_name }}
                    </td>
                    <td class="border p-1 sm:p-2 text-[10px] sm:text-xs md:text-sm">{{ item.unit_of_measure }}</td>
                    <td class="border p-1 sm:p-2 text-[10px] sm:text-xs md:text-sm">{{ item.stock_minimum }}</td>
                    <td class="border p-1 sm:p-2 text-[10px] sm:text-xs md:text-sm">{{ item.stock_maximum }}</td>
                    <td class="border p-1 sm:p-2 text-[10px] sm:text-xs md:text-sm">

                        <div :class="item.statusClass" class="flex flex-col items-center px-2 py-1 rounded">
                            {{ item.status }}
                        </div>
                    </td>
                    <td class="border p-1 sm:p-2 text-[10px] sm:text-xs md:text-sm">
                        <div v-if="item.status === 'UNCHECKED'" class="flex items-center gap-1">
                            <input v-model.number="item.daily_stock" type="number" min="0"
                                class="w-20 px-2 py-1 text-xs border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Stock" />
                            <button @click="submitDailyStock(item)"
                                class="px-2 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700 transition whitespace-nowrap">
                                Submit
                            </button>
                        </div>
                        <div v-else class="">
                            {{ item.daily_stock }}
                        </div>
                    </td>
                    <td class=" border p-1 sm:p-2 text-[10px] sm:text-xs md:text-sm">
                        <div class="flex flex-col sm:flex-row gap-1 justify-center">
                            <button v-if="item.status !== 'UNCHECKED'" @click="deleteInput(item)"
                                class="px-2 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700 transition whitespace-nowrap">
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>

                <tr v-if="!paginatedItems.length">
                    <td colspan="7" class="border p-4 text-center text-gray-500 text-xs sm:text-sm">
                        No data available
                    </td>
                </tr>
            </tbody>
        </table>
    </div>



    <!-- Pagination Controls -->
    <div v-if="totalPages > 1" class="flex flex-col sm:flex-row items-center justify-between mt-4 gap-3">
        <div class="text-xs sm:text-sm text-gray-600 order-2 sm:order-1">
            Showing {{ startItem }} to {{ endItem }} of {{ totalItems }} entries
        </div>

        <div class="flex gap-1 sm:gap-2 order-1 sm:order-2 flex-wrap justify-center">
            <button @click="currentPage = 1" :disabled="currentPage === 1"
                class="px-2 sm:px-3 py-1 text-xs sm:text-sm border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                First
            </button>

            <button @click="currentPage--" :disabled="currentPage === 1"
                class="px-2 sm:px-3 py-1 text-xs sm:text-sm border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                Prev
            </button>

            <div class="flex gap-1">
                <button v-for="page in visiblePages" :key="page" @click="currentPage = page" :class="[
                    'px-2 sm:px-3 py-1 text-xs sm:text-sm border rounded transition',
                    currentPage === page
                        ? 'bg-blue-500 text-white'
                        : 'hover:bg-gray-100'
                ]">
                    {{ page }}
                </button>
            </div>

            <button @click="currentPage++" :disabled="currentPage === totalPages"
                class="px-2 sm:px-3 py-1 text-xs sm:text-sm border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                Next
            </button>

            <button @click="currentPage = totalPages" :disabled="currentPage === totalPages"
                class="px-2 sm:px-3 py-1 text-xs sm:text-sm border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                Last
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import axios from 'axios'

const reportData = ref({
    checked: [],
    missing: []
})
const intervalId = ref(null)
const currentPage = ref(1)
const itemsPerPage = 30
const searchTerm = ref('')
const selectedDate = ref(new Date().toISOString().split('T')[0])
const selectedPIC = ref('')

const fetchData = async () => {
    try {
        const res = await axios.get(`/api/daily-input/status?date=${selectedDate.value}`)
        reportData.value = {
            checked: res.data.checked || [],
            missing: res.data.missing || []
        }
    } catch (error) {
        console.error('Failed to load report data:', error)
    }
}

const getStatusClass = (status) => {
    const statusColors = {
        'CRITICAL': 'bg-red-500 text-white font-semibold',
        'WARNING': 'bg-yellow-500 text-white font-semibold',
        'NORMAL': 'bg-green-500 text-white font-semibold'
    }
    return statusColors[status] || 'bg-gray-200'
}

const missingItems = computed(() => {
    return reportData.value.missing.map((it) => ({
        ...it,
        daily_stock: it.daily_stock ?? null
    }))
})

const filteredMissingItems = computed(() => {
    if (!modalSearchTerm.value) return missingItems.value
    const q = modalSearchTerm.value.toLowerCase()
    return missingItems.value.filter((it) => {
        return (
            String(it.material_number || '').toLowerCase().includes(q) ||
            String(it.description || '').toLowerCase().includes(q) ||
            String(it.pic_name || '').toLowerCase().includes(q)
        )
    })
})

const uncheckedCount = computed(() => reportData.value.missing.length)

const submitDailyStock = async (item) => {
    if (!item.daily_stock) {
        alert('Please enter daily stock for ' + item.material_number)
        return
    }

    try {
        const payload = {
            material_id: item.id,
            date: selectedDate.value,
            daily_stock: Number(item.daily_stock)
        }

        await axios.post('/api/daily-input', payload)
        alert('Entry submitted successfully')
        await fetchData()
    } catch (err) {
        console.error('submitDailyStock error', err)
        alert('Failed to submit. See console for details.')
    }
}

const allItems = computed(() => {
    const items = []

    reportData.value.checked.forEach((item, index) => {
        items.push({
            key: `checked-${item.id}`,
            number: index + 1,
            material_number: item.material.material_number,
            description: item.material.description,
            pic_name: item.material.pic_name,
            unit_of_measure: item.material.unit_of_measure,
            stock_minimum: item.material.stock_minimum,
            stock_maximum: item.material.stock_maximum,
            daily_stock: item.daily_stock,
            status: item.status,
            statusClass: getStatusClass(item.status)
        })
    })

    reportData.value.missing.forEach((item, index) => {
        items.push({
            key: `missing-${item.id}`,
            id: item.id,
            number: reportData.value.checked.length + index + 1,
            material_number: item.material_number,
            description: item.description,
            pic_name: item.pic_name,
            unit_of_measure: item.unit_of_measure,
            stock_minimum: item.stock_minimum,
            stock_maximum: item.stock_maximum,
            daily_stock: null,
            status: 'UNCHECKED',
            statusClass: 'bg-gray-300 text-gray-600'
        })
    })

    return items
})

const filteredItems = computed(() => {
    let items = allItems.value

    // Filter by PIC
    if (selectedPIC.value) {
        items = items.filter((it) => it.pic_name === selectedPIC.value)
    }

    // Filter by search term
    if (searchTerm.value) {
        const q = searchTerm.value.toLowerCase()
        items = items.filter((it) => {
            return (
                String(it.pic_name || '').toLowerCase().includes(q) ||
                String(it.material_number).toLowerCase().includes(q) ||
                String(it.description || '').toLowerCase().includes(q) ||
                String(it.status || '').toLowerCase().includes(q)
            )
        })
    }

    return items
})

// Get unique PIC names for dropdown
const uniquePICs = computed(() => {
    const pics = new Set()
    allItems.value.forEach(item => {
        if (item.pic_name) {
            pics.add(item.pic_name)
        }
    })
    return Array.from(pics).sort()
})

const totalItems = computed(() => filteredItems.value.length)
const totalPages = computed(() => Math.ceil(totalItems.value / itemsPerPage))

const paginatedItems = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage
    const end = start + itemsPerPage
    return filteredItems.value.slice(start, end)
})

const startItem = computed(() => {
    return totalItems.value === 0 ? 0 : (currentPage.value - 1) * itemsPerPage + 1
})

const endItem = computed(() => {
    return Math.min(currentPage.value * itemsPerPage, totalItems.value)
})

const visiblePages = computed(() => {
    const pages = []
    const maxVisible = 5
    let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2))
    let end = Math.min(totalPages.value, start + maxVisible - 1)

    if (end - start + 1 < maxVisible) {
        start = Math.max(1, end - maxVisible + 1)
    }

    for (let i = start; i <= end; i++) {
        pages.push(i)
    }

    return pages
})

const deleteInput = async (item) => {
    try {
        await axios.delete(`/api/daily-input/delete/${item.key.replace('checked-', '')}`)
        await fetchData()
        alert('Entry deleted successfully')
    } catch (error) {
        console.error('Failed to delete entry:', error)
        alert('Failed to delete entry. See console for details.')
    }
}

const clearFilters = () => {
    searchTerm.value = ''
    selectedPIC.value = ''
    currentPage.value = 1
}

const onDateChange = async () => {
    currentPage.value = 1
    await fetchData()
}

watch(searchTerm, () => {
    currentPage.value = 1
})

watch(selectedPIC, () => {
    currentPage.value = 1
})

onMounted(() => {
    fetchData()
    intervalId.value = setInterval(fetchData, 10000)
})

onUnmounted(() => {
    if (intervalId.value) {
        clearInterval(intervalId.value)
    }
})
</script>
