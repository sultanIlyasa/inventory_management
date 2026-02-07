# Discrepancy Import Excel Template

## Column Structure (in this exact order):

| Column # | Header Name         | Type    | Description                                    | Example       |
|----------|---------------------|---------|------------------------------------------------|---------------|
| A (1)    | Material Number     | Text    | Unique material identifier                     | RM-2024-001   |
| B (2)    | SoH                 | Integer | Stock on Hand from external system             | 50            |
| C (3)    | Outstanding GR      | Integer | Outstanding Goods Receipt (positive)           | 10            |
| D (4)    | Outstanding GI      | Integer | Outstanding Goods Issue (should be negative)   | -5            |
| E (5)    | Error Moving        | Integer | Error Movement (can be positive or negative)   | 0             |
| F (6)    | Price               | Decimal | Material price per unit                        | 150000.00     |

## Example Excel Content:

```
Material Number | SoH  | Outstanding GR | Outstanding GI | Error Moving | Price
RM-2024-001    | 50   | 0              | 0              | 0            | 150000
PK-2024-055    | 1200 | 0              | -100           | 0            | 5000
EL-2024-889    | 45   | 5              | 0              | 0            | 75000
CH-2024-112    | 10   | 0              | -2             | 0            | 250000
```

## Important Notes:

1. **First row must be the header** (Material Number, SoH, Outstanding GR, etc.)
2. **Material Number must exist** in the materials table, otherwise the row will be skipped
3. **Outstanding GI values** should be negative (e.g., -100 for 100 units issued)
4. **Empty rows** will be skipped automatically
5. **Errors** will be reported after import, but successful rows will still be imported
6. The import will **create new records** for materials that don't have discrepancy data yet
7. The import will **update existing records** for materials that already have discrepancy data

## Partial Updates (NEW!)

You can now update only specific fields by leaving other columns blank:

- **Blank cells** → Field will NOT be updated (existing database value preserved)
- **Zero ("0")** → Field will be set to zero (explicit value)
- **Material Number** → Always required (row skipped if blank)

### Examples:

**Update only SoH:**
```
Material Number | SoH  | O/S GR | O/S GI | Error Moving | Price
RM-2024-001    | 100  |        |        |              |
```
→ Only SoH updated to 100, other fields remain unchanged

**Update multiple fields:**
```
Material Number | SoH  | O/S GR | O/S GI | Error Moving | Price
RM-2024-001    | 200  | 15     |        |              | 175000
```
→ SoH, O/S GR, and Price updated; O/S GI and Error Moving unchanged

**Set field to zero explicitly:**
```
Material Number | SoH  | O/S GR | O/S GI | Error Moving | Price
RM-2024-001    | 50   | 0      |        |              |
```
→ SoH updated to 50, O/S GR set to 0 (not skipped); others unchanged

### Important Notes:

1. **First-time imports (new materials)**: If a material doesn't have a discrepancy record yet, all blank fields will default to 0
2. **Existing materials**: Blank fields preserve current database values
3. **Empty rows**: Rows with only Material Number (all data fields blank) will be skipped
4. **Backwards compatible**: Full row updates still work exactly as before

## Business Logic:

The system calculates:
- **Initial Discrepancy** = Qty Actual (from daily input) - SoH (from Excel)
- **Final Discrepancy** = Initial Discrepancy + Outstanding GR + Outstanding GI + Error Moving

If Final Discrepancy = 0, the discrepancy is "explained" and matched.
If Final Discrepancy ≠ 0, there's still an unexplained discrepancy.
