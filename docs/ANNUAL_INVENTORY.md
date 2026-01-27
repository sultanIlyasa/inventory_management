# Annual Inventory Feature

## Overview

The Annual Inventory feature allows warehouse staff to perform physical inventory counts by distributing materials into Physical Inventory Documents (PIDs). Each PID can contain up to 200 materials and is assigned to a specific location and person in charge (PIC).

## Flow

```
1. Admin uploads PID Excel file (location, pid, material_number)
      ↓
2. System creates PIDs and assigns materials
      ↓
3. Staff searches for their assigned PID
      ↓
4. Staff counts physical inventory for each material
      ↓
5. Staff submits actual quantity
      ↓
6. Admin reviews discrepancy page, inputs SOH, GR/GI/Error adjustments
      ↓
7. System calculates final discrepancy
```

## Database Structure

### Table: `annual_inventories`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| pid | string | Unique PID number |
| date | date | Inventory date |
| status | string | Not Checked / In Progress / Completed |
| pic_name | string | Person in charge name |
| location | string | Warehouse location |
| timestamps | | created_at, updated_at |

### Table: `annual_inventory_items`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| annual_inventory_id | FK | Reference to PID |
| material_number | string | Material number |
| description | string | Material description |
| rack_address | string | Storage location |
| unit_of_measure | string | UoM |
| system_qty | integer | System quantity |
| soh | integer | Stock on hand |
| actual_qty | integer | Physical count (nullable) |
| price | integer | Unit price |
| outstanding_gr | integer | Outstanding goods receipt |
| outstanding_gi | integer | Outstanding goods issue |
| error_movement | integer | Movement errors |
| final_discrepancy | integer | Calculated discrepancy |
| final_discrepancy_amount | decimal | Discrepancy value |
| status | string | PENDING / COUNTED / VERIFIED |
| counted_by | string | Counter name |
| counted_at | datetime | Count timestamp |
| image_path | string | S3 image path |
| notes | text | Optional notes |
| timestamps | | created_at, updated_at |

## API Endpoints

### PID Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/annual-inventory` | List all PIDs with pagination |
| GET | `/api/annual-inventory/search?q=` | Search PIDs by number or PIC |
| GET | `/api/annual-inventory/{id}` | Get single PID with all items (by ID) |
| GET | `/api/annual-inventory/pid/{pid}` | Get PID by exact PID number |
| GET | `/api/annual-inventory/by-pid/{pid}` | Get PID with paginated items (by PID number) |
| PUT | `/api/annual-inventory/{id}` | Update PID details (pid, location, pic_name) |
| DELETE | `/api/annual-inventory/{id}` | Delete PID and all items |

### Item Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| PUT | `/api/annual-inventory/items/{itemId}` | Update single item |
| POST | `/api/annual-inventory/items/bulk-update` | Bulk update items |
| POST | `/api/annual-inventory/items/{itemId}/upload-image` | Upload photo evidence |

### Discrepancy Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/annual-inventory/discrepancy` | Get discrepancy items with filters |
| GET | `/api/annual-inventory/discrepancy/template` | Download discrepancy Excel template |
| POST | `/api/annual-inventory/discrepancy/import` | Import discrepancy data from Excel |
| POST | `/api/annual-inventory/discrepancy/bulk-update` | Bulk update SOH/GR/GI/Error |

