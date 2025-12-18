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
                            updated.
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


    </AuthenticatedLayout>
</template>

<script setup>
import SearchBar from '@/Components/SearchBar.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, watch } from 'vue'
import debounce from 'lodash/debounce'
import axios from 'axios'
import { route } from 'ziggy-js'
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
        await fetchVendorData()
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


</script>
