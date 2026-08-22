<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
boot_session();

$section = preg_replace('/[^a-z_]/', '', $_GET['section'] ?? 'overview');

/* ---- logout ---- */
if ($section === 'logout') { logout(); redirect('index.php?section=login'); }

/* ---- login ---- */
if ($section === 'login') {
    if (current_admin()) redirect('index.php');
    $err = '';
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        csrf_check();
        require_once __DIR__ . '/turnstile.php';
        if (!turnstile_verify($_POST['cf-turnstile-response'] ?? '')) $err = 'Подтвердите, что вы не робот.';
        elseif (attempt_login($_POST['email'] ?? '', $_POST['password'] ?? '')) redirect('index.php');
        else $err = 'Неверный e-mail или пароль.';
    }
    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Вход — CENG admin</title><style>'.admin_css().'</style></head><body>';
    echo '<div class="login"><form class="box" method="post">';
    echo '<div style="margin-bottom:14px"><img src="/admin/logo.png" alt="CENG" style="max-height:46px;max-width:220px"></div>';
    echo '<h2>Вход в панель</h2><div class="sub">Управление контентом сайта</div>';
    if ($err) echo '<div style="background:#b91c1c;color:#fff;padding:10px;border-radius:8px;margin-bottom:12px">'.e($err).'</div>';
    echo csrf_field();
    echo '<label>E-mail</label><input type="email" name="email" required autofocus>';
    echo '<label>Пароль</label><input type="password" name="password" required>';
    require_once __DIR__ . '/turnstile.php';
    echo '<div style="margin-top:14px;display:flex;justify-content:center">'; turnstile_widget(); echo '</div>';
    echo '<button class="btn" style="width:100%;margin-top:20px" type="submit">Войти</button>';
    echo '</form></div></body></html>';
    exit;
}

/* ---- everything below requires auth ---- */
require_login();
if (!isset(SECTIONS[$section])) $section = 'overview';
if (in_array($section, ADMIN_ONLY, true) && !is_admin()) { flash('Недостаточно прав (нужна роль Администратор).','err'); redirect('index.php?section=overview'); }
csrf_check();
$db = pdo();

