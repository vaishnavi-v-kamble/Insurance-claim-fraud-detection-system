<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "db.php";

// Always return JSON from this endpoint
header('Content-Type: application/json; charset=utf-8');
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "error", "msg" => "No data received"]);
    exit;
}

$claim_id = "CLM" . rand(1000,9999);

// fields
$name = $data['name'] ?? '';
$phone = $data['phone'] ?? '';
$email = $data['email'] ?? '';
$address = $data['address'] ?? '';

$policy_amount     = $data['policy_amount'] ?? 0;
$claim_amount      = $data['claim_amount'] ?? 0;
$user_claim_count  = $data['user_claim_count'] ?? 0;
$policy_count      = $data['policy_count'] ?? 1;

$policy_start_date = $data['policy_start_date'] ?? date("Y-m-d");
// always use today's current date as the claim filing date for safer scoring
$claim_date        = date("Y-m-d");

$vehicle = $data['vehicle'] ?? '';
$type = $data['type'] ?? '';
$model = $data['model'] ?? '';
$year = $data['year'] ?? '';
$engine = $data['engine'] ?? '';

$policy = $data['policy'] ?? '';
$company = $data['company'] ?? '';
$start = $data['start'] ?? '';
$end = $data['end'] ?? '';
$coverage = $data['coverage'] ?? '';

$date = $data['date'] ?? '';
$time = $data['time'] ?? '';
$location = $data['location'] ?? '';
$accidentType = $data['accidentType'] ?? '';

$desc = $data['desc'] ?? '';
$vehicles = $data['vehicles'] ?? '';

$otherVehicle = $data['otherVehicle'] ?? '';
$driver = $data['driver'] ?? '';
$police = $data['police'] ?? '';
$firno = $data['firno'] ?? '';

$cost = $data['cost'] ?? '';
$garage = $data['garage'] ?? '';
$previous = $data['previous'] ?? '';


// ================= FEATURE ENGINE =================
$claim_to_policy_ratio = ($policy_amount > 0) ? ($claim_amount / $policy_amount) : 0;

$claim_frequency = ($policy_count > 0) ? ($user_claim_count / $policy_count) : 0;

$policy_age_days = (strtotime($claim_date) - strtotime($policy_start_date)) / 86400;

if ($policy_age_days < 0) {
    $policy_age_days = 0;
}

// days between accident date and claim submission
$delay_days = max(0, floor((strtotime($claim_date) - strtotime($date)) / 86400));

// default risk level placeholder (may be updated by ML)
$risk_level = 'UNKNOWN';

// ================= RISK SCORE =================
$risk_score = 0;

$risk_score += ($claim_to_policy_ratio > 0.8) ? 40 : (($claim_to_policy_ratio > 0.5) ? 25 : 10);
$risk_score += ($claim_frequency > 3) ? 30 : (($claim_frequency > 1) ? 20 : 5);
// same-day or very recent claims are safer; longer delays are riskier
if ($delay_days <= 1) {
    $risk_score += 5;
} elseif ($delay_days < 30) {
    $risk_score += 10;
} elseif ($delay_days < 90) {
    $risk_score += 20;
} else {
    $risk_score += 30;
}

if ($policy_age_days < 30) {

    $risk_score += 30;

} elseif ($policy_age_days < 90) {

    $risk_score += 15;

} else {

    $risk_score += 5;
}
// FIR Available
if (!empty($firno)) {
    $risk_score -= 5;
}

// Images / Evidence Uploaded
if (!empty($data['evidence'])) {
    $risk_score -= 5;
}

// No Previous Claims
if (
    strtolower($previous) == 'no' ||
    $user_claim_count == 0
) {
    $risk_score -= 10;
}

// Small Claim Amount
if ($claim_amount < 20000) {
    $risk_score -= 10;
}

// Old Genuine Policy
if ($policy_age_days > 365) {
    $risk_score -= 10;
}
// Prevent Negative Score
if ($risk_score < 0) {
    $risk_score = 0;
}


$traditional_risk_level = '';

if ($risk_score >= 70) {

    $traditional_risk_level = "HIGH RISK";

} elseif ($risk_score >= 40) {

    $traditional_risk_level = "MEDIUM RISK";

} else {
    $traditional_risk_level = "LOW RISK";
}

// ================= ML PREDICTION =================
$current_year = (int)date("Y");
$vehicle_year = (int)($year ?: $current_year);
$vehicle_age = max(0, $current_year - $vehicle_year);

$current_time = time();
$acc_date = $date ?: date('Y-m-d');
$days_to_claim = max(0, floor(($current_time - strtotime($acc_date)) / 86400));

