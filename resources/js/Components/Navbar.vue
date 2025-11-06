<template>
    <header class="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">

                <!-- Right: Navigation Links -->
                <div class="hidden sm:flex space-x-4">
                    <ResponsiveNavLink href="/dashboard" :active="page.url.startsWith('/dashboard')">
                        Dashboard
                    </ResponsiveNavLink>

                    <ResponsiveNavLink href="/reports" :active="page.url.startsWith('/reports')">
                        Reports
                    </ResponsiveNavLink>

                    <ResponsiveNavLink href="/analytics" :active="page.url.startsWith('/analytics')">
                        Analytics
                    </ResponsiveNavLink>

                    <ResponsiveNavLink href="/settings" :active="page.url.startsWith('/settings')">
                        Settings
                    </ResponsiveNavLink>
                </div>

                <!-- Mobile Menu Button -->
                <div class="sm:hidden">
                    <button @click="isOpen = !isOpen"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-600 hover:text-gray-800 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                        <svg v-if="!isOpen" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Dropdown Menu -->
        <div v-if="isOpen" class="sm:hidden border-t border-gray-200 bg-white">
            <div class="space-y-1 px-2 pt-2 pb-3">
                <ResponsiveNavLink href="/dashboard" :active="page.url.startsWith('/dashboard')">
                    Dashboard
                </ResponsiveNavLink>

                <ResponsiveNavLink href="/reports" :active="page.url.startsWith('/reports')">
                    Reports
                </ResponsiveNavLink>

                <ResponsiveNavLink href="/analytics" :active="page.url.startsWith('/analytics')">
                    Analytics
                </ResponsiveNavLink>

                <ResponsiveNavLink href="/settings" :active="page.url.startsWith('/settings')">
                    Settings
                </ResponsiveNavLink>
            </div>
        </div>
    </header>
</template>

<script setup>
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";

const isOpen = ref(false);
const page = usePage();
</script>
