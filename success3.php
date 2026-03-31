<?php
session_start();
include 'connection2.php'; 

if (!isset($_GET['register_number']) || !isset($_GET['branch_allocated']) || !isset($_GET['allocated_category']) || !isset($_GET['fees_paid']) || !isset($_GET['receipt_number'])) {
    echo "Invalid access!";
    exit();
}

$register_number = $_GET['register_number'];
$branch_allocated = $_GET['branch_allocated'];
$allocated_category = $_GET['allocated_category'];
$fees_paid = $_GET['fees_paid'];
$receipt_number = $_GET['receipt_number'];

$sql = "SELECT * FROM students WHERE SSLC_Register = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", $register_number);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
    } else {
        header("Location: error.php?message=" . urlencode("Student not found!"));
        exit();
    }
    $stmt->close();
} else {
    echo "Database query failed!";
    exit();
}

$application_fee = 'N/A'; 
$sql_fee = "SELECT application_fee FROM fees_receipt WHERE register_number = ?";
$stmt_fee = $conn->prepare($sql_fee);
if ($stmt_fee) {
    $stmt_fee->bind_param("s", $register_number);
    $stmt_fee->execute();
    $result_fee = $stmt_fee->get_result();
    if ($result_fee->num_rows > 0) {
        $fee_row = $result_fee->fetch_assoc();
        $application_fee = $fee_row['application_fee'];
    }
    $stmt_fee->close();
}

$student_photo = isset($student['student_photo']) ? trim($student['student_photo']) : null;
$student_photo_path = null;
if ($student_photo && $student_photo !== '') {
    if (strpos($student_photo, 'uploads/') === 0) {
        $student_photo_path = $student_photo;
    } else {
        $student_photo_path = "uploads/" . basename($student_photo);
    }
}

$branches = [
    1 => 'Computer Science',
    2 => 'Electronics & Electrical',
    3 => 'Mechanical Engineering',
    4 => 'Electronics & Communication',
    5 => 'Civil Engineering',
    6 => 'Automobile Engineering',
];

$branch_name = isset($branches[$branch_allocated]) ? $branches[$branch_allocated] : 'Unknown Branch';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Seat Allotment Success</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0; padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }
        nav {
            background-color: #4CAF50;
            padding: 10px;
            text-align: center;
        }
        nav a {
            color: white;
            margin: 0 15px;
            text-decoration: none;
            font-weight: bold;
        }
        nav a:hover {
            text-decoration: underline;
        }
        .container {
            max-width: 800px;
            margin: 30px auto 50px auto;
            padding: 40px;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: black;
            margin-bottom: 20px;
            text-align: center;
        }
        .content {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }
        .passport-photo {
            flex: 0 0 150px;
            margin-right: 20px;
        }
        .passport-photo img {
            width: 150px;
            height: 200px;
            object-fit: cover;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .info-grid {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px 25px;
        }
        .info-grid p {
            margin: 0;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #fafafa;
        }
        .info-grid p strong {
            color: black;
        }
        .info-grid p:last-child {
            grid-column: auto; 
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .back-link {
            margin-top: 30px;
            text-align: center;
        }
        .back-link a {
            color: black;
            text-decoration: none;
            font-weight: bold;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        .print-button {
            display: block;
            margin: 20px auto 40px auto;
            padding: 10px 25px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-align: center;
            text-decoration: none;
        }
        .print-button:hover {
            background-color: #45a049;
        }
        .signatures {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            flex-wrap: nowrap;
        }
        .signature {
            width: 30%;
            text-align: center;
            margin-top: 20px;
        }
        .signature p {
            margin-bottom: 10px;
            font-weight: bold;
            color: black;
        }
        .signature-line {
            border-top: 1px solid #000;
            height: 40px;
            margin: 0 auto;
            width: 80%;
        }
        @media (max-width: 900px) {
            .signatures {
                flex-wrap: wrap;
                justify-content: center;
            }
            .signature {
                width: 80%;
                margin-bottom: 30px;
            }
        }
        @media (max-width: 600px) {
            .content {
                flex-direction: column;
                align-items: flex-start;
            }
            .info-grid {
                grid-template-columns: 1fr;
            }
            .passport-photo {
                margin-right: 0;
                margin-bottom: 20px;
            }
        }
        @media print {
            body * {
                visibility: hidden;
            }
            .container, .container * {
                visibility: visible;
            }
            .container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
                background: #fff;
                border-radius: 0;
            }
            .print-button, .back-link {
                display: none;
            }
            nav {
                display: none;
            }
        }
    </style>
</head>
<body>

<nav>
    <a href="application_form.php">Application Form</a>
    <a href="fetch_marks.php">Fetch Marks</a>
    <a href="marks_card.php">Document Verification</a>
    <a href="view_marks.php">View Marks</a>
    <a href="seat_allotment.php">Seat Allotment</a>
</nav>

<div class="container">
    <h2>DACG Govt POLYTECHNIC</h2>
    <div class="content">
        <div class="passport-photo">
            <?php if ($student_photo_path && file_exists($student_photo_path)): ?>
                <?php
                    $ext = strtolower(pathinfo($student_photo_path, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])):
                        echo '<img src="' . htmlspecialchars($student_photo_path) . '" alt="Student Photo">';
                    elseif ($ext === 'pdf'):
                        echo '<embed src="' . htmlspecialchars($student_photo_path) . '" type="application/pdf" width="100%" height="200px" />';
                    else:
                        echo '<a href="' . htmlspecialchars($student_photo_path) . '" target="_blank">View uploaded document</a>';
                    endif;
                ?>
            <?php else: ?>
                <p class="error">Student image or document not found.</p>
            <?php endif; ?>
        </div>

        <div class="info-grid">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($student['names']); ?></p>
            <p><strong>SSLC Register Number:</strong> <?php echo htmlspecialchars($student['SSLC_Register']); ?></p>
            <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($student['dob']); ?></p>
            <p><strong>Gender:</strong> <?php echo htmlspecialchars($student['gender']); ?></p>
            <p><strong>Father Name:</strong> <?php echo htmlspecialchars($student['father_name']); ?></p>
            <p><strong>Category:</strong> <?php echo htmlspecialchars($student['reserved_category']); ?></p>
            <p><strong>Branch Allocated:</strong> <?php echo htmlspecialchars($branch_name); ?></p>
            <p><strong>Allocated Category:</strong> <?php echo htmlspecialchars($allocated_category); ?></p>
            <p><strong>Fees Paid Receipt:</strong> <?php echo htmlspecialchars($fees_paid); ?></p>
            <p><strong>Receipt Number:</strong> <?php echo htmlspecialchars($receipt_number); ?></p>
            <p><strong>Application Fees:</strong> <?php echo htmlspecialchars($application_fee); ?></p>
        </div>
    </div>

    <div class="signatures">
        <div class="signature">
            <p>Candidate Signature:</p>
            <div class="signature-line"></div>
        </div>
        <div class="signature">
            <p>Parent Signature:</p>
            <div class="signature-line"></div>
        </div>
        <div class="signature">
            <p>Principal Signature:</p>
            <div class="signature-line"></div>
        </div>
    </div>

    <div class="back-link">
        <p><a href="seat_allotment.php">← Back to Seat Allotment</a></p>
    </div>
    <button class="print-button" onclick="window.print()" type="button">Print this page</button>
</div>

</body>
</html>

  