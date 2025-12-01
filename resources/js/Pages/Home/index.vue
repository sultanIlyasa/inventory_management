<template>
    <MainAppLayout>
        <template #header>
            <h2 class="font-semibold text-2xl text-gray-800 leading-tight tracking-wide">
                Inventory Checkup
            </h2>
        </template>

        <div class="py-12 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <!-- Welcome Banner -->
                <div class="bg-white shadow-md sm:rounded-xl p-8 border border-gray-100 text-center mb-10">
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome!</h1>
                    <p class="text-gray-600 text-sm">
                        Manage your daily stock input, monitor warehouse status, and access your account.
                    </p>
                </div>

                <!-- Feature Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                    <!-- Daily Input -->
                    <Link href="/daily-input"
                        class="group hover:shadow-xl transition-all duration-300 rounded-xl bg-white border border-gray-200 p-6 flex flex-col items-center text-center cursor-pointer">
                    <div class="rounded-full bg-emerald-100 p-3 mb-4 group-hover:bg-emerald-200 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-emerald-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800">Daily Input</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Input daily warehouse stock and update material availability.
                    </p>
                    <span
                        class="mt-4 inline-block px-4 py-2 text-sm rounded bg-emerald-600 text-white group-hover:bg-emerald-700 transition">
                        Go
                    </span>
                    </Link>

                    <!-- Warehouse Monitoring -->
                    <Link href="/warehouse-monitoring"
                        class="group hover:shadow-xl transition-all duration-300 rounded-xl bg-white border border-gray-200 p-6 flex flex-col items-center text-center cursor-pointer">
                    <div class="rounded-full bg-blue-100 p-3 mb-4 group-hover:bg-blue-200 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h4l3 10h8l3-10h4M5 6h14M10 10V6m4 4V6" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800">Warehouse Monitoring</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Track shortages, overflows, and real-time warehouse performance.
                    </p>
                    <span
                        class="mt-4 inline-block px-4 py-2 text-sm rounded bg-blue-600 text-white group-hover:bg-blue-700 transition">
                        Monitor
                    </span>
                    </Link>

                    <!-- Login -->
                    <Link href="/login"
                        class="group hover:shadow-xl transition-all duration-300 rounded-xl bg-white border border-gray-200 p-6 flex flex-col items-center text-center cursor-pointer">
                    <div class="rounded-full bg-purple-100 p-3 mb-4 group-hover:bg-purple-200 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800">Login</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Access your account to manage inventory and warehouse tasks.
                    </p>
                    <span
                        class="mt-4 inline-block px-4 py-2 text-sm rounded bg-purple-600 text-white group-hover:bg-purple-700 transition">
                        Login
                    </span>
                    </Link>

                </div>

            </div>
        </div>
    </MainAppLayout>
</template>

<script setup>
import MainAppLayout from '@/Layouts/MainAppLayout.vue';
import { Link } from '@inertiajs/vue3';
</script>
