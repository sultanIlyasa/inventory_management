<script setup>
import { ref, computed } from 'vue';
import {
  Search,
  Upload,
  Download,
  Filter,
  ChevronRight,
  FileText
} from 'lucide-vue-next';

// --- 1. DUMMY DATA GENERATOR (200 Items) ---
const generateDummyData = () => {
  const data = [];
  const locations = ['Sunter_1', 'Sunter_2', 'Cikarang_A', 'Karawang_West'];
  const pics = ['ANWAR', 'BUDI', 'SITI', 'DAVID', 'EKO'];
  const statuses = ['Not Checked', 'In Progress', 'Completed'];

  for (let i = 1; i <= 200; i++) {
    data.push({
      pid: `PID-${123213 + i}`,
      location: locations[Math.floor(Math.random() * locations.length)],
      pic: pics[Math.floor(Math.random() * pics.length)],
      status: statuses[Math.floor(Math.random() * statuses.length)],
      // Simulate some dates or extra info if needed
      lastUpdated: '2025-01-24'
    });
  }
  return data;
};

const inventoryItems = ref(generateDummyData());

// --- 2. FILTERS & SEARCH STATE ---
const searchQuery = ref('');
const locationFilter = ref('');
const statusFilter = ref('');

// --- 3. FILTER LOGIC ---
const filteredItems = computed(() => {
  return inventoryItems.value.filter(item => {
    const matchesSearch = item.pid.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                          item.pic.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesLocation = locationFilter.value ? item.location === locationFilter.value : true;
    const matchesStatus = statusFilter.value ? item.status === statusFilter.value : true;

    return matchesSearch && matchesLocation && matchesStatus;
  });
});

// --- 4. DYNAMIC ROUTING HANDLER ---
const navigateToDetail = (pid) => {
  // In a real Laravel/Vue router app, you would use:
  // router.push({ name: 'inventory.show', params: { id: pid } });

  console.log(`Navigating to dynamic route: /inventory/${pid}`);
  window.location.href = `/inventory/${pid}`; // Simulating the redirect
};

// --- HELPER: Status Badge Color ---
const getStatusColor = (status) => {
  switch(status) {
    case 'Completed': return 'bg-green-100 text-green-700 border-green-200';
    case 'In Progress': return 'bg-blue-100 text-blue-700 border-blue-200';
    default: return 'bg-gray-100 text-gray-600 border-gray-200';
  }
};
</script>

