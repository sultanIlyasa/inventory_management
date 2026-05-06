# Warehouse Monitoring Feature

## Overview

The Warehouse Monitoring feature is a real-time inventory health dashboard. The main page (`index.vue`) is a component-based multi-section dashboard where every section is wired to a live API. Filters are global — changing any filter instantly refetches every visible section.

## Page Entry Point

- **Route**: `GET /warehouse-monitoring`
- **Page Component**: `resources/js/Pages/WarehouseMonitoring/index.vue`
- **Layout**: `MainAppLayout` with title "Warehouse Monitoring"
- **Controller**: `WarehouseMonitoringController`

---

## Architecture

```
Pages/WarehouseMonitoring/
  ├── index.vue                  (Dashboard — global filter, component-based)
  ├── Leaderboard.vue            (Full Caution/Shortage leaderboard with tabs)
  ├── OverdueDays.vue            (Full overdue days report)
  ├── LeaderChecklist.vue        (Full check compliance report)
  ├── StatusChange.vue           (Full status change tracker)
  └── RecoveryDays.vue           (Full recovery days report)

Components/
  ├── CautionOverdueLeaderboard.vue     (Reusable: compact/full; self-fetch when filters prop given)
  ├── ShortageOverdueLeaderboard.vue    (Reusable: compact/full; self-fetch when filters prop given)
  ├── StatusChangeContent.vue           (Reusable: compact/full; self-fetch when filters prop given)
  ├── RecoveryDaysReportContent.vue     (Reusable: compact/full; self-fetch when filters prop given)
  ├── OverdueTrendChart.vue             (Chart.js bar chart — status count overview)
  ├── RecoveryTrendChart.vue            (Chart.js line chart — avg recovery days by month)
  ├── DistributionDonutChart.vue        (Chart.js doughnut — OK/Caution/Shortage distribution)
  ├── FastestToCriticalChart.vue        (Chart.js horizontal bar — top 5 materials by days)
  ├── SystemRecommendationPanel.vue     (Derived recommendations from shortage/caution data)
  └── MaterialInventoryOverview.vue     (Full-width inventory table; dummy data, filters prop ready)

Controllers/
  ├── WarehouseMonitoringController.php    (Dashboard index + /api/dashboard endpoint)
  ├── ProblematicMaterialsController.php   (Problematic materials + consumption averages)
  ├── LeaderboardController.php            (Caution/Shortage leaderboards)
  ├── OverdueDaysController.php            (Overdue days report)
  ├── LeaderChecklistController.php        (Leader checklist report)
  ├── StatusChangeController.php           (Status change tracker)
  └── RecoveryDaysController.php           (Recovery days report)

Services/
  ├── ProblematicMaterialsService   (DB query + external API join + severity logic)
  ├── LeaderboardService
  ├── OverdueDaysService
  ├── LeaderChecklistService
  ├── StatusChangeService
  ├── RecoveryDaysService
  └── MaterialReportService

Utilities/
  └── resources/js/utils/filterParams.ts   (buildFilterParams — shared by all self-fetching components)
```

---

## Routes

### Web Routes (Inertia Pages)

| Method | Path | Controller | Page |
|--------|------|------------|------|
| `GET` | `/warehouse-monitoring` | `WarehouseMonitoringController@index` | Dashboard |
| `GET` | `/warehouse-monitoring/overdue-days` | `OverdueDaysController@index` | Overdue Days |
| `GET` | `/warehouse-monitoring/leader-checklist` | `LeaderChecklistController@index` | Leader Checklist |
| `GET` | `/warehouse-monitoring/leaderboard` | `LeaderboardController@index` | Leaderboard |
| `GET` | `/warehouse-monitoring/recovery-days` | `RecoveryDaysController@index` | Recovery Days |
| `GET` | `/warehouse-monitoring/status-change` | `StatusChangeController@index` | Status Change |

### API Routes (JSON)

