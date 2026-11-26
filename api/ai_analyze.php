<?php
/**
 * AI Analysis Endpoint (api/ai_analyze.php)
 * Supports Gemini/OpenAI API via config.php with a deterministic PHP heuristic fallback engine.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? 'intake_assess';
$narrative = trim($input['narrative'] ?? '');

if ($action === 'intake_assess' && strlen($narrative) < 15) {
    echo json_encode([
        'status' => 'waiting',
        'message' => 'Type at least 15 characters of your complaint narrative for live AI assessment.'
    ]);
    exit;
}

// Optional: Try external LLM if API key is configured
$aiResult = null;
if (!empty(GEMINI_API_KEY) || !empty(OPENAI_API_KEY)) {
    $aiResult = tryExternalAI($action, $input);
}

// Fallback to deterministic heuristic engine
if (!$aiResult) {
    $aiResult = runHeuristicAI($action, $input);
}

echo json_encode($aiResult);
exit;

/**
 * Deterministic Heuristic Legal AI Engine for Kenyan Advocates Act (Cap. 16)
 */
function runHeuristicAI(string $action, array $input): array {
    $narrative = trim($input['narrative'] ?? '');
    $lower = strtolower($narrative);

    // 1. Category Classification
    $categoryScores = [
        'Withholding Client Funds' => 0,
        'Professional Negligence' => 0,
        'Conflict of Interest' => 0,
        'Overcharging / Fee Dispute' => 0,
        'Failure to Account' => 0,
        'Delay / Inaction' => 0,
    ];

    $keywords = [
        'Withholding Client Funds' => ['withheld', 'withholding', 'client account', 'proceeds', 'cheque', 'remit', 'compensation', 'deposit', 'purchase price', 'refused to pay', 'pocketed'],
        'Professional Negligence' => ['negligen', 'search', 'ardhisasa', 'forgery', 'forged', 'due diligence', 'wrong advice', 'ex-parte', 'default judgment', 'failed to file'],
        'Conflict of Interest' => ['conflict', 'both sides', 'both parties', 'confidential', 'caveat', 'opposing', 'represented the buyer'],
        'Overcharging / Fee Dispute' => ['overcharg', 'fee', 'remuneration', 'excessive', 'bill of costs', 'taxation', 'retainer'],
        'Failure to Account' => ['account', 'statement', 'rent', 'succession', 'estate', 'receipts', 'breakdown'],
        'Delay / Inaction' => ['delay', 'inaction', 'unresponsive', 'ignoring', 'months', 'years', 'stalled', 'no progress', 'calls'],
    ];

    foreach ($keywords as $cat => $words) {
        foreach ($words as $w) {
            if (str_contains($lower, $w)) {
                $categoryScores[$cat] += 2;
            }
        }
    }

    arsort($categoryScores);
    $topCat = array_key_first($categoryScores);
    if ($categoryScores[$topCat] === 0) {
        $topCat = !empty($input['category']) ? $input['category'] : 'Delay / Inaction';
    }

    // 2. Entity Extraction (KES Amounts & Dates)
    $amounts = [];
    if (preg_match_all('/(?:kes|ksh|shs?\.?)\s*([\d,]+(?:\.\d{1,2})?)/i', $narrative, $m)) {
        foreach ($m[1] as $raw) {
            $val = (float)str_replace(',', '', $raw);
            if ($val > 0) {
                $amounts[] = $val;
            }
        }
    }
    $maxAmount = !empty($amounts) ? max($amounts) : (float)($input['amount_involved'] ?? 0);

    $dates = [];
    if (preg_match_all('/\b(\d{1,2}(?:st|nd|rd|th)?\s+(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\s+\d{4}|\d{4}-\d{2}-\d{2}|\d{1,2}\/\d{1,2}\/\d{4})\b/i', $narrative, $dm)) {
        $dates = array_unique($dm[1]);
    }

    // 3. Urgency Score (1-100)
    $score = 40;
    $catBase = [
        'Withholding Client Funds' => 30,
        'Conflict of Interest' => 25,
        'Failure to Account' => 22,
        'Professional Negligence' => 20,
        'Delay / Inaction' => 12,
        'Overcharging / Fee Dispute' => 10,
    ];
    $score += $catBase[$topCat] ?? 10;

    if ($maxAmount >= 1000000) $score += 22;
    elseif ($maxAmount >= 250000) $score += 14;
    elseif ($maxAmount > 0) $score += 7;

    foreach (['auctioneer', 'hospital', 'forged', 'forgery', 'elderly', 'widow', 'fraud', 'criminal', 'urgent', 'attachment'] as $urgentWord) {
        if (str_contains($lower, $urgentWord)) {
            $score += 6;
        }
    }
    $score = min(99, max(15, $score));

    $severity = $score >= 80 ? 'Critical' : ($score >= 60 ? 'High' : ($score >= 40 ? 'Medium' : 'Low'));

    if ($action === 'intake_assess') {
        $missing = [];
        if (empty($dates)) {
            $missing[] = 'Include specific incident or payment dates (e.g., 15th June 2026).';
        }
        if (in_array($topCat, ['Withholding Client Funds', 'Failure to Account', 'Overcharging / Fee Dispute']) && $maxAmount <= 0) {
            $missing[] = 'Specify the exact monetary amount involved in KES.';
        }
        if (strlen($narrative) < 80) {
            $missing[] = 'Provide more detail on how the advocate responded to your follow-ups.';
        }

        return [
            'status' => 'ok',
            'engine' => 'ACC Heuristic Legal AI',
            'suggested_category' => $topCat,
            'urgency_score' => $score,
            'severity_label' => $severity,
            'extracted_amount' => $maxAmount,
            'extracted_dates' => array_values($dates),
            'missing_info_prompts' => $missing
        ];
    }

    // 4. Case Brief & Notice to Show Cause Generation
    $ref = $input['ref_number'] ?? 'ACC-2026-XXXX';
    $advocate = $input['advocate_name'] ?? 'the Respondent Advocate';
    $lsk = !empty($input['lsk_number']) ? $input['lsk_number'] : 'LSK/Pending Verification';
    $complainant = $input['complainant_name'] ?? 'the Complainant';
    $amountStr = $maxAmount > 0 ? 'KES ' . number_format($maxAmount, 2) : 'Unspecified / Non-monetary';
    $datesStr = !empty($dates) ? implode(', ', $dates) : ($input['incident_date'] ?? 'Not specified');

    $citations = match ($topCat) {
        'Withholding Client Funds', 'Failure to Account' => [
            'Section 55 & Section 60 of the Advocates Act (Cap. 16, Laws of Kenya) — Professional misconduct & disciplinary jurisdiction.',
            'Advocates (Accounts) Rules (Cap. 16, Sub. Leg.) — Mandatory duty to hold client funds in a separate client account and account without undue delay.',
            'Section 60A of the Advocates Act — Power of the Commission to order restitution/repayment of client money.'
        ],
        'Conflict of Interest' => [
            'Section 60 of the Advocates Act (Cap. 16) — Conduct unbecoming of an advocate.',
            'Rule 8 & Rule 9 of the Advocates (Practice) Rules — Prohibition against acting for both parties where a conflict of interest exists or confidential information is at risk.'
        ],
        'Overcharging / Fee Dispute' => [
            'Section 44 & Section 45 of the Advocates Act (Cap. 16) — Remuneration agreements and compliance with the Advocates (Remuneration) Order.',
            'Section 60 of the Advocates Act — Overcharging as professional misconduct.'
        ],
        default => [
            'Section 53 & Section 60 of the Advocates Act (Cap. 16, Laws of Kenya) — Jurisdiction of the Advocates Complaints Commission over professional negligence and undue delay.',
            'Law Society of Kenya Code of Standards of Professional Practice and Ethical Conduct (SOPPEC) — Duty of diligence and timely communication.'
        ]
    };

    $summary = "Complaint {$ref} lodged against {$advocate} ({$lsk}) alleges {$topCat}. "
        . ($maxAmount > 0 ? "The matter involves {$amountStr}. " : "")
        . "Based on the complainant's narrative, the AI Triage Engine assigns an urgency score of {$score}/100 ({$severity}). "
        . "Key dates referenced: {$datesStr}.";

    $today = date('jS F Y');
    $letter = "REPUBLIC OF KENYA\n"
        . "ADVOCATES COMPLAINTS COMMISSION (ACC)\n"
        . "------------------------------------------------------------\n"
        . "Ref: {$ref}\n"
        . "Date: {$today}\n\n"
        . "TO: {$advocate}\n"
        . "LSK Practice No: {$lsk}\n\n"
        . "RE: NOTICE TO SHOW CAUSE & PRELIMINARY INQUIRY — COMPLAINT BY {$complainant}\n\n"
        . "Take notice that the Advocates Complaints Commission has received a formal complaint against you under Section 53 of the Advocates Act (Cap. 16, Laws of Kenya) alleging {$topCat}"
        . ($maxAmount > 0 ? " involving {$amountStr}" : "") . ".\n\n"
        . "Pursuant to the Commission's statutory mandate, you are hereby required within FOURTEEN (14) DAYS from the date of this Notice to submit a comprehensive written response, together with certified copies of your client account ledger, correspondence, and relevant file records.\n\n"
        . "Failure to respond within the stipulated period may result in the matter being escalated to the Advocates Disciplinary Tribunal under Section 60 of the Advocates Act.\n\n"
        . "Yours faithfully,\n\n"
        . "COMMISSION SECRETARY\n"
        . "ADVOCATES COMPLAINTS COMMISSION (ACC)";

    return [
        'status' => 'ok',
        'engine' => 'ACC Heuristic Legal AI',
        'executive_summary' => $summary,
        'extracted_entities' => [
            'amount_kes' => $amountStr,
            'key_dates' => $datesStr,
            'category' => $topCat,
            'urgency' => "{$score}/100 ({$severity})"
        ],
        'legal_citations' => $citations,
        'draft_letter' => $letter
    ];
}

