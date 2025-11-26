<template>
    <MainAppLayout title="Daily Input" subtitle="Manage daily stock entries">
        <div class="min-h-screen w-full overflow-hidden bg-gray-50">
            <div class="flex">
                <div class="flex-1">
                    <!-- Main content -->
                    <div class="mx-auto w-full max-w-6xl flex-1 flex flex-col px-4 py-6 md:px-8">
                        <!-- Table Section -->
                        <div class="bg-white rounded-2xl shadow-lg p-4 md:p-6  w-full">
                            <StatusOverdueDashboard variant="compact" />
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </MainAppLayout>
</template>

<script setup>

import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import MainAppLayout from '@/Layouts/MainAppLayout.vue'
import StatusOverdueDashboard from '@/Components/StatusOverdueDashboard.vue'

const page = usePage()
const user = computed(() => (page.props && page.props.auth ? page.props.auth.user : null))
const canLogin = computed(() => Boolean(page.props && page.props.canLogin))

</script>