$claim_for_ml = [
    [
        "claim_id" => $claim_id,
        "vehicle_age" => $vehicle_age,
        "claim_amount" => (float)$claim_amount,
        "previous_claims" => (int)(strtolower($previous) === 'yes' ? 1 :  0),
        "days_to_claim" => $days_to_claim
    ]
];

$tmp_file = tempnam(sys_get_temp_dir(), 'claim_');
file_put_contents($tmp_file, json_encode($claim_for_ml));
$output = shell_exec("python predict_fraud.py " . escapeshellarg($tmp_file));
@unlink($tmp_file);

$db_status = 'Pending';
$ml_risk = 'Unknown';
$fraud_probability = 0;
$final_risk_level = $traditional_risk_level;
$ml_features = [];
$confidence = 0;
$prediction_reasoning = '';

if ($output) {
    $parsed = json_decode($output, true);
    if (is_array($parsed) && isset($parsed[$claim_id])) {
        $prediction = $parsed[$claim_id];
        $ml_risk = $prediction['status'] ?? 'Unknown'; // SAFE, SUSPICIOUS, FRAUD
        $fraud_probability = $prediction['fraud_probability'] ?? 0;
        $risk_level_pred = $prediction['risk_level'] ?? null;
        $ml_features = $prediction['features'] ?? [];
        $confidence = $prediction['confidence'] ?? 0;
        $prediction_reasoning = $prediction['prediction_reasoning'] ?? '';

        if ($ml_risk === 'SAFE') {
            $db_status = 'Approved';
        } elseif ($ml_risk === 'FRAUD') {
            $db_status = 'Rejected';
        } elseif ($ml_risk === 'SUSPICIOUS') {
            $db_status = 'Under Review';
        }

        // Determine risk level from fraud probability if available
        if (is_numeric($fraud_probability)) {
            if ($fraud_probability <= 50) {
                $risk_level = "LOW RISK";
            } elseif ($fraud_probability < 70) {
                $risk_level = "MEDIUM RISK";
            } else {
                $risk_level = "HIGH RISK";
            }
        } elseif ($risk_level_pred) {
            $risk_level = $risk_level_pred;
        }

        // Set DB status based on risk level (if not already set by ML status)
        if (stripos($risk_level, 'LOW') !== false) {
            $db_status = 'Approved';
        } elseif (stripos($risk_level, 'HIGH') !== false) {
            $db_status = 'Rejected';
        }

        // Ensure final_risk_level matches the computed risk_level for JSON output
        $final_risk_level = $risk_level;
    }
}

// ================= INSERT DB (NOW INCLUDING RISK) =================
// escape inputs to avoid SQL injection and syntax errors
$esc = function($v) use ($conn) { return $conn->real_escape_string((string)$v); };

$sql = "INSERT INTO claims (
claim_id,name,phone,email,address,
vehicle,type,model,year,engine,
policy,company,start_date,end_date,coverage,
accident_date,accident_time,location,accident_type,
description,vehicles_involved,
other_vehicle,driver,police_station,fir_no,
cost,garage,previous_claim,status
) VALUES (
'".$esc($claim_id)."',
'".$esc($name)."',
'".$esc($phone)."',
'".$esc($email)."',
'".$esc($address)."',
'".$esc($vehicle)."',
'".$esc($type)."',
'".$esc($model)."',
'".$esc($year)."',
'".$esc($engine)."',
'".$esc($policy)."',
'".$esc($company)."',
'".$esc($start)."',
'".$esc($end)."',
'".$esc($coverage)."',
'".$esc($date)."',
'".$esc($time)."',
'".$esc($location)."',
'".$esc($accidentType)."',
'".$esc($desc)."',
'".$esc($vehicles)."',
'".$esc($otherVehicle)."',
'".$esc($driver)."',
'".$esc($police)."',
'".$esc($firno)."',
'".$esc($cost)."',
'".$esc($garage)."',
'".$esc($previous)."',
'".$esc($db_status)."'
)";

if ($conn->query($sql)) {

    echo json_encode([
        "status" => "success",
        "claim_id" => $claim_id,
        "db_status" => $db_status,
        "ml_risk" => $ml_risk,
        "fraud_probability" => $fraud_probability,
        "display_risk" => $risk_level,
        "confidence" => $confidence,
        "prediction_reasoning" => $prediction_reasoning,
        "ml_features" => $ml_features,
        "risk_score" => $risk_score,
        "traditional_risk_level" => $traditional_risk_level,
        "features" => [
            "claim_to_policy_ratio" => $claim_to_policy_ratio,
            "claim_frequency" => $claim_frequency,
            "delay_days" => $delay_days
        ]
    ]);

} else {
    echo json_encode([
        "status" => "error",
        "msg" => $conn->error
    ]);
}
?>