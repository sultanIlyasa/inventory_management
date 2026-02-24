# Recovery Days

## Page

**File**: `resources/js/Pages/WarehouseMonitoring/RecoveryDays.vue`
**Route**: `GET /warehouse-monitoring/recovery-days`
**Controller**: `RecoveryDaysController@index`

## Purpose

Tracks materials that recovered from a problem status (SHORTAGE, CAUTION, OVERFLOW) back to OK, and measures how many days recovery took. Includes a monthly trend chart to visualize recovery performance over time.

## Layout

```
┌────────────────────────────────────────────────────────┐
│  Filters: Date, Month, Usage, Location, Gentani         │
│  [Apply] [Clear]                                        │
├────────────────────────────────────────────────────────┤
│  RecoveryDaysReportContent (size="full")                │
│  - Summary stats                                        │
│  - Full table with sortable Recovery Days column        │
│  - Trend line chart                                     │
│  - Pagination                                           │
└────────────────────────────────────────────────────────┘
```

## Props (from Inertia)

| Prop | Type | Description |
|------|------|-------------|
| `recoveryData` | Object | `{ data: [], pagination: {} }` |
| `statistics` | Object | `{ total_recovered, average_recovery_days, fastest_recovery, slowest_recovery }` |
| `trendData` | Array | Monthly trend: `[{ month, total_recovered, average_recovery_days }]` |
| `filters` | Object | Current filter values |

## State

| Ref | Type | Description |
|-----|------|-------------|
| `isLoading` | Boolean | Loading state |
| `recoveryData` | Object | Local copy (updated on Inertia success) |
| `statistics` | Object | Local copy |
| `trendData` | Array | Local copy |
| `pagination` | Object | `{ current_page, last_page, per_page, total }` |
| `localFilters` | Object | `{ month, usage, location, gentani }` |
| `showMobileFilters` | Boolean | Toggle filter panel |

## Filter & Pagination

Uses Inertia `router.get()` with partial reload. Debounced at 500ms.

```js
// Filter change → resets to page 1
router.get(route('warehouse-monitoring.recovery-days'), {
    ...localFilters.value, page: 1
}, { preserveState: true, only: ['recoveryData', 'statistics', 'trendData'] })

// Page change
router.get(route('warehouse-monitoring.recovery-days'), {
    ...localFilters.value, page
}, { preserveState: true, only: ['recoveryData'] })
```

### Prop Watchers
The page watches `props.recoveryData`, `props.statistics`, and `props.trendData` to sync local refs when Inertia updates props from the server.

---

## RecoveryDaysReportContent Component

**File**: `resources/js/Components/RecoveryDaysReportContent.vue`

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `recoveryData` | Array | `[]` | Material recovery entries |
| `statistics` | Object | `{ total_recovered: 0, average_recovery_days: 0, fastest_recovery: 0, slowest_recovery: 0 }` | Aggregate stats |
| `trendData` | Array | `[]` | Monthly trend: `[{ month, total_recovered, average_recovery_days }]` |
| `pagination` | Object | `{ current_page: 1, last_page: 1, per_page: 10, total: 0 }` | Pagination |
| `size` | String | `"full"` | `"mini"`, `"compact"`, or `"full"` |
| `limit` | Number | null | Override default item limit |
| `showChart` | Boolean | `true` | Show trend chart |
| `showViewAll` | Boolean | `true` | Show link to full page |
| `viewAllUrl` | String | `/warehouse-monitoring/recovery-days` | Link target |
| `hideRefresh` | Boolean | `false` | Hide refresh button |

### Events

| Event | Payload | Description |
|-------|---------|-------------|
| `refresh` | none | Request parent to re-fetch |
| `page-change` | page number | Request specific page |

### Internal State

| Ref | Type | Description |
|-----|------|-------------|
| `sortOrder` | String | `"desc"` or `"asc"` — client-side sort on recovery_days |
| `chartCanvas` | Ref | Canvas element for full-size chart |
| `compactChartCanvas` | Ref | Canvas element for compact chart |

### Display Modes

**Compact/Mini** — Scrollable list with material name, recovery days (color-coded), and optional compact trend chart.

**Full** — Summary stats + table + trend chart:

#### Summary Stats (full mode)
| Stat | Description |
|------|-------------|
| Total Recovered | Number of materials that recovered |
| Avg Days | Average recovery time |
| Fastest | Fewest days to recover |
| Slowest | Most days to recover |

#### Full Table Columns
| Column | Description |
|--------|-------------|
| # | Row number |
| Material | Material number |
| Description | Material name |
| PIC | Person in charge |
| Recovery Days | Days from problem to OK (sortable, click header to toggle) |
| Problem Date | When the problem status started |
| Recovery Date | When status returned to OK |

### Recovery Days Color

| Condition | Color | Meaning |
|-----------|-------|---------|
| `<= 3 days` | Green | Fast recovery |
| `<= 7 days` | Yellow | Moderate |
| `> 7 days` | Red | Slow recovery |

### Trend Line Chart

