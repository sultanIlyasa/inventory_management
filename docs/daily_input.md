# Daily Input Feature

## Overview

The Daily Input feature allows users to record and monitor daily stock levels for materials across warehouse locations. It provides a dashboard to view checked/unchecked materials, submit stock quantities, and track inventory status (OK, SHORTAGE, CAUTION, OVERFLOW).

## Page Entry Point

- **Route**: `GET /daily-input`
- **Page Component**: `resources/js/Pages/DailyInput/index.vue`
- **Layout**: `MainAppLayout` with title "Daily Input"

The page renders a single `DashboardTable` component (`resources/js/Components/DailyInputDashboard.vue`) which orchestrates the entire feature.

## Architecture

```
Pages/DailyInput/index.vue
  └── Components/DailyInputDashboard.vue
        ├── Components/SearchBar.vue
        ├── Components/Filterbar.vue
        ├── Components/DailyInputTable.vue
        │     └── Components/Modal.vue
        └── Components/Pagination.vue

Composeables/useDailyInput.js  (state & logic)
```

## Component Integration

### How Components Connect

```
┌─────────────────────────────────────────────────────────────────────┐
│  index.vue (Page)                                                   │
│  - Receives auth props from Inertia                                 │
│  - Renders DailyInputDashboard inside MainAppLayout                 │
│                                                                     │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │  DailyInputDashboard.vue (Orchestrator)                       │  │
│  │  - Calls useDailyInput() composable on setup                  │  │
│  │  - Destructures all state, computed, and actions               │  │
│  │  - Distributes data downward to child components via props    │  │
│  │  - Handles Refresh, Download, Sync buttons directly           │  │
│  │                                                                │  │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────────┐ ┌────────────┐  │  │
│  │  │ SearchBar│ │ Filterbar│ │DailyInputTable│ │ Pagination │  │  │
│  │  └──────────┘ └──────────┘ └──────────────┘ └────────────┘  │  │
│  └───────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
         │                              │
         ▼                              ▼
  useDailyInput.js              DailyInputController.php
  (composable)                  (Laravel API)
```

### Integration Details

**index.vue → DailyInputDashboard.vue**
- `index.vue` is a thin wrapper. It places `DailyInputDashboard` inside `MainAppLayout` and does nothing else. No props are passed down; the dashboard is self-contained.

**DailyInputDashboard.vue → useDailyInput.js (composable)**
- The dashboard calls `useDailyInput()` on setup and destructures everything it needs: reactive state (`searchTerm`, `selectedDate`, filter selections), computed values (`paginatedItems`, `stats`, `uniquePICs`, `usages`), and actions (`fetchDailyData`, `submitDailyStock`, `deleteInput`, `syncDailyInputStatus`).
- `onMounted` triggers `fetchDailyData()` for the initial load.
- The dashboard wires its three action buttons directly:
  - **Refresh** → calls `fetchDailyData()` with loading state
  - **Download** → builds URL from current filter state and redirects to `/api/daily-input/export`
  - **Sync Status** → calls `syncDailyInputStatus()` and alerts the user

**DailyInputDashboard.vue → SearchBar.vue**
- Binds `v-model:searchTerm` for two-way text search.
- Listens to `@clear` event which calls `clearFilters()` from the composable.

**DailyInputDashboard.vue → Filterbar.vue**
- Two-way binds all filter state via `v-model`: `selectedPIC`, `selectedLocation`, `selectedUsage`, `selectedGentani`, `selectedDate`.
- Passes dynamic options via props: `picOptions` (from `uniquePICs` computed) and `usageOptions` (from `usages` computed).
- When the user changes a filter value, the `v-model` updates the composable's reactive refs. The composable's watchers on `selectedDate`, `selectedUsage`, and `selectedLocation` automatically re-fetch data from the API. Filters like `selectedPIC` and `selectedGentani` only affect client-side filtering (no API call).

