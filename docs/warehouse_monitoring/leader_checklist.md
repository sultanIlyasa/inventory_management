# Leader Checklist

**File**: `resources/js/Pages/WarehouseMonitoring/LeaderChecklist.vue`
**Route**: `GET /warehouse-monitoring/leader-checklist`
**Controller**: `LeaderChecklistController@index`

## Purpose

Shows materials that are due for checking based on their usage schedule but haven't been checked yet. Helps ensure all materials are inspected on time according to their DAILY, WEEKLY, or MONTHLY schedule.

## Layout

```
┌──────────────────────────────────────────────────────────┐
│  Info Banner (blue):                                      │
│  "Check schedule explanation..."                          │
├──────────────────────────────────────────────────────────┤
│  Filters: Search, PIC, Location, Usage, Gentani           │
│  [Apply] [Clear]                                          │
├──────────────────────────────────────────────────────────┤
│  ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌───────┐ │
│  │DAILY Need  │ │WEEKLY Need │ │MONTHLY Need│ │ TOTAL │ │
│  │  Check: 15 │ │  Check: 8  │ │  Check: 3  │ │  26   │ │
│  └────────────┘ └────────────┘ └────────────┘ └───────┘ │
├──────────────────────────────────────────────────────────┤
│  Table:                                                   │
│  Material Number | Description | PIC | Usage |            │
│  Stock Range | Last Check Status                          │
│                                                           │
│  Pagination + Jump to page                                │
└──────────────────────────────────────────────────────────┘
```

## Props (from Inertia)

| Prop | Type | Description |
|------|------|-------------|
| `complianceReports` | Object | `{ data: [], pagination: {} }` |
| `statistics` | Object | Counts per usage type + total |
| `filterOptions` | Object | `{ pics[], locations[], usages[], gentani[] }` |
| `filters` | Object | Current filter values |

## State

| Ref | Type | Description |
|-----|------|-------------|
| `showFilters` | Boolean | Toggle filter panel |
| `isLoading` | Boolean | Loading state |
| `jumpToPageInput` | String | Page number input for jump-to-page |
| `localFilters` | Object | `{ search, pic, location, usage, gentani }` |

## Filters

| Filter | Type | Options |
|--------|------|---------|
| Search | Text input | Free text search |
| PIC | Select | Dynamic from `filterOptions.pics` |
| Location | Select | Dynamic from `filterOptions.locations` |
| Usage | Select | DAILY, WEEKLY, MONTHLY |
| Gentani | Select | GENTAN-I, NON_GENTAN-I |

No sort options — server determines order.

## Navigation

Uses Inertia `router.get()` with partial reload:
```js
router.get(route('warehouse-monitoring.leader-checklist'), {
    ...localFilters.value,
    page: targetPage
}, {
    preserveState: true,
    preserveScroll: true,
    only: ['complianceReports', 'statistics', 'filterOptions']
})
```

## Table Columns

| Column | Description |
|--------|-------------|
| Material Number | Part number |
| Description | Material name |
| PIC | Person in charge |
| Usage | Badge: DAILY (green), WEEKLY (blue), MONTHLY (purple) |
| Stock Range | Min - Max range |
| Last Check Status | Last recorded status and date |

## Empty State

When all materials are up to date, shows: "All materials up to date!"

## Jump-to-Page

Unique to this page — includes a numeric input for jumping directly to a page number:
```js
jumpToPage() {
    const pageNum = parseInt(jumpToPageInput.value)
    if (pageNum >= 1 && pageNum <= pagination.last_page) {
        changePage(pageNum)
    }
}
```

## Days Color Logic

| Condition | Color |
|-----------|-------|
| `days === 999` | Red (never checked) |
| `days >= 30` | Red |
| `days >= 14` | Orange |
| `days >= 7` | Yellow |
| `days < 7` | Default gray |

## Backend: LeaderChecklistController

**File**: `app/Http/Controllers/LeaderChecklistController.php`
**Service**: `LeaderChecklistService`
**Request**: `LeaderChecklistRequest`

### Response Shape
```php
Inertia::render('WarehouseMonitoring/LeaderChecklist', [
    'complianceReports' => ['data' => [...], 'pagination' => [...]],
    'statistics'        => [...],
    'filterOptions'     => ['pics' => [], 'locations' => [], 'usages' => [], 'gentani' => []],
    'filters'           => [...],
]);
```

### Caching
```
Key: "leader_checklist_{filterHash}_page_{page}_per_{perPage}"
TTL: 5 minutes
```

### Default Filters
```php
['usage' => '', 'location' => '', 'gentani' => '', 'search' => '', 'pic' => '']
```

---

## Service: LeaderChecklistService

**File**: `app/Services/LeaderChecklistService.php`

Uses raw SQL queries via `DB::select()` with parameterized bindings.

### getComplianceReport($filters, $perPage, $page)

Main entry point. Returns `{ data, pagination, statistics, filter_options }`.

### Core Logic: "Needs Checking" Rules

The service determines which materials need checking based on their `usage` schedule:

```sql
WHERE (
    (materials.usage = 'DAILY'   AND daily_inputs.id IS NULL)
    OR (materials.usage = 'WEEKLY'  AND (last_check_date IS NULL OR last_check_date < CURDATE() - 7 days))
    OR (materials.usage = 'MONTHLY' AND (last_check_date IS NULL OR last_check_date < CURDATE() - 30 days))
)
```

| Usage | Needs Check When |
|-------|-----------------|
| DAILY | No `daily_inputs` record exists for today (`CURDATE()`) |
| WEEKLY | Last check was more than 7 days ago, or never checked |
| MONTHLY | Last check was more than 30 days ago, or never checked |

### How "Last Check" is Found

```sql
LEFT JOIN (
    SELECT material_id, MAX(date) as last_check_date
    FROM daily_inputs
    GROUP BY material_id
) as last_check ON materials.id = last_check.material_id
```

### Data Columns Returned

| Column | SQL Logic |
|--------|-----------|
| `check_status` | `'Never Checked'` if no record, `'Due Today'` for DAILY, `'Last: DD/MM/YYYY'` for WEEKLY/MONTHLY |
| `days_since_check` | `DATEDIFF(CURDATE(), last_check_date)`, or `999` if never checked |

### Sort Order

```sql
ORDER BY days_since_check DESC, materials.usage ASC
```

Materials never checked (`999` days) appear first. Within same days, sorted by usage (DAILY < MONTHLY < WEEKLY alphabetically).

### Statistics Return Shape
```php
[
    'DAILY'   => 15,    // daily materials needing check
    'WEEKLY'  => 8,     // weekly materials overdue
    'MONTHLY' => 3,     // monthly materials overdue
    'TOTAL'   => 26,    // sum of above
]
```

### Filter Options

Same pattern as OverdueDaysService — queries `materials` for distinct `pic_name`, `location`, `usage`, `gentani` values, narrowed by active filters.
