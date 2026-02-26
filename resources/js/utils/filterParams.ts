export function buildFilterParams(filters: Record<string, unknown>): URLSearchParams {
    const p = new URLSearchParams()
    for (const [k, v] of Object.entries(filters)) {
        if (v !== null && v !== undefined && v !== '' && v !== 'all') p.set(k, String(v))
    }
    return p
}
