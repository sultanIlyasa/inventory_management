# Discrepancy Dashboard - New Features

## Updates Summary

### 1. Download Excel Template Button ✅

**Frontend:** Added purple "Download Template" button with download icon
- **Location:** Top right of dashboard, before "Upload Excel"
- **Functionality:** Downloads a pre-formatted Excel template with example data
- **Endpoint:** `GET /api/discrepancy/template`

**Template Contents:**
- Header row with proper column names
- 3 example rows showing correct data format
- Styled header (bold, gray background)
- Auto-sized columns

**Example Data in Template:**
```
Material Number | SoH  | Outstanding GR | Outstanding GI | Error Moving | Price
RM-2024-001    | 50   | 0              | 0              | 0            | 150000
PK-2024-055    | 1200 | 0              | -100           | 0            | 5000
EL-2024-889    | 45   | 5              | 0              | 0            | 75000
```

### 2. Location Filter ✅

**Frontend:** Dropdown filter above the table
- **Location:** New filter bar between statistics cards and table
- **Options:** "All Locations" + all unique locations from materials with discrepancy data
- **Behavior:** Resets to page 1 when filter changes

**Backend:**
- Service method: `getLocations()` - retrieves unique locations
- Query filtering in `getDiscrepancyData()` - applies location filter to results
- Statistics updated based on filtered data

**UI Features:**
- Shows count: "Showing X of Y items"
- Styled with shadow and border
- Responsive dropdown

### 3. Pagination ✅

**Backend:**
- Default: 50 items per page
- Configurable via `per_page` query parameter
- Returns pagination metadata:
  - `current_page`
  - `per_page`
  - `total` (total items)
  - `last_page` (total pages)

**Frontend:**
- Smart page number display (shows ellipsis for large page counts)
- Navigation buttons:
  - First / Last
  - Previous / Next
  - Individual page numbers
- Current page highlighted in blue
- Disabled state for unavailable actions
- Statistics calculated from ALL items (not just current page)

**Page Number Logic:**
- Shows all pages if ≤ 7 pages
- Shows 1 ... 4 5 6 ... 10 pattern for many pages
- Always shows first and last page

## New Backend Files

### 1. `app/Exports/DiscrepancyTemplateExport.php`
Export class for generating Excel template
- Implements `FromCollection`, `WithHeadings`, `ShouldAutoSize`, `WithStyles`
- Returns 3 example rows
- Styled header row

### 2. Updated Files

**`app/Services/DiscrepancyService.php`**
- `getDiscrepancyData()` - Added filters and pagination parameters
- `getLocations()` - New method to get unique locations
- Statistics now calculated from all filtered items (not just current page)

**`app/Models/Materials.php`**
- Added `discrepancyMaterial()` relationship

**`app/Http/Controllers/DiscrepancyController.php`**
- `index()` - Now passes locations to view
- `getDiscrepancyData()` - Handles filter and pagination params
- `downloadTemplate()` - New method for template download

**`routes/web.php`**
- Added `GET /api/discrepancy/template` route

**`resources/js/Pages/Testing/index.vue`**
- Added props: `locations`
- Added state: `pagination`, `selectedLocation`
- Added functions:
  - `handleLocationChange()` - Reset to page 1 on filter change
  - `goToPage(page)` - Navigate to specific page
  - `downloadTemplate()` - Trigger template download
  - `getPageNumbers()` - Smart pagination display
- Updated `fetchData()` - Now accepts page parameter and filter
- Added UI components:
  - Download template button
  - Location filter dropdown
  - Pagination controls with page numbers

## Usage Flow

### 1. First Time Setup

1. Click **"Sync"** to create records for all materials with daily inputs
2. Click **"Download Template"** to get the Excel template
3. Fill in your external system data:
   - Material Number (must match existing materials)
   - SoH (Stock on Hand)
   - Outstanding GR (positive numbers)
   - Outstanding GI (negative numbers)
   - Error Moving (can be + or -)
   - Price (per unit)
4. Click **"Upload Excel"** and select your filled template
5. Review import results

### 2. Daily Operations

1. **Filter by Location:**
   - Select location from dropdown
   - View only items from that location
   - Statistics update automatically

2. **Browse Data:**
   - Use pagination to navigate through pages
   - 50 items shown per page by default
   - Click page numbers or First/Last/Next/Previous

3. **Update Data:**
   - Download new template anytime
   - Upload updated Excel with latest external system data
   - Or manually edit Outstanding GR/GI/Error Moving in the table

## API Changes

### GET /api/discrepancy

**New Query Parameters:**
- `location` (optional) - Filter by location
- `per_page` (optional, default: 50) - Items per page
- `page` (optional, default: 1) - Current page

**Example:**
```
GET /api/discrepancy?location=Sunter%201&per_page=50&page=2
```

**Response Changes:**
```json
{
  "success": true,
  "data": {
    "items": [...],
    "statistics": {
      "surplusCount": 5,
      "discrepancyCount": 3,
      "surplusAmount": 1000000,
      "discrepancyAmount": 500000
    },
    "pagination": {
      "current_page": 2,
      "per_page": 50,
      "total": 150,
      "last_page": 3
    }
  }
}
```

### GET /api/discrepancy/template (NEW)

Downloads Excel template file

**Response:**
- Content-Type: `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`
- Filename: `discrepancy_import_template_YYYYMMDD.xlsx`

## Visual Changes

**Before:**
```
[Header with Upload Excel | Sync | Refresh]
[Statistics Cards]
[Table with all data]
```

**After:**
```
[Header with Download Template | Upload Excel | Sync | Refresh]
[Statistics Cards]
[Filter Bar: Location Dropdown | Showing X of Y items]
[Table with paginated data]
[Pagination: First | Previous | 1 2 3 ... | Next | Last | Total: X items]
```

## Performance Improvements

1. **Pagination:**
   - Only loads 50 items at a time (instead of all)
   - Faster page load
   - Reduced memory usage

2. **Location Filter:**
   - Filter applied at database level
   - Only queries needed data
   - Statistics calculated from filtered set

3. **Smart Statistics:**
   - Statistics show totals for ALL filtered items
   - Not just current page
   - Gives accurate overview even when paginated

## Future Enhancements

Potential improvements:
- Per-page selector (25, 50, 100)
- Search by material number/name
- Additional filters (usage, gentani, PIC)
- Sort by columns
- Bulk edit mode
- Export filtered/paginated data to Excel
