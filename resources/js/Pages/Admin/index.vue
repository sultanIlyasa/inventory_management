<template>
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-50 py-8">
            <div class="max-w-7xl mx-auto px-4">

                <!-- Header -->
                <header class="mb-8">
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Administration</p>
                    <h1 class="text-3xl font-semibold text-gray-900">Vendor Directory</h1>
                    <p class="mt-2 text-sm text-gray-500">Review vendors and manage their materials.</p>
                </header>

                <!-- Search Bar -->
                <SearchBar v-model:searchTerm="searchTerm" @clear="clearSearch" />

                <!-- Actions -->
                <div class="mb-4 flex gap-3 justify-end">
                    <button
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700 disabled:opacity-60"
                        @click="fetchVendorData" :disabled="isLoading">
                        {{ isLoading ? 'Refreshing...' : 'Refresh Data' }}
                    </button>
                    <button
                        class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                        @click="openCreateVendorModal">
                        Add New Vendor
                    </button>
                </div>

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

                <!-- No Results -->
                <div v-if="!filteredVendors.length && !isLoading"
                    class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">
                    No vendors match your search.
                </div>

                <!-- Vendor Cards -->
                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <VendorCards v-for="entry in filteredVendors" :key="entry.vendor.id" :vendorData="entry"
                        @edit-vendor="openEditVendorModal" @delete-vendor="openDeleteVendorModal"
                        @create-material="openCreateMaterialModal" @attach-material="openAttachMaterialModal"
                        @edit-material="openEditMaterialModal" @remove-material="openRemoveMaterialModal" />
                </div>
            </div>
        </div>

        <!-- VENDOR MODALS -->

        <!-- Create Vendor -->
        <Modal :show="showCreateVendor" @close="closeCreateVendorModal">
            <div class="p-6 space-y-4">
                <h2 class="text-lg font-semibold">Create New Vendor</h2>

                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-medium text-gray-700">Vendor Number</label>
                        <input v-model="vendorForm.vendor_number" type="text"
                            class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700">Vendor Name</label>
                        <input v-model="vendorForm.vendor_name" type="text"
                            class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>

                    <div>
                        <label class="text-xs font-medium text-gray-700">Phone Number</label>
                        <input v-model="vendorForm.phone_number" type="text"
                            class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700">Emails (comma separated)</label>
                        <input v-model="vendorForm.emails" type="text"
                            class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <button class="px-3 py-2 text-sm rounded border" @click="closeCreateVendorModal">
                        Cancel
                    </button>
                    <button
                        class="px-4 py-2 text-sm rounded bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-60"
                        @click="submitCreateVendor" :disabled="isSubmittingVendor">
                        {{ isSubmittingVendor ? 'Saving...' : 'Save' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Edit Vendor -->
        <Modal :show="showEditVendor" @close="closeEditVendorModal">
            <div class="p-6 space-y-4">
                <h2 class="text-lg font-semibold">Edit Vendor</h2>

                <div v-if="activeVendor" class="space-y-3">
                    <div>
                        <label class="text-xs font-medium text-gray-700">Vendor Number</label>
                        <input v-model="vendorForm.vendor_number" type="text"
                            class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700">Vendor Name</label>
                        <input v-model="vendorForm.vendor_name" type="text"
                            class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700">Phone Number</label>
                        <input v-model="vendorForm.phone_number" type="text"
                            class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700">Emails (comma separated)</label>
                        <input v-model="vendorForm.emails" type="text"
                            class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <button class="px-3 py-2 text-sm rounded border" @click="closeEditVendorModal">
                        Cancel
                    </button>
                    <button
                        class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-60"
                        @click="submitEditVendor" :disabled="isSubmittingVendor">
                        {{ isSubmittingVendor ? 'Saving...' : 'Update' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Delete Vendor -->
        <Modal :show="showDeleteVendor" @close="closeDeleteVendorModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold mb-2">Delete Vendor</h2>
                <p class="text-sm text-gray-600 mb-4">
                    Are you sure you want to delete this vendor? Its materials will be detached and marked as
                    vendorless.
                </p>
                <div class="flex justify-end gap-2">
                    <button class="px-3 py-2 text-sm rounded border" @click="closeDeleteVendorModal">
                        Cancel
                    </button>
                    <button class="px-4 py-2 text-sm rounded bg-red-600 text-white hover:bg-red-700 disabled:opacity-60"
                        @click="submitDeleteVendor" :disabled="isSubmittingVendor">
                        {{ isSubmittingVendor ? 'Deleting...' : 'Delete' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- MATERIAL MODALS -->

        <!-- Create Material -->
        <Modal :show="showCreateMaterial" @close="closeCreateMaterialModal">
            <div class="p-6 space-y-4">
                <h2 class="text-lg font-semibold">
                    Create Material for {{ activeVendor?.vendor_name || 'Vendor' }}
                </h2>

                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-medium text-gray-700">Material Number</label>
                        <input v-model="materialForm.material_number" type="text"
                            class="mt-1 w-full rounded border px-3 py-2 text-sm" />
                    </div>
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
                    <button class="px-3 py-2 text-sm rounded border" @click="closeCreateMaterialModal">
                        Cancel
                    </button>
                    <button
                        class="px-4 py-2 text-sm rounded bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-60"
                        @click="submitCreateMaterial" :disabled="isSubmittingMaterial">
                        {{ isSubmittingMaterial ? 'Saving...' : 'Save' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Edit Material -->
        <Modal :show="showEditMaterial" @close="closeEditMaterialModal">
            <div class="p-6 space-y-4">
                <h2 class="text-lg font-semibold">
                    Edit Material
                </h2>

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

        <!-- Remove Material -->
        <Modal :show="showRemoveMaterial" @close="closeRemoveMaterialModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold mb-2">Remove Material</h2>
                <p class="text-sm text-gray-600 mb-4">
                    This will detach the material from the vendor, but the material will remain as vendorless.
                </p>
                <div class="flex justify-end gap-2">
                    <button class="px-3 py-2 text-sm rounded border" @click="closeRemoveMaterialModal">
                        Cancel
                    </button>
                    <button class="px-4 py-2 text-sm rounded bg-red-600 text-white hover:bg-red-700 disabled:opacity-60"
                        @click="submitRemoveMaterial" :disabled="isSubmittingMaterial">
                        {{ isSubmittingMaterial ? 'Removing...' : 'Remove' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Attach Material (vendorless list with search + pagination) -->
        <Modal :show="showAttachMaterial" @close="closeAttachMaterialModal" maxWidth="2xl">
            <div class="p-6 space-y-4">
                <h2 class="text-lg font-semibold">
                    Attach Material to {{ activeVendor?.vendor_name || 'Vendor' }}
                </h2>

                <!-- Search -->
                <div class="flex gap-2 mb-2">
                    <input v-model="vendorlessSearch" type="search" placeholder="Search vendorless materials..."
                        class="flex-1 rounded border px-3 py-2 text-sm" @input="debouncedFetchVendorless" />
                    <button class="px-3 py-2 text-sm rounded border" @click="clearVendorlessSearch">
                        Clear
                    </button>
                </div>

                <!-- List -->
                <div class="border rounded-lg max-h-80 overflow-y-auto">
                    <div v-if="isLoadingVendorless" class="p-4 text-sm text-gray-500">
                        Loading vendorless materials...
                    </div>
                    <div v-else-if="!vendorlessMaterials.length" class="p-4 text-sm text-gray-500">
                        No vendorless materials found.
                    </div>
                    <ul v-else class="divide-y">
                        <li v-for="mat in vendorlessMaterials" :key="mat.id"
                            class="flex items-center justify-between p-3 text-sm">
                            <div>
                                <p class="font-medium text-gray-800">
                                    {{ mat.material_number }} — {{ mat.description }}
                                </p>
                                <p class="text-[11px] text-gray-500">
                                    Usage: {{ mat.usage }} • Location: {{ mat.location }}
                                </p>
                            </div>
                            <button
                                class="ml-3 rounded bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                                @click="attachMaterial(mat.id)" :disabled="isAttaching">
                                Attach
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Pagination -->
                <div v-if="vendorlessPagination.last_page > 1"
                    class="mt-3 flex justify-between items-center text-xs text-gray-600">
                    <div>
                        Page {{ vendorlessPagination.current_page }} of {{ vendorlessPagination.last_page }}
                    </div>
                    <div class="flex gap-1">
                        <button class="px-2 py-1 border rounded disabled:opacity-50"
                            :disabled="vendorlessPagination.current_page === 1"
                            @click="fetchVendorlessMaterials(vendorlessPagination.current_page - 1)">
                            Prev
                        </button>
                        <button class="px-2 py-1 border rounded disabled:opacity-50"
                            :disabled="vendorlessPagination.current_page === vendorlessPagination.last_page"
                            @click="fetchVendorlessMaterials(vendorlessPagination.current_page + 1)">
                            Next
                        </button>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button class="px-3 py-2 text-sm rounded border" @click="closeAttachMaterialModal">
                        Close
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import { route } from 'ziggy-js'
import VendorCards from '@/Components/VendorCards.vue'
import SearchBar from '@/Components/SearchBar.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    initialVendorData: {
        type: Array,
        default: () => []
    }
})
const PIC_NAMES = [
    "ADE N", "AKBAR", "ANWAR", "BAHTIYAR", "DEDHI",
    "EKA S", "EKO P", "FAHRI", "IBNU", "IRPANDI",
    "IRVAN", "MIKS", "RACHMAT", "ZAINAL A."
];


const vendorData = ref(props.initialVendorData)
const searchTerm = ref('')
const isLoading = ref(false)
const bulkFileInput = ref(null)
const bulkFile = ref(null)
const bulkResult = ref(null)
const bulkErrors = ref([])
const isUploadingBulk = ref(false)

// ---- vendor modals state ----
const showCreateVendor = ref(false)
const showEditVendor = ref(false)
const showDeleteVendor = ref(false)

// ---- material modals state ----
const showCreateMaterial = ref(false)
const showEditMaterial = ref(false)
const showRemoveMaterial = ref(false)
const showAttachMaterial = ref(false)

const activeVendor = ref(null)
const activeMaterial = ref(null)

// ---- forms ----
const vendorForm = ref({
    vendor_number: '',
    vendor_name: '',
    phone_number: '',
    emails: ''
})

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

const isSubmittingVendor = ref(false)
const isSubmittingMaterial = ref(false)

// ---- vendorless materials for attach modal ----
const vendorlessMaterials = ref([])
const vendorlessPagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 10
})
const vendorlessSearch = ref('')
const isLoadingVendorless = ref(false)
const isAttaching = ref(false)
let vendorlessTimer = null

// ---- search/filter ----
const clearSearch = () => searchTerm.value = ''

const filteredVendors = computed(() => {
    const term = searchTerm.value.toLowerCase()
    if (!term) return vendorData.value

    return vendorData.value.filter(entry => {
        const v = entry.vendor
        const m = entry.materials

        return (
            v.vendor_name?.toLowerCase().includes(term) ||
            v.vendor_number?.toLowerCase().includes(term) ||
            v.phone_number?.toLowerCase().includes(term) ||
            (Array.isArray(v.emails) && v.emails.join(' ').toLowerCase().includes(term)) ||
            m.some(mat =>
                Object.values(mat).some(val =>
                    String(val).toLowerCase().includes(term)
                )
            )
        )
    })
})

// ---- fetch vendors ----
const fetchVendorData = async () => {
    isLoading.value = true
    try {
        // adjust this route name to your actual one
        const response = await axios.get(route('admin-vendor-api'))
        if (response.data?.success) {
            vendorData.value = Array.isArray(response.data.data) ? response.data.data : []
        }
    } catch (e) {
        console.error('Failed to fetch vendors', e)
    } finally {
        isLoading.value = false
    }
}

// ---- vendor modals handlers ----
const resetVendorForm = () => {
    vendorForm.value = {
        vendor_number: '',
        vendor_name: '',
        phone_number: '',
        emails: ''
    }
}

const openCreateVendorModal = () => {
    resetVendorForm()
    showCreateVendor.value = true
}
const closeCreateVendorModal = () => showCreateVendor.value = false

const openEditVendorModal = (vendor) => {
    activeVendor.value = vendor
    vendorForm.value = {
        vendor_number: vendor.vendor_number || '',
        vendor_name: vendor.vendor_name || '',
        phone_number: vendor.phone_number || '',
        emails: Array.isArray(vendor.emails) ? vendor.emails.join(', ') : ''
    }
    showEditVendor.value = true
}
const closeEditVendorModal = () => {
    showEditVendor.value = false
    activeVendor.value = null
}

const openDeleteVendorModal = (vendor) => {
    activeVendor.value = vendor
    showDeleteVendor.value = true
}
const closeDeleteVendorModal = () => {
    showDeleteVendor.value = false
    activeVendor.value = null
}

const submitCreateVendor = async () => {
    isSubmittingVendor.value = true
    try {
        const payload = {
            ...vendorForm.value,
            emails: vendorForm.value.emails
                ? vendorForm.value.emails.split(',').map(e => e.trim())
                : []
        }
        // adjust route name
        await axios.post(route('admin.vendors.store'), payload)
        await fetchVendorData()
        closeCreateVendorModal()
    } catch (e) {
        console.error('Create vendor failed', e)
    } finally {
        isSubmittingVendor.value = false
    }
}

const submitEditVendor = async () => {
    if (!activeVendor.value) return
    isSubmittingVendor.value = true
    try {
        const payload = {
            ...vendorForm.value,
            emails: vendorForm.value.emails
                ? vendorForm.value.emails.split(',').map(e => e.trim())
                : []
        }
        // adjust route name
        await axios.patch(route('admin.vendors.update', activeVendor.value.id), payload)
        await fetchVendorData()
        closeEditVendorModal()
    } catch (e) {
        console.error('Update vendor failed', e)
    } finally {
        isSubmittingVendor.value = false
    }
}

const submitDeleteVendor = async () => {
    if (!activeVendor.value) return
    isSubmittingVendor.value = true
    try {
        // adjust route name
        await axios.delete(route('admin.vendors.destroy', activeVendor.value.id))
        await fetchVendorData()
        closeDeleteVendorModal()
    } catch (e) {
        console.error('Delete vendor failed', e)
    } finally {
        isSubmittingVendor.value = false
    }
}

// ---- material modals handlers ----
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

const openCreateMaterialModal = (vendor) => {
    activeVendor.value = vendor
    resetMaterialForm()
    showCreateMaterial.value = true
}
const closeCreateMaterialModal = () => {
    showCreateMaterial.value = false
    activeVendor.value = null
}

const openEditMaterialModal = ({ vendor, material }) => {
    activeVendor.value = vendor
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
    activeVendor.value = null
}

const openRemoveMaterialModal = ({ vendor, material }) => {
    activeVendor.value = vendor
    activeMaterial.value = material
    showRemoveMaterial.value = true
}
const closeRemoveMaterialModal = () => {
    showRemoveMaterial.value = false
    activeMaterial.value = null
    activeVendor.value = null
}

const submitCreateMaterial = async () => {
    if (!activeVendor.value) return
    isSubmittingMaterial.value = true
    try {
        const payload = { ...materialForm.value }
        // adjust route name - expects vendor ID
        await axios.post(route('admin.materials.store', activeVendor.value.id), payload)
        await fetchVendorData()
        closeCreateMaterialModal()
    } catch (e) {
        console.error('Create material failed', e)
    } finally {
        isSubmittingMaterial.value = false
    }
}

const submitEditMaterial = async () => {
    if (!activeMaterial.value) return
    isSubmittingMaterial.value = true
    try {
        const payload = { ...materialForm.value }
        // adjust route name
        await axios.patch(route('admin.materials.update', activeMaterial.value.id), payload)
        await fetchVendorData()
        closeEditMaterialModal()
    } catch (e) {
        console.error('Update material failed', e)
    } finally {
        isSubmittingMaterial.value = false
    }
}

const submitRemoveMaterial = async () => {
    if (!activeMaterial.value) return
    isSubmittingMaterial.value = true
    try {
        // adjust route name
        await axios.patch(route('admin.materials.remove', activeMaterial.value.id))
        await fetchVendorData()
        closeRemoveMaterialModal()
    } catch (e) {
        console.error('Remove material failed', e)
    } finally {
        isSubmittingMaterial.value = false
    }
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

// ---- vendorless materials attach modal ----
const openAttachMaterialModal = (vendor) => {
    activeVendor.value = vendor
    showAttachMaterial.value = true
    fetchVendorlessMaterials(1)
}
const closeAttachMaterialModal = () => {
    showAttachMaterial.value = false
    activeVendor.value = null
    vendorlessMaterials.value = []
    vendorlessSearch.value = ''
}

const fetchVendorlessMaterials = async (page = 1) => {
    isLoadingVendorless.value = true
    try {
        // adjust route name to your actual vendorless API
        const response = await axios.get(route('materials.vendorless'), {
            params: {
                page,
                search: vendorlessSearch.value
            }
        })

        if (response.data?.success) {
            const paginated = response.data.data
            vendorlessMaterials.value = paginated.data || []
            vendorlessPagination.value = {
                current_page: paginated.current_page,
                last_page: paginated.last_page,
                total: paginated.total,
                per_page: paginated.per_page
            }
        }
    } catch (e) {
        console.error('Fetch vendorless materials failed', e)
    } finally {
        isLoadingVendorless.value = false
    }
}

const debouncedFetchVendorless = () => {
    clearTimeout(vendorlessTimer)
    vendorlessTimer = setTimeout(() => {
        fetchVendorlessMaterials(1)
    }, 400)
}

const clearVendorlessSearch = () => {
    vendorlessSearch.value = ''
    fetchVendorlessMaterials(1)
}

const attachMaterial = async (materialId) => {
    if (!activeVendor.value) return
    isAttaching.value = true
    try {
        // adjust route name
        await axios.patch(route('admin.materials.attach', materialId), {
            vendor_id: activeVendor.value.id
        })
        await fetchVendorData()
        await fetchVendorlessMaterials(vendorlessPagination.value.current_page)
    } catch (e) {
        console.error('Attach material failed', e)
    } finally {
        isAttaching.value = false
    }
}
</script>
