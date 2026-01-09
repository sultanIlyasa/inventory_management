import { ref, computed, watch } from "vue";
import axios from "axios";

export function useDailyInput() {
    // State
    const reportData = ref({ checked: [], missing: [] });
    const searchTerm = ref("");
    const selectedDate = ref(new Date().toISOString().split("T")[0]);
    const selectedUsage = ref(""); // default DAILY
    const selectedPIC = ref("");
    const selectedLocation = ref("");
    const selectedGentani = ref("");

    const currentPage = ref(1);
    const itemsPerPage = 25;

    const isLoading = ref(false);
    const error = ref(null);

    const fetchDailyData = async () => {
        isLoading.value = true;
        try {
            const res = await axios.get("/api/daily-input/status", {
                params: {
                    date: selectedDate.value,
                    usage: selectedUsage.value || null,
                    location: selectedLocation.value || null,
                },
            });

            reportData.value = {
                checked: res.data.checked || [],
                missing: res.data.missing || [],
            };
        } catch (err) {
            console.error("Load report failed:", err);
            error.value = err.message;
        } finally {
            isLoading.value = false;
        }
    };

    // Data Mapping
    const allItems = computed(() => {
        const items = [];

        reportData.value.checked.forEach((item) => {
            items.push({
                key: `checked-${item.id}`,
                id: item.id,
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

        reportData.value.missing.forEach((item) => {
            items.push({
                key: `missing-${item.id}`,
                id: item.id,
                material_number: item.material_number,
                description: item.description,
                pic_name: item.pic_name,
                unit_of_measure: item.unit_of_measure,
                stock_minimum: item.stock_minimum,
                stock_maximum: item.stock_maximum,
                rack_address: item.rack_address,
                daily_stock: null,
                status: "UNCHECKED",
                location: item.location,
                usage: item.usage,
                gentani: item.gentani,
            });
        });

        return items;
    });

    // Filters
    const filteredItems = computed(() => {
        let items = allItems.value;
        if (selectedPIC.value)
            items = items.filter((it) => it.pic_name === selectedPIC.value);

        if (selectedLocation.value)
            items = items.filter(
                (it) => it.location === selectedLocation.value
            );
        if (selectedUsage.value)
            items = items.filter((it) => it.usage === selectedUsage.value);

        if (selectedGentani.value)
            items = items.filter((it) => it.gentani === selectedGentani.value);

        if (searchTerm.value) {
            const q = searchTerm.value.toLowerCase();
            items = items.filter((it) =>
                Object.values(it).join(" ").toLowerCase().includes(q)
            );
        }

        return items;
    });

    // Sort and Pagination
    const sortOrder = ref("default");
    const sortedItems = computed(() => {
        let items = [...filteredItems.value];

        if (sortOrder.value === "priority") {
            const map = {
                SHORTAGE: 1,
                OVERFLOW: 2,
                CAUTION: 3,
                UNCHECKED: 4,
                OK: 5,
            };
            return items.sort(
                (a, b) => (map[a.status] || 99) - (map[b.status] || 99)
            );
        }

        if (sortOrder.value === "rack-asc")
            return items.sort((a, b) =>
                a.rack_address.localeCompare(b.rack_address)
            );

        if (sortOrder.value === "rack-desc")
            return items.sort((a, b) =>
                b.rack_address.localeCompare(a.rack_address)
            );

        return items;
    });

    const totalItems = computed(() => sortedItems.value.length);
    const totalPages = computed(() =>
        Math.ceil(totalItems.value / itemsPerPage)
    );

    const paginatedItems = computed(() => {
        const start = (currentPage.value - 1) * itemsPerPage;
        return sortedItems.value.slice(start, start + itemsPerPage);
    });

    // Statistics based on filtered items
    const stats = computed(() => {
        const items = filteredItems.value;
        return {
            unchecked: items.filter(item => item.status === 'UNCHECKED').length,
            shortage: items.filter(item => item.status === 'SHORTAGE').length,
            caution: items.filter(item => item.status === 'CAUTION').length,
            overflow: items.filter(item => item.status === 'OVERFLOW').length,
        };
    });

    const clearFilters = () => {
        searchTerm.value = "";
        selectedPIC.value = "";
        selectedLocation.value = "";
        selectedUsage.value = "";
        selectedGentani.value = "";
        currentPage.value = 1;
    };

    const submitDailyStock = async (item) => {
        try {
            await axios.post("/api/daily-input", {
                material_id: item.id,
                date: selectedDate.value,
                daily_stock: Number(item.daily_stock),
            });

            fetchDailyData();
        } catch (err) {
            console.error("Submit failed:", err);
        }
    };

    const deleteInput = async (item) => {
        try {
            await axios.delete(`/api/daily-input/delete/${item.id}`);
            fetchDailyData();
        } catch (err) {
            console.error("Delete failed:", err);
        }
    };

    const syncDailyInputStatus = async () => {
        isLoading.value = true;
        try {
            const res = await axios.post("/api/daily-input/sync-status");
            if (res.data.success) {
                await fetchDailyData();
            }
            return res.data;
        } catch (err) {
            console.error("Sync failed:", err);
            error.value = err.message;
            return { success: false, message: err.message };
        } finally {
            isLoading.value = false;
        }
    };

    // Watchers
    watch([selectedDate, selectedUsage, selectedLocation], fetchDailyData);

    return {
        searchTerm,
        selectedDate,
        selectedPIC,
        selectedLocation,
        selectedUsage,
        selectedGentani,
        uniquePICs: computed(() => [
            ...new Set(allItems.value.map((i) => i.pic_name)),
        ]),
        usages: computed(() => [
            ...new Set(allItems.value.map((i) => i.usage)),
        ]),
        locations: computed(() => [
            ...new Set(allItems.value.map((i) => i.location)),
        ]),
        gentaniItems: ["GENTAN-I", "NON_GENTAN-I"],

        paginatedItems,
        totalPages,
        totalItems,
        startItem: computed(() =>
            totalItems.value ? (currentPage.value - 1) * itemsPerPage + 1 : 0
        ),
        endItem: computed(() =>
            Math.min(currentPage.value * itemsPerPage, totalItems.value)
        ),

        visiblePages: computed(() => {
            const pages = [];
            const total = totalPages.value;
            const current = currentPage.value;
            const delta = 2;
            let start = Math.max(1, current - delta);
            let end = Math.min(total, current + delta);

            if (current <= delta) {
                end = Math.min(total, end + (delta - current + 1));
            }
            if (current + delta >= total) {
                start = Math.max(1, start - (current + delta - total));
            }

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }
            return pages;
        }),
        currentPage,
        sortOrder,
        uncheckedCount: computed(() => reportData.value.missing.length),
        stats,
        fetchDailyData,
        clearFilters,
        submitDailyStock,
        deleteInput,
        syncDailyInputStatus,
        isLoading,
    };
}
