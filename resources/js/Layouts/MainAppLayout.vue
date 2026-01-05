<template>
    <div class="min-h-screen bg-gray-50">

        <!-- MOBILE TOP BAR -->
        <div
            class="lg:hidden fixed top-0 left-0 right-0 z-40 bg-white border-b px-4 py-3 flex items-center justify-between">
            <h1 class="text-lg font-semibold text-gray-900">{{ title || "Dashboard" }}</h1>

            <button @click="toggleSidebar" class="p-2 rounded-md hover:bg-gray-100">
                <Menu class="w-5 h-5 text-gray-700" />
            </button>
        </div>

        <!-- MOBILE OVERLAY -->
        <transition name="fade">
            <div v-if="sidebarOpen && isMobile" @click="toggleSidebar"
                class="fixed inset-0 bg-black bg-opacity-40 z-40"></div>
        </transition>

        <!-- SIDEBAR -->
        <aside :class="[
            'fixed top-0 left-0 h-full bg-white border-r shadow-sm transition-all z-50 flex flex-col',
            sidebarCollapsed ? 'w-20' : 'w-64',
            isMobile && !sidebarOpen ? '-translate-x-full' : 'translate-x-0',
            'lg:translate-x-0'
        ]">
            <!-- SIDEBAR HEADER -->
            <div class="flex items-center h-16 px-4 border-b">
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 bg-blue-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm">WH</span>
                    </div>

                    <transition name="fade">
                        <span v-if="!sidebarCollapsed" class="text-lg font-semibold">
                            Information System
                        </span>
                    </transition>
                </div>
            </div>

            <!-- NAVIGATION -->
            <nav class="flex-1 p-3 overflow-y-auto">
                <ul class="space-y-1">

                    <!-- MENU ITEMS -->
                    <li v-for="item in menuItems" :key="item.name" class="relative group select-none">

                        <!-- TOOLTIP (TOP LEVEL) -->
                        <div v-if="sidebarCollapsed" class="pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-2
                     rounded bg-gray-900 text-white text-xs px-2 py-1 whitespace-nowrap
                     opacity-0 group-hover:opacity-100 transition-opacity z-50">
                            {{ item.name }}
                        </div>

                        <!-- COLLAPSIBLE GROUP -->
                        <template v-if="item.children">
                            <button
                                class="flex items-center justify-between w-full px-3 py-2 rounded-lg hover:bg-gray-100"
                                @click="handleGroupClick(item.name)">
                                <div :class="[
                                    'flex items-center gap-3',
                                    sidebarCollapsed ? 'justify-center w-full' : ''
                                ]">
                                    <component :is="item.icon" class="w-5 h-5 text-gray-600" />
                                    <span v-if="!sidebarCollapsed" class="text-sm font-medium">
                                        {{ item.name }}
                                    </span>
                                </div>

                                <ChevronRight v-if="!sidebarCollapsed"
                                    class="w-4 h-4 text-gray-500 transition-transform"
                                    :class="{ 'rotate-90': isGroupOpen(item.name) }" />
                            </button>

                            <!-- CHILD LINKS (only when expanded & not collapsed) -->
                            <transition name="slide-fade">
                                <ul v-if="isGroupOpen(item.name) && !sidebarCollapsed" class="ml-8 mt-1 space-y-1">
                                    <li v-for="child in item.children" :key="child.name">
                                        <Link :href="route(child.route)" :class="[
                                            'block px-3 py-2 rounded-md text-sm hover:bg-gray-100',
                                            isActive(child.route)
                                                ? 'bg-blue-50 text-blue-600 font-medium'
                                                : 'text-gray-600'
                                        ]">
                                        {{ child.name }}
                                        </Link>
                                    </li>
                                </ul>
                            </transition>
                        </template>

                        <!-- SINGLE LINK -->
                        <template v-else>
                            <Link :href="route(item.route)" :class="[
                                'flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-100',
                                isActive(item.route)
                                    ? 'bg-blue-50 text-blue-600 font-medium'
                                    : 'text-gray-700',
                                sidebarCollapsed ? 'justify-center' : ''
                            ]">
                            <component :is="item.icon" class="w-5 h-5 text-gray-600" />
                            <span v-if="!sidebarCollapsed" class="text-sm">{{ item.name }}</span>
                            </Link>
                        </template>
                    </li>
                </ul>
            </nav>

            <!-- COLLAPSE TOGGLE - CENTER ARROW ON EDGE (DESKTOP ONLY) -->
            <button class="hidden lg:flex items-center justify-center
               absolute top-1/2 -right-3 -translate-y-1/2
               w-6 h-6 rounded-full border bg-white shadow
               hover:bg-gray-100 transition" @click="toggleCollapse">
                <ChevronLeft class="w-4 h-4 text-gray-700 transition-transform"
                    :class="{ 'rotate-180': sidebarCollapsed }" />
            </button>
        </aside>

        <!-- MAIN CONTENT -->
        <main :class="['transition-all duration-300', sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64']">
            <div class="p-4 lg:pt-4 pt-16">
                <slot />
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import {
    Home,
    ListChecks,
    BarChart3,
    LogIn,
    Menu,
    ChevronRight,
    ChevronLeft
} from "lucide-vue-next";

