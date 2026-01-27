# Discrepancy Dashboard - Documentation

## Overview
The Discrepancy Dashboard allows you to compare the latest daily input stock data with external system stocks. It helps identify and explain stock discrepancies using Outstanding GI/GR and Error Movement data.

## Features
- ✅ Compare daily input (Qty Actual) with external system (SoH)
- ✅ Import external system data via Excel
- ✅ Track Outstanding Goods Receipt (GR)
- ✅ Track Outstanding Goods Issue (GI)
- ✅ Track Error Movements
- ✅ Real-time discrepancy calculation
- ✅ Financial impact analysis
- ✅ Sync with daily inputs
- ✅ Download Excel template for import
- ✅ Export discrepancy data to Excel
- ✅ Filter by Location, Usage, PIC
- ✅ Search by material number/description
- ✅ Pagination (50 items per page)
- ✅ Save All Changes - Persist Outstanding GR/GI/Error edits
- ✅ Percentage statistics for quantity and amount discrepancies
- ✅ Sort by Final Qty and Final Amount

## Access
- **URL:** `/discrepancy-dashboard`
- **Page:** `resources/js/Pages/Discrepancy/index.vue`

## Backend Structure

### 1. Database Table: `discrepancy_materials`
```sql
- id
- material_id (foreign key to materials table)
- price (decimal)
- soh (Stock on Hand from external system)
- outstanding_gr (Outstanding Goods Receipt)
- outstanding_gi (Outstanding Goods Issue)
- error_moving (Error Movement)
- last_synced_at (timestamp)
- created_at
- updated_at
```

### 2. Model: `App\Models\DiscrepancyMaterials`
**Location:** `app/Models/DiscrepancyMaterials.php`

**Methods:**
- `material()` - Relationship to Materials
- `getInitialDiscrepancy()` - Calculate Qty Actual - SoH
- `getFinalDiscrepancy()` - Calculate final variance after adjustments
- `getFinalDiscrepancyAmount()` - Calculate financial impact

### 3. Service: `App\Services\DiscrepancyService`
**Location:** `app/Services/DiscrepancyService.php`

**Methods:**
- `getDiscrepancyData()` - Fetch all discrepancy data with calculations, filters, and pagination
- `getLocations()` - Get unique locations for filter dropdown
- `getPics()` - Get unique PIC names for filter dropdown
- `getUsages()` - Get unique usage values for filter dropdown
- `importFromExcel($filePath)` - Import Excel file
- `syncWithDailyInputs()` - Create records for all materials with daily inputs
- `updateDiscrepancy($materialId, $data)` - Update specific material
- `bulkUpdateDiscrepancy($items)` - Bulk update multiple items (Outstanding GR/GI/Error)

### 4. Controller: `App\Http\Controllers\DiscrepancyController`
**Location:** `app/Http/Controllers/DiscrepancyController.php`

**Endpoints:**
- `GET /discrepancy-dashboard` - Render dashboard page
- `GET /api/discrepancy` - Fetch all data with filters and pagination
- `GET /api/discrepancy/template` - Download import template
- `GET /api/discrepancy/export` - Export discrepancy data to Excel
- `POST /api/discrepancy/import` - Upload Excel
- `POST /api/discrepancy/sync` - Sync with daily inputs
- `POST /api/discrepancy/bulk-update` - Bulk update Outstanding GR/GI/Error
- `PATCH /api/discrepancy/{materialId}` - Update single material

### 5. Export Classes
**Location:** `app/Exports/`

- `DiscrepancyTemplateExport.php` - Generate import template with example data
- `DiscrepancyDataExport.php` - Export discrepancy data with applied filters

## API Endpoints

### GET /api/discrepancy
Fetch all discrepancy data with filters and pagination

**Query Parameters:**
- `location` (optional) - Filter by location
- `pic` (optional) - Filter by PIC name
- `usage` (optional) - Filter by usage
- `search` (optional) - Search material number or description
- `sort_by` (optional) - Sort column: `final_qty` or `final_amount`
- `sort_order` (optional) - Sort direction: `asc` or `desc`
- `per_page` (optional, default: 50) - Items per page
- `page` (optional, default: 1) - Current page

**Example:**
```
GET /api/discrepancy?location=Sunter_1&pic=John&per_page=50&page=1
```

