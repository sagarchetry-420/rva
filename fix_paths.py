import os

folder = r'C:\wamp64\www\RVA\home'
for file in os.listdir(folder):
    if file.endswith('.html'):
        path = os.path.join(folder, file)
        with open(path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        content = content.replace('href="/home/"', 'href="index.html"')
        content = content.replace('href="/home/notices"', 'href="notices.html"')
        content = content.replace('href="/home/gallery"', 'href="gallery.html"')
        content = content.replace('href="/assets/', 'href="../assets/')
        content = content.replace('href="/school_management/', 'href="../school_management/')
        content = content.replace('src="/rossie/', 'src="../rossie/')
        content = content.replace('href="/rossie/', 'href="../rossie/')
        
        with open(path, 'w', encoding='utf-8') as f:
            f.write(content)

rossie_js = r'C:\wamp64\www\RVA\rossie\rossie.js'
with open(rossie_js, 'r', encoding='utf-8') as f:
    r_content = f.read()

r_content = r_content.replace('"/rossie/rossie.js"', 'basePath + "/rossie.js"')
r_content = r_content.replace('"/rossie/rossie.png"', 'basePath + "/rossie.png"')
r_content = r_content.replace('"/rossie/tts.php"', 'basePath + "/tts.php"')
r_content = r_content.replace("'/rossie/tts.php'", "basePath + '/tts.php'")
r_content = r_content.replace('src="/rossie/rossie.png"', 'src="../rossie/rossie.png"')

with open(rossie_js, 'w', encoding='utf-8') as f:
    f.write(r_content)
print('Done!')
