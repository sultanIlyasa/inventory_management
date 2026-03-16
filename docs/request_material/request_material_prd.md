# PRD: Material Delivery Request Feature

**Product**: Manufacturing / Warehouse Operations App
**Author**: Sultan
**Status**: Draft
**Last Updated**: March 2026

---

## 1. Overview

### Problem Statement

Field workers (MPs) currently lack a structured, trackable way to request material deliveries from vendors. Additionally, vendors sometimes need to proactively push a delivery to the warehouse — with no standardized way to submit or get that approved. Both flows are currently informal, difficult to trace, and have no visibility into status.

### Proposed Solution

A cross-platform material delivery request system spanning two surfaces:

- **Website** — where MPs (Material Planners / requesters) submit requests and view a recap of all requests
- **WhatsApp Bot (via Openclaw)** — where approvals, vendor coordination, and delivery confirmations are handled through an automated bot

### Goals

- Provide a structured, web-based intake for material delivery requests (MP-initiated)
- Allow vendors to submit delivery requests directly via WhatsApp bot (vendor-initiated)
- Automate vendor contact and follow-up via a WhatsApp bot (Openclaw)
- Route all requests through an appropriate approval chain before fulfillment
- Give requesters a consolidated view of all request statuses on the website
- Support advance delivery flagging with daily follow-up automation

### Non-Goals

- Inventory management or stock-level tracking
- Direct vendor portal (vendor interaction is via WhatsApp only)
- Payment or invoicing workflows
- Mobile app (website is the primary interface for requesters in V1)

---

## 2. Platforms & System Components

| Platform | Role |
|----------|------|
| **Website** | Request submission (MP), document system check, recap dashboard |
| **WhatsApp Bot (Openclaw)** | Approval routing, vendor contact, vendor confirmation, advance delivery follow-up |

---

## 3. User Personas

### MP (Material Planner / Requester)
- Submits material delivery requests via the website
- Maintains the vendor contact list in the system
- Monitors all request statuses via the website recap dashboard

### Group Leader (GL)
- First-level approver for **all** requests (both standard and advance delivery)
- Approves via WhatsApp bot

### Section Head
- Second-level approver, required only for **Advance Delivery** requests
- Approves via WhatsApp bot

### Vendor
- Receives bot-initiated contact via WhatsApp for MP-initiated requests
- Can also **proactively submit a delivery request** via the WhatsApp bot (vendor-initiated flow)
- Confirms, reschedules, or rejects delivery requests via WhatsApp

---

## 4. End-to-End Flow

### Stage 1 — Website: Request Submission & DN System Check

1. **MP submits a Material Delivery Request** via the website form, specifying one or more materials and their requested quantities
2. The system performs an automated **Delivery Note (DN) Check** per material line:
   - Every morning, an RPA system uploads Delivery Notes from an external source into the system
   - Each DN contains: DN Number, material info (one DN can cover multiple materials), and remaining deliverable quantity per material
   - The check validates two conditions **per material**:
     - **DN exists** for the material
     - **Remaining quantity on the DN ≥ requested quantity**
3. Each material line is independently routed based on its DN check result — a single request can produce both standard and advance delivery material lines simultaneously

**DN Check Outcomes (per material line):**

| Condition | Result |
|-----------|--------|
| DN exists AND remaining quantity is sufficient | ✅ Standard path → GL approval only |
| DN does not exist OR remaining quantity is insufficient | ⚠️ Advance Delivery → MP must provide PR Number; GL + Section Head approval required; delivery proceeds normally but incomplete document reminder is shown on the website until documents are complete |

> The MP is prompted to enter a **PR Number per failing material line** before the request can be submitted. The missing DN does not block delivery — the transaction proceeds through the full flow with a persistent document follow-up reminder.

**Data model note:** Each material line in a request is stored and tracked as an independent transaction row. A request with 5 materials produces 5 transaction rows in the recap dashboard, each with its own status, approval state, and delivery outcome.

---

### Stage 2 — WhatsApp Bot (Openclaw): Approval Routing

