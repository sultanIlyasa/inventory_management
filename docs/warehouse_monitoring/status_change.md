# Status Change Tracker

## Page

**File**: `resources/js/Pages/WarehouseMonitoring/StatusChange.vue`
**Route**: `GET /warehouse-monitoring/status-change`
**Controller**: `StatusChangeController@index`

## Purpose

Tracks materials with frequent status transitions (e.g., OK -> Caution -> OK -> Shortage). Materials with high change frequency indicate instability and may need root cause analysis. Includes a bar chart showing overall material distribution by status.

## Layout

```
┌────────────────────────────────────────────────────────┐
│  Filters: Date, Month, Usage, Location, Gentani         │
│  [Apply] [Clear]                                        │
├────────────────────────────────────────────────────────┤
│  StatusChangeContent (size="full")                      │
│  - Summary stats                                        │
│  - Full table with pagination                           │
├────────────────────────────────────────────────────────┤
│  StatusBarChart (size="full")                           │
│  - Bar chart of material count per status               │
└────────────────────────────────────────────────────────┘
```

## Props (from Inertia)

| Prop | Type | Description |
|------|------|-------------|
| `statusChangeData` | Object | `{ data: [], pagination: {} }` |
| `statistics` | Object | Transition counts |
| `filters` | Object | Current filter values |

## State

| Ref | Type | Description |
|-----|------|-------------|
| `isLoading` | Boolean | Loading state |
| `statusChangeData` | Object | Local copy of props (for partial updates) |
| `statistics` | Object | Local copy of stats |
| `pagination` | Object | `{ current_page, last_page, per_page, total }` |
| `localFilters` | Object | `{ month, usage, location, gentani }` |
| `showMobileFilters` | Boolean | Toggle filter panel |

## Filter & Pagination

Uses Inertia `router.get()` with partial reload. Debounced at 500ms for input changes.

```js
// Filter change
router.get(route('warehouse-monitoring.status-change'), {
    ...localFilters.value, page: 1
}, { preserveState: true, only: ['statusChangeData', 'statistics'] })

// Page change
router.get(route('warehouse-monitoring.status-change'), {
    ...localFilters.value, page
}, { preserveState: true, only: ['statusChangeData'] })
```

On success, local refs are updated from `page.props`.

---

## StatusChangeContent Component

**File**: `resources/js/Components/StatusChangeContent.vue`

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `initialStatusChangeData` | Array | `[]` | Material entries with change frequencies |
| `initialStatistics` | Object | `{}` | Aggregate transition counts |
| `initialPagination` | Object | `{ current_page: 1, last_page: 1, per_page: 10, total: 0 }` | Pagination |
| `size` | String | `"full"` | `"mini"`, `"compact"`, or `"full"` |
| `limit` | Number | null | Override default item limit |
| `showViewAll` | Boolean | `true` | Show link to full page |
| `viewAllUrl` | String | `"warehouse-monitoring/status-change"` | Link target |
| `hideRefresh` | Boolean | `false` | Hide refresh button |

### Events

| Event | Payload | Description |
|-------|---------|-------------|
| `refresh` | none | Request parent to re-fetch |
| `page-change` | page number | Request specific page |

### Display Modes

**Compact/Mini** — Ranked list showing material number, description, and caution/shortage frequency counts.

**Full** — Summary stats + full table:

#### Summary Stats (full mode)
| Stat | Description |
|------|-------------|
| To Caution | Total transitions to CAUTION |
| To Shortage | Total transitions to SHORTAGE |
| To Overflow | Total transitions to OVERFLOW |
| Recovered | Total recoveries back to OK |

#### Full Table Columns
| Column | Description |
|--------|-------------|
| # | Row number |
| Material | Material number |
| Description | Material name |
| OK -> Caution | Count of transitions from OK to CAUTION |
| OK -> Shortage | Count of transitions from OK to SHORTAGE |
| OK -> Overflow | Count of transitions from OK to OVERFLOW |
| Current Status | Current material status |
| Total Changes | Sum of all transitions |

### Pagination
5-page window with First/Prev/Next/Last. Blue active page indicator.

---

## StatusBarChart Component

**File**: `resources/js/Components/StatusBarChart.vue`

### Purpose
Renders a bar chart showing material count per status using Chart.js.

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `chartData` | Array | `[]` | Array of `{ status: string, count: number }` |

