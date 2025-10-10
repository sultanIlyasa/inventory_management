<template>
    <div>
        <!-- Mobile Menu Button -->
        <button @click="toggleSidebar"
            class="lg:hidden fixed top-4 left-4 z-50 p-2 bg-gray-800 text-white rounded-lg shadow-lg">
            <svg v-if="!isSidebarOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <!-- Backdrop for mobile -->
        <div v-if="isSidebarOpen" @click="closeSidebar"
            class="lg:hidden fixed inset-0 bg-black/50 z-40 transition-opacity">
        </div>

        <!-- Sidebar -->
        <div :class="[
            'bg-gray-800 text-white flex flex-col p-4 transition-transform duration-300 ease-in-out overflow-y-auto',
            'fixed lg:sticky inset-y-0 left-0 z-40 lg:top-0',
            'w-64 sm:w-72 lg:w-56',
            'min-h-screen lg:h-screen',
            isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
        ]">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold mt-10">DIMS Menu</h2>

            </div>

            <!-- Menu Items -->
            <ul class="space-y-3 w-full flex-1">
                <li>
                    <a href="/" @click="closeSidebar"
                        class="block hover:bg-gray-700 p-3 rounded cursor-pointer transition-colors">
                        Daily Input
                    </a>
                </li>
                <!-- <li>
                    <a href="/daily-input" @click="closeSidebar"
                        class="block hover:bg-gray-700 p-3 rounded cursor-pointer transition-colors">
                        Daily Input
                    </a>
                </li> -->
                <li>
                    <a href="/reports" @click="closeSidebar"
                        class="block hover:bg-gray-700 p-3 rounded cursor-pointer transition-colors">
                        Reports
                    </a>
                </li>
            </ul>

            <!-- Auth Section -->
            <div class="w-full pt-4 border-t border-gray-700">
                <template v-if="!user">
                    <a v-if="canLogin" href="/login" @click="closeSidebar"
                        class="block w-full text-center px-3 py-2 bg-blue-600 hover:bg-blue-700 rounded transition-colors">
                        Login
                    </a>
                </template>
                <template v-else>
                    <div class="mb-3 p-2 bg-gray-700 rounded text-sm">
                        <div class="font-medium truncate">{{ user.name }}</div>
                        <div class="text-xs text-gray-400 truncate">{{ user.email }}</div>
                    </div>
                    <a href="/admin" @click="closeSidebar"
                        class="block w-full text-center px-3 py-2 bg-gray-700 hover:bg-gray-600 rounded mb-2 transition-colors">
                        Admin
                    </a>
                    <a href="/logout" method="post" @click="closeSidebar"
                        class="block w-full text-center px-3 py-2 bg-red-600 hover:bg-red-700 rounded transition-colors">
                        Logout
                    </a>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const page = usePage()
const user = computed(() => (page.props && page.props.auth ? page.props.auth.user : null))
const canLogin = computed(() => Boolean(page.props && page.props.canLogin))

const isSidebarOpen = ref(false)

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value
}

const closeSidebar = () => {
    isSidebarOpen.value = false
}
</script>
