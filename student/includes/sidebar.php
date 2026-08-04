<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_display_name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Islam';
$first_letter = strtoupper(substr($user_display_name, 0, 1));
?>
<aside>
  <div>
    <div class="brand">
      <div class="brand-mark">S</div>
      <div class="brand-name">Student Housing</div>
    </div>
    <nav>
      <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="index.php">
        <i class="fa-solid fa-door-open"></i> Available rooms
      </a>
      <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'mybooking.php' ? 'active' : ''; ?>" href="mybooking.php">
        <i class="fa-solid fa-clipboard-check"></i> My Booking
      </a>
      <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'mykey.php' ? 'active' : ''; ?>" href="mykey.php">
        <i class="fa-solid fa-key"></i> My key
      </a>
      <a class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'maintenance.php' ? 'active' : ''; ?>" href="maintenance.php">
        <i class="fa-solid fa-screwdriver-wrench"></i> Maintenance
      </a>
      <a class="nav-item" href="../logout.php">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
      </a>
    </nav>
  </div>

  <div class="sidebar-foot">
    <div>
      <strong>INFO</strong>
      <ul class="list">
        <li><a class="link" href="../about.php">About Us</a></li>
        <li><a class="link" href="../contact.php">Contact Us</a></li>
        <li><a class="link" href="../faq.php">FAQ</a></li>
        <li><a class="link" href="../terms.php">Terms</a></li>
        <li><a class="link" href="../privacy.php">Privacy</a></li>
      </ul>
    </div>
    
    <div class="user-profile-summary">
      <div class="avatar"><?php echo htmlspecialchars($first_letter); ?></div>
      <span class="user-name-text"><?php echo htmlspecialchars($user_display_name); ?></span>
    </div>
  </div>
</aside>