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
- `getDiscrepancyData()` - Fetch all discrepancy data with calculations
- `importFromExcel($filePath)` - Import Excel file
- `syncWithDailyInputs()` - Create records for all materials with daily inputs
- `updateDiscrepancy($materialId, $data)` - Update specific material

### 4. Controller: `App\Http\Controllers\DiscrepancyController`
**Location:** `app/Http/Controllers/DiscrepancyController.php`

**Endpoints:**
- `GET /discrepancy-dashboard` - Render dashboard page
- `GET /api/discrepancy` - Fetch all data
- `POST /api/discrepancy/import` - Upload Excel
- `POST /api/discrepancy/sync` - Sync with daily inputs
- `PATCH /api/discrepancy/{materialId}` - Update material

## API Endpoints

### GET /api/discrepancy
Fetch all discrepancy data

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
        "location": "Sunter 1",
        "price": 150000,
        "soh": 50,
        "qtyActual": 50,
        "timeDiff": 0.5,
        "outGR": 0,
        "outGI": 0,
        "errorMvmt": 0,
        "last_update": "2026-01-07",
        "initialDiscrepancy": 0,
        "finalDiscrepancy": 0,
        "finalDiscrepancyAmount": 0
      }
    ],
    "statistics": {
      "surplusCount": 5,
      "discrepancyCount": 3,
      "surplusAmount": 1000000,
      "discrepancyAmount": 500000
    }
  }
}
```

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

**Example:**
```
Material Number | SoH  | Outstanding GR | Outstanding GI | Error Moving | Price
RM-2024-001    | 50   | 0              | 0              | 0            | 150000
PK-2024-055    | 1200 | 0              | -100           | 0            | 5000
```

See `storage/app/templates/discrepancy_import_template.md` for detailed template.

## Business Logic

### Discrepancy Calculation
```
Initial Discrepancy = Qty Actual (Daily Input) - SoH (External System)
Explained Amount = Outstanding GR + Outstanding GI + Error Moving
Final Discrepancy = Initial Discrepancy + Explained Amount
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

## Frontend Integration

The frontend (`resources/js/Pages/Testing/index.vue`) automatically:
- Fetches data on page load
- Allows real-time editing of Outstanding GR/GI/Error Moving
- Recalculates discrepancies as you type
- Shows color-coded results (green = surplus, red = shortage)
- Displays operational and financial impact

## Usage Flow

1. **Initial Setup:**
   - Click "Sync with Daily Inputs" to create records for all materials

2. **Import External System Data:**
   - Prepare Excel file with current SoH, Outstanding GR/GI, Error Moving
   - Click "Upload Excel"
   - Select your file
   - Review import results

3. **Analyze Discrepancies:**
   - View Initial Discrepancy column (Actual - SoH)
   - Adjust Outstanding GR/GI/Error Moving if needed
   - Watch Final Discrepancy update in real-time
   - Green values = surplus, Red values = shortage, Gray = matched

4. **Monitor Impact:**
   - Top cards show count of surplus/shortage items
   - Bottom cards show financial impact

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
        └── Testing/
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
- PhpSpreadsheet (for Excel import)
- Inertia.js
- Vue 3
- Axios

## Troubleshooting

### Excel Import Fails
- Check that Material Numbers exist in materials table
- Verify column order matches template
- Ensure Outstanding GI values are negative

### Data Not Loading
- Check browser console for errors
- Verify API endpoint is accessible: `/api/discrepancy`
- Check database connection

### CSRF Token Error
- Ensure `resources/js/bootstrap.js` includes axios configuration
- Clear browser cookies and retry

## Future Enhancements

Potential improvements:
- Export discrepancy report to Excel
- Filter by location/usage/PIC
- History tracking of changes
- Auto-sync scheduler
- Email notifications for large discrepancies
