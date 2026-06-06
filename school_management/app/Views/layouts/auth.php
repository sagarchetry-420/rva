<?php
/**
 * Auth Layout — Minimal layout for login/reset pages (no sidebar)
 */
$pageTitle = $pageTitle ?? 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="/assets/logo/logo_png.png">
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if (isset($moduleCss)): ?>
        <?php foreach ((array)$moduleCss as $cssFile): ?>
            <link rel="stylesheet" href="<?php echo asset('css/modules/' . $cssFile); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (isset($extraCss)): ?><style><?php echo $extraCss; ?></style><?php endif; ?>
</head>
<body class="login-page">
    <?php
    // Display flash messages
    $flash = getFlashMessage();
    if ($flash):
        $iconMap = ['success'=>'fa-circle-check','error'=>'fa-circle-xmark','warning'=>'fa-triangle-exclamation','info'=>'fa-circle-info'];
        $colorMap = ['success'=>'#22c55e','error'=>'#ef4444','warning'=>'#f59e0b','info'=>'#3b82f6'];
        $icon = $iconMap[$flash['type']] ?? 'fa-circle-info';
        $color = $colorMap[$flash['type']] ?? '#3b82f6';
    ?>
    <div id="toastNotification" style="position:fixed;top:20px;left:50%;transform:translateX(-50%);min-width:380px;max-width:500px;background:#fff;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.25);display:flex;align-items:stretch;overflow:hidden;z-index:999999;border-left:5px solid <?php echo $color; ?>;animation:toastFadeIn 0.45s ease forwards;">
        <div style="display:flex;align-items:center;justify-content:center;padding:0 18px;font-size:24px;color:<?php echo $color; ?>;min-width:56px;">
            <i class="fa-solid <?php echo $icon; ?>"></i>
        </div>
        <div style="flex:1;padding:14px 8px 14px 0;">
            <p style="font-weight:700;font-size:14px;margin:0 0 2px;text-transform:capitalize;color:<?php echo $color; ?>;"><?php echo ucfirst($flash['type']); ?></p>
            <p style="font-size:13px;color:#6b7280;margin:0;line-height:1.4;"><?php echo htmlspecialchars($flash['message']); ?></p>
        </div>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;color:#999;font-size:14px;cursor:pointer;padding:8px 14px;align-self:flex-start;">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <style>
    @keyframes toastFadeIn{from{opacity:0;transform:translate(-50%,-30px)}to{opacity:1;transform:translate(-50%,0)}}
    </style>
    <script>setTimeout(function(){var t=document.getElementById('toastNotification');if(t)t.remove();},10000);</script>
    <?php endif; ?>

    <?php echo $__content; ?>

    <script src="<?php echo asset('js/script.js'); ?>"></script>
    <?php if (isset($extraJs)): ?><script><?php echo $extraJs; ?></script><?php endif; ?>
</body>
</html>
