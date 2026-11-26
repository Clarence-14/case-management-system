<?php
/**
 * Advocates Complaints Commission (ACC) of Kenya
 * Case Management System - Example Configuration
 *
 * Copy this file to config.php and add your API keys if desired.
 */

define('APP_NAME', 'Advocates Complaints Commission (ACC)');
define('APP_TAGLINE', 'Accountability & Justice Rebooted');
define('DB_PATH', __DIR__ . '/data/acc_cms.sqlite');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Optional AI API Keys (Leave empty to use the built-in deterministic heuristic AI engine)
define('GEMINI_API_KEY', '');
define('OPENAI_API_KEY', '');

// Case Workflow Statuses
define('CASE_STATUSES', [
    'Received',
    'Preliminary Triage',
    'Under Investigation',
    'Disciplinary Committee',
    'Resolved',
    'Dismissed'
]);

// Misconduct Categories
define('MISCONDUCT_CATEGORIES', [
    'Withholding Client Funds',
    'Professional Negligence',
    'Conflict of Interest',
    'Overcharging / Fee Dispute',
    'Failure to Account',
    'Delay / Inaction',
    'Other Misconduct'
]);

// Legal Officers for Assignment
define('LEGAL_OFFICERS', [
    'Unassigned',
    'Adv. Wanjiku Mwangi (Senior Counsel)',
    'Adv. Ochieng Otieno (Investigations)',
    'Adv. Fatuma Hassan (Compliance)',
    'Adv. Kiprop Bett (Disciplinary)'
]);
