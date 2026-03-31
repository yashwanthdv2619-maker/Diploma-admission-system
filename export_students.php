<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "diploma_admission";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=students_data.csv');


$output = fopen('php://output', 'w');


$branches = [
    1 => 'CS',
    2 => 'E&E',
    3 => 'ME',
    4 => 'EC',
    5 => 'CE',
    6 => 'AT',
];


$headers = [
    'ID', 'SATS No', 'Aadhar No', 'Name', 'Mother Name', 'Father Name', 'DOB', 'Gender',
    'Nationality', 'Religion', 'Exam Type', 'Native State', 'Native District',
    'State Appeared', 'Years Studied in Karnataka', 'Rural Study', 'Kannada Medium',
    'Exemption Rule', 'Clause Code', 'SNQ Quota', 'Hyd-Kar Quota', 'Special Category',
    'Reserved Category', 'Caste Name', 'Annual Income', 'Total Marks', 'Marks Obtained',
    'Science Marks', 'Maths Marks', 'Science+Math Total', 'Mobile', 'Parent Mobile',
    'Email', 'Address', 'Course Name', 'SSLC Register No', 'Institution Code',
    'College Name', 'Submission Date', 'Year of Passing', 'Pincode',
    'Allocated Category', 'Branch Allocated'
];


$sql = "
    SELECT s.*, sa.allocated_category, sa.branch_allocated
    FROM students s
    LEFT JOIN seat_allotment sa ON s.SSLC_Register = sa.SSLC_Register
";


$result = $conn->query($sql);

if (!$result) {
    fputcsv($output, ["SQL Error: " . $conn->error]);
    fclose($output);
    $conn->close();
    exit;
}

if ($result->num_rows == 0) {
    fputcsv($output, ["No records found."]);
    fclose($output);
    $conn->close();
    exit;
}


$branchData = [];


while ($row = $result->fetch_assoc()) {
    $branchCode = $row['branch_allocated'] ?? null;
    $branchName = isset($branchCode) && isset($branches[$branchCode]) ? $branches[$branchCode] : 'Not Assigned';

    if (!isset($branchData[$branchName])) {
        $branchData[$branchName] = [];
    }
    $branchData[$branchName][] = $row;
}

uksort($branchData, function($a, $b) {
    if ($a === 'Not Assigned') return 1;
    if ($b === 'Not Assigned') return -1;
    return strcmp($a, $b);
});


fputcsv($output, $headers);


foreach ($branchData as $branchName => $students) {

    fputcsv($output, []);
    fputcsv($output, ["Branch: $branchName (Total Students: " . count($students) . ")"]);

    foreach ($students as $student) {
        
        $allocatedCategory = trim($student['allocated_category'] ?? '');
        if ($allocatedCategory === '') {
            $allocatedCategory = 'Not Allotted';
        }

        fputcsv($output, [
            $student['id'] ?? '',
            $student['sats_no'] ?? '',
            $student['aadhar_no'] ?? '',
            $student['names'] ?? '',
            $student['mother_name'] ?? '',
            $student['father_name'] ?? '',
            $student['dob'] ?? '',
            $student['gender'] ?? '',
            $student['nationality'] ?? '',
            $student['religion'] ?? '',
            $student['exam_type'] ?? '',
            $student['native_state'] ?? '',
            $student['native_district'] ?? '',
            $student['state_appeared'] ?? '',
            $student['years_studied'] ?? '',
            $student['rural_study'] ?? '',
            $student['kannada_medium'] ?? '',
            $student['exemption_rule'] ?? '',
            $student['clause_code'] ?? '',
            $student['snq_quota'] ?? '',
            $student['hyd_kar_quota'] ?? '',
            $student['special_category'] ?? '',
            $student['reserved_category'] ?? '',
            $student['caste_name'] ?? '',
            $student['annual_income'] ?? '',
            $student['total_marks'] ?? '',
            $student['marks_obtained'] ?? '',
            $student['science_marks'] ?? '',
            $student['maths_marks'] ?? '',
            $student['science_math_total'] ?? '',
            $student['mobile'] ?? '',
            $student['parent_mobile'] ?? '',
            $student['email'] ?? '',
            $student['address'] ?? '',
            $student['course_name'] ?? '',
            $student['SSLC_Register'] ?? '',
            $student['institution_code'] ?? '',
            $student['college_name'] ?? '',
            $student['submission_date'] ?? '',
            $student['Year_of_passing'] ?? '',
            $student['pincode'] ?? '',
            $allocatedCategory,
            $branchName
        ]);
    }
}

fclose($output);
$conn->close();
?>

