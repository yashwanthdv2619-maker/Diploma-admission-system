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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if this is a status check request
    if (isset($_POST['search_submit'])) {
        $search_value = sanitize_input($_POST['search_number']);
        if ($search_value !== "") {
            $stmt = $conn->prepare("SELECT * FROM students WHERE SSLC_Register = ? OR application_number = ?");
            $stmt->bind_param("ss", $search_value, $search_value);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $existing_data = $result->fetch_assoc();
                $message = "Application already exists. You cannot submit again.";
            } else {
                $message = "No application found with that number. You can fill a new application below.";
            }
            $stmt->close();
        }
    } else {
        $SSLC_Register = sanitize_input($_POST['SSLC_Register']);

        // Check if application already exists for this SSLC_Register
        $stmt_check = $conn->prepare("SELECT * FROM students WHERE SSLC_Register = ?");
        $stmt_check->bind_param("s", $SSLC_Register);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            $existing_data = $result_check->fetch_assoc();
            $message = "Application already exists. You cannot submit again.";
        } else {
            // Prepare insert operation
            $fields = [
                'sats_no','aadhar_no','names','mother_name','father_name','dob','gender','nationality',
                'religion','exam_type','native_state','native_district','years_studied','rural_study',
                'kannada_medium','exemption_rule','clause_code','snq_quota','hyd_kar_quota','special_category',
                'reserved_category','caste_name','annual_income','total_marks','marks_obtained','science_marks',
                'maths_marks','science_math_total','mobile','parent_mobile','email','address','course_name',
                'SSLC_Register','institution_code','college_name','signature_candidate','signature_parent',
                'submission_date','Year_of_passing','pincode','state_appeared'
            ];

            $data = [];
            foreach ($fields as $field) {
                $data[$field] = isset($_POST[$field]) ? sanitize_input($_POST[$field]) : "";
            }

            $data['science_math_total'] = (int)$data['science_marks'] + (int)$data['maths_marks'];

            $total_marks = 625;
            $marks_obtained = isset($data['marks_obtained']) ? (int)$data['marks_obtained'] : 0;
            $percentage = $total_marks > 0 ? round(($marks_obtained / $total_marks) * 100, 2) : 0;

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
            foreach ($documents as $doc) {
                if (isset($_FILES[$doc]) && $_FILES[$doc]['error'] == UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES[$doc]['tmp_name'];
                    $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES[$doc]['name']));
                    $file_path = "$uploads_dir/$name";
                    move_uploaded_file($tmp_name, $file_path);
                    $file_paths[$doc] = $file_path;
                } else {
                    $file_paths[$doc] = null;
                }
            }

            $insert_sql = "INSERT INTO students (
                sats_no,aadhar_no,names,mother_name,father_name,dob,gender,nationality,
                religion,exam_type,native_state,native_district,years_studied,rural_study,
                kannada_medium,exemption_rule,clause_code,snq_quota,hyd_kar_quota,special_category,
                reserved_category,caste_name,annual_income,total_marks,marks_obtained,science_marks,
                maths_marks,science_math_total,mobile,parent_mobile,email,address,course_name,
                SSLC_Register,institution_code,college_name,signature_candidate,signature_parent,
                submission_date,Year_of_passing,pincode,state_appeared,
                sslc_marks_card,tc,caste_certificate,income_certificate,study_certificate,
                kannada_medium_certificate,rural_quota_certificate,special_quota_certificate,aadhar_card,
                student_photo,percentage
            ) VALUES (
                '". $conn->real_escape_string($data['sats_no']) ."',
                '". $conn->real_escape_string($data['aadhar_no']) ."',
                '". $conn->real_escape_string($data['names']) ."',
                '". $conn->real_escape_string($data['mother_name']) ."',
                '". $conn->real_escape_string($data['father_name']) ."',
                '". $conn->real_escape_string($data['dob']) ."',
                '". $conn->real_escape_string($data['gender']) ."',
                '". $conn->real_escape_string($data['nationality']) ."',
                '". $conn->real_escape_string($data['religion']) ."',
                '". $conn->real_escape_string($data['exam_type']) ."',
                '". $conn->real_escape_string($data['native_state']) ."',
                '". $conn->real_escape_string($data['native_district']) ."',
                '". $conn->real_escape_string($data['years_studied']) ."',
                '". $conn->real_escape_string($data['rural_study']) ."',
                '". $conn->real_escape_string($data['kannada_medium']) ."',
                '". $conn->real_escape_string($data['exemption_rule']) ."',
                '". $conn->real_escape_string($data['clause_code']) ."',
                '". $conn->real_escape_string($data['snq_quota']) ."',
                '". $conn->real_escape_string($data['hyd_kar_quota']) ."',
                '". $conn->real_escape_string($data['special_category']) ."',
                '". $conn->real_escape_string($data['reserved_category']) ."',
                '". $conn->real_escape_string($data['caste_name']) ."',
                '". $conn->real_escape_string($data['annual_income']) ."',
                '". $conn->real_escape_string($total_marks) ."',
                '". $conn->real_escape_string($marks_obtained) ."',
                '". $conn->real_escape_string($data['science_marks']) ."',
                '". $conn->real_escape_string($data['maths_marks']) ."',
                '". $conn->real_escape_string($data['science_math_total']) ."',
                '". $conn->real_escape_string($data['mobile']) ."',
                '". $conn->real_escape_string($data['parent_mobile']) ."',
                '". $conn->real_escape_string($data['email']) ."',
                '". $conn->real_escape_string($data['address']) ."',
                '". $conn->real_escape_string($data['course_name']) ."',
                '". $conn->real_escape_string($data['SSLC_Register']) ."',
                '". $conn->real_escape_string($data['institution_code']) ."',
                '". $conn->real_escape_string($data['college_name']) ."',
                '". $conn->real_escape_string($data['signature_candidate']) ."',
                '". $conn->real_escape_string($data['signature_parent']) ."',
                '". $conn->real_escape_string($data['submission_date']) ."',
                '". $conn->real_escape_string($data['Year_of_passing']) ."',
                '". $conn->real_escape_string($data['pincode']) ."',
                '". $conn->real_escape_string($data['state_appeared']) ."',
                '". $conn->real_escape_string($file_paths['sslc_marks_card']) ."',
                '". $conn->real_escape_string($file_paths['tc']) ."',
                '". $conn->real_escape_string($file_paths['caste_certificate']) ."',
                '". $conn->real_escape_string($file_paths['income_certificate']) ."',
                '". $conn->real_escape_string($file_paths['study_certificate']) ."',
                '". $conn->real_escape_string($file_paths['kannada_medium_certificate']) ."',
                '". $conn->real_escape_string($file_paths['rural_quota_certificate']) ."',
                '". $conn->real_escape_string($file_paths['special_quota_certificate']) ."',
                '". $conn->real_escape_string($file_paths['aadhar_card']) ."',
                '". $conn->real_escape_string($file_paths['student_photo']) ."',
                '". $conn->real_escape_string($percentage) ."'
            )";

            if ($conn->query($insert_sql) === TRUE) {
                header("Location: success.php");
                exit();
            } else {
                $message = "Error inserting record: " . $conn->error;
                $existing_data = $data;
            }
        }
        $stmt_check->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Diploma Admission Form</title>
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
        input[type="submit"], button {
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
        input[type="submit"]:hover, button:hover {
            background-color: #45a049;
        }
        .message {
            max-width: 850px;
            margin: 10px auto;
            padding: 12px 20px;
            border-radius: 8px;
            background-color: #e0ffe0;
            color: #2c662d;
            font-weight: 600;
            text-align: center;
            font-size: 18px;
            border: 1px solid #2c662d;
        }
        .error-message {
            max-width: 850px;
            margin: 10px auto;
            padding: 12px 20px;
            border-radius: 8px;
            background-color: #ffe0e0;
            color: #662c2c;
            font-weight: 600;
            text-align: center;
            font-size: 18px;
            border: 1px solid #662c2c;
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
    </style>
    <script>
        function calculateScienceMathTotal() {
            const science = parseFloat(document.getElementById('science_marks').value) || 0;
            const maths = parseFloat(document.getElementById('maths_marks').value) || 0;
            const total = science + maths;
            document.getElementById('science_math_total').value = total;

            const marksObtained = parseFloat(document.getElementById('marks_obtained').value) || 0;
            const totalMarks = parseFloat(document.getElementById('total_marks').value) || 625;
            const percentage = totalMarks > 0 ? ((marksObtained / totalMarks) * 100).toFixed(2) : '0';
            document.getElementById('percentage').value = percentage;
        }

        function calculatePercentage() {
            const marksObtained = parseFloat(document.getElementById('marks_obtained').value) || 0;
            const totalMarks = parseFloat(document.getElementById('total_marks').value) || 625;
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
     <a href="edit_application_form.php">EDIT FORM</a>
    <a href="fetch_marks.php">Fetch Marks</a>
    <a href="view_marks.php">Document Verification</a>
    <a href="seat_allotment.php">Seat Allotment</a>
    <a href="seat_details.php">Seat Allotted</a>
    <a href="seat_balances.php">Seat Balance</a>
    <a href="document_details.php">Document Details</a>
    <a href="merit_list.php">Check Merit</a>
</nav>

<h2>Diploma Admission Form</h2>

<?php if (!empty($message)): ?>
    <div class="<?php echo (strpos($message, 'Error') === false) ? 'message' : 'error-message'; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<?php
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
?>

<form method="post" enctype="multipart/form-data" autocomplete="off">
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

    <label>a. SSLC Register No.:</label>
    <input type="text" name="SSLC_Register" value="<?php echo val('SSLC_Register'); ?>" <?php echo ($existing_data !== null) ? 'readonly' : ''; ?>>

    <label>b. Year of passing:</label>
    <input type="text" name="Year_of_passing" value="<?php echo val('Year_of_passing'); ?>">

    <label>1. Total Marks in all subjects:</label>
    <input type="number" name="total_marks" id="total_marks" value="625" readonly>

    <label>Marks obtained:</label>
    <input type="number" name="marks_obtained" id="marks_obtained" value="<?php echo val('marks_obtained'); ?>" oninput="calculatePercentage()" >

    <label>2. Max. Science Marks:</label>
    <input type="number" name="max_science_marks" id="max_science_marks" value="100" readonly>

    <label>Science Marks:</label>
    <input type="number" name="science_marks" id="science_marks" value="<?php echo val('science_marks'); ?>" oninput="calculateScienceMathTotal()" >

    <label>3. Max. Maths Marks:</label>
    <input type="number" name="max_maths_marks" id="max_maths_marks" value="100" readonly>

    <label>Maths Marks:</label>
    <input type="number" name="maths_marks" id="maths_marks" value="<?php echo val('maths_marks'); ?>" oninput="calculateScienceMathTotal()" >

    <label>4. Max. Science & Maths Marks:</label>
    <input type="number" name="max_science_math_marks" id="max_science_math_marks" value="200" readonly>

    <label>Science & Maths Total:</label>
    <input type="number" name="science_math_total" id="science_math_total" value="<?php echo val('science_math_total'); ?>" readonly>

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

    <h3>Documents Upload Section</h3>

    <label>37. SSLC Marks Card:</label>
    <input type="file" name="sslc_marks_card">

    <label>38. Student Photo:</label>
    <input type="file" name="student_photo">

    <label>39. Transfer Certificate:</label>
    <input type="file" name="tc">

    <label>40. Caste Certificate:</label>
    <input type="file" name="caste_certificate">

    <label>41. Income Certificate:</label>
    <input type="file" name="income_certificate">

    <label>42. Study Certificate:</label>
    <input type="file" name="study_certificate">

    <label>43. Kannada Medium Eligibility Certificate:</label>
    <input type="file" name="kannada_medium_certificate">

    <label>44. Rural Quota Eligibility Certificate:</label>
    <input type="file" name="rural_quota_certificate">

    <label>45. Special Quota Eligibility Certificate:</label>
    <input type="file" name="special_quota_certificate">

    <label>46. Aadhar Card:</label>
    <input type="file" name="aadhar_card">

    <input type="submit" value="Submit Application">
</form>

</body>
</html>

