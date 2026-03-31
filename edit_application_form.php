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

$existing_data = null;
$message = "";

function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

function getFileLink($filepath) {
    if ($filepath && file_exists($filepath)) {
        $filename = basename($filepath);
        return '<a href="' . htmlspecialchars($filepath) . '" target="_blank">' . htmlspecialchars($filename) . '</a>';
    }
    return 'No file uploaded';
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['search_submit'])) {
        $SSLC_Register = sanitize_input($_POST['SSLC_Register']);
        if ($SSLC_Register !== "") {
            $stmt_check = $conn->prepare("SELECT * FROM students WHERE SSLC_Register = ?");
            $stmt_check->bind_param("s", $SSLC_Register);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            if ($result_check->num_rows > 0) {
                $existing_data = $result_check->fetch_assoc();
                $message = "Application found. You can edit your details below.";
            } else {
                $message = "No application found with that SSLC Register number.";
            }
            $stmt_check->close();
        } else {
            $message = "Please enter your SSLC Register number to search.";
        }
    } elseif (isset($_POST['update'])) {
        // Update existing application
        $fields = [
            'sats_no','aadhar_no','names','mother_name','father_name','dob','gender','nationality',
            'religion','exam_type','native_state','native_district','years_studied','rural_study',
            'kannada_medium','exemption_rule','clause_code','snq_quota','hyd_kar_quota','special_category',
            'reserved_category','caste_name','annual_income','marks_obtained','science_marks',
            'maths_marks','mobile','parent_mobile','email','address','course_name',
            'institution_code','college_name','signature_candidate','signature_parent',
            'submission_date','Year_of_passing','pincode','state_appeared'
        ];

        $data = [];
        foreach ($fields as $field) {
            $data[$field] = isset($_POST[$field]) ? sanitize_input($_POST[$field]) : null;
        }

        // Get SSLC_Register from hidden field for WHERE clause
        $SSLC_Register = isset($_POST['SSLC_Register']) ? sanitize_input($_POST['SSLC_Register']) : '';

        // Fetch existing data for preservation
        $stmt_existing = $conn->prepare("SELECT * FROM students WHERE SSLC_Register = ?");
        $stmt_existing->bind_param("s", $SSLC_Register);
        $stmt_existing->execute();
        $res_existing = $stmt_existing->get_result();
        $existing_files = $res_existing->fetch_assoc();
        $stmt_existing->close();

        // Calculate derived marks fields and percentage (if any marks provided)
        $science_marks = isset($data['science_marks']) ? (int)$data['science_marks'] : (int)($existing_files['science_marks'] ?? 0);
        $maths_marks = isset($data['maths_marks']) ? (int)$data['maths_marks'] : (int)($existing_files['maths_marks'] ?? 0);
        $marks_obtained = isset($data['marks_obtained']) ? (int)$data['marks_obtained'] : (int)($existing_files['marks_obtained'] ?? 0);
        $science_math_total = $science_marks + $maths_marks;
        $total_marks = 625;
        $percentage = $total_marks > 0 ? round(($marks_obtained / $total_marks) * 100, 2) : 0;

        // Setup uploads directory
        $uploads_dir = 'uploads';
        if (!is_dir($uploads_dir)) {
            mkdir($uploads_dir, 0755, true);
        }

        $documents = [
            'sslc_marks_card',
            'tc',
            'caste_certificate',
            'income_certificate',
            'study_certificate',
            'kannada_medium_certificate',
            'rural_quota_certificate',
            'special_quota_certificate',
            'aadhar_card',
            'student_photo'
        ];

        $file_paths = [];

        // Process file uploads: Only update if new file uploaded, else preserve existing file path
        foreach ($documents as $doc) {
            if (isset($_FILES[$doc]) && $_FILES[$doc]['error'] == UPLOAD_ERR_OK && is_uploaded_file($_FILES[$doc]['tmp_name'])) {
                $tmp_name = $_FILES[$doc]['tmp_name'];
                $safe_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES[$doc]['name']));
                $file_path = "$uploads_dir/" . time() . "_" . $safe_name;
                if (move_uploaded_file($tmp_name, $file_path)) {
                    $file_paths[$doc] = $file_path;
                    // Delete old file if differs
                    if (!empty($existing_files[$doc]) && $existing_files[$doc] != $file_path && file_exists($existing_files[$doc])) {
                        @unlink($existing_files[$doc]);
                    }
                } else {
                    $file_paths[$doc] = $existing_files[$doc] ?? null;
                }
            } else {
                $file_paths[$doc] = $existing_files[$doc] ?? null;
            }
        }

        // Start preparing dynamic update statement
        $update_fields = [];
        $update_values = [];
        $types = '';

        // Add normal fields if they are non-null in $data
        foreach ($fields as $field) {
            if ($data[$field] !== null) {
                $update_fields[] = "$field = ?";
                $update_values[] = $data[$field];
                $types .= 's'; // assuming all string for simplicity
            }
        }

        // Add calculated fields (overwrite or add these)
        $update_fields[] = "science_math_total = ?";
        $update_values[] = $science_math_total;
        $types .= 'i';

        $update_fields[] = "percentage = ?";
        $update_values[] = $percentage;
        $types .= 'd';

        // Add file paths fields (strings, can be null)
        foreach ($documents as $doc) {
            // Update files only if path known, else keep null
            $update_fields[] = "$doc = ?";
            $update_values[] = $file_paths[$doc] ?? null;
            $types .= 's';
        }

        // Final WHERE clause parameter
        $types .= 's';
        $update_values[] = $SSLC_Register;

        // Build update query
        if (count($update_fields) > 0) {
            $update_sql = "UPDATE students SET " . implode(", ", $update_fields) . " WHERE SSLC_Register = ?";
            $stmt = $conn->prepare($update_sql);
            if (!$stmt) {
                $message = "Prepare failed: (" . $conn->errno . ") " . $conn->error;
            } else {
                // Bind params dynamically
                $refs = [];
                foreach ($update_values as $key => $value) {
                    $refs[$key] = &$update_values[$key];
                }
                array_unshift($refs, $types);

                call_user_func_array([$stmt, 'bind_param'], $refs);

                if ($stmt->execute()) {
                    $message = "Application updated successfully.";
                    // Reload updated data
                    $stmt_reload = $conn->prepare("SELECT * FROM students WHERE SSLC_Register = ?");
                    $stmt_reload->bind_param("s", $SSLC_Register);
                    $stmt_reload->execute();
                    $res_reload = $stmt_reload->get_result();
                    if ($res_reload && $res_reload->num_rows > 0) {
                        $existing_data = $res_reload->fetch_assoc();
                    }
                    $stmt_reload->close();
                } else {
                    $message = "Error updating record: " . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $message = "No fields to update.";
        }
    }
}

