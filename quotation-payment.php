<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php'; 

// Connect using the helper function from config.php
$pdo = getDBConnection();
if (!$pdo) {
    die("Database Connection Failed. Please check your MySQL setup.");
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$client_id = $_SESSION['user_id'];
$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

// Fetch all service requests for this client
$requestsStmt = $pdo->prepare("
    SELECT sr.Request_ID, et.Name AS Event_Type, sr.Preferred_Date, rs.Status_Name AS Request_Status 
    FROM service_request sr
    JOIN event_type et ON sr.Event_Type_ID = et.Event_Type_ID
    JOIN request_status rs ON sr.Request_Status_ID = rs.Request_Status_ID
    WHERE sr.Client_ID = :client_id
    ORDER BY sr.Created_at DESC
");
$requestsStmt->execute([':client_id' => $client_id]);
$clientRequests = $requestsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch quotation and payment if a specific request is selected
$quotation = null;
$payment = null;
if ($request_id > 0) {
    $qStmt = $pdo->prepare("
        SELECT * FROM quotation 
        WHERE Request_ID = :request_id 
        ORDER BY Revision_No DESC LIMIT 1
    ");
    $qStmt->execute([':request_id' => $request_id]);
    $quotation = $qStmt->fetch(PDO::FETCH_ASSOC);

    $pStmt = $pdo->prepare("
        SELECT p.*, pm.Method_Name, ps.Status_Name AS Payment_Status 
        FROM payment p
        JOIN payment_method pm ON p.Payment_Method_ID = pm.Payment_Method_ID
        JOIN payment_status ps ON p.Payment_Status_ID = ps.Payment_Status_ID
        WHERE p.Request_ID = :request_id
    ");
    $pStmt->execute([':request_id' => $request_id]);
    $payment = $pStmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation & Payment</title>
    <link rel="stylesheet" href="quotation-payment.css">
</head>
<body>

    <header class="navbar">
        <div class="brand">
            <img src="logo.svg" alt="Logo" width="34" height="34" style="display:block;width:34px;height:34px;">
            <span>Payments</span>
        </div>
        <nav class="navigation">
            <a href="dashboard.php">DASHBOARD</a>
            <a href="services.php">SERVICES</a>
            <a href="new-request.php">NEW REQUEST</a>
            <a href="quotation-payment.php" class="active">PAYMENTS</a>
            <a href="logout.php">LOGOUT</a>
        </nav>
    </header>

    <main class="payments-container" style="padding: 20px; max-width: 1200px; margin: auto;">
        <h1>Quotations & Invoices</h1>
        <p>Review active quotes and process downpayments or final balances[cite: 2].</p>

        <!-- Step 1: Select Service Request -->
        <div class="card" style="margin: 20px 0; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 8px;">
            <h3>Select Service Request</h3>
            <form method="GET" action="quotation-payment.php">
                <select name="request_id" onchange="this.form.submit()" style="padding: 10px; width: 100%; max-width: 400px; border-radius: 4px; border: 1px solid #ccc;">
                    <option value="">-- Choose your request --</option>
                    <?php foreach ($clientRequests as $req): ?>
                        <option value="<?php echo $req['Request_ID']; ?>" <?php echo ($request_id == $req['Request_ID']) ? 'selected' : ''; ?>>
                            #<?php echo $req['Request_ID']; ?> - <?php echo htmlspecialchars($req['Event_Type']); ?> (<?php echo $req['Preferred_Date']; ?>) [<?php echo $req['Request_Status']; ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if ($request_id > 0): ?>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                
                <!-- Quotation Details Card -->
                <div class="card" style="padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 8px;">
                    <h3>Quotation Details</h3>
                    <?php if ($quotation): ?>
                        <p><strong>Revision Number:</strong> Rev <?php echo htmlspecialchars($quotation['Revision_No']); ?>[cite: 2]</p>
                        <p><strong>Total Amount Due:</strong> ₱<?php echo number_format($quotation['Amount'], 2); ?></p>
                        <p><strong>Details / Inclusions:</strong> <?php echo htmlspecialchars($quotation['Details']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($quotation['Status']); ?></p>
                        <p><strong>Valid Until:</strong> <?php echo htmlspecialchars($quotation['Quotation_Expiry_Date']); ?></p>

                        <?php if ($quotation['Status'] == 'Pending' || $quotation['Status'] == 'Pending Acceptance'): ?>
                            <form action="process-request.php" method="POST" style="margin-top: 15px;">
                                <input type="hidden" name="quotation_id" value="<?php echo $quotation['Quotation_ID']; ?>">
                                <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">
                                <button type="submit" name="action" value="accept" style="background: #28a745; color: #fff; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">Accept Quotation</button>
                                <button type="submit" name="action" value="decline" style="background: #dc3545; color: #fff; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">Decline Quotation</button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>No quotation has been issued for this request yet[cite: 5].</p>
                    <?php endif; ?>
                </div>

                <!-- Payment Upload Card -->
                <div class="card" style="padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 8px;">
                    <h3>Make Payment & Proof Upload</h3>
                    <?php if ($quotation && ($quotation['Status'] == 'Accepted' || $quotation['Status'] == 'Approved')): ?>
                        <form action="process-payment.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">
                            
                            <label style="display:block; margin-top:10px;">Payment Method:</label>
                            <select name="payment_method_id" required style="width:100%; padding:8px; border-radius: 4px; border: 1px solid #ccc;">
                                <option value="1">GCash</option>
                                <option value="2">Bank Transfer</option>
                                <option value="3">Maya</option>
                            </select>

                            <label style="display:block; margin-top:10px;">Reference Number:</label>
                            <input type="text" name="reference_number" placeholder="Enter transaction reference number" required style="width:100%; padding:8px; border-radius: 4px; border: 1px solid #ccc;">

                            <label style="display:block; margin-top:10px;">Upload Receipt (JPG, PNG, PDF up to 5MB):</label>
                            <input type="file" name="receipt_file" accept=".jpg, .jpeg, .png, .pdf" required style="width:100%; margin-top:5px;">

                            <button type="submit" style="margin-top: 15px; background: #007bff; color: #fff; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; width:100%;">Submit Payment Proof</button>
                        </form>
                    <?php else: ?>
                        <p style="color: #666;">You must accept the quotation before you can submit payment proof.</p>
                    <?php endif; ?>

                    <?php if ($payment): ?>
                        <div style="margin-top: 20px; padding: 10px; background: #eef; border: 1px solid #ccd; border-radius: 4px;">
                            <p><strong>Submitted Reference:</strong> <?php echo htmlspecialchars($payment['Reference_Number']); ?></p>
                            <p><strong>Method:</strong> <?php echo htmlspecialchars($payment['Method_Name']); ?></p>
                            <p><strong>Verification Status:</strong> <span style="color: orange; font-weight: bold;"><?php echo htmlspecialchars($payment['Payment_Status']); ?></span></p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        <?php endif; ?>
    </main>

</body>
</html>