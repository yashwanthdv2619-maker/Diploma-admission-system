<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "diploma_admission";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Filters
$branch = $_POST['branch'] ?? 'all';
$category = $_POST['category'] ?? 'all';
$gender = $_POST['gender'] ?? 'all';
$sortOrder = $_POST['sort_order'] ?? 'desc'; // New: Sort order

$query = "SELECT * FROM students WHERE 1=1";

if ($branch !== 'all') {
    $query .= " AND course_name = '$branch'";
}
if ($category !== 'all') {
    $query .= " AND reserved_category = '$category'";
}
if ($gender !== 'all') {
    $query .= " AND gender = '$gender'";
}

// Sort logic
$sortOrder = strtolower($sortOrder) === 'asc' ? 'ASC' : 'DESC';
$query .= " ORDER BY marks_obtained $sortOrder";

$result = $conn->query($query);
$students = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

$totalApplicationsQuery = "SELECT COUNT(*) as total FROM students";
$totalApplicationsResult = $conn->query($totalApplicationsQuery);
$totalApplications = $totalApplicationsResult->fetch_assoc()['total'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
     <nav>
        <a href="branch.php">Branches</a>
        <div class="dropdown">
            <a href="#">Details</a>
            <div class="dropdown-content">
                <a href="admission_details.php">Admission Details</a>
                <a href="fees_details.php">Fees Details</a>
            </div>
        </div>
        <a href="application_form.php">Application Form</a>
        <a href="fetch_marks.php">Fetch Marks</a>
        <a href="marks_card.php">Document Verification</a>
        <a href="view_marks.php">View Marks</a>
        <a href="seat_allotment.php">Seat Allotment</a>
        <a href="seat_details.php">Alloted Seat</a>
        <a href="seat_balances.php">Seat Balance</a>
        <a href="document_details.php">Documnet Details</a>
        <a href="seat_allotment_count.php"> Alloted Seat Count </a>
        <a href="merit_list.php"> Merit list </a>
        <a href="export_students.php"> Export </a>
    </nav>
    <meta charset="UTF-8">
    <title>Merit List</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #4CAF50; color: white; }
        select, input[type="submit"] {
            margin: 10px;
            padding: 6px;
            font-size: 14px;
        }
        
        nav {
            background-color: green; /* Semi-transparent black background */
            padding: 8px;
            position: fixed; /* Fixed position for sticky navigation */
            width: 100%;
            top: 0;
            z-index: 10; /* Higher z-index to stay above the background */
            display: flex; /* Display navigation links in a single line */
            justify-content: center; /* Center the navigation links */
            align-items: center; /* Align the links vertically */
            flex-wrap: wrap; /* Wrap links to the next line if necessary */
            gap: 20px; /* Space between the items */
        }

        nav a {
            color: white; /* Change text color to white for visibility */
            margin: 0 15px;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
            padding: 10px; /* Add padding to the links */
            position: relative; /* Position relative for dropdown */
        }

        nav a:hover {
            text-decoration: underline;
            color: #d1e7dd; /* Light color on hover */
        }

        .dropdown {
            position: relative; /* Position relative for dropdown */
        }

        .dropdown-content {
            display: none; /* Hidden by default */
            position: absolute; /* Position absolute for dropdown items */
            background-color: rgba(255, 255, 255, 0.9); /* White background with transparency */
            min-width: 160px; /* Minimum width for dropdown */
            z-index: 1; /* Ensure dropdown is above other content */
            border-radius: 5px; /* Rounded corners */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Shadow for dropdown */
            top: 100%; /* Position below the dropdown link */
            left: 0; /* Align to the left of the dropdown link */
        }

        .dropdown:hover .dropdown-content {
            display: block; /* Show dropdown on hover */
        }

        .dropdown-content a {
            color: black; /* Text color for dropdown items */
            padding: 12px 16px; /* Padding for dropdown items */
            text-decoration: none; /* No underline */
            display: block; /* Block display for dropdown items */
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1; /* Light background on hover */
        }
    </style>
</head>
<body>

<h1>Merit List Before Seat Allotment</h1>

<form method="POST" action="merit_list.php">
    <label for="branch">Select Branch:</label>
    <select name="branch" id="branch">
        <option value="all" <?= $branch == 'all' ? 'selected' : '' ?>>All</option>
        <option value="CE" <?= $branch == 'CE' ? 'selected' : '' ?>>CE</option>
        <option value="CS" <?= $branch == 'CS' ? 'selected' : '' ?>>CS</option>
        <option value="ECE" <?= $branch == 'ECE' ? 'selected' : '' ?>>ECE</option>
        <option value="EE" <?= $branch == 'EE' ? 'selected' : '' ?>>EE</option>
        <option value="ME" <?= $branch == 'ME' ? 'selected' : '' ?>>ME</option>
    </select>

    <label for="category">Select Category:</label>
    <select name="category" id="category">
        <option value="all" <?= $category == 'all' ? 'selected' : '' ?>>All</option>
        <option value="2A" <?= $category == '2A' ? 'selected' : '' ?>>2A</option>
        <option value="2B" <?= $category == '2B' ? 'selected' : '' ?>>2B</option>
        <option value="3A" <?= $category == '3A' ? 'selected' : '' ?>>3A</option>
        <option value="3B" <?= $category == '3B' ? 'selected' : '' ?>>3B</option>
        <option value="SC" <?= $category == 'SC' ? 'selected' : '' ?>>SC</option>
        <option value="ST" <?= $category == 'ST' ? 'selected' : '' ?>>ST</option>
    </select>

    <label for="gender">Select Gender:</label>
    <select name="gender" id="gender">
        <option value="all" <?= $gender == 'all' ? 'selected' : '' ?>>All</option>
        <option value="Male" <?= $gender == 'Male' ? 'selected' : '' ?>>Male</option>
        <option value="Female" <?= $gender == 'Female' ? 'selected' : '' ?>>Female</option>
    </select>

    <label for="sort_order">Sort by Merit:</label>
    <select name="sort_order" id="sort_order">
        <option value="desc" <?= $sortOrder === 'DESC' ? 'selected' : '' ?>>Highest to Lowest</option>
        <option value="asc" <?= $sortOrder === 'ASC' ? 'selected' : '' ?>>Lowest to Highest</option>
    </select>

    <input type="submit" value="Filter">
</form>

<h2>Total Applications: <?= $totalApplications ?></h2>

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Register Number</th>
            <th>Caste</th>
            <th>Gender</th>
            <th>Mobile Number</th>
            <th>Marks Obtained</th>
            <th>Branch</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($students)): ?>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?= htmlspecialchars($student['names']) ?></td>
                    <td><?= htmlspecialchars($student['SSLC_Register']) ?></td>
                    <td><?= htmlspecialchars($student['reserved_category']) ?></td>
                    <td><?= htmlspecialchars($student['gender']) ?></td>
                    <td><?= htmlspecialchars($student['mobile']) ?></td>
                    <td><?= htmlspecialchars($student['marks_obtained']) ?></td>
                    <td><?= htmlspecialchars($student['course_name']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">No students found for selected filters.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
