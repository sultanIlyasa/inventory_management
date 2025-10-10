import { ref, computed, watch } from "vue";
import axios from "axios";

export function useReport() {
    // State
    const reportData = ref({ reports: [] });
    const isLoading = ref(false);
    const error = ref(null);
    const selectedPIC = ref("");
    const itemsPerPage = 15;
    const currentPage = ref(1);
    const searchTerm = ref("");
    const selectedDate = ref(new Date().toISOString().split("T")[0]);

    // Fetch Data
    const fetchDataCurrent = async () => {
        isLoading.value = true;
        error.value = null;
        try {
            const res = await axios.get(`/api/reports/current-status`);
            reportData.value = {
                reports: res.data || [],
            };
            console.log("Report Data:", reportData.value);
        } catch (err) {
            console.error("Failed to load report data:", err);
            error.value = err.message;
        } finally {
            isLoading.value = false;
        }
    };
    // Computed
    const allReports = computed(() => {
        const items = [];
        reportData.value.reports.forEach((report, index) => {
            items.push({
                key: `report-${report.id}`,
                id: report.id,
                number: index + 1,
                material_number: report.material_number,
                description: report.description,
                pic: report.pic,
                instock: report.instock,
                status: report.current_status,
                days: report.days,
            });
        });
        return items;
    });

    const filteredReports = computed(() => {
        let filtered = allReports.value;

        if (selectedPIC.value) {
            filtered = filtered.filter(
                (item) => item.pic === selectedPIC.value
            );
        }

        if (searchTerm.value) {
            const term = searchTerm.value.toLowerCase();
            filtered = filtered.filter(
                (item) =>
                    item.material_number.toLowerCase().includes(term) ||
                    item.description.toLowerCase().includes(term) ||
                    item.pic.toLowerCase().includes(term) ||
                    item.status.toLowerCase().includes(term)
            );
        }
        return filtered;
    });

    // Pagination
    const totalReports = computed(() => filteredReports.value.length);
    const totalPages = computed(() =>
        Math.ceil(totalReports.value / itemsPerPage)
    );

    const paginatedItems = computed(() => {
        const start = (currentPage.value - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        return filteredReports.value.slice(start, end);
    });

    const startItem = computed(() => {
        return totalReports.value === 0
            ? 0
            : (currentPage.value - 1) * itemsPerPage + 1;
    });

    const endItem = computed(() => {
        return Math.min(currentPage.value * itemsPerPage, totalReports.value);
    });

    const visiblePages = computed(() => {
        const pages = [];
        const maxVisible = 5;
        let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2));
        let end = Math.min(totalPages.value, start + maxVisible - 1);

        if (end - start + 1 < maxVisible) {
            start = Math.max(1, end - maxVisible + 1);
        }

        for (let i = start; i <= end; i++) {
            pages.push(i);
        }

        return pages;
    });

    const clearFilters = () => {
        searchTerm.value = "";
        selectedPIC = "";
        currentPage.value = 1;
    };

    watch(selectedDate, fetchDataCurrent);
    watch([searchTerm, selectedPIC], () => {
        currentPage.value = 1;
    });
    watch(reportData, () => {
        console.log("Reports updated:", allReports.value);
    });

    // Process Items

    return {
        // State
        reportData,
        currentPage,
        selectedDate,
        searchTerm,
        allReports,
        isLoading,
        error,
        filteredReports,
        selectedPIC,
        totalReports,
        totalPages,
        paginatedItems,
        startItem,
        endItem,
        visiblePages,
        itemsPerPage,
        totalReports,
        allReports,
        // Pagination
        // Methods
        fetchDataCurrent,
    };
}
