<?php
require_once 'includes/config.php';

// Must have verified OTP
if(!isset($_SESSION['otp_verified']) || !isset($_SESSION['reset_email'])) {
    redirect('forgot_password.php');
}

$email = $_SESSION['reset_email'];
$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if(strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters!';
    } elseif($new_password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } else {
        // Update password
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashed, $email]);
        
        // Clean up
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$email]);
        
        unset($_SESSION['reset_email'], $_SESSION['otp_verified'], $_SESSION['dev_otp']);
        
        $success = 'Password reset successfully! Redirecting to login...';
        header("Refresh: 2; url=login.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Neer Nigrani</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="wave-container"><div class="wave"></div><div class="wave"></div></div>

<div class="auth-container">
    <div class="card auth-card fade-in">
        <div class="auth-header">
            <div class="auth-icon">🔒</div>
            <h2>Set New Password</h2>
            <p>Create a new password for <strong><?= sanitize($email) ?></strong></p>
        </div>
        
        <?php if($success): ?>
            <div class="msg-success" style="background:rgba(6,214,160,0.15);border:1px solid var(--secondary);padding:12px;border-radius:10px;margin-bottom:15px;text-align:center;">
                ✅ <?= $success ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="msg-error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if(!$success): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label><i class="fas fa-lock"></i> New Password</label>
                <input type="password" name="new_password" class="form-control" placeholder="Min 6 characters" minlength="6" required>
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" minlength="6" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Reset Password</button>
        </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
