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
| location | string | Warehouse location (e.g. "2000 - Warehouse Consummable, Chemical, & Raw Material") |
| sloc | string | Storage location / plant (auto-assigned from location mapping) |
| group_leader | string | Group leader name (auto-assigned from location mapping) |
| pic_input | string | PIC input name (auto-assigned from location mapping) |
| pic_name_signature | longText | Base64 PNG data URI for PIC Name signature (nullable) |
| group_leader_signature | longText | Base64 PNG data URI for Group Leader signature (nullable) |
| pic_input_signature | longText | Base64 PNG data URI for PIC Input signature (nullable) |
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
| system_qty | decimal | System quantity |
| soh | decimal | Stock on hand |
| actual_qty | decimal | Physical count (nullable) |
| price | decimal | Unit price |
| outstanding_gr | decimal | Outstanding goods receipt |
| outstanding_gi | decimal | Outstanding goods issue |
| error_movement | decimal | Movement errors |
| final_discrepancy | decimal | Calculated discrepancy |
| final_discrepancy_amount | decimal | Discrepancy value |
| final_counted_qty | decimal | Predicted SOH after adjustments (SOH + Final Discrepancy) |
| status | string | PENDING / COUNTED / VERIFIED |
| counted_by | string | Counter name |
| counted_at | datetime | Count timestamp |
| image_path | string | S3 image path |
| notes | text | Optional notes |
| actual_qty_history | json | Array of qty revision history |
| timestamps | | created_at, updated_at |

#### `actual_qty_history` JSON Structure

Each entry in the history array contains:

```json
[
  {
    "actual_qty": 100,
    "counted_by": "John Doe",
    "counted_at": "2026-02-04 10:30:00"
  },
  {
    "actual_qty": 105,
    "counted_by": "Jane Smith",
    "counted_at": "2026-02-04 14:15:00"
  }
]
```

## API Endpoints

### PID Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/annual-inventory` | List all PIDs with pagination |
| GET | `/api/annual-inventory/search?q=` | Search PIDs by number or PIC |
| GET | `/api/annual-inventory/{id}` | Get single PID with all items (by ID) |
| GET | `/api/annual-inventory/pid/{pid}` | Get PID by exact PID number |
| GET | `/api/annual-inventory/by-pid/{pid}` | Get PID with paginated items, supports sorting |
| PUT | `/api/annual-inventory/{id}` | Update PID details (pid, location, pic_name) |
| DELETE | `/api/annual-inventory/{id}` | Delete PID and all items |

### Per-PID Signatures

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/annual-inventory/{id}/signatures` | Save or delete a signature for a specific role |

**Request body:**

```json
{ "role": "pic_name|group_leader|pic_input", "signature": "data:image/png;base64,..." }
```

Send `"signature": null` to delete a signature.

**Response:**

```json
{
  "success": true,
  "message": "Signature saved",
  "data": {
    "has_pic_name_signature": true,
    "has_group_leader_signature": false,
    "has_pic_input_signature": false
  }
}
```

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
| GET | `/api/annual-inventory/discrepancy/export` | Export discrepancy data to Excel |
| POST | `/api/annual-inventory/discrepancy/import` | Import discrepancy data from Excel |
| POST | `/api/annual-inventory/discrepancy/bulk-update` | Bulk update SOH/GR/GI/Error |
| POST | `/api/annual-inventory/recalculate-discrepancy` | Recalculate all stored discrepancy values |

### Data Sync

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/annual-inventory/sync-pic-gl` | Sync pic_input, group_leader, and sloc for all PIDs based on location mapping |

