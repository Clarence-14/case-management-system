<?php
/**
 * Database Connection & Auto-Initialization (SQLite via PDO)
 */
require_once __DIR__ . '/../config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dataDir = dirname(DB_PATH);
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $isNew = !file_exists(DB_PATH);
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    if ($isNew) {
        initSchema($pdo);
        seedDatabase($pdo);
    } else {
        // Ensure tables exist even if file was empty
        $check = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='complaints'")->fetch();
        if (!$check) {
            initSchema($pdo);
            seedDatabase($pdo);
        } else {
            migrateSchema($pdo);
        }
    }

    return $pdo;
}

function initSchema(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS complaints (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ref_number TEXT UNIQUE NOT NULL,
            is_anonymous INTEGER DEFAULT 0,
            complainant_name TEXT NOT NULL,
            complainant_email TEXT,
            complainant_phone TEXT,
            advocate_name TEXT NOT NULL,
            lsk_number TEXT,
            incident_date TEXT,
            county TEXT DEFAULT 'Nairobi',
            category TEXT NOT NULL,
            ai_category TEXT,
            ai_urgency_score INTEGER DEFAULT 50,
            ai_summary TEXT,
            narrative TEXT NOT NULL,
            original_narrative TEXT,
            detected_language TEXT DEFAULT 'English',
            recommended_division TEXT DEFAULT 'Review & Investigation',
            receipt_ocr_json TEXT,
            advocate_response TEXT,
            ai_verified_by TEXT,
            amount_involved REAL DEFAULT 0,
            evidence_path TEXT,
            status TEXT DEFAULT 'Received',
            assigned_officer TEXT DEFAULT 'Unassigned',
            step_completed_by TEXT,
            step_completed_at TEXT,
            created_at TEXT DEFAULT (datetime('now', 'localtime')),
            updated_at TEXT DEFAULT (datetime('now', 'localtime'))
        );

        CREATE TABLE IF NOT EXISTS case_notes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            complaint_id INTEGER NOT NULL,
            author TEXT NOT NULL,
            note_type TEXT DEFAULT 'Note',
            content TEXT NOT NULL,
            created_at TEXT DEFAULT (datetime('now', 'localtime')),
            FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS acc_staff (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lsk_number TEXT UNIQUE NOT NULL,
            full_name TEXT NOT NULL,
            role TEXT NOT NULL,
            is_active INTEGER DEFAULT 1
        );
    ");
    seedAccStaff($pdo);
    $pdo->exec("UPDATE acc_staff SET role = 'Senior Counsel' WHERE role LIKE 'Senior Counsel%'");
    $pdo->exec("UPDATE acc_staff SET role = 'Commission Secretary' WHERE role LIKE 'Commission Secretary%'");
}

function migrateSchema(PDO $pdo): void {
    $cols = $pdo->query("PRAGMA table_info(complaints)")->fetchAll(PDO::FETCH_COLUMN, 1);
    $needed = [
        'original_narrative' => "TEXT",
        'detected_language' => "TEXT DEFAULT 'English'",
        'recommended_division' => "TEXT DEFAULT 'Review & Investigation'",
        'receipt_ocr_json' => "TEXT",
        'advocate_response' => "TEXT",
        'ai_verified_by' => "TEXT",
        'step_completed_by' => "TEXT",
        'step_completed_at' => "TEXT"
    ];
    foreach ($needed as $col => $def) {
        if (!in_array($col, $cols, true)) {
            $pdo->exec("ALTER TABLE complaints ADD COLUMN {$col} {$def}");
        }
    }
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS acc_staff (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lsk_number TEXT UNIQUE NOT NULL,
            full_name TEXT NOT NULL,
            role TEXT NOT NULL,
            is_active INTEGER DEFAULT 1
        );
    ");
    seedAccStaff($pdo);
}

function seedAccStaff(PDO $pdo): void {
    $count = (int)$pdo->query("SELECT COUNT(*) FROM acc_staff")->fetchColumn();
    if ($count > 0) {
        return;
    }
    $stmt = $pdo->prepare("INSERT INTO acc_staff (lsk_number, full_name, role, is_active) VALUES (?, ?, ?, 1)");
    $stmt->execute(['LSK/2014/01001', 'Adv. Wanjiku Mwangi',        'Senior Counsel']);
    $stmt->execute(['LSK/2012/00450', 'Commissioner Ochieng Otieno', 'Commission Secretary']);
    $stmt->execute(['LSK/2016/05512', 'Adv. Ochieng Otieno',         'Investigations Officer']);
    $stmt->execute(['LSK/2018/07234', 'Adv. Fatuma Hassan',           'Compliance Officer']);
    $stmt->execute(['LSK/2015/04891', 'Adv. Kiprop Bett',             'Disciplinary Officer']);
}

