    <template>
        <MainAppLayout title="Annual Inventory" subtitle="Manage annual inventory PIDs">
            <div class="min-h-screen w-full overflow-hidden bg-gray-50">
                <div class="flex">
                    <div class="flex-1">
                        <div class="mx-auto w-full">
                            <h1 class="flex flex-col mx-auto text-2xl font-bold text-center my-10">
                                Annual Inventory Taking Input
                            </h1>

                            <!-- Statistics Cards -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
                                    <p class="text-xs text-gray-500 uppercase tracking-wide">Total PIDs</p>
                                    <p class="text-2xl font-bold text-gray-800">{{ statistics.pids.total }}</p>
                                </div>
                                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
                                    <p class="text-xs text-gray-500 uppercase tracking-wide">Completed</p>
                                    <p class="text-2xl font-bold text-green-600">{{ statistics.pids.completed }}</p>
                                </div>
                                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
                                    <p class="text-xs text-gray-500 uppercase tracking-wide">In Progress</p>
                                    <p class="text-2xl font-bold text-blue-600">{{ statistics.pids.in_progress }}</p>
                                </div>
                                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
                                    <p class="text-xs text-gray-500 uppercase tracking-wide">Completion Rate</p>
                                    <p class="text-2xl font-bold text-gray-800">{{ statistics.pids.completion_rate }}%
                                    </p>
                                </div>
                            </div>

                            <!-- Main Content Card -->
                            <div class="bg-white rounded-2xl shadow-lg p-4">
                                <!-- Loading State -->
                                <div v-if="isLoading"
                                    class="absolute inset-0 bg-white/70 flex items-center justify-center z-10 h-full">
                                    <div class="text-center">
                                        <div
                                            class="inline-block animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mb-3">
                                        </div>
                                        <p class="text-sm text-gray-600 font-medium">Loading data...</p>
                                    </div>
                                </div>

                                <div class="space-y-3 sm:space-y-4">
                                    <!-- Search Bar -->
                                    <div class="relative">
                                        <Search
                                            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                                        <input v-model="searchQuery" type="text" placeholder="Search PID or PIC..."
                                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm" />
                                        <button v-if="searchQuery" @click="searchQuery = ''"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                            <X class="w-4 h-4" />
                                        </button>
                                    </div>

                                    <!-- Filters & Actions -->
                                    <div class="flex flex-col lg:flex-row gap-3 lg:gap-4">
                                        <!-- Filters -->
                                        <div class="flex-1 flex flex-col sm:flex-row items-start sm:items-center gap-3">
                                            <div class="flex items-center gap-2 text-sm text-gray-500 font-medium">
                                                <Filter class="w-4 h-4" />
                                                <span>Filter:</span>
                                            </div>
                                            <div class="flex w-full sm:w-auto gap-2">
                                                <select v-model="locationFilter"
                                                    class="flex-1 sm:w-36 px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                                    <option value="">All Locations</option>
                                                    <option v-for="loc in locations" :key="loc" :value="loc">{{ loc }}
                                                    </option>
                                                </select>
                                                <select v-model="statusFilter"
                                                    class="flex-1 sm:w-36 px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                                    <option value="">All Statuses</option>
                                                    <option value="Not Checked">Not Checked</option>
                                                    <option value="In Progress">In Progress</option>
                                                    <option value="Completed">Completed</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div
                                            class="flex flex-col sm:flex-row gap-2 lg:gap-3 w-full lg:w-auto lg:min-w-fit">
                                            <button @click="fetchPIDs"
                                                class="w-full sm:w-auto px-4 py-2.5 sm:py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors font-medium text-sm shadow-sm">
                                                <span class="flex items-center justify-center gap-2">
                                                    <RefreshCw class="w-4 h-4" />
                                                    Refresh
                                                </span>
                                            </button>
                                            <button @click="downloadPIDExcel"
                                                class="w-full sm:w-auto px-4 py-2.5 sm:py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium text-sm shadow-sm">
                                                <span class="flex items-center justify-center gap-2">
                                                    <Download class="w-4 h-4" />
                                                    Download Excel
                                                </span>
                                            </button>
                                            <button @click="openUploadModal"
                                                class="w-full sm:w-auto px-4 py-2.5 sm:py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium text-sm shadow-sm">
                                                <span class="flex items-center justify-center gap-2">
                                                    <Upload class="w-4 h-4" />
                                                    Upload PID
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
                                                    <th class="p-2 border text-xs lg:text-sm">PID Number</th>
                                                    <th class="p-2 border text-xs lg:text-sm">Location</th>
                                                    <th class="p-2 border text-xs lg:text-sm">PIC</th>
                                                    <th class="p-2 border text-xs lg:text-sm">Progress</th>
                                                    <th class="p-2 border text-xs lg:text-sm">Status</th>
                                                    <th class="p-2 border text-xs lg:text-sm">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(item, index) in inventoryItems" :key="item.id"
                                                    class="text-center hover:bg-gray-50">
                                                    <td class="border p-2 font-semibold text-xs lg:text-sm">
                                                        {{ (pagination.current_page - 1) * pagination.per_page + index +
                                                            1 }}
                                                    </td>
                                                    <td class="border p-2 text-xs lg:text-sm font-medium">
                                                        <div class="flex items-center justify-center gap-2">
                                                            <FileText class="w-4 h-4 text-gray-400" />
                                                            {{ item.pid }}
                                                        </div>
                                                    </td>
                                                    <td class="border p-2 text-xs lg:text-sm">{{ item.location }}</td>
                                                    <td class="border p-2 text-xs lg:text-sm">
                                                        <span
                                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                                            {{ item.pic_name || '-' }}
                                                        </span>
                                                    </td>
                                                    <td class="border p-2 text-xs lg:text-sm">
                                                        <div class="flex items-center justify-center gap-2">
                                                            <div
                                                                class="w-20 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                                <div class="h-full bg-blue-500 rounded-full transition-all"
                                                                    :style="{ width: `${item.progress}%` }"></div>
                                                            </div>
                                                            <span class="text-xs text-gray-500">{{ item.counted_count
                                                            }}/{{
                                                                    item.items_count }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="border p-2 text-xs lg:text-sm">
                                                        <span v-if="item.status === 'Completed'"
                                                            class="bg-green-200 font-semibold px-2 py-1 rounded-xl text-xs">
                                                            Completed
                                                        </span>
                                                        <span v-else-if="item.status === 'In Progress'"
                                                            class="bg-blue-200 font-semibold px-2 py-1 rounded-xl text-xs">
                                                            In Progress
                                                        </span>
                                                        <span v-else
                                                            class="bg-gray-200 px-2 py-1 rounded-xl text-xs">Not
                                                            Checked</span>
                                                    </td>
                                                    <td class="border p-2 text-xs lg:text-sm">
                                                        <div class="flex items-center justify-center gap-1">
                                                            <button @click="navigateToDetail(item.pid)"
                                                                class="px-3 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700 transition">
                                                                Submit
                                                            </button>
                                                            <button @click="openEditModal(item)"
                                                                class="p-1 text-yellow-600 hover:bg-yellow-50 rounded transition">
                                                                <Pencil class="w-4 h-4" />
                                                            </button>
                                                            <button @click="confirmDelete(item)"
                                                                class="p-1 text-red-600 hover:bg-red-50 rounded transition">
                                                                <Trash2 class="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr v-if="!inventoryItems.length">
                                                    <td colspan="7"
                                                        class="border p-4 text-center text-gray-500 text-sm">
                                                        No inventory records found
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Mobile Card View -->
                                    <div class="md:hidden space-y-3">
                                        <div v-for="(item, index) in inventoryItems" :key="item.id"
                                            class="bg-white border rounded-lg shadow-sm overflow-hidden">
                                            <!-- Card Header -->
                                            <div
                                                class="bg-gray-100 px-3 py-2 flex items-center justify-between border-b">
                                                <div class="flex items-center gap-2">
                                                    <FileText class="w-4 h-4 text-gray-500" />
                                                    <span class="text-sm font-semibold text-gray-900">{{ item.pid
                                                    }}</span>
                                                </div>
                                                <div>
                                                    <span v-if="item.status === 'Completed'"
                                                        class="bg-green-200 font-semibold px-2 py-1 rounded-lg text-xs">
                                                        Completed
                                                    </span>
                                                    <span v-else-if="item.status === 'In Progress'"
                                                        class="bg-blue-200 font-semibold px-2 py-1 rounded-lg text-xs">
                                                        In Progress
                                                    </span>
                                                    <span v-else class="bg-gray-200 px-2 py-1 rounded-lg text-xs">Not
                                                        Checked</span>
                                                </div>
                                            </div>

                                            <!-- Card Body -->
                                            <div class="p-3 space-y-2">
                                                <div class="grid grid-cols-2 gap-2 text-xs">
                                                    <div class="bg-gray-50 px-2 py-1 rounded">
                                                        <p class="text-gray-600">Location</p>
                                                        <p class="font-semibold text-gray-900">{{ item.location }}</p>
                                                    </div>
                                                    <div class="bg-gray-50 px-2 py-1 rounded">
                                                        <p class="text-gray-600">PIC</p>
                                                        <p class="font-semibold text-gray-900">{{ item.pic_name || '-'
                                                        }}</p>
                                                    </div>
                                                </div>

                                                <!-- Progress Bar -->
                                                <div class="bg-blue-50 px-2 py-2 rounded">
                                                    <div class="flex justify-between items-center mb-1">
                                                        <span class="text-xs text-gray-600">Progress</span>
                                                        <span class="text-xs font-semibold text-blue-600">{{
                                                            item.counted_count }}/{{ item.items_count }}</span>
                                                    </div>
                                                    <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                                                        <div class="h-full bg-blue-500 rounded-full"
                                                            :style="{ width: `${item.progress}%` }"></div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Card Footer -->
                                            <div class="bg-gray-50 px-3 py-2 border-t flex gap-2">
                                                <button @click="navigateToDetail(item.pid)"
                                                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded text-sm font-medium hover:bg-blue-700 transition">
                                                    Submit Inventory
                                                </button>
                                                <button @click="openEditModal(item)"
                                                    class="px-3 py-2 bg-yellow-500 text-white rounded text-sm font-medium hover:bg-yellow-600 transition">
                                                    <Pencil class="w-4 h-4" />
                                                </button>
                                                <button @click="confirmDelete(item)"
                                                    class="px-3 py-2 bg-red-500 text-white rounded text-sm font-medium hover:bg-red-600 transition">
                                                    <Trash2 class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>

                                        <div v-if="!inventoryItems.length"
                                            class="text-center py-8 text-gray-500 text-sm">
                                            No records found matching your filters.
                                        </div>
                                    </div>

                                    <!-- Pagination -->
                                    <div v-if="pagination.total > pagination.per_page"
                                        class="flex justify-between items-center pt-4 border-t">
                                        <span class="text-sm text-gray-500">
                                            Showing {{ inventoryItems.length }} of {{ pagination.total }} entries
                                        </span>
                                        <div class="flex gap-1">
                                            <button @click="goToPage(pagination.current_page - 1)"
                                                :disabled="pagination.current_page === 1"
                                                class="px-3 py-1 border rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                                <ChevronLeft class="w-4 h-4" />
                                            </button>

                                            <template v-for="page in pagination.last_page" :key="page">
                                                <button
                                                    v-if="page === 1 || page === pagination.last_page || (page >= pagination.current_page - 1 && page <= pagination.current_page + 1)"
                                                    @click="goToPage(page)" class="px-3 py-1 border rounded" :class="page === pagination.current_page
                                                        ? 'bg-blue-50 text-blue-600 border-blue-200'
                                                        : 'hover:bg-gray-50'">
                                                    {{ page }}
                                                </button>
                                                <span
                                                    v-else-if="page === pagination.current_page - 2 || page === pagination.current_page + 2"
                                                    class="px-2">...</span>
                                            </template>

                                            <button @click="goToPage(pagination.current_page + 1)"
                                                :disabled="pagination.current_page === pagination.last_page"
                                                class="px-3 py-1 border rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
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

            <!-- Edit PID Modal -->
            <Modal :show="showEditModal" @close="closeEditModal" max-width="md">
                <div class="p-4 sm:p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                            <Pencil class="w-5 h-5 text-yellow-600" />
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900">Edit PID</h2>
                    </div>

                    <div class="space-y-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">PID Number</label>
                            <input v-model="editForm.pid" type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                placeholder="Enter PID number" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                            <input v-model="editForm.location" type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                placeholder="Enter location" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">PIC Name</label>
                            <input v-model="editForm.pic_name" type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                placeholder="Enter PIC name" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button @click="closeEditModal"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300">
                            Cancel
                        </button>
                        <button @click="saveEdit" :disabled="isSaving"
                            class="px-4 py-2 bg-yellow-600 text-white rounded text-sm hover:bg-yellow-700 disabled:opacity-50 flex items-center gap-2">
                            <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
                            {{ isSaving ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </div>
            </Modal>

            <!-- Delete Confirmation Modal -->
            <Modal :show="showDeleteModal" @close="closeDeleteModal" max-width="sm">
                <div class="p-4 sm:p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                            <Trash2 class="w-5 h-5 text-red-600" />
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900">Delete PID</h2>
                    </div>

                    <p class="text-sm text-gray-600 mb-6">
                        Are you sure you want to delete PID <strong>{{ deleteItem?.pid }}</strong>?
                        This will also delete all {{ deleteItem?.items_count || 0 }} items associated with it.
                        This action cannot be undone.
                    </p>

                    <div class="flex justify-end gap-3">
                        <button @click="closeDeleteModal"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300">
                            Cancel
                        </button>
                        <button @click="deletePID" :disabled="isDeleting"
                            class="px-4 py-2 bg-red-600 text-white rounded text-sm hover:bg-red-700 disabled:opacity-50 flex items-center gap-2">
                            <Loader2 v-if="isDeleting" class="w-4 h-4 animate-spin" />
                            {{ isDeleting ? 'Deleting...' : 'Delete' }}
                        </button>
                    </div>
                </div>
            </Modal>

            <!-- Upload Modal -->
            <Modal :show="showUploadModal" @close="closeUploadModal" max-width="lg">
                <div class="p-4 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                                <Upload class="w-5 h-5 text-green-600" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">Bulk Upload PIDs</h2>
                                <p class="text-xs text-gray-500">Upload multiple PIDs and materials at once</p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center mb-4 hover:border-blue-400 hover:bg-blue-50/30 transition-colors">
                        <input type="file" accept=".xlsx,.xls,.csv" @change="handleFileSelect" class="hidden"
                            id="file-upload" multiple />
                        <label for="file-upload" class="cursor-pointer">
                            <Upload class="w-10 h-10 text-gray-400 mx-auto mb-2" />
                            <p class="text-sm text-gray-600">
                                <span class="text-blue-600 font-medium">Click to upload</span> or drag and drop
                            </p>
                            <p class="text-xs text-gray-400 mt-1">XLSX, XLS, CSV (max 10MB each) - <strong>Multiple
                                    files
                                    supported</strong></p>
                        </label>
                    </div>

                    <!-- Selected Files List -->
                    <div v-if="uploadFiles.length > 0" class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium text-gray-700">Selected Files ({{ uploadFiles.length }}):</p>
                            <button @click="clearFiles" class="text-xs text-red-600 hover:text-red-800">Clear
                                All</button>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 max-h-32 overflow-y-auto space-y-2">
                            <div v-for="(file, index) in uploadFiles" :key="index"
                                class="flex items-center justify-between bg-white px-3 py-2 rounded border text-sm">
                                <div class="flex items-center gap-2">
                                    <FileText class="w-4 h-4 text-green-600" />
                                    <span class="text-gray-700">{{ file.name }}</span>
                                    <span class="text-xs text-gray-400">({{ formatFileSize(file.size) }})</span>
                                </div>
                                <button @click="removeFile(index)" class="text-red-500 hover:text-red-700">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>


                    <!-- Upload Progress -->
                    <div v-if="isUploading && uploadProgress.total > 0" class="mb-4">
                        <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                            <span>Uploading {{ uploadProgress.current }} of {{ uploadProgress.total }} files...</span>
                            <span>{{ Math.round((uploadProgress.current / uploadProgress.total) * 100) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                :style="{ width: `${(uploadProgress.current / uploadProgress.total) * 100}%` }"></div>
                        </div>
                    </div>

                    <!-- Upload Results -->
                    <div v-if="uploadResults.length > 0" class="mb-4 space-y-2 max-h-48 overflow-y-auto">
                        <div v-for="(result, index) in uploadResults" :key="index"
                            :class="result.success ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'"
                            class="border rounded-lg p-3">
                            <div class="flex items-center gap-2 mb-1">
                                <CheckCircle v-if="result.success" class="w-4 h-4 text-green-600" />
                                <AlertCircle v-else class="w-4 h-4 text-red-600" />
                                <span class="text-sm font-medium"
                                    :class="result.success ? 'text-green-700' : 'text-red-700'">
                                    {{ result.filename }}
                                </span>
                            </div>
                            <div v-if="result.success" class="text-xs text-green-600 ml-6">
                                PIDs: {{ result.pids_created }} created, {{ result.pids_updated }} updated |
                                Items: {{ result.items_created }} created
                            </div>
                            <div v-else class="text-xs text-red-600 ml-6">
                                {{ result.error }}
                            </div>
                        </div>
                    </div>

                    <!-- Upload Summary -->
                    <div v-if="uploadSummary && !isUploading"
                        class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center gap-2 text-blue-700 font-medium mb-2">
                            <CheckCircle class="w-5 h-5" />
                            <span>Upload Complete - {{ uploadSummary.filesProcessed }} files processed</span>
                        </div>
                        <ul class="text-sm text-blue-600 space-y-1">
                            <li>Total PIDs Created: {{ uploadSummary.totalPidsCreated }}</li>
                            <li>Total PIDs Updated: {{ uploadSummary.totalPidsUpdated }}</li>
                            <li>Total Items Created: {{ uploadSummary.totalItemsCreated }}</li>
                            <li v-if="uploadSummary.failedFiles > 0" class="text-red-600">
                                Failed Files: {{ uploadSummary.failedFiles }}
                            </li>
                        </ul>
                    </div>

                    <!-- Upload Error -->
                    <div v-if="uploadError" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center gap-2 text-red-700">
                            <AlertCircle class="w-5 h-5 flex-shrink-0" />
                            <span class="text-sm">{{ uploadError }}</span>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button @click="closeUploadModal"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300">
                            Close
                        </button>
                        <button @click="uploadAllFiles" :disabled="uploadFiles.length === 0 || isUploading"
                            class="px-4 py-2 bg-green-600 text-white rounded text-sm hover:bg-green-700 disabled:opacity-50 flex items-center gap-2">
                            <Loader2 v-if="isUploading" class="w-4 h-4 animate-spin" />
                            {{ isUploading ? 'Uploading...' : `Upload ${uploadFiles.length} File${uploadFiles.length !==
                                1 ? 's' :
                                ''}` }}
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
        const response = await axios.get('/api/annual-inventory/statistics');
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
    }, 300);
});

watch([locationFilter, statusFilter], () => {
    fetchPIDs(1);
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
const downloadPIDExcel = () => {
    const params = new URLSearchParams();
    if (searchQuery.value) params.append('search', searchQuery.value);
    if (locationFilter.value) params.append('location', locationFilter.value);
    if (statusFilter.value) params.append('status', statusFilter.value);

    window.location.href = `/api/annual-inventory/export?${params.toString()}`;
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
            const resultData = response.data.data || {};

            if (resultData) {
                uploadResults.value.push({
                    success: true,
                    filename: file.name,
                    ...response.data.data
                });

                summary.totalPidsCreated += resultData.pids_created ?? 0;
                summary.totalPidsUpdated += resultData.pids_updated ?? 0;
                summary.totalItemsCreated += resultData.items_created ?? 0;
            } else {
                throw new Error(response.data.message);
            }
        } catch (error) {
            uploadResults.value.push({
                success: true,
                filename: file.name,
                pids_created: resultData.pids_created ?? 0,
                pids_updated: resultData.pids_updated ?? 0,
                items_created: resultData.items_created ?? 0
            });
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


// --- LIFECYCLE ---
onMounted(() => {
    fetchPIDs();
    fetchStatistics();
    fetchLocations();
});
</script>
