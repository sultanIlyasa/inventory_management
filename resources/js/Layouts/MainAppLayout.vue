<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Mobile Menu Button -->
        <div
            class="lg:hidden fixed top-0 left-0 right-0 z-40 bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
            <h1 class="text-lg font-semibold text-gray-900">{{ title || 'Dashboard' }}</h1>
            <button @click="toggleSidebar" class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <!-- Overlay for mobile -->
        <Transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="sidebarOpen && isMobile" @click="closeSidebar"
                class="fixed inset-0 bg-gray-600 bg-opacity-75 z-40 lg:hidden">
            </div>
        </Transition>

        <!-- Sidebar -->
        <aside :class="[
            'fixed top-0 left-0 z-50 h-full bg-white border-r border-gray-200 transition-all duration-300 ease-in-out',
            sidebarCollapsed ? 'w-20' : 'w-64',
            isMobile && !sidebarOpen ? '-translate-x-full' : 'translate-x-0',
            'lg:translate-x-0'
        ]">
            <!-- Sidebar Header -->
            <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200">
                <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
                    enter-to-class="opacity-100" leave-active-class="transition-opacity duration-200"
                    leave-from-class="opacity-100" leave-to-class="opacity-0" mode="out-in">
                    <div v-if="!sidebarCollapsed" class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">WH</span>
                        </div>
                        <span class="text-lg font-semibold text-gray-900">Information System</span>
                    </div>
                    <div v-else class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center mx-auto">
                        <span class="text-white font-bold text-sm">WH</span>
                    </div>
                </Transition>
            </div>

            <!-- Sidebar Navigation -->
            <nav class="flex-1 overflow-y-auto p-4">
                <ul class="space-y-2">
                    <li v-for="item in menuItems" :key="item.name">
                        <!-- If menu has children, render as collapsible group -->
                        <div v-if="item.children" class="flex flex-col">
                            <button @click="toggleGroup(item.name)"
                                class="flex items-center justify-between w-full px-3 py-2.5 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                <span class="flex items-center gap-3">
                                    <span v-if="!sidebarCollapsed" class="text-sm font-medium">{{ item.name }}</span>
                                </span>
                                <svg v-if="!sidebarCollapsed"
                                    :class="['w-4 h-4 transition-transform', isGroupOpen(item.name) ? 'rotate-90' : '']"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </button>

                            <Transition name="slide-fade">
                                <ul v-if="isGroupOpen(item.name)" class="ml-5 mt-1 space-y-1">
                                    <li v-for="child in item.children" :key="child.name">
                                        <Link :href="route(child.route)" :class="[
                                            'block px-3 py-2 rounded-md text-sm transition-colors',
                                            isActive(child.route)
                                                ? 'bg-blue-50 text-blue-600 font-medium'
                                                : 'text-gray-600 hover:bg-gray-100'
                                        ]">
                                        {{ child.name }}
                                        </Link>
                                    </li>
                                </ul>
                            </Transition>
                        </div>

                        <!-- Otherwise, render single link -->
                        <div v-else>
                            <Link :href="route(item.route)" :class="[
                                'flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors',
                                isActive(item.route)
                                    ? 'bg-blue-50 text-blue-600 font-medium'
                                    : 'text-gray-700 hover:bg-gray-50',
                                sidebarCollapsed ? 'justify-center' : ''
                            ]">
                            <span v-if="!sidebarCollapsed" class="text-sm">{{ item.name }}</span>
                            </Link>
                        </div>
                    </li>
                </ul>
            </nav>

            <!-- Sidebar Footer (User & Collapse Button) -->
            <div class="border-t border-gray-200 p-4">
                <!-- Collapse Toggle Button (Desktop only) -->
                <button @click="toggleCollapse"
                    class="hidden lg:flex w-full items-center justify-center gap-2 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg transition-colors">
                    <svg :class="['w-4 h-4 transition-transform', sidebarCollapsed ? 'rotate-180' : '']" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                    <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
                        enter-to-class="opacity-100" leave-active-class="transition-opacity duration-200"
                        leave-from-class="opacity-100" leave-to-class="opacity-0">
                        <span v-if="!sidebarCollapsed">Collapse</span>
                    </Transition>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <div :class="[
            'transition-all duration-300 pt-16 lg:pt-0',
            sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'
        ]">


            <!-- Page Content -->
            <main class="p-4 sm:p-6 lg:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
const openGroups = ref([])


defineProps({
    title: {
        type: String,
        default: 'Dashboard'
    },
    subtitle: {
        type: String,
        default: null
    }
})

// Get current user from shared Inertia data
const page = usePage()
const user = computed(() => page.props.auth?.user)

// Sidebar state
const sidebarOpen = ref(false)
const sidebarCollapsed = ref(false)
const isMobile = ref(false)

// Check if mobile
const checkMobile = () => {
    isMobile.value = window.innerWidth < 1024
    if (!isMobile.value) {
        sidebarOpen.value = true
    }
}

onMounted(() => {
    checkMobile()
    window.addEventListener('resize', checkMobile)

    // Load collapsed state from localStorage
    const savedState = localStorage.getItem('sidebarCollapsed')
    if (savedState !== null) {
        sidebarCollapsed.value = savedState === 'true'
    }
})

onUnmounted(() => {
    window.removeEventListener('resize', checkMobile)
})

// Toggle functions
const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value
}

const closeSidebar = () => {
    sidebarOpen.value = false
}

const toggleCollapse = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value
    localStorage.setItem('sidebarCollapsed', sidebarCollapsed.value.toString())
}

// Check if route is active
const isActive = (routeName) => {
    return route().current(routeName + '*')
}
const isGroupOpen = (groupName) => openGroups.value.includes(groupName)
const toggleGroup = (groupName) => {
    if (openGroups.value.includes(groupName)) {
        openGroups.value = openGroups.value.filter((name) => name !== groupName)
    } else {
        openGroups.value.push(groupName)
    }
}




const menuItems = [
    {
        name: 'Homepage',
        route: 'homepage.index'
    },
    {
        name: 'Daily Input',
        route: 'daily-input.index',
    },
    {
        name: 'Warehouse Monitoring',
        children: [
            {
                name: 'Dashboard Monitoring',
                route: 'warehouse-monitoring.index'
            },
            {
                name: 'Leaderboard',
                route: 'warehouse-monitoring.leaderboard'
            },
            {
                name: 'Recovery Days',
                route: 'warehouse-monitoring.recovery-days'
            },
            {
                name: 'Status Change',
                route: 'warehouse-monitoring.status-change'
            },
            {
                name: 'Overdue Days',
                route: 'warehouse-monitoring.overdue-days'
            },


        ]
    },
    {
        name: 'Login',
        route: 'login'
    }

]
</script>

<style scoped>
/* Custom scrollbar for sidebar */
aside nav::-webkit-scrollbar {
    width: 6px;
}

aside nav::-webkit-scrollbar-track {
    background: transparent;
}

aside nav::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 3px;
}

aside nav::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}

.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.25s ease;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    opacity: 0;
    max-height: 0;
    transform: translateY(-4px);
}

.slide-fade-enter-to,
.slide-fade-leave-from {
    opacity: 1;
    max-height: 200px;
    transform: translateY(0);
}
</style>