function verifyAccStaff(string $lskNumber): ?array {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM acc_staff WHERE UPPER(TRIM(lsk_number)) = UPPER(TRIM(?)) AND is_active = 1");
    $stmt->execute([$lskNumber]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function seedDatabase(PDO $pdo): void {
    $count = (int)$pdo->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
    if ($count > 0) {
        return;
    }

    $seedCases = [
        [
            'ref_number' => 'ACC-2026-1001',
            'is_anonymous' => 0,
            'complainant_name' => 'Grace Muthoni Kariuki',
            'complainant_email' => 'grace.kariuki@example.co.ke',
            'complainant_phone' => '+254 712 345 678',
            'advocate_name' => 'Adv. Kamau Njoroge',
            'lsk_number' => 'LSK/2014/04821',
            'incident_date' => '2026-06-15',
            'county' => 'Nairobi',
            'category' => 'Withholding Client Funds',
            'ai_category' => 'Withholding Client Funds',
            'ai_urgency_score' => 94,
            'ai_summary' => 'Complainant alleges advocate withheld KES 3,450,000 from a completed Kilimani land sale conveyance for over 5 months.',
            'narrative' => 'I engaged Adv. Kamau Njoroge on 15th June 2026 to handle the sale of my late father\'s plot in Kilimani, Nairobi (LR No. 209/4512). The buyer paid the full purchase price of KES 3,450,000 into the advocate\'s client account on 20th July 2026. Despite title transfer completing in August 2026, the advocate has refused to remit my proceeds, citing vague bank delays and ignoring my calls.',
            'amount_involved' => 3450000,
            'status' => 'Disciplinary Committee',
            'assigned_officer' => 'Adv. Wanjiku Mwangi (Senior Counsel)',
            'created_at' => '2026-09-10 09:30:00'
        ],
        [
            'ref_number' => 'ACC-2026-1002',
            'is_anonymous' => 0,
            'complainant_name' => 'Hassan Omar Bakari',
            'complainant_email' => 'hassan.bakari@example.co.ke',
            'complainant_phone' => '+254 722 891 034',
            'advocate_name' => 'Adv. Salim Mwawasi',
            'lsk_number' => 'LSK/2018/09112',
            'incident_date' => '2026-08-02',
            'county' => 'Mombasa',
            'category' => 'Delay / Inaction',
            'ai_category' => 'Delay / Inaction',
            'ai_urgency_score' => 68,
            'ai_summary' => 'Advocate allegedly failed to file a defence in a Mombasa commercial suit after receiving KES 180,000 retainer, leading to default judgment.',
            'narrative' => 'In August 2026, I paid Adv. Salim Mwawasi a legal fee of KES 180,000 to represent my logistics company in Mombasa CMCC No. 412 of 2026. He failed to enter appearance or file a defence within the statutory 14 days, resulting in an ex-parte judgment against us and auctioneers threatening attachment.',
            'amount_involved' => 180000,
            'status' => 'Under Investigation',
            'assigned_officer' => 'Adv. Ochieng Otieno (Investigations)',
            'created_at' => '2026-09-18 14:15:00'
        ],
        [
            'ref_number' => 'ACC-2026-1003',
            'is_anonymous' => 0,
            'complainant_name' => 'Beatrice Chebet Korir',
            'complainant_email' => 'b.korir@example.co.ke',
            'complainant_phone' => '+254 733 560 219',
            'advocate_name' => 'Adv. Kamau Njoroge',
            'lsk_number' => 'LSK/2014/04821',
            'incident_date' => '2026-09-01',
            'county' => 'Nakuru',
            'category' => 'Failure to Account',
            'ai_category' => 'Failure to Account',
            'ai_urgency_score' => 86,
            'ai_summary' => 'Repeat-offender alert: Advocate allegedly collected KES 850,000 in estate succession rent proceeds without rendering accounts.',
            'narrative' => 'Adv. Kamau Njoroge was appointed to administer our family\'s estate succession cause in Nakuru High Court (Succession Cause No. 88 of 2025). Since September 2026, he has collected monthly rental income totaling KES 850,000 from our Milimani apartments but has failed to provide a statement of account or distribute shares to beneficiaries.',
            'amount_involved' => 850000,
            'status' => 'Preliminary Triage',
            'assigned_officer' => 'Adv. Fatuma Hassan (Compliance)',
            'created_at' => '2026-10-02 11:05:00'
        ],
        [
            'ref_number' => 'ACC-2026-1004',
            'is_anonymous' => 1,
            'complainant_name' => 'Anonymous Whistleblower',
            'complainant_email' => '',
            'complainant_phone' => '',
            'advocate_name' => 'Adv. Brian Odhiambo',
            'lsk_number' => 'LSK/2016/06340',
            'incident_date' => '2026-10-12',
            'county' => 'Kisumu',
            'category' => 'Conflict of Interest',
            'ai_category' => 'Conflict of Interest',
            'ai_urgency_score' => 82,
            'ai_summary' => 'Whistleblower reports advocate simultaneously representing both vendor and purchaser in a disputed Kisumu lakeside parcel transfer.',
            'narrative' => 'I am reporting a serious conflict of interest involving Adv. Brian Odhiambo in Kisumu. He previously acted for the family estate in drafting a caveat on Parcel Kisumu/Municipality/Block 4/119, and is now acting for a private developer attempting to lift the same caveat using confidential family documents.',
            'amount_involved' => 0,
            'status' => 'Under Investigation',
            'assigned_officer' => 'Adv. Kiprop Bett (Disciplinary)',
            'created_at' => '2026-10-15 16:40:00'
        ],
        [
            'ref_number' => 'ACC-2026-1005',
            'is_anonymous' => 0,
            'complainant_name' => 'Samuel Kipchoge Ruto',
            'complainant_email' => 'samuel.ruto@example.co.ke',
            'complainant_phone' => '+254 701 234 890',
            'advocate_name' => 'Adv. Mercy Jepchirchir',
            'lsk_number' => 'LSK/2020/11205',
            'incident_date' => '2026-07-20',
            'county' => 'Uasin Gishu',
            'category' => 'Overcharging / Fee Dispute',
            'ai_category' => 'Overcharging / Fee Dispute',
            'ai_urgency_score' => 54,
            'ai_summary' => 'Client disputes advocate\'s demand of KES 420,000 above the Advocates Remuneration Order scale for a simple lease agreement.',
            'narrative' => 'Adv. Mercy Jepchirchir drafted a standard 5-year commercial lease in Eldoret for my hardware shop. Although we agreed on KES 65,000, she withheld the executed lease and demanded KES 420,000, which is far beyond the Advocates Remuneration Order.',
            'amount_involved' => 420000,
            'status' => 'Resolved',
            'assigned_officer' => 'Adv. Wanjiku Mwangi (Senior Counsel)',
            'created_at' => '2026-08-12 10:20:00'
        ],
        [
            'ref_number' => 'ACC-2026-1006',
            'is_anonymous' => 0,
            'complainant_name' => 'Esther Nyambura Maina',
            'complainant_email' => 'esther.maina@example.co.ke',
            'complainant_phone' => '+254 720 445 112',
            'advocate_name' => 'Adv. Peter Ndung\'u',
            'lsk_number' => 'LSK/2012/03109',
            'incident_date' => '2026-10-28',
            'county' => 'Kiambu',
            'category' => 'Professional Negligence',
            'ai_category' => 'Professional Negligence',
            'ai_urgency_score' => 76,
            'ai_summary' => 'Advocate allegedly failed to conduct an official land search before advising client to pay KES 1,200,000 deposit on a fraudulent title.',
            'narrative' => 'I retained Adv. Peter Ndung\'u to conduct due diligence for a residential plot in Ruaka, Kiambu. He assured me the title was clean without conducting an official ArdhiSasa search. I paid KES 1,200,000 deposit, only to discover the title deed was a forgery and the seller vanished.',
            'amount_involved' => 1200000,
            'status' => 'Preliminary Triage',
            'assigned_officer' => 'Unassigned',
            'created_at' => '2026-11-01 08:50:00'
        ],
        [
            'ref_number' => 'ACC-2026-1007',
            'is_anonymous' => 0,
            'complainant_name' => 'David Mutua Musyoka',
            'complainant_email' => 'd.musyoka@example.co.ke',
            'complainant_phone' => '+254 718 990 321',
            'advocate_name' => 'Adv. Faith Kalondu',
            'lsk_number' => 'LSK/2019/10455',
            'incident_date' => '2026-11-10',
            'county' => 'Machakos',
            'category' => 'Withholding Client Funds',
            'ai_category' => 'Withholding Client Funds',
            'ai_urgency_score' => 91,
            'ai_summary' => 'Insurance compensation cheque of KES 960,000 for road traffic accident allegedly banked by advocate without remitting to injured claimant.',
            'narrative' => 'Following my road traffic accident claim in Machakos High Court, the insurance company issued a settlement cheque of KES 960,000 to my advocate, Adv. Faith Kalondu, on 10th November 2026. She has refused to release my compensation despite repeated hospital bills.',
            'amount_involved' => 960000,
            'status' => 'Received',
            'assigned_officer' => 'Unassigned',
            'created_at' => '2026-11-20 13:10:00'
        ],
        [
            'ref_number' => 'ACC-2026-1008',
            'is_anonymous' => 0,
            'complainant_name' => 'Amina Mohamed Abdi',
            'complainant_email' => 'amina.abdi@example.co.ke',
            'complainant_phone' => '+254 725 678 441',
            'advocate_name' => 'Adv. George Wachira',
            'lsk_number' => 'LSK/2015/05590',
            'incident_date' => '2026-05-14',
            'county' => 'Nairobi',
            'category' => 'Delay / Inaction',
            'ai_category' => 'Delay / Inaction',
            'ai_urgency_score' => 45,
            'ai_summary' => 'Advocate delayed filing appeal record in Court of Appeal; matter was subsequently settled out of court.',
            'narrative' => 'Adv. George Wachira delayed compiling the record of appeal for 4 months in Nairobi. However, after we engaged another counsel, the parties reached an amicable out-of-court settlement and my original documents were returned.',
            'amount_involved' => 50000,
            'status' => 'Resolved',
            'assigned_officer' => 'Adv. Fatuma Hassan (Compliance)',
            'created_at' => '2026-06-25 15:00:00'
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO complaints (
            ref_number, is_anonymous, complainant_name, complainant_email, complainant_phone,
            advocate_name, lsk_number, incident_date, county, category, ai_category,
            ai_urgency_score, ai_summary, narrative, original_narrative, detected_language,
            recommended_division, advocate_response, amount_involved, status, assigned_officer, created_at
        ) VALUES (
            :ref_number, :is_anonymous, :complainant_name, :complainant_email, :complainant_phone,
            :advocate_name, :lsk_number, :incident_date, :county, :category, :ai_category,
            :ai_urgency_score, :ai_summary, :narrative, :original_narrative, :detected_language,
            :recommended_division, :advocate_response, :amount_involved, :status, :assigned_officer, :created_at
        )
    ");

    $noteStmt = $pdo->prepare("
        INSERT INTO case_notes (complaint_id, author, note_type, content, created_at)
        VALUES (:complaint_id, :author, :note_type, :content, :created_at)
    ");

    foreach ($seedCases as $c) {
        $c['original_narrative'] = $c['original_narrative'] ?? $c['narrative'];
        $c['detected_language'] = $c['detected_language'] ?? 'English';
        $c['recommended_division'] = $c['recommended_division'] ?? ($c['ai_urgency_score'] >= 80 ? 'Prosecution (Tribunal)' : 'Review & Investigation');
        $c['advocate_response'] = $c['advocate_response'] ?? null;

        if ($c['ref_number'] === 'ACC-2026-1001') {
            $c['recommended_division'] = 'Prosecution (Tribunal)';
            $c['advocate_response'] = 'I acknowledge receipt of the Notice to Show Cause. The purchase price of KES 3,450,000 was received, however disbursement was delayed due to pending Land Control Board consent and routine bank compliance checks. I deny any misappropriation of client funds.';
        } elseif ($c['ref_number'] === 'ACC-2026-1002') {
            $c['detected_language'] = 'Kiswahili';
            $c['original_narrative'] = 'Mwezi wa Agosti 2026 nilimlipa Wakili Salim Mwawasi ada ya KES 180,000 aniwakilishe katika kesi ya kibiashara Mombasa CMCC No. 412 of 2026. Hakufika mahakamani wala kuwasilisha utetezi ndani ya siku 14, na sasa wapiga mnada wanataka kukamata mali yangu.';
        } elseif ($c['ref_number'] === 'ACC-2026-1005') {
            $c['recommended_division'] = 'IHADR';
        } elseif ($c['ref_number'] === 'ACC-2026-1007') {
            $c['detected_language'] = 'Kiswahili';
            $c['original_narrative'] = 'Baada ya ajali ya barabarani Machakos, kampuni ya bima ilitoa hundi ya fidia ya KES 960,000 kwa wakili wangu Adv. Faith Kalondu tarehe 10 Novemba 2026. Amekataa kunipa pesa zangu za matibabu.';
        }

        $stmt->execute($c);
        $id = $pdo->lastInsertId();
        $noteStmt->execute([
            'complaint_id' => $id,
            'author' => 'ACC Intake System',
            'note_type' => 'Status Change',
            'content' => 'Complaint lodged and assigned reference ' . $c['ref_number'] . '.',
            'created_at' => $c['created_at']
        ]);
        if ($c['status'] !== 'Received') {
            $noteStmt->execute([
                'complaint_id' => $id,
                'author' => $c['assigned_officer'] !== 'Unassigned' ? $c['assigned_officer'] : 'ACC Triage Desk',
                'note_type' => 'Status Change',
                'content' => 'Case progressed to status: ' . $c['status'] . '.',
                'created_at' => date('Y-m-d H:i:s', strtotime($c['created_at'] . ' +2 days'))
            ]);
        }
    }
}
