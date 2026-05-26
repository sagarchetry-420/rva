Add-Type -AssemblyName System.Drawing
$img = [System.Drawing.Image]::FromFile("c:\wamp64\www\RVA\assets\school_image\img4.png")
$img.Save("c:\wamp64\www\RVA\assets\school_image\img4_compressed.jpg", [System.Drawing.Imaging.ImageFormat]::Jpeg)
$img.Dispose()
