<template>
    <MainAppLayout title="Annual Inventory" subtitle="Submit inventory count">
        <div class="min-h-screen w-full overflow-hidden bg-gray-50">
            <div class="flex">
                <div class="flex-1">
                    <div class="mx-auto w-full">
                        <!-- Back Button & Title -->
                        <div class="flex items-center gap-4 my-6">
                            <button @click="goBack"
                                class="p-2 hover:bg-white rounded-lg border border-gray-200 transition-colors">
                                <ArrowLeft class="w-5 h-5 text-gray-600" />
                            </button>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">{{ pidData?.pid || 'Loading...' }}</h1>
                                <p class="text-sm text-gray-500">{{ pidData?.location }} • {{ pidData?.date }}</p>
                            </div>
                        </div>

                        <!-- Main Content Card -->
                        <div class="bg-white rounded-2xl shadow-lg p-4">
                            <!-- Loading State -->
                            <div v-if="isLoading" class="flex items-center justify-center py-20">
                                <div class="text-center">
                                    <div
                                        class="inline-block animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mb-3">
                                    </div>
                                    <p class="text-sm text-gray-600 font-medium">Loading data...</p>
                                </div>
                            </div>

                            <div v-else class="space-y-3 sm:space-y-4">
                                <!-- Search Bar -->
                                <div class="relative">
                                    <Search
                                        class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                                    <input v-model="searchQuery" type="text"
                                        placeholder="Search material number, description, rack..."
                                        class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm" />
                                    <button v-if="searchQuery" @click="searchQuery = ''"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <X class="w-4 h-4" />
                                    </button>
                                </div>

                                <!-- Sort Button (Mobile) -->
                                <div class="md:hidden">
                                    <button @click="toggleRackSort"
                                        class="w-full flex items-center justify-center gap-2 px-3 py-2 border rounded-lg text-sm transition-colors"
                                        :class="rackSortOrder ? 'bg-blue-50 border-blue-300 text-blue-700' : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-50'">
                                        <ArrowUp v-if="rackSortOrder === 'asc'" class="w-4 h-4" />
                                        <ArrowDown v-else-if="rackSortOrder === 'desc'" class="w-4 h-4" />
                                        <ArrowUpDown v-else class="w-4 h-4" />
                                        <span>Rack: {{ rackSortOrder === 'asc' ? 'A → Z' : rackSortOrder === 'desc' ? 'Z → A' : 'Unsorted' }}</span>
                                    </button>
                                </div>

                                <!-- Statistics & Actions -->
                                <div class="flex flex-col lg:flex-row gap-3 lg:gap-4">
                                    <!-- Statistics Badges -->
                                    <div class="flex-1 grid grid-cols-2 sm:flex sm:flex-row gap-2 sm:gap-2 md:gap-3">
                                        <div
                                            class="px-2 sm:px-3 md:px-4 py-2 md:py-3 bg-gradient-to-br from-gray-100 to-gray-200 text-gray-700 rounded-lg md:rounded-xl border border-gray-300 text-xs sm:text-sm flex items-center justify-center gap-1 sm:gap-2">
                                            <span class="font-medium">Total:</span>
                                            <span
                                                class="font-bold bg-gray-600 text-white px-2 py-0.5 rounded-md text-xs">{{
                                                    statistics.total }}</span>
                                        </div>
                                        <div
                                            class="px-2 sm:px-3 md:px-4 py-2 md:py-3 bg-gradient-to-br from-amber-100 to-amber-200 text-amber-700 rounded-lg md:rounded-xl border border-amber-300 text-xs sm:text-sm flex items-center justify-center gap-1 sm:gap-2">
                                            <span class="font-medium">Pending:</span>
                                            <span
                                                class="font-bold bg-amber-600 text-white px-2 py-0.5 rounded-md text-xs">{{
                                                    statistics.pending }}</span>
                                        </div>
                                        <div
                                            class="px-2 sm:px-3 md:px-4 py-2 md:py-3 bg-gradient-to-br from-blue-100 to-blue-200 text-blue-700 rounded-lg md:rounded-xl border border-blue-300 text-xs sm:text-sm flex items-center justify-center gap-1 sm:gap-2">
                                            <span class="font-medium">Counted:</span>
                                            <span
                                                class="font-bold bg-blue-600 text-white px-2 py-0.5 rounded-md text-xs">{{
                                                    statistics.counted }}</span>
                                        </div>
                                        <div
                                            class="px-2 sm:px-3 md:px-4 py-2 md:py-3 bg-gradient-to-br from-green-100 to-green-200 text-green-700 rounded-lg md:rounded-xl border border-green-300 text-xs sm:text-sm flex items-center justify-center gap-1 sm:gap-2">
                                            <span class="font-medium">Progress:</span>
                                            <span
                                                class="font-bold bg-green-600 text-white px-2 py-0.5 rounded-md text-xs">{{
                                                    statistics.progress }}%</span>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex flex-col sm:flex-row gap-2 lg:gap-3 w-full lg:w-auto lg:min-w-fit">
                                        <button @click="fetchPIDData(pagination.current_page)"
                                            class="w-full sm:w-auto px-4 py-2.5 sm:py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors font-medium text-sm shadow-sm">
                                            <span class="flex items-center justify-center gap-2">
                                                <RefreshCw class="w-4 h-4" />
                                                Refresh
                                            </span>
                                        </button>
                                        <button @click="saveAllItems" :disabled="!hasUnsavedChanges || isSaving"
                                            class="w-full sm:w-auto px-4 py-2.5 sm:py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium text-sm shadow-sm">
                                            <span class="flex items-center justify-center gap-2">
                                                <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
                                                <Save v-else class="w-4 h-4" />
                                                {{ isSaving ? 'Saving...' : 'Save All' }}
                                            </span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Desktop Table View -->
                                <div class="hidden md:block overflow-x-auto">
                                    <table class="w-full border-collapse border border-gray-300">
                                        <thead>
                                            <tr class="bg-gray-200">
                                                <th class="p-2 border text-xs lg:text-sm">No</th>
                                                <th class="p-2 border text-xs lg:text-sm">Material Number</th>
                                                <th class="p-2 border text-xs lg:text-sm">Description</th>
                                                <th class="p-2 border text-xs lg:text-sm cursor-pointer select-none hover:bg-gray-300 transition-colors"
                                                    @click="toggleRackSort">
                                                    <span class="inline-flex items-center gap-1">
                                                        Rack
                                                        <ArrowUp v-if="rackSortOrder === 'asc'" class="w-3 h-3" />
                                                        <ArrowDown v-else-if="rackSortOrder === 'desc'" class="w-3 h-3" />
                                                        <ArrowUpDown v-else class="w-3 h-3 opacity-40" />
                                                    </span>
                                                </th>
                                                <th class="p-2 border text-xs lg:text-sm">UoM</th>
                                                <th class="p-2 border text-xs lg:text-sm">SoH</th>
                                                <th class="p-2 border text-xs lg:text-sm bg-blue-100">Actual Qty</th>
                                                <th class="p-2 border text-xs lg:text-sm">Status</th>
                                                <th class="p-2 border text-xs lg:text-sm">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(item, index) in filteredItems" :key="item.id"
                                                class="text-center hover:bg-gray-50"
                                                :class="{ 'bg-yellow-50': item._dirty }">
                                                <td class="border p-2 font-semibold text-xs lg:text-sm">{{ index + 1 }}
                                                </td>
                                                <td class="border p-2 text-xs lg:text-sm font-medium">{{
                                                    item.material_number }}</td>
                                                <td class="border p-2 text-xs lg:text-sm text-left">{{ item.description
                                                    }}</td>
                                                <td class="border p-2 text-xs lg:text-sm">{{ item.rack_address || '-' }}
                                                </td>
                                                <td class="border p-2 text-xs lg:text-sm">{{ item.unit_of_measure }}
                                                </td>
                                                <td class="border p-2 text-xs lg:text-sm">{{ item.soh }}
                                                </td>

                                                <td class="border p-2 text-xs lg:text-sm bg-blue-50">
                                                    <input :value="item.actual_qty"
                                                        @input="handleActualQtyChange(item, $event.target.value)"
                                                        :disabled="item.status !== 'PENDING'"
                                                        type="number" min="0"
                                                        class="w-20 px-2 py-1 text-sm border-2 border-blue-300 rounded text-center focus:ring-2 focus:ring-blue-500 font-medium disabled:bg-gray-100 disabled:border-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed"
                                                        placeholder="Qty" />
                                                </td>
                                                <td class="border p-2 text-xs lg:text-sm">
                                                    <span v-if="item.status === 'COUNTED'"
                                                        class="bg-blue-200 font-semibold px-2 py-1 rounded-xl text-xs">
                                                        COUNTED
                                                    </span>
                                                    <span v-else-if="item.status === 'VERIFIED'"
                                                        class="bg-green-500 text-white font-semibold px-2 py-1 rounded-xl text-xs">
                                                        VERIFIED
                                                    </span>
                                                    <span v-else
                                                        class="bg-gray-200 px-2 py-1 rounded-xl text-xs">PENDING</span>
                                                </td>
                                                <td class="border p-2 text-xs lg:text-sm">
                                                    <div class="flex items-center justify-center gap-1">
                                                        <button @click="openSubmitModal(item)"
                                                            class="px-2 py-1 text-white rounded text-xs transition"
                                                            :class="item.status === 'PENDING' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-yellow-500 hover:bg-yellow-600'">
                                                            {{ item.status === 'PENDING' ? 'Submit' : 'Edit' }}
                                                        </button>
                                                        <button @click="openImageModal(item)"
                                                            class="p-1 rounded hover:bg-gray-100" :class="{
                                                                'text-green-600': item.image_url,
                                                                'text-gray-400': !item.image_url
                                                            }">
                                                            <Camera class="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr v-if="!filteredItems.length">
                                                <td colspan="8" class="border p-4 text-center text-gray-500 text-sm">
                                                    No items found
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Mobile Card View -->
                                <div class="md:hidden space-y-3">
                                    <div v-for="(item, index) in filteredItems" :key="item.id"
                                        class="bg-white border rounded-lg shadow-sm overflow-hidden"
                                        :class="{ 'ring-2 ring-blue-500': item._dirty }">
                                        <!-- Card Header -->
                                        <div class="bg-gray-100 px-3 py-2 flex items-center justify-between border-b">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-gray-600">#{{ index + 1 }}</span>
                                                <span class="text-sm font-semibold text-gray-900">{{
                                                    item.material_number }}</span>
                                            </div>
                                            <div>
                                                <span v-if="item.status === 'COUNTED'"
                                                    class="bg-blue-200 font-semibold px-2 py-1 rounded-lg text-xs">
                                                    COUNTED
                                                </span>
                                                <span v-else-if="item.status === 'VERIFIED'"
                                                    class="bg-green-500 text-white font-semibold px-2 py-1 rounded-lg text-xs">
                                                    VERIFIED
                                                </span>
                                                <span v-else
                                                    class="bg-gray-200 px-2 py-1 rounded-lg text-xs">PENDING</span>
                                            </div>
                                        </div>

                                        <!-- Card Body -->
                                        <div class="p-3 space-y-2">
                                            <div class="bg-yellow-50 px-2 py-1 rounded">
                                                <p class="text-xs text-gray-600">Description</p>
                                                <p class="text-sm font-medium text-gray-900">{{ item.description }}</p>
                                            </div>

                                            <div class="grid grid-cols-3 gap-2 text-xs">
                                                <div class="bg-gray-50 px-2 py-1 rounded">
                                                    <p class="text-gray-600">Rack</p>
                                                    <p class="font-semibold text-gray-900">{{ item.rack_address || '-'
                                                    }}
                                                    </p>
                                                </div>
                                                <div class="bg-gray-50 px-2 py-1 rounded">
                                                    <p class="text-gray-600">UoM</p>
                                                    <p class="font-semibold text-gray-900">{{ item.unit_of_measure }}
                                                    </p>
                                                </div>
                                                <div class="bg-gray-50 px-2 py-1 rounded">
                                                    <p class="text-gray-600">SoH</p>
                                                    <p class="font-semibold text-gray-900">{{ item.soh }}</p>
                                                </div>
                                            </div>

                                            <!-- Actual Qty Input -->
                                            <div class="bg-blue-50 px-2 py-2 rounded">
                                                <p class="text-xs text-gray-600 mb-1">Actual Qty</p>
                                                <div class="flex gap-2">
                                                    <input :value="item.actual_qty"
                                                        @input="handleActualQtyChange(item, $event.target.value)"
                                                        :disabled="item.status !== 'PENDING'"
                                                        type="number" min="0" placeholder="Enter count"
                                                        class="flex-1 px-3 py-2 text-sm border-2 border-blue-300 rounded focus:ring-2 focus:ring-blue-500 font-medium disabled:bg-gray-100 disabled:border-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed" />
                                                    <button @click="openSubmitModal(item)"
                                                        class="px-4 py-2 text-white rounded text-sm font-medium transition"
                                                        :class="item.status === 'PENDING' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-yellow-500 hover:bg-yellow-600'">
                                                        {{ item.status === 'PENDING' ? 'Submit' : 'Edit' }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card Footer -->
                                        <div class="bg-gray-50 px-3 py-2 border-t flex justify-between items-center">
                                            <button @click="openImageModal(item)"
                                                class="flex items-center gap-1 text-sm" :class="{
                                                    'text-green-600': item.image_url,
                                                    'text-gray-500': !item.image_url
                                                }">
                                                <Camera class="w-4 h-4" />
                                                {{ item.image_url ? 'View Image' : 'Add Image' }}
                                            </button>
                                            <span v-if="item.counted_at" class="text-xs text-gray-400">
                                                {{ item.counted_at }}
                                            </span>
                                        </div>
                                    </div>

                                    <div v-if="!filteredItems.length" class="text-center py-8 text-gray-500 text-sm">
                                        No items found
                                    </div>
                                </div>

                                <!-- Pagination -->
                                <div v-if="pagination.total > pagination.per_page"
                                    class="flex flex-col sm:flex-row justify-between items-center pt-4 border-t gap-3">
                                    <span class="text-sm text-gray-500">
                                        Page {{ pagination.current_page }} of {{ pagination.last_page }}
                                        ({{ pagination.total }} total items)
                                    </span>
                                    <div class="flex gap-1">
                                        <button @click="goToPage(1)"
                                            :disabled="pagination.current_page === 1"
                                            class="px-2 py-1 border rounded text-xs hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                            First
                                        </button>
                                        <button @click="goToPage(pagination.current_page - 1)"
                                            :disabled="pagination.current_page === 1"
                                            class="px-2 py-1 border rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                            <ChevronLeft class="w-4 h-4" />
                                        </button>

                                        <template v-for="page in pagination.last_page" :key="page">
                                            <button v-if="page === 1 || page === pagination.last_page || (page >= pagination.current_page - 1 && page <= pagination.current_page + 1)"
                                                @click="goToPage(page)" class="px-3 py-1 border rounded text-sm" :class="page === pagination.current_page
                                                    ? 'bg-blue-50 text-blue-600 border-blue-200'
                                                    : 'hover:bg-gray-50'">
                                                {{ page }}
                                            </button>
                                            <span v-else-if="page === pagination.current_page - 2 || page === pagination.current_page + 2"
                                                class="px-2 text-gray-400">...</span>
                                        </template>

                                        <button @click="goToPage(pagination.current_page + 1)"
                                            :disabled="pagination.current_page === pagination.last_page"
                                            class="px-2 py-1 border rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                            <ChevronRight class="w-4 h-4" />
                                        </button>
                                        <button @click="goToPage(pagination.last_page)"
                                            :disabled="pagination.current_page === pagination.last_page"
                                            class="px-2 py-1 border rounded text-xs hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                            Last
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Confirmation Modal -->
        <Modal :show="showSubmitModal" @close="closeSubmitModal" max-width="md">
            <div class="p-4 sm:p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div
                        class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                        <Check class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600" />
                    </div>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900">Confirm Stock Count</h2>
                </div>

                <div class="bg-gray-50 rounded-lg p-3 sm:p-4 mb-4 sm:mb-6 space-y-2">
                    <div class="flex flex-col sm:flex-row sm:justify-between gap-1">
                        <span class="text-xs sm:text-sm text-gray-600">Material Number:</span>
                        <span class="text-xs sm:text-sm font-semibold text-gray-900">{{ itemToSubmit?.material_number
                            }}</span>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:justify-between gap-1">
                        <span class="text-xs sm:text-sm text-gray-600">Description:</span>
                        <span class="text-xs sm:text-sm font-medium text-gray-900">{{ itemToSubmit?.description
                        }}</span>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:justify-between items-center gap-1 pt-2">
                        <label class="text-xs sm:text-sm text-gray-600 font-medium">Actual Count:</label>
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <input v-model="editingQty" type="number" min="0" class="w-full sm:w-32 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 font-bold text-lg text-blue-600 text-center" />
                            <span class="text-sm font-medium text-gray-500">{{ itemToSubmit?.unit_of_measure }}</span>
                        </div>
                    </div>
                </div>

                <p class="text-xs sm:text-sm text-gray-600 mb-4 sm:mb-6">
                    Are you sure you want to submit this count?
                </p>

                <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3">
                    <button @click="closeSubmitModal"
                        class="w-full sm:w-auto px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300 transition order-2 sm:order-1">
                        Cancel
                    </button>
                    <button @click="confirmSubmit" :disabled="isSaving"
                        class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 disabled:opacity-50 transition order-1 sm:order-2">
                        {{ isSaving ? 'Saving...' : 'Confirm Submit' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Image Upload Modal -->
        <Modal :show="showImageModal" @close="closeImageModal" max-width="lg">
            <div class="p-4 sm:p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <Camera class="w-5 h-5 text-blue-600" />
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900">Photo Evidence</h2>
                </div>

                <!-- Preview -->
                <div class="aspect-video bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center mb-4">
                    <img v-if="imagePreview" :src="imagePreview" alt="Preview"
                        class="max-w-full max-h-full object-contain" />
                    <div v-else class="text-center text-gray-400">
                        <ImageIcon class="w-12 h-12 mx-auto mb-2 opacity-30" />
                        <p class="text-sm">No image</p>
                    </div>
                </div>

                <!-- File Input -->
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center mb-4">
                    <input type="file" accept="image/jpeg,image/png,image/jpg" @change="handleImageSelect"
                        class="hidden" id="image-upload" />
                    <label for="image-upload" class="cursor-pointer">
                        <Camera class="w-8 h-8 text-gray-400 mx-auto mb-2" />
                        <p class="text-sm text-gray-600">
                            <span class="text-blue-600 font-medium">Click to select</span> or take photo
                        </p>
                        <p class="text-xs text-gray-400 mt-1">JPEG, PNG (max 5MB)</p>
                    </label>
                </div>

                <div class="flex justify-end gap-3">
                    <button @click="closeImageModal"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300">
                        Cancel
                    </button>
                    <button @click="uploadImage" :disabled="!imageFile || isUploadingImage"
                        class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 disabled:opacity-50">
                        {{ isUploadingImage ? 'Uploading...' : 'Upload Image' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Toast Message -->
        <transition name="fade">
            <div v-if="saveMessage"
                class="fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg flex items-center gap-2"
                :class="saveMessage.type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'">
                <CheckCircle v-if="saveMessage.type === 'success'" class="w-5 h-5" />
                <AlertCircle v-else class="w-5 h-5" />
                <span>{{ saveMessage.message }}</span>
            </div>
        </transition>
    </MainAppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Save,
    Camera,
    Check,
    X,
    Search,
    RefreshCw,
    Loader2,
    AlertCircle,
    CheckCircle,
    ChevronLeft,
    ChevronRight,
    ArrowUpDown,
    ArrowUp,
    ArrowDown,
    Image as ImageIcon
} from 'lucide-vue-next';
import axios from 'axios';
import MainAppLayout from '@/Layouts/MainAppLayout.vue';
import Modal from '@/Components/Modal.vue';

// Props from Inertia
const props = defineProps({
    pid: {
        type: String,
        required: true
    }
});

// --- STATE ---
const pidData = ref(null);
const items = ref([]);
const isLoading = ref(true);
const isSaving = ref(false);
const searchQuery = ref('');
const rackSortOrder = ref(null); // null | 'asc' | 'desc'
const saveMessage = ref(null);
const countedByName = ref('');
const pagination = ref({
    current_page: 1,
    per_page: 50,
    total: 0,
    last_page: 1
});
const statistics = ref({
    total: 0,
    counted: 0,
    pending: 0,
    progress: 0
});

// Modal states
const showSubmitModal = ref(false);
const itemToSubmit = ref(null);
const showImageModal = ref(false);
const editingQty = ref(null);
const selectedItem = ref(null);
const imageFile = ref(null);
const imagePreview = ref(null);
const isUploadingImage = ref(false);

// --- FETCH DATA ---
const fetchPIDData = async (page = 1) => {
    isLoading.value = true;
    try {
        const params = new URLSearchParams();
        params.append('page', page);
        params.append('per_page', pagination.value.per_page);
        if (searchQuery.value) params.append('search', searchQuery.value);
        if (rackSortOrder.value) {
            params.append('sort_by', 'rack_address');
            params.append('sort_order', rackSortOrder.value);
        }

        const response = await axios.get(`/api/annual-inventory/by-pid/${encodeURIComponent(props.pid)}?${params.toString()}`);
        if (response.data.success) {
            pidData.value = response.data.data;
            items.value = response.data.data.items.map(item => ({
                ...item,
                _dirty: false,
                _original: { ...item }
            }));
            pagination.value = response.data.data.pagination;
            statistics.value = response.data.data.statistics;
        }
    } catch (error) {
        console.error('Failed to fetch PID data:', error);
        showSaveMessage('Failed to load data', 'error');
    } finally {
        isLoading.value = false;
    }
};

// --- COMPUTED ---
const filteredItems = computed(() => {
    if (!rackSortOrder.value) return items.value;
    return [...items.value].sort((a, b) => {
        const rackA = (a.rack_address || '').toLowerCase();
        const rackB = (b.rack_address || '').toLowerCase();
        const cmp = rackA.localeCompare(rackB);
        return rackSortOrder.value === 'asc' ? cmp : -cmp;
    });
});

const pendingCount = computed(() => statistics.value.pending);
const countedCount = computed(() => statistics.value.counted);
const progressPercent = computed(() => statistics.value.progress);

const hasUnsavedChanges = computed(() => items.value.some(i => i._dirty));

// Search debounce
let searchTimeout = null;
const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchPIDData(1);
    }, 300);
};

