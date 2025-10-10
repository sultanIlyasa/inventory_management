// composables/useSearchFilters.js
import { ref, computed, watch } from 'vue'
import axios from 'axios'

export function useDailyInput() {
    // State
    const reportData = ref({ checked: [], missing: [] })
    const searchTerm = ref('')
    const selectedDate = ref(new Date().toISOString().split('T')[0])
    const selectedPIC = ref('')
    const currentPage = ref(1)
    const itemsPerPage = 25
    const isLoading = ref(false)
    const error = ref(null)

    // Fetch data
    const fetchData = async () => {
        isLoading.value = true
        error.value = null
        try {
            const res = await axios.get(`/api/daily-input/status?date=${selectedDate.value}`)
            reportData.value = {
                checked: res.data.checked || [],
                missing: res.data.missing || []
            }
        } catch (err) {
            console.error('Failed to load report data:', err)
            error.value = err.message
        } finally {
            isLoading.value = false
        }
    }



    const allItems = computed(() => {
        const items = []

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
                status: item.status,

            })
        })

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
                status: 'UNCHECKED',

            })
        })

        return items
    })

    const filteredItems = computed(() => {
        let items = allItems.value

        if (selectedPIC.value) {
            items = items.filter((it) => it.pic_name === selectedPIC.value)
        }

        if (searchTerm.value) {
            const q = searchTerm.value.toLowerCase()
            items = items.filter((it) => {
                return (
                    String(it.pic_name || '').toLowerCase().includes(q) ||
                    String(it.material_number).toLowerCase().includes(q) ||
                    String(it.description || '').toLowerCase().includes(q) ||
                    String(it.status || '').toLowerCase().includes(q)
                )
            })
        }

        return items
    })

    const uniquePICs = computed(() => {
        const pics = new Set()
        allItems.value.forEach(item => {
            if (item.pic_name) pics.add(item.pic_name)
        })
        return Array.from(pics).sort()
    })

    const uncheckedCount = computed(() => reportData.value.missing.length)

    // Pagination
    const totalItems = computed(() => filteredItems.value.length)
    const totalPages = computed(() => Math.ceil(totalItems.value / itemsPerPage))

    const paginatedItems = computed(() => {
        const start = (currentPage.value - 1) * itemsPerPage
        const end = start + itemsPerPage
        return filteredItems.value.slice(start, end)
    })

    const startItem = computed(() => {
        return totalItems.value === 0 ? 0 : (currentPage.value - 1) * itemsPerPage + 1
    })

    const endItem = computed(() => {
        return Math.min(currentPage.value * itemsPerPage, totalItems.value)
    })

    const visiblePages = computed(() => {
        const pages = []
        const maxVisible = 5
        let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2))
        let end = Math.min(totalPages.value, start + maxVisible - 1)

        if (end - start + 1 < maxVisible) {
            start = Math.max(1, end - maxVisible + 1)
        }

        for (let i = start; i <= end; i++) {
            pages.push(i)
        }

        return pages
    })

    // Methods
    const clearFilters = () => {
        searchTerm.value = ''
        selectedPIC.value = ''
        currentPage.value = 1
    }

    const submitDailyStock = async (item) => {
        if (item.daily_stock < 0) {
            alert('Please enter valid daily stock')
            return
        }

        try {
            await axios.post('/api/daily-input', {
                material_id: item.id,
                date: selectedDate.value,
                daily_stock: Number(item.daily_stock)
            })
            alert('Entry submitted successfully')
            await fetchData()
        } catch (err) {
            console.error('submitDailyStock error', err)
            alert('Failed to submit')
        }
    }

    const deleteInput = async (item) => {
        if (!confirm('Delete this entry?')) return

        try {
            await axios.delete(`/api/daily-input/delete/${item.id}`)
            alert('Entry deleted successfully')
            await fetchData()
        } catch (error) {
            console.error('Failed to delete entry:', error)
            alert('Failed to delete entry')
        }
    }

    // Watchers
    watch(selectedDate, fetchData)
    watch([searchTerm, selectedPIC], () => {
        currentPage.value = 1
    })

    return {
        // State
        reportData,
        searchTerm,
        selectedDate,
        selectedPIC,
        currentPage,
        itemsPerPage,
        isLoading,
        error,

        // Computed
        allItems,
        filteredItems,
        uniquePICs,
        uncheckedCount,
        paginatedItems,
        totalItems,
        totalPages,
        startItem,
        endItem,
        visiblePages,

        // Methods
        fetchData,
        clearFilters,
        submitDailyStock,
        deleteInput
    }
}
