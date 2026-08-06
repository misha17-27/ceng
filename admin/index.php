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
        if (attempt_login($_POST['email'] ?? '', $_POST['password'] ?? '')) redirect('index.php');
        $err = 'Неверный e-mail или пароль.';
    }
    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Вход — CENG admin</title><style>'.admin_css().'</style></head><body>';
    echo '<div class="login"><form class="box" method="post">';
    echo '<div style="display:flex;gap:10px;align-items:center;margin-bottom:8px"><span class="logo" style="background:#0f9d76;color:#fff;width:34px;height:34px;border-radius:8px;display:grid;place-items:center;font-weight:800">CE</span><b style="font-size:12px;line-height:1.15">CASPIAN<br>ENGINEERING</b></div>';
    echo '<h2>Вход в панель</h2><div class="sub">Управление контентом сайта</div>';
    if ($err) echo '<div style="background:#b91c1c;color:#fff;padding:10px;border-radius:8px;margin-bottom:12px">'.e($err).'</div>';
    echo csrf_field();
    echo '<label>E-mail</label><input type="email" name="email" required autofocus>';
    echo '<label>Пароль</label><input type="password" name="password" required>';
    echo '<button class="btn" style="width:100%;margin-top:20px" type="submit">Войти</button>';
    echo '</form></div></body></html>';
    exit;
}