/* ================= POST actions ================= */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $act = $_POST['action'] ?? '';

    if ($section === 'texts' && $act === 'save') {
        foreach (($_POST['t'] ?? []) as $k=>$v) kv_set('texts', $k, (string)$v);
        flash('Тексты сохранены.'); redirect('index.php?section=texts');
    }
    if ($section === 'contacts' && $act === 'save') {
        foreach (['phone','phone2','email','address','hours','map',
                  'soc1_name','soc1_url','soc2_name','soc2_url','soc3_name','soc3_url','soc4_name','soc4_url'] as $k)
            kv_set('contacts',$k,(string)($_POST[$k]??''));
        flash('Контакты сохранены.'); redirect('index.php?section=contacts');
    }
    if ($section === 'services' && $act === 'save') {
        foreach (($_POST['svc'] ?? []) as $i => $row) {
            $id = (int)($row['id'] ?? 0);
            $title = trim($row['title'] ?? '');
            if ($id && $title === '') { $db->prepare('DELETE FROM services WHERE id=?')->execute([$id]); continue; }
            if ($title === '' && trim($row['slug'] ?? '') === '') continue;
            $vals = [$row['slug']??'', $row['code']??'', $title, $row['short']??'', $row['descr']??'', $row['points']??'', $i];
            if ($id) { $db->prepare('UPDATE services SET slug=?,code=?,title=?,short=?,descr=?,points=?,sort=? WHERE id=?')->execute([...$vals,$id]); }
            else { $db->prepare('INSERT INTO services (slug,code,title,short,descr,points,sort) VALUES (?,?,?,?,?,?,?)')->execute($vals); }
        }
        flash('Услуги сохранены.'); redirect('index.php?section=services');
    }
    if ($section === 'projects') {
        if ($act === 'save') {
            $cover = trim($_POST['cover'] ?? '');
            if ($p = admin_upload('cover_file')) $cover = $p;                 // uploaded cover overrides
            $video = trim($_POST['video'] ?? '');
            if ($p = admin_upload('video_file')) $video = $p;                 // uploaded video overrides
            $gallery = array_values(array_filter(array_map('trim', $_POST['gallery'] ?? []), fn($x)=>$x!==''));
            foreach (admin_upload_multi('gallery_files') as $p) $gallery[] = $p;
            $f = [
              'title'=>$_POST['title']??'', 'slug'=>$_POST['slug']??'', 'category_id'=>($_POST['category_id']?:null),
              'year'=>$_POST['year']??'', 'location'=>$_POST['location']??'', 'area'=>$_POST['area']??'',
              'client'=>$_POST['client']??'', 'cover'=>$cover, 'video'=>$video, 'gallery'=>json_encode($gallery, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
              'descr'=>$_POST['descr']??'', 'content'=>$_POST['content']??'', 'scope'=>$_POST['scope']??'',
              'seo_title'=>$_POST['seo_title']??'', 'seo_desc'=>$_POST['seo_desc']??'',
              'robots'=>$_POST['robots']??'index,follow', 'canonical'=>$_POST['canonical']??'',
              'sort'=>(int)($_POST['sort']??0), 'visible'=>isset($_POST['visible'])?1:0,
              'status'=>$_POST['status']??'published',
            ];
            $saveProj = function(array $f) use ($db) {
                if (!empty($_POST['id'])) {
                    $set = implode(',', array_map(fn($k)=>"`$k`=?", array_keys($f)));
                    $db->prepare("UPDATE projects SET $set WHERE id=?")->execute([...array_values($f), (int)$_POST['id']]);
                } else {
                    $cols = '`'.implode('`,`', array_keys($f)).'`';
                    $ph = implode(',', array_fill(0, count($f), '?'));
                    $db->prepare("INSERT INTO projects ($cols) VALUES ($ph)")->execute(array_values($f));
                }
            };
            try { $saveProj($f); }
            catch (Throwable $e) { unset($f['video']); $saveProj($f); } // video column may not exist yet (run migrate.php)
            flash('Проект сохранён.'); redirect('index.php?section=projects');
        } elseif ($act === 'del') { $db->prepare('DELETE FROM projects WHERE id=?')->execute([(int)$_POST['id']]); flash('Удалено.'); redirect('index.php?section=projects'); }
    }
    if ($section === 'partners') {
        if ($act === 'save') {
            if ($_POST['id']) { $db->prepare('UPDATE partners SET name=?,image=?,url=?,sort=? WHERE id=?')
                ->execute([$_POST['name'],$_POST['image'],$_POST['url'],(int)$_POST['sort'],(int)$_POST['id']]); }
            else { $db->prepare('INSERT INTO partners (name,image,url,sort) VALUES (?,?,?,?)')
                ->execute([$_POST['name'],$_POST['image'],$_POST['url'],(int)$_POST['sort']]); }
            flash('Партнёр сохранён.');
        } elseif ($act === 'del') { $db->prepare('DELETE FROM partners WHERE id=?')->execute([(int)$_POST['id']]); flash('Удалено.'); }
        redirect('index.php?section=partners');
    }
    if ($section === 'pages' && $act === 'save') {
        $slug = $_POST['slug'] ?? '';
        $db->prepare('UPDATE pages SET title=?, hero_title=?, blue_short=?, blue_text=? WHERE slug=?')
           ->execute([$_POST['title']??'', $_POST['hero_title']??'', $_POST['blue_short']??'', $_POST['blue_text']??'', $slug]);
        $db->prepare('INSERT INTO seo_pages (slug,title,descr,robots,canonical) VALUES (?,?,?,?,?)
                      ON DUPLICATE KEY UPDATE title=VALUES(title),descr=VALUES(descr),robots=VALUES(robots),canonical=VALUES(canonical)')
           ->execute([$slug, $_POST['seo_title']??'', $_POST['seo_desc']??'', $_POST['robots']??'index,follow', $_POST['canonical']??'']);
        flash('Страница сохранена.'); redirect('index.php?section=pages');
    }
    if ($section === 'seo' && $act === 'save') {
        $u = $db->prepare('UPDATE seo_pages SET title=?, descr=? WHERE slug=?');
        foreach (($_POST['title'] ?? []) as $slug=>$title) $u->execute([$title, $_POST['descr'][$slug] ?? '', $slug]);
        flash('SEO сохранён.'); redirect('index.php?section=seo');
    }
    if ($section === 'submissions') {
        if ($act === 'read') { $db->prepare('UPDATE submissions SET is_read=1 WHERE id=?')->execute([(int)$_POST['id']]); }
        elseif ($act === 'del') { $db->prepare('DELETE FROM submissions WHERE id=?')->execute([(int)$_POST['id']]); flash('Заявка удалена.'); }
        redirect('index.php?section=submissions');
    }
    if ($section === 'profile') {
        $a = current_admin();
        if ($act === 'save') {
            $db->prepare('UPDATE admins SET name=?, email=? WHERE id=?')->execute([$_POST['name']??'', trim($_POST['email']??''), $a['id']]);
            $_SESSION['admin']['name'] = $_POST['name'] ?? ''; $_SESSION['admin']['email'] = trim($_POST['email'] ?? '');
            flash('Профиль сохранён.');
        } elseif ($act === 'passwd') {
            $st = $db->prepare('SELECT pass_hash FROM admins WHERE id=?'); $st->execute([$a['id']]); $row=$st->fetch();
            if (!password_verify($_POST['old'] ?? '', $row['pass_hash'] ?? '')) flash('Текущий пароль неверный.', 'err');
            elseif (strlen($_POST['new'] ?? '') < 6) flash('Новый пароль слишком короткий (мин. 6).', 'err');
            elseif (($_POST['new'] ?? '') !== ($_POST['new2'] ?? '')) flash('Пароли не совпадают.', 'err');
            else { $db->prepare('UPDATE admins SET pass_hash=? WHERE id=?')->execute([password_hash($_POST['new'], PASSWORD_DEFAULT), $a['id']]); flash('Пароль изменён.'); }
        }
        redirect('index.php?section=profile');
    }
    if ($section === 'security' && $act === 'save') {
        kv_set('settings','turnstile_site', trim($_POST['turnstile_site']??''));
        if (($_POST['turnstile_secret']??'') !== '') kv_set('settings','turnstile_secret', trim($_POST['turnstile_secret']));
        flash('Настройки безопасности сохранены.'); redirect('index.php?section=security');
    }
    if ($section === 'smtp') {
        if ($act === 'save') {
            foreach (['smtp_host','smtp_port','smtp_user','smtp_from','smtp_from_name','smtp_secure'] as $k) kv_set('settings',$k,(string)($_POST[$k]??''));
            if (($_POST['smtp_pass']??'') !== '') kv_set('settings','smtp_pass',(string)$_POST['smtp_pass']);
            flash('Настройки SMTP сохранены.'); redirect('index.php?section=smtp');
        } elseif ($act === 'test') {
            $err=''; $to = trim($_POST['test_to'] ?? '');
            if (smtp_send($to, 'Тест SMTP — ceng.az', "Это тестовое письмо из админ-панели.\nЕсли вы его получили — SMTP настроен верно.", $err))
                flash('Тестовое письмо отправлено на '.$to);
            else flash('Ошибка отправки: '.$err, 'err');
            redirect('index.php?section=smtp');
        }
    }
    if ($section === 'users') {
        if (!empty($_POST['delete_id'])) {
            $id = (int)$_POST['delete_id'];
            if ($id !== (int)current_admin()['id']) { $db->prepare('DELETE FROM admins WHERE id=?')->execute([$id]); flash('Пользователь удалён.'); }
            else flash('Нельзя удалить самого себя.', 'err');
            redirect('index.php?section=users');
        }
        if ($act === 'add') {
            $em = trim($_POST['email'] ?? '');
            if (!filter_var($em, FILTER_VALIDATE_EMAIL)) flash('Некорректный e-mail.', 'err');
            elseif (strlen($_POST['password'] ?? '') < 8) flash('Пароль минимум 8 символов.', 'err');
            else {
                $chk = $db->prepare('SELECT id FROM admins WHERE email=?'); $chk->execute([$em]);
                if ($chk->fetch()) flash('Пользователь с таким e-mail уже есть.', 'err');
                else { $db->prepare('INSERT INTO admins (name,email,pass_hash,role,active) VALUES (?,?,?,?,1)')
                    ->execute([$_POST['name']??'', $em, password_hash($_POST['password'], PASSWORD_DEFAULT), ($_POST['role']??'editor')==='admin'?'admin':'editor']);
                    flash('Пользователь добавлен.'); }
            }
            redirect('index.php?section=users');
        } elseif ($act === 'save') {
            $me = current_admin()['id'];
            foreach (($_POST['u'] ?? []) as $id => $row) {
                $id = (int)$id;
                $role = ($row['role'] ?? 'editor') === 'admin' ? 'admin' : 'editor';
                $active = isset($row['active']) ? 1 : 0;
                if ($id === (int)$me) { $active = 1; $role = 'admin'; } // don't lock yourself out
                $db->prepare('UPDATE admins SET name=?, role=?, active=? WHERE id=?')->execute([$row['name']??'', $role, $active, $id]);
                if (($row['password'] ?? '') !== '') {
                    if (strlen($row['password']) >= 8) $db->prepare('UPDATE admins SET pass_hash=? WHERE id=?')->execute([password_hash($row['password'], PASSWORD_DEFAULT), $id]);
                }
            }
            flash('Изменения сохранены.'); redirect('index.php?section=users');
        } elseif ($act === 'del') {
            $id = (int)$_POST['id'];
            if ($id !== (int)current_admin()['id']) { $db->prepare('DELETE FROM admins WHERE id=?')->execute([$id]); flash('Пользователь удалён.'); }
            else flash('Нельзя удалить самого себя.', 'err');
            redirect('index.php?section=users');
        }
    }
    if ($section === 'images' && $act === 'upload') {
        if ($p = admin_upload('file')) flash('Загружено: ' . $p);
        else flash('Не удалось загрузить (jpg/png/webp/gif/svg/mp4/webm/ogg/mov).', 'err');
        redirect('index.php?section=images');
    }
}

/* ================= render ================= */
$titles = ['overview'=>'Обзор','texts'=>'Тексты сайта','pages'=>'Страницы','services'=>'Услуги',
 'projects'=>'Проекты','partners'=>'Партнёры','images'=>'Изображения','contacts'=>'Контакты',
 'submissions'=>'Заявки','seo'=>'SEO','security'=>'Безопасность'];
layout_top($section, $titles[$section] ?? 'Панель');

$count = fn($t) => (int)$db->query("SELECT COUNT(*) c FROM `$t`")->fetch()['c'];

if ($section === 'overview') {
    $nsub = $count('submissions'); $nunread = (int)$db->query("SELECT COUNT(*) c FROM submissions WHERE is_read=0")->fetch()['c'];
    echo '<div class="cards">';
    foreach ([['texts','текстовых блоков'],['services','услуг'],['projects','проектов'],['submissions','заявок']] as [$t,$l])
        echo '<div class="card"><div class="n">'.$count($t).'</div><div class="l">'.$l.'</div></div>';
    echo '</div>';
    echo '<div class="panel"><h3>Быстрые действия</h3><p class="muted">Чаще всего меняют тексты, проекты, контакты и заявки.</p>';
    foreach ([['texts','Редактировать тексты'],['projects','Проекты'],['contacts','Контакты'],['submissions','Заявки с сайта']] as [$s,$l])
        echo '<a class="btn" style="margin:6px 8px 0 0" href="index.php?section='.$s.'">'.$l.'</a>';
    if ($nunread) echo '<p style="margin-top:16px">🔔 Новых заявок: <b>'.$nunread.'</b></p>';
    echo '</div>';
}

elseif ($section === 'texts') {
    $t = fn($k)=>e(kv_get('texts',$k));
    echo '<form method="post" class="panel"><h3>Основное</h3>'.csrf_field().'<input type="hidden" name="action" value="save">';
    echo '<label>Короткое имя</label><input type="text" name="t[site_short]" value="'.$t('site_short').'">';
    echo '<label>Полное имя</label><input type="text" name="t[site_full]" value="'.$t('site_full').'">';
    echo '<label>Слоган</label><input type="text" name="t[site_slogan]" value="'.$t('site_slogan').'">';
    echo '<div style="margin-top:16px"><button class="btn">Сохранить основное</button></div></form>';

    echo '<form method="post" class="panel"><h3>Редактируемые тексты</h3>'.csrf_field().'<input type="hidden" name="action" value="save">';
    echo '<label>Заголовок первого экрана</label><textarea name="t[hero_title]">'.$t('hero_title').'</textarea>';
    echo '<label>Синий блок: короткий заголовок</label><textarea name="t[blue_short]">'.$t('blue_short').'</textarea>';
    echo '<label>Синий блок: текст</label><textarea name="t[blue_text]" style="min-height:120px">'.$t('blue_text').'</textarea>';
    echo '<div style="margin-top:16px"><button class="btn">Сохранить тексты</button></div></form>';

    $shown = ['site_short','site_full','site_slogan','hero_title','blue_short','blue_text','about_title','footer_slogan'];
    $rest = array_filter($db->query('SELECT k,v FROM texts ORDER BY k')->fetchAll(), fn($r)=>!in_array($r['k'],$shown));
    if ($rest) {
        echo '<form method="post" class="panel"><h3>Тексты страниц</h3><p class="muted">Появляются автоматически, когда открывается страница сайта. Ключ = где текст находится.</p>'.csrf_field().'<input type="hidden" name="action" value="save">';
        foreach ($rest as $r) echo '<label>'.e(text_label($r['k'])).'</label><textarea name="t['.e($r['k']).']">'.e($r['v']).'</textarea>';
        echo '<div style="margin-top:16px"><button class="btn">Сохранить</button></div></form>';
    }
}

elseif ($section === 'contacts') {
    $g = fn($k)=>e(kv_get('contacts',$k));
    echo '<form method="post" class="panel">'.csrf_field().'<input type="hidden" name="action" value="save">';
    echo '<label>Телефон</label><input type="text" name="phone" value="'.$g('phone').'">';
    echo '<label>Телефон для ссылки tel:</label><input type="text" name="phone2" value="'.$g('phone2').'">';
    echo '<label>E-mail</label><input type="text" name="email" value="'.$g('email').'">';
    echo '<label>Адрес</label><input type="text" name="address" value="'.$g('address').'">';
    echo '<label>Часы работы</label><input type="text" name="hours" value="'.$g('hours').'">';
    echo '<label>Google Maps embed URL</label><input type="text" name="map" value="'.$g('map').'">';
    for ($i=1;$i<=4;$i++) {
        echo '<fieldset style="border:1px solid #e6ebea;border-radius:10px;padding:12px 16px;margin-top:14px"><legend style="color:#011640;font-weight:700">Соцсеть '.$i.'</legend>';
        echo '<div class="row"><div><label>Название</label><input type="text" name="soc'.$i.'_name" value="'.$g('soc'.$i.'_name').'"></div>';
        echo '<div><label>URL</label><input type="text" name="soc'.$i.'_url" value="'.$g('soc'.$i.'_url').'"></div></div></fieldset>';
    }
    echo '<div style="margin-top:18px"><button class="btn">Сохранить контакты</button></div></form>';
}

elseif ($section === 'services') {
    $rows = $db->query('SELECT * FROM services ORDER BY sort,id')->fetchAll();
    $slots = $rows;
    for ($i=0;$i<2;$i++) $slots[] = ['id'=>'','slug'=>'','code'=>'','title'=>'','short'=>'','descr'=>'','points'=>''];
    echo '<form method="post" class="panel">'.csrf_field().'<input type="hidden" name="action" value="save">';
    foreach ($slots as $i=>$s) {
        echo '<div style="border-bottom:1px solid #eef2f1;padding-bottom:16px;margin-bottom:16px"><b style="color:#011640">Услуга '.($i+1).'</b>';
        echo '<input type="hidden" name="svc['.$i.'][id]" value="'.e($s['id']??'').'">';
        echo '<label>Slug</label><input type="text" name="svc['.$i.'][slug]" value="'.e($s['slug']??'').'">';
        echo '<label>Код</label><input type="text" name="svc['.$i.'][code]" value="'.e($s['code']??'').'">';
        echo '<label>Название</label><input type="text" name="svc['.$i.'][title]" value="'.e($s['title']??'').'">';
        echo '<label>Кратко</label><textarea name="svc['.$i.'][short]">'.e($s['short']??'').'</textarea>';
        echo '<label>Описание</label><textarea name="svc['.$i.'][descr]">'.e($s['descr']??'').'</textarea>';
        echo '<label>Пункты, каждый с новой строки</label><textarea name="svc['.$i.'][points]">'.e($s['points']??'').'</textarea>';
        echo '</div>';
    }
    echo '<button class="btn" style="width:100%">Сохранить услуги</button></form>';
    echo '<p class="muted">Чтобы удалить услугу — очисти её «Название» и сохрани.</p>';
}

elseif ($section === 'projects') { render_projects($db); }

elseif ($section === 'partners') { crud_list($db,'partners','Партнёр',
    ['name'=>'Название','image'=>'Логотип (URL)','url'=>'Ссылка','sort'=>'Порядок'], ['name','sort']); }

elseif ($section === 'pages') {
    $mkurl = fn($slug) => ($u = '/'.ltrim((string)$slug,'/')) === '/' ? '/' : rtrim($u,'/').'/';
    if (isset($_GET['edit'])) {
        $slug = (string)$_GET['edit'];
        $st = $db->prepare('SELECT * FROM pages WHERE slug=?'); $st->execute([$slug]); $pg = $st->fetch();
        $st2 = $db->prepare('SELECT * FROM seo_pages WHERE slug=?'); $st2->execute([$slug]);
        $seo = $st2->fetch() ?: ['title'=>'','descr'=>'','robots'=>'index,follow','canonical'=>''];
        if (!$pg) { echo '<div class="panel">Страница не найдена. <a href="index.php?section=pages">Назад</a></div>'; }
        else {
            $url = $mkurl($pg['slug']);
            echo '<form method="post" class="panel">'.csrf_field().'<input type="hidden" name="action" value="save"><input type="hidden" name="slug" value="'.e($pg['slug']).'">';
            echo '<h3>Редактировать: '.e($pg['title']).'</h3><p class="muted">Permalink: https://yeni.ceng.az'.e($url).'</p>';
            echo '<label>Заголовок / пункт меню</label><input type="text" name="title" value="'.e($pg['title']).'">';
            echo '<fieldset style="border:1px solid #e6ebea;border-radius:10px;padding:14px 16px;margin-top:16px"><legend style="color:#011640;font-weight:700">Контент страницы</legend>';
            echo '<label>Заголовок первого экрана</label><textarea name="hero_title">'.e($pg['hero_title']??'').'</textarea>';
            echo '<label>Короткий заголовок синего блока</label><textarea name="blue_short">'.e($pg['blue_short']??'').'</textarea>';
            echo '<label>Текст синего блока</label><textarea name="blue_text" style="min-height:120px">'.e($pg['blue_text']??'').'</textarea></fieldset>';
            echo '<fieldset style="border:1px solid #e6ebea;border-radius:10px;padding:14px 16px;margin-top:16px"><legend style="color:#011640;font-weight:700">SEO этой страницы</legend>';
            echo '<label>SEO Title</label><input type="text" name="seo_title" value="'.e($seo['title']).'">';
            echo '<label>Meta Description</label><textarea name="seo_desc">'.e($seo['descr']).'</textarea>';
            echo '<div class="row"><div><label>Robots</label><input type="text" name="robots" value="'.e($seo['robots']??'index,follow').'"></div>';
            echo '<div><label>Canonical</label><input type="text" name="canonical" value="'.e($seo['canonical']??'').'"></div></div></fieldset>';
            echo '<div style="margin-top:18px"><button class="btn">Сохранить страницу</button> <a class="btn ghost" href="index.php?section=pages">Назад к списку</a> <a class="btn ghost" href="'.e($url).'" target="_blank">Открыть на сайте</a></div></form>';
        }
    } else {
        $rows = $db->query('SELECT p.*, s.title AS seo_t FROM pages p LEFT JOIN seo_pages s ON s.slug=p.slug ORDER BY p.sort')->fetchAll();
        echo '<div class="panel"><div class="muted" style="margin-bottom:12px">Все ('.count($rows).') &nbsp;|&nbsp; Опубликованные ('.count($rows).')</div>';
        echo '<table><tr><th>Заголовок</th><th>URL</th><th>SEO</th><th></th></tr>';
        foreach ($rows as $r) {
            $url = $mkurl($r['slug']);
            $dot = trim((string)($r['seo_t'] ?? '')) !== '' ? '#011640' : '#cbd3d1';
            echo '<tr><td><b>'.e($r['title']).'</b></td><td><span class="muted" style="background:#f0f3f2;padding:2px 8px;border-radius:6px">'.e($url).'</span></td>';
            echo '<td><span style="display:inline-block;width:11px;height:11px;border-radius:50%;background:'.$dot.'"></span></td>';
            echo '<td class="right" style="white-space:nowrap"><a class="btn sm ghost" href="index.php?section=pages&edit='.rawurlencode($r['slug']).'">Редактировать</a> <a class="btn sm ghost" href="'.e($url).'" target="_blank">Открыть</a></td></tr>';
        }
        echo '</table></div>';
    }
}

elseif ($section === 'seo') {
    $rows = $db->query('SELECT * FROM seo_pages ORDER BY slug')->fetchAll();
    echo '<form method="post" class="panel">'.csrf_field().'<input type="hidden" name="action" value="save">';
    foreach ($rows as $r) {
        echo '<div style="border-bottom:1px solid #eef2f1;padding-bottom:14px;margin-bottom:14px"><b>/'.e(ltrim($r['slug'],'/')).'</b>';
        echo '<label>Title</label><input type="text" name="title['.e($r['slug']).']" value="'.e($r['title']).'">';
        echo '<label>Meta description</label><textarea name="descr['.e($r['slug']).']">'.e($r['descr']).'</textarea></div>';
    }
    echo '<button class="btn">Сохранить</button></form>';
}

elseif ($section === 'submissions') {
    $rows = $db->query('SELECT * FROM submissions ORDER BY created_at DESC')->fetchAll();
    echo '<div class="panel"><table><tr><th>Дата</th><th>Имя</th><th>Телефон</th><th>Email</th><th>Сообщение</th><th></th></tr>';
    if (!$rows) echo '<tr><td colspan="6" class="muted">Пока нет заявок.</td></tr>';
    foreach ($rows as $r) {
        $b = $r['is_read'] ? '' : ' style="background:#eafaf4"';
        echo '<tr'.$b.'><td class="muted">'.e($r['created_at']).'</td><td>'.e($r['name']).'</td><td>'.e($r['phone']).'</td><td>'.e($r['email']).'</td><td>'.nl2br(e($r['message'])).'</td><td class="right" style="white-space:nowrap">';
        if (!$r['is_read']) echo '<form method="post" style="display:inline">'.csrf_field().'<input type="hidden" name="action" value="read"><input type="hidden" name="id" value="'.$r['id'].'"><button class="btn sm ghost">Прочитано</button></form> ';
        echo '<form method="post" style="display:inline" onsubmit="return confirm(\'Удалить заявку?\')">'.csrf_field().'<input type="hidden" name="action" value="del"><input type="hidden" name="id" value="'.$r['id'].'"><button class="btn sm red">✕</button></form></td></tr>';
    }
    echo '</table></div>';
}

elseif ($section === 'images') {
    $root  = dirname(__DIR__);
    $updir = $root . '/wp-content/uploads';
    echo '<form method="post" enctype="multipart/form-data" class="panel">'.csrf_field().'<input type="hidden" name="action" value="upload">';
    echo '<label>Загрузить изображение или видео</label><input type="file" name="file" accept="image/*,video/*" required>';
    echo '<div style="margin-top:14px"><button class="btn">Загрузить</button></div><p class="muted">jpg/png/webp/gif/svg/mp4/webm. Файлы кладутся в /wp-content/uploads/admin/. Клик по URL — выделить и скопировать.</p></form>';

    $vidext = ['mp4','webm','ogg','mov'];
    $files = [];
    if (is_dir($updir)) {
        try {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($updir, FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if (!$f->isFile()) continue;
                $ext = strtolower($f->getExtension());
                if (!in_array($ext, array_merge(['jpg','jpeg','png','webp','gif','svg'], $vidext))) continue;
                if (preg_match('/-\d+x\d+\.[a-z0-9]+$/i', $f->getFilename())) continue; // skip resized thumbnails
                $files[] = ['path'=>$f->getPathname(), 'mtime'=>$f->getMTime(), 'video'=>in_array($ext, $vidext)];
            }
        } catch (Throwable $e) {}
    }
    usort($files, fn($a,$b) => $b['mtime'] <=> $a['mtime']);
    $files = array_slice($files, 0, 200);

    echo '<div class="panel"><h3>Медиа сайта ('.count($files).')</h3>';
    echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:14px">';
    foreach ($files as $m) {
        $rel = str_replace('\\','/', substr($m['path'], strlen($root)));
        $url = '/' . implode('/', array_map('rawurlencode', explode('/', ltrim($rel,'/'))));
        echo '<div style="border:1px solid #eef2f1;border-radius:8px;padding:8px;text-align:center">';
        if ($m['video']) echo '<video src="'.e($url).'#t=0.1" preload="metadata" style="width:100%;height:92px;object-fit:cover;border-radius:6px;background:#000"></video>';
        else echo '<img src="'.e($url).'" loading="lazy" style="width:100%;height:92px;object-fit:contain;background:#f4f6f6;border-radius:6px">';
        echo '<input type="text" readonly value="'.e($url).'" onclick="this.select();document.execCommand(\'copy\')" title="Кликни, чтобы скопировать" style="width:100%;font-size:10px;margin-top:6px;border:1px solid #cfd8d6;border-radius:5px;padding:4px;cursor:pointer">';
        echo '</div>';
    }
    if (!$files) echo '<p class="muted">Медиа не найдено.</p>';
    echo '</div></div>';
}

elseif ($section === 'security') {
    $site = kv_get('settings','turnstile_site'); $hasSecret = kv_get('settings','turnstile_secret') !== '';
    $on = ($site !== '' && $hasSecret);
    echo '<form method="post" class="panel">'.csrf_field().'<input type="hidden" name="action" value="save">';
    echo '<h3>Cloudflare Turnstile (капча) <span style="font-size:12px;background:'.($on?'#011640':'#c9ced0').';color:#fff;padding:3px 10px;border-radius:20px;vertical-align:middle">'.($on?'включена':'выключена').'</span></h3>';
    echo '<p class="muted">Защищает форму на сайте и вход в панель от ботов.</p>';
    echo '<label>Site Key (публичный ключ)</label><input type="text" name="turnstile_site" value="'.e($site).'">';
    echo '<label>Secret Key (секретный ключ)</label><input type="password" name="turnstile_secret" placeholder="'.($hasSecret?'•••••••• (оставь пустым — не менять)':'').'">';
    echo '<div style="margin-top:16px"><button class="btn">Сохранить</button></div></form>';
    echo '<div class="panel"><h3>Где взять ключи</h3><ol class="muted"><li>Зайдите в <b>dash.cloudflare.com</b> → раздел <b>Turnstile</b>.</li><li>Нажмите <b>Add widget</b>.</li><li>Domain — укажите <b>ceng.az</b> и <b>yeni.ceng.az</b>.</li><li>Widget Mode — <b>Managed</b>.</li><li>Скопируйте Site Key и Secret Key в поля выше.</li></ol></div>';
    echo '<div class="panel"><h3>Что уже защищено</h3><ul class="muted"><li>Пароли хранятся в виде необратимого хэша.</li><li>Все формы защищены от CSRF.</li><li>Проверка типа файлов при загрузке.</li><li>Защита от подстановки заголовков в письмах.</li></ul></div>';
}

elseif ($section === 'profile') {
    $a = current_admin();
    echo '<form method="post" class="panel" style="max-width:520px">'.csrf_field().'<input type="hidden" name="action" value="save"><h3>Мой профиль</h3>';
    echo '<label>Имя</label><input type="text" name="name" value="'.e($a['name'] ?? '').'">';
    echo '<label>E-mail</label><input type="email" name="email" value="'.e($a['email'] ?? '').'">';
    echo '<div class="muted" style="margin-top:8px">Роль: <b>'.($a['role']==='admin'?'Администратор':'Редактор').'</b></div>';
    echo '<div style="margin-top:16px"><button class="btn">Сохранить</button></div></form>';
    echo '<form method="post" class="panel" style="max-width:520px">'.csrf_field().'<input type="hidden" name="action" value="passwd"><h3>Сменить пароль</h3>';
    echo '<label>Текущий пароль</label><input type="password" name="old" required>';
    echo '<label>Новый пароль</label><input type="password" name="new" required>';
    echo '<label>Повторите новый</label><input type="password" name="new2" required>';
    echo '<div style="margin-top:16px"><button class="btn">Изменить пароль</button></div></form>';
}

elseif ($section === 'smtp') {
    $g = fn($k)=>e(kv_get('settings',$k));
    $mode = kv_get('settings','smtp_host')!=='' ? 'SMTP' : 'стандартная функция mail()';
    echo '<form method="post" class="panel">'.csrf_field().'<input type="hidden" name="action" value="save">';
    echo '<h3>Настройки SMTP <span class="muted" style="font-size:13px;font-weight:400">— способ отправки сейчас: <b>'.e($mode).'</b></span></h3>';
    echo '<div class="row3"><div><label>SMTP-сервер</label><input type="text" name="smtp_host" value="'.$g('smtp_host').'" placeholder="mail.ceng.az"></div>';
    echo '<div><label>Порт</label><input type="text" name="smtp_port" value="'.$g('smtp_port').'"></div>';
    echo '<div><label>Шифрование</label><select name="smtp_secure">';
    foreach (['tls'=>'STARTTLS (587)','ssl'=>'SSL/TLS (465)','none'=>'Без шифрования'] as $v=>$l) echo '<option value="'.$v.'"'.(kv_get('settings','smtp_secure')===$v?' selected':'').'>'.$l.'</option>';
    echo '</select></div></div>';
    echo '<div class="row"><div><label>Пользователь (обычно полный адрес почты)</label><input type="text" name="smtp_user" value="'.$g('smtp_user').'"></div>';
    echo '<div><label>Пароль</label><input type="password" name="smtp_pass" placeholder="'.(kv_get('settings','smtp_pass')!==''?'•••••• (оставь пустым — не менять)':'').'"></div></div>';
    echo '<div class="row"><div><label>Адрес отправителя</label><input type="text" name="smtp_from" value="'.$g('smtp_from').'"></div>';
    echo '<div><label>Имя отправителя</label><input type="text" name="smtp_from_name" value="'.$g('smtp_from_name').'"></div></div>';
    echo '<div style="margin-top:16px"><button class="btn">Сохранить</button></div></form>';
    echo '<form method="post" class="panel">'.csrf_field().'<input type="hidden" name="action" value="test"><h3>Проверка отправки</h3><p class="muted">Отправим тестовое письмо, чтобы убедиться, что настройки верные.</p>';
    echo '<div style="display:flex;gap:12px;align-items:flex-end"><div style="flex:1"><label>Адрес получателя</label><input type="email" name="test_to" value="'.e(current_admin()['email'] ?? '').'" required></div><button class="btn ghost">Отправить тест</button></div></form>';
    echo '<div class="panel"><h3>Где взять данные</h3><p class="muted">В cPanel → <b>Email Accounts</b> → у нужного ящика нажмите <b>Connect Devices</b>. Там сервер, порт и способ шифрования. Пользователь — полный адрес почты, пароль — от этого ящика.</p></div>';
}

elseif ($section === 'users') {
    $me = current_admin()['id'];
    echo '<form method="post" class="panel">'.csrf_field().'<input type="hidden" name="action" value="add"><h3>Добавить пользователя</h3>';
    echo '<p class="muted">Роль «Администратор» даёт доступ к управлению пользователями. «Редактор» — только контент.</p>';
    echo '<div class="row"><div><label>Имя</label><input type="text" name="name"></div><div><label>E-mail</label><input type="email" name="email" required></div></div>';
    echo '<div class="row"><div><label>Пароль (мин. 8 символов)</label><input type="text" name="password" required></div>';
    echo '<div><label>Роль</label><select name="role"><option value="editor">Редактор</option><option value="admin">Администратор</option></select></div></div>';
    echo '<div style="margin-top:16px"><button class="btn">Добавить</button></div></form>';

    $rows = $db->query('SELECT * FROM admins ORDER BY id')->fetchAll();
    echo '<form method="post" class="panel"><h3>Все пользователи</h3><p class="muted">Поле пароля оставьте пустым, если менять его не нужно.</p>'.csrf_field().'<input type="hidden" name="action" value="save">';
    echo '<table><tr><th>Имя</th><th>E-mail</th><th>Роль</th><th>Новый пароль</th><th>Активен</th><th>Вход</th><th></th></tr>';
    foreach ($rows as $r) {
        $self = (int)$r['id'] === (int)$me;
        echo '<tr><td><input type="text" name="u['.$r['id'].'][name]" value="'.e($r['name'] ?? '').'" style="width:130px"></td>';
        echo '<td>'.e($r['email']).($self?' <span style="font-size:11px;background:#dbe6f5;color:#0a2a5c;padding:2px 8px;border-radius:20px">это вы</span>':'').'</td>';
        echo '<td><select name="u['.$r['id'].'][role]"'.($self?' disabled':'').'><option value="admin"'.(($r['role']??'admin')==='admin'?' selected':'').'>Администратор</option><option value="editor"'.(($r['role']??'')==='editor'?' selected':'').'>Редактор</option></select></td>';
        echo '<td><input type="text" name="u['.$r['id'].'][password]" placeholder="—" style="width:120px"></td>';
        echo '<td style="text-align:center"><input type="checkbox" name="u['.$r['id'].'][active]" '.((int)($r['active']??1)?'checked':'').($self?' disabled':'').'></td>';
        echo '<td class="muted">'.e($r['last_login'] ?? '—').'</td>';
        echo '<td class="right">'.($self?'':'<button class="btn sm red" name="delete_id" value="'.$r['id'].'" formnovalidate onclick="return confirm(\'Удалить пользователя?\')">Удалить</button>').'</td></tr>';
    }
    echo '</table><div style="margin-top:16px"><button class="btn">Сохранить изменения</button></div></form>';
}

layout_bottom();

/* generic CRUD renderer for services/projects/partners */
function crud_list(PDO $db, string $table, string $noun, array $fields, array $cols): void {
    $editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
    $edit = null;
    if ($editId) { $st=$db->prepare("SELECT * FROM `$table` WHERE id=?"); $st->execute([$editId]); $edit=$st->fetch() ?: null; }
    $rows = $db->query("SELECT * FROM `$table` ORDER BY sort, id")->fetchAll();

    // form
    echo '<form method="post" class="panel">'.csrf_field().'<input type="hidden" name="action" value="save"><input type="hidden" name="id" value="'.($edit['id']??'').'">';
    echo '<h3>'.($edit?'Редактировать':'Добавить').' — '.e($noun).'</h3>';
    foreach ($fields as $name=>$label) {
        $val = $edit[$name] ?? '';
        if ($name === 'visible') { echo '<label><input type="checkbox" name="visible" '.(!empty($val)||!$edit?'checked':'').'> '.e($label).'</label>'; }
        elseif ($name === 'descr') { echo '<label>'.e($label).'</label><textarea name="descr">'.e($val).'</textarea>'; }
        else { $t=$name==='sort'?'number':'text'; echo '<label>'.e($label).'</label><input type="'.$t.'" name="'.e($name).'" value="'.e($val).'">'; }
    }
    echo '<div style="margin-top:16px"><button class="btn">Сохранить</button>';
    if ($edit) echo ' <a class="btn ghost" href="index.php?section='.$_GET['section'].'">Отмена</a>';
    echo '</div></form>';

    // list
    echo '<div class="panel"><table><tr>';
    foreach ($cols as $c) echo '<th>'.e($fields[$c] ?? $c).'</th>';
    echo '<th></th></tr>';
    if (!$rows) echo '<tr><td colspan="'.(count($cols)+1).'" class="muted">Пусто.</td></tr>';
    foreach ($rows as $r) {
        echo '<tr>';
        foreach ($cols as $c) echo '<td>'.e($r[$c] ?? '').'</td>';
        echo '<td class="right" style="white-space:nowrap"><a class="btn sm ghost" href="index.php?section='.$_GET['section'].'&edit='.$r['id'].'">✎</a> ';
        echo '<form method="post" style="display:inline" onsubmit="return confirm(\'Удалить?\')">'.csrf_field().'<input type="hidden" name="action" value="del"><input type="hidden" name="id" value="'.$r['id'].'"><button class="btn sm red">✕</button></form></td></tr>';
    }
    echo '</table></div>';
}


/* ---- upload helpers ---- */
function admin_move(string $tmp, string $name): ?string {
    $dir = dirname(__DIR__) . '/wp-content/uploads/admin';
    @mkdir($dir, 0755, true);
    $name = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($name));
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp','gif','svg','mp4','webm','ogg','mov'])) return null;
    if (!is_uploaded_file($tmp)) return null;
    $name = time() . '_' . $name;
    return move_uploaded_file($tmp, "$dir/$name") ? '/wp-content/uploads/admin/' . $name : null;
}
function admin_upload(string $field): ?string {
    if (empty($_FILES[$field]['name'])) return null;
    return admin_move($_FILES[$field]['tmp_name'], $_FILES[$field]['name']);
}
function admin_upload_multi(string $field): array {
    $out = [];
    if (empty($_FILES[$field]['name'][0])) return $out;
    foreach ($_FILES[$field]['name'] as $i => $name) {
        if ($name === '') continue;
        if ($p = admin_move($_FILES[$field]['tmp_name'][$i], $name)) $out[] = $p;
    }
    return $out;
}

