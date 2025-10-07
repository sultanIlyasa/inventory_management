<template>

    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard
            </h2>
        </template>
        <div class="min-h-screen bg-gray-50 p-4 sm:p-6">
            <div class="max-w-6xl mx-auto bg-white rounded shadow p-4 sm:p-6">
                <h1 class="text-xl sm:text-2xl font-bold mb-4">Admin Panel</h1>
                <p class="text-sm text-gray-600 mb-4">Welcome to the admin landing page. From here you can manage
                    materials and view reports.</p>

                <!-- Materials Management -->
                <div class="mt-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                        <div>
                            <h2 class="text-lg font-semibold">Materials</h2>
                            <div class="text-sm text-gray-600">Total: {{ materials.length }}</div>
                        </div>
                        <button @click="openCreate"
                            class="w-full sm:w-auto px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Create New Material</span>
                        </button>
                    </div>

                    <!-- Search -->
                    <div class="mb-4">
                        <input v-model="searchTerm" type="search"
                            placeholder="Search by material number, description, or PIC..."
                            class="w-full px-3 py-2 text-sm rounded border focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse border border-gray-200">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="p-2 border text-left text-[10px] sm:text-xs md:text-sm">#</th>
                                    <th class="p-2 border text-left text-[10px] sm:text-xs md:text-sm">Material Number
                                    </th>
                                    <th class="p-2 border text-left text-[10px] sm:text-xs md:text-sm">Description</th>
                                    <th class="p-2 border text-left text-[10px] sm:text-xs md:text-sm">PIC</th>
                                    <th class="p-2 border text-left text-[10px] sm:text-xs md:text-sm">UoM</th>
                                    <th class="p-2 border text-left text-[10px] sm:text-xs md:text-sm">Min</th>
                                    <th class="p-2 border text-left text-[10px] sm:text-xs md:text-sm">Max</th>
                                    <th class="p-2 border text-left text-[10px] sm:text-xs md:text-sm">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(m, idx) in paginatedMaterials" :key="m.id"
                                    class="align-top hover:bg-gray-50">
                                    <td class="p-2 border text-[10px] sm:text-xs md:text-sm">{{ (currentPage - 1) *
                                        itemsPerPage
                                        + idx + 1 }}</td>
                                    <td class="p-2 border text-[10px] sm:text-xs md:text-sm">{{ m.material_number }}
                                    </td>
                                    <td class="p-2 border text-[10px] sm:text-xs md:text-sm">{{ m.description }}</td>
                                    <td class="p-2 border text-[10px] sm:text-xs md:text-sm">{{ m.pic_name }}</td>
                                    <td class="p-2 border text-[10px] sm:text-xs md:text-sm">{{ m.unit_of_measure }}
                                    </td>
                                    <td class="p-2 border text-[10px] sm:text-xs md:text-sm">{{ m.stock_minimum }}</td>
                                    <td class="p-2 border text-[10px] sm:text-xs md:text-sm">{{ m.stock_maximum }}</td>
                                    <td class="p-2 border">
                                        <div class="flex gap-1">
                                            <button @click="openEdit(m)"
                                                class="px-2 py-1 bg-blue-600 text-white rounded text-[10px] sm:text-xs hover:bg-blue-700 transition">
                                                Edit
                                            </button>
                                            <button @click="deleteMaterial(m.id)"
                                                class="px-2 py-1 bg-red-600 text-white rounded text-[10px] sm:text-xs hover:bg-red-700 transition">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!paginatedMaterials.length">
                                    <td colspan="7" class="p-4 text-center text-gray-500 text-sm">No materials found
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="totalPages > 1"
                        class="flex flex-col sm:flex-row items-center justify-between mt-4 gap-3">
                        <div class="text-xs sm:text-sm text-gray-600 order-2 sm:order-1">
                            Showing {{ startItem }} to {{ endItem }} of {{ filteredMaterials.length }} entries
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
                                    currentPage === page ? 'bg-blue-500 text-white' : 'hover:bg-gray-100'
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
                </div>
            </div>

            <!-- Create/Edit Modal -->
            <div v-if="showModal"
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/40 p-0 sm:p-4"
                @click.self="closeModal">
                <div class="bg-white rounded-t-2xl sm:rounded-lg w-full sm:max-w-xl max-h-[90vh] flex flex-col">
                    <!-- Modal Header -->
                    <div
                        class="flex items-center justify-between p-4 border-b sticky top-0 bg-white rounded-t-2xl sm:rounded-t-lg">
                        <h3 class="text-lg font-semibold">{{ isEditMode ? 'Edit Material' : 'Create New Material' }}
                        </h3>
                        <button @click="closeModal" class="text-gray-500 hover:text-gray-700 p-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Content -->
                    <div class="flex-1 overflow-y-auto p-4">
                        <div class="grid grid-cols-1 gap-4">
                            <label class="text-sm">
                                <span class="font-medium">Material Number <span class="text-red-500">*</span></span>
                                <input v-model="form.material_number" type="text"
                                    class="w-full mt-1 p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="e.g., MAT-001" />
                            </label>

                            <label class="text-sm">
                                <span class="font-medium">Description <span class="text-red-500">*</span></span>
                                <textarea v-model="form.description"
                                    class="w-full mt-1 p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    rows="3" placeholder="Material description"></textarea>
                            </label>

                            <label class="text-sm">
                                <span class="font-medium">PIC Name <span class="text-red-500">*</span></span>
                                <input v-model="form.pic_name" type="text"
                                    class="w-full mt-1 p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Person in charge" />
                            </label>

                            <label class="text-sm">
                                <span class="font-medium">Unit of Measure <span class="text-red-500">*</span></span>
                                <input v-model="form.unit_of_measure" type="text"
                                    class="w-full mt-1 p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="e.g., pcs, kg, liters" />
                            </label>

                            <div class="grid grid-cols-2 gap-3">
                                <label class="text-sm">
                                    <span class="font-medium">Min Stock <span class="text-red-500">*</span></span>
                                    <input v-model.number="form.stock_minimum" type="number" min="0"
                                        class="w-full mt-1 p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                </label>
                                <label class="text-sm">
                                    <span class="font-medium">Max Stock <span class="text-red-500">*</span></span>
                                    <input v-model.number="form.stock_maximum" type="number" min="0"
                                        class="w-full mt-1 p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-4 border-t bg-gray-50 flex justify-end gap-2">
                        <button @click="closeModal"
                            class="px-4 py-2 border rounded text-sm hover:bg-gray-100 transition">
                            Cancel
                        </button>
                        <button @click="submitForm"
                            class="px-4 py-2 bg-green-600 text-white rounded text-sm hover:bg-green-700 transition">
                            {{ isEditMode ? 'Update' : 'Create' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted, computed, watch } from 'vue'
import axios from 'axios'

const materials = ref([])
const showModal = ref(false)
const isEditMode = ref(false)
const searchTerm = ref('')
const currentPage = ref(1)
const itemsPerPage = 20

const form = reactive({
    id: null,
    material_number: '',
    description: '',
    pic_name: '',
    unit_of_measure: '',
    stock_minimum: 0,
    stock_maximum: 0
})

const fetchMaterials = async () => {
    try {
        const res = await axios.get('/api/materials')
        materials.value = res.data.data || []
    } catch (err) {
        console.error('Failed to load materials', err)
    }
}

const filteredMaterials = computed(() => {
    if (!searchTerm.value) return materials.value
    const q = searchTerm.value.toLowerCase()
    return materials.value.filter((m) => {
        return (
            String(m.material_number || '').toLowerCase().includes(q) ||
            String(m.description || '').toLowerCase().includes(q) ||
            String(m.pic_name || '').toLowerCase().includes(q)
        )
    })
})

const totalPages = computed(() => Math.ceil(filteredMaterials.value.length / itemsPerPage))

const paginatedMaterials = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage
    const end = start + itemsPerPage
    return filteredMaterials.value.slice(start, end)
})