/* ---- everything below requires auth ---- */
require_login();
if (!isset(SECTIONS[$section])) $section = 'overview';
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
        foreach (['phone','phone2','email','address'] as $k) kv_set('contacts',$k,(string)($_POST[$k]??''));
        flash('Контакты сохранены.'); redirect('index.php?section=contacts');
    }
    if ($section === 'services') {
        if ($act === 'save') {
            if ($_POST['id']) { $db->prepare('UPDATE services SET title=?,descr=?,icon=?,sort=? WHERE id=?')
                ->execute([$_POST['title'],$_POST['descr'],$_POST['icon'],(int)$_POST['sort'],(int)$_POST['id']]); }
            else { $db->prepare('INSERT INTO services (title,descr,icon,sort) VALUES (?,?,?,?)')
                ->execute([$_POST['title'],$_POST['descr'],$_POST['icon'],(int)$_POST['sort']]); }
            flash('Услуга сохранена.');
        } elseif ($act === 'del') { $db->prepare('DELETE FROM services WHERE id=?')->execute([(int)$_POST['id']]); flash('Удалено.'); }
        redirect('index.php?section=services');
    }
    if ($section === 'projects') {
        if ($act === 'save') {
            $cover = trim($_POST['cover'] ?? '');
            if ($p = admin_upload('cover_file')) $cover = $p;                 // uploaded cover overrides
            $gallery = array_values(array_filter(array_map('trim', $_POST['gallery'] ?? []), fn($x)=>$x!==''));
            foreach (admin_upload_multi('gallery_files') as $p) $gallery[] = $p;
            $f = [
              'title'=>$_POST['title']??'', 'slug'=>$_POST['slug']??'', 'category_id'=>($_POST['category_id']?:null),
              'year'=>$_POST['year']??'', 'location'=>$_POST['location']??'', 'area'=>$_POST['area']??'',
              'client'=>$_POST['client']??'', 'cover'=>$cover, 'gallery'=>json_encode($gallery, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
              'descr'=>$_POST['descr']??'', 'content'=>$_POST['content']??'', 'scope'=>$_POST['scope']??'',
              'seo_title'=>$_POST['seo_title']??'', 'seo_desc'=>$_POST['seo_desc']??'',
              'robots'=>$_POST['robots']??'index,follow', 'canonical'=>$_POST['canonical']??'',
              'sort'=>(int)($_POST['sort']??0), 'visible'=>isset($_POST['visible'])?1:0,
              'status'=>$_POST['status']??'published',
            ];
            if (!empty($_POST['id'])) {
                $set = implode(',', array_map(fn($k)=>"`$k`=?", array_keys($f)));
                $st = $db->prepare("UPDATE projects SET $set WHERE id=?");
                $st->execute([...array_values($f), (int)$_POST['id']]);
            } else {
                $cols = '`'.implode('`,`', array_keys($f)).'`';
                $ph = implode(',', array_fill(0, count($f), '?'));
                $db->prepare("INSERT INTO projects ($cols) VALUES ($ph)")->execute(array_values($f));
            }
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
        foreach (($_POST['title'] ?? []) as $slug=>$title) {
            $db->prepare('UPDATE pages SET title=?, in_menu=? WHERE slug=?')
               ->execute([$title, isset($_POST['in_menu'][$slug])?1:0, $slug]);
        }
        flash('Страницы сохранены.'); redirect('index.php?section=pages');
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
    if ($section === 'security' && $act === 'passwd') {
        $a = current_admin();
        $st = $db->prepare('SELECT pass_hash FROM admins WHERE id=?'); $st->execute([$a['id']]); $row=$st->fetch();
        if (!password_verify($_POST['old'] ?? '', $row['pass_hash'] ?? '')) flash('Текущий пароль неверный.', 'err');
        elseif (strlen($_POST['new'] ?? '') < 6) flash('Новый пароль слишком короткий.', 'err');
        elseif (($_POST['new'] ?? '') !== ($_POST['new2'] ?? '')) flash('Пароли не совпадают.', 'err');
        else { $db->prepare('UPDATE admins SET pass_hash=? WHERE id=?')
                  ->execute([password_hash($_POST['new'], PASSWORD_DEFAULT), $a['id']]); flash('Пароль изменён.'); }
        redirect('index.php?section=security');
    }
    if ($section === 'images' && $act === 'upload' && !empty($_FILES['file']['name'])) {
        $dir = dirname(__DIR__) . '/wp-content/uploads/admin';
        @mkdir($dir, 0755, true);
        $name = preg_replace('/[^A-Za-z0-9._-]/','_', basename($_FILES['file']['name']));
        $ok = in_array(strtolower(pathinfo($name,PATHINFO_EXTENSION)), ['jpg','jpeg','png','webp','gif','svg']);
        if ($ok && move_uploaded_file($_FILES['file']['tmp_name'], "$dir/$name")) flash('Загружено: /wp-content/uploads/admin/'.$name);
        else flash('Не удалось загрузить (только jpg/png/webp/gif/svg).', 'err');
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
    $rows = $db->query('SELECT k,v FROM texts ORDER BY k')->fetchAll();
    echo '<form method="post" class="panel">'.csrf_field().'<input type="hidden" name="action" value="save">';
    if (!$rows) echo '<p class="muted">Пока нет текстовых блоков (создаются установщиком).</p>';
    foreach ($rows as $r) {
        echo '<label>'.e($r['k']).'</label><textarea name="t['.e($r['k']).']">'.e($r['v']).'</textarea>';
    }
    echo '<div style="margin-top:16px"><button class="btn">Сохранить</button></div></form>';
}

elseif ($section === 'contacts') {
    $g = fn($k)=>e(kv_get('contacts',$k));
    echo '<form method="post" class="panel">'.csrf_field().'<input type="hidden" name="action" value="save">';
    echo '<div class="row"><div><label>Телефон (в шапке)</label><input type="text" name="phone" value="'.$g('phone').'"></div>';
    echo '<div><label>Телефон 2 (в футере)</label><input type="text" name="phone2" value="'.$g('phone2').'"></div></div>';
    echo '<div class="row"><div><label>E-mail</label><input type="text" name="email" value="'.$g('email').'"></div>';
    echo '<div><label>Адрес</label><input type="text" name="address" value="'.$g('address').'"></div></div>';
    echo '<div style="margin-top:16px"><button class="btn">Сохранить</button></div></form>';
    echo '<p class="muted">Эти значения выводятся в шапке и футере сайта (после привязки фронтенда).</p>';
}

elseif ($section === 'services') { crud_list($db,'services','Услуга',
    ['title'=>'Название','descr'=>'Описание','icon'=>'Иконка (класс/URL)','sort'=>'Порядок'], ['title','icon','sort']); }

elseif ($section === 'projects') { render_projects($db); }

elseif ($section === 'partners') { crud_list($db,'partners','Партнёр',
    ['name'=>'Название','image'=>'Логотип (URL)','url'=>'Ссылка','sort'=>'Порядок'], ['name','sort']); }

elseif ($section === 'pages') {
    $rows = $db->query('SELECT * FROM pages ORDER BY sort')->fetchAll();
    echo '<form method="post" class="panel">'.csrf_field().'<input type="hidden" name="action" value="save"><table><tr><th>Slug</th><th>Заголовок</th><th>В меню</th></tr>';
    foreach ($rows as $r) {
        echo '<tr><td class="muted">/'.e(ltrim($r['slug'],'/')).'</td>';
        echo '<td><input type="text" name="title['.e($r['slug']).']" value="'.e($r['title']).'"></td>';
        echo '<td><input type="checkbox" name="in_menu['.e($r['slug']).']" '.($r['in_menu']?'checked':'').'></td></tr>';
    }
    echo '</table><div style="margin-top:16px"><button class="btn">Сохранить</button></div></form>';
    echo '<p class="muted">Набор страниц фиксирован (это статические PHP-страницы). Здесь меняются заголовок и наличие в меню.</p>';
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
    $base = dirname(__DIR__) . '/wp-content/uploads/admin';
    echo '<form method="post" enctype="multipart/form-data" class="panel">'.csrf_field().'<input type="hidden" name="action" value="upload">';
    echo '<label>Загрузить изображение</label><input type="file" name="file" accept="image/*" required>';
    echo '<div style="margin-top:14px"><button class="btn">Загрузить</button></div><p class="muted">Файлы кладутся в /wp-content/uploads/admin/. Скопируй URL и вставь в проект/партнёра.</p></form>';
    echo '<div class="panel"><h3>Загруженные</h3><div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:14px">';
    foreach (glob($base.'/*') ?: [] as $f) {
        $u = '/wp-content/uploads/admin/'.rawurlencode(basename($f));
        echo '<div style="text-align:center"><img src="'.e($u).'" style="max-width:100%;height:90px;object-fit:contain;background:#f4f6f6;border-radius:8px"><div class="muted" style="font-size:11px;word-break:break-all">'.e(basename($f)).'</div></div>';
    }
    echo '</div></div>';
}

elseif ($section === 'security') {
    echo '<form method="post" class="panel" style="max-width:460px">'.csrf_field().'<input type="hidden" name="action" value="passwd">';
    echo '<h3>Сменить пароль</h3>';
    echo '<label>Текущий пароль</label><input type="password" name="old" required>';
    echo '<label>Новый пароль</label><input type="password" name="new" required>';
    echo '<label>Повторите новый</label><input type="password" name="new2" required>';
    echo '<div style="margin-top:16px"><button class="btn">Изменить</button></div></form>';
    echo '<div class="panel"><h3>Рекомендации</h3><ul class="muted"><li>Удали <code>install.php</code> после установки.</li><li>Используй сложный пароль.</li><li>Панель доступна по HTTPS.</li></ul></div>';
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
    if (!in_array($ext, ['jpg','jpeg','png','webp','gif','svg'])) return null;
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
        echo '<table><tr><th>Заголовок</th><th>Фото</th><th>Категория</th><th>Год</th><th>SEO</th><th></th></tr>';
        if (!$rows) echo '<tr><td colspan="6" class="muted">Пока нет проектов.</td></tr>';
        foreach ($rows as $r) {
            $cover = $r['cover'] ?: ($r['image'] ?? '');
            $seo = trim((string)($r['seo_title'] ?? '')) !== '' ? '#0f9d76' : '#cbd3d1';
            echo '<tr><td><b>'.e($r['title']).'</b><br><span class="muted" style="font-size:12px">'.e($r['slug']).'</span></td>';
            echo '<td>'.($cover ? '<img src="'.e($cover).'" style="width:74px;height:48px;object-fit:cover;border-radius:6px">' : '—').'</td>';
            echo '<td>'.e($catName[$r['category_id']] ?? '—').'</td><td>'.e($r['year']).'</td>';
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
          'cover'=>'','gallery'=>'[]','descr'=>'','content'=>'','scope'=>'','seo_title'=>'','seo_desc'=>'',
          'robots'=>'index,follow','canonical'=>'','sort'=>0,'visible'=>1,'status'=>'published','image'=>''];
    if ($mode === 'edit') { $st = $db->prepare('SELECT * FROM projects WHERE id=?'); $st->execute([(int)$_GET['edit']]); $p = array_merge($p, $st->fetch() ?: []); }
    $gallery = json_decode($p['gallery'] ?: '[]', true) ?: [];
    $cover = $p['cover'] ?: $p['image'];

    echo '<form method="post" enctype="multipart/form-data" class="panel">'.csrf_field();
    echo '<input type="hidden" name="action" value="save"><input type="hidden" name="id" value="'.e($p['id']).'">';
    echo '<h3>'.($mode==='edit' ? 'Редактировать: '.e($p['title']) : 'Новый проект').'</h3>';

    echo '<div class="row3"><div><label>Название</label><input type="text" name="title" value="'.e($p['title']).'"></div>';
    echo '<div><label>Slug (URL)</label><input type="text" name="slug" value="'.e($p['slug']).'"></div>';
    echo '<div><label>Категория</label><select name="category_id"><option value="">—</option>';
    foreach ($cats as $c) echo '<option value="'.$c['id'].'"'.($p['category_id']==$c['id']?' selected':'').'>'.e($c['name']).'</option>';
    echo '</select></div></div>';

    echo '<div class="row3"><div><label>Год</label><input type="text" name="year" value="'.e($p['year']).'"></div>';
    echo '<div><label>Локация</label><input type="text" name="location" value="'.e($p['location']).'"></div>';
    echo '<div><label>Площадь</label><input type="text" name="area" value="'.e($p['area']).'"></div></div>';
    echo '<label>Клиент</label><input type="text" name="client" value="'.e($p['client']).'">';

    echo '<label>Обложка, путь</label><input type="text" name="cover" value="'.e($cover).'">';
    if ($cover) echo '<div style="margin-top:8px"><img src="'.e($cover).'" style="max-width:200px;border-radius:8px"></div>';
    echo '<label>Загрузить новую обложку</label><input type="file" name="cover_file" accept="image/*">';

    echo '<label style="margin-top:20px;color:#0f9d76">Галерея проекта</label>';
    echo '<div id="gal" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px">';
    foreach ($gallery as $g) echo gal_item((string)$g);
    echo '</div>';
    echo '<button type="button" class="btn ghost" style="margin-top:10px" onclick="addGal()">Добавить ещё</button>';
    echo '<label>Загрузить новые изображения</label><input type="file" name="gallery_files[]" accept="image/*" multiple>';

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

    echo '<h4 style="color:#0f9d76;margin-top:22px">SEO этого проекта</h4>';
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