**Path A — Standard (DN valid & quantity sufficient)**
- Bot sends the material line to2 the **Group Leader (GL)** for approval
- GL approves → bot proceeds to **Bot Contact Vendor**

**Path B — Advance Delivery (no DN or insufficient quantity)**
- Bot sends the material line to the **Group Leader (GL)** for approval
- GL approves → bot escalates to **Section Head** for approval
- Section Head approves → bot proceeds to **Bot Contact Vendor**
- Bot automatically sends daily follow-up messages for this material line until vendor confirms

> GL approval is required for all material lines. Section Head approval is an additional gate exclusive to Advance Delivery material lines.

---

### Stage 3 — WhatsApp Bot: Vendor Coordination

1. **Bot contacts the vendor** via WhatsApp with delivery details — this applies to both standard and Advance Delivery material lines
2. Vendor responds with one of three outcomes:

| Vendor Response | Outcome |
|----------------|---------|
| **Confirm** | Delivery is confirmed; transaction status updated |
| **Reschedule** | Vendor proposes new delivery time; transaction rescheduled (max 3 times) |
| **Rejected** | Vendor cannot fulfill; transaction marked as rejected |

3. For Advance Delivery material lines, the bot continues sending **daily follow-up messages** reminding the relevant parties to complete the outstanding documents — this runs in parallel to the normal delivery flow and does not block it

---

### Stage 4 — Website: Delivery & Document Follow-up

- Advance Delivery transactions display a persistent **incomplete document reminder** on the website recap dashboard until documents are marked complete
- Warehouse members can receive and execute the delivery normally regardless of document status
- The reminder is dismissed once the required documents are confirmed complete

---

---

### Stage 5 — Website: Recap Dashboard

- All material delivery transactions (across all statuses) appear on the recap dashboard
- All warehouse staff can review the full list, current status, and document completion state of each transaction

---

## 4B. Vendor-Initiated Delivery Request Flow

This is a separate, simpler flow where the vendor proactively submits a delivery request via the WhatsApp bot. It follows the same downstream flow as the MP-initiated flow but without the website submission step and DN system check.

### Stage 1 — WhatsApp Bot: Vendor Submits Request

1. Vendor messages the **WhatsApp bot** to initiate a delivery request
2. Bot collects shared request details:
   - Proposed delivery date
   - Delivery location / zone
   - Notes / special instructions (optional)
3. Bot then collects **per material line**: material type & description, quantity (with unit), DN Number
4. Bot prompts the vendor to add additional material lines until they confirm the submission is complete
5. Bot summarizes the full request and forwards it to the GL for approval

### Stage 2 — WhatsApp Bot: GL Approval

- Bot sends the vendor's request to the **Group Leader (GL)** for approval
- GL approves → delivery confirmed back to vendor via bot; one transaction row per material line created in the system
- GL rejects → vendor is notified via bot; transactions closed and **rejection is surfaced on the website recap dashboard** for warehouse staff visibility

> Vendor-initiated requests go through **GL approval only** — no Section Head approval, no DN check, no Advance Delivery path.

### Stage 3 — Website: Recap Dashboard

- Vendor-initiated transactions appear in the same recap dashboard as MP-initiated transactions
- Identifiable via an **Initiator** column: "MP" or "Vendor"
- Same status tracking, filters, and XLSX export apply

---

## 5. Feature Requirements

### 5.1 Website — Request Submission Form

**Required fields:**
- Material type & description (repeatable — MP can add multiple material lines per request)
- Quantity (with unit) — per material line
- **Requested delivery date & slot** — MP selects a delivery date and an available time slot (AM or PM). Slot availability is shown in real time based on remaining capacity for that day and session:
  - **Standard requests**: minimum 3 working days from submission date; system blocks selection of non-compliant dates
  - **Urgent requests**: if the selected date does not comply with the 3-working-day rule, the material line is **automatically flagged as Advance Delivery**; the MP is informed of the flag and must provide a PR Number; GL must explicitly authorize the urgency as part of the approval step
  - Slots are displayed as available or full; MP cannot select a full slot
