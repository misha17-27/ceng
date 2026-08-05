# ceng.az — PHP versiyası

Bu, `https://ceng.az/` (WordPress + Elementor) saytının statik/PHP-ə köçürülmüş dəqiq kopyasıdır.
WordPress, verilənlər bazası və admin panel tələb olunmur — sadəcə PHP dəstəyi olan istənilən hostinqdə (məsələn cPanel) işləyir.

## Struktur

```
/
├── index.php                 Ana səhifə
├── haqqimizda/index.php      Haqqımızda
├── xidmetlerimiz/index.php   Xidmətlərimiz
├── layiheler/index.php       Layihələr (siyahı)
├── elaqe/index.php           Əlaqə (işləyən forma ilə)
├── <layihə-slug>/index.php   14 layihə səhifəsi (bine-stadium, bakcell-arena, ...)
├── includes/
│   ├── header.php            Bütün səhifələr üçün ümumi header/naviqasiya
│   └── footer.php            Bütün səhifələr üçün ümumi footer
├── contact-handler.php       Əlaqə formasının PHP göndərici skripti (mail)
├── .htaccess                 DirectoryIndex + keşləmə qaydaları
├── wp-content/               Bütün CSS, JS, şəkillər, fontlar (Elementor)
└── wp-includes/              jQuery və digər əsas skriptlər
```

Naviqasiya, header və footer hər səhifədə eyni olduğu üçün bir dəfə `includes/` qovluğunda saxlanılır və
`<?php include ... ?>` ilə çağırılır. Bu, redaktəni asanlaşdırır: menyu və ya footer-i dəyişmək üçün yalnız
bir faylı düzəltmək kifayətdir.

## cPanel-də yerləşdirmə

1. **Git Version Control** (cPanel) və ya FTP ilə bu reponu domenin kök qovluğuna (`public_html`) yükləyin.
2. Fayllar birbaşa kök qovluqda olmalıdır (yəni `public_html/index.php`), alt qovluqda yox.
   Qovluq strukturu domenin kökündən başladığı üçün bütün asset yolları `/wp-content/...` kimi mütləq (root-relative) yazılıb.
3. Bütün. Sayt dərhal işləyir.

## Əlaqə forması

- Forma `contact-handler.php`-ə POST edir və `mail()` funksiyası ilə **info@ceng.az** ünvanına göndərir.
- Uğurlu göndərişdən sonra istifadəçi `/elaqe/?sent=1` səhifəsinə yönləndirilir və təsdiq mesajı göstərilir.
- Hostinqdə PHP `mail()` konfiqurasiya olunmalıdır. SMTP istəsəniz, `contact-handler.php` daxilində
  `mail()` çağırışını PHPMailer/SMTP ilə əvəz edin.

## Qeydlər

- Sayt WordPress-in orijinal Elementor CSS/JS fayllarını olduğu kimi istifadə edir — görünüş 1:1 eynidir.
- Dinamik funksiyalar (axtarış, WP admin, oEmbed) yoxdur — bu statik məzmun kopyasıdır.
- Şəkilləri və ya mətnləri dəyişmək üçün müvafiq `*/index.php` faylını redaktə edin.
