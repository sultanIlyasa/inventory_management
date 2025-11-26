import { ref, computed, watch } from "vue";
import axios from "axios";

export function useStatusOverdue() {
    // State
    const reportData = ref({ reports: [] });
    const searchTerm = ref("");
    const selectedDate = ref(new Date().toISOString().split("T")[0]);
    const selectedPIC = ref("");
    const selectedUsage = ref("");
    const selectedLocation = ref("");
    const selectedGentani = ref("");
    const itemsPerPage = 15;
    const currentPage = ref(1);
    const isLoading = ref(false);
    const error = ref(null);
    const selectedStatus = ref("");

    // Sorting state
    const sortField = ref("status"); // 'status' or 'days'
    const sortDirection = ref("desc"); // 'asc' or 'desc'

    // Fetch Data
    const fetchDataCurrent = async () => {
        isLoading.value = true;
        error.value = null;
        try {
            const res = await axios.get(`/api/reports/overdue-status`);
            reportData.value = {
                reports: res.data || [],
            };
        } catch (err) {
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
                key: `report-${index}`,
                id: report.id || index,
                number: index + 1,
                material_number: report.material_number,
                description: report.description,
                pic_name: report.pic,
                instock: report.instock,
                status: report.current_status,
                days: report.days || 0,
                location: report.location,
                usage: report.usage,
                gentani: report.gentani,
            });
        });
        return items;
    });
    const gentaniItems = ["GENTAN-I", "NON_GENTAN-I"];

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

        if (selectedLocation.value) {
            filtered = filtered.filter(
                (it) => it.location === selectedLocation.value
            );
        }

        if (selectedUsage.value) {
            filtered = filtered.filter(
                (it) => it.usage === selectedUsage.value
            );
        }
        if (selectedGentani.value) {
            filtered = filtered.filter(
                (it) => it.gentani === selectedGentani.value
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

    const locations = computed(() => {
        const locations = new Set();
        allReports.value.forEach((item) => {
            if (item.location) locations.add(item.location);
        });
        return Array.from(locations).sort();
    });

    const usages = computed(() => {
        const usages = new Set();
        allReports.value.forEach((item) => {
            if (item.usage) usages.add(item.usage);
        });
        return Array.from(usages).sort();
    });

    // Status priority for sorting
    const statusPriority = {
        SHORTAGE: 1,
        CAUTION: 2,
        OVERFLOW: 3,
        OK: 4,
        UNCHECKED: 5,
    };

    // Sorted reports
    const sortedReports = computed(() => {
        const reports = [...filteredReports.value];

        return reports.sort((a, b) => {
            let compareValue = 0;

            if (sortField.value === "status") {
                // Sort by status priority
                const aPriority = statusPriority[a.status] || 999;
                const bPriority = statusPriority[b.status] || 999;
                compareValue = aPriority - bPriority;

                // Secondary sort by days if same status
                if (compareValue === 0) {
                    compareValue = b.days - a.days;
                }
            } else if (sortField.value === "days") {
                // Sort by days
                compareValue = b.days - a.days;

                // Secondary sort by status if same days
                if (compareValue === 0) {
                    const aPriority = statusPriority[a.status] || 999;
                    const bPriority = statusPriority[b.status] || 999;
                    compareValue = aPriority - bPriority;
                }
            }

            // Apply sort direction
            return sortDirection.value === "asc" ? -compareValue : compareValue;
        });
    });

    const totalReports = computed(() => sortedReports.value.length);
    const totalPages = computed(() =>
        Math.ceil(totalReports.value / itemsPerPage)
    );

    const paginatedItems = computed(() => {
        const start = (currentPage.value - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        return sortedReports.value.slice(start, end);
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
        selectedPIC.value = "";
        selectedLocation.value = "";
        selectedUsage.value = "";
        currentPage.value = 1;
    };

    const handleSortChange = ({ field, direction }) => {
        sortField.value = field;
        sortDirection.value = direction;
        currentPage.value = 1; // Reset to first page when sorting
    };

    // Watchers
    watch(selectedDate, fetchDataCurrent);
    watch([searchTerm, selectedPIC, selectedUsage, selectedLocation], () => {
        currentPage.value = 1;
    });

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
        uniquePICs,
        selectedStatus,
        selectedLocation,
        locations,
        selectedUsage,
        usages,
        sortField,
        sortDirection,
        selectedGentani,
        gentaniItems,
        // Methods
        fetchDataCurrent,
        clearFilters,
        handleSortChange,
    };
}