- Delivery location / zone
- Attachments (photos, documents — optional supporting docs)
- Notes / special instructions (optional)
- **PR Number** — conditionally required per material line; shown only for lines where DN check fails

**Behavior:**
- On submission, system automatically runs the DN Check per material line
- Material lines that pass proceed to the standard GL approval path
- Material lines that fail are flagged as Advance Delivery; MP is prompted to enter a PR Number for each failing line before the request can be finalized
- If the RPA upload has not run or failed that morning, the system allows submission but displays a warning to the MP that DN data may not be current
- MP receives a confirmation with a request reference ID once submitted
- Each material line is created as an independent transaction record
- Rejected requests cannot be edited; MP must create a new request

### 5.2 Website — DN System Check (Automated)

- RPA uploads Delivery Notes from an external source every morning
- Each DN record contains: DN Number, associated materials, and remaining deliverable quantity per material
- On each request submission, the system checks per material line:
  1. Does a DN exist for the requested material?
  2. Is the remaining quantity on that DN ≥ the requested quantity?
- DN remaining quantity is used as a **reference point** — it reflects consumption from the external website and is read-only within this system (not decremented by this system)
- Check result is recorded per material line and determines its approval path
- If the RPA upload has not completed for the day, submission is permitted with a visible warning to the MP; the previous day's DN data is used as the reference for the check

### 5.3 Website — Delivery Slot Management

Each working day is divided into two sessions: **AM** and **PM**. Each session has a fixed maximum number of delivery slots, separated by material type. Slot capacity is configured per day and enforced at the time of submission.

**Slot capacity (per session per day):**

| Session | Consumable Slots | Raw Material & Oli Slots |
|---------|-----------------|--------------------------|
| AM | 6 | 3 |
| PM | 4 | 2 |
| **Daily Total** | **10** | **5** |

**Processing time reference (informational, not system-enforced in V1):**

| Material Category | Estimated Processing Time |
|-------------------|--------------------------|
| Consumable | ~45 minutes per slot |
| Raw Material & Oli | ~90 minutes per slot |

**Slot behavior:**
- When an MP or vendor selects a delivery date, the system shows slot availability per session (AM/PM) and material category
- A slot is consumed when the delivery is confirmed (GL approved + vendor confirmed); it is released if the transaction is rejected or cancelled
- If all slots for a session are full, the MP must select a different session or date
- Rescheduled deliveries by the vendor re-enter the slot selection — the original slot is released and a new slot must be available on the rescheduled date/session
- Slot counts and remaining availability are visible on the recap dashboard



- Bot sends structured approval messages to the GL for all material lines
- For Advance Delivery material lines, bot additionally routes to Section Head after GL approval
- All approvals and scheduling confirmations are handled exclusively via WhatsApp bot (no in-app approval UI)
- Bot reads and writes to the database to track approval state and scheduling per material line
- All approval actions are logged with timestamps
- Bot triggers daily follow-up messages for Advance Delivery material lines until vendor confirmation is received
- If the GL rejects a material line, the transaction is **immediately closed**; the MP must create a new request if they wish to resubmit
- For urgent Advance Delivery material lines, the GL's WhatsApp approval message must include an **explicit urgency acknowledgment**; the bot will prompt the GL for this and will not proceed to vendor contact until it is received

### 5.4 WhatsApp Bot — Vendor Contact & Confirmation

- Bot is triggered via **REST webhook** to send a standardized delivery request message to the vendor via WhatsApp, per material line
- All vendor confirmations and delivery scheduling are handled exclusively via WhatsApp bot
- Vendor contact list is maintained in the system by MPs
- Bot captures vendor response (Confirm / Reschedule / Rejected) and writes outcome to the database
- **Reschedule**: bot updates the delivery date in the database for that material line transaction; vendor may reschedule a maximum of **3 times**
  - If the vendor proposes more than 3 reschedules, the bot stops mediating and **sends a notification to the MP requester's WhatsApp** informing them to contact the vendor directly for further negotiation