| Method | Path | Controller | Description |
|--------|------|------------|-------------|
| `GET` | `/warehouse-monitoring/api/dashboard` | `WarehouseMonitoringController@dashboardApi` | All charts/stats data at once |
| `GET` | `/warehouse-monitoring/api/problematic` | `ProblematicMaterialsController@index` | Problematic materials with severity |
| `PATCH` | `/warehouse-monitoring/api/problematic/{id}` | `ProblematicMaterialsController@update` | Update `estimated_gr` |
| `GET` | `/warehouse-monitoring/api/consumption-averages` | `ProblematicMaterialsController@getConsumptionAverages` | Raw consumption data from external API |
| `GET` | `/warehouse-monitoring/api/caution` | `LeaderboardController@cautionApi` | Caution leaderboard |
| `GET` | `/warehouse-monitoring/api/shortage` | `LeaderboardController@shortageApi` | Shortage leaderboard |
| `GET` | `/warehouse-monitoring/api/recovery-days` | `RecoveryDaysController@recoveryApi` | Recovery days data |
| `GET` | `/warehouse-monitoring/api/status-change-api` | `StatusChangeController@statusChangeApi` | Status change data |

### Query Parameters (Dashboard API)

| Parameter | Values | Description |
|-----------|--------|-------------|
| `usage` | `DAILY`, `WEEKLY`, `MONTHLY` | Filter by material usage frequency |
| `location` | `SUNTER_1`, `SUNTER_2` | Filter by warehouse location |
| `month` | `YYYY-MM` | Filter by specific month |
| `gentani` | `GENTAN-I`, `NON_GENTAN-I` | Filter by gentani classification |
| `page` | integer | For paginated endpoints |
| `per_page` | integer | Items per page (default: 10) |

Omitting a parameter (or passing `all`) returns data for all values of that dimension.

---

## Dashboard (index.vue)

### Layout (3-slide carousel)

The dashboard is a horizontal carousel with three slides. Tab pills + arrow buttons + dot indicators (mobile only) control navigation. Autoplay advances every 15s; pauses on hover. Mobile users can also swipe (50px threshold).

```
┌──────────────────────────────────────────────────────────────────────┐
│  Header: "Warehouse Performance Monitoring"                           │
│  [Filters] (mobile only) → Location pills | Month input | Gentani | Reset │
│  Filter bar is always visible on lg+; toggle-only on mobile.          │
├──────────────────────────────────────────────────────────────────────┤
│  Carousel Nav: [Materials Overview] [Analytics] [Leaderboards]        │
│                                                ◀ ●●● ▶                │
├──────────────────────────────────────────────────────────────────────┤
│  Slide 1 — Materials Overview                                         │
│  ┌──────────────────────────────────────┬─────────────────────────┐   │
│  │ Problematic Materials table (live)   │ SystemRecommendation    │   │
│  │  • Drag-to-pan horizontal scroll     │ StatusChange (compact)  │   │
│  │  • Inline estimated_gr (flatpickr)   │                         │   │
│  │  • Row click → Detail Modal          │                         │   │
│  │  • Est. GR column hidden < sm        │                         │   │
│  └──────────────────────────────────────┴─────────────────────────┘   │
├──────────────────────────────────────────────────────────────────────┤
│  Slide 2 — Analytics & Trends                                         │
│  ┌──────────────────────┬───────────────────────────────────────────┐ │
│  │ RecoveryTrendChart   │ DistributionDonutChart                    │ │
│  │ (lg:col-span-7)      │ FastestToCriticalChart                    │ │
│  │                      │ (lg:col-span-5)                           │ │
│  └──────────────────────┴───────────────────────────────────────────┘ │
├──────────────────────────────────────────────────────────────────────┤
│  Slide 3 — Leaderboards                                               │
│  ┌──────────────────────────┬──────────────────────────────────────┐  │
│  │ ShortageOverdueLeaderboard│ CautionOverdueLeaderboard           │  │
│  └──────────────────────────┴──────────────────────────────────────┘  │
│  MaterialInventoryOverview (dummy; filters prop wired)                │
└──────────────────────────────────────────────────────────────────────┘
```

### Carousel behavior

- **Tab pills** always visible (top-left of nav row); always show all 3 slide labels.
- **Arrows** (◀ / ▶) always visible (top-right of nav row); use Lucide `ChevronLeft` / `ChevronRight`. `aria-label` on each.
- **Dot indicators** are `lg:hidden` — they're redundant when tab pills are visible on desktop.
- **Autoplay** runs `setInterval(nextSlide, 15000)`. Pauses on `mouseenter`, resumes on `mouseleave`. Resets on manual navigation.
- **Touch swipe**: `@touchstart.passive` records `clientX`; `@touchend.passive` checks delta. >50px → next/prev + reset autoplay.