$conn->close();

function val($field) {
    global $existing_data;
    return htmlspecialchars($existing_data[$field] ?? '');
}

function checked($field, $value) {
    global $existing_data;
    return (isset($existing_data[$field]) && $existing_data[$field] == $value) ? 'checked' : '';
}

function selected($field, $value) {
    global $existing_data;
    return (isset($existing_data[$field]) && $existing_data[$field] == $value) ? 'selected' : '';
}

function fileLinkOrText($field) {
    global $existing_data;
    return getFileLink($existing_data[$field] ?? null);
}

$SSLC_Register = $existing_data['SSLC_Register'] ?? '';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Diploma Admission Application</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 30px;
            font-size: 18px;
        }
        h2 {
            text-align: center;
            color: #333;
            font-size: 28px;
            margin-bottom: 20px;
        }
        .message, .error-message {
            max-width: 850px;
            margin: 10px auto;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            font-size: 18px;
            border: 1px solid;
        }
        .message {
            background-color: #e0ffe0;
            color: #2c662d;
            border-color: #2c662d;
        }
        .error-message {
            background-color: #ffe0e0;
            color: #662c2c;
            border-color: #662c2c;
        }
        form {
            background: #fff;
            padding: 30px 40px;
            border-radius: 8px;
            box-shadow: 0 0 12px rgba(0,0,0,0.15);
            max-width: 850px;
            margin: 0 auto 40px auto;
        }
        label {
            display: block;
            margin: 15px 0 6px 0;
            font-weight: 600;
        }
        input[type="text"], input[type="number"], input[type="date"], input[type="email"], textarea, select {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 12px;
            border: 1.5px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        textarea {
            resize: vertical;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 14px 0;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 600;
            width: 100%;
            margin-top: 15px;
            transition: background-color 0.3s ease;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        .radio-group {
            display: flex;
            flex-direction: column;
            max-width: 200px;
            margin: 5px 0 15px 0;
            font-size: 18px;
        }
        .radio-group label {
            font-weight: normal;
            padding-left: 10px;
            margin: 6px 0;
            cursor: pointer;
        }
        input[readonly] {
            background-color: #f9f9f9;
            border-color: #bbb;
        }
        nav {
            background-color: #4CAF50;
            padding: 12px;
            text-align: center;
            margin-bottom: 20px;
            font-size: 18px;
        }
        nav a {
            color: white;
            margin: 0 16px;
            text-decoration: none;
            font-weight: 600;
        }
        nav a:hover {
            text-decoration: underline;
        }
        .file-link {
            margin-bottom: 12px;
            font-size: 14px;
        }
    </style>
    <script>
        function calculateScienceMathTotal() {
            const science = parseFloat(document.getElementById('science_marks').value) || 0;
            const maths = parseFloat(document.getElementById('maths_marks').value) || 0;
            const total = science + maths;
            document.getElementById('science_math_total').value = total;

            const marksObtained = parseFloat(document.getElementById('marks_obtained').value) || 0;
            const totalMarks = 625;
            const percentage = totalMarks > 0 ? ((marksObtained / totalMarks) * 100).toFixed(2) : '0';
            document.getElementById('percentage').value = percentage;
        }

        function calculatePercentage() {
            const marksObtained = parseFloat(document.getElementById('marks_obtained').value) || 0;
            const totalMarks = 625;
            const percentage = totalMarks > 0 ? ((marksObtained / totalMarks) * 100).toFixed(2) : '0';
            document.getElementById('percentage').value = percentage;
        }

        window.addEventListener('DOMContentLoaded', () => {
            document.getElementById('science_marks').addEventListener('input', calculateScienceMathTotal);
            document.getElementById('maths_marks').addEventListener('input', calculateScienceMathTotal);
            document.getElementById('marks_obtained').addEventListener('input', calculatePercentage);
        });
    </script>
</head>
<body>
<nav>
    <a href="home.php">Home</a>
    <a href="branch.php">Branches</a>
    <a href="admission_details.php">Admission Details</a>
    <a href="fees_details.php">Fees Details</a>
    <a href="application_form.php">Application Form</a>
    <a href="fetch_marks.php">Document verification</a>
    <a href="view_marks.php">view marks</a>
    <a href="seat_allotment.php">Seat Allotment</a>
    <a href="seat_details.php">Seat Allotted</a>
    <a href="seat_balances.php">Seat Balance</a>
    <a href="document_details.php">Document Details</a>
    <a href="merit_list.php">Check Merit</a>
</nav>

<h2>Edit Diploma Admission Application</h2>

<?php if (!empty($message)): ?>
    <div class="<?php echo (strpos($message, 'Error') === false) ? 'message' : 'error-message'; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<form method="post" style="max-width:400px; margin: 20px auto;">
    <label>Enter SSLC Register Number to Load Application:</label>
    <input type="text" name="SSLC_Register" required>
    <input type="submit" name="search_submit" value="Load Application">
</form>

<?php if ($existing_data): ?>
<form method="post" enctype="multipart/form-data" autocomplete="off" style="max-width:850px; margin: 0 auto;">
    <input type="hidden" name="SSLC_Register" value="<?php echo htmlspecialchars($SSLC_Register); ?>">

    <label>1. SATS No:</label>
    <input type="text" name="sats_no" value="<?php echo val('sats_no'); ?>">

    <label>2. Aadhar No:</label>
    <input type="text" name="aadhar_no" pattern="^\d{12}$" title="Please enter a valid 12-digit Aadhar number." value="<?php echo val('aadhar_no'); ?>">

    <label>3. Name:</label>
    <input type="text" name="names" value="<?php echo val('names'); ?>">

    <label>4. Mother's Name:</label>
    <input type="text" name="mother_name" value="<?php echo val('mother_name'); ?>">

    <label>5. Father's Name:</label>
    <input type="text" name="father_name" value="<?php echo val('father_name'); ?>">

    <label>6. Date of Birth:</label>
    <input type="date" name="dob" value="<?php echo val('dob'); ?>">

    <label>7. Gender:</label>
    <select name="gender">
        <option value="">Select Gender</option>
        <option value="Male" <?php echo selected('gender', 'Male'); ?>>Male</option>
        <option value="Female" <?php echo selected('gender', 'Female'); ?>>Female</option>
    </select>

    <label>8. Indian Nationality:</label>
    <div class="radio-group">
        <label><input type="radio" name="nationality" value="Yes" <?php echo checked('nationality', 'Yes'); ?>> Yes</label>
        <label><input type="radio" name="nationality" value="No" <?php echo checked('nationality', 'No'); ?>> No</label>
    </div>

    <label>9. Religion:</label>
    <input type="text" name="religion" value="<?php echo val('religion'); ?>">

    <label>10. Qualifying Examination:</label>
    <div class="radio-group">
        <label><input type="radio" name="exam_type" value="SSLC" <?php echo checked('exam_type', 'SSLC'); ?>> SSLC</label>
        <label><input type="radio" name="exam_type" value="CBSE" <?php echo checked('exam_type', 'CBSE'); ?>> CBSE</label>
        <label><input type="radio" name="exam_type" value="ICSE" <?php echo checked('exam_type', 'ICSE'); ?>> ICSE</label>
        <label><input type="radio" name="exam_type" value="Others" <?php echo checked('exam_type', 'Others'); ?>> Others</label>
    </div>

    <label>11. Code of the Native State:</label>
    <input type="text" name="native_state" value="<?php echo val('native_state'); ?>">

    <label>12. Karnataka, code of the Native District:</label>
    <input type="text" name="native_district" value="<?php echo val('native_district'); ?>">

    <label>13. Code of the State appeared for SSLC:</label>
    <input type="text" name="state_appeared" value="<?php echo val('state_appeared'); ?>">

    <label>14. Total number of Years Studied in Karnataka:</label>
    <input type="number" name="years_studied" value="<?php echo val('years_studied'); ?>">

    <label>15. Have you studied in Rural areas (1st to 10th):</label>
    <div class="radio-group">
        <label><input type="radio" id="rural_yes" name="rural_study" value="Yes" <?php echo checked('rural_study', 'Yes'); ?>> Yes</label>
        <label><input type="radio" id="rural_no" name="rural_study" value="No" <?php echo checked('rural_study', 'No'); ?>> No</label>
    </div>

    <label>16. Have you studied in Kannada Medium (from 1st to 10th):</label>
    <div class="radio-group">
        <label><input type="radio" id="kannada_yes" name="kannada_medium" value="Yes" <?php echo checked('kannada_medium', 'Yes'); ?>> Yes</label>
        <label><input type="radio" id="kannada_no" name="kannada_medium" value="No" <?php echo checked('kannada_medium', 'No'); ?>> No</label>
    </div>

    <label>17. Do you claim Exemption from the 5-year study rule?</label>
    <div class="radio-group">
        <label><input type="radio" id="exemption_yes" name="exemption_rule" value="Yes" <?php echo checked('exemption_rule', 'Yes'); ?>> Yes</label>
        <label><input type="radio" id="exemption_no" name="exemption_rule" value="No" <?php echo checked('exemption_rule', 'No'); ?>> No</label>
    </div>

    <label>18. If yes, mention Clause Code:</label>
    <input type="text" name="clause_code" value="<?php echo val('clause_code'); ?>">

    <label>19. Do you claim SNQ Quota?</label>
    <div class="radio-group">
        <label><input type="radio" id="snq_yes" name="snq_quota" value="Yes" <?php echo checked('snq_quota', 'Yes'); ?>> Yes</label>
        <label><input type="radio" id="snq_no" name="snq_quota" value="No" <?php echo checked('snq_quota', 'No'); ?>> No</label>
    </div>

    <label>20. Do you claim Hyderabad-Karnataka Quota?</label>
    <div class="radio-group">
        <label><input type="radio" id="hyd_kar_yes" name="hyd_kar_quota" value="Yes" <?php echo checked('hyd_kar_quota', 'Yes'); ?>> Yes</label>
        <label><input type="radio" id="hyd_kar_no" name="hyd_kar_quota" value="No" <?php echo checked('hyd_kar_quota', 'No'); ?>> No</label>
    </div>

    <label>21. Do you claim Special Category?</label>
    <select name="special_category" id="special_category">
        <option value="" disabled <?php echo !val('special_category') ? 'selected' : ''; ?>>Select an option</option>
        <?php
        $specialOptions = ["NCC", "JTS", "JOC", "EDP", "DP", "PS", "SP", "AI", "CI", "HK", "GK", "ITI", "SG", "PH"];
        foreach ($specialOptions as $opt) {
            echo '<option value="'. $opt .'" '. selected('special_category', $opt) .'>'. $opt .'</option>';
        }
        ?>
    </select>

    <label>22. Reserved Category code:</label>
    <input type="text" name="reserved_category" value="<?php echo val('reserved_category'); ?>">

    <label>23. Caste Name:</label>
    <input type="text" name="caste_name" value="<?php echo val('caste_name'); ?>">

    <label>24. Annual Income:</label>
    <input type="number" name="annual_income" value="<?php echo val('annual_income'); ?>">

    <p><b>25. Educational particulars and marks details</b></p>

    <label>Marks obtained:</label>
    <input type="number" name="marks_obtained" id="marks_obtained" value="<?php echo val('marks_obtained'); ?>" oninput="calculatePercentage()" >

    <label>Science Marks:</label>
    <input type="number" name="science_marks" id="science_marks" value="<?php echo val('science_marks'); ?>" oninput="calculateScienceMathTotal()" >

    <label>Maths Marks:</label>
    <input type="number" name="maths_marks" id="maths_marks" value="<?php echo val('maths_marks'); ?>" oninput="calculateScienceMathTotal()" >

    <label>Science &amp; Maths Total:</label>
    <input type="number" name="science_math_total" id="science_math_total" value="<?php echo htmlspecialchars((int)(val('science_marks')) + (int)(val('maths_marks'))); ?>" readonly>

    <label>Percentage:</label>
    <input type="text" name="percentage" id="percentage" value="<?php echo val('percentage'); ?>" readonly>

    <label>26. Student Mobile No.:</label>
    <input type="text" name="mobile" pattern="^\d{10}$" title="Please enter a valid 10-digit mobile number." value="<?php echo val('mobile'); ?>">

    <label>27. Address:</label>
    <textarea name="address" rows="3"><?php echo val('address'); ?></textarea>

    <label>28. Parent Mobile No.:</label>
    <input type="text" name="parent_mobile" pattern="^\d{10}$" title="Please enter a valid 10-digit mobile number." value="<?php echo val('parent_mobile'); ?>">

    <label>29. Email:</label>
    <input type="email" name="email" value="<?php echo val('email'); ?>">

    <label>30. PIN CODE:</label>
    <textarea name="pincode" rows="2"><?php echo val('pincode'); ?></textarea>

    <label>31. Course Name:</label>
    <input type="text" name="course_name" value="<?php echo val('course_name'); ?>">

    <label>32. Institution Code:</label>
    <input type="text" name="institution_code" value="<?php echo val('institution_code'); ?>">

    <label>33. College Name:</label>
    <input type="text" name="college_name" value="<?php echo val('college_name'); ?>">

    <label>34. Candidate Signature:</label>
    <input type="text" name="signature_candidate" value="<?php echo val('signature_candidate'); ?>">

    <label>35. Parent Signature:</label>
    <input type="text" name="signature_parent" value="<?php echo val('signature_parent'); ?>">

    <label>36. Submission Date:</label>
    <input type="date" name="submission_date" value="<?php echo val('submission_date'); ?>">

    <h3>Documents Upload Section (Upload to replace existing)</h3>

    <label>SSLC Marks Card:</label>
    <div class="file-link"><?php echo fileLinkOrText('sslc_marks_card'); ?></div>
    <input type="file" name="sslc_marks_card">

    <label>Student Photo:</label>
    <div class="file-link"><?php echo fileLinkOrText('student_photo'); ?></div>
    <input type="file" name="student_photo">

    <label>Transfer Certificate:</label>
    <div class="file-link"><?php echo fileLinkOrText('tc'); ?></div>
    <input type="file" name="tc">

    <label>Caste Certificate:</label>
    <div class="file-link"><?php echo fileLinkOrText('caste_certificate'); ?></div>
    <input type="file" name="caste_certificate">

    <label>Income Certificate:</label>
    <div class="file-link"><?php echo fileLinkOrText('income_certificate'); ?></div>
    <input type="file" name="income_certificate">

    <label>Study Certificate:</label>
    <div class="file-link"><?php echo fileLinkOrText('study_certificate'); ?></div>
    <input type="file" name="study_certificate">

    <label>Kannada Medium Eligibility Certificate:</label>
    <div class="file-link"><?php echo fileLinkOrText('kannada_medium_certificate'); ?></div>
    <input type="file" name="kannada_medium_certificate">

    <label>Rural Quota Eligibility Certificate:</label>
    <div class="file-link"><?php echo fileLinkOrText('rural_quota_certificate'); ?></div>
    <input type="file" name="rural_quota_certificate">

    <label>Special Quota Eligibility Certificate:</label>
    <div class="file-link"><?php echo fileLinkOrText('special_quota_certificate'); ?></div>
    <input type="file" name="special_quota_certificate">

    <label>Aadhar Card:</label>
    <div class="file-link"><?php echo fileLinkOrText('aadhar_card'); ?></div>
    <input type="file" name="aadhar_card">

    <input type="submit" name="update" value="Update Application">
</form>
<?php endif; ?>

<script>
    function calculateScienceMathTotal() {
        const science = parseFloat(document.getElementById('science_marks').value) || 0;
        const maths = parseFloat(document.getElementById('maths_marks').value) || 0;
        const total = science + maths;
        document.getElementById('science_math_total').value = total;

        const marksObtained = parseFloat(document.getElementById('marks_obtained').value) || 0;
        const totalMarks = 625;
        const percentage = totalMarks > 0 ? ((marksObtained / totalMarks) * 100).toFixed(2) : '0';
        document.getElementById('percentage').value = percentage;
    }

    function calculatePercentage() {
        const marksObtained = parseFloat(document.getElementById('marks_obtained').value) || 0;
        const totalMarks = 625;
        const percentage = totalMarks > 0 ? ((marksObtained / totalMarks) * 100).toFixed(2) : '0';
        document.getElementById('percentage').value = percentage;
    }

    window.addEventListener('DOMContentLoaded', () => {
        document.getElementById('science_marks').addEventListener('input', calculateScienceMathTotal);
        document.getElementById('maths_marks').addEventListener('input', calculateScienceMathTotal);
        document.getElementById('marks_obtained').addEventListener('input', calculatePercentage);
    });
</script>

</body>
</html>

