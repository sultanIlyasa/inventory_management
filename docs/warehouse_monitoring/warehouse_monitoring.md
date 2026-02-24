# Warehouse Monitoring Feature

## Overview

The Warehouse Monitoring feature provides a performance dashboard for tracking warehouse inventory health. The main dashboard (`index.vue`) is a redesigned multi-section overview that fetches each section independently via parallel client-side API calls. Each section links to its own dedicated full-page view.

## Page Entry Point

- **Route**: `GET /warehouse-monitoring`
- **Page Component**: `resources/js/Pages/WarehouseMonitoring/index.vue`
- **Layout**: `MainAppLayout` with title "Warehouse Monitoring"
- **Controller**: `WarehouseMonitoringController`

## Architecture

```
Pages/WarehouseMonitoring/
  ├── index.vue                  (Dashboard - parallel fetch, multi-section)
  ├── Leaderboard.vue            (Full Caution/Shortage leaderboard with tabs)
  ├── OverdueDays.vue            (Full overdue days report)
  ├── LeaderChecklist.vue        (Full check compliance report)
  ├── StatusChange.vue           (Full status change tracker)
  └── RecoveryDays.vue           (Full recovery days report)

Components/
  ├── CautionOverdueLeaderboard.vue   (Reusable: compact + full)
  ├── ShortageOverdueLeaderboard.vue  (Reusable: compact + full)
  ├── StatusChangeContent.vue         (Reusable: compact + full)
  ├── StatusBarChart.vue              (Chart.js bar chart)
  ├── RecoveryDaysReportContent.vue   (Reusable: compact + full, with trend chart)
  └── LeaderChecklistCompact.vue      (Compact view for dashboard)

Controllers/
  ├── WarehouseMonitoringController.php    (Dashboard index + legacy dashboard API)
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
```

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
| `GET` | `/warehouse-monitoring/api/dashboard` | `WarehouseMonitoringController@dashboardApi` | Legacy: all data at once |
| `GET` | `/warehouse-monitoring/api/problematic` | `ProblematicMaterialsController@index` | Problematic materials with severity |
| `GET` | `/warehouse-monitoring/api/consumption-averages` | `ProblematicMaterialsController@getConsumptionAverages` | Raw consumption data from external API |
| `GET` | `/warehouse-monitoring/api/caution` | `LeaderboardController@cautionApi` | Caution leaderboard data |
| `GET` | `/warehouse-monitoring/api/shortage` | `LeaderboardController@shortageApi` | Shortage leaderboard data |
| `GET` | `/warehouse-monitoring/api/recovery-days` | `RecoveryDaysController@recoveryApi` | Recovery days data |
| `GET` | `/warehouse-monitoring/api/status-change-api` | `StatusChangeController@statusChangeApi` | Status change data |

### Query Parameters

| Parameter | Values | Applies To |
|-----------|--------|------------|
| `limit` | integer (max 50) | `/api/problematic` |
| `date` | `YYYY-MM-DD` | Full pages |
| `month` | `YYYY-MM` | Full pages |
| `usage` | `DAILY`, `WEEKLY`, `MONTHLY` | Full pages |
| `location` | `SUNTER_1`, `SUNTER_2` | Full pages |
| `gentani` | `GENTAN-I`, `NON_GENTAN-I` | Full pages |
| `page` | integer | Full pages |
| `per_page` | integer | Full pages (default: 10) |

---

## Dashboard (index.vue)

### Layout

```
┌──────────────────────────────────────────────────────────────────────┐
│  Header + Filter toggle                                               │
│  [Show Filters] → Warehouse / Shift / Search (currently UI-only)     │
├──────────────────────────────────────────────────┬───────────────────┤
│  LEFT (lg:col-span-8)                            │ RIGHT (col-span-4)│
│                                                  │                   │
│  ┌────────────────────────────────────────────┐  │ Distribution donut│
│  │ Problematic Materials Table ← REAL DATA    │  │ (dummy)           │
│  │ Skeleton loading state                     │  │                   │
│  │ Empty state                                │  ├───────────────────┤
│  │ Row click → Detail Modal                   │  │ Shortage LB       │
│  └────────────────────────────────────────────┘  │ (dummy)           │
│                                                  ├───────────────────┤
│  ┌──────────────────────┐ ┌─────────────────┐   │ Caution LB        │
│  │ Overdue Trend (dummy)│ │ Recommendation  │   │ (dummy)           │
│  │ Recovery Trend(dummy)│ │ Panel (dummy)   │   ├───────────────────┤
│  └──────────────────────┘ └─────────────────┘   │ Status Changes bar│
│                                                  │ (dummy)           │
│                                                  ├───────────────────┤
│                                                  │ Fastest to Crit.  │
│                                                  │ (dummy)           │
├──────────────────────────────────────────────────┴───────────────────┤
│  Material Inventory Overview Table (dummy)                            │
└──────────────────────────────────────────────────────────────────────┘
```

### Data Fetching Strategy

The dashboard uses **parallel client-side fetch calls** — each section fetches its own data independently on `onMounted`. Only the Problematic Materials section is currently wired to a real API endpoint. All other sections still use dummy data pending future integration.

