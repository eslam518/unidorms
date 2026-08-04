<?php include 'includes/header.php'; ?>

<?php
$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    if ($name == "" || $email == "" || $message == "") {
        $error = "Please fill in all fields.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $name, $email, $message);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Thank you! Your message has been sent successfully.";
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>

<main class="page-container">
    <div class="page-header">
        <span class="page-badge"><i class="fa-solid fa-headset"></i> Get in Touch</span>
        <h1 class="page-title">Contact Us</h1>
        <p class="page-subtitle">Have questions or need assistance? We are here to help you anytime.</p>
    </div>

    <div class="container contact-wrapper">
        <div class="contact-info">
            <h2>Reach Out to Us</h2>
            <p>If you have any queries regarding room bookings, payment procedures, or general inquiries, feel free to send us a message.</p>
            
            <div class="contact-item">
                <div class="contact-icon"><i class="fa-solid fa-envelope"></i></div>
                <div>
                    <strong>Email Support</strong>
                    <p>support@unidorms.com</p>
                </div>
            </div>

            <div class="contact-item">
                <div class="contact-icon"><i class="fa-solid fa-phone"></i></div>
                <div>
                    <strong>Phone Inquiry</strong>
                    <p>+20 100 000 0000</p>
                </div>
            </div>

            <div class="contact-item">
                <div class="contact-icon"><i class="fa-solid fa-clock"></i></div>
                <div>
                    <strong>Working Hours</strong>
                    <p>Sun - Thu: 9:00 AM - 5:00 PM</p>
                </div>
            </div>
        </div>

        <div class="contact-form-container">
            <?php if ($success): ?>
                <div class="message success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="contact.php" class="styled-form">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-user input-icon"></i>
                        <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" placeholder="name@example.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="message">Your Message</label>
                    <textarea id="message" name="message" rows="5" placeholder="How can we help you?" required></textarea>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>