// Pagination
const goToPage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        fetchPIDData(page);
    }
};

// --- SORT ---
const toggleRackSort = () => {
    if (rackSortOrder.value === null) rackSortOrder.value = 'asc';
    else if (rackSortOrder.value === 'asc') rackSortOrder.value = 'desc';
    else rackSortOrder.value = null;
    fetchPIDData(1);
};

// --- HANDLERS ---
const handleActualQtyChange = (item, value) => {
    item.actual_qty = value === '' ? null : parseFloat(value);
    item._dirty = true;
};

// --- SUBMIT MODAL ---
const openSubmitModal = (item) => {
    if (item.actual_qty === null || item.actual_qty === undefined) {
        showSaveMessage('Please enter actual quantity first', 'error');
        return;
    }
    itemToSubmit.value = item;
    editingQty.value = item.actual_qty;
    countedByName.value = item.counted_by || '';
    showSubmitModal.value = true;
};

const closeSubmitModal = () => {
    showSubmitModal.value = false;
    itemToSubmit.value = null;
    editingQty.value = null;
};

const confirmSubmit = async () => {
    if (!itemToSubmit.value) return;

    isSaving.value = true;
    try {
        const payload = {
            actual_qty: editingQty.value,
            counted_by: countedByName.value,
            status: 'COUNTED'
        };

        const response = await axios.put(`/api/annual-inventory/items/${itemToSubmit.value.id}`, payload);

        if (response.data.success) {
            itemToSubmit.value._dirty = false;
            itemToSubmit.value.actual_qty = parseFloat(editingQty.value);
            itemToSubmit.value.status = response.data.data.status;
            itemToSubmit.value.counted_at = response.data.data.counted_at;
            itemToSubmit.value.counted_by = countedByName.value;

            showSaveMessage('Item submitted successfully', 'success');
            closeSubmitModal();
        }
    } catch (error) {
        showSaveMessage('Failed to submit item', 'error');
    } finally {
        isSaving.value = false;
    }
};

