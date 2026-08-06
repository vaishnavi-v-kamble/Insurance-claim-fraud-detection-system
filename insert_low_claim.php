<?php
include "db.php";

$claim_id = 'CLM' . rand(5000, 9999);

$name = 'Auto LowRisk Insert';
$phone = '9001122334';
$email = 'autolowinsert@example.com';
$address = '12 Safe Lane';

$vehicle = 'MH01ZZ0000';
$type = 'Car';
$model = 'Honda Civic';
$year = '2017';
$engine = 'ENGLOW0001';

$policy = 'POLLW0001';
$company = 'Trusted Insurance';
$start = '2018-01-01';
$end = '2026-12-31';
$coverage = 'Comprehensive';

$date = date('Y-m-d');
$time = date('H:i');
$location = 'Downtown';
$accidentType = 'Minor Collision';

$desc = 'Minor bumper scratch, no injuries.';
$vehicles = 1;
$otherVehicle = '';
$driver = '';
$police = '';
$firno = '';

$cost = 1200;
$garage = '';
$previous = 'No';

$status = 'Approved';

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
'".$esc($status)."'
)
";

if ($conn->query($sql) === TRUE) {
    echo "Inserted claim: " . $claim_id . PHP_EOL;
} else {
    echo "Error inserting: " . $conn->error . PHP_EOL;
}

?>