### Import & Export

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/annual-inventory/template` | Download PID import Excel template |
| POST | `/api/annual-inventory/importpid` | Import PIDs from Excel (supports bulk upload) |
| GET | `/api/annual-inventory/export` | Export PIDs with actual qty to Excel |
| GET | `/api/annual-inventory/statistics` | Get dashboard statistics (supports filters) |
| GET | `/api/annual-inventory/locations` | Get unique locations |
| GET | `/api/annual-inventory/pids-dropdown` | Get PIDs for dropdown filter |

### Query Parameters

#### `/api/annual-inventory/by-pid/{pid}`

| Parameter | Default | Description |
|-----------|---------|-------------|
| page | 1 | Page number |
| per_page | 50 | Items per page |
| search | | Search material number, description, or rack |
| sort_by | material_number | Sort column (allowed: `material_number`, `rack_address`, `description`, `status`) |
| sort_order | asc | Sort direction (`asc` or `desc`) |

#### `/api/annual-inventory/statistics`

| Parameter | Default | Description |
|-----------|---------|-------------|
| search | | Search PID number or PIC name |
| location | | Filter by location |
| status | | Filter by PID status |

Statistics respond to the same filters as the index page, so stats update in real-time as filters are applied.

#### `/api/annual-inventory/discrepancy`

| Parameter | Default | Description |
|-----------|---------|-------------|
| page | 1 | Page number |
| per_page | 50 | Items per page |
| pid_id | | Filter by PID |
| status | | Filter by item status |
| discrepancy_type | | Filter by type: `surplus`, `deficit`, `match` |
| location | | Filter by location |
| search | | Search material number, description, or PID |
| counted_only | true | Show only counted items (status != PENDING) |

## Frontend Pages

### Index Page (`/annual-inventory`)

- **Statistics Cards**: Total PIDs, Completed, In Progress, Completion Rate
  - Statistics update dynamically based on active filters (search, location, status)
- **Search Bar**: Search by PID number or PIC name (debounced 300ms)
- **Filters**: Location, Status
- **Actions**:
  - Refresh - Reload data
  - Sync PIC & GL - Sync pic_input, group_leader, and sloc for all PIDs based on location mapping
  - Download Selected - Download selected PIDs as Excel (blocked if any selected PID has missing signatures)
  - Download All - Export all PIDs with current filters
  - Upload - Import new PIDs from Excel (supports bulk upload)
- **Checkbox Selection**:
  - Per-row checkboxes for selecting PIDs
  - Select all checkbox in header (with indeterminate state)
  - Selection info bar showing count with clear button
  - Selected PIDs used for targeted Excel download
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
  - Signature (pen icon) - Open per-PID signature modal; green dot when all 3 roles are signed
  - Edit (pencil icon) - Edit PID details
  - Delete (trash icon) - Delete PID with confirmation
- **Mobile**: Card view for small screens with signature button in footer

### Per-PID Signature Modal

Each PID has its own set of 3 signatures (PIC Name, PIC Input, Group Leader) stored in the database. Signatures are persistent and can be completed independently across different devices/sessions.

- **Modal**: Shows 3 role cards, each displaying the assigned person's name
  - **Unsigned**: "Tap to Sign" button opens a signature pad
  - **Signed**: Green checkmark with a trash icon to delete the signature
- **Signature Pad**: Transparent background PNG, saved via `POST /api/annual-inventory/{id}/signatures`
- **Delete**: Sends `signature: null` to clear a specific role's signature
- **Download Guard**: "Download Selected" is blocked if any selected PID has incomplete signatures, with an alert listing which PIDs are missing signatures
- **Export**: Signatures are read from the database per-PID during export and embedded into the worksheet template at cells E3 (Group Leader), F3 (PIC Name), G3 (PIC Input), H3 (Group Leader duplicate)

### Submit Page (`/annual-inventory/{pid}`)

**Note**: URL uses PID number instead of database ID (e.g., `/annual-inventory/PID-2026-001`)

- **Header**: PID number, location, date
- **Statistics**: Total items, Pending, Counted, Progress percentage
- **Search**: Filter items by material number, description, rack
- **Rack Address Sorting**: Server-side sort toggle (unsorted / A-Z / Z-A)
  - Desktop: Click "Rack" column header to cycle sort order (icon indicator)
  - Mobile: Dedicated sort button below search bar showing current state
  - Sort is applied server-side for correct pagination across pages
  - Allowed sort columns: `material_number`, `rack_address`, `description`, `status`
- **Pagination**: 50 items per page with navigation controls
- **Table Columns**:
  - Material Number
  - Description
  - Rack Address (sortable)
  - UoM
  - SoH (Stock on Hand)
  - Actual Qty (main input)
  - Status
  - Actions (Submit, Camera)
- **Mobile Card View**:
  - Material number and status in card header
  - Description
  - 3-column grid: Rack, UoM, SoH
  - Actual Qty input with Submit button
  - Image upload in card footer
- **Features**:
  - Individual item submit with confirmation modal
  - Save All button for bulk save
  - Image upload for photo evidence

### Discrepancy Page (`/annual-inventory/discrepancy`)

- **Header Actions**:
  - Download Template - Excel template for bulk import
  - Download Excel - Export discrepancy data with filters
  - Upload Excel - Import discrepancy adjustments
  - Save All Changes - Save modified items
  - Refresh - Reload data
- **Counting Progress Bar**:
  - Shows counted items vs total items ratio
  - Percentage badge (green at 100%, blue otherwise)
  - Visual bar with color transition
  - Counted and Pending counts below bar
  - Updates based on active filters (PID, location, search)
  - Progress ignores `counted_only` filter to show true completion ratio
- **Statistics Cards** (with hover popovers explaining each calculation):
  - Operational Impact: Surplus Items (+), Deficit Items (-)
  - Financial Impact: Surplus Amount, Deficit Amount
  - Nett Discrepancy (signed net across all items)
  - System Amount (total SOH x Price)
  - Stats reflect only counted items (filtered by `counted_only`)
  - Overall Discrepancy Impact % = Nett Discrepancy Amount / System Amount x 100
- **Filters**:
  - Location - Filter by warehouse location
  - PID - Filter by specific PID
  - Type - Surplus/Deficit/Match (discrepancy type)
  - Search - Material number, description, or PID
- **Table Columns**:
  - PID, Material Number, Material Name, Rack, Price
  - SOH (editable)
  - Actual Qty (from counting) with Edit button
  - Initial Gap (Actual - SOH)
  - Outstanding GR (editable, +)
  - Outstanding GI (editable, -)
  - Error Movement (editable)
  - Final Gap (calculated)
  - Final Counted Qty (predicted SOH)
  - Final Amount (calculated)
  - Notes (icon button, opens modal)
- **Drag-to-Scroll**: Table supports horizontal drag-to-scroll with a 5px threshold to preserve text selection
- **Pagination**: 50 items per page
- **Auto-Recalculation**: On page load, all stored discrepancy values are automatically recalculated to ensure consistency between frontend and backend
- **Edit Actual Qty Modal**:
  - Shows material info (number, description, current qty)
  - Displays quantity revision history table
  - Input field for new actual quantity
  - Saves and recalculates discrepancy automatically
- **Unsaved Changes Protection**:
  - Browser beforeunload warning on refresh/close/URL change
  - Confirmation dialog on navigation, pagination, filter change
  - Prevents accidental data loss

### Discrepancy Statistics Architecture

The discrepancy page uses three separate queries for different concerns:

| Query | Purpose | `counted_only` applied? | `discrepancy_type` applied? |
|-------|---------|-------------------------|-----------------------------|
| Main query | Table data (paginated) | Yes | Yes |
| Stats query | Surplus/shortage/match calculations | Yes | No |
| Progress query | Counted vs total progress bar | No | No |

This ensures:
- The table only shows counted items (default behavior)
- Discrepancy stats (surplus/shortage/match) are calculated from counted items only
- The progress bar shows true completion ratio across all items matching base filters

## Bulk Upload Feature

The index page supports uploading multiple Excel files at once for PID import:

### How It Works

1. Click "Upload" button to open upload modal
2. Click to select multiple Excel files
3. Selected files appear in a list with remove buttons
4. Click "Start Upload" to start processing
5. Progress bar shows current file being processed (x of y)
6. Each file shows individual result (success or error)
7. After upload: data, statistics, and locations are refreshed automatically

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

### Worksheet Export (Download Excel)

Uses a template file at `storage/app/templates/worksheet_template.xlsx`. Template files must be committed to git (whitelisted in `storage/app/.gitignore`).

Template structure:
- E6: Group Leader, F6: PIC Name, G6: PIC Input, H6: Group Leader
- D11: Plant (sloc), D12: SLOC (location), D13: PID, D14: Doc Date
- Row 23 onwards: Item data (No, Material Number, Description, Batch, Rack Address, UoM, Final Counted Qty)

**Note**: The "Checking" column (H) exports `final_counted_qty` (predicted SOH) instead of `actual_qty`.

Signature images are read from the database per-PID and embedded as transparent PNG drawings:
- E3: Group Leader signature
- F3: PIC Name signature
- G3: PIC Input signature
- H3: Group Leader signature (duplicate)

**Note**: When loading templates with PhpSpreadsheet, `$spreadsheet->setUnparsedLoadedData([])` must be called after `IOFactory::load()` to prevent the writer from silently dropping newly added Drawing objects.

Supports single PID download (`.xlsx`) or multi-PID download (`.zip`). In zip mode, each PID gets its own signatures embedded.

## Edit Actual Qty Feature

The discrepancy page allows editing the actual quantity with full revision history tracking.

### How It Works

1. Click the pencil icon next to any item's Actual Qty
2. Modal opens showing:
   - Material information (number, description, current qty)
   - Quantity revision history table (all previous entries)
   - Input field for new quantity
3. Enter new quantity and click "Save Changes"
4. System automatically:
   - Updates actual_qty
   - Appends entry to actual_qty_history
   - Recalculates final_discrepancy and final_discrepancy_amount
   - Updates counted_at timestamp

### History Table Columns

| Column | Description |
|--------|-------------|
| # | Revision number |
| Qty | Quantity value at that revision |
| Date/Time | When the count was recorded |

## Unsaved Changes Protection

The discrepancy page protects against accidental data loss.

### Protected Actions

| Action | Behavior |
|--------|----------|
| Browser refresh/close | Native browser "Leave site?" dialog |
| Navigation (links, back) | Custom confirmation dialog via Inertia router |
| Refresh button | Confirmation if unsaved changes |
| Pagination | Confirmation if unsaved changes |
| Filter change | Confirmation if unsaved changes |
| Search | Confirmation if unsaved changes |

### Skipped Confirmation

- Initial page load
- After successful save
- After successful import

## Auto-Assignment (PIC, Group Leader, SLOC)

The `AnnualInventory` model automatically assigns `pic_input`, `group_leader`, and `sloc` based on the `location` column when a PID is saved.

### Location Mapping

| Location | pic_input | group_leader | sloc |
|----------|-----------|--------------|------|
| 2000 - Warehouse Consummable, Chemical, & Raw Material | ADE N | DEDY S | 2000 - Sunter 2 |
| 2300 - Warehouse Consummable & Tools | EKO P | AHMAD J | 1000 - Sunter 1 |
| 5002 - Mach Kaizen | EKO P | AHMAD J | 1000 - Sunter 1 |

### How It Works

- The `AnnualInventory` model has a `saving` event hook in `booted()` that calls `assignPicAndGroupLeader()` before every save
- The method checks `$this->location` against the `$slocMapping` and `$plantMapping` static arrays
- If a match is found, `pic_input`, `group_leader`, and `sloc` are auto-filled
- For existing records, use the "Sync PIC & GL" button on the index page or call `POST /api/annual-inventory/sync-pic-gl`

## Notes/Remarks Feature

The discrepancy page allows adding notes to individual items via a modal dialog.

### How It Works

1. Click the message icon (MessageSquare) next to any item in the table (desktop) or card footer (mobile)
2. Icon is **amber** with a dot indicator when the item has existing notes, **gray** when empty
3. Modal opens showing material info (number, description) and a textarea for notes
4. Click "Save Notes" to persist — calls `PUT /api/annual-inventory/items/{id}` with `{ notes: notesText }`
5. Notes are saved to the `notes` column on `annual_inventory_items`

## Discrepancy Calculation

```
Initial Gap = Actual Qty - SOH
Final Discrepancy = (Actual Qty - SOH) - (Outstanding GR + Outstanding GI + Error Movement)
Final Discrepancy Amount = Final Discrepancy x Unit Price
Final Counted Qty = SOH + Final Discrepancy
```

**Note**:
- Positive Final Discrepancy = Surplus (actual > expected)
- Negative Final Discrepancy = Deficit (actual < expected)
- Outstanding GI values are typically negative
- `Final Counted Qty` is the frontend's `predictedSOH` — it is persisted to the database on every save, refresh, import, and auto-recalculation
- The formula is consistent across all backend calculation points: `insertActualQty`, `bulkUpdateDiscrepancy`, `importDiscrepancyExcel`, `exportDiscrepancyToExcel`, `recalculateDiscrepancy`, and `calculateDiscrepancyStatistics`

## Status Flow

### PID Status

```
Not Checked -> In Progress -> Completed
     |              |            |
  No items    Some items    All items
  counted      counted       verified
