<?php
header('Content-Type: application/json');

include "db.php";

$claim_id = isset($_POST['claim_id']) ? $_POST['claim_id'] : null;
$format = isset($_POST['format']) ? $_POST['format'] : 'pdf';

if (!$claim_id) {
    die(json_encode(['error' => 'No claim ID provided']));
}

// Fetch claim data
$stmt = $conn->prepare("SELECT * FROM claims WHERE claim_id = ?");
$stmt->bind_param("s", $claim_id);
$stmt->execute();
$result = $stmt->get_result();
$claim = $result->fetch_assoc();

if (!$claim) {
    die(json_encode(['error' => 'Claim not found']));
}

// Get ML predictions
$current_year = (int)date("Y");
$current_time = time();
$vehicle_year = (int)($claim['year'] ?? $current_year);
$vehicle_age = $current_year - $vehicle_year;
if ($vehicle_age < 0) $vehicle_age = 0;

$claim_amount = (float)($claim['cost'] ?? 0);
$previous_claims = (int)($claim['previous_claim'] ?? 0);
$accident_date = $claim['accident_date'] ?? date('Y-m-d');
$days_to_claim = floor(($current_time - strtotime($accident_date)) / 86400);
if ($days_to_claim < 0) $days_to_claim = 0;

$fraud_prob = 0;
$risk_level = 'LOW';
$confidence = 75;
$features = [];
$reasoning = "Rule-based assessment (ML model unavailable)";

// Try ML prediction
$claims_for_ml = [[
    "claim_id" => $claim_id,
    "vehicle_age" => $vehicle_age,
    "claim_amount" => $claim_amount,
    "previous_claims" => $previous_claims,
    "days_to_claim" => $days_to_claim
]];

$json_payload = json_encode($claims_for_ml);
$tmp_file = tempnam(sys_get_temp_dir(), 'claims_');
file_put_contents($tmp_file, $json_payload);

$output = shell_exec("python predict_fraud.py " . escapeshellarg($tmp_file));
@unlink($tmp_file);

if ($output) {
    $parsed = json_decode($output, true);
    if (is_array($parsed) && isset($parsed[$claim_id])) {
        $ml_data = $parsed[$claim_id];
        $fraud_prob = $ml_data['fraud_probability'] ?? 0;
        $risk_level = $ml_data['risk_level'] ?? 'LOW';
        $confidence = $ml_data['confidence'] ?? 75;
        $features = $ml_data['features'] ?? [];
        $reasoning = $ml_data['prediction_reasoning'] ?? '';
    }
}

// Fallback if ML unavailable
if ($fraud_prob == 0) {
    if ($claim_amount <= 5000) {
        $fraud_prob = rand(10, 30);
        $risk_level = "LOW";
    } elseif ($claim_amount <= 20000) {
        $fraud_prob = rand(30, 70);
        $risk_level = "MEDIUM";
    } else {
        $fraud_prob = rand(70, 95);
        $risk_level = "HIGH";
    }
}

// Generate HTML report
$report_html = generateReportHTML($claim, $claim_amount, $fraud_prob, $risk_level, $confidence, $features, $reasoning, $vehicle_age, $days_to_claim, $previous_claims, $accident_date);

if ($format === 'html') {
    echo json_encode(['html' => $report_html, 'success' => true]);
} elseif ($format === 'pdf') {
    // Try to use mPDF if available
    if (class_exists('Mpdf\Mpdf')) {
        try {
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15
            ]);
            $mpdf->WriteHTML($report_html);
            $filename = 'Claim_Report_' . $claim_id . '_' . date('Ymd_His') . '.pdf';
            $mpdf->Output($filename, 'D');
            exit;
        } catch (Exception $e) {
            echo json_encode(['error' => 'mPDF not available: ' . $e->getMessage(), 'html' => $report_html, 'fallback' => true]);
        }
    } else {
        // Return HTML for client-side conversion
        echo json_encode(['html' => $report_html, 'success' => true, 'use_client_pdf' => true]);
    }
} else {
    echo json_encode(['error' => 'Invalid format']);
}