- **Rejected**: transaction status updated; MP can view updated status on the recap dashboard
- Advance Delivery material lines: bot continues daily follow-up via WhatsApp until documents are confirmed complete — this runs in parallel to delivery and does not block it

### 5.5 Website — Incomplete Document Reminder

- All Advance Delivery transactions display a persistent **incomplete document reminder** banner or indicator on the recap dashboard
- The reminder is visible to all warehouse staff
- The reminder is dismissed once the required documents are confirmed as complete (manual action by MP or authorized staff)
- Delivery and warehouse receipt of materials are **not blocked** by the outstanding document reminder

### 5.6 WhatsApp Bot — Vendor-Initiated Delivery Request

- Vendor can initiate a delivery request at any time by messaging the WhatsApp bot
- Bot guides the vendor through a structured conversation to collect delivery details **per material line** — vendors can submit multiple materials and delivery numbers in a single request:
  - Per material line: material type & description, material category (Consumable / Raw Material & Oli), quantity + unit, DN Number
  - Per request (shared): proposed delivery date, preferred session (AM / PM), delivery zone, optional notes
- Bot checks slot availability for the requested date and session; if the session is full, bot informs the vendor and asks them to choose an alternative
- Bot allows the vendor to add additional material lines before finalizing submission
- Bot validates that all required fields are provided before forwarding to the GL
- Bot forwards the completed request to the GL for approval via WhatsApp
- GL approves → bot notifies the vendor of approval; transactions created in the system (one row per material line) with status **Vendor Confirmed**
- GL rejects → bot notifies the vendor; transactions closed; **rejection is also surfaced on the website recap dashboard** for warehouse staff visibility
- No DN check, PR Number, Section Head approval, or Advance Delivery flag applies to vendor-initiated requests

### 5.7 Website — Recap Dashboard

- All warehouse staff (MPs, Group Leaders, Section Heads) have access
- Data is displayed **per material line transaction** — one request with 5 materials shows as 5 rows
- **Default view**: all transactions across all MPs and vendors, sorted by submission date descending
- **Columns**: Initiator (MP / Vendor), Request ID, Material, Material Category, Quantity, Delivery Zone, DN Number (if applicable), PR Number (if advance delivery), Delivery Type (Regular / Advance), Submission Date, Requested Delivery Date, Delivery Session (AM / PM), Slot Number, DN Check Result, GL Approval, Section Head Approval (if applicable), Vendor Status, Reschedule Count, Confirmed Delivery Date, Document Status (Complete / Incomplete — Advance Delivery only)
- **Filters available**: Initiator (MP / Vendor), Advance Delivery flag, Delivery zone / location, Requester (MP), Date range, Delivery session (AM / PM), Status, GL Approval status, Vendor response status
- **Export**: transaction data exportable to XLSX; attachments excluded. Export columns: Initiator, Delivery Type (Regular / Advance), Issuer (MP name or Vendor name), DN Number, Material Number, Material Category, Delivered Quantity, Requested Delivery Date, Delivery Session, Actual Delivery Date. Export respects active filters; full unfiltered export also available
- Each row expandable to show full transaction details and attachments

---

## 6. Transaction Status Model

Statuses apply to each material line transaction independently. Advance Delivery is a **flag**, not a separate status — Advance Delivery transactions move through the same statuses as standard ones, with an additional Document Status tracked in parallel.

| Status | Description |
|--------|-------------|
| **Pending Approval** | Awaiting GL approval (and Section Head for MP-initiated Advance Delivery) |
| **Approved** | All approvals cleared; vendor contact initiated |
| **Vendor Confirmed** | Vendor confirmed delivery |
| **Rescheduled** | Vendor proposed a new delivery date (up to 3 times) |
| **Awaiting Negotiation** | Vendor exceeded 3 reschedules; MP notified to negotiate directly |
| **Rejected** | GL rejected or vendor rejected; transaction closed |
| **Delivered** | Delivery completed and received by warehouse |