// --- SAVE ALL ---
const saveAllItems = async () => {
    const dirtyItems = items.value.filter(i => i._dirty);
    if (dirtyItems.length === 0) return;

    isSaving.value = true;
    try {
        const payload = {
            items: dirtyItems.map(item => ({
                id: item.id,
                actual_qty: item.actual_qty,
                counted_by: item.counted_by,
                status: item.actual_qty !== null ? 'COUNTED' : 'PENDING'
            }))
        };

        const response = await axios.post('/api/annual-inventory/items/bulk-update', payload);

        if (response.data.success) {
            showSaveMessage(`Saved ${response.data.updated} items`, 'success');
            await fetchPIDData(pagination.value.current_page);
        }
    } catch (error) {
        showSaveMessage('Failed to save items', 'error');
    } finally {
        isSaving.value = false;
    }
};

// --- IMAGE MODAL ---
const openImageModal = (item) => {
    selectedItem.value = item;
    imageFile.value = null;
    imagePreview.value = item.image_url;
    showImageModal.value = true;
};

const closeImageModal = () => {
    showImageModal.value = false;
    selectedItem.value = null;
    imageFile.value = null;
    imagePreview.value = null;
};

const handleImageSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        imageFile.value = file;
        imagePreview.value = URL.createObjectURL(file);
    }
};

const uploadImage = async () => {
    if (!imageFile.value || !selectedItem.value) return;

    isUploadingImage.value = true;
    try {
        const formData = new FormData();
        formData.append('image', imageFile.value);

        const response = await axios.post(
            `/api/annual-inventory/items/${selectedItem.value.id}/upload-image`,
            formData,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );

        if (response.data.success) {
            selectedItem.value.image_path = response.data.image_path;
            selectedItem.value.image_url = response.data.image_url;
            showSaveMessage('Image uploaded', 'success');
            closeImageModal();
        }
    } catch (error) {
        showSaveMessage('Failed to upload image', 'error');
    } finally {
        isUploadingImage.value = false;
    }
};

// --- HELPERS ---
const showSaveMessage = (message, type) => {
    saveMessage.value = { message, type };
    setTimeout(() => {
        saveMessage.value = null;
    }, 3000);
};

const goBack = () => {
    router.visit('/annual-inventory');
};

// --- WATCHERS ---
watch(searchQuery, () => {
    handleSearch();
});

// --- LIFECYCLE ---
onMounted(() => {
    fetchPIDData();
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