**DailyInputDashboard.vue → DailyInputTable.vue**
- Props passed: `items` (paginated list), `uncheckedCount`, `stats`, `sortOrder`, `startItem`, `selectedDate`.
- Events listened to:
  - `@submit` → calls `submitDailyStock(item)` from composable
  - `@delete` → calls `deleteInput(item)` from composable
  - `@update:sortOrder` → updates composable's `sortOrder` ref

**DailyInputDashboard.vue → Pagination.vue**
- Only rendered when `totalPages > 1`.
- Two-way binds `v-model:currentPage`.
- Props: `totalPages`, `startItem`, `endItem`, `totalItems`, `visiblePages` (all computed from composable).

**DailyInputTable.vue → Modal.vue**
- Uses three modal instances internally:
  - **Submit Confirmation**: shown before posting a stock entry, displays material details and entered quantity.
  - **Delete Confirmation**: shown before deleting a record.
  - **Timestamp**: shows the `created_at` time of a checked entry.

### Data Flow: Submit Stock Entry

```
1. User enters quantity in DailyInputTable inline input
2. User clicks Submit → Submit Confirmation Modal opens
3. User confirms → DailyInputTable emits "submit" event with item data
4. DailyInputDashboard receives event → calls composable's submitDailyStock(item)
5. Composable POSTs to /api/daily-input with { material_id, date, daily_stock }
6. DailyInputController.store() validates, computes status, creates DailyInput record
7. Composable calls fetchDailyData() to refresh → GETs /api/daily-input/status
8. Controller.dailyStatus() returns updated checked[] + missing[] arrays
9. Composable updates reportData → computed pipeline recalculates allItems → filteredItems → sortedItems → paginatedItems
10. Vue reactivity updates DailyInputTable, stats badges, and Pagination
```

### Data Flow: Filter Change

```
1. User selects a filter in Filterbar (e.g., Usage = "WEEKLY")
2. v-model emits update → composable's selectedUsage ref changes
3. Watcher fires → fetchDailyData() called with new params
4. API returns data scoped to the usage's date range (week boundaries)
5. Composable rebuilds allItems from response
6. Client-side filters (PIC, Gentani, search) applied on top
7. Pagination resets view; stats recalculated from filteredItems
```

## API Endpoints

