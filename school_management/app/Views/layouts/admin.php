<?php
/**
 * Admin Layout — Wraps admin pages with header + sidebar
 * Variables available: $__content (buffered view), $pageTitle
 */
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="/assets/logo/logo_png.png">
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    <?php if (isset($moduleCss)): ?>
        <?php foreach ((array)$moduleCss as $cssFile): ?>
            <link rel="stylesheet" href="<?php echo asset('css/modules/' . $cssFile); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" defer></script>
    <?php if (isset($extraCss)): ?>
    <style><?php echo $extraCss; ?></style>
    <?php endif; ?>
    <!-- Compression Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/compressorjs/1.2.1/compressor.min.js" defer></script>
    <script src="<?php echo asset('js/compression.js'); ?>" defer></script>
</head>
<body>
    <?php require APP_ROOT . '/app/Views/partials/header.php'; ?>
    <div class="main-container">
        <?php require APP_ROOT . '/app/Views/partials/sidebar.php'; ?>
        <div class="content">
            <?php echo $__content; ?>
        </div>
    </div>
    <script src="<?php echo asset('js/script.js'); ?>"></script>
    <?php if (isset($extraJs)): ?>
    <script><?php echo $extraJs; ?></script>
    <?php endif; ?>
    <!-- Rossie Chatbot -->
    <script src="/rossie/rossie.js"></script>
</body>
</html>