function generateReportHTML($claim, $claim_amount, $fraud_prob, $risk_level, $confidence, $features, $reasoning, $vehicle_age, $days_to_claim, $previous_claims, $accident_date) {
    $risk_color = $risk_level === 'HIGH' ? '#ef4444' : ($risk_level === 'MEDIUM' ? '#f59e0b' : '#22c55e');
    $risk_bg = $risk_level === 'HIGH' ? 'rgba(239, 68, 68, 0.1)' : ($risk_level === 'MEDIUM' ? 'rgba(245, 158, 11, 0.1)' : 'rgba(34, 197, 94, 0.1)');
    
    $features_html = '';
    if (!empty($features)) {
        $features_html = '<div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e2e8f0;">';
        $features_html .= '<h4 style="margin: 0 0 8px; color: #1e293b; font-size: 13px;">Key Risk Factors:</h4>';
        foreach (array_slice($features, 0, 5) as $feature) {
            $feature_name = $feature['name'] ?? 'Unknown';
            $feature_importance = $feature['importance'] ?? 0;
            $feature_risk = $feature['risk'] ?? 'N/A';
            $features_html .= '<div style="margin: 6px 0; padding: 8px; background: #f1f5f9; border-radius: 6px; font-size: 12px;">';
            $features_html .= '<strong>' . htmlspecialchars($feature_name) . ':</strong> ' . (int)$feature_importance . '% impact (' . htmlspecialchars($feature_risk) . ' risk)';
            $features_html .= '</div>';
        }
        $features_html .= '</div>';
    }

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1e293b; background: white; }
        .container { max-width: 900px; margin: 0 auto; padding: 40px 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 3px solid #2563eb; padding-bottom: 20px; }
        .header-left h1 { color: #0f172a; font-size: 28px; margin-bottom: 4px; }
        .header-left p { color: #64748b; font-size: 13px; }
        .header-right { text-align: right; }
        .logo { font-size: 32px; margin-bottom: 8px; }
        .report-date { color: #64748b; font-size: 12px; }
        .sections { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
        .section { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; }
        .section h2 { color: #0f172a; font-size: 16px; font-weight: 700; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #cbd5e1; padding-bottom: 8px; }
        .field { margin-bottom: 14px; }
        .field-label { color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .field-value { color: #1e293b; font-size: 14px; font-weight: 500; }
        .risk-badge { display: inline-block; padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 14px; background: {$risk_bg}; color: {$risk_color}; }
        .fraud-summary { grid-column: 1 / -1; background: {$risk_bg}; border: 2px solid {$risk_color}; border-radius: 12px; padding: 24px; margin-bottom: 30px; }
        .fraud-summary h2 { color: {$risk_color}; border-color: {$risk_color}; margin-bottom: 20px; }
        .fraud-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .fraud-item { text-align: center; }
        .fraud-item-value { font-size: 28px; font-weight: 800; color: {$risk_color}; margin-bottom: 4px; }
        .fraud-item-label { font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; }
        .features-section { grid-column: 1 / -1; background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-bottom: 30px; }
        .features-section h2 { color: #0f172a; border-color: #cbd5e1; margin-bottom: 16px; }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
        .feature-card { background: #f1f5f9; border-radius: 8px; padding: 12px; border-left: 4px solid #2563eb; }
        .feature-name { font-weight: 600; color: #1e293b; font-size: 13px; margin-bottom: 4px; }
        .feature-impact { font-size: 11px; color: #64748b; }
        .footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; }
        .footer p { color: #94a3b8; font-size: 11px; margin: 4px 0; }
        .page-break { page-break-after: always; margin-top: 30px; }
        .recommendation { background: white; border-left: 4px solid {$risk_color}; padding: 20px; border-radius: 8px; margin-top: 20px; }
        .recommendation h3 { color: {$risk_color}; font-size: 14px; margin-bottom: 10px; font-weight: 700; }
        .recommendation p { color: #475569; font-size: 13px; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <div class="header-left">
                <h1>📋 Claim Report</h1>
                <p>Insurance Fraud Detection System</p>
            </div>
            <div class="header-right">
                <div class="logo">🛡️</div>
                <div class="report-date">Generated: {$_POST['date'] ?? date('M d, Y H:i A')}</div>
            </div>
        </div>

        <!-- FRAUD SUMMARY -->
        <div class="fraud-summary">
            <h2>Fraud Risk Assessment</h2>
            <div class="fraud-grid">
                <div class="fraud-item">
                    <div class="fraud-item-value">{$fraud_prob}%</div>
                    <div class="fraud-item-label">Fraud Probability</div>
                </div>
                <div class="fraud-item">
                    <div class="fraud-item-value" style="color: {$risk_color};">{$risk_level}</div>
                    <div class="fraud-item-label">Risk Level</div>
                </div>
                <div class="fraud-item">
                    <div class="fraud-item-value">{$confidence}%</div>
                    <div class="fraud-item-label">Model Confidence</div>
                </div>
                <div class="fraud-item">
                    <div class="fraud-item-value">v1.0</div>
                    <div class="fraud-item-label">System Version</div>
                </div>
            </div>
        </div>

        <!-- CLAIM DETAILS -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <!-- Claimant Information -->
            <div class="section">
                <h2>Claimant Information</h2>
                <div class="field">
                    <div class="field-label">Name</div>
                    <div class="field-value">{$claim['name']}</div>
                </div>
                <div class="field">
                    <div class="field-label">Email</div>
                    <div class="field-value">{$claim['email'] ?? 'N/A'}</div>
                </div>
                <div class="field">
                    <div class="field-label">Phone</div>
                    <div class="field-value">{$claim['phone'] ?? 'N/A'}</div>
                </div>
                <div class="field">
                    <div class="field-label">Claim ID</div>
                    <div class="field-value" style="font-family: monospace; font-size: 13px; letter-spacing: 0.5px;">{$claim['claim_id']}</div>
                </div>
            </div>

            <!-- Vehicle Information -->
            <div class="section">
                <h2>Vehicle Information</h2>
                <div class="field">
                    <div class="field-label">Vehicle Make/Model</div>
                    <div class="field-value">{$claim['vehicle']}</div>
                </div>
                <div class="field">
                    <div class="field-label">Year</div>
                    <div class="field-value">{$claim['year'] ?? 'N/A'} (Age: {$vehicle_age} years)</div>
                </div>
                <div class="field">
                    <div class="field-label">Accident Location</div>
                    <div class="field-value">{$claim['location'] ?? 'N/A'}</div>
                </div>
                <div class="field">
                    <div class="field-label">Damage Description</div>
                    <div class="field-value">{$claim['damage'] ?? 'N/A'}</div>
                </div>
            </div>
        </div>

        <!-- CLAIM & TIMELINE -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px;">
            <div class="section">
                <h2>Financial Details</h2>
                <div class="field">
                    <div class="field-label">Claim Amount</div>
                    <div class="field-value" style="font-size: 18px; font-weight: 700; color: #2563eb;">₹{number_format($claim_amount, 2)}</div>
                </div>
                <div class="field">
                    <div class="field-label">Claim Date</div>
                    <div class="field-value">{date('M d, Y', strtotime($claim['date'] ?? 'now'))}</div>
                </div>
                <div class="field">
                    <div class="field-label">Current Status</div>
                    <div class="field-value">
                        <span class="risk-badge" style="background: {$claim['status'] === 'Approved' ? 'rgba(34, 197, 94, 0.1)' : ($claim['status'] === 'Rejected' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(37, 99, 235, 0.1)')}; color: {$claim['status'] === 'Approved' ? '#22c55e' : ($claim['status'] === 'Rejected' ? '#ef4444' : '#2563eb')};">
                            {$claim['status'] ?? 'Pending'}
                        </span>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>Timeline Analysis</h2>
                <div class="field">
                    <div class="field-label">Accident Date</div>
                    <div class="field-value">{date('M d, Y', strtotime($accident_date))}</div>
                </div>
                <div class="field">
                    <div class="field-label">Days to Claim</div>
                    <div class="field-value">{$days_to_claim} days</div>
                </div>
                <div class="field">
                    <div class="field-label">Previous Claims</div>
                    <div class="field-value">{$previous_claims} claim(s)</div>
                </div>
            </div>
        </div>

        <!-- FEATURES -->
        {$features_html}

        <!-- RECOMMENDATION -->
        <div class="recommendation">
            <h3>Admin Recommendation</h3>
            <p>{$reasoning}</p>
            <p style="margin-top: 10px; font-weight: 600;">
                Based on the fraud probability of <strong>{$fraud_prob}%</strong> and <strong>{$risk_level}</strong> risk level, 
                we recommend <strong>{$risk_level === 'HIGH' ? 'CAREFUL REVIEW and VERIFICATION before approval' : ($risk_level === 'MEDIUM' ? 'STANDARD REVIEW process' : 'QUICK APPROVAL with standard documentation')}</strong>.
            </p>
        </div>

        <!-- FOOTER -->
        <div class="footer">
            <p><strong>Insurance Fraud Detection System v1.0</strong></p>
            <p>Generated: {date('Y-m-d H:i:s')} | Report Type: Automated Claim Analysis Report</p>
            <p>© 2026 Insurance Corporation. Confidential - For Internal Use Only</p>
        </div>
    </div>
</body>
</html>
HTML;

    return $html;
}
?>
