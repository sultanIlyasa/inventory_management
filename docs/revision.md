# Annual Inventory Feature - Revision

## Index Page (`/annual-inventory`)
- Add edit PID button for editing a singular PID
- Add delete PID button
- Add download PID with actual Qty button (excel)

## Submit Page (`/annual-inventory/{id}`)
- Change the dynamic routing to use PID number instead of ID if possible
- add pagination to the data

## Discrepancy Page (`/annual-inventory/discrepancy`)
- Make it exactly the same with Discrepancy/index
### 1. Download Excel Template Button ✅
**Frontend:** Added purple "Download Template" button with download icon
- **Location:** Top right of dashboard, before "Upload Excel"
- **Functionality:** Downloads a pre-formatted Excel template with example data

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
