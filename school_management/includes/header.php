<?php
/**
 * Header Include - Top Navigation Bar
 * Included in all authenticated pages
 */
$currentUser = getUsername();
$currentRole = ucfirst(getUserType());
?>
<header class="main-header">
    <div class="header-left">
        <div class="logo">
            <a href="<?php echo BASE_URL; ?>/index.php">
                <h2><i class="fa-solid fa-university"></i> SMS</h2>
            </a>
        </div>
        <button class="menu-toggle" id="menuToggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
    </div>
    
    <div class="header-right">
        <div class="user-menu">
            <span class="user-name"><?php echo htmlspecialchars($currentUser); ?></span>
            <span class="user-badge badge-<?php echo strtolower(getUserType()); ?>"><?php echo $currentRole; ?></span>
            <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="logout-btn"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</header>

<?php
// Display flash messages if any
$flash = getFlashMessage();
if ($flash):
?>
<div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
    <?php echo htmlspecialchars($flash['message']); ?>
    <span class="alert-close" onclick="this.parentElement.remove()">×</span>
</div>
<?php endif; ?>