All routes defined in `routes/api.php`, handled by `App\Http\Controllers\DailyInputController`.

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/daily-input` | List all daily inputs for a given date |
| `POST` | `/api/daily-input` | Submit a new daily stock entry |
| `GET` | `/api/daily-input/missing` | Get materials not yet checked for a date |
| `GET` | `/api/daily-input/status` | Get checked + missing materials with status (primary data endpoint) |
| `DELETE` | `/api/daily-input/delete/{id}` | Delete a daily input record |
| `GET` | `/api/daily-input/weekly-status` | Get weekly status data |
| `GET` | `/api/daily-input/export` | Download Excel export of daily input data |
| `POST` | `/api/daily-input/sync-status` | Re-evaluate and sync statuses against current min/max thresholds |

### Key Query Parameters

- `date` (YYYY-MM-DD) - Target date, defaults to today
- `usage` (DAILY / WEEKLY / MONTHLY) - Filter by material usage frequency
- `location` (SUNTER_1 / SUNTER_2) - Filter by warehouse location

## Data Model

### DailyInput (`app/Models/DailyInput.php`)

**Table**: `daily_inputs`

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Primary key |
| `material_id` | integer | FK to `materials` table |
| `date` | date | Date of the stock check |
| `daily_stock` | integer | Recorded stock quantity |
| `status` | string | Computed status: OK, SHORTAGE, CAUTION, OVERFLOW |
| `created_at` | timestamp | Record creation time |
| `updated_at` | timestamp | Last update time |

**Relationships**:
- `belongsTo(Materials)` via `material_id`

**Query Scopes**:
- `inDateRange($start, $end)` - Filter by date range
- `inMonth($month)` - Filter by month
- `byStatus($status)` - Filter by status
- `weekdaysOnly()` - Exclude weekends (Saturday/Sunday)
- `latestPerMaterial()` - Get most recent entry per material

**Static Methods**:
- `getLatestForMaterial($materialId, $beforeDate)` - Latest entry for a material
- `getStatusDaysForMaterial($materialId, $month, $beforeDate)` - Status distribution over days
- `getStatusTransitionsForMaterial($materialId, $month, $beforeDate)` - Status change history
- `getConsecutiveDaysForMaterial($materialId, $status, $latestDate, $lookbackLimit)` - Count consecutive days with same status

## Status Logic

When a stock entry is submitted, status is computed in the `store` method:

```
SHORTAGE  → daily_stock === 0
CAUTION   → daily_stock < stock_minimum
OVERFLOW  → daily_stock > stock_maximum
OK        → within min/max range (inclusive)
```

Duplicate entries for the same material on the same date (today) are prevented with a 409 response.

## Frontend Composable (`useDailyInput.js`)

Central state management via Vue 3 composable. Handles:

### State
- `reportData` - Raw API response (checked + missing arrays)
- `searchTerm` - Text search filter
- `selectedDate` - Date filter (defaults to today)
- `selectedUsage`, `selectedPIC`, `selectedLocation`, `selectedGentani` - Dropdown filters
- `currentPage`, `sortOrder` - Pagination and sort state
- `isLoading` - Loading indicator

### Computed Data Pipeline

```
API response → allItems (merged checked + missing) → filteredItems (applied filters + search) → sortedItems (sort order applied) → paginatedItems(25 items per page)
```

### Sort Options
- `default` - No specific sort
- `priority` - Problems first (SHORTAGE > OVERFLOW > CAUTION > UNCHECKED > OK)
- `rack-asc` / `rack-desc` - By rack address alphabetically

### Actions
- `fetchDailyData()` - Fetches `/api/daily-input/status` with current filters
- `submitDailyStock(item)` - POST to `/api/daily-input` then refreshes
- `deleteInput(item)` - DELETE then refreshes
- `syncDailyInputStatus()` - POST to sync, alerts user with result
- `clearFilters()` - Reset all filters to default

### Watchers
- `selectedDate`, `selectedUsage`, `selectedLocation` changes trigger automatic `fetchDailyData()`

## Filter Bar (`Filterbar.vue`)

Collapsible filter panel with:

| Filter | Options |
|--------|---------|
| PIC | Dynamic from data |
| Location | SUNTER_1, SUNTER_2 |
| Usage | DAILY, WEEKLY, MONTHLY |
| Gentan-I | GENTAN-I, NON_GENTAN-I |
| Date | FlatPickr date picker with week/month shortcuts |

- Usage selection affects date picker behavior (weekly mode shows "This Week"/"Last Week" buttons, monthly mode uses month picker)
- Shows active filter count badge
- Starts expanded by default

## Table View (`DailyInputTable.vue`)

### Display Modes
- **Desktop (md+)**: Full table with columns: No, Material Number, Description, PIC, UoM, Rack Address, Min, Max, SOH, Status, Actual Stock, Action
- **Mobile**: Card-based layout with same data

### Status Badges
Color-coded badges with counts displayed above the table:
- **Unchecked** (gray) - Not yet recorded
- **Shortage** (red) - Stock is zero
- **Caution** (yellow) - Below minimum
- **Overflow** (blue) - Above maximum

### Actions
- **Submit**: Number input + submit button for unchecked items, with confirmation modal
- **Delete**: Available for checked items, with confirmation modal
- **Timestamp**: Click to view creation timestamp in a modal

## Excel Export

Triggered via "Download Data" button. Calls `/api/daily-input/export` with current date/usage/location filters. Uses `Maatwebsite\Excel` with `DailyInputExport` class. File is named `Inventory-{date}_{time}_WIB.xlsx`.

## Sync Status

The "Sync Status" button re-evaluates all daily input records against current material min/max thresholds. This is useful when material thresholds are updated and existing records need their status recalculated. Processes in chunks within a database transaction.
