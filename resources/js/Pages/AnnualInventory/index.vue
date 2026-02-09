<template>
    <MainAppLayout title="Annual Inventory" subtitle="Manage annual inventory PIDs">
        <div class="min-h-screen w-full overflow-hidden bg-gray-50">
            <div class="flex">
                <div class="flex-1">
                    <div class="mx-auto w-full max-w-7xl px-3 sm:px-6 lg:px-8">
                        <h1 class="flex flex-col mx-auto text-xl sm:text-2xl font-bold text-center my-6 sm:my-10">
                            Annual Inventory Taking Input
                        </h1>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 sm:gap-4 mb-4 sm:mb-6">
                            <div class="bg-white rounded-xl p-3 sm:p-4 shadow-sm border border-gray-200">
                                <p class="text-[10px] sm:text-xs text-gray-500 uppercase tracking-wide">Total PIDs</p>
                                <p class="text-xl sm:text-2xl font-bold text-gray-800 truncate">{{ statistics.pids.total
                                    }}</p>
                            </div>
                            <div class="bg-white rounded-xl p-3 sm:p-4 shadow-sm border border-gray-200">
                                <p class="text-[10px] sm:text-xs text-gray-500 uppercase tracking-wide">Completed</p>
                                <p class="text-xl sm:text-2xl font-bold text-green-600 truncate">{{
                                    statistics.pids.completed }}</p>
                            </div>
                            <div class="bg-white rounded-xl p-3 sm:p-4 shadow-sm border border-gray-200">
                                <p class="text-[10px] sm:text-xs text-gray-500 uppercase tracking-wide">In Progress</p>
                                <p class="text-xl sm:text-2xl font-bold text-blue-600 truncate">{{
                                    statistics.pids.in_progress }}</p>
                            </div>
                            <div class="bg-white rounded-xl p-3 sm:p-4 shadow-sm border border-gray-200">
                                <p class="text-[10px] sm:text-xs text-gray-500 uppercase tracking-wide">Completion Rate
                                </p>
                                <p class="text-xl sm:text-2xl font-bold text-gray-800 truncate">{{
                                    statistics.pids.completion_rate }}%</p>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg p-3 sm:p-4 relative">
                            <div v-if="isLoading"
                                class="absolute inset-0 bg-white/70 flex items-center justify-center z-20 h-full rounded-xl">
                                <div class="text-center">
                                    <div
                                        class="inline-block animate-spin rounded-full h-8 w-8 sm:h-10 sm:w-10 border-b-2 border-blue-600 mb-3">
                                    </div>
                                    <p class="text-xs sm:text-sm text-gray-600 font-medium">Loading data...</p>
                                </div>
                            </div>

                            <div class="space-y-3 sm:space-y-4">
                                <div class="relative">
                                    <Search
                                        class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                                    <input v-model="searchQuery" type="text" placeholder="Search PID or PIC..."
                                        class="w-full pl-10 pr-4 py-2 sm:py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-shadow" />
                                    <button v-if="searchQuery" @click="searchQuery = ''"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 p-1">
                                        <X class="w-4 h-4" />
                                    </button>
                                </div>

                                <div class="flex flex-col lg:flex-row gap-3 lg:gap-4">
                                    <div
                                        class="flex-1 flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3">
                                        <div
                                            class="hidden sm:flex items-center gap-2 text-sm text-gray-500 font-medium">
                                            <Filter class="w-4 h-4" />
                                            <span>Filter:</span>
                                        </div>
                                        <div class="grid grid-cols-2 w-full sm:w-auto gap-2">
                                            <select v-model="locationFilter"
                                                class="w-full sm:w-36 px-2 sm:px-3 py-2 bg-white border border-gray-300 rounded-lg text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 outline-none truncate">
                                                <option value="">All Locations</option>
                                                <option v-for="loc in locations" :key="loc" :value="loc">{{ loc }}
                                                </option>
                                            </select>
                                            <select v-model="statusFilter"
                                                class="w-full sm:w-36 px-2 sm:px-3 py-2 bg-white border border-gray-300 rounded-lg text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 outline-none truncate">
                                                <option value="">All Statuses</option>
                                                <option value="Not Checked">Not Checked</option>
                                                <option value="In Progress">In Progress</option>
                                                <option value="Completed">Completed</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap sm:flex-row gap-2 lg:gap-3 w-full lg:w-auto">
                                        <button @click="fetchPIDs"
                                            class="flex-1 sm:flex-none px-2 sm:px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors font-medium text-xs sm:text-sm shadow-sm flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2">
                                            <RefreshCw class="w-4 h-4" />
                                            <span>Refresh</span>
                                        </button>
                                        <button @click="downloadPIDExcel('selected')"
                                            :disabled="isDownloading || selectedPids.length === 0"
                                            class="flex-1 sm:flex-none px-2 sm:px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium text-xs sm:text-sm shadow-sm flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2">
                                            <Loader2 v-if="isDownloading" class="w-4 h-4 animate-spin" />
                                            <Download v-else class="w-4 h-4" />
                                            <span>{{ selectedPids.length > 0 ? `Download (${selectedPids.length})` :
                                                'Select PIDs' }}</span>
                                        </button>
                                        <button @click="downloadPIDExcel('all')" :disabled="isDownloading"
                                            class="flex-1 sm:flex-none px-2 sm:px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium text-xs sm:text-sm shadow-sm flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2">
                                            <Loader2 v-if="isDownloading" class="w-4 h-4 animate-spin" />
                                            <Download v-else class="w-4 h-4" />
                                            <span>Download All</span>
                                        </button>
                                        <button @click="openUploadModal"
                                            class="flex-1 sm:flex-none px-2 sm:px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium text-xs sm:text-sm shadow-sm flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2">
                                            <Upload class="w-4 h-4" />
                                            <span>Upload</span>
                                        </button>
                                        <button @click="syncPicAndGroupLeader" :disabled="isSyncing"
                                            class="flex-1 sm:flex-none px-2 sm:px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 disabled:opacity-50 transition-colors font-medium text-xs sm:text-sm shadow-sm flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2">
                                            <Loader2 v-if="isSyncing" class="w-4 h-4 animate-spin" />
                                            <RefreshCw v-else class="w-4 h-4" />
                                            <span>{{ isSyncing ? 'Syncing...' : 'Sync PIC & GL' }}</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Selection info bar -->
                                <div v-if="selectedPids.length > 0"
                                    class="flex items-center justify-between bg-purple-50 border border-purple-200 rounded-lg px-4 py-2">
                                    <span class="text-sm text-purple-700 font-medium">
                                        {{ selectedPids.length }} PID{{ selectedPids.length > 1 ? 's' : '' }} selected
                                    </span>
                                    <button @click="selectedPids = []"
                                        class="text-sm text-purple-600 hover:text-purple-800 font-medium flex items-center gap-1">
                                        <X class="w-4 h-4" />
                                        Clear Selection
                                    </button>
                                </div>

                                <div class="hidden md:block overflow-x-auto rounded-lg border border-gray-200">
                                    <table class="w-full border-collapse">
                                        <thead>
                                            <tr class="bg-gray-100/50">
                                                <th class="p-3 border-b text-center w-10">
                                                    <input type="checkbox" :checked="isAllSelected"
                                                        :indeterminate="isIndeterminate" @change="toggleSelectAll"
                                                        class="w-4 h-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500 cursor-pointer" />
                                                </th>
                                                <th
                                                    class="p-3 border-b text-xs font-semibold text-gray-600 uppercase tracking-wider text-left w-12">
                                                    No</th>
                                                <th
                                                    class="p-3 border-b text-xs font-semibold text-gray-600 uppercase tracking-wider text-left">
                                                    PID Number</th>
                                                <th
                                                    class="p-3 border-b text-xs font-semibold text-gray-600 uppercase tracking-wider text-left">
                                                    Location</th>
                                                <th
                                                    class="p-3 border-b text-xs font-semibold text-gray-600 uppercase tracking-wider text-left">
                                                    PIC</th>
                                                <th
                                                    class="p-3 border-b text-xs font-semibold text-gray-600 uppercase tracking-wider text-center">
                                                    Progress</th>
                                                <th
                                                    class="p-3 border-b text-xs font-semibold text-gray-600 uppercase tracking-wider text-center">
                                                    Status</th>
                                                <th
                                                    class="p-3 border-b text-xs font-semibold text-gray-600 uppercase tracking-wider text-center">
                                                    Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <tr v-for="(item, index) in inventoryItems" :key="item.id"
                                                class="hover:bg-gray-50 transition-colors"
                                                :class="{ 'bg-purple-50': selectedPids.includes(item.pid) }">
                                                <td class="p-3 text-center">
                                                    <input type="checkbox" :value="item.pid" v-model="selectedPids"
                                                        class="w-4 h-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500 cursor-pointer" />
                                                </td>
                                                <td class="p-3 text-sm text-gray-600">
                                                    {{ (pagination.current_page - 1) * pagination.per_page + index + 1
                                                    }}
                                                </td>
                                                <td class="p-3 text-sm font-medium text-gray-900">
                                                    <div class="flex items-center gap-2">
                                                        <FileText class="w-4 h-4 text-gray-400" />
                                                        {{ item.pid }}
                                                    </div>
                                                </td>
                                                <td class="p-3 text-sm text-gray-600">{{ item.location }}</td>
                                                <td class="p-3 text-sm text-gray-600">
                                                    <span v-if="item.pic_name"
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        {{ item.pic_name }}
                                                    </span>
                                                    <span v-else class="text-gray-400">-</span>
                                                </td>
                                                <td class="p-3 text-sm text-gray-600">
                                                    <div class="flex flex-col items-center justify-center gap-1">
                                                        <div
                                                            class="w-full max-w-[100px] h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                                            <div class="h-full bg-blue-500 rounded-full transition-all duration-500"
                                                                :style="{ width: `${item.progress}%` }"></div>
                                                        </div>
                                                        <span class="text-[10px] text-gray-500">{{ item.counted_count
                                                        }}/{{ item.items_count }}</span>
                                                    </div>
                                                </td>
                                                <td class="p-3 text-sm text-center">
                                                    <span v-if="item.status === 'Completed'"
                                                        class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                                                        Completed
                                                    </span>
                                                    <span v-else-if="item.status === 'In Progress'"
                                                        class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                                                        In Progress
                                                    </span>
                                                    <span v-else
                                                        class="bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs font-medium">
                                                        Not Checked
                                                    </span>
                                                </td>
                                                <td class="p-3 text-sm">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <button @click="navigateToDetail(item.pid)"
                                                            class="px-3 py-1 bg-white border border-blue-600 text-blue-600 rounded text-xs hover:bg-blue-50 transition">
                                                            Submit
                                                        </button>
                                                        <button @click="openEditModal(item)"
                                                            class="text-gray-400 hover:text-yellow-600 transition">
                                                            <Pencil class="w-4 h-4" />
                                                        </button>
                                                        <button @click="confirmDelete(item)"
                                                            class="text-gray-400 hover:text-red-600 transition">
                                                            <Trash2 class="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr v-if="!inventoryItems.length">
                                                <td colspan="8" class="p-8 text-center text-gray-500 text-sm">
                                                    <div class="flex flex-col items-center gap-2">
                                                        <FileText class="w-8 h-8 text-gray-300" />
                                                        <p>No inventory records found</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="md:hidden space-y-3">
                                    <div v-for="(item, index) in inventoryItems" :key="item.id"
                                        class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden active:shadow-md transition-shadow"
                                        :class="{ 'border-purple-400 bg-purple-50/30': selectedPids.includes(item.pid) }">
                                        <div
                                            class="bg-gray-50/50 px-3 py-2.5 flex items-center justify-between border-b border-gray-100">
                                            <div class="flex items-center gap-2">
                                                <input type="checkbox" :value="item.pid" v-model="selectedPids"
                                                    class="w-4 h-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500 cursor-pointer" />
                                                <span
                                                    class="bg-blue-100 text-blue-700 text-[10px] font-bold px-1.5 py-0.5 rounded">
                                                    #{{ (pagination.current_page - 1) * pagination.per_page + index + 1
                                                    }}
                                                </span>
                                                <span class="text-sm font-bold text-gray-900">{{ item.pid }}</span>
                                            </div>
                                            <div>
                                                <span v-if="item.status === 'Completed'"
                                                    class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide">
                                                    Done
                                                </span>
                                                <span v-else-if="item.status === 'In Progress'"
                                                    class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide">
                                                    Active
                                                </span>
                                                <span v-else
                                                    class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide">
                                                    New
                                                </span>
                                            </div>
                                        </div>

                                        <div class="p-3 space-y-2.5">
                                            <div class="grid grid-cols-2 gap-2 text-xs">
                                                <div>
                                                    <p class="text-gray-500 text-[10px] uppercase">Location</p>
                                                    <p class="font-medium text-gray-900 truncate">{{ item.location }}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p class="text-gray-500 text-[10px] uppercase">PIC</p>
                                                    <p class="font-medium text-gray-900 truncate">{{ item.pic_name ||
                                                        '-' }}</p>
                                                </div>
                                            </div>

                                            <div>
                                                <div class="flex justify-between items-center mb-1">
                                                    <span class="text-[10px] text-gray-500 uppercase">Progress</span>
                                                    <span class="text-xs font-bold text-blue-600">{{ item.counted_count
                                                    }}/{{ item.items_count }}</span>
                                                </div>
                                                <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                                    <div class="h-full bg-blue-500 rounded-full transition-all"
                                                        :style="{ width: `${item.progress}%` }"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            class="bg-gray-50 px-3 py-2 border-t border-gray-100 grid grid-cols-5 gap-2">
                                            <button @click="navigateToDetail(item.pid)"
                                                class="col-span-3 px-3 py-2 bg-blue-600 text-white rounded-lg text-xs font-semibold hover:bg-blue-700 active:bg-blue-800 transition shadow-sm flex items-center justify-center gap-1.5">
                                                <span>Submit Data</span>
                                                <ChevronRight class="w-3 h-3" />
                                            </button>
                                            <button @click="openEditModal(item)"
                                                class="col-span-1 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-xs hover:bg-gray-50 flex items-center justify-center shadow-sm">
                                                <Pencil class="w-4 h-4" />
                                            </button>
                                            <button @click="confirmDelete(item)"
                                                class="col-span-1 py-2 bg-white border border-red-200 text-red-600 rounded-lg text-xs hover:bg-red-50 flex items-center justify-center shadow-sm">
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>

                                    <div v-if="!inventoryItems.length"
                                        class="text-center py-10 bg-white rounded-xl border border-dashed border-gray-300">
                                        <p class="text-gray-500 text-sm">No records found matching filters.</p>
                                    </div>
                                </div>

                                <div v-if="pagination.total > pagination.per_page"
                                    class="flex flex-col sm:flex-row justify-between items-center gap-3 pt-4 border-t border-gray-100">
                                    <span class="text-xs sm:text-sm text-gray-500">
                                        {{ inventoryItems.length }} of {{ pagination.total }}
                                    </span>
                                    <div class="flex gap-1">
                                        <button @click="goToPage(pagination.current_page - 1)"
                                            :disabled="pagination.current_page === 1"
                                            class="p-1.5 sm:px-3 sm:py-1 border rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition">
                                            <ChevronLeft class="w-4 h-4" />
                                        </button>

                                        <div class="flex sm:hidden items-center px-2 text-sm font-medium">
                                            Page {{ pagination.current_page }} / {{ pagination.last_page }}
                                        </div>

                                        <template v-for="page in pagination.last_page" :key="page">
                                            <button
                                                v-if="page === 1 || page === pagination.last_page || (page >= pagination.current_page - 1 && page <= pagination.current_page + 1)"
                                                @click="goToPage(page)"
                                                class="hidden sm:block px-3 py-1 border rounded text-sm transition"
                                                :class="page === pagination.current_page
                                                    ? 'bg-blue-50 text-blue-600 border-blue-200 font-medium'
                                                    : 'hover:bg-gray-50 text-gray-600'">
                                                {{ page }}
                                            </button>
                                            <span
                                                v-else-if="page === pagination.current_page - 2 || page === pagination.current_page + 2"
                                                class="hidden sm:block px-2 text-gray-400">...</span>
                                        </template>

                                        <button @click="goToPage(pagination.current_page + 1)"
                                            :disabled="pagination.current_page === pagination.last_page"
                                            class="p-1.5 sm:px-3 sm:py-1 border rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition">
                                            <ChevronRight class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showEditModal" @close="closeEditModal" max-width="md">
            <div class="p-4 sm:p-6 w-full">
                <div class="flex items-center gap-3 mb-4">
                    <div
                        class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-yellow-100 flex items-center justify-center flex-shrink-0">
                        <Pencil class="w-4 h-4 sm:w-5 sm:h-5 text-yellow-600" />
                    </div>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900">Edit PID</h2>
                </div>

                <div class="space-y-3 sm:space-y-4 mb-6">
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">PID Number</label>
                        <input v-model="editForm.pid" type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                            placeholder="Enter PID number" />
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Location</label>
                        <input v-model="editForm.location" type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                            placeholder="Enter location" />
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">PIC Name</label>
                        <input v-model="editForm.pic_name" type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                            placeholder="Enter PIC name" />
                    </div>
                </div>

                <div class="flex justify-end gap-2 sm:gap-3">
                    <button @click="closeEditModal"
                        class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-xs sm:text-sm hover:bg-gray-200 font-medium">
                        Cancel
                    </button>
                    <button @click="saveEdit" :disabled="isSaving"
                        class="px-3 py-2 bg-yellow-600 text-white rounded-lg text-xs sm:text-sm hover:bg-yellow-700 disabled:opacity-50 flex items-center gap-2 font-medium">
                        <Loader2 v-if="isSaving" class="w-3 h-3 sm:w-4 sm:h-4 animate-spin" />
                        {{ isSaving ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </div>
        </Modal>

        <Modal :show="showDeleteModal" @close="closeDeleteModal" max-width="sm">
            <div class="p-4 sm:p-6 w-full">
                <div class="flex items-center gap-3 mb-4">
                    <div
                        class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                        <Trash2 class="w-4 h-4 sm:w-5 sm:h-5 text-red-600" />
                    </div>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900">Delete PID</h2>
                </div>

                <p class="text-xs sm:text-sm text-gray-600 mb-6 leading-relaxed">
                    Are you sure you want to delete PID <strong>{{ deleteItem?.pid }}</strong>?
                    This will also delete all <span class="font-medium text-red-600">{{ deleteItem?.items_count || 0
                    }}</span>
                    associated items.
                </p>

                <div class="flex justify-end gap-2 sm:gap-3">
                    <button @click="closeDeleteModal"
                        class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-xs sm:text-sm hover:bg-gray-200 font-medium">
                        Cancel
                    </button>
                    <button @click="deletePID" :disabled="isDeleting"
                        class="px-3 py-2 bg-red-600 text-white rounded-lg text-xs sm:text-sm hover:bg-red-700 disabled:opacity-50 flex items-center gap-2 font-medium">
                        <Loader2 v-if="isDeleting" class="w-3 h-3 sm:w-4 sm:h-4 animate-spin" />
                        {{ isDeleting ? 'Deleting...' : 'Delete' }}
                    </button>
                </div>
            </div>
        </Modal>

        <Modal :show="showUploadModal" @close="closeUploadModal" max-width="lg">
            <div class="p-4 sm:p-6 w-full">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                            <Upload class="w-4 h-4 sm:w-5 sm:h-5 text-green-600" />
                        </div>
                        <div>
                            <h2 class="text-base sm:text-lg font-semibold text-gray-900">Bulk Upload</h2>
                            <p class="text-[10px] sm:text-xs text-gray-500">Upload multiple PIDs</p>
                        </div>
                    </div>
                </div>

                <div
                    class="border-2 border-dashed border-gray-300 rounded-xl p-4 sm:p-6 text-center mb-4 hover:border-blue-400 hover:bg-blue-50/30 transition-colors bg-gray-50">
                    <input type="file" accept=".xlsx,.xls,.csv" @change="handleFileSelect" class="hidden"
                        id="file-upload" multiple />
                    <label for="file-upload" class="cursor-pointer block w-full h-full">
                        <Upload class="w-8 h-8 sm:w-10 sm:h-10 text-gray-400 mx-auto mb-2" />
                        <p class="text-xs sm:text-sm text-gray-600">
                            <span class="text-blue-600 font-medium">Click to upload</span> or drag
                        </p>
                        <p class="text-[10px] text-gray-400 mt-1">XLSX, CSV (Multiple allowed)</p>
                    </label>
                </div>

                <div v-if="uploadFiles.length > 0" class="mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs sm:text-sm font-medium text-gray-700">Selected ({{ uploadFiles.length }}):</p>
                        <button @click="clearFiles"
                            class="text-[10px] sm:text-xs text-red-600 hover:text-red-800 font-medium">Clear
                            All</button>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-2 max-h-32 overflow-y-auto space-y-2 border border-gray-100">
                        <div v-for="(file, index) in uploadFiles" :key="index"
                            class="flex items-center justify-between bg-white px-2 py-1.5 rounded border border-gray-200 text-xs sm:text-sm">
                            <div class="flex items-center gap-2 truncate flex-1">
                                <FileText class="w-3 h-3 sm:w-4 sm:h-4 text-green-600 flex-shrink-0" />
                                <span class="text-gray-700 truncate">{{ file.name }}</span>
                            </div>
                            <button @click="removeFile(index)" class="text-red-500 hover:text-red-700 ml-2">
                                <X class="w-3 h-3 sm:w-4 sm:h-4" />
                            </button>
                        </div>
                    </div>
                </div>


                <div v-if="isUploading && uploadProgress.total > 0" class="mb-4">
                    <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                        <span>Uploading {{ uploadProgress.current }}/{{ uploadProgress.total }}</span>
                        <span>{{ Math.round((uploadProgress.current / uploadProgress.total) * 100) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                        <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-300"
                            :style="{ width: `${(uploadProgress.current / uploadProgress.total) * 100}%` }"></div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 sm:gap-3">
                    <button @click="closeUploadModal"
                        class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-xs sm:text-sm hover:bg-gray-200 font-medium">
                        Close
                    </button>
                    <button @click="uploadAllFiles" :disabled="uploadFiles.length === 0 || isUploading"
                        class="px-3 py-2 bg-green-600 text-white rounded-lg text-xs sm:text-sm hover:bg-green-700 disabled:opacity-50 flex items-center gap-2 font-medium">
                        <Loader2 v-if="isUploading" class="w-3 h-3 sm:w-4 sm:h-4 animate-spin" />
                        {{ isUploading ? 'Uploading...' : 'Start Upload' }}
                    </button>
                </div>
            </div>
        </Modal>
    </MainAppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    Search,
    Upload,
    Download,
    Filter,
    ChevronRight,
    ChevronLeft,
    FileText,
    X,
    Loader2,
    AlertCircle,
    CheckCircle,
    RefreshCw,
    Pencil,
    Trash2
} from 'lucide-vue-next';
import axios from 'axios';
import MainAppLayout from '@/Layouts/MainAppLayout.vue';
import Modal from '@/Components/Modal.vue';

// --- STATE ---
const inventoryItems = ref([]);
const pagination = ref({
    current_page: 1,
    per_page: 20,
    total: 0,
    last_page: 1
});
const statistics = ref({
    pids: { total: 0, completed: 0, in_progress: 0, not_checked: 0, completion_rate: 0 },
    items: { total: 0, counted: 0, pending: 0, completion_rate: 0 }
});
const locations = ref([]);

const searchQuery = ref('');
const locationFilter = ref('');
const statusFilter = ref('');
const isLoading = ref(false);
const isUploading = ref(false);

// Upload modal state
const showUploadModal = ref(false);
const uploadError = ref(null);
const uploadFiles = ref([]);
const uploadResults = ref([]);
const uploadProgress = ref({
    current: 0,
    total: 0
});
const uploadSummary = ref(null);

// Edit modal state
const showEditModal = ref(false);
const editItem = ref(null);
const editForm = ref({ pid: '', location: '', pic_name: '' });
const isSaving = ref(false);

// Delete modal state
const showDeleteModal = ref(false);
const deleteItem = ref(null);
const isDeleting = ref(false);

// Sync state
const isSyncing = ref(false);

// Selection state
const selectedPids = ref([]);

const isAllSelected = computed(() => {
    return inventoryItems.value.length > 0 &&
        inventoryItems.value.every(item => selectedPids.value.includes(item.pid));
});

const isIndeterminate = computed(() => {
    return selectedPids.value.length > 0 &&
        !isAllSelected.value &&
        inventoryItems.value.some(item => selectedPids.value.includes(item.pid));
});

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        // Deselect all items on current page
        const currentPagePids = inventoryItems.value.map(item => item.pid);
        selectedPids.value = selectedPids.value.filter(pid => !currentPagePids.includes(pid));
    } else {
        // Select all items on current page
        const currentPagePids = inventoryItems.value.map(item => item.pid);
        const newSelection = [...selectedPids.value];
        currentPagePids.forEach(pid => {
            if (!newSelection.includes(pid)) {
                newSelection.push(pid);
            }
        });
        selectedPids.value = newSelection;
    }
};

// --- FETCH DATA ---
const fetchPIDs = async (page = 1) => {
    isLoading.value = true;
    try {
        const params = new URLSearchParams();
        params.append('page', page);
        params.append('per_page', pagination.value.per_page);

        if (searchQuery.value) params.append('search', searchQuery.value);
        if (locationFilter.value) params.append('location', locationFilter.value);
        if (statusFilter.value) params.append('status', statusFilter.value);

        const response = await axios.get(`/api/annual-inventory?${params.toString()}`);

        if (response.data.success) {
            inventoryItems.value = response.data.data;
            pagination.value = response.data.pagination;
        }
    } catch (error) {
        console.error('Failed to fetch PIDs:', error);
    } finally {
        isLoading.value = false;
    }
};


const fetchStatistics = async () => {
    try {
        const params = new URLSearchParams();
        if (searchQuery.value) params.append('search', searchQuery.value);
        if (locationFilter.value) params.append('location', locationFilter.value);
        if (statusFilter.value) params.append('status', statusFilter.value);

        const response = await axios.get(`/api/annual-inventory/statistics?${params.toString()}`);
        if (response.data.success) {
            statistics.value = response.data.data;
        }
    } catch (error) {
        console.error('Failed to fetch statistics:', error);
    }
};

const fetchLocations = async () => {
    try {
        const response = await axios.get('/api/annual-inventory/locations');
        if (response.data.success) {
            locations.value = response.data.data;
        }
    } catch (error) {
        console.error('Failed to fetch locations:', error);
    }
};

// --- SEARCH & FILTER ---
let searchTimeout = null;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchPIDs(1);
        fetchStatistics();
    }, 300);
});

