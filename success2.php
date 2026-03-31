<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if submitted data exists in session
if (!isset($_SESSION['submitted_data'])) {
    die("❌ Error: No data submitted. Please go back and submit the form.");
}

// Retrieve submitted data
$submitted_data = $_SESSION['submitted_data'];

// Clear the submitted data from session
unset($_SESSION['submitted_data']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Submission Success</title>
<style>
    @page {
    size: A4;
    margin: 1cm;
}

@media print {
    html, body {
        width: 100%;
        height: 100%;
        margin: 0;
        padding: 0;
        overflow: visible;
    }}
    body {
        font-family: 'Roboto', sans-serif;
        background-color: #f0f2f5;
        padding: 20px;
        margin: 0;
    }
    nav {
        background-color: #3a9a2d;
        padding: 15px;
        text-align: center;
        box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    }
    nav a {
        color: #fff;
        margin: 0 20px;
        text-decoration: none;
        font-weight: bold;
        transition: color 0.3s;
    }
    nav a:hover {
        text-decoration: underline;
        color: #d1e7dd;
    }
    .container {
        max-width: 600px;
        margin: 30px auto 0 auto;
        background: #fff;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border-left: 5px solid #4CAF50;
    }
    h2 {
        text-align: center;
        color: #333;
        margin-bottom: 20px;
    }
    p {
         text-align: center;
        font-size: 18px;
        color: #555;
    }
    h3 {
        color:BLACK;
         text-align: center;
        margin-top: 20px;
        margin-bottom: 10px;
    }
    ul {
        list-style: none;
        padding: 0;
        display: flex;
        flex-wrap: wrap;
    }
    ul li {
        background: #e9ecef;
        padding: 15px;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        font-size: 16px;
        flex: 0 0 48%;
        margin: 1%;
        box-sizing: border-box;
    }
    .back-button {
        margin-top: 30px;
        text-align: center;
    }
    
    .back-button a {
        display: inline-block;
        text-decoration: none;
        color: white;
        background-color: #3a9a2d;
        padding: 12px 25px;
        border-radius: 5px;
        font-weight: bold;
        transition: background-color 0.3s, transform 0.3s;
    }
    .back-button a:hover {
        background-color: #2e7d27;
        transform: translateY(-2px);
    }
    .print-button {
        margin-top: 20px;
        text-align: center;
    }
    .print-button button {
        color: white;
        background-color: #007bff;
        padding: 12px 25px;
        border-radius: 5px;
        font-weight: bold;
        border: none;
        cursor: pointer;
        transition: background-color 0.3s, transform 0.3s;
    }
    .print-button button:hover {
        background-color: #0056b3;
        transform: translateY(-2px);
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
        ul li {
            flex: 0 0 100%;
            margin: 5px 0;
        }
    }
    @media print {
        nav, .back-button, .print-button {
            display: none;
        }
        body {
            background-color: white;
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
    <a href="success2.php">Document Success</a>
</nav>

<div class="container">
    <h2>DACG GOVT POLYTECHNIC COLLEGE</h2>
    <p> CHIKKAMAGLURU-577101</p>

    <h3>Document Submission Status:</h3>
    <ul>
        <?php foreach ($submitted_data as $key => $value): ?>
            <li><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . ": " . htmlspecialchars($value); ?></li>
        <?php endforeach; ?>
    </ul>

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
</div>

<div class="back-button">
    <a href="view_marks.php">Go to View Marks</a>
</div>
<div class="print-button">
    <button onclick="window.print()">Print this page</button>
</div>
</body>
</html>