### Global Filter State

All filter state lives in a single `reactive` object in `index.vue`:

```ts
const globalFilter = reactive({
    usage:   'all' as 'all' | 'DAILY' | 'WEEKLY' | 'MONTHLY',
    location: 'all' as 'all' | 'SUNTER_1' | 'SUNTER_2',
    month:   null  as string | null,   // 'YYYY-MM' or null
    gentani: 'all' as 'all' | 'GENTAN-I' | 'NON_GENTAN-I',
})
```

A single deep watcher triggers a full refresh whenever any dimension changes:

```ts
watch(globalFilter, () => {
    fetchDashboardData()
    fetchProblematicMaterials(1)
}, { deep: true })
```

### Data Fetching Strategy

```
onMounted()
  ├── fetchDashboardData()        → GET /api/dashboard?{globalFilter}
  └── fetchProblematicMaterials() → GET /api/problematic?{globalFilter}

Self-fetching components watch their own `filters` prop:
  ├── CautionOverdueLeaderboard   → GET /api/caution?{filters}
  ├── ShortageOverdueLeaderboard  → GET /api/shortage?{filters}
  ├── StatusChangeContent         → GET /api/status-change-api?{filters}
  └── RecoveryDaysReportContent   → GET /api/recovery-days?{filters}
```

### Dashboard Data Map

`dashboardData` ref is populated from `/api/dashboard`. Its fields feed the following components:

| `dashboardData` field | Component | Notes |
|-----------------------|-----------|-------|
| `dashboardData.barChart` | `DistributionDonutChart` | `summary.OK/CAUTION/SHORTAGE` for donut counts |
| `dashboardData.barChart` | `OverdueTrendChart` | `statusBarChart[]` for bar chart |
| `dashboardData.recovery.trendData` | `RecoveryTrendChart` | `[{month, average_recovery_days}]` |
| `dashboardData.shortage.leaderboard` | `FastestToCriticalChart` | Top 5 by `days` |
| `dashboardData.shortage` | `SystemRecommendationPanel` | Full stats + leaderboard array |
| `dashboardData.caution` | `SystemRecommendationPanel` | Full stats + leaderboard array |

### Dashboard API Response Shape

```json
{
  "success": true,
  "data": {
    "caution": {
      "statistics": { "type": "CAUTION", "total": 32, "average_days": 8.5, "max_days": 22, "min_days": 1 },
      "leaderboard": [{ "material_number": "...", "description": "...", "pic_name": "...", "days": 22, "current_stock": 5, "usage": "DAILY" }],
      "pagination": { "current_page": 1, "last_page": 4, "per_page": 10, "total": 32 }
    },
    "shortage": { "...same shape..." },
    "recovery": {
      "statistics": { "total_recovered": 15, "average_recovery_days": 4.2 },
      "data": [...],
      "pagination": {...},
      "trendData": [{ "month": 1, "average_recovery_days": 3.8, "total_recovered": 5 }]
    },
    "statusChange": { "statistics": {...}, "data": [...], "pagination": {...} },
    "barChart": {
      "statusBarChart": [
        { "status": "SHORTAGE", "count": 15 },
        { "status": "CAUTION",  "count": 32 },
        { "status": "OK",       "count": 248 }
      ],
      "summary": { "OK": 248, "CAUTION": 32, "SHORTAGE": 15, "OVERFLOW": 3, "UNCHECKED": 12 }
    }
  },
  "filters": { "usage": "DAILY" },
  "timestamp": "2026-02-25T10:30:00"
}
```

### Section Status

| Section | Slide | Data Source | Live? |
|---------|-------|-------------|-------|
| Problematic Materials table | 1 | `/api/problematic` | Yes |
| SystemRecommendationPanel | 1 | `dashboardData.shortage` + `dashboardData.caution` | Yes |
| StatusChangeContent (compact) | 1 | `/api/status-change-api` (self-fetch) | Yes |
| RecoveryTrendChart | 2 | `dashboardData.recovery.trendData` | Yes |
| DistributionDonutChart | 2 | `dashboardData.barChart.summary` | Yes |
| FastestToCriticalChart | 2 | `dashboardData.shortage.leaderboard` | Yes |
| ShortageOverdueLeaderboard | 3 | `/api/shortage` (self-fetch) | Yes |
| CautionOverdueLeaderboard | 3 | `/api/caution` (self-fetch) | Yes |
| MaterialInventoryOverview | 3 | Dummy data | No (filters prop ready) |
| OverdueTrendChart | — | — | Component exists but is not rendered on the dashboard |
| RecoveryDaysReportContent | — | — | Component exists but is not rendered on the dashboard |

