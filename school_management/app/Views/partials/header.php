<?php
/**
 * Header Partial — Top Navigation Bar
 * Adapted for new modular routing system
 */
$currentUser = getUsername();
$currentRole = ucfirst(getUserType() ?? 'Guest');
?>
<header class="main-header">
    <div class="header-left">
        <button class="menu-toggle" id="menuToggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
        <div class="logo">
            <a href="<?php echo baseUrl('index.php'); ?>" class="logo-link" style="display:flex; align-items:center; gap:10px; text-decoration: none;">
                <img src="<?php echo dirname(BASE_URL); ?>/assets/logo/logo_small.png" alt="Logo" class="logo-img" style="height:50px; width:auto; object-fit:contain;">
                <div class="logo-text-container" style="display: flex; flex-direction: column; justify-content: center;">
                    <h2 class="logo-title" style="margin:0; color:#FFFFFF; font-size: 20px; font-weight: bold; line-height: 1.2;">Rose Valley Academy</h2>
                    <span class="logo-subtitle" style="margin:2px 0 0 0; font-size: 10px; color:#EEEEEE; font-weight: bold; letter-spacing: 0.5px;">AFFILIATED TO ASSEB | KEKURI BONGALI GAON, DIBRUGARH</span>
                </div>
            </a>
        </div>
    </div>
        
    <div class="header-right">
        <div class="user-menu">
            <?php if (strtolower(getUserType() ?? '') === 'admin'): ?>
                <a href="<?php echo baseUrl('index.php?module=admin&action=clear-cache'); ?>" class="logout-btn" style="background:#f59e0b; margin-right:5px;"><i class="fa-solid fa-bolt"></i> <span class="logout-text">Clear Cache</span></a>
            <?php endif; ?>
            <span class="user-badge badge-<?php echo strtolower(getUserType() ?? 'guest'); ?>"><?php echo $currentRole; ?></span>
            <a href="<?php echo moduleUrl('auth', 'logout'); ?>" class="logout-btn"><i class="fa-solid fa-sign-out-alt"></i> <span class="logout-text">Logout</span></a>
        </div>
    </div>
</header>

<?php
// Display flash messages (toast notifications)
$flash = getFlashMessage();
if ($flash):
    $iconMap = ['success'=>'fa-circle-check','error'=>'fa-circle-xmark','warning'=>'fa-triangle-exclamation','info'=>'fa-circle-info'];
    $colorMap = ['success'=>'#22c55e','error'=>'#ef4444','warning'=>'#f59e0b','info'=>'#3b82f6'];
    $icon = $iconMap[$flash['type']] ?? 'fa-circle-info';
    $color = $colorMap[$flash['type']] ?? '#3b82f6';
?>
<style>
@keyframes toastFadeIn { from { opacity:0; transform:translate(-50%,-30px); } to { opacity:1; transform:translate(-50%,0); } }
@keyframes toastFadeOut { from { opacity:1; transform:translate(-50%,0); } to { opacity:0; transform:translate(-50%,-30px); } }
@keyframes toastProgress { from { width:100%; } to { width:0%; } }
.toast-exit-anim { animation: toastFadeOut 0.4s ease forwards !important; }
</style>
<div id="toastNotification" style="
    position:fixed; top:80px; left:50%; transform:translateX(-50%);
    min-width:380px; max-width:500px; background:#fff; border-radius:12px;
    box-shadow:0 10px 40px rgba(0,0,0,0.25),0 4px 12px rgba(0,0,0,0.15);
    display:flex; align-items:stretch; overflow:hidden; z-index:999999;
    border-left:5px solid <?php echo $color; ?>;
    animation:toastFadeIn 0.45s cubic-bezier(0.21,1.02,0.73,1) forwards;
">
    <div style="display:flex;align-items:center;justify-content:center;padding:0 18px;font-size:24px;color:<?php echo $color; ?>;min-width:56px;">
        <i class="fa-solid <?php echo $icon; ?>"></i>
    </div>
    <div style="flex:1;padding:14px 8px 14px 0;">
        <p style="font-weight:700;font-size:14px;margin:0 0 2px;text-transform:capitalize;color:<?php echo $color; ?>;"><?php echo ucfirst($flash['type']); ?></p>
        <p style="font-size:13px;color:#6b7280;margin:0;line-height:1.4;"><?php echo strip_tags($flash['message'], '<strong><b><i><br><ul><li>'); ?></p>
    </div>
    <button onclick="dismissToast()" style="background:none;border:none;color:#999;font-size:14px;cursor:pointer;padding:8px 14px;align-self:flex-start;">
        <i class="fa-solid fa-xmark"></i>
    </button>
    <?php if ($flash['type'] !== 'error'): ?>
    <div style="position:absolute;bottom:0;left:0;right:0;height:3px;background:rgba(0,0,0,0.06);">
        <div style="height:100%;width:100%;background:<?php echo $color; ?>;animation:toastProgress 4s linear forwards;"></div>
    </div>
    <?php endif; ?>
</div>
<script>
function dismissToast(){var t=document.getElementById('toastNotification');if(t){t.classList.add('toast-exit-anim');setTimeout(function(){t.remove();},400);}}
<?php if ($flash['type'] !== 'error'): ?>
setTimeout(function(){dismissToast();}, 4000);
<?php endif; ?>
</script>
<?php endif; ?>