/* ---- rich Projects section (list + editor) ---- */
function render_projects(PDO $db): void {
    $cats = $db->query('SELECT * FROM categories ORDER BY sort,name')->fetchAll();
    $catName = []; foreach ($cats as $c) $catName[$c['id']] = $c['name'];
    $mode = isset($_GET['edit']) ? 'edit' : (isset($_GET['new']) ? 'new' : 'list');

    if ($mode === 'list') {
        $rows = $db->query('SELECT * FROM projects ORDER BY sort,id')->fetchAll();
        $pub = count(array_filter($rows, fn($r) => ($r['status'] ?? 'published') === 'published'));
        echo '<div class="panel"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">';
        echo '<div class="muted">Все ('.count($rows).') &nbsp;|&nbsp; Опубликованные ('.$pub.')</div>';
        echo '<a class="btn" href="index.php?section=projects&new=1">+ Добавить проект</a></div>';
        echo '<table><tr><th>Заголовок</th><th>Фото</th><th>SEO</th><th></th></tr>';
        if (!$rows) echo '<tr><td colspan="4" class="muted">Пока нет проектов.</td></tr>';
        foreach ($rows as $r) {
            $cover = $r['cover'] ?: ($r['image'] ?? '');
            $seo = trim((string)($r['seo_title'] ?? '')) !== '' ? '#011640' : '#cbd3d1';
            echo '<tr><td><b>'.e($r['title']).'</b><br><span class="muted" style="font-size:12px">'.e($r['slug']).'</span></td>';
            echo '<td>'.($cover ? '<img src="'.e($cover).'" style="width:74px;height:48px;object-fit:cover;border-radius:6px">' : '—').'</td>';
            echo '<td><span style="display:inline-block;width:11px;height:11px;border-radius:50%;background:'.$seo.'"></span></td>';
            echo '<td class="right" style="white-space:nowrap">';
            echo '<a class="btn sm ghost" href="index.php?section=projects&edit='.$r['id'].'">Редактировать</a> ';
            echo '<a class="btn sm ghost" href="/'.e(ltrim((string)$r['slug'],'/')).'/" target="_blank">Открыть</a> ';
            echo '<form method="post" style="display:inline" onsubmit="return confirm(\'Удалить проект?\')">'.csrf_field().'<input type="hidden" name="action" value="del"><input type="hidden" name="id" value="'.$r['id'].'"><button class="btn sm red">✕</button></form></td></tr>';
        }
        echo '</table></div>';
        return;
    }

    $p = ['id'=>'','title'=>'','slug'=>'','category_id'=>'','year'=>'','location'=>'','area'=>'','client'=>'',
          'cover'=>'','video'=>'','gallery'=>'[]','descr'=>'','content'=>'','scope'=>'','seo_title'=>'','seo_desc'=>'',
          'robots'=>'index,follow','canonical'=>'','sort'=>0,'visible'=>1,'status'=>'published','image'=>''];
    if ($mode === 'edit') { $st = $db->prepare('SELECT * FROM projects WHERE id=?'); $st->execute([(int)$_GET['edit']]); $p = array_merge($p, $st->fetch() ?: []); }
    $gallery = json_decode($p['gallery'] ?: '[]', true) ?: [];
    $cover = $p['cover'] ?: $p['image'];

    echo '<form method="post" enctype="multipart/form-data" class="panel">'.csrf_field();
    echo '<input type="hidden" name="action" value="save"><input type="hidden" name="id" value="'.e($p['id']).'">';
    echo '<h3>'.($mode==='edit' ? 'Редактировать: '.e($p['title']) : 'Новый проект').'</h3>';

    echo '<div class="row"><div><label>Название</label><input type="text" name="title" value="'.e($p['title']).'"></div>';
    echo '<div><label>Slug (URL)</label><input type="text" name="slug" value="'.e($p['slug']).'"></div></div>';

    echo '<label>Обложка, путь</label><input type="text" name="cover" value="'.e($cover).'">';
    if ($cover) echo '<div style="margin-top:8px"><img src="'.e($cover).'" style="max-width:200px;border-radius:8px"></div>';
    echo '<label>Загрузить новую обложку</label><input type="file" name="cover_file" accept="image/*">';

    echo '<label style="margin-top:20px;color:#011640">Галерея проекта</label>';
    echo '<div id="gal" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px">';
    foreach ($gallery as $g) echo gal_item((string)$g);
    echo '</div>';
    echo '<button type="button" class="btn ghost" style="margin-top:10px" onclick="addGal()">Добавить ещё</button>';
    echo '<label>Загрузить новые изображения</label><input type="file" name="gallery_files[]" accept="image/*" multiple>';

    echo '<label style="margin-top:20px;color:#011640">Видео проекта (URL или загрузка)</label>';
    echo '<input type="text" name="video" value="'.e($p['video']).'" placeholder="/wp-content/uploads/admin/... или https://...">';
    if (!empty($p['video'])) echo '<div style="margin-top:8px"><video src="'.e($p['video']).'" controls style="max-width:300px;border-radius:8px;background:#000"></video></div>';
    echo '<label>Загрузить видео (mp4/webm)</label><input type="file" name="video_file" accept="video/*">';

    echo '<label style="margin-top:20px">Краткое описание</label><textarea name="descr">'.e($p['descr']).'</textarea>';

    echo '<label>Контент проекта</label>';
    echo '<div class="rtbar"><button type="button" onmousedown="rt(event,\'bold\')"><b>B</b></button>';
    echo '<button type="button" onmousedown="rt(event,\'italic\')"><i>I</i></button>';
    echo '<button type="button" onmousedown="rt(event,\'underline\')"><u>U</u></button>';
    echo '<button type="button" onmousedown="rt(event,\'insertUnorderedList\')">• список</button>';
    echo '<button type="button" onmousedown="rt(event,\'insertOrderedList\')">1. список</button>';
    echo '<button type="button" onmousedown="rtLink(event)">Ссылка</button></div>';
    echo '<div id="rt" contenteditable="true" class="rtarea">'.$p['content'].'</div>';
    echo '<textarea name="content" id="rtsrc" style="display:none">'.e($p['content']).'</textarea>';

    echo '<label>Объём работ, каждый пункт с новой строки</label><textarea name="scope" style="min-height:110px">'.e($p['scope']).'</textarea>';

    echo '<h4 style="color:#011640;margin-top:22px">SEO этого проекта</h4>';
    echo '<label>SEO Title</label><input type="text" name="seo_title" value="'.e($p['seo_title']).'">';
    echo '<label>Meta Description</label><textarea name="seo_desc">'.e($p['seo_desc']).'</textarea>';
    echo '<div class="row"><div><label>Robots</label><input type="text" name="robots" value="'.e($p['robots']).'"></div>';
    echo '<div><label>Canonical</label><input type="text" name="canonical" value="'.e($p['canonical']).'"></div></div>';

    echo '<div class="row3" style="margin-top:8px"><div><label>Порядок</label><input type="number" name="sort" value="'.e($p['sort']).'"></div>';
    echo '<div><label>Статус</label><select name="status"><option value="published"'.($p['status']==='published'?' selected':'').'>Опубликовано</option><option value="draft"'.($p['status']==='draft'?' selected':'').'>Черновик</option></select></div>';
    echo '<div><label>&nbsp;</label><label style="font-weight:400"><input type="checkbox" name="visible" '.(!empty($p['visible'])?'checked':'').'> Показывать</label></div></div>';

    echo '<div style="margin-top:20px"><button class="btn">Сохранить проект</button> ';
    echo '<a class="btn ghost" href="index.php?section=projects">Назад к списку</a></div>';
    echo '</form>';
    echo projects_js();
}
function gal_item(string $path): string {
    return '<div class="gitem" style="border:1px solid #e6ebea;border-radius:10px;padding:8px;text-align:center">'
        .'<img src="'.e($path).'" style="width:100%;height:90px;object-fit:cover;border-radius:6px">'
        .'<input type="text" name="gallery[]" value="'.e($path).'" style="font-size:12px;margin-top:6px">'
        .'<button type="button" class="btn sm red" style="margin-top:6px" onclick="this.parentNode.remove()">Удалить</button></div>';
}
function projects_js(): string {
    return <<<'HTML'
<style>.row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}@media(max-width:700px){.row3{grid-template-columns:1fr}}
.rtbar{display:flex;gap:6px;flex-wrap:wrap;border:1px solid #cfd8d6;border-bottom:0;border-radius:9px 9px 0 0;padding:8px;background:#f7faf9}
.rtbar button{background:#fff;border:1px solid #d7dedc;border-radius:6px;padding:4px 10px;cursor:pointer}
.rtarea{border:1px solid #cfd8d6;border-radius:0 0 9px 9px;min-height:140px;padding:12px;background:#fff}
.gitem input{width:100%;border:1px solid #cfd8d6;border-radius:6px;padding:5px}</style>
<script>
function rt(e,cmd){e.preventDefault();document.execCommand(cmd,false,null);document.getElementById('rt').focus();}
function rtLink(e){e.preventDefault();var u=prompt('URL ссылки:','https://');if(u)document.execCommand('createLink',false,u);}
function syncRT(){document.getElementById('rtsrc').value=document.getElementById('rt').innerHTML;}
document.querySelector('form').addEventListener('submit',syncRT);
function addGal(){var d=document.createElement('div');d.className='gitem';d.style.cssText='border:1px solid #e6ebea;border-radius:10px;padding:8px;text-align:center';
d.innerHTML='<input type="text" name="gallery[]" placeholder="/wp-content/uploads/..." style="width:100%;border:1px solid #cfd8d6;border-radius:6px;padding:6px"><button type="button" class="btn sm red" style="margin-top:6px" onclick="this.parentNode.remove()">Удалить</button>';
document.getElementById('gal').appendChild(d);}
</script>
HTML;
}