**Document Status (Advance Delivery transactions only):**

| Document Status | Description |
|----------------|-------------|
| **Incomplete** | Required documents not yet confirmed; reminder active on dashboard |
| **Complete** | Documents confirmed; reminder dismissed |

---

## 7. Technical Requirements

### Integrations

| Integration | Purpose |
|-------------|---------|
| **Openclaw (WhatsApp Bot)** | Approval routing, vendor messaging, advance delivery follow-up — triggered via REST webhook |
| **Document storage** | Secure storage for attachments linked to each request |
| **Website backend** | Request CRUD, status sync from bot events, recap dashboard data |

### Data Schema

**Request (parent record)**

| Field | Type | Required |
|-------|------|----------|
| Request ID | UUID | Yes |
| Initiator | Enum: MP / Vendor | Yes |
| Requester (MP) ID | String | Conditional (MP-initiated only) |
| Delivery zone | String | Yes |
| Requested delivery date | ISO 8601 date | Yes |
| Notes | String (max 500 chars) | No |
| Attachments | Array of file refs (max 5, 10MB each) | No |
| Submission timestamp | ISO 8601 | Yes |

**Material Line Transaction (child record, one per material)**

| Field | Type | Required |
|-------|------|----------|
| Transaction ID | UUID | Yes |
| Request ID (parent) | UUID | Yes |
| Material type | String | Yes |
| Material category | Enum: Consumable / Raw Material & Oli | Yes |
| Quantity + unit | String | Yes |
| Delivery session | Enum: AM / PM | Yes |
| Slot number | Integer | Yes |
| DN Number | String | Conditional (if DN check passes) |
| DN remaining quantity (at time of check) | Number | Yes |
| DN check result | Enum: Pass / Fail | Yes |
| Advance delivery flag | Boolean | Yes |
| Urgent flag (auto-set if < 3 working days) | Boolean | Yes |
| PR Number | String | Conditional (required if advance delivery) |
| Approval status (GL) | Enum: Pending / Approved / Rejected | Yes |
| Approval status (Section Head) | Enum: Pending / Approved / Rejected / N/A | Yes |
| Vendor contact triggered | Boolean | Yes |
| Vendor response | Enum: Confirmed / Rescheduled / Rejected / Pending | Yes |
| Reschedule count | Integer (0–3) | Yes |
| Redirected to MP flag | Boolean | Yes |
| Document status | Enum: Complete / Incomplete / N/A | Yes |
| Confirmed/rescheduled delivery date | ISO 8601 date | No |
| Current status | Enum (see §6) | Yes |

### Performance

- Form submission: < 2s response (P95)
- Document check: < 5s (P95)
- Bot message delivery: < 30s from trigger event (P95)
- Recap dashboard load: < 2s (P95)

---

## 8. Out of Scope (V1)

- Mobile app for requesters
- Automated inventory deduction upon delivery
- Vendor web portal (vendor interaction is via WhatsApp bot only)
- SLA enforcement or escalation timers
- Integration with ERP/procurement systems

---

## 9. Success Metrics

| Metric | Target |
|--------|--------|
| Request form completion rate | ≥ 85% |
| Document check pass rate (first submission) | ≥ 75% |
| Approval turnaround time (both tracks) | ≤ 24 hours median |
| Vendor confirmation rate (first contact) | ≥ 80% |
| Requests visible in recap dashboard within 1 min of submission | ≥ 99% |
| Reduction in untracked / informal delivery requests | ≥ 70% vs. baseline |

---

## 10. Open Questions

All questions resolved. No outstanding open questions at this time.

---

---

## 11. Timeline & Milestones

| Milestone | Target |
|-----------|--------|
| Flow & requirements sign-off | TBD |
| Openclaw integration spec finalized | TBD |
| Website design mockups reviewed | TBD |
| Engineering kickoff | TBD |
| Internal QA complete | TBD |
| Pilot rollout | TBD |
| Full production release | TBD |

---

*This document is a living draft. All requirements are subject to change pending stakeholder review.*