```
onMounted()
  ├── fetchProblematicMaterials()  → GET /warehouse-monitoring/api/problematic
  └── initCharts()                 → Chart.js initialisation
```

### Sections: Real Data vs Dummy

| Section | Status | API Endpoint |
|---------|--------|-------------|
| Problematic Materials | **Real data** | `/api/problematic` |
| Distribution donut | Dummy | — |
| Shortage Leaderboard | Dummy | `/api/shortage` (not yet wired) |
| Caution Leaderboard | Dummy | `/api/caution` (not yet wired) |
| Overdue Trend chart | Dummy | — |
| Recovery Trend chart | Dummy | — |
| Status Changes bar | Dummy | `/api/status-change-api` (not yet wired) |
| Fastest to Critical | Dummy | — |
| Material Inventory Overview | Dummy | — |

### Caching

| Cache key | TTL | What |
|-----------|-----|------|
| `problematic_materials_{limit}` | 5 min | Final joined result |
| `consumption_averages_all` | 1 hour | External consumption API response |

---

## Modules

### 1. Problematic Materials (Dashboard section)

The top table on the dashboard. Shows SHORTAGE and CAUTION materials sorted by urgency, enriched with coverage/durability data from the external consumption API.

**File**: `app/Http/Controllers/ProblematicMaterialsController.php`
**Service**: `app/Services/ProblematicMaterialsService.php`
**Route**: `GET /warehouse-monitoring/api/problematic?limit=10`

#### Response Shape

```json
{
  "success": true,
  "total": 8,
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
      "instock": 10,
      "streak_days": 4,
      "location": "SUNTER_1",
      "usage": "DAILY",
      "last_updated": "2026-02-20"
    }
  ]
}
```

#### Sort Order

Backend sorts: `status_priority ASC` (SHORTAGE=1, CAUTION=2), then `streak_days DESC`.
Frontend re-sorts by severity rank so `Line-Stop Risk` always floats above `High`.

#### Severity Logic (`resolveSeverity()`)

| Status | Condition | Severity |
|--------|-----------|----------|
| SHORTAGE | `coverage_shifts < 1` | Line-Stop Risk |
| SHORTAGE | `coverage_shifts` 1–3 | High |
| SHORTAGE | `coverage_shifts >= 3` or no data | Medium / High |
| CAUTION | `streak_days > 7` | High |
| CAUTION | `streak_days > 3` | Medium |
| CAUTION | `streak_days ≤ 3` | Low |

#### TypeScript Types (index.vue)

```typescript
type Status   = 'CAUTION' | 'SHORTAGE'
type Severity = 'Low' | 'Medium' | 'High' | 'Line-Stop Risk'

type MaterialRow = {
    id: number
    material_number: string
    description: string
    pic_name: string
    status: Status
    severity: Severity
    coverage_shifts: number | null   // null when no consumption data from external API
    daily_avg: number | null
    shift_avg: number | null
    instock: number
    streak_days: number
    location: string
    usage: string
    last_updated: string | null
}
```

#### Detail Modal

Clicking a row opens a slide-up modal (`showDetailModal`) showing:
- Coverage shifts (or "No data" if null)
- Streak days
- Current stock + usage unit
- Action recommendation (Escalate / Monitor)
- Material info grid: PIC, Location, Usage, Shift avg

#### Loading States

- **Skeleton**: 5 animated placeholder rows while fetching
- **Empty state**: "No problematic materials found." if API returns empty array
- **Error**: Banner at top of page if fetch fails

---

### 2. External Consumption API

**Endpoint**: `http://103.93.52.79:2000/api/transaction/analytics/consumption-average`
**Auth**: `X-API-Key: WAREHOUSE_DASHBOARD_KEY_2026`
**Config**: `config('services.consumption_api.url')` / `config('services.consumption_api.key')`

The service fetches **all materials** (`limit=500`) and keys the result by `material_id` for O(1) lookup. This is then joined with the DB query result in PHP.

```
material_id (external API) ↔ material_number (materials table)
```

**External API response fields used**:

| Field | Used For |
|-------|----------|
| `material_id` | Join key |
| `shift_avg` | `coverage_shifts = instock / shift_avg` |
| `daily_avg` | Shown in modal |

**Exposed as its own endpoint** (`/api/consumption-averages`) for frontend debugging/inspection.

---

### 3. Caution/Shortage Leaderboard

**Full Page**: `Leaderboard.vue` with tabbed interface (CAUTION / SHORTAGE tabs).

**What it shows**: Materials ranked by consecutive days in CAUTION or SHORTAGE status. Top 3 entries get medal icons.

**Controller**: `LeaderboardController` using `LeaderboardService`.

**Dashboard "View All"**: `/warehouse-monitoring/leaderboard?tab=CAUTION` / `?tab=SHORTAGE`

---

### 4. Overdue Days

**Full Page**: `OverdueDays.vue`

**What it shows**: Materials in SHORTAGE, CAUTION, OVERFLOW, or UNCHECKED status with consecutive weekday count. Weekday formula excludes Saturday/Sunday.