Uses Chart.js (auto import) to render a line chart:
- **X-axis**: Month abbreviations (Jan, Feb, ..., Dec)
- **Y-axis**: Average recovery days
- **Style**: Blue line with area fill, tension 0.4, point radius 3
- Only months with `total_recovered > 0` are plotted
- Chart is destroyed and re-rendered when `trendData`, `size`, or `showChart` changes

### Client-Side Sorting

Recovery days column is sortable by clicking the header:
```js
toggleSort() → sortOrder toggles between 'desc' and 'asc'
sortedRecoveryData → sorts [...recoveryData] by recovery_days
```

This is purely client-side and does not trigger an API call.

---

## Backend: RecoveryDaysController

**File**: `app/Http/Controllers/RecoveryDaysController.php`
**Service**: `RecoveryDaysService`
**Request**: `RecoveryDaysRequest`

### Endpoints

| Method | Handler | Returns |
|--------|---------|---------|
| `index(RecoveryDaysRequest)` | Inertia page | `recoveryData`, `statistics`, `trendData`, `filters` |
| `recoveryApi(RecoveryDaysRequest)` | JSON endpoint | `{ success, recoveryData, statistics, trendData, pagination }` |
| `trendApi(RecoveryDaysRequest)` | JSON trend only | `{ success, data: trendData }` |

### Caching
```
Recovery data: "recovery_days_{filterHash}_page_{page}_per_{perPage}" → 5 min
Trend data:    "recovery_trend_{year}" → 5 min
```

Trend data is cached separately by year since it doesn't change with page/filter combinations.

### Default Filters
```php
['date' => '', 'month' => '', 'usage' => '', 'location' => '', 'gentani' => '']
```

---

## Service: RecoveryDaysService

**File**: `app/Services/RecoveryDaysService.php`

Uses Laravel Eloquent Query Builder with subquery joins.

### getRecoveryReport($filters, $perPage, $page)

Main entry point. Returns `{ data, pagination, statistics }`.

### Core Logic: How Recovery is Detected

The service finds materials that transitioned from a problem status (CAUTION/SHORTAGE) back to OK:

**Step 1: Find latest OK record per material**
```sql
-- Subquery: latestOk
SELECT material_id, daily_stock, MAX(date) as recovery_date
FROM daily_inputs
WHERE status = 'OK'
GROUP BY material_id, daily_stock
```

**Step 2: Find the most recent problem BEFORE the recovery**
```sql
LEFT JOIN daily_inputs as problem
  ON problem.material_id = materials.id
  AND problem.status IN ('CAUTION', 'SHORTAGE')
  AND problem.date <= ok.recovery_date
```

**Step 3: Calculate recovery days**
```sql
SELECT
    materials.*,
    ok.recovery_date,
    MAX(problem.date) as last_problem_date,
    DATEDIFF(ok.recovery_date, MAX(problem.date)) + 1 as recovery_days
FROM materials
JOIN ok ON materials.id = ok.material_id
LEFT JOIN problem ...
GROUP BY materials.id, ok.recovery_date, ok.daily_stock
HAVING last_problem_date IS NOT NULL AND recovery_days > 0
```

The `+1` includes both the problem day and the recovery day in the count.

**Filters applied**:
- Material filters (`usage`, `location`, `gentani`) via `Materials::applyFilters()`
- `month` filter → `whereBetween(ok.recovery_date, [startOfMonth, endOfMonth])`
- `year` filter (for trend) → applied within `applyFilters`

### Statistics

Calculated from the full base query (not just the current page):
```php
[
    'total_recovered'       => 45,      // COUNT(*)
    'average_recovery_days' => 5.3,     // AVG(recovery_days), rounded to 1 decimal
    'fastest_recovery'      => 1,       // MIN(recovery_days)
    'slowest_recovery'      => 22,      // MAX(recovery_days)
]
```

### getRecoveryTrend($year)

Generates monthly trend data for the trend line chart.

```sql
SELECT
    MONTH(recovery.recovery_date) as month,
    AVG(recovery.recovery_days) as average_recovery_days,
    COUNT(*) as total_recovered
FROM (base recovery query filtered by year) as recovery
GROUP BY MONTH(recovery.recovery_date)
ORDER BY month
```

**Return shape**:
```php
[
    ['month' => 1, 'average_recovery_days' => 4.2, 'total_recovered' => 12],
    ['month' => 2, 'average_recovery_days' => 3.8, 'total_recovered' => 15],
    ...
]
```

Only months with recoveries appear. The frontend maps `month` integers to abbreviations (Jan, Feb, etc.).

### Data Item Shape
```php
[
    'material_number'  => '12345678',
    'description'      => 'BOLT M10',
    'pic'              => 'ADE N',
    'instock'          => 50,
    'current_status'   => 'OK',
    'recovery_days'    => 5,
    'last_problem_date'=> '2026-02-10',
    'recovery_date'    => '2026-02-14',
    'usage'            => 'DAILY',
    'location'         => 'SUNTER_1',
    'gentani'          => 'GENTAN-I',
]
```