### Implementation
- Registers Chart.js modules manually: `BarController`, `BarElement`, `CategoryScale`, `LinearScale`, `Tooltip`, `Legend`
- Labels from `chartData[].status`, values from `chartData[].count`
- Responsive, no fixed aspect ratio
- Auto-destroys and re-renders on `chartData` change (deep watcher)
- Shows "No status data available." when data is empty

---

## Backend: StatusChangeController

**File**: `app/Http/Controllers/StatusChangeController.php`
**Service**: `StatusChangeService`
**Request**: `StatusChangeRequest`

### Endpoints

| Method | Handler | Returns |
|--------|---------|---------|
| `index(StatusChangeRequest)` | Inertia page | `statusChangeData`, `statistics`, `filters` |
| `statusChangeApi(StatusChangeRequest)` | JSON endpoint | `{ success, statusChangeData, statistics, pagination }` |

### Caching
```
Key: "status_change_{filterHash}_page_{page}_per_{perPage}"
TTL: 5 minutes
```

---

## Service: StatusChangeService

**File**: `app/Services/StatusChangeService.php`

Uses Laravel Query Builder with subqueries. More complex than other services due to window functions (`LAG()`).

### getStatusChangeReport($filters, $perPage, $page)

Main entry point. Returns `{ data, pagination, statistics }`.

### Query Architecture

The service builds a chain of subqueries:

```
statusHistorySubquery (raw daily_inputs with LAG() window function)
  → statusTransitionsSubquery (aggregated transition counts per material)
    → buildBaseQuery (joins materials + transitions + latest status)
      → getStatusChangeReport (pagination + statistics)
```

### Step 1: Status History Subquery

Uses SQL `LAG()` window function to get previous status for each record:
```sql
SELECT
    material_id,
    status,
    LAG(status) OVER (PARTITION BY material_id ORDER BY date, id) as prev_status
FROM daily_inputs
JOIN materials ON materials.id = daily_inputs.material_id
[WHERE date filters and material filters]
```

This gives each daily_input row its previous status, enabling transition detection.

### Step 2: Transition Counts Subquery

Aggregates the history into counts per material:
```sql
SELECT
    material_id,
    SUM(CASE WHEN prev_status='OK' AND status='CAUTION'  THEN 1 ELSE 0 END) as ok_to_caution,
    SUM(CASE WHEN prev_status='OK' AND status='SHORTAGE' THEN 1 ELSE 0 END) as ok_to_shortage,
    SUM(CASE WHEN prev_status='OK' AND status='OVERFLOW' THEN 1 ELSE 0 END) as ok_to_overflow,
    SUM(CASE WHEN prev_status='CAUTION'  AND status='OK' THEN 1 ELSE 0 END) as caution_to_ok,
    SUM(CASE WHEN prev_status='SHORTAGE' AND status='OK' THEN 1 ELSE 0 END) as shortage_to_ok,
    SUM(CASE WHEN prev_status='OK' AND status!='OK'      THEN 1 ELSE 0 END) as total_from_ok,
    SUM(CASE WHEN status='OK' AND prev_status IS NOT NULL AND prev_status!='OK' THEN 1 ELSE 0 END) as total_to_ok
FROM history
GROUP BY material_id
```

### Step 3: Base Query

Joins `materials` with transition counts and latest status:
- Only includes materials where `total_from_ok > 0 OR total_to_ok > 0` (at least one transition)
- Ordered by `total_from_ok DESC` (most unstable materials first)

### Step 4: Latest Status Subquery

```sql
SELECT material_id, status as current_status
FROM daily_inputs
WHERE id IN (SELECT MAX(id) FROM daily_inputs GROUP BY material_id [WHERE date <= :date])
```

### Statistics Calculation

Statistics are cached separately for 10 minutes:
```php
Cache key: "status_change_stats:{filterHash}"
TTL: 10 minutes
```

Aggregates across all materials (not just current page):
```php
[
    'total_materials'  => 45,
    'total_to_caution' => 120,
    'total_to_shortage'=> 85,
    'total_to_overflow'=> 30,
    'total_recovered'  => 95,
    'total_changes'    => 235,
]
```

### Filter Support

Material filters (`usage`, `location`, `gentani`, `pic_name`) support comma-separated values:
```php
// Single: "DAILY"
// Multiple: "DAILY,WEEKLY"  → whereIn(['DAILY', 'WEEKLY'])
```

Date/month filters apply to the history subquery:
- `month` → `whereBetween(date, [startOfMonth, endOfMonth])`
- `date` (without month) → `where(date, '<=', date)`

### Default Filters
```php
['date' => '', 'month' => '', 'usage' => '', 'location' => '', 'gentani' => '']
```
