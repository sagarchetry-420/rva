Add-Type -AssemblyName System.Drawing
$img = [System.Drawing.Image]::FromFile("c:\wamp64\www\RVA\assets\logo\logo_png.png")
Write-Output "Width: $($img.Width) px"
Write-Output "Height: $($img.Height) px"
$img.Dispose()
