# Annual Inventory Feature - Context Summary

## Tech Stack
- **Backend**: Laravel (PHP) with Service/Controller pattern
- **Frontend**: Vue 3 + Inertia.js
- **Storage**: AWS S3 for images
- **Database**: MySQL

## Core Concept
Physical Inventory Documents (PIDs) distribute materials for annual counting. Each PID can have up to 200 items assigned to a location and PIC (Person in Charge).

## Database Tables

### `annual_inventories` (PID header)
- `id`, `pid` (unique), `date`, `status`, `pic_name`, `location`, `timestamps`
- Status: `Not Checked` | `In Progress` | `Completed`

### `annual_inventory_items` (materials in PID)
- `id`, `annual_inventory_id` (FK), `material_number`, `description`, `rack_address`, `unit_of_measure`
- `system_qty`, `soh`, `actual_qty`, `price`
- `outstanding_gr` (+), `outstanding_gi` (-), `error_movement`
- `final_discrepancy`, `final_discrepancy_amount`
- `status`, `counted_by`, `counted_at`, `image_path`, `notes`, `timestamps`
- Item Status: `PENDING` | `COUNTED` | `VERIFIED`

## Discrepancy Formula
```
Initial Gap = Actual Qty - SOH
Final Discrepancy = Actual Qty - SOH + Outstanding GR + Outstanding GI + Error Movement
Final Discrepancy Amount = Final Discrepancy × Price
```

## API Endpoints

### PID Management
- `GET /api/annual-inventory` - List PIDs with pagination
- `GET /api/annual-inventory/search?q=` - Search PIDs
- `GET /api/annual-inventory/{id}` - Get PID with items (by ID)
- `GET /api/annual-inventory/pid/{pid}` - Get by PID number
- `GET /api/annual-inventory/by-pid/{pid}` - Get PID with paginated items (by PID number)
- `PUT /api/annual-inventory/{id}` - Update PID details
- `DELETE /api/annual-inventory/{id}` - Delete PID

### Item Management
- `PUT /api/annual-inventory/items/{itemId}` - Update item
- `POST /api/annual-inventory/items/bulk-update` - Bulk update
- `POST /api/annual-inventory/items/{itemId}/upload-image` - Upload image

### Discrepancy
- `GET /api/annual-inventory/discrepancy` - Get discrepancy items (supports location filter)
- `GET /api/annual-inventory/discrepancy/template` - Download Excel template
- `POST /api/annual-inventory/discrepancy/import` - Import discrepancy Excel
- `POST /api/annual-inventory/discrepancy/bulk-update` - Bulk update SOH/GR/GI/Error
- `GET /api/annual-inventory/pids-dropdown` - PIDs for dropdown

### Import & Export
- `GET /api/annual-inventory/template` - Download PID import Excel template
- `POST /api/annual-inventory/importpid` - Import PIDs from Excel (supports bulk upload)
- `GET /api/annual-inventory/export` - Export PIDs with actual qty to Excel
- `GET /api/annual-inventory/statistics` - Dashboard stats
- `GET /api/annual-inventory/locations` - Unique locations

## Frontend Pages
- `/annual-inventory` - PID list (index.vue)
- `/annual-inventory/{pid}` - Submit/counting page using PID number (show.vue)
- `/annual-inventory/discrepancy` - Discrepancy analysis (discrepancy.vue)

## Key Files
```
app/Http/Controllers/AnnualInventoryController.php
app/Services/AnnualInventoryService.php
app/Models/AnnualInventory.php
app/Models/AnnualInventoryItems.php
resources/js/Pages/AnnualInventory/index.vue
resources/js/Pages/AnnualInventory/show.vue
resources/js/Pages/AnnualInventory/discrepancy.vue
routes/api.php
routes/web.php
```

## Flow
1. Admin uploads Excel (location, pid, material_number)
2. Staff finds assigned PID and counts materials
3. Staff submits actual_qty (show.vue with pagination)
4. Admin reviews discrepancy page, inputs SOH, GR/GI/Error adjustments
5. System calculates final discrepancy

## Page Responsibilities

### index.vue (PID List)
- Bulk upload PIDs from multiple Excel files at once
  - Select multiple files in one dialog
  - Progress bar showing upload progress (x of y files)
  - Per-file results display (success/error)
  - Summary totals (PIDs created, updated, items created)
  - Remove individual files before upload
- Download PID import template
- Edit PID details (pid, location, pic_name)
- Delete PID with confirmation
- Download PIDs with actual qty to Excel
- Navigate to counting page using PID number

### show.vue (Counting Page)
- URL uses PID number: `/annual-inventory/PID-2026-001`
- Paginated items (50 per page)
- Staff inputs `actual_qty` only
- Submit individual items with confirmation
- Save All for bulk save
- Image upload for photo evidence

### discrepancy.vue (Admin Analysis)
- Download Excel template for bulk import
- Upload Excel to import discrepancy data
- Filter by: Location, PID, Discrepancy type
- Edit: `SOH`, `Outstanding GR`, `Outstanding GI`, `Error Movement`
- View calculated final discrepancy and amounts
- Pagination (50 items per page)

## Excel Formats

### PID Import (3 columns)
Location | PID | Material Number

### Discrepancy Template (7 columns)
Material Number | SOH | Outstanding GR | Outstanding GI | Error Movement | Price | Location

### Export (17 columns)
PID | Location | PIC | Status | Material Number | Description | Rack | UoM | System Qty | SOH | Actual Qty | Discrepancy | Price | Discrepancy Amount | Counted By | Counted At | Item Status

## Sidebar Menu
Annual Inventory (ClipboardList icon) with children:
- PID List → `annual-inventory.index`
- Discrepancy → `annual-inventory.discrepancy`