---

## Self-Fetching Component Pattern

Components that manage their own data use a `filters` prop sentinel:

```js
const props = defineProps({
    filters: { type: Object, default: null },  // null = prop-driven (legacy), object = self-fetch
    // ...other initial* props for legacy prop-driven mode
})

const isSelfFetch = computed(() => props.filters !== null)

// Switch between local (self-fetch) and initial (prop-driven) data
const currentLeaderboard = computed(() =>
    isSelfFetch.value ? localLeaderboard.value : (props.initialLeaderboard ?? [])
)

async function fetchData(page = 1) {
    if (!isSelfFetch.value) return
    const params = buildFilterParams({ ...props.filters, page, per_page: 5 })
    const res = await fetch(`/warehouse-monitoring/api/caution?${params}`)
    const json = await res.json()
    // All endpoints wrap payload: { success, data: { data, statistics, pagination } }
    const payload = json.data ?? json
    localLeaderboard.value = payload.data ?? payload.leaderboard ?? []
    localStatistics.value  = payload.statistics ?? { total: 0, average_days: 0 }
    localPagination.value  = payload.pagination  ?? { current_page: 1, last_page: 1, ... }
}

watch(() => props.filters, (f) => { if (f !== null) fetchData(1) }, { deep: true, immediate: true })
```

**API envelope**: every endpoint responds with `{ success, data: {...} }`. Always unwrap with `json.data ?? json` before reading fields.

**Legacy mode**: pass `initialLeaderboard`, `initialStatistics`, `initialPagination` props and omit `filters`. Used by standalone full-page views (`Leaderboard.vue`, etc.) that drive pagination via Inertia router.

---

## Filter Utility

**File**: `resources/js/utils/filterParams.ts`

```ts
export function buildFilterParams(filters: Record<string, unknown>): URLSearchParams {
    const p = new URLSearchParams()
    for (const [k, v] of Object.entries(filters)) {
        if (v !== null && v !== undefined && v !== '' && v !== 'all') p.set(k, String(v))
    }
    return p
}
```

Skips null, empty string, and `'all'` values so the API receives only active filters.

---

## Chart Components

All chart components are **presentational** — they receive processed data as props and own their Chart.js lifecycle (`onMounted`, `onBeforeUnmount`, `watch`).

### OverdueTrendChart

**File**: `resources/js/Components/OverdueTrendChart.vue`
**Title**: "Status Count Overview"
**Chart type**: Vertical bar
**Data**: `data.statusBarChart` — one bar per status with status-specific colors
**Props**: `data?: { statusBarChart?: {status, count}[]; summary?: Record<string,number> } | null`, `filters?`

Status colors: `SHORTAGE=#ef4444`, `CAUTION=#f59e0b`, `OK=#10b981`, `OVERFLOW=#3b82f6`, `UNCHECKED=#9ca3af`

### RecoveryTrendChart

**File**: `resources/js/Components/RecoveryTrendChart.vue`
**Title**: "Avg Recovery Days"
**Chart type**: Line (smooth)
**Data**: `data` — array of `{ month: 1-12, average_recovery_days: number, total_recovered: number }`
**Props**: `data?: TrendItem[] | null`, `filters?`

Converts numeric `month` (1-12) to abbreviated month names (Jan, Feb, ...).

### DistributionDonutChart

**File**: `resources/js/Components/DistributionDonutChart.vue`
**Title**: "Material Status Distribution"
**Chart type**: Doughnut
**Data**: `data.summary.OK/CAUTION/SHORTAGE`
**Props**: `data?: { summary?: Record<string,number>; statusBarChart?: {status,count}[] } | null`, `filters?`

Shows numeric counters (OK / Caution / Shortage) alongside the donut.

### FastestToCriticalChart

**File**: `resources/js/Components/FastestToCriticalChart.vue`
**Title**: "Top 5 Fastest to Critical"
**Chart type**: Horizontal bar
**Data**: `data` — top 5 leaderboard items; reads `description` (truncated to 20 chars) and `days`
**Props**: `data?: LeaderboardItem[] | null`, `filters?`

