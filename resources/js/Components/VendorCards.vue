<template>
    <section class="rounded-2xl border border-gray-100 bg-white shadow-sm">

        <!-- Accordion Header -->
        <button @click="isOpen = !isOpen" class="w-full flex items-center justify-between p-4 sm:p-5">
            <div class="text-left space-y-1">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-500">
                    Vendor
                </p>

                <h2 class="text-lg sm:text-xl font-semibold text-gray-900">
                    {{ vendor.vendor_name || 'Unnamed Vendor' }}
                </h2>

                <p class="text-xs sm:text-sm text-gray-500">
                    Vendor #{{ vendor.vendor_number || '-' }}
                </p>
            </div>

            <span class="text-gray-500 transition-transform" :class="isOpen ? 'rotate-180' : ''">
                ▼
            </span>
        </button>

        <!-- Divider -->
        <div class="border-t border-gray-200"></div>

        <!-- Accordion Content -->
        <transition name="slide-fade">
            <div v-if="isOpen" class="p-4 sm:p-5">

                <!-- Vendor Action Buttons -->
                <div class="flex flex-wrap gap-2 mb-4 justify-center">
                    <button
                        class="inline-flex items-center rounded-lg border border-blue-100 bg-blue-50 px-3 py-1.5 text-xs sm:text-sm font-medium text-blue-700 hover:bg-blue-100"
                        @click.stop="handleEditVendor">
                        Edit Vendor
                    </button>

                    <button
                        class="inline-flex items-center rounded-lg border border-red-100 bg-red-50 px-3 py-1.5 text-xs sm:text-sm font-medium text-red-700 hover:bg-red-100"
                        @click.stop="handleDeleteVendor">
                        Delete Vendor
                    </button>
                </div>

                <!-- Vendor Details -->
                <dl
                    class="grid grid-cols-1 gap-4 rounded-xl bg-gray-50 p-4 text-xs sm:text-sm text-gray-700 sm:grid-cols-2 lg:grid-rows-1">
                    <div>
                        <dt class="uppercase tracking-wide text-gray-500 text-[10px] sm:text-xs">
                            Phone
                        </dt>
                        <dd class="mt-1 font-medium">{{ vendor.phone_number || '-' }}</dd>
                    </div>

                    <div>
                        <dt class="uppercase tracking-wide text-gray-500 text-[10px] sm:text-xs">
                            Email
                        </dt>
                        <dd class="mt-1 font-medium break-all">{{ primaryEmail || '-' }}</dd>
                    </div>

                    <div>
                        <dt class="uppercase tracking-wide text-gray-500 text-[10px] sm:text-xs">
                            Materials Count
                        </dt>
                        <dd class="mt-1 font-medium">{{ materials.length }}</dd>
                    </div>
                </dl>

                <!-- Materials Section -->
                <div class="mt-6">
                    <!-- Material action buttons -->
                    <div class="flex flex-wrap gap-2 mb-4 justify-center">
                        <button
                            class="inline-flex items-center rounded-lg border border-blue-100 bg-blue-50 px-3 py-1.5 text-xs sm:text-sm font-medium text-blue-700 hover:bg-blue-100"
                            @click.stop="handleAttachMaterial">
                            Add Existing Materials
                        </button>
                        <button
                            class="inline-flex items-center rounded-lg border border-blue-100 bg-blue-50 px-3 py-1.5 text-xs sm:text-sm font-medium text-blue-700 hover:bg-blue-100"
                            @click.stop="handleCreateMaterial">
                            Create New Material
                        </button>
                    </div>

                    <div class="flex items-center justify-between">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900">Materials</h3>
                        <span class="text-xs sm:text-sm text-gray-500">{{ materials.length }} items</span>
                    </div>

                    <div v-if="!materials.length"
                        class="mt-4 rounded-xl border border-dashed border-gray-200 p-4 text-xs sm:text-sm text-gray-500">
                        This vendor has no materials linked yet.
                    </div>

                    <!-- Scrollable Materials List -->
                    <div v-else
                        class="mt-4 max-h-64 sm:max-h-80 overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
                        <ul class="space-y-3">
                            <li v-for="material in materials" :key="material.id"
                                class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm hover:border-blue-200 hover:shadow-md transition">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                                    <!-- Material Info -->
                                    <div>
                                        <p class="text-xs sm:text-sm uppercase tracking-wide text-gray-500">
                                            {{ material.material_number || '-' }}
                                        </p>

                                        <p class="text-sm sm:text-base font-semibold text-gray-900">
                                            {{ material.description || 'Unnamed Material' }}
                                        </p>

                                        <div class="mt-1 flex flex-wrap gap-2 text-[11px] sm:text-xs text-gray-500">
                                            <span>Usage: <span class="font-medium">{{ material.usage }}</span></span>
                                            <span>Location: <span class="font-medium">{{ material.location
                                            }}</span></span>

                                        </div>
                                    </div>

                                    <!-- Buttons -->
                                    <div class="flex gap-2 text-xs sm:text-sm">
                                        <button
                                            class="rounded-lg border border-blue-100 bg-blue-50 px-3 py-1.5 font-medium text-blue-700 hover:bg-blue-100"
                                            @click="handleEditMaterial(material)">
                                            Edit
                                        </button>

                                        <button
                                            class="rounded-lg border border-red-100 bg-red-50 px-3 py-1.5 font-medium text-red-700 hover:bg-red-100"
                                            @click="handleRemoveMaterial(material)">
                                            Remove
                                        </button>
                                    </div>

                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </transition>

    </section>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    vendorData: Object
})

const emit = defineEmits([
    'edit-vendor',
    'delete-vendor',
    'edit-material',
    'remove-material',
    'create-material',
    'attach-material'
])

const vendor = computed(() => props.vendorData?.vendor || {})
const materials = computed(() => props.vendorData?.materials || [])

const primaryEmail = computed(() => {
    const emails = vendor.value.emails
    return Array.isArray(emails) ? emails[0] : emails ?? ''
})

const isOpen = ref(false)

const handleEditVendor = () => emit('edit-vendor', vendor.value)
const handleDeleteVendor = () => emit('delete-vendor', vendor.value)
const handleEditMaterial = (material) => emit('edit-material', { vendor: vendor.value, material })
const handleRemoveMaterial = (material) => emit('remove-material', { vendor: vendor.value, material })
const handleCreateMaterial = () => emit('create-material', vendor.value)
const handleAttachMaterial = () => emit('attach-material', vendor.value)
</script>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.25s ease;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}

.scrollbar-thin::-webkit-scrollbar {
    width: 6px;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
    background: #d1d1d1;
    border-radius: 6px;
}

.scrollbar-thin::-webkit-scrollbar-track {
    background: #f3f3f3;
}
</style>