function tryExternalAI(string $action, array $input): ?array {
    if (empty(GEMINI_API_KEY) || !function_exists('curl_init')) {
        return null;
    }

    $narrative = trim($input['narrative'] ?? '');
    $amount = $input['amount_involved'] ?? 0;

    if ($action === 'intake_assess') {
        $prompt = "You are a legal intake AI for the Advocates Complaints Commission (ACC) of Kenya (Advocates Act Cap. 16). "
            . "Analyze this citizen complaint narrative and return ONLY valid JSON with keys: "
            . "\"status\" (\"ok\"), \"engine\" (\"Gemini 2.5 Flash\"), "
            . "\"suggested_category\" (one of: Withholding Client Funds, Professional Negligence, Conflict of Interest, Overcharging / Fee Dispute, Failure to Account, Delay / Inaction, Other Misconduct), "
            . "\"urgency_score\" (integer 1-100), \"severity_label\" (Low, Medium, High, or Critical), "
            . "\"extracted_amount\" (number), \"extracted_dates\" (array of strings), "
            . "\"missing_info_prompts\" (array of 0-3 short actionable prompts for missing details).\n\n"
            . "Amount provided: KES {$amount}\nNarrative: {$narrative}";
    } else {
        $ref = $input['ref_number'] ?? 'ACC-2026-XXXX';
        $advocate = $input['advocate_name'] ?? 'Respondent Advocate';
        $lsk = $input['lsk_number'] ?? 'N/A';
        $complainant = $input['complainant_name'] ?? 'Complainant';
        $prompt = "You are a legal officer AI for the Advocates Complaints Commission (ACC) of Kenya. "
            . "Analyze this complaint and return ONLY valid JSON with keys: "
            . "\"status\" (\"ok\"), \"engine\" (\"Gemini 2.5 Flash\"), "
            . "\"executive_summary\" (concise 2-3 sentence summary), "
            . "\"legal_citations\" (array of 2-3 relevant sections of the Advocates Act Cap. 16, Laws of Kenya), "
            . "\"draft_letter\" (formal Notice to Show Cause letter addressed to {$advocate}, LSK {$lsk}, Ref {$ref}, regarding complaint by {$complainant}, giving 14 days to respond).\n\n"
            . "Narrative: {$narrative}";
    }

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . urlencode(GEMINI_API_KEY);
    $payload = json_encode([
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => ['responseMimeType' => 'application/json', 'temperature' => 0.2]
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 12
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        return null;
    }

    $decoded = json_decode($response, true);
    $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $parsed = json_decode($text, true);

    return (is_array($parsed) && ($parsed['status'] ?? '') === 'ok') ? $parsed : null;
}