```

### Item Status

```
PENDING -> COUNTED -> VERIFIED
    |         |          |
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
    ├── 2026_01_14_105710_create_annual_inventory_items_table.php
    └── 2026_02_11_100000_add_signatures_to_annual_inventories_table.php

resources/js/Pages/
└── AnnualInventory/
    ├── index.vue        # PID list with edit/delete/export, filter-reactive stats
    ├── show.vue         # Counting page with pagination and rack address sorting
    └── discrepancy.vue  # Discrepancy analysis with progress bar, import/export

storage/app/
└── templates/
    ├── worksheet_template.xlsx   # Excel export template
    ├── worksheet_template.xls
    └── discrepancy_import_template.md

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

### API: Get PID with pagination and sorting

```bash
GET /api/annual-inventory/by-pid/PID-2026-001?page=1&per_page=50&search=MAT&sort_by=rack_address&sort_order=asc
```

### API: Get statistics with filters

```bash
GET /api/annual-inventory/statistics?location=Sunter_1&status=Completed
```

### API: Get discrepancy items with filters

```bash
GET /api/annual-inventory/discrepancy?location=Sunter_1&pid_id=1&discrepancy_type=deficit&page=1
```

### API: Import discrepancy data

```bash
POST /api/annual-inventory/discrepancy/import
Content-Type: multipart/form-data

file: [discrepancy.xlsx]
```

