<?php
require_once 'includes/config.php';
if(isLoggedIn()) redirect('pages/user_dashboard.php');

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if(!$user) {
        $error = 'No account found with this email address!';
    } else {
        // Generate 6-digit OTP
        $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // Delete old OTPs for this email
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$email]);
        
        // Store hashed OTP
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, otp, expires_at, attempts) VALUES (?, ?, ?, 0)");
        $stmt->execute([$email, password_hash($otp, PASSWORD_DEFAULT), $expires_at]);
        
        // Send OTP via email
        $to = $email;
        $subject = "Neer Nigrani - Password Reset OTP";
        $message = "Hello " . $user['name'] . ",\n\nYour OTP for password reset is: " . $otp . "\n\nThis OTP is valid for 10 minutes.\n\nIf you didn't request this, please ignore this email.\n\nRegards,\nNeer Nigrani Team";
        $headers = "From: noreply@neernigrani.com\r\nContent-Type: text/plain; charset=UTF-8";
        
        $mail_sent = @mail($to, $subject, $message, $headers);
        
        // Store email in session for next step
        $_SESSION['reset_email'] = $email;
        
        if($mail_sent) {
            redirect('verify_otp.php');
        } else {
            // Dev mode: show OTP if mail server not configured
            $_SESSION['dev_otp'] = $otp;
            redirect('verify_otp.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Neer Nigrani</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="wave-container"><div class="wave"></div><div class="wave"></div></div>

<div class="auth-container">
    <div class="card auth-card fade-in">
        <div class="auth-header">
            <div class="auth-icon">🔐</div>
            <h2>Forgot Password?</h2>
            <p>Enter your registered email to receive an OTP</p>
        </div>
        
        <?php if($error): ?>
            <div class="msg-error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Registered Email</label>
                <input type="email" name="email" class="form-control" placeholder="your@email.com" required value="<?= sanitize($email ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Send OTP</button>
        </form>
        
        <p class="text-center mt-2" style="font-size:0.9rem;color:var(--text-muted);">
            Remember your password? <a href="login.php" style="color:var(--primary-light);">Sign In</a>
        </p>
    </div>
</div>

</body>
</html>