### Modal Chart

The detail modal in `index.vue` renders a per-material line chart (dummy trend data) using Chart.js directly on `modalChartRef`.

---

## SystemRecommendationPanel

**File**: `resources/js/Components/SystemRecommendationPanel.vue`

Derives two action blocks from live shortage and caution data:

| Block | Color | Content |
|-------|-------|---------|
| Immediate Action | Red | Top shortage material + PIC + days + stock + total count |
| Urgent (Next 2 shifts) | Amber | Caution count + average days + worst-case days |

**Props**:
- `shortage?: { leaderboard?: LeaderboardItem[]; statistics?: LBStats }`
- `caution?: { leaderboard?: LeaderboardItem[]; statistics?: LBStats }`
- `updatedAt?: string`

**Emits**: `view-material` with the top shortage `LeaderboardItem`

**"View Material Analysis" button**: disabled when no shortage materials; clicking emits `view-material` and opens the detail modal.

---

## Modules

### 1. Problematic Materials

The top table on Slide 1 of the dashboard. SHORTAGE/CAUTION materials sorted by severity, enriched (best-effort) with consumption data from the external API.

**File**: `app/Http/Controllers/ProblematicMaterialsController.php`
**Service**: `app/Services/ProblematicMaterialsService.php`

#### Sync Semantics (important)

The `sync()` method on every page load (cached 5 min) rebuilds the `problematic_materials` table by joining `materials` with the latest `daily_inputs` row per material:

```sql
LEFT JOIN (
    SELECT di1.material_id, di1.status, di1.daily_stock, di1.date
    FROM daily_inputs di1
    INNER JOIN (
        SELECT material_id, MAX(date) AS max_date
        FROM daily_inputs
        GROUP BY material_id
    ) di2 ON di2.material_id = di1.material_id AND di2.max_date = di1.date
) as latest ON materials.id = latest.material_id
WHERE latest.status IN ('SHORTAGE', 'CAUTION')
```

**"Latest" is determined by `MAX(date)`, NOT `MAX(id)`.** This matters because users occasionally **backfill** past dates (e.g., enter a daily_input today for `date = 2026-01-15`). The unique constraint on `(material_id, date)` guarantees one row per (material_id, date), so `MAX(date)` is unambiguous and reflects the actual most-recent business date — not the most-recently-submitted row. Using `MAX(id)` would let a backfill insert silently shadow the real recent row.

**Consumption enrichment is best-effort.** If `fetchConsumptionAveragesAll()` returns empty (API unreachable, allowlist miss, etc.), sync still runs from `daily_inputs` data. Materials without consumption data get `coverage_shifts = null`, `daily_avg = null`, `shift_avg = null`. `resolveSeverity()` falls back to `'High'` for null-coverage SHORTAGE and to streak-based severity for CAUTION.

**No filter on consumption presence.** All SHORTAGE/CAUTION materials appear in the PM table regardless of whether they're tracked by the consumption API. This is a change from earlier behavior where industrial tools / spare parts (not in the API's ~1,233 consumables) were silently excluded.

#### API Endpoints

- `GET /warehouse-monitoring/api/problematic` — paginated list with severity
- `PATCH /warehouse-monitoring/api/problematic/{id}` — update `estimated_gr` field only

#### Query Parameters

| Parameter | Values | Default |
|-----------|--------|---------|
| `page` | integer | 1 |
| `per_page` | integer | 10 |
| `status` | `SHORTAGE`, `CAUTION` | all |
| `usage` | `DAILY`, `WEEKLY`, `MONTHLY` | all |
| `location` | `SUNTER_1`, `SUNTER_2` | all |
| `gentani` | `GENTAN-I`, `NON_GENTAN-I` | all |