<template>
  <div class="min-h-screen bg-gray-50 p-4 md:p-8 font-sans text-slate-800">

    <div class="max-w-6xl mx-auto bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">

      <div class="p-6 border-b border-slate-100 space-y-6">
        <h1 class="text-2xl font-bold text-center md:text-left text-slate-800">
          Annual Inventory Taking Input
        </h1>

        <div class="flex flex-col md:flex-row gap-4 justify-between items-start md:items-center">

          <div class="w-full md:w-auto flex flex-col md:flex-row gap-3">
            <div class="relative w-full md:w-64">
              <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search PID or PIC..."
                class="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm"
              />
            </div>

            <div class="flex gap-2">
              <button class="flex-1 md:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 text-sm font-medium text-slate-700 transition-colors">
                <Upload class="w-4 h-4" />
                <span>Upload PID</span>
              </button>
              <button class="flex-1 md:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 text-sm font-medium transition-colors shadow-sm">
                <Download class="w-4 h-4" />
                <span>Download All</span>
              </button>
            </div>
          </div>

          <div class="w-full md:w-auto flex flex-col md:flex-row items-start md:items-center gap-3">
            <div class="flex items-center gap-2 text-sm text-slate-500 font-medium">
              <Filter class="w-4 h-4" />
              <span>Filter by:</span>
            </div>

            <div class="flex w-full md:w-auto gap-2">
              <select v-model="locationFilter" class="flex-1 md:w-36 px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">All Locations</option>
                <option value="Sunter_1">Sunter_1</option>
                <option value="Sunter_2">Sunter_2</option>
                <option value="Karawang_West">Karawang_West</option>
              </select>

              <select v-model="statusFilter" class="flex-1 md:w-36 px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">All Statuses</option>
                <option value="Not Checked">Not Checked</option>
                <option value="In Progress">In Progress</option>
                <option value="Completed">Completed</option>
              </select>
            </div>
          </div>

        </div>
      </div>

      <div class="bg-slate-50 min-h-[500px]">

        <div class="hidden md:block overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead class="bg-slate-100 text-slate-600 font-semibold text-sm border-b border-slate-200">
              <tr>
                <th class="px-6 py-4 w-16">#</th>
                <th class="px-6 py-4">PID Number</th>
                <th class="px-6 py-4">Location</th>
                <th class="px-6 py-4">PIC</th>
                <th class="px-6 py-4">Status</th>
                <th class="px-6 py-4 text-center">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
              <tr v-for="(item, index) in filteredItems" :key="item.pid" class="hover:bg-blue-50/50 transition-colors group">
                <td class="px-6 py-4 text-slate-400 text-sm">{{ index + 1 }}</td>
                <td class="px-6 py-4 font-medium text-slate-900 flex items-center gap-2">
                  <FileText class="w-4 h-4 text-slate-400" />
                  {{ item.pid }}
                </td>
                <td class="px-6 py-4 text-slate-600">{{ item.location }}</td>
                <td class="px-6 py-4">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                    {{ item.pic }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <span class="px-3 py-1 rounded-full text-xs font-semibold border" :class="getStatusColor(item.status)">
                    {{ item.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-center">
                  <button
                    @click="navigateToDetail(item.pid)"
                    class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded shadow-sm transition-all active:scale-95"
                  >
                    Submit
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="filteredItems.length === 0" class="flex flex-col items-center justify-center py-20 text-slate-400">
            <FileText class="w-12 h-12 mb-3 opacity-20" />
            <p>No inventory records found.</p>
          </div>
        </div>

        <div class="md:hidden p-4 space-y-4">
          <div
            v-for="item in filteredItems"
            :key="item.pid"
            class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex flex-col gap-3 active:border-blue-300 transition-colors"
          >
            <div class="flex justify-between items-start">
              <div class="flex items-center gap-2">
                <div class="p-2 bg-slate-100 rounded-lg">
                  <FileText class="w-5 h-5 text-slate-500" />
                </div>
                <div>
                  <h3 class="font-bold text-slate-800">{{ item.pid }}</h3>
                  <p class="text-xs text-slate-500">Loc: {{ item.location }}</p>
                </div>
              </div>
              <span class="px-2 py-1 rounded text-[10px] font-bold uppercase border" :class="getStatusColor(item.status)">
                {{ item.status }}
              </span>
            </div>

            <hr class="border-slate-100" />

            <div class="flex justify-between items-center">
              <div class="text-sm">
                <span class="text-slate-400 text-xs block mb-0.5">PIC Name</span>
                <span class="font-medium text-slate-700">{{ item.pic }}</span>
              </div>

              <button
                @click="navigateToDetail(item.pid)"
                class="flex items-center gap-1 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg active:bg-blue-700"
              >
                <span>Submit</span>
                <ChevronRight class="w-4 h-4" />
              </button>
            </div>
          </div>

          <div v-if="filteredItems.length === 0" class="text-center py-10 text-slate-500">
            No records found matching your filters.
          </div>
        </div>

      </div>

      <div class="p-4 border-t border-slate-200 bg-white flex justify-between items-center text-sm text-slate-500">
        <span>Showing {{ filteredItems.length }} of 200 entries</span>
        <div class="flex gap-1">
          <button class="px-3 py-1 border rounded hover:bg-slate-50 disabled:opacity-50" disabled>Prev</button>
          <button class="px-3 py-1 border rounded bg-blue-50 text-blue-600 border-blue-200">1</button>
          <button class="px-3 py-1 border rounded hover:bg-slate-50">2</button>
          <button class="px-3 py-1 border rounded hover:bg-slate-50">Next</button>
        </div>
      </div>

    </div>
  </div>
</template>