### API: Sync PIC & Group Leader

```bash
POST /api/annual-inventory/sync-pic-gl
```

Response: `{ "success": true, "message": "Synced 5 PIDs with pic_input and group_leader", "updated": 5 }`

### API: Recalculate Discrepancy

```bash
POST /api/annual-inventory/recalculate-discrepancy
```

Response: `{ "success": true, "message": "Recalculated 42 items", "updated": 42 }`

### API: Update Item Notes

```bash
PUT /api/annual-inventory/items/123
Content-Type: application/json

{
  "notes": "Checked with warehouse team, confirmed count is correct"
}
```

### API: Save a signature for a PID

```bash
POST /api/annual-inventory/42/signatures
Content-Type: application/json

{
  "role": "pic_name",
  "signature": "data:image/png;base64,iVBORw0KGgo..."
}
```

### API: Delete a signature for a PID

```bash
POST /api/annual-inventory/42/signatures
Content-Type: application/json

{
  "role": "pic_name",
  "signature": null
}
```

### API: Export PIDs to Excel

```bash
GET /api/annual-inventory/export?location=Sunter_1&status=Completed
```

## Sidebar Navigation

```
Annual Inventory (ClipboardList icon)
├── PID List        -> /annual-inventory
└── Discrepancy     -> /annual-inventory/discrepancy
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

6. **Template files missing on production**
   - Ensure `storage/app/templates/` is committed to git
   - Check `storage/app/.gitignore` whitelists `!templates/` and `!templates/**`

7. **Progress bar shows 100% on discrepancy page**
   - The progress query intentionally ignores `counted_only` filter
   - If still 100%, all items matching the current filters have been counted
