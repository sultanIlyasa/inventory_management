# Leaderboard (Caution & Shortage)

## Page

**File**: `resources/js/Pages/WarehouseMonitoring/Leaderboard.vue`
**Route**: `GET /warehouse-monitoring/leaderboard`
**Controller**: `LeaderboardController@index`

## Purpose

Full-page view for Caution and Shortage material leaderboards. Uses a tabbed interface to switch between the two. Materials are ranked by consecutive days in a problem status.

## Layout

```
┌──────────────────────────────────────────────────────┐
│  Filters: Date, Month, PIC, Usage, Location, Gentani │
│  [Apply] [Clear]                                      │
├──────────────────────────────────────────────────────┤
│  [ CAUTION (32) ]  [ SHORTAGE (15) ]   ← Tab headers │
├──────────────────────────────────────────────────────┤
│  Active tab content:                                  │
│  CautionOverdueLeaderboard (size="full")              │
│  OR                                                   │
│  ShortageOverdueLeaderboard (size="full")             │
│                                                       │
│  Full table + pagination                              │
└──────────────────────────────────────────────────────┘
```

## Props (from Inertia)

| Prop | Type | Description |
|------|------|-------------|
| `cautionData` | Object | `{ statistics, leaderboard (aliased as data), pagination }` |
| `shortageData` | Object | `{ statistics, leaderboard (aliased as data), pagination }` |
| `activeTab` | String | `"CAUTION"` or `"SHORTAGE"`, default `"CAUTION"` |
| `filters` | Object | Current filter values |

## State

| Ref | Type | Description |
|-----|------|-------------|
| `currentTab` | String | Active tab: `"CAUTION"` or `"SHORTAGE"` |
| `isLoading` | Boolean | Loading state |
| `localFilters` | Object | `{ date, month, usage, location, gentani, pic_name }` |
| `showMobileFilters` | Boolean | Toggle filter panel |

## Tab Switching

`switchTab(tabId)` uses `router.get()` with `{ tab: tabId, ...filters, page: 1 }`. Only reloads `cautionData`, `shortageData`, and `activeTab` props via Inertia partial reload.

## Pagination

`handlePageChange(tab, page)` sends `router.get()` with the tab, filters, and target page. Tab count badges show `statistics.total` for each tab.

## Filter Options

PIC names are hardcoded in the component:
```js
const uniquePICs = ["ADE N", "AKBAR", "ANWAR", "BAHTIYAR", "DEDHI", ...]
```

---

## CautionOverdueLeaderboard Component

**File**: `resources/js/Components/CautionOverdueLeaderboard.vue`

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `filters` | Object | `null` | When non-null, component self-fetches from `/api/caution` |
| `initialLeaderboard` | Array | `[]` | Material entries ranked by days (prop-driven mode only) |
| `initialStatistics` | Object | `{ total: 0, average_days: 0 }` | Aggregate stats (prop-driven mode only) |
| `initialPagination` | Object | `{ current_page, last_page, per_page, total }` | Pagination metadata (prop-driven mode only) |
| `size` | String | `"full"` | `"mini"`, `"compact"`, or `"full"` |
| `limit` | Number | null | Override default item limit |
| `showViewAll` | Boolean | `true` | Show "View All" link |
| `viewAllUrl` | String | `/warehouse-monitoring/leaderboard?tab=CAUTION` | Link target |
| `hideRefresh` | Boolean | `false` | Hide refresh button |

### Modes

| Mode | How to activate | Data source |
|------|-----------------|-------------|
| Self-fetch | Pass `:filters="globalFilter"` | Fetches `/api/caution?{filters}` independently |
| Prop-driven | Omit `filters` (defaults to `null`) | Reads `initialLeaderboard/Statistics/Pagination` |

Self-fetch mode is used on the dashboard. Prop-driven mode is used by `Leaderboard.vue` (full page, Inertia-driven).

Pagination in self-fetch mode is handled internally (calls `fetchData(page)`). In prop-driven mode, pagination emits `page-change` upward.

### Events

| Event | Payload | Description |
|-------|---------|-------------|
| `refresh` | none | Request parent to re-fetch (prop-driven mode only) |
| `page-change` | page number | Request specific page (prop-driven mode only) |

### Display Modes

| Size | Default Limit | Layout | Container |
|------|--------------|--------|-----------|
| `mini` | 3 | Ranked list | `max-h-80 overflow-hidden` |
| `compact` | 5 | Ranked list with "View All" | `max-h-96 overflow-y-auto` |
| `full` | 10 | Summary stats + full table + pagination | No constraint |

### Ranked List (compact/mini)
- Top 3 get medal emojis
- Each entry shows material number, description, and days count
- Days colored: red >= 15, orange >= 7, yellow < 7

### Full Table Columns
| Column | Description |
|--------|-------------|
| PIC | Person in charge |
| Material | Material number |
| Description | Material description |
| Usage | DAILY/WEEKLY/MONTHLY |
| SOH | Stock on hand |
| Days | Consecutive days in CAUTION |

