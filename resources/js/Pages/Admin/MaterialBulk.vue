<template>
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-50 py-8">
            <div class="max-w-7xl mx-auto px-4">

                <!-- Bulk Material Maintenance -->
                <section class="mb-10 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Bulk Material Maintenance</h2>
                            <p class="text-sm text-gray-500">
                                Download the current snapshot, edit it in Excel, then upload it back. Keep the Material
                                ID column intact so existing daily inputs remain linked.
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <a :href="route('admin.materials.export')"
                                class="rounded-lg border border-blue-200 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50">
                                Download XLSX
                            </a>
                            <button type="button"
                                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100"
                                @click="clearBulkFile">
                                Reset
                            </button>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-col gap-3 md:flex-row md:items-center">
                        <input ref="bulkFileInput" type="file" accept=".xlsx,.xls"
                            class="w-full rounded border px-3 py-2 text-sm file:mr-4 file:cursor-pointer file:rounded-md file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-700"
                            @change="handleBulkFileChange" />
                        <button type="button"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60"
                            @click="submitBulkUpload" :disabled="!bulkFile || isUploadingBulk">
                            {{ isUploadingBulk ? 'Uploading...' : 'Upload & Sync' }}
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">
                        • Leave Material ID blank to create a new entry. • Keep Vendor ID to avoid breaking vendor
                        assignments.
                    </p>
                    <div v-if="bulkResult" class="mt-4 rounded-lg bg-gray-50 p-3 text-sm text-gray-700">
                        <p>
                            <span class="font-semibold text-emerald-600">{{ bulkResult.created || 0 }}</span>
                            created,
                            <span class="font-semibold text-blue-600">{{ bulkResult.updated || 0 }}</span>
                            updated<span v-if="bulkResult.deleted">,
                            <span class="font-semibold text-red-600">{{ bulkResult.deleted }}</span>
                            deleted</span>.
                        </p>
                        <p v-if="bulkResult.skipped" class="mt-1 text-xs text-orange-600">
                            {{ bulkResult.skipped }} row(s) skipped.
                        </p>
                    </div>
                    <ul v-if="bulkErrors.length" class="mt-3 list-disc space-y-1 pl-5 text-xs text-red-600">
                        <li v-for="(error, idx) in bulkErrors" :key="idx">
                            {{ error }}
                        </li>
                    </ul>
                </section>

                <!-- Table -->
                <section>
                    <SearchBar v-model:searchTerm="searchTerm" @clear="clearSearch" />
                    <div v-if="isLoading"
                        class="absolute inset-0 bg-white/70 flex items-center justify-center z-10 h-[1500px]">
                        <div class="text-center">
                            <div
                                class="inline-block animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mb-3">
                            </div>
                            <p class="text-sm text-gray-600 font-medium">Loading data...</p>
                        </div>
                    </div>
                    <div class="flex flex-col mx-auto ">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-lg font-semibold">Materials Data</h3>
                            <button
                                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 disabled:opacity-50"
                                @click="fetchMaterialData" :disabled="isLoading">
                                {{ isLoading ? 'Refreshing...' : 'Refresh' }}
                            </button>
                        </div>
                        <table>
                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="p-2 border text-xs lg:text-sm">No</th>
                                    <th class="p-2 border text-xs lg:text-sm">PIC</th>
                                    <th class="p-2 border text-xs lg:text-sm">Material Number</th>
                                    <th class="p-2 border text-xs lg:text-sm">Material Description</th>
                                    <th class="p-2 border text-xs lg:text-sm">Rack Adress</th>
                                    <th class="p-2 border text-xs lg:text-sm">UoM</th>
                                    <th class="p-2 border text-xs lg:text-sm">Min Stock</th>
                                    <th class="p-2 border text-xs lg:text-sm">Max Stock</th>
                                    <th class="p-2 border text-xs lg:text-sm">Usage</th>
                                    <th class="p-2 border text-xs lg:text-sm">Gentani</th>
                                    <th class="p-2 border text-xs lg:text-sm">Last Updated</th>
                                    <th class="p-2 border text-xs lg:text-sm">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in materialsData.data" :key="item.id">
                                    <td class="border p-2 font-semibold  text-xs lg:text-sm">{{ item.id }}
                                    </td>
                                    <td class="border p-2  text-xs lg:text-sm">{{ item.pic_name }}</td>
                                    <td class="border p-2  text-xs lg:text-sm">{{ item.material_number }}
                                    </td>
                                    <td class="border p-2  text-xs lg:text-sm">{{ item.description }}</td>
                                    <td class="border p-2  text-xs lg:text-sm">{{ item.rack_address }}</td>
                                    <td class="border p-2  text-xs lg:text-sm">{{ item.unit_of_measure }}</td>
                                    <td class="border p-2  text-xs lg:text-sm">{{ item.stock_minimum }}</td>
                                    <td class="border p-2  text-xs lg:text-sm">{{ item.stock_maximum }}</td>
                                    <td class="border p-2  text-xs lg:text-sm">{{ item.usage }}</td>
                                    <td class="border p-2  text-xs lg:text-sm">{{ item.gentani }}</td>
                                    <td class="border p-2 text-xs lg:text-sm">
                                        {{ formatDate(item.updated_at) }}
                                    </td>
                                    <td class="border p-2 text-xs lg:text-sm">
                                        <div class="flex gap-1 justify-center">
                                            <button @click="openEditMaterialModal(item)"
                                                class="px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                                                Edit
                                            </button>
                                            <button @click="openDeleteMaterialModal(item)"
                                                class="px-2 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>

                        </table>
                    </div>
                    <!-- Pagination -->
                    <div class="flex flex-col mx-auto justify-center items-center">
                        <span class="my-1 text-gray-500 font-semibold"> Page {{ materialsData.current_page }} of {{ materialsData.last_page }}
                        </span>

                        <div class="flex gap-1 mt-1">
                            <button v-for="link in materialsData.links" :key="link.label" v-html="link.label"
                                class="px-3 py-1 border rounded text-sm" :class="{
                                    'bg-blue-600 text-white': link.active,
                                    'text-gray-400 cursor-not-allowed': !link.url
                                }" :disabled="!link.url" @click="link.url && goToLink(link.url)" />
                        </div>

                    </div>

                </section>
            </div>
        </div>

        <!-- Edit Material Modal -->
        <Modal :show="showEditMaterial" @close="closeEditMaterialModal">
            <div class="p-6 space-y-4">
                <h2 class="text-lg font-semibold">Edit Material</h2>

                <div v-if="activeMaterial" class="space-y-3">
                    <div>
                        <label class="text-xs font-medium text-gray-700">Description</label>
                        <input v-model="materialForm.description" type="text"
                            class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-medium text-gray-700">Stock Min</label>
                            <input v-model.number="materialForm.stock_minimum" type="number" min="0"
                                class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-700">Stock Max</label>
                            <input v-model.number="materialForm.stock_maximum" type="number" min="0"
                                class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700">Unit of Measure</label>
                        <input v-model="materialForm.unit_of_measure" type="text"
                            class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700">PIC Name</label>
                        <select v-model="materialForm.pic_name"
                            class="mt-1 w-full rounded border px-3 py-2 text-sm bg-white">
                            <option disabled value="">Select PIC</option>
                            <option v-for="pic in PIC_NAMES" :key="pic" :value="pic">
                                {{ pic }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700">Rack Address</label>
                        <input v-model="materialForm.rack_address" type="text"
                            class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-medium text-gray-700">Usage</label>
                            <select v-model="materialForm.usage" class="mt-1 w-full rounded border px-3 py-2 text-sm">
                                <option value="DAILY">DAILY</option>
                                <option value="WEEKLY">WEEKLY</option>
                                <option value="MONTHLY">MONTHLY</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-700">Location</label>
                            <select v-model="materialForm.location"
                                class="mt-1 w-full rounded border px-3 py-2 text-sm">
                                <option value="SUNTER_1">SUNTER_1</option>
                                <option value="SUNTER_2">SUNTER_2</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700">Gentani</label>
                        <input v-model="materialForm.gentani" type="text"
                            class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <button class="px-3 py-2 text-sm rounded border" @click="closeEditMaterialModal">
                        Cancel
                    </button>
                    <button
                        class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-60"
                        @click="submitEditMaterial" :disabled="isSubmittingMaterial">
                        {{ isSubmittingMaterial ? 'Saving...' : 'Update' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Delete Material Modal -->
        <Modal :show="showDeleteMaterial" @close="closeDeleteMaterialModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold mb-2">Delete Material</h2>
                <p class="text-sm text-gray-600 mb-4">
                    Are you sure you want to delete this material? This action cannot be undone.
                </p>
                <div class="flex justify-end gap-2">
                    <button class="px-3 py-2 text-sm rounded border" @click="closeDeleteMaterialModal">
                        Cancel
                    </button>
                    <button class="px-4 py-2 text-sm rounded bg-red-600 text-white hover:bg-red-700 disabled:opacity-60"
                        @click="submitDeleteMaterial" :disabled="isSubmittingMaterial">
                        {{ isSubmittingMaterial ? 'Deleting...' : 'Delete' }}
                    </button>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>

<script setup>
import SearchBar from '@/Components/SearchBar.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { ref, computed, watch } from 'vue'
import debounce from 'lodash/debounce'
import axios from 'axios'
import { route } from 'ziggy-js'

const PIC_NAMES = [
    "ADE N", "AKBAR", "ANWAR", "BAHTIYAR", "DEDHI",
    "EKA S", "EKO P", "FAHRI", "IBNU", "IRPANDI",
    "IRVAN", "MIKS", "RACHMAT", "ZAINAL A."
];

const searchTerm = ref('')
const props = defineProps({
    initialMaterialsData: {
        type: Object,
        default: () => []
    }
})


const materialsData = ref(props.initialMaterialsData);

const bulkFileInput = ref(null)
const bulkFile = ref(null)
const bulkResult = ref(null)
const bulkErrors = ref([])
const isUploadingBulk = ref(false)
const isLoading = ref(false)

// Material modals state
const showEditMaterial = ref(false)
const showDeleteMaterial = ref(false)
const activeMaterial = ref(null)
const isSubmittingMaterial = ref(false)

const materialForm = ref({
    material_number: '',
    description: '',
    stock_minimum: 0,
    stock_maximum: 0,
    unit_of_measure: '',
    pic_name: '',
    rack_address: '',
    usage: '',
    location: '',
    gentani: ''
})

const clearSearch = () => {
    searchTerm.value = ''
}


const fetchMaterialData = async () => {
    isLoading.value = true

    try {
        const response = await axios.get(route('api-get-materials'))

        if (response.data?.success) {
            materialsData.value = response.data.data
        }
    } catch (error) {
        console.error('Failed to fetch materials', error)
    } finally {
        isLoading.value = false
    }
}

const searchMaterials = async (pageUrl = null) => {
    isLoading.value = true

    try {
        const response = await axios.get(
            pageUrl ?? route('admin.materials.search'),
            {
                params: {
                    search: searchTerm.value || undefined
                }
            }
        )

        materialsData.value = response.data.data
    } catch (error) {
        console.error('Failed to fetch materials', error)
    } finally {
        isLoading.value = false
    }
}


const debouncedSearch = debounce(() => {
    searchMaterials()
}, 400)

watch(searchTerm, () => {
    debouncedSearch()
})


const goToLink = async (url) => {
    isLoading.value = true

    try {
        const response = await axios.get(url)
        materialsData.value = response.data.data
    } finally {
        isLoading.value = false
    }
}


const formatDate = (value) => {
    if (!value) return '-'

    const date = new Date(value)

    return date.toLocaleString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// ---- bulk material upload ----
const resetBulkFileInput = () => {
    if (bulkFileInput.value) {
        bulkFileInput.value.value = ''
    }
}

const handleBulkFileChange = (event) => {
    const file = event?.target?.files?.[0] || null
    bulkFile.value = file
    bulkResult.value = null
    bulkErrors.value = []
}

const clearBulkFile = () => {
    bulkFile.value = null
    bulkResult.value = null
    bulkErrors.value = []
    resetBulkFileInput()
}

const submitBulkUpload = async () => {
    if (!bulkFile.value) return

    isUploadingBulk.value = true
    bulkErrors.value = []

    try {
        const formData = new FormData()
        formData.append('file', bulkFile.value)

        const response = await axios.post(route('admin.materials.import'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })

        const summary = response.data?.data || null
        bulkResult.value = summary
        bulkErrors.value = Array.isArray(summary?.errors) ? summary.errors : []
        bulkFile.value = null
        resetBulkFileInput()
        await fetchMaterialData()
    } catch (error) {
        if (error.response?.data?.errors) {
            const messages = Object.values(error.response.data.errors)
                .reduce((carry, item) => carry.concat(item), [])
                .map((msg) => String(msg))
            bulkErrors.value = messages.length ? messages : ['Bulk upload failed.']
        } else {
            bulkErrors.value = ['Bulk upload failed.']
        }
    } finally {
        isUploadingBulk.value = false
    }
}

// ---- material edit/delete handlers ----
const resetMaterialForm = () => {
    materialForm.value = {
        material_number: '',
        description: '',
        stock_minimum: 0,
        stock_maximum: 0,
        unit_of_measure: '',
        pic_name: '',
        rack_address: '',
        usage: '',
        location: '',
        gentani: ''
    }
}

const openEditMaterialModal = (material) => {
    activeMaterial.value = material
    materialForm.value = {
        material_number: material.material_number,
        description: material.description,
        stock_minimum: material.stock_minimum ?? 0,
        stock_maximum: material.stock_maximum ?? 0,
        unit_of_measure: material.unit_of_measure ?? '',
        pic_name: material.pic_name ?? '',
        rack_address: material.rack_address ?? '',
        usage: material.usage,
        location: material.location,
        gentani: material.gentani ?? 'NON_GENTAN-I'
    }
    showEditMaterial.value = true
}

const closeEditMaterialModal = () => {
    showEditMaterial.value = false
    activeMaterial.value = null
    resetMaterialForm()
}

const submitEditMaterial = async () => {
    if (!activeMaterial.value) return
    isSubmittingMaterial.value = true
    try {
        const payload = { ...materialForm.value }
        await axios.patch(route('admin.materials.update', activeMaterial.value.id), payload)
        await fetchMaterialData()
        closeEditMaterialModal()
    } catch (e) {
        console.error('Update material failed', e)
    } finally {
        isSubmittingMaterial.value = false
    }
}

const openDeleteMaterialModal = (material) => {
    activeMaterial.value = material
    showDeleteMaterial.value = true
}

const closeDeleteMaterialModal = () => {
    showDeleteMaterial.value = false
    activeMaterial.value = null
}

const submitDeleteMaterial = async () => {
    if (!activeMaterial.value) return
    isSubmittingMaterial.value = true
    try {
        await axios.delete(route('admin.materials.destroy', activeMaterial.value.id))
        await fetchMaterialData()
        closeDeleteMaterialModal()
    } catch (e) {
        console.error('Delete material failed', e)
    } finally {
        isSubmittingMaterial.value = false
    }
}

</script>
