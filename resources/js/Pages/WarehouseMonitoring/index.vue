<template>
    <MainAppLayout title="Warehouse Monitoring" subtitle="Warehouse GR Monitoring">
        <div class="flex flex-col justify-center min-h-screen bg-gray-50 ">
            <h1 class="flex justify-center text-xl font-bold bg-white">Warehouse Performance Monitoring</h1>
            <div class="flex flex-col justify-center items-center mx-4 my-4">
                <!-- All in one Dashboard -->
                <div class="min-h-screen grid grid-cols-1 md:grid-cols-3 bg-white ">
                    <div class="flex flex-row justify-center mx-6 ">
                        <section>
                            <div class="mt-2">
                                <CautionOverdueLeaderboard size="compact" :limit="5" :autoRefresh="false"
                                    :showViewAll="true" viewAllUrl="/general-report/leaderboard" />
                            </div>
                            <div class="mt-2">
                                <ShortageOverdueLeaderboard size="compact" :limit="5" :autoRefresh="false"
                                    :showViewAll="true" viewAllUrl="/general-report/leaderboard" />
                            </div>
                        </section>
                    </div>

                    <div class="flex flex-row justify-center mx-6">
                        <section class="mt-2">
                            <RecoveryDaysReport size="compact" :limit="4" :show-chart="true"
                                view-all-url="/recovery-report" />
                        </section>
                    </div>
                    <div class="flex flex-row justify-center mx-6 ">
                        <section>
                            <div class="mt-2">
                                <StatusPieChart size="COMPACT" />
                            </div>
                            <div class="">
                                <StatusFrequencyTable size="compact" />
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </MainAppLayout>


</template>

<script setup>
import CautionOverdueLeaderboard from '@/Components/CautionOverdueLeaderboard.vue';
import ShortageOverdueLeaderboard from '@/Components/ShortageOverdueLeaderboard.vue';
import StatusPieChart from '@/Components/StatusPieChart.vue';
import RecoveryDaysReport from '@/Components/RecoveryDaysReport.vue';
import StatusFrequencyTable from '@/Components/StatusFrequencyTable.vue';
import MainAppLayout from '@/Layouts/MainAppLayout.vue';


</script>
