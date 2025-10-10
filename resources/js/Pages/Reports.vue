<template>
    <div class="min-h-screen bg-gray-50">
        <div class="flex">
            <Sidebar />
            <div class="flex-1">
                <!-- Main content -->
                <div class="mx-auto w-full max-w-6xl flex-1 flex flex-col px-4 py-6 md:px-8">
                    <h1 class="flex flex-col mx-auto text-2xl font-bold text-center my-10">
                        General Reports
                    </h1>

                    <!-- Table Section -->
                    <div class="bg-white rounded-2xl shadow-lg p-4 md:p-6  w-full">

                        <DashboardReport />
                    </div>

                    <!-- General Section -->
                    <div class="mt-6">
                        General
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import Sidebar from '@/Components/Sidebar.vue'
import DashboardReport from '@/Components/ReportDashboard.vue'

</script>
