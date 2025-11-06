import { ref, computed, watch } from "vue";
import axios from "axios";

export function useStatusOverdue() {
    // State
    const reportData = ref({ reports: [] });
    const searchTerm = ref("");
    const selectedDate = ref(new Date().toISOString().split("T")[0]);
    const selectedPIC = ref("");
    const itemsPerPage = 15;
    const currentPage = ref(1);
    const isLoading = ref(false);
    const error = ref(null);
    const selectedStatus = ref("");

    // Fetch Data
    const fetchDataCurrent = async () => {
        isLoading.value = true;
        error.value = null;
        try {
            const res = await axios.get(`/api/reports/overdue-status`);
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
                pic_name: report.pic,
                instock: report.instock,
                status: report.current_status,
                days: report.days,
            });
        });
        return items;
    });

    const filteredReports = computed(() => {
        let filtered = allReports.value;

        if (selectedStatus.value && selectedStatus.value !== "OK") {
            filtered = filtered.filter(
                (it) => it.status === selectedStatus.value
            );
        }

        if (selectedPIC.value) {
            filtered = filtered.filter(
                (it) => it.pic_name === selectedPIC.value
            );
        }

        if (searchTerm.value) {
            const q = searchTerm.value.toLowerCase();
            filtered = filtered.filter((it) => {
                return (
                    String(it.pic_name || "")
                        .toLowerCase()
                        .includes(q) ||
                    String(it.material_number).toLowerCase().includes(q) ||
                    String(it.description || "")
                        .toLowerCase()
                        .includes(q) ||
                    String(it.status || "")
                        .toLowerCase()
                        .includes(q)
                );
            });
        }
        return filtered;
    });
    const uniquePICs = computed(() => {
        const pics = new Set();
        allReports.value.forEach((item) => {
            if (item.pic_name) pics.add(item.pic_name);
        });
        return Array.from(pics).sort();
    });

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
        uniquePICs,
        clearFilters,
        // Pagination
        // Methods
        fetchDataCurrent,
        selectedStatus,
    };
}