const startItem = computed(() => {
    return filteredMaterials.value.length === 0 ? 0 : (currentPage.value - 1) * itemsPerPage + 1
})

const endItem = computed(() => {
    return Math.min(currentPage.value * itemsPerPage, filteredMaterials.value.length)
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

const resetForm = () => {
    form.id = null
    form.material_number = ''
    form.description = ''
    form.pic_name = ''
    form.unit_of_measure = ''
    form.stock_minimum = 0
    form.stock_maximum = 0
}

const openCreate = () => {
    resetForm()
    isEditMode.value = false
    showModal.value = true
}

const openEdit = (m) => {
    form.id = m.id
    form.material_number = m.material_number
    form.description = m.description
    form.pic_name = m.pic_name
    form.unit_of_measure = m.unit_of_measure
    form.stock_minimum = m.stock_minimum
    form.stock_maximum = m.stock_maximum
    isEditMode.value = true
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    resetForm()
}

const validateForm = () => {
    if (!form.material_number || !form.description || !form.pic_name || !form.unit_of_measure) {
        alert('Please fill in all required fields')
        return false
    }
    if (form.stock_minimum < 0 || form.stock_maximum < 0) {
        alert('Stock values cannot be negative')
        return false
    }
    if (form.stock_minimum > form.stock_maximum) {
        alert('Minimum stock cannot be greater than maximum stock')
        return false
    }
    return true
}

const submitForm = async () => {
    if (!validateForm()) return

    try {
        const payload = {
            material_number: form.material_number,
            description: form.description,
            pic_name: form.pic_name,
            unit_of_measure: form.unit_of_measure,
            stock_minimum: Number(form.stock_minimum),
            stock_maximum: Number(form.stock_maximum)
        }

        if (isEditMode.value) {
            await axios.put(`/api/materials/${form.id}`, payload)
            alert('Material updated successfully!')
        } else {
            await axios.post('/api/materials', payload)
            alert('Material created successfully!')
        }

        await fetchMaterials()
        closeModal()
    } catch (err) {
        console.error('Failed to submit material', err)
        alert('Failed to submit material. Please try again.')
    }
}

const deleteMaterial = async (id) => {
    if (!confirm('Are you sure you want to delete this material?')) return

    try {
        await axios.delete(`/api/materials/${id}`)
        alert('Material deleted successfully!')
        await fetchMaterials()
    } catch (err) {
        console.error('Failed to delete material', err)
        alert('Failed to delete material. Please try again.')
    }
}

watch(searchTerm, () => {
    currentPage.value = 1
})

onMounted(() => {
    fetchMaterials()
})
</script>