#### Response Shape

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 42,
        "material_number": "B021-100000",
        "description": "PLASTIC WRAP 50 CM X 17 MIC",
        "pic_name": "Budi",
        "status": "SHORTAGE",
        "severity": "Line-Stop Risk",
        "coverage_shifts": 0.5,
        "daily_avg": 2.5,
        "shift_avg": 0.83,
        "current_stock": 10,
        "streak_days": 4,
        "location": "SUNTER_1",
        "usage": "DAILY",
        "estimated_gr": "2026-03-01",
        "last_updated": "2026-02-20"
      }
    ],
    "pagination": { "current_page": 1, "last_page": 3, "per_page": 10, "total": 28 }
  }
}
```

#### Severity Logic (`resolveSeverity()`)

| Status | Condition | Severity |
|--------|-----------|----------|
| SHORTAGE | `coverage_shifts < 1` | Line-Stop Risk |
| SHORTAGE | `coverage_shifts` 1–3 | High |
| SHORTAGE | `coverage_shifts >= 3` or no data | Medium / High |
| CAUTION | `streak_days > 7` | High |
| CAUTION | `streak_days > 3` | Medium |
| CAUTION | `streak_days ≤ 3` | Low |

#### `estimated_gr` Editing

Inline date input per row. On blur/change emits a `PATCH /api/problematic/{id}` with `{ estimated_gr: 'YYYY-MM-DD' }`. The column is excluded from upsert to preserve user-entered values.

#### Detail Modal

Clicking a row opens a modal showing:
- Coverage shifts (or "No data" if null), streak days, current stock
- Action recommendation (Escalate / Monitor)
- Material info: PIC, Location, Usage, Shift avg
- Per-material consumption chart (dummy trend)

#### Loading States

- **Skeleton**: 5 animated placeholder rows while fetching
- **Empty state**: message if API returns empty
- **Error**: banner at top of page if fetch fails

---

### 2. External Consumption API

**Endpoint**: `http://103.93.52.79:2000/api/transaction/analytics/consumption-average`
**Auth**: `X-API-Key: WAREHOUSE_DASHBOARD_KEY_2026`
**Config**: `config('services.consumption_api.url')` / `config('services.consumption_api.key')`

Only consumable materials are tracked by this API (~1,233 items). Industrial tools / spare parts have no consumption data; they still appear in the PM table but with `coverage_shifts`, `daily_avg`, `shift_avg` set to `null`.

Bulk fetch parameters: `location_id=1`, `months=3`, `page`, `limit=200`. The bulk fetch paginates until `page >= totalPages` (or page count drops below `limit`).

Join key: `material_id` (API) ↔ `material_number` (materials table). Both sides normalize via `strtoupper(trim(...))`.

**Caching**:
- `consumption_averages_all` — 1 hour. Only populated when the response is non-empty (so an API hiccup never poisons the cache with `[]`).
- `problematic_materials_synced` — 5 min. Prevents redundant syncs within the window.

**Best-effort enrichment**: if the API returns an empty / errored result, sync proceeds without consumption data. Coverage / avg fields go `null` for that cycle, and the next sync retries cleanly.

---

### 3. Caution/Shortage Leaderboard

See [`leaderboard.md`](./leaderboard.md) for full documentation.

**Summary**: Materials ranked by consecutive distinct days in CAUTION or SHORTAGE. Components support both prop-driven mode (full-page `Leaderboard.vue`) and self-fetch mode (dashboard, pass `filters` prop).

---

### 4. Recovery Days

See [`recovery_days.md`](./recovery_days.md).

**Dashboard widget**: `RecoveryDaysReportContent` with `size="compact"` and `:filters="globalFilter"` — self-fetches from `/api/recovery-days`. The `trendData` from the dashboard API feeds `RecoveryTrendChart`.

---

### 5. Status Change Tracker

See [`status_change.md`](./status_change.md).

**Dashboard widget**: `StatusChangeContent` with `size="compact"` and `:filters="globalFilter"` — self-fetches from `/api/status-change-api`.

---

### 6. Overdue Days

See [`overdue_days.md`](./overdue_days.md).

**Dashboard**: No compact widget. "View All" link from the Problematic Materials section goes to `/warehouse-monitoring/overdue-days`.

---

### 7. Leader Checklist

See [`leader_checklist.md`](./leader_checklist.md).

**Dashboard**: No compact widget. Accessible from the top navigation.

---

## Backend Services

| Service | Key Methods |
|---------|-------------|
| `ProblematicMaterialsService` | `sync()`, `getProblematicMaterials($page, $perPage, $status)`, `fetchConsumptionAveragesAll()` |
| `LeaderboardService` | `getCautionLeaderboard($filters, $perPage, $page)`, `getShortageLeaderboard(...)`, `getAllLeaderboards($filters, $perPage)` |
| `OverdueDaysService` | `getOverdueReports($filters, $perPage, $page)` |
| `LeaderChecklistService` | `getComplianceReport($filters, $perPage, $page)` |
| `StatusChangeService` | `getStatusChangeReport($filters, $perPage, $page)` |
| `RecoveryDaysService` | `getRecoveryReport($filters, $perPage, $page)`, `getRecoveryTrend($year)` |
| `MaterialReportService` | `getStatusBarChart($filters)` |

