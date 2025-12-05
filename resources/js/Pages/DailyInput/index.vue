<template>
    <MainAppLayout title="Daily Input" subtitle="Manage daily stock entries">
        <div class="min-h-screen w-full overflow-hidden bg-gray-50">
            <div class="flex">
                <div class="flex-1">
                    <!-- Main content -->
                    <div class="mx-auto w-full">
                        <h1 class="flex flex-col mx-auto text-2xl font-bold text-center my-10">
                            Daily Input Dashboard
                        </h1>
                        <!-- Table Section -->
                        <div class="bg-white rounded-2xl shadow-lg p-4 ">
                            <DashboardTable />
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </MainAppLayout>
</template>

<script setup>
import DashboardTable from '@/Components/DailyInputDashboard.vue'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import MainAppLayout from '@/Layouts/MainAppLayout.vue'

const page = usePage()
const user = computed(() => (page.props && page.props.auth ? page.props.auth.user : null))
const canLogin = computed(() => Boolean(page.props && page.props.canLogin))

</script>
