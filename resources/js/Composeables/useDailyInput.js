// composables/useSearchFilters.js
import { ref, computed, watch } from "vue";
import axios from "axios";

export function useDailyInput() {
    // State
    const reportData = ref({ checked: [], missing: [] });
    const searchTerm = ref("");
    const selectedDate = ref(new Date().toISOString().split("T")[0]);
    const selectedPIC = ref("");
    const selectedUsage = ref("");
    const selectedLocation = ref("");
    const selectedGentani = ref("");
    const currentPage = ref(1);
    const itemsPerPage = 25;
    const isLoading = ref(false);
    const error = ref(null);

    // Fetch data
    const fetchData = async () => {
        isLoading.value = true;
        error.value = null;
        try {
            const res = await axios.get(
                `/api/daily-input/status?date=${selectedDate.value}`
            );
            reportData.value = {
                checked: res.data.checked || [],
                missing: res.data.missing || [],
            };
        } catch (err) {
            console.error("Failed to load report data:", err);
            error.value = err.message;
        } finally {
            isLoading.value = false;
        }
    };

    const allItems = computed(() => {
        const items = [];

        reportData.value.checked.forEach((item, index) => {
            items.push({
                key: `checked-${item.id}`,
                id: item.id,
                number: index + 1,
                material_number: item.material.material_number,
                description: item.material.description,
                pic_name: item.material.pic_name,
                unit_of_measure: item.material.unit_of_measure,
                stock_minimum: item.material.stock_minimum,
                stock_maximum: item.material.stock_maximum,
                rack_address: item.material.rack_address,
                daily_stock: item.daily_stock,
                created_at: item.created_at,
                status: item.status,
                location: item.material.location,
                usage: item.material.usage,
                gentani: item.material.gentani,
            });
        });

        reportData.value.missing.forEach((item, index) => {
            items.push({
                key: `missing-${item.id}`,
                id: item.id,
                number: reportData.value.checked.length + index + 1,
                material_number: item.material_number,
                description: item.description,
                pic_name: item.pic_name,
                unit_of_measure: item.unit_of_measure,
                stock_minimum: item.stock_minimum,
                stock_maximum: item.stock_maximum,
                rack_address: item.rack_address,
                daily_stock: null,
                status: "UNCHECKED",
                location: item.location, // This should already be correct for missing items
                usage: item.usage, // This should already be correct for missing items
                gentani: item.gentani,
            });
        });

        return items;
    });

    const filteredItems = computed(() => {
        let items = allItems.value;

        if (selectedPIC.value) {
            items = items.filter((it) => it.pic_name === selectedPIC.value);
        }
        if (selectedLocation.value) {
            items = items.filter(
                (it) => it.location === selectedLocation.value
            );
        }
        if (selectedUsage.value) {
            items = items.filter((it) => it.usage === selectedUsage.value);
        }

        if (selectedGentani.value) {
            items = items.filter((it) => it.gentani === selectedGentani.value);
        }

        if (searchTerm.value) {
            const q = searchTerm.value.toLowerCase();
            items = items.filter((it) => {
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

        return items;
    });

    const uniquePICs = computed(() => {
        const pics = new Set();
        allItems.value.forEach((item) => {
            if (item.pic_name) pics.add(item.pic_name);
        });
        return Array.from(pics).sort();
    });

    const locations = computed(() => {
        const locations = new Set();
        allItems.value.forEach((item) => {
            if (item.location) locations.add(item.location);
        });
        return Array.from(locations).sort();
    });
    const usages = computed(() => {
        const usages = new Set();
        allItems.value.forEach((item) => {
            if (item.usage) usages.add(item.usage);
        });
        return Array.from(usages).sort();
    });
    const uncheckedCount = computed(() => reportData.value.missing.length);
    const gentaniItems = ["GENTAN-I", "NON_GENTAN-I"];

    const sortOrder = ref("default");
    const filteredAndSortedItems = computed(() => {
        let items = filteredItems.value;

        switch (sortOrder.value) {
            case "priority":
                return [...items].sort((a, b) => {
                    const orderMap = {
                        SHORTAGE: 1,
                        OVERFLOW: 2,
                        CAUTION: 3,
                        UNCHECKED: 4,
                        OK: 5,
                    };
                    return (
                        (orderMap[a.status] || 999) -
                        (orderMap[b.status] || 999)
                    );
                });
            case "status-asc":
                return [...items].sort((a, b) =>
                    a.status.localeCompare(b.status)
                );
            case "status-desc":
                return [...items].sort((a, b) =>
                    b.status.localeCompare(a.status)
                );
            default:
                return items;
        }
    });
    // Pagination (now uses sortedItems instead of filteredItems)
    const totalItems = computed(() => filteredAndSortedItems.value.length);
    const totalPages = computed(() =>
        Math.ceil(totalItems.value / itemsPerPage)
    );

    const paginatedItems = computed(() => {
        const start = (currentPage.value - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        return filteredAndSortedItems.value.slice(start, end);
    });

    const startItem = computed(() => {
        return totalItems.value === 0
            ? 0
            : (currentPage.value - 1) * itemsPerPage + 1;
    });

    const endItem = computed(() => {
        return Math.min(currentPage.value * itemsPerPage, totalItems.value);
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
    // Methods
    const clearFilters = () => {
        searchTerm.value = "";
        selectedPIC.value = "";
        selectedLocation.value = "";
        selectedUsage.value = "";
        selectedGentani.value = "";
        currentPage.value = 1;
    };

    const submitDailyStock = async (item) => {
        if (item.daily_stock < 0) {
            alert("Please enter valid daily stock");
            return;
        }

        try {
            await axios.post("/api/daily-input", {
                material_id: item.id,
                date: selectedDate.value,
                daily_stock: Number(item.daily_stock),
            });
            await fetchData();
        } catch (err) {
            console.error("submitDailyStock error", err);
        }
    };

    const deleteInput = async (item) => {
        try {
            await axios.delete(`/api/daily-input/delete/${item.id}`);
            await fetchData();
        } catch (error) {
            console.error("Failed to delete entry:", error);
        }
    };

    // Watchers
    watch(selectedDate, fetchData);
    watch(
        [
            searchTerm,
            selectedPIC,
            selectedLocation,
            selectedUsage,
            selectedGentani,
        ],
        () => {
            currentPage.value = 1;
        }
    );

    watch(totalPages, (newTotal) => {
        const safeTotal = Math.max(1, newTotal || 1);
        if (currentPage.value > safeTotal) {
            currentPage.value = safeTotal;
        }
    });

    return {
        // State

        reportData,
        searchTerm,
        selectedDate,
        selectedPIC,
        selectedLocation,
        selectedUsage,
        selectedGentani,
        currentPage,
        itemsPerPage,
        isLoading,
        error,

        // Computed

        usages,
        filteredItems,
        uniquePICs,
        allItems,
        locations,
        gentaniItems,
        uncheckedCount,
        paginatedItems,
        totalItems,
        totalPages,
        startItem,
        endItem,
        visiblePages,
        sortOrder,
        paginatedItems,

        // Methods
        fetchData,
        clearFilters,
        submitDailyStock,
        deleteInput,
    };
}
