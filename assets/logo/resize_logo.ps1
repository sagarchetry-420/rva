Add-Type -AssemblyName System.Drawing
$img = [System.Drawing.Image]::FromFile("c:\wamp64\www\RVA\assets\logo\logo_png.png")
$newWidth = 250
$newHeight = [int]($img.Height * ($newWidth / $img.Width))
$newImg = New-Object System.Drawing.Bitmap($newWidth, $newHeight)
$g = [System.Drawing.Graphics]::FromImage($newImg)
$g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
$g.DrawImage($img, 0, 0, $newWidth, $newHeight)
$newImg.Save("c:\wamp64\www\RVA\assets\logo\logo_small.png", [System.Drawing.Imaging.ImageFormat]::Png)
$g.Dispose()
$newImg.Dispose()
$img.Dispose()