### Form Requests (Full Pages)

Each full-page controller uses a dedicated Form Request:
- `LeaderboardRequest` — provides `getFilters()`, `getPaginationParams()`
- `OverdueDaysRequest`
- `LeaderChecklistRequest`
- `StatusChangeRequest`
- `RecoveryDaysRequest`

---

## Data Flow

### Dashboard: On Mount

```
onMounted()
  ├── fetchDashboardData()
  │     └── GET /api/dashboard?{globalFilter}
  │           └── WarehouseMonitoringController@dashboardApi
  │                 ├── LeaderboardService::getAllLeaderboards  → shortage, caution
  │                 ├── RecoveryDaysService::getRecoveryReport  → recovery.data + pagination
  │                 ├── RecoveryDaysService::getRecoveryTrend   → recovery.trendData
  │                 ├── StatusChangeService::getStatusChangeReport → statusChange
  │                 └── MaterialReportService::getStatusBarChart   → barChart
  │
  └── fetchProblematicMaterials(1)
        └── GET /api/problematic?{globalFilter}&page=1
              └── ProblematicMaterialsController@index
                    └── ProblematicMaterialsService@sync() → upsert → paginate
```

### Dashboard: On Filter Change

```
globalFilter changes (any field)
  └── watch(globalFilter, ..., { deep: true })
        ├── fetchDashboardData()       — refreshes all charts & panel
        └── fetchProblematicMaterials(1)  — resets PM table to page 1

Self-fetching components (CautionLeaderboard, ShortageLeaderboard, StatusChange, RecoveryDays):
  └── watch(() => props.filters, ...)  — each component re-fetches its own endpoint
```

### Full Page Navigation

```
User changes filter or page
  └── router.get(route('warehouse-monitoring.xxx'), params, {
        preserveState: true, preserveScroll: true, only: ['relevantProps']
      })
        └── Controller@index → Service → Cache::remember() → DB
              └── Inertia::render() → partial prop update → Vue reactivity
```

---

## Known Limitations

| Limitation | Detail |
|------------|--------|
| No overdue trend history | `OverdueDaysService` tracks current status only; no historical series. `OverdueTrendChart` was repurposed as a "Status Count Overview" bar chart but is not currently rendered on the dashboard. |
| MaterialInventoryOverview dummy | The bottom inventory table on Slide 3 uses static placeholder data. The `filters` prop is wired but the fetch is not yet implemented. |
| Consumption API coverage | Only consumable materials are tracked (~1,233 items). Industrial tools / spare parts appear in the PM table but with `null` `coverage_shifts` / `daily_avg` / `shift_avg`. |
| External API dependency | `ProblematicMaterialsService` depends on `http://103.93.52.79:2000`. If unreachable, sync still runs from `daily_inputs` (since the empty-API hard-skip was removed); enrichment fields just go `null` until the API recovers. |
| `streak_days` is "days since latest input", not "consecutive days in critical status" | The SQL formula computes business days between `CURDATE()` and `latest.date`. A material that's been SHORTAGE for 5 consecutive days but had its latest daily_input *yesterday* will show `streak_days = 1`. `resolveSeverity()` for CAUTION is keyed on this value, so severity may under-report. |
| Hardcoded `location_id=1` in bulk consumption fetch | `fetchConsumptionAveragesAll()` always passes `location_id=1`. If the API uses location IDs to scope its response and Sunter 2 materials sit under a different ID, those materials will be enriched as if absent from the API. |

---

## Configuration

### External API (`config/services.php`)

```php
'consumption_api' => [
    'url' => env('CONSUMPTION_API_URL'),
    'key' => env('CONSUMPTION_API_KEY'),
],
```

### Required `.env` entries

```
CONSUMPTION_API_URL=http://103.93.52.79:2000/api/transaction/analytics/consumption-average
CONSUMPTION_API_KEY=WAREHOUSE_DASHBOARD_KEY_2026
```