### Import & Export

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/annual-inventory/template` | Download PID import Excel template |
| POST | `/api/annual-inventory/importpid` | Import PIDs from Excel (supports bulk upload) |
| GET | `/api/annual-inventory/export` | Export PIDs with actual qty to Excel |
| GET | `/api/annual-inventory/statistics` | Get dashboard statistics |
| GET | `/api/annual-inventory/locations` | Get unique locations |
| GET | `/api/annual-inventory/pids-dropdown` | Get PIDs for dropdown filter |

## Frontend Pages

### Index Page (`/annual-inventory`)

- **Statistics Cards**: Total PIDs, Completed, In Progress, Completion Rate
- **Search Bar**: Search by PID number or PIC name
- **Filters**: Location, Status
- **Actions**:
  - Refresh - Reload data
  - Download Template - Download PID import Excel template
  - Download Excel - Export PIDs with actual quantities
  - Upload PID - Import new PIDs from Excel (supports bulk upload)
- **Bulk Upload Feature**:
  - Select multiple Excel files at once
  - File list display with remove buttons
  - Progress bar showing upload progress (x of y files)
  - Per-file results (success with counts, or error message)
  - Summary totals after upload:
    - Files processed
    - Total PIDs created
    - Total PIDs updated
    - Total items created
    - Failed files count
- **Table**: PID list with progress indicators, pagination
- **Row Actions**:
  - Submit - Navigate to counting page
  - Edit (pencil icon) - Edit PID details
  - Delete (trash icon) - Delete PID with confirmation
- **Mobile**: Card view for small screens

### Submit Page (`/annual-inventory/{pid}`)

**Note**: URL uses PID number instead of database ID (e.g., `/annual-inventory/PID-2026-001`)

- **Header**: PID number, location, date
- **Statistics**: Total items, Pending, Counted, Progress percentage
- **Search**: Filter items by material number, description, rack
- **Pagination**: 50 items per page with navigation controls
- **Table Columns**:
  - Material Number
  - Description
  - Rack Address
  - UoM
  - Actual Qty (main input)
  - Status
  - Actions (Submit, Camera)
- **Features**:
  - Individual item submit with confirmation modal
  - Save All button for bulk save
  - Image upload for photo evidence

### Discrepancy Page (`/annual-inventory/discrepancy`)

- **Header Actions**:
  - Download Template - Excel template for bulk import
  - Upload Excel - Import discrepancy adjustments
  - Save All Changes - Save modified items
  - Refresh - Reload data
- **Statistics Cards**:
  - Operational Impact: Surplus Items (+), Shortage Items (-)
  - Financial Impact: Surplus Amount, Shortage Amount
- **Filters**:
  - Location - Filter by warehouse location
  - PID - Filter by specific PID
  - Discrepancy - Surplus/Shortage/Match
  - Search - Material number or description
- **Table Columns**:
  - Material Number, Name, Rack, Price
  - SOH (editable)
  - Actual Qty (from counting)
  - Initial Gap (Actual - SOH)
  - Outstanding GR (editable, +)
  - Outstanding GI (editable, -)
  - Error Movement (editable)
  - Final Discrepancy (calculated)
  - Final Amount (calculated)
- **Pagination**: 50 items per page

## Bulk Upload Feature

The index page supports uploading multiple Excel files at once for PID import:

### How It Works

1. Click "Upload PID" button to open upload modal
2. Click "Choose Files" to select multiple Excel files
3. Selected files appear in a list with remove buttons
4. Click "Upload All" to start processing
5. Progress bar shows current file being processed (x of y)
6. Each file shows individual result (success or error)
7. Summary totals displayed after all files complete:
   - Files processed
   - Total PIDs created across all files
   - Total PIDs updated across all files
   - Total items created across all files
   - Count of failed files

### Upload Response Format

Each file upload returns:

```json
{
  "success": true,
  "message": "Import completed",
  "pids_created": 5,
  "pids_updated": 2,
  "items_created": 150,
  "errors": []
}
```

## Excel Import/Export Formats

### PID Import Format (Upload PID)

| Column | Header | Example |
|--------|--------|---------|
| A | Location | Sunter_1 |
| B | PID | PID-2026-001 |
| C | Material Number | MAT-001234 |

### Discrepancy Template Format

| Column | Header | Example |
|--------|--------|---------|
| A | Material Number | RM-2024-001 |
| B | SOH | 50 |
| C | Outstanding GR | 0 |
| D | Outstanding GI | -100 |
| E | Error Movement | 0 |
| F | Price | 150000 |
| G | Location | Sunter_1 |

### Export Format (Download Excel)

| Columns |
|---------|
| PID, Location, PIC, Status, Material Number, Description, Rack, UoM, System Qty, SOH, Actual Qty, Discrepancy, Price, Discrepancy Amount, Counted By, Counted At, Item Status |

## Discrepancy Calculation

```
Initial Gap = Actual Qty - SOH
Final Discrepancy = Actual Qty - SOH + Outstanding GR + Outstanding GI + Error Movement
Final Discrepancy Amount = Final Discrepancy × Unit Price
```

## Status Flow

### PID Status

```
Not Checked → In Progress → Completed
     ↑              ↑            ↑
  No items    Some items    All items
  counted      counted       verified
```

### Item Status

```
PENDING → COUNTED → VERIFIED
    ↑         ↑          ↑
  Initial   Actual    Confirmed
  state     qty set   by admin
```

## File Structure

```
app/
├── Http/
│   └── Controllers/
│       └── AnnualInventoryController.php
├── Models/
│   ├── AnnualInventory.php
│   └── AnnualInventoryItems.php
└── Services/
    └── AnnualInventoryService.php

database/
└── migrations/
    ├── 2026_01_14_105704_create_annual_inventory_table.php
    └── 2026_01_14_105710_create_annual_inventory_items_table.php

resources/js/Pages/
└── AnnualInventory/
    ├── index.vue        # PID list with edit/delete/export
    ├── show.vue         # Counting page with pagination
    └── discrepancy.vue  # Discrepancy analysis with import/export

routes/
├── api.php      # API routes
└── web.php      # Web routes
```

## Usage Examples

### API: Update PID details

```bash
PUT /api/annual-inventory/1
Content-Type: application/json

{
  "pid": "PID-2026-001",
  "location": "Sunter_1",
  "pic_name": "John Doe"
}
```

### API: Get PID with pagination (by PID number)

```bash
GET /api/annual-inventory/by-pid/PID-2026-001?page=1&per_page=50&search=MAT
```

### API: Get discrepancy items with filters

```bash
GET /api/annual-inventory/discrepancy?location=Sunter_1&pid_id=1&discrepancy_type=shortage&page=1
```

### API: Import discrepancy data

```bash
POST /api/annual-inventory/discrepancy/import
Content-Type: multipart/form-data

file: [discrepancy.xlsx]
```

### API: Export PIDs to Excel

```bash
GET /api/annual-inventory/export?location=Sunter_1&status=Completed
```

## Sidebar Navigation

```
Annual Inventory (ClipboardList icon)
├── PID List        → /annual-inventory
└── Discrepancy     → /annual-inventory/discrepancy
```

## Troubleshooting

### Common Issues

1. **Material not found during import**
   - Ensure material exists in `materials` table with exact material_number

2. **Image upload fails**
   - Check S3 credentials in `.env`
   - Verify bucket permissions allow PutObject

3. **PID exceeds 200 items**
   - Split materials into multiple PIDs
   - Limit is enforced during import

4. **Discrepancy not calculating**
   - Ensure actual_qty is entered on the counting page
   - SOH field should be populated on discrepancy page

5. **Cannot access PID by URL**
   - URL now uses PID number, not database ID
   - Example: `/annual-inventory/PID-2026-001`