### Pagination
5-page window with First/Prev/Next/Last navigation. Shows "Showing X to Y of Z entries".

---

## ShortageOverdueLeaderboard Component

**File**: `resources/js/Components/ShortageOverdueLeaderboard.vue`

Structurally identical to `CautionOverdueLeaderboard` (including self-fetch mode via `filters` prop, endpoint: `/api/shortage`) with these differences:

| Aspect | Caution | Shortage |
|--------|---------|----------|
| Header | "Caution Materials Leaderboard" | "Shortage Materials Leaderboard" |
| Color theme | Orange | Red |
| Days color | red >= 15, orange >= 7, yellow < 7 | red-700 >= 15, red-600 >= 7, orange < 7 |
| Default viewAllUrl | `?tab=CAUTION` | `?tab=SHORTAGE` |
| Active page color | Blue | Red |

Same props, events, display modes, and pagination logic.

---

## Backend: LeaderboardController

**File**: `app/Http/Controllers/LeaderboardController.php`
**Service**: `LeaderboardService`
**Request**: `LeaderboardRequest`

### Endpoints

| Method | Handler | Returns |
|--------|---------|---------|
| `index(LeaderboardRequest)` | Fetches both tabs, returns Inertia page | `cautionData`, `shortageData`, `activeTab`, `filters` |
| `cautionApi(LeaderboardRequest)` | JSON endpoint for CAUTION | `{ success, data }` |
| `shortageApi(LeaderboardRequest)` | JSON endpoint for SHORTAGE | `{ success, data }` |

### Caching
```php
Cache key: "leaderboard_{TYPE}_{filterHash}_page_{page}_per_{perPage}"
TTL: 5 minutes
```

Both CAUTION and SHORTAGE are always fetched together for the Inertia page (to populate tab badges).

---

## Service: LeaderboardService

**File**: `app/Services/LeaderboardService.php`

Uses raw SQL queries via `DB::select()` for performance.

### Public Methods

| Method | Description |
|--------|-------------|
| `getCautionLeaderboard($filters, $perPage, $page)` | Get CAUTION leaderboard (delegates to `getLeaderboard`) |
| `getShortageLeaderboard($filters, $perPage, $page)` | Get SHORTAGE leaderboard (delegates to `getLeaderboard`) |
| `getAllLeaderboards($filters, $perPage)` | Get both leaderboards at once (page 1) |
| `getTopMaterials($status, $filters, $limit)` | Get top N materials for widgets (no pagination) |
| `getLeaderboardSummary($filters)` | Get caution/shortage counts summary |

### getLeaderboard($status, $filters, $perPage, $page) — Core Logic

**Step 1: Build WHERE conditions**

Filters supported: `usage`, `location`, `gentani`, `pic_name`. All applied to the `materials` table. Status is always matched against `daily_inputs.status`.

**Step 2: Count distinct materials**
```sql
SELECT COUNT(DISTINCT materials.id) as total
FROM materials
INNER JOIN daily_inputs ON materials.id = daily_inputs.material_id
WHERE daily_inputs.status = :status [AND material filters...]
```

**Step 3: Get paginated data**
```sql
SELECT
    materials.id, materials.material_number, materials.description,
    materials.pic_name, materials.usage, materials.location, materials.gentani,
    COUNT(DISTINCT daily_inputs.date) as days,
    MAX(daily_inputs.daily_stock) as current_stock
FROM materials
INNER JOIN daily_inputs ON materials.id = daily_inputs.material_id
WHERE daily_inputs.status = :status [AND material filters...]
GROUP BY materials.id, ...
ORDER BY days DESC
LIMIT :limit OFFSET :offset
```

The `days` column counts **distinct dates** where the material had the given status — this is how materials are ranked.

**Step 4: Calculate statistics**
```sql
SELECT AVG(days), MAX(days), MIN(days)
FROM (
    SELECT materials.id, COUNT(DISTINCT daily_inputs.date) as days
    FROM materials INNER JOIN daily_inputs ...
    WHERE status = :status
    GROUP BY materials.id
) as days_per_material
```

**Return shape**:
```php
[
    'data' => [
        ['id' => 1, 'material_number' => '...', 'description' => '...', 'pic_name' => '...', 'usage' => '...', 'location' => '...', 'gentani' => '...', 'days' => 15, 'current_stock' => 100],
        ...
    ],
    'statistics' => [
        'type' => 'CAUTION',        // or 'SHORTAGE'
        'total' => 32,              // total materials in this status
        'average_days' => 8.5,      // avg days across all materials
        'max_days' => 22,           // worst case
        'min_days' => 1,            // best case
    ],
    'pagination' => [
        'current_page' => 1, 'last_page' => 4, 'per_page' => 10, 'total' => 32,
        'from' => 1, 'to' => 10,
    ],
]
```