watch([locationFilter, statusFilter], () => {
    fetchPIDs(1);
    fetchStatistics();
});

// --- PAGINATION ---
const goToPage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        fetchPIDs(page);
    }
};

// --- NAVIGATION ---
const navigateToDetail = (pid) => {
    router.visit(`/annual-inventory/${encodeURIComponent(pid)}`);
};

// --- DOWNLOAD EXCEL ---
const isDownloading = ref(false);

const downloadPIDExcel = async (mode = 'all', format = 'auto') => {
    // Validate selected mode
    if (mode === 'selected' && selectedPids.value.length === 0) {
        alert('Please select at least one PID to download.');
        return;
    }

    const params = new URLSearchParams();

    if (mode === 'selected') {
        // Download only selected PIDs
        selectedPids.value.forEach(pid => params.append('pids[]', pid));
    } else {
        // Download all with current filters
        if (searchQuery.value) params.append('search', searchQuery.value);
        if (locationFilter.value) params.append('location', locationFilter.value);
        if (statusFilter.value) params.append('status', statusFilter.value);
    }

    // Add format parameter (auto, csv, single, zip)
    if (format !== 'auto') {
        params.append('mode', format);
    }

    isDownloading.value = true;
    try {
        const response = await axios.get(`/api/annual-inventory/export?${params.toString()}`, {
            responseType: 'blob',
            timeout: 300000, // 5 minutes timeout
        });

        // Check if response is JSON error (blob containing JSON)
        if (response.data.type === 'application/json') {
            const text = await response.data.text();
            const json = JSON.parse(text);
            throw new Error(json.message || 'Export failed');
        }

        // Extract filename from Content-Disposition header
        const contentDisposition = response.headers['content-disposition'];
        let filename = 'annual_inventory_export.xlsx';
        if (contentDisposition) {
            const match = contentDisposition.match(/filename="?(.+)"?/);
            if (match) filename = match[1].replace(/"/g, '');
        }

        // Create download link
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        // Clear selection after successful download
        if (mode === 'selected') {
            selectedPids.value = [];
        }
    } catch (error) {
        if (error.response?.status === 404) {
            alert('No data found for export.');
        } else if (error.code === 'ECONNABORTED' || error.message?.includes('timeout')) {
            alert('Export timed out. Please try again or contact support.');
        } else {
            console.error('Failed to download:', error);
            // Try to get error message from blob
            let errorMessage = error.message;
            if (error.response?.data instanceof Blob) {
                try {
                    const text = await error.response.data.text();
                    const json = JSON.parse(text);
                    errorMessage = json.message || errorMessage;
                } catch (e) {
                    // ignore parse error
                }
            }
            alert('Failed to download: ' + errorMessage);
        }
    } finally {
        isDownloading.value = false;
    }
};

// --- DOWNLOAD TEMPLATE ---
const downloadPIDTemplate = () => {
    window.location.href = '/api/annual-inventory/template';
};

// --- EDIT MODAL ---
const openEditModal = (item) => {
    editItem.value = item;
    editForm.value = {
        pid: item.pid,
        location: item.location,
        pic_name: item.pic_name || ''
    };
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editItem.value = null;
    editForm.value = { pid: '', location: '', pic_name: '' };
};

const saveEdit = async () => {
    if (!editItem.value) return;

    isSaving.value = true;
    try {
        const response = await axios.put(`/api/annual-inventory/${editItem.value.id}`, editForm.value);
        if (response.data.success) {
            await fetchPIDs(pagination.value.current_page);
            closeEditModal();
        }
    } catch (error) {
        console.error('Failed to update PID:', error);
        alert('Failed to update PID: ' + (error.response?.data?.message || error.message));
    } finally {
        isSaving.value = false;
    }
};

// --- DELETE MODAL ---
const confirmDelete = (item) => {
    deleteItem.value = item;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    deleteItem.value = null;
};

const deletePID = async () => {
    if (!deleteItem.value) return;

    isDeleting.value = true;
    try {
        const response = await axios.delete(`/api/annual-inventory/${deleteItem.value.id}`);
        if (response.data.success) {
            await fetchPIDs(1);
            await fetchStatistics();
            closeDeleteModal();
        }
    } catch (error) {
        console.error('Failed to delete PID:', error);
        alert('Failed to delete PID: ' + (error.response?.data?.message || error.message));
    } finally {
        isDeleting.value = false;
    }
};

// --- UPLOAD MODAL ---
const openUploadModal = () => {
    showUploadModal.value = true;
    uploadFiles.value = [];
    uploadResults.value = [];
    uploadSummary.value = null;
    uploadError.value = null;
};

const closeUploadModal = () => {
    showUploadModal.value = false;
};


const handleFileSelect = (event) => {
    const files = Array.from(event.target.files);

    if (!files.length) return;

    // Optional: prevent duplicates
    const existingNames = uploadFiles.value.map(f => f.name);
    const newFiles = files.filter(f => !existingNames.includes(f.name));

    uploadFiles.value.push(...newFiles);
    uploadError.value = null;

    // reset input so same file can be re-selected
    event.target.value = '';
};
const removeFile = (index) => {
    uploadFiles.value.splice(index, 1);
};

const clearFiles = () => {
    uploadFiles.value = [];
};

const formatFileSize = (bytes) => {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
};



const uploadAllFiles = async () => {
    if (uploadFiles.value.length === 0) {
        uploadError.value = 'Please select at least one file';
        return;
    }

    isUploading.value = true;
    uploadResults.value = [];
    uploadSummary.value = null;
    uploadProgress.value = {
        current: 0,
        total: uploadFiles.value.length
    };

    let summary = {
        filesProcessed: 0,
        totalPidsCreated: 0,
        totalPidsUpdated: 0,
        totalItemsCreated: 0,
        failedFiles: 0
    };

    for (const file of uploadFiles.value) {
        try {
            const formData = new FormData();
            formData.append('file', file);

            const response = await axios.post(
                '/api/annual-inventory/importpid',
                formData,
                { headers: { 'Content-Type': 'multipart/form-data' } }
            );

            if (response.data.success) {
                uploadResults.value.push({
                    success: true,
                    filename: file.name,
                    pids_created: response.data.pids_created || 0,
                    pids_updated: response.data.pids_updated || 0,
                    items_created: response.data.items_created || 0
                });

                summary.totalPidsCreated += response.data.pids_created || 0;
                summary.totalPidsUpdated += response.data.pids_updated || 0;
                summary.totalItemsCreated += response.data.items_created || 0;
            } else {
                throw new Error(response.data.message || 'Import failed');
            }
        } catch (error) {
            uploadResults.value.push({
                success: false,
                filename: file.name,
                error: error.response?.data?.message || error.message || 'Upload failed'
            });
            summary.failedFiles++;
        } finally {
            uploadProgress.value.current++;
            summary.filesProcessed++;
        }
    }

    uploadSummary.value = summary;

    await fetchPIDs(1);
    await fetchStatistics();
    await fetchLocations();

    isUploading.value = false;
};


// --- SYNC PIC & GROUP LEADER ---
const syncPicAndGroupLeader = async () => {
    isSyncing.value = true;
    try {
        const response = await axios.post('/api/annual-inventory/sync-pic-gl');
        if (response.data.success) {
            alert(`Synced ${response.data.updated} PIDs with PIC & Group Leader`);
            await fetchPIDs(pagination.value.current_page);
        }
    } catch (error) {
        console.error('Failed to sync:', error);
        alert('Failed to sync: ' + (error.response?.data?.message || error.message));
    } finally {
        isSyncing.value = false;
    }
};

// --- LIFECYCLE ---
onMounted(() => {
    fetchPIDs();
    fetchStatistics();
    fetchLocations();
});
</script>
