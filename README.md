# Advocates Complaints Commission (ACC) of Kenya — AI-Powered Case Management System

**Initiative:** *Accountability & Justice Rebooted*  
A lightweight, full-stack, AI-powered Case Management System (CMS) built for the **Advocates Complaints Commission (ACC) of Kenya** to streamline citizen complaint intake, AI-assisted legal triage, repeat-offender detection, and disciplinary workflow tracking.

---

## 1. Tech Stack

- **Backend:** Pure PHP 8+ (PDO + SQLite, zero heavy frameworks)
- **Database:** SQLite (`data/acc_cms.sqlite`) with automatic schema initialization and seeding
- **Frontend:** HTML5, CSS3, Vanilla JavaScript (ES6+), Bootstrap 5.3, Bootstrap Icons, Chart.js
- **Typography:** `Cormorant Garamond` (headings & case references) + `DM Sans` (UI body)
- **AI Engine:** Google Gemini (`gemini-2.5-flash`) via REST/cURL with an automatic deterministic PHP heuristic fallback engine

---

## 2. Project Structure

```text
case-management-system/
├── api/
│   ├── ai_analyze.php        # Live AI intake assessment & formal case brief generator
│   ├── submit_complaint.php  # Citizen complaint submission & evidence upload
│   └── update_case.php       # Workflow status, officer assignment & investigation notes
├── assets/
│   ├── css/style.css         # Legal integrity theme & responsive/print styles
│   └── js/app.js             # Toast notifications & clipboard helpers
├── includes/
│   ├── auth.php              # Session-based officer authentication
│   ├── db.php                # PDO SQLite connection, schema & 8 seeded Kenyan cases
│   ├── header.php            # Shared navigation bar
│   └── footer.php            # Shared footer
├── data/                     # Auto-generated SQLite database (gitignored)
├── uploads/                  # Uploaded evidence files (gitignored)
├── admin.php                 # ACC Officer & Commissioner Dashboard (desktop table + mobile cards)
├── analytics.php             # Institutional transparency & Chart.js analytics
├── case_view.php             # Case Detail & AI Workbench (Briefs & Notice to Show Cause)
├── config.example.php        # Configuration template (copy to config.php)
├── index.php                 # Public Citizen Portal & Live AI Intake Assistant
├── login.php                 # Officer Portal login
├── logout.php                # Officer logout
├── setup.php                 # Database health check & demo reset (?reset=1)
└── track.php                 # Public Case Tracker (no login required)
```

---

## 3. Core Modules

1. **Public Citizen Portal ([index.php](index.php)):**
   - Lodge a formal complaint with optional **Anonymous / Whistleblower** mode.
   - Upload supporting evidence (`PDF`, `JPG`, `PNG` up to 5MB).
   - **Live AI Intake Assistant:** Real-time misconduct classification, 1–100 urgency scoring, and missing-detail prompts as the citizen types.
2. **Public Case Tracker ([track.php](track.php)):**
   - Citizens enter their `ACC-2026-XXXX` reference number to view a visual progress stepper and activity timeline without logging in.
3. **ACC Officer & Commissioner Dashboard ([admin.php](admin.php)):**
   - Protected by session authentication ([login.php](login.php)).
   - Displays 5 KPIs, search/status filters, AI Urgency Scores, and **Repeat-Offender Flags** when an advocate appears in multiple complaints.
   - Responsive mobile card layout (`d-md-none`) to eliminate horizontal scrolling.
4. **Case Detail & AI Workbench ([case_view.php](case_view.php)):**
   - One-click **AI Case Brief & Notice to Show Cause** generator citing relevant sections of the *Advocates Act (Cap. 16, Laws of Kenya)*, with copy and print support.
   - Update case status (`Received → Preliminary Triage → Under Investigation → Disciplinary Committee → Resolved / Dismissed`), assign Legal Officers, and add internal notes.
5. **Integrity & Analytics Overview ([analytics.php](analytics.php)):**
   - Chart.js visualizations for complaints by misconduct category, monthly intake vs. resolution trends, and top flagged counties.

---

## 4. Setup & Installation (XAMPP)

1. Place the project folder inside `c:\xampp\htdocs\case-management-system`.
2. Copy `config.example.php` to `config.php`:

   ```bash
   cp config.example.php config.php
   ```

3. *(Optional)* Add your `GEMINI_API_KEY` in `config.php` to enable live `gemini-2.5-flash` responses. If left empty, the built-in PHP heuristic AI engine handles all analysis offline.
4. Start **Apache** in XAMPP and open:
   - **Citizen Portal:** `http://localhost/case-management-system/index.php`
   - **Officer Login:** `http://localhost/case-management-system/login.php`
     - Demo Email: `officer@acc.go.ke`
     - Demo Password: `ACC2026!`
5. To reset the database to the 8 seeded Kenyan legal complaint scenarios at any time, visit:
   `http://localhost/case-management-system/setup.php?reset=1`
