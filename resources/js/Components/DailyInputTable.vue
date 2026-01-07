<template>
    <div class="w-full overflow-hidden">
        <!-- Statistics & Sort Controls -->
        <div class="flex flex-col gap-3 my-2 sm:my-3">
            <!-- Statistics Badges - Grid on mobile, flex on larger screens -->
            <div class="grid grid-cols-2 sm:flex sm:flex-row gap-2 sm:gap-2 md:gap-3 lg:gap-4">
                <div
                    class="px-2 sm:px-3 md:px-4 lg:px-5 py-2 md:py-3 bg-gradient-to-br from-gray-100 to-gray-200 text-gray-700 rounded-lg md:rounded-xl border border-gray-300 text-xs sm:text-sm md:text-base flex items-center justify-center gap-1 sm:gap-2 md:gap-3 hover:shadow-md hover:from-gray-200 hover:to-gray-300 transition-all duration-200">
                    <span class="hidden sm:inline font-medium">Unchecked:</span>
                    <span class="sm:hidden font-medium">Uncheck:</span>
                    <span
                        class="font-bold bg-gray-600 text-white px-2 md:px-3 py-0.5 md:py-1 rounded-md md:rounded-lg text-xs md:text-sm shadow-sm">{{
                        stats.unchecked }}</span>
                </div>
                <div
                    class="px-2 sm:px-3 md:px-4 lg:px-5 py-2 md:py-3 bg-gradient-to-br from-red-100 to-red-200 text-red-700 rounded-lg md:rounded-xl border border-red-300 text-xs sm:text-sm md:text-base flex items-center justify-center gap-1 sm:gap-2 md:gap-3 hover:shadow-md hover:from-red-200 hover:to-red-300 transition-all duration-200">
                    <span class="hidden sm:inline font-medium">Shortage:</span>
                    <span class="sm:hidden font-medium">Short:</span>
                    <span
                        class="font-bold bg-red-600 text-white px-2 md:px-3 py-0.5 md:py-1 rounded-md md:rounded-lg text-xs md:text-sm shadow-sm">{{
                        stats.shortage }}</span>
                </div>
                <div
                    class="px-2 sm:px-3 md:px-4 lg:px-5 py-2 md:py-3 bg-gradient-to-br from-orange-100 to-orange-200 text-orange-700 rounded-lg md:rounded-xl border border-orange-300 text-xs sm:text-sm md:text-base flex items-center justify-center gap-1 sm:gap-2 md:gap-3 hover:shadow-md hover:from-orange-200 hover:to-orange-300 transition-all duration-200">
                    <span class="hidden sm:inline font-medium">Critical:</span>
                    <span class="sm:hidden font-medium">Crit:</span>
                    <span
                        class="font-bold bg-orange-600 text-white px-2 md:px-3 py-0.5 md:py-1 rounded-md md:rounded-lg text-xs md:text-sm shadow-sm">{{
                        stats.caution }}</span>
                </div>
                <div
                    class="px-2 sm:px-3 md:px-4 lg:px-5 py-2 md:py-3 bg-gradient-to-br from-blue-100 to-blue-200 text-blue-700 rounded-lg md:rounded-xl border border-blue-300 text-xs sm:text-sm md:text-base flex items-center justify-center gap-1 sm:gap-2 md:gap-3 hover:shadow-md hover:from-blue-200 hover:to-blue-300 transition-all duration-200">
                    <span class="hidden sm:inline font-medium">Overflow:</span>
                    <span class="sm:hidden font-medium">Over:</span>
                    <span
                        class="font-bold bg-blue-600 text-white px-2 md:px-3 py-0.5 md:py-1 rounded-md md:rounded-lg text-xs md:text-sm shadow-sm">{{
                        stats.overflow }}</span>
                </div>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                    <label class="text-xs sm:text-sm font-medium text-gray-700">Sort by:</label>
                    <select :value="sortOrder" @change="$emit('update:sortOrder', $event.target.value)"
                        class="w-full sm:w-auto px-3 py-2 border border-gray-300 rounded-lg text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="default">Default Order</option>
                        <option value="priority">Priority (Problems First)</option>
                        <option value="rack-asc">Rack Address (A → Z)</option>
                        <option value="rack-desc">Rack Address (Z → A)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Desktop/Tablet View (md and up) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2 border text-xs lg:text-sm">No</th>
                        <th class="p-2 border text-xs lg:text-sm">Material Number</th>
                        <th class="p-2 border  text-xs lg:text-sm">Material Description</th>
                        <th class="p-2 border  text-xs lg:text-sm">PIC</th>
                        <th class="p-2 border text-xs lg:text-sm">UoM</th>
                        <th class="p-2 border text-xs lg:text-sm cursor-pointer hover:bg-gray-300"
                            @click="toggleSortRackAddress">
                            <div class="flex items-center justify-center gap-1">
                                <span>Rack Address</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                </svg>
                            </div>
                        </th>
                        <th class="p-2 border text-xs lg:text-sm">Min Stock</th>
                        <th class="p-2 border text-xs lg:text-sm">Max Stock</th>
                        <th class="p-2 border text-xs lg:text-sm cursor-pointer hover:bg-gray-300" @click="toggleSort">
                            <div class="flex items-center justify-center gap-1">
                                <span>Status</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                </svg>
                            </div>
                        </th>
                        <th class="p-2 border text-xs lg:text-sm">Actual Stock</th>
                        <th class="p-2 border text-xs lg:text-sm">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in items" :key="item.key" class="text-center hover:bg-gray-50">
                        <td class="border p-2 font-semibold  text-xs lg:text-sm">{{ startItem + index }}</td>
                        <td class="border p-2 text-xs lg:text-sm">{{ item.material_number }}</td>
                        <td class="border p-2  text-xs lg:text-sm">{{ item.description }}</td>
                        <td class="border p-2  text-xs lg:text-sm">{{ item.pic_name }}</td>
                        <td class="border p-2 text-xs lg:text-sm">{{ item.unit_of_measure }}</td>
                        <td class="border p-2 text-xs lg:text-sm">{{ item.rack_address }}</td>
                        <td class="border p-2 text-xs lg:text-sm">{{ item.stock_minimum }}</td>
                        <td class="border p-2 text-xs lg:text-sm">{{ item.stock_maximum }}</td>
                        <td class="border p-2 text-xs lg:text-sm">
                            <span v-if="item.status === 'OK'"
                                class="bg-green-200 font-semibold px-2 py-1 rounded-xl text-xs">
                                {{ item.status }}
                            </span>
                            <span v-else-if="item.status === 'CAUTION'"
                                class="bg-yellow-200 font-semibold px-2 py-1 rounded-xl text-xs">
                                {{ item.status }}
                            </span>
                            <span v-else-if="item.status === 'SHORTAGE'"
                                class="bg-red-500 text-white font-semibold px-2 py-1 rounded-xl text-xs">
                                {{ item.status }}
                            </span>
                            <span v-else-if="item.status === 'OVERFLOW'"
                                class="bg-blue-500 text-white font-semibold px-2 py-1 rounded-xl text-xs">
                                {{ item.status }}
                            </span>
                            <span v-else class="bg-gray-200 px-2 py-1 rounded text-xs">{{ item.status }}</span>
                        </td>
                        <td class="border p-2 text-xs lg:text-sm">
                            <div v-if="item.status === 'UNCHECKED'" class="flex items-center gap-1 justify-center">
                                <input v-model.number="item.daily_stock" type="number" min="0"
                                    :placeholder="placeholder"
                                    class="w-10 px-2 py-1 text-xs border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    @keypress.enter="openSubmitModal(item)" />

                            </div>
                            <div v-else>
                                <button @click="openTimeStampModal(item)"
                                    class="hover:underline text-blue-600 font-bold">
                                    {{ item.daily_stock }}
                                </button>
                            </div>
                        </td>
                        <td class="border p-2 text-xs lg:text-sm">
                            <button v-if="item.status === 'UNCHECKED'" @click=" openSubmitModal(item)"
                                class="px-2 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700 transition whitespace-nowrap">
                                Submit
                            </button>
                            <button v-if="item.status !== 'UNCHECKED'" @click="openDeleteModal(item)"
                                class="px-2 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700 transition">
                                Delete
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!items.length">
                        <td colspan="11" class="border p-4 text-center text-gray-500 text-sm">{{ emptyMessage }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View (sm and below) -->
        <div class="md:hidden space-y-3">
            <div v-for="(item, index) in items" :key="item.key"
                class="bg-white border rounded-lg shadow-sm overflow-hidden">
                <!-- Card Header -->
                <div class="bg-gray-100 px-3 py-2 flex items-center justify-between border-b">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-gray-600">#{{ startItem + index }}</span>
                        <span class="text-sm font-semibold text-gray-900">{{ item.material_number }}</span>
                    </div>
                    <div>
                        <span v-if="item.status === 'OK'"
                            class="bg-green-200 font-semibold px-2 py-1 rounded-lg text-xs">
                            {{ item.status }}
                        </span>
                        <span v-else-if="item.status === 'CAUTION'"
                            class="bg-orange-400 font-semibold px-2 py-1 rounded-lg text-xs">
                            {{ item.status }}
                        </span>
                        <span v-else-if="item.status === 'SHORTAGE'"
                            class="bg-red-500 text-white font-semibold px-2 py-1 rounded-lg text-xs">
                            {{ item.status }}
                        </span>
                        <span v-else-if="item.status === 'OVERFLOW'"
                            class="bg-blue-500 text-white font-semibold px-2 py-1 rounded-lg text-xs">
                            {{ item.status }}
                        </span>
                        <span v-else class="bg-gray-200 px-2 py-1 rounded-lg text-xs">{{ item.status }}</span>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="p-3 space-y-2">
                    <div class="bg-yellow-50 px-2 py-1 rounded">
                        <p class="text-xs text-gray-600">Description</p>
                        <p class="text-sm font-medium text-gray-900">{{ item.description }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="bg-red-50 px-2 py-1 rounded">
                            <p class="text-gray-600">PIC</p>
                            <p class="font-semibold text-gray-900">{{ item.pic_name }}</p>
                        </div>
                        <div class="bg-gray-50 px-2 py-1 rounded">
                            <p class="text-gray-600">UoM</p>
                            <p class="font-semibold text-gray-900">{{ item.unit_of_measure }}</p>
                        </div>
                        <div class="bg-gray-50 px-2 py-1 rounded">
                            <p class="text-gray-600">Rack</p>
                            <p class="font-semibold text-gray-900">{{ item.rack_address }}</p>
                        </div>
                        <div class="bg-gray-50 px-2 py-1 rounded">
                            <p class="text-gray-600">Min - Max</p>
                            <p class="font-semibold text-gray-900">{{ item.stock_minimum }} - {{ item.stock_maximum }}
                            </p>
                        </div>
                    </div>

                    <!-- Daily Stock Section -->
                    <div v-if="item.status === 'UNCHECKED'" class="bg-blue-50 px-2 py-2 rounded">
                        <p class="text-xs text-gray-600 mb-1">Daily Stock</p>
                        <div class="flex gap-2">
                            <input v-model.number="item.daily_stock" type="number" min="0" :placeholder="placeholder"
                                class="flex-1 px-3 py-2 text-sm border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                @keypress.enter="openSubmitModal(item)" />
                            <button @click="openSubmitModal(item)"
                                class="px-4 py-2 bg-blue-600 text-white rounded text-sm font-medium hover:bg-blue-700 transition">
                                Submit
                            </button>
                        </div>
                    </div>
                    <div v-else class="bg-gray-50 px-2 py-2 rounded space-y-1">
                        <p class="text-xs text-gray-600">Daily Stock</p>
                        <p class="text-lg font-bold text-gray-900">
                            {{ item.daily_stock }}
                            <span class="text-sm text-gray-600">{{ item.unit_of_measure }}</span>
                        </p>
                        <p v-if="item.created_at" class="text-[10px] sm:text-xs text-gray-500 font-medium italic">
                            Last updated:
                            {{ new Date(item.created_at).toLocaleString('id-ID', {
                                dateStyle: 'medium',
                                timeStyle: 'short'
                            }) }}
                        </p>
                    </div>

                </div>

                <!-- Card Footer (Actions) -->
                <div v-if="item.status !== 'UNCHECKED'" class="bg-gray-50 px-3 py-2 border-t">
                    <button @click="openDeleteModal(item)"
                        class="w-full px-3 py-2 bg-red-600 text-white rounded text-sm font-medium hover:bg-red-700 transition">
                        Delete Entry
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!items.length" class="text-center py-8 text-gray-500 text-sm">
                {{ emptyMessage }}
            </div>
        </div>
    </div>

    <!-- Show Timestamp Modal -->
    <Modal :show="showTimestampModal" @close="closeTimestampModal" max-width="md">
        <div class="p-6 sm:p-8 space-y-6">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center shadow">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">
                        Last Stock Update
                    </h2>
                    <p class="text-xs text-gray-500">
                        Timestamp information for this material
                    </p>
                </div>
            </div>

            <!-- Body -->
            <div class="bg-gray-50 rounded-xl p-5 space-y-3 shadow-inner">
                <div class="border-t border-gray-300 my-3"></div>
                <div class="flex flex-col text-sm">
                    <span class="text-gray-600 mb-1">Last Input Time:</span>
                    <span class="text-lg font-bold text-blue-600">
                        {{ formattedTimestamp }}
                    </span>
                </div>

            </div>
            <!-- Footer -->
            <div class="flex justify-end">
                <button @click="closeTimestampModal"
                    class="px-5 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                    Close
                </button>
            </div>

        </div>
    </Modal>

    <!-- Submit Confirmation Modal -->
    <Modal :show="showSubmitModal" @close="closeSubmitModal" max-width="md">
        <div class="p-4 sm:p-6">
            <div class="flex items-center gap-3 mb-4">
                <div
                    class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="text-base sm:text-lg font-semibold text-gray-900">Confirm Stock Entry</h2>
            </div>

            <div class="bg-gray-50 rounded-lg p-3 sm:p-4 mb-4 sm:mb-6 space-y-2">
                <div class="flex flex-col sm:flex-row sm:justify-between gap-1">
                    <span class="text-xs sm:text-sm text-gray-600">Material Number:</span>
                    <span class="text-xs sm:text-sm font-semibold text-gray-900">{{ itemToSubmit?.material_number
                    }}</span>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between gap-1">
                    <span class="text-xs sm:text-sm text-gray-600">Description:</span>
                    <span class="text-xs sm:text-sm font-medium text-gray-900">{{ itemToSubmit?.description }}</span>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between gap-1">
                    <span class="text-xs sm:text-sm text-gray-600">Stock Value:</span>
                    <span class="text-base sm:text-lg font-bold text-blue-600">{{ itemToSubmit?.daily_stock }} {{
                        itemToSubmit?.unit_of_measure }}</span>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between gap-1">
                    <span class="text-xs sm:text-sm text-gray-600">Min - Max:</span>
                    <span class="text-xs sm:text-sm text-gray-900">{{ itemToSubmit?.stock_minimum }} - {{
                        itemToSubmit?.stock_maximum }}</span>
                </div>
            </div>

            <p class="text-xs sm:text-sm text-gray-600 mb-4 sm:mb-6">
                Are you sure you want to submit this stock entry?
            </p>

            <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3">
                <button @click="closeSubmitModal"
                    class="w-full sm:w-auto px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300 transition order-2 sm:order-1">
                    Cancel
                </button>
                <button @click="confirmSubmit"
                    class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 transition order-1 sm:order-2">
                    Confirm Submit
                </button>
            </div>
        </div>
    </Modal>

    <!-- Delete Confirmation Modal -->
    <Modal :show="showDeleteModal" @close="closeDeleteModal" max-width="md">
        <div class="p-4 sm:p-6">
            <div class="flex items-center gap-3 mb-4">
                <div
                    class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-red-600" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h2 class="text-base sm:text-lg font-semibold text-gray-900">Confirm Delete</h2>
            </div>

            <p class="text-sm text-gray-600 mb-2 sm:mb-4">
                Are you sure you want to delete the entry for
                <span class="font-semibold">{{ itemToDelete?.material_number }}</span>?
            </p>
            <p class="text-xs sm:text-sm text-gray-500 mb-4 sm:mb-6">
                {{ itemToDelete?.description }}
            </p>

            <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3">
                <button @click="closeDeleteModal"
                    class="w-full sm:w-auto px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300 transition order-2 sm:order-1">
                    Cancel
                </button>
                <button @click="confirmDelete"
                    class="w-full sm:w-auto px-4 py-2 bg-red-600 text-white rounded text-sm hover:bg-red-700 transition order-1 sm:order-2">
                    Delete
                </button>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { ref, computed } from 'vue'
import Modal from './Modal.vue'


const props = defineProps({

    items: {
        type: Array,
        required: true,
        default: () => []
    },
    placeholder: {
        type: String,
        default: 'Qty'
    },
    emptyMessage: {
        type: String,
        default: 'No data available'
    },
    uncheckedCount: {
        type: Number,
        default: 0
    },
    stats: {
        type: Object,
        default: () => ({
            unchecked: 0,
            shortage: 0,
            caution: 0,
            overflow: 0
        })
    },
    sortOrder: {
        type: String,
        default: 'default'
    },
    startItem: {
        type: Number,
        required: true
    },
    rackOrder: {
        type: String,
        default: 'rack-asc'
    }


})

const emit = defineEmits(['submit', 'delete', 'update:sortOrder', 'update:rackOrder'])

// Toggle sort handler
const toggleSort = () => {
    let newOrder
    if (props.sortOrder === 'default') {
        newOrder = 'priority'
    } else {
        newOrder = 'default'
    }
    emit('update:sortOrder', newOrder)
}

const toggleSortRackAddress = () => {
    let newOrder
    if (props.sortOrder === 'rack-asc') {
        newOrder = 'rack-desc'
    } else {
        newOrder = 'rack-asc'
    } emit('update:sortOrder', newOrder)
}

// Submit modal state
const showSubmitModal = ref(false)
const itemToSubmit = ref(null)

// Delete modal state
const showDeleteModal = ref(false)
const itemToDelete = ref(null)

// Timestamp modal state
const showTimestampModal = ref(false)
const itemToCheckTimestamp = ref(null)

const formattedTimestamp = computed(() => {
    if (!itemToCheckTimestamp.value?.created_at)
        return 'No timestamp available'

    return new Date(itemToCheckTimestamp.value.created_at).toLocaleString('id-ID', {
        dateStyle: 'full',
        timeStyle: 'short'
    })
})

const isValidInput = (value) => {
    return value !== null && value !== undefined && value !== '' && value >= 0
}

const openSubmitModal = (item) => {
    if (!isValidInput(item.daily_stock)) {
        alert(`Please enter a valid stock value for ${item.material_number}`)
        return
    }
    itemToSubmit.value = item
    showSubmitModal.value = true
}

const openTimeStampModal = (item) => {
    if (!item.created_at) {
        alert('No Input')
    }
    itemToCheckTimestamp.value = item
    showTimestampModal.value = true
}

const closeTimestampModal = () => {
    showTimestampModal.value = false
    itemToCheckTimestamp.value = null

}

const closeSubmitModal = () => {
    showSubmitModal.value = false
    itemToSubmit.value = null
}

const confirmSubmit = () => {
    if (itemToSubmit.value) {
        emit('submit', itemToSubmit.value)
        closeSubmitModal()
    }
}

const openDeleteModal = (item) => {
    itemToDelete.value = item
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    showDeleteModal.value = false
    itemToDelete.value = null
}

const confirmDelete = () => {
    if (itemToDelete.value) {
        emit('delete', itemToDelete.value)
        closeDeleteModal()
    }
}
</script>