**Filters**: Search, PIC, Location, Usage, Gentani, Status, Sort By, Sort Direction.

**Statistics**: Counts for SHORTAGE, CAUTION, OVERFLOW, UNCHECKED.

**Table columns**: Material Number, Description, PIC, Stock, Status, Overdue Days, Last Updated.

**Controller**: `OverdueDaysController` using `OverdueDaysService`.

**Dashboard "View All"**: `/warehouse-monitoring/overdue-days`

---

### 5. Leader Checklist

**Full Page**: `LeaderChecklist.vue`

**What it shows**: Materials due for checking today based on usage schedule (DAILY/WEEKLY/MONTHLY) that haven't been checked yet.

**Statistics**: DAILY Need Check, WEEKLY Need Check, MONTHLY Need Check, TOTAL.

**Features**: Jump-to-page input for large result sets.

**Controller**: `LeaderChecklistController` using `LeaderChecklistService`.

**Dashboard "View All"**: `/warehouse-monitoring/leader-checklist`

---

### 6. Status Change Tracker

**Full Page**: `StatusChange.vue`
**Component**: `StatusChangeContent.vue`

**What it shows**: Materials with frequent status transitions (OK→Caution, OK→Shortage, OK→Overflow) indicating instability.

**Controller**: `StatusChangeController` using `StatusChangeService`.

**Dashboard "View All"**: `/warehouse-monitoring/status-change`

---

### 7. Recovery Days

**Full Page**: `RecoveryDays.vue`
**Component**: `RecoveryDaysReportContent.vue`

**What it shows**: Materials that recovered from a problem status back to OK, with recovery duration and a monthly trend chart.

**Controller**: `RecoveryDaysController` using `RecoveryDaysService`.

**Dashboard "View All"**: `/warehouse-monitoring/recovery-days`

---

## Data Flow

### Dashboard: Problematic Materials

```
onMounted
  └── fetchProblematicMaterials()
        └── GET /warehouse-monitoring/api/problematic?limit=10
              └── ProblematicMaterialsController@index
                    └── Cache::remember('problematic_materials_10', 300)
                          └── ProblematicMaterialsService@getProblematicMaterials()
                                ├── queryProblematicMaterials()  ← DB: SHORTAGE/CAUTION, sorted
                                └── fetchConsumptionAverages()   ← External API (cached 1h)
                                      └── join by material_number = material_id
                                      └── compute coverage_shifts, severity
```

### Dashboard → Full Page Navigation

| Dashboard Section | View All Target |
|---|---|
| Shortage Leaderboard | `/warehouse-monitoring/leaderboard?tab=SHORTAGE` |
| Caution Leaderboard | `/warehouse-monitoring/leaderboard?tab=CAUTION` |
| Problematic Materials | `/warehouse-monitoring/overdue-days` |
| Recovery Trend | `/warehouse-monitoring/recovery-days` |
| Status Changes | `/warehouse-monitoring/status-change` |

### Full Page Data Flow (Inertia)

```
User changes filter or page
  └── router.get(route('warehouse-monitoring.xxx'), params, {
        preserveState: true,
        preserveScroll: true,
        only: ['relevantProps']
      })
        └── Controller@index → Service → Cache::remember() → DB
              └── Inertia::render() → partial prop update → Vue reactivity
```

---

## Backend Services

| Service | Key Methods |
|---------|-------------|
| `ProblematicMaterialsService` | `getProblematicMaterials($limit)`, `fetchConsumptionAverages()` |
| `LeaderboardService` | `getCautionLeaderboard($filters, $perPage, $page)`, `getShortageLeaderboard(...)` |
| `OverdueDaysService` | `getOverdueReports($filters, $perPage, $page)` |
| `LeaderChecklistService` | `getComplianceReport($filters, $perPage, $page)` |
| `StatusChangeService` | `getStatusChangeReport($filters, $perPage, $page)` |
| `RecoveryDaysService` | `getRecoveryReport($filters, $perPage, $page)`, `getRecoveryTrend($year)` |
| `MaterialReportService` | `getStatusBarChart($filters)` |

### Form Requests (Full Pages)

Each full-page controller uses a dedicated Form Request providing `getFilters()` and `getPaginationParams()`:
- `LeaderboardRequest`
- `OverdueDaysRequest`
- `LeaderChecklistRequest`
- `StatusChangeRequest`
- `RecoveryDaysRequest`

---

## Charts (index.vue)

All charts use Chart.js with manual controller registration. Chart instances are stored as refs and destroyed in `onBeforeUnmount`.

| Canvas ref | Type | Data | Status |
|---|---|---|---|
| `overdueCanvas` | Line | Overdue days trend by month | Dummy |
| `recoveryCanvas` | Line | Recovery days trend by month | Dummy |
| `distributionCanvas` | Doughnut | OK / Caution / Shortage counts | Dummy |
| `statusChangeCanvas` | Bar | Status changes by month | Dummy |
| `fastestCanvas` | Bar (horizontal) | Top 5 fastest to critical | Dummy |
| `modalCanvas` | Line | Per-material history (in detail modal) | Dummy |

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