**Response:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 1,
        "materialNo": "RM-2024-001",
        "name": "Copper Wire 5mm",
        "uom": "Roll",
        "pic": "John Doe",
        "location": "Sunter_1",
        "usage": "Production",
        "price": 150000,
        "soh": 50,
        "sohTimestamp": "2026-01-07 10:00",
        "qtyActual": 50,
        "qtyActualTimestamp": "2026-01-07 14:30",
        "timeDiff": 4.5,
        "outGR": 0,
        "outGI": 0,
        "errorMvmt": 0,
        "initialDiscrepancy": 0,
        "finalDiscrepancy": 0,
        "finalDiscrepancyAmount": 0
      }
    ],
    "statistics": {
      "totalItems": 100,
      "surplusCount": 5,
      "discrepancyCount": 3,
      "matchCount": 92,
      "surplusAmount": 1000000,
      "discrepancyAmount": 500000,
      "surplusCountPercent": 5.0,
      "discrepancyCountPercent": 3.0,
      "matchCountPercent": 92.0,
      "surplusAmountPercent": 66.7,
      "discrepancyAmountPercent": 33.3
    },
    "pagination": {
      "current_page": 1,
      "per_page": 50,
      "total": 100,
      "last_page": 2
    }
  }
}
```

### GET /api/discrepancy/template
Download Excel template for import

**Response:**
- Content-Type: `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`
- Filename: `discrepancy_import_template_YYYYMMDD.xlsx`

### GET /api/discrepancy/export
Export discrepancy data to Excel with applied filters

**Query Parameters:**
- `location` (optional) - Filter by location
- `pic` (optional) - Filter by PIC name
- `usage` (optional) - Filter by usage
- `search` (optional) - Search material number or description

**Response:**
- Content-Type: `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`
- Filename: `discrepancy_data_YYYYMMDD_HHMMSS.xlsx`

**Export Columns:**
| Column | Description |
|--------|-------------|
| Material Number | Material identifier |
| Description | Material name |
| Location | Storage location |
| PIC | Person in charge |
| UoM | Unit of measure |
| Price | Unit price |
| SoH | Stock on Hand |
| SoH Timestamp | When SoH was last updated |
| Qty Actual | Actual quantity from daily input |
| Qty Actual Timestamp | When qty was last counted |
| Initial Gap | Qty Actual - SoH |
| Outstanding GR | Outstanding goods receipt |
| Outstanding GI | Outstanding goods issue |
| Error Movement | Error movements |
| Final Discrepancy | Calculated final variance |
| Final Amount | Final discrepancy × price |

### POST /api/discrepancy/import
Upload Excel file to update external system data

**Request:**
```
Content-Type: multipart/form-data
file: [Excel file]
```

**Response:**
```json
{
  "success": true,
  "message": "Excel imported successfully",
  "data": {
    "imported": 10,
    "updated": 5,
    "skipped": 2,
    "errors": []
  }
}
```

### POST /api/discrepancy/sync
Sync discrepancy records with daily inputs

**Response:**
```json
{
  "success": true,
  "message": "Sync completed successfully",
  "data": {
    "created": 15
  }
}
```

### POST /api/discrepancy/bulk-update
Bulk update Outstanding GR/GI/Error for multiple items

**Request:**
```json
{
  "items": [
    {
      "id": 1,
      "outGR": 10,
      "outGI": -5,
      "errorMvmt": 0
    },
    {
      "id": 2,
      "outGR": 0,
      "outGI": -20,
      "errorMvmt": 5
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Changes saved successfully",
  "data": {
    "updated": 2,
    "errors": []
  }
}
```

### PATCH /api/discrepancy/{materialId}
Update discrepancy data for specific material

**Request:**
```json
{
  "price": 150000,
  "soh": 50,
  "outstanding_gr": 10,
  "outstanding_gi": -5,
  "error_moving": 0
}
```

## Excel Import Format

**Required Columns (in order):**
1. Material Number (text) - Must exist in materials table
2. SoH (integer) - Stock on Hand
3. Outstanding GR (integer) - Positive value
4. Outstanding GI (integer) - Negative value
5. Error Moving (integer) - Can be positive or negative
6. Price (decimal) - Material price per unit
7. Location (text) - Material location

**Example:**
```
Material Number | SoH  | Outstanding GR | Outstanding GI | Error Moving | Price  | Location
RM-2024-001    | 50   | 0              | 0              | 0            | 150000 | SUNTER_1
PK-2024-055    | 1200 | 0              | -100           | 0            | 5000   | SUNTER_2
```

See `storage/app/templates/discrepancy_import_template.md` for detailed template.

## Business Logic

### Discrepancy Calculation
```
Initial Discrepancy = Qty Actual (Daily Input) - SoH (External System)
Explained Amount = Outstanding GR + Outstanding GI + Error Moving
Final Discrepancy = Initial Discrepancy + Explained Amount
```

### Percentage Calculations
```
Surplus Count % = (Surplus Items / Total Items) × 100
Discrepancy Count % = (Shortage Items / Total Items) × 100
Match Count % = (Matched Items / Total Items) × 100

Surplus Amount % = (Surplus Amount / Total Discrepancy Amount) × 100
Discrepancy Amount % = (Shortage Amount / Total Discrepancy Amount) × 100
```

### Examples

**Example 1: Perfect Match**
- SoH: 100
- Qty Actual: 100
- Initial Discrepancy: 0
- Final Discrepancy: 0 ✅ MATCH

**Example 2: Explained by Outstanding GI**
- SoH: 100
- Qty Actual: 90
- Outstanding GI: -10
- Initial Discrepancy: -10
- Final Discrepancy: -10 + (-10) = 0 ✅ MATCH

**Example 3: Unexplained Discrepancy**
- SoH: 100
- Qty Actual: 85
- Outstanding GI: -10
- Initial Discrepancy: -15
- Final Discrepancy: -15 + (-10) = -5 ❌ UNMATCHED

## Frontend Features

The frontend (`resources/js/Pages/Discrepancy/index.vue`) provides:

### Header Actions
- **Download Template** - Download Excel import template
- **Download Excel** - Export current data with filters to Excel
- **Upload Excel** - Import external system data
- **Save Changes** - Save modified Outstanding GR/GI/Error (shows count of modified items)
- **Sync** - Sync with daily inputs
- **Refresh** - Reload data

### Statistics Cards
- **Operational Impact**: Surplus/Shortage item counts with percentages
- **Financial Impact**: Surplus/Shortage amounts with percentages
- **Totals**: Total items count, matched items count with percentage

### Filters
- **Location** - Filter by warehouse location
- **Usage** - Filter by material usage
- **PIC** - Filter by person in charge
- **Search** - Search by material number or description

### Table Features
- Real-time editing of Outstanding GR/GI/Error Moving
- Modified items highlighted with green ring
- Sortable columns (Final Qty, Final Amount)
- Color-coded results (blue = surplus, red = shortage, gray = matched)
- Pagination with smart page numbers

## Usage Flow

### 1. Initial Setup
1. Click **"Sync"** to create records for all materials with daily inputs
2. Click **"Download Template"** to get the Excel template
3. Fill in your external system data
4. Click **"Upload Excel"** and select your filled template
5. Review import results

### 2. Daily Operations

1. **Filter Data:**
   - Select Location, Usage, or PIC from dropdowns
   - Use search to find specific materials
   - Statistics update automatically based on filters

2. **Edit Adjustments:**
   - Modify Outstanding GR/GI/Error Moving directly in the table
   - Modified rows are highlighted with green ring
   - Click **"Save Changes"** to persist edits (button shows count of modified items)

3. **Export Data:**
   - Click **"Download Excel"** to export current filtered data
   - Export includes all calculated fields

4. **Monitor Impact:**
   - Top cards show item counts with percentages
   - Bottom cards show financial impact with percentages
   - Total and matched counts displayed

## Migration

Run the migration to create the table:
```bash
php artisan migrate
```

The migration file is located at:
`database/migrations/2026_01_07_140031_create_discrepancy_materials_table.php`

## File Structure

```
app/
├── Exports/
│   ├── DiscrepancyTemplateExport.php
│   └── DiscrepancyDataExport.php
├── Models/
│   └── DiscrepancyMaterials.php
├── Services/
│   └── DiscrepancyService.php
└── Http/
    └── Controllers/
        └── DiscrepancyController.php

database/
└── migrations/
    └── 2026_01_07_140031_create_discrepancy_materials_table.php

resources/
└── js/
    └── Pages/
        └── Discrepancy/
            └── index.vue

routes/
└── web.php (discrepancy routes added)

storage/
└── app/
    └── templates/
        └── discrepancy_import_template.md
```

## Dependencies

- Laravel 10+
- Maatwebsite/Excel (for Excel import/export)
- PhpSpreadsheet
- Inertia.js
- Vue 3
- Axios

## Troubleshooting

### Excel Import Fails
- Check that Material Numbers exist in materials table
- Verify column order matches template
- Ensure Outstanding GI values are negative
- Check Location column matches existing locations

### Data Not Loading
- Check browser console for errors
- Verify API endpoint is accessible: `/api/discrepancy`
- Check database connection

### CSRF Token Error
- Ensure `resources/js/bootstrap.js` includes axios configuration
- Clear browser cookies and retry

### Changes Not Saving
- Make sure to click "Save Changes" button after editing
- Check browser console for API errors
- Verify the discrepancy record exists in database

### Statistics Not Matching Filters
- Statistics are calculated from ALL filtered items (not just current page)
- Ensure all filters (Location, Usage, PIC, Search) are applied correctly