defineProps({
    title: {
        type: String,
        default: "Dashboard"
    },
    subtitle: {
        type: String,
        default: null
    }
});

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);

const sidebarOpen = ref(true);
const sidebarCollapsed = ref(false);
const isMobile = ref(false);
const openGroups = ref([]);

// MENU ITEMS WITH LUCIDE ICONS
const menuItems = [
    {
        name: "Homepage",
        route: "homepage.index",
        icon: Home
    },
    {
        name: "Daily Input",
        route: "daily-input.index",
        icon: ListChecks
    },
    {
        name: "Warehouse Monitoring",
        icon: BarChart3,
        children: [
            { name: "Dashboard Monitoring", route: "warehouse-monitoring.index" },
            { name: "Leaderboard", route: "warehouse-monitoring.leaderboard" },
            { name: "Recovery Days", route: "warehouse-monitoring.recovery-days" },
            { name: "Status Change", route: "warehouse-monitoring.status-change" },
            { name: "Overdue Days", route: "warehouse-monitoring.overdue-days" },
            { name: "Compliance", route: "warehouse-monitoring.check-compliance" }
        ]
    },
    {
        name: "Login",
        route: "login",
        icon: LogIn
    }
];

// RESPONSIVE BEHAVIOR
const checkMobile = () => {
    isMobile.value = window.innerWidth < 1024;
    if (!isMobile.value) {
        sidebarOpen.value = true;
    }
};

onMounted(() => {
    checkMobile();
    window.addEventListener("resize", checkMobile);

    const saved = localStorage.getItem("sidebarCollapsed");
    if (saved !== null) {
        sidebarCollapsed.value = saved === "true";
    }
});

onUnmounted(() => {
    window.removeEventListener("resize", checkMobile);
});

// TOGGLES
const toggleSidebar = () => {
    if (isMobile.value) {
        sidebarOpen.value = !sidebarOpen.value;
    }
};

const toggleCollapse = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
    if (sidebarCollapsed.value) {
        // when collapsing, close all groups to avoid weird states
        openGroups.value = [];
    }
    localStorage.setItem("sidebarCollapsed", sidebarCollapsed.value.toString());
};

const isGroupOpen = (name) => openGroups.value.includes(name);

const handleGroupClick = (name) => {
    if (sidebarCollapsed.value) return; // do nothing when collapsed
    if (openGroups.value.includes(name)) {
        openGroups.value = openGroups.value.filter((n) => n !== name);
    } else {
        openGroups.value.push(name);
    }
};

const isActive = (routeName) => route().current(routeName + "*");
</script>

<style scoped>
/* nice fade */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* slide for child menu */
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.2s ease;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
