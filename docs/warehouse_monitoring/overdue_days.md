# Overdue Days

**File**: `resources/js/Pages/WarehouseMonitoring/OverdueDays.vue`
**Route**: `GET /warehouse-monitoring/overdue-days`
**Controller**: `OverdueDaysController@index`

## Purpose

Displays materials currently stuck in a problem status (SHORTAGE, CAUTION, OVERFLOW, UNCHECKED) along with how many consecutive days they've been in that state. Helps identify materials that need urgent attention.

## Layout

```
┌─────────────────────────────────────────────────────────┐
│  [Show Filters]                                          │
├─────────────────────────────────────────────────────────┤
│  Filters (collapsible):                                  │
│  Search, PIC, Location, Usage, Gentani,                  │
│  Status, Sort By, Sort Direction                         │
│  [Apply] [Clear]                                         │
├─────────────────────────────────────────────────────────┤
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌───────────┐  │
│  │ SHORTAGE │ │ CAUTION  │ │ OVERFLOW │ │ UNCHECKED │  │
│  │    12    │ │    32    │ │     5    │ │     8     │  │
│  └──────────┘ └──────────┘ └──────────┘ └───────────┘  │
├─────────────────────────────────────────────────────────┤
│  Table:                                                  │
│  Material Number | Description | PIC | Stock | Status    │
│  | Overdue Days | Last Updated                           │
│                                                          │
│  [Previous] [Next]                                       │
└─────────────────────────────────────────────────────────┘
```

## Props (from Inertia)

| Prop | Type | Description |
|------|------|-------------|
| `overdueReports` | Object | `{ data: [], pagination: {} }` |
| `statistics` | Object | Counts per status |
| `filterOptions` | Object | `{ pics[], locations[], usages[], gentani[] }` |
| `filters` | Object | Current filter values |

## State

| Ref | Type | Description |
|-----|------|-------------|
| `showFilters` | Boolean | Toggle filter panel |
| `isLoading` | Boolean | Loading state |
| `localFilters` | Object | `{ search, pic, location, usage, gentani, status, sortField, sortDirection }` |

## Filters

| Filter | Type | Options |
|--------|------|---------|
| Search | Text input | Free text search |
| PIC | Select | Dynamic from `filterOptions.pics` |
| Location | Select | Dynamic from `filterOptions.locations` |
| Usage | Select | DAILY, WEEKLY, MONTHLY |
| Gentani | Select | GENTAN-I, NON_GENTAN-I |
| Status | Select | SHORTAGE, CAUTION, OVERFLOW, UNCHECKED |
| Sort By | Select | status, overdue_days, material_number, pic_name |
| Sort Direction | Select | asc, desc |

Default sort: `status desc` (most severe first).

## Navigation

Uses Inertia `router.get()` with partial reload:
```js
router.get(route('warehouse-monitoring.overdue-days'), {
    ...localFilters.value,
    page: targetPage
}, {
    preserveState: true,
    preserveScroll: true,
    only: ['overdueReports', 'statistics', 'filterOptions']
})
```

## Table Columns

| Column | Description |
|--------|-------------|
| Material Number | Part number |
| Description | Material name |
| PIC | Person in charge |
| Stock | Current stock level |
| Status | Badge: SHORTAGE (red), CAUTION (orange), OVERFLOW (gray), UNCHECKED (gray-400) |
| Overdue Days | Days in current status. Color: red >= 15, orange >= 7, yellow < 7 |
| Last Updated | Relative date: "Today", "Yesterday", or DD/MM/YYYY |

## Pagination

Simple Previous/Next navigation (no page numbers).

## Backend: OverdueDaysController

**File**: `app/Http/Controllers/OverdueDaysController.php`
**Service**: `OverdueDaysService`
**Request**: `OverdueDaysRequest`

### Response Shape
```php
Inertia::render('WarehouseMonitoring/OverdueDays', [
    'overdueReports' => ['data' => [...], 'pagination' => [...]],
    'statistics'     => [...],
    'filterOptions'  => ['pics' => [], 'locations' => [], 'usages' => [], 'gentani' => []],
    'filters'        => [...],
]);
```

### Caching
```
Key: "overdue_days_{filterHash}_page_{page}_per_{perPage}"
TTL: 5 minutes
```

### Default Filters
```php
['date' => '', 'month' => '', 'usage' => '', 'location' => '', 'gentani' => '',
 'search' => '', 'pic' => '', 'status' => '', 'sortField' => 'status', 'sortDirection' => 'desc']
```

---

## Service: OverdueDaysService

**File**: `app/Services/OverdueDaysService.php`

Uses raw SQL queries via `DB::select()` with parameterized bindings.

### getOverdueReports($filters, $perPage, $page)

Main entry point. Returns `{ data, pagination, statistics, filter_options }`.

**Internal flow**:
1. `buildWhereConditions($filters)` → SQL WHERE fragments
2. `buildBindings($filters)` → Named parameter bindings
3. `getTotalCount()` → Count for pagination
4. `getPaginatedData()` → Actual rows
5. `getStatistics()` → Status counts
6. `getFilterOptions()` → Dynamic dropdown options

### How "Latest Status" is Determined

Uses a subquery to find each material's most recent daily_input:
```sql
LEFT JOIN (
    SELECT di.material_id, di.status, di.daily_stock, di.date
    FROM daily_inputs di
    WHERE di.id IN (
        SELECT MAX(id) FROM daily_inputs
        [WHERE date <= :date]   -- optional date filter
        GROUP BY material_id
    )
) as latest_status ON materials.id = latest_status.material_id
```

Materials with no daily_input record at all get status `NULL`, treated as `UNCHECKED`.

### How "Consecutive Days" (Overdue Days) is Calculated

Uses a weekday-aware `DATEDIFF` formula:
```sql
DATEDIFF(CURDATE(), di.date)
  - (WEEK(CURDATE()) - WEEK(di.date)) * 2
  - CASE WHEN DAYOFWEEK(di.date) = 1 THEN 1 ELSE 0 END
  - CASE WHEN DAYOFWEEK(CURDATE()) = 7 THEN 1 ELSE 0 END
```
This subtracts weekends from the day count to get **business days only**.

### Status Filter Behavior

| Filter Value | SQL Condition |
|-------------|---------------|
| `UNCHECKED` | `latest_status.status IS NULL` (no daily_input exists) |
| `SHORTAGE` / `CAUTION` / `OVERFLOW` | `latest_status.status = :status` |
| *(empty)* | `status IN ('SHORTAGE','CAUTION','OVERFLOW') OR status IS NULL` — all problem statuses |

### Sort Options

| sortField | SQL ORDER BY |
|-----------|-------------|
| `days` | `consecutive_days {dir}, status_priority ASC` |
| `last_updated` | `last_updated_date {dir}, status_priority ASC` |
| *(default: status)* | `status_priority {dir}, consecutive_days DESC` |

Status priority: SHORTAGE=1, CAUTION=2, OVERFLOW=3, UNCHECKED=4, other=5.

### Statistics Return Shape
```php
['SHORTAGE' => 12, 'CAUTION' => 32, 'OVERFLOW' => 5, 'UNCHECKED' => 8]
```

### Filter Options

Queries the `materials` table for distinct values of `pic_name`, `location`, `usage`, `gentani`. Respects currently applied material filters (e.g., selecting location=SUNTER_1 narrows the PIC dropdown).
