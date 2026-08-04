<?php include 'includes/header.php'; ?>

<section class="hero-section">
  <div class="hero-content">
    <span class="hero-badge">
      <i class="fa-solid fa-hotel"></i> Premium Student Living
    </span>
    <h1 class="hero-title">Welcome to UniDorms</h1>
    <p class="hero-subtitle">
      Your ultimate student housing experience. Discover comfortable, secure, and modern living spaces tailored specifically for your academic journey.
    </p>

    <?php if (isset($_SESSION['user_id'])): ?>
      <div class="welcome-box">
        <p>Hello, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Student'); ?></strong>! You are logged in.</p>
        <a href="student/index.php" class="btn-primary">
          <i class="fa-solid fa-chart-line"></i> Go to Dashboard
        </a>
      </div>
    <?php else: ?>
      <div class="hero-actions">
        <a href="login.php" class="btn-primary">
          <i class="fa-solid fa-right-to-bracket"></i> Login
        </a>
        <a href="register.php" class="btn-secondary">
          <i class="fa-solid fa-user-plus"></i> Register
        </a>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="features-section">
  <div class="feature-card">
    <div class="feature-icon"><i class="fa-solid fa-shield-halved"></i></div>
    <h3>Safe & Secure</h3>
    <p>24/7 security and verified digital keys for complete peace of mind.</p>
  </div>
  <div class="feature-card">
    <div class="feature-icon"><i class="fa-solid fa-wifi"></i></div>
    <h3>Modern Amenities</h3>
    <p>High-speed internet, fully furnished rooms, and dedicated study areas.</p>
  </div>
  <div class="feature-card">
    <div class="feature-icon"><i class="fa-solid fa-key"></i></div>
    <h3>Easy Management</h3>
    <p>Track your bookings, payments, and maintenance requests effortlessly.</p>
  </div>
</section>

<?php include 'includes/footer.php'; ?>