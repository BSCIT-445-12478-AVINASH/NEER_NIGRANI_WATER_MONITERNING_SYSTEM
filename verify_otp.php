<?php
require_once 'includes/config.php';

// Must have reset_email in session
if(!isset($_SESSION['reset_email'])) {
    redirect('forgot_password.php');
}

$email = $_SESSION['reset_email'];
$error = '';
$dev_otp = $_SESSION['dev_otp'] ?? null;

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = sanitize($_POST['otp'] ?? '');
    
    // Get stored OTP record
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$email]);
    $record = $stmt->fetch();
    
    if(!$record) {
        $error = 'No OTP found. Please request a new one.';
    } elseif(strtotime($record['expires_at']) < time()) {
        $error = 'OTP has expired! Please request a new one.';
        // Clean up expired OTP
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$email]);
    } elseif($record['attempts'] >= 5) {
        $error = 'Too many failed attempts! Please request a new OTP.';
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$email]);
    } elseif(!password_verify($entered_otp, $record['otp'])) {
        // Increment attempts
        $stmt = $pdo->prepare("UPDATE password_resets SET attempts = attempts + 1 WHERE id = ?");
        $stmt->execute([$record['id']]);
        $remaining = 4 - $record['attempts'];
        $error = "Invalid OTP! " . max(0, $remaining) . " attempts remaining.";
    } else {
        // OTP verified successfully
        $_SESSION['otp_verified'] = true;
        unset($_SESSION['dev_otp']);
        redirect('reset_password.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Neer Nigrani</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="wave-container"><div class="wave"></div><div class="wave"></div></div>

<div class="auth-container">
    <div class="card auth-card fade-in">
        <div class="auth-header">
            <div class="auth-icon">📩</div>
            <h2>Verify OTP</h2>
            <p>Enter the 6-digit OTP sent to <strong><?= sanitize($email) ?></strong></p>
        </div>
        
        <?php if($dev_otp): ?>
            <div class="msg-success" style="background:rgba(6,214,160,0.15);border:1px solid var(--secondary);padding:12px;border-radius:10px;margin-bottom:15px;text-align:center;">
                <strong>⚠️ Dev Mode:</strong> Mail server not configured.<br>Your OTP is: <strong style="font-size:1.3rem;letter-spacing:3px;"><?= $dev_otp ?></strong>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="msg-error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label><i class="fas fa-key"></i> Enter OTP</label>
                <input type="text" name="otp" class="form-control" placeholder="Enter 6-digit OTP" maxlength="6" pattern="[0-9]{6}" required style="text-align:center;font-size:1.3rem;letter-spacing:5px;">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Verify OTP</button>
        </form>
        
        <p class="text-center mt-2" style="font-size:0.85rem;color:var(--text-muted);">
            OTP valid for 10 minutes • Max 5 attempts
        </p>
        <p class="text-center" style="font-size:0.85rem;margin-top:8px;">
            <a href="forgot_password.php" style="color:var(--primary-light);">← Resend OTP</a>
        </p>
    </div>
</div>

</body>
</html>
