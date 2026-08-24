<?php
/* Dynamic project page template. The calling loader sets $PROJECT_SLUG
   (and optionally $PROJECT_FALLBACK with title/cover). Data comes from the
   admin `projects` table; falls back gracefully if the DB is unavailable. */
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/data.php';
$P = ['slug'=>$PROJECT_SLUG,'title'=>'','cover'=>'','video'=>'','gallery'=>'[]','content'=>'',
      'descr'=>'','scope'=>'','seo_title'=>'','seo_desc'=>'','robots'=>'index,follow','canonical'=>''];
if (isset($PROJECT_FALLBACK) && is_array($PROJECT_FALLBACK)) $P = array_merge($P, $PROJECT_FALLBACK);
$__pdo = _site_pdo();
if ($__pdo) { try { $__st = $__pdo->prepare('SELECT * FROM projects WHERE slug=? LIMIT 1'); $__st->execute([$PROJECT_SLUG]);
    if ($__r = $__st->fetch(PDO::FETCH_ASSOC)) { foreach ($__r as $__k=>$__v) { if ($__v !== null && $__v !== '') $P[$__k] = $__v; } }
} catch (Throwable $e) {} }
if ($P['title'] === '') $P['title'] = ucwords(str_replace('-', ' ', (string)$PROJECT_SLUG));
$PU = '/' . $P['slug'] . '/';
$HOST = isset($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] : '';
$GALLERY = json_decode($P['gallery'] ?: '[]', true) ?: [];
$SCOPE = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string)$P['scope']))));
$RELATED = [];
if ($__pdo) { try { $__q = $__pdo->prepare("SELECT slug,title,cover FROM projects WHERE slug<>? AND visible=1 AND (status IS NULL OR status='' OR status='published') ORDER BY sort,id LIMIT 3");
    $__q->execute([$PROJECT_SLUG]); $RELATED = $__q->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) {} }
$VIDEO_EMBED = '';
if (!empty($P['video'])) { $__v = (string)$P['video'];
    if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{6,})~', $__v, $__m))
        $VIDEO_EMBED = '<iframe class="proj-video" src="https://www.youtube.com/embed/' . $__m[1] . '" frameborder="0" allowfullscreen loading="lazy"></iframe>';
    else $VIDEO_EMBED = '<video class="proj-video" src="' . htmlspecialchars($__v, ENT_QUOTES) . '" controls preload="metadata"></video>';
}
?>
<!doctype html>
<html lang="az">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<meta name='robots' content='<?php echo esc_html($P["robots"] ?: "index,follow"); ?>' />
	<style>img:is([sizes="auto" i], [sizes^="auto," i]) { contain-intrinsic-size: 3000px 1500px }</style>
	<link rel="alternate" hreflang="az" href="<?php echo esc_html($PU); ?>" />
<link rel="alternate" hreflang="x-default" href="<?php echo esc_html($PU); ?>" />

	<title><?php echo esc_html($P['seo_title'] !== '' ? $P['seo_title'] : $P['title'] . ' - Ceng.az'); ?></title>
	<meta name="description" content="<?php echo esc_html($P['seo_desc'] !== '' ? $P['seo_desc'] : $P['descr']); ?>" />
	<link rel="canonical" href="<?php echo esc_html($P['canonical'] !== '' ? $P['canonical'] : $HOST . $PU); ?>" />
	<meta property="og:locale" content="az_AZ" />
	<meta property="og:type" content="article" />
	<meta property="og:title" content="<?php echo esc_html($P['title']); ?>" />
	<meta property="og:description" content="<?php echo esc_html($P['seo_desc'] !== '' ? $P['seo_desc'] : $P['descr']); ?>" />
	<meta property="og:url" content="<?php echo esc_html($HOST . $PU); ?>" />
	<?php if ($P['cover'] !== ''): ?><meta property="og:image" content="<?php echo esc_html($HOST . $P['cover']); ?>" /><?php endif; ?>
	<meta name="twitter:card" content="summary_large_image" />


<link rel="alternate" type="application/rss+xml" title="Ceng.az &raquo; Qidalandırıcısı" href="../feed/index.html" />
<link rel="alternate" type="application/rss+xml" title="Ceng.az &raquo; Şərh Qidalandırıcısı" href="../comments/feed/index.html" />
<link rel="alternate" type="application/rss+xml" title="Ceng.az &raquo; <?php echo esc_html($P['title']); ?> Şərh Qidalandırıcısı" href="feed/index.html" />
<script>
window._wpemojiSettings = {"baseUrl":"https:\/\/s.w.org\/images\/core\/emoji\/15.0.3\/72x72\/","ext":".png","svgUrl":"https:\/\/s.w.org\/images\/core\/emoji\/15.0.3\/svg\/","svgExt":".svg","source":{"concatemoji":"\/wp-includes\/js\/wp-emoji-release.min.js?ver=6.7.2"}};
/*! This file is auto-generated */
!function(i,n){var o,s,e;function c(e){try{var t={supportTests:e,timestamp:(new Date).valueOf()};sessionStorage.setItem(o,JSON.stringify(t))}catch(e){}}function p(e,t,n){e.clearRect(0,0,e.canvas.width,e.canvas.height),e.fillText(t,0,0);var t=new Uint32Array(e.getImageData(0,0,e.canvas.width,e.canvas.height).data),r=(e.clearRect(0,0,e.canvas.width,e.canvas.height),e.fillText(n,0,0),new Uint32Array(e.getImageData(0,0,e.canvas.width,e.canvas.height).data));return t.every(function(e,t){return e===r[t]})}function u(e,t,n){switch(t){case"flag":return n(e,"\ud83c\udff3\ufe0f\u200d\u26a7\ufe0f","\ud83c\udff3\ufe0f\u200b\u26a7\ufe0f")?!1:!n(e,"\ud83c\uddfa\ud83c\uddf3","\ud83c\uddfa\u200b\ud83c\uddf3")&&!n(e,"\ud83c\udff4\udb40\udc67\udb40\udc62\udb40\udc65\udb40\udc6e\udb40\udc67\udb40\udc7f","\ud83c\udff4\u200b\udb40\udc67\u200b\udb40\udc62\u200b\udb40\udc65\u200b\udb40\udc6e\u200b\udb40\udc67\u200b\udb40\udc7f");case"emoji":return!n(e,"\ud83d\udc26\u200d\u2b1b","\ud83d\udc26\u200b\u2b1b")}return!1}function f(e,t,n){var r="undefined"!=typeof WorkerGlobalScope&&self instanceof WorkerGlobalScope?new OffscreenCanvas(300,150):i.createElement("canvas"),a=r.getContext("2d",{willReadFrequently:!0}),o=(a.textBaseline="top",a.font="600 32px Arial",{});return e.forEach(function(e){o[e]=t(a,e,n)}),o}function t(e){var t=i.createElement("script");t.src=e,t.defer=!0,i.head.appendChild(t)}"undefined"!=typeof Promise&&(o="wpEmojiSettingsSupports",s=["flag","emoji"],n.supports={everything:!0,everythingExceptFlag:!0},e=new Promise(function(e){i.addEventListener("DOMContentLoaded",e,{once:!0})}),new Promise(function(t){var n=function(){try{var e=JSON.parse(sessionStorage.getItem(o));if("object"==typeof e&&"number"==typeof e.timestamp&&(new Date).valueOf()<e.timestamp+604800&&"object"==typeof e.supportTests)return e.supportTests}catch(e){}return null}();if(!n){if("undefined"!=typeof Worker&&"undefined"!=typeof OffscreenCanvas&&"undefined"!=typeof URL&&URL.createObjectURL&&"undefined"!=typeof Blob)try{var e="postMessage("+f.toString()+"("+[JSON.stringify(s),u.toString(),p.toString()].join(",")+"));",r=new Blob([e],{type:"text/javascript"}),a=new Worker(URL.createObjectURL(r),{name:"wpTestEmojiSupports"});return void(a.onmessage=function(e){c(n=e.data),a.terminate(),t(n)})}catch(e){}c(n=f(s,u,p))}t(n)}).then(function(e){for(var t in e)n.supports[t]=e[t],n.supports.everything=n.supports.everything&&n.supports[t],"flag"!==t&&(n.supports.everythingExceptFlag=n.supports.everythingExceptFlag&&n.supports[t]);n.supports.everythingExceptFlag=n.supports.everythingExceptFlag&&!n.supports.flag,n.DOMReady=!1,n.readyCallback=function(){n.DOMReady=!0}}).then(function(){return e}).then(function(){var e;n.supports.everything||(n.readyCallback(),(e=n.source||{}).concatemoji?t(e.concatemoji):e.wpemoji&&e.twemoji&&(t(e.twemoji),t(e.wpemoji)))}))}((window,document),window._wpemojiSettings);
</script>
<style id='wp-emoji-styles-inline-css'>

	img.wp-smiley, img.emoji {
		display: inline !important;
		border: none !important;
		box-shadow: none !important;
		height: 1em !important;
		width: 1em !important;
		margin: 0 0.07em !important;
		vertical-align: -0.1em !important;
		background: none !important;
		padding: 0 !important;
	}
</style>
<link rel='stylesheet' id='wp-block-library-css' href='/wp-includes/css/dist/block-library/style.min.css@ver=6.7.2.css' media='all' />
<style id='global-styles-inline-css'>
:root{--wp--preset--aspect-ratio--square: 1;--wp--preset--aspect-ratio--4-3: 4/3;--wp--preset--aspect-ratio--3-4: 3/4;--wp--preset--aspect-ratio--3-2: 3/2;--wp--preset--aspect-ratio--2-3: 2/3;--wp--preset--aspect-ratio--16-9: 16/9;--wp--preset--aspect-ratio--9-16: 9/16;--wp--preset--color--black: #000000;--wp--preset--color--cyan-bluish-gray: #abb8c3;--wp--preset--color--white: #ffffff;--wp--preset--color--pale-pink: #f78da7;--wp--preset--color--vivid-red: #cf2e2e;--wp--preset--color--luminous-vivid-orange: #ff6900;--wp--preset--color--luminous-vivid-amber: #fcb900;--wp--preset--color--light-green-cyan: #7bdcb5;--wp--preset--color--vivid-green-cyan: #00d084;--wp--preset--color--pale-cyan-blue: #8ed1fc;--wp--preset--color--vivid-cyan-blue: #0693e3;--wp--preset--color--vivid-purple: #9b51e0;--wp--preset--gradient--vivid-cyan-blue-to-vivid-purple: linear-gradient(135deg,rgba(6,147,227,1) 0%,rgb(155,81,224) 100%);--wp--preset--gradient--light-green-cyan-to-vivid-green-cyan: linear-gradient(135deg,rgb(122,220,180) 0%,rgb(0,208,130) 100%);--wp--preset--gradient--luminous-vivid-amber-to-luminous-vivid-orange: linear-gradient(135deg,rgba(252,185,0,1) 0%,rgba(255,105,0,1) 100%);--wp--preset--gradient--luminous-vivid-orange-to-vivid-red: linear-gradient(135deg,rgba(255,105,0,1) 0%,rgb(207,46,46) 100%);--wp--preset--gradient--very-light-gray-to-cyan-bluish-gray: linear-gradient(135deg,rgb(238,238,238) 0%,rgb(169,184,195) 100%);--wp--preset--gradient--cool-to-warm-spectrum: linear-gradient(135deg,rgb(74,234,220) 0%,rgb(151,120,209) 20%,rgb(207,42,186) 40%,rgb(238,44,130) 60%,rgb(251,105,98) 80%,rgb(254,248,76) 100%);--wp--preset--gradient--blush-light-purple: linear-gradient(135deg,rgb(255,206,236) 0%,rgb(152,150,240) 100%);--wp--preset--gradient--blush-bordeaux: linear-gradient(135deg,rgb(254,205,165) 0%,rgb(254,45,45) 50%,rgb(107,0,62) 100%);--wp--preset--gradient--luminous-dusk: linear-gradient(135deg,rgb(255,203,112) 0%,rgb(199,81,192) 50%,rgb(65,88,208) 100%);--wp--preset--gradient--pale-ocean: linear-gradient(135deg,rgb(255,245,203) 0%,rgb(182,227,212) 50%,rgb(51,167,181) 100%);--wp--preset--gradient--electric-grass: linear-gradient(135deg,rgb(202,248,128) 0%,rgb(113,206,126) 100%);--wp--preset--gradient--midnight: linear-gradient(135deg,rgb(2,3,129) 0%,rgb(40,116,252) 100%);--wp--preset--font-size--small: 13px;--wp--preset--font-size--medium: 20px;--wp--preset--font-size--large: 36px;--wp--preset--font-size--x-large: 42px;--wp--preset--spacing--20: 0.44rem;--wp--preset--spacing--30: 0.67rem;--wp--preset--spacing--40: 1rem;--wp--preset--spacing--50: 1.5rem;--wp--preset--spacing--60: 2.25rem;--wp--preset--spacing--70: 3.38rem;--wp--preset--spacing--80: 5.06rem;--wp--preset--shadow--natural: 6px 6px 9px rgba(0, 0, 0, 0.2);--wp--preset--shadow--deep: 12px 12px 50px rgba(0, 0, 0, 0.4);--wp--preset--shadow--sharp: 6px 6px 0px rgba(0, 0, 0, 0.2);--wp--preset--shadow--outlined: 6px 6px 0px -3px rgba(255, 255, 255, 1), 6px 6px rgba(0, 0, 0, 1);--wp--preset--shadow--crisp: 6px 6px 0px rgba(0, 0, 0, 1);}:root { --wp--style--global--content-size: 800px;--wp--style--global--wide-size: 1200px; }:where(body) { margin: 0; }.wp-site-blocks > .alignleft { float: left; margin-right: 2em; }.wp-site-blocks > .alignright { float: right; margin-left: 2em; }.wp-site-blocks > .aligncenter { justify-content: center; margin-left: auto; margin-right: auto; }:where(.wp-site-blocks) > * { margin-block-start: 24px; margin-block-end: 0; }:where(.wp-site-blocks) > :first-child { margin-block-start: 0; }:where(.wp-site-blocks) > :last-child { margin-block-end: 0; }:root { --wp--style--block-gap: 24px; }:root :where(.is-layout-flow) > :first-child{margin-block-start: 0;}:root :where(.is-layout-flow) > :last-child{margin-block-end: 0;}:root :where(.is-layout-flow) > *{margin-block-start: 24px;margin-block-end: 0;}:root :where(.is-layout-constrained) > :first-child{margin-block-start: 0;}:root :where(.is-layout-constrained) > :last-child{margin-block-end: 0;}:root :where(.is-layout-constrained) > *{margin-block-start: 24px;margin-block-end: 0;}:root :where(.is-layout-flex){gap: 24px;}:root :where(.is-layout-grid){gap: 24px;}.is-layout-flow > .alignleft{float: left;margin-inline-start: 0;margin-inline-end: 2em;}.is-layout-flow > .alignright{float: right;margin-inline-start: 2em;margin-inline-end: 0;}.is-layout-flow > .aligncenter{margin-left: auto !important;margin-right: auto !important;}.is-layout-constrained > .alignleft{float: left;margin-inline-start: 0;margin-inline-end: 2em;}.is-layout-constrained > .alignright{float: right;margin-inline-start: 2em;margin-inline-end: 0;}.is-layout-constrained > .aligncenter{margin-left: auto !important;margin-right: auto !important;}.is-layout-constrained > :where(:not(.alignleft):not(.alignright):not(.alignfull)){max-width: var(--wp--style--global--content-size);margin-left: auto !important;margin-right: auto !important;}.is-layout-constrained > .alignwide{max-width: var(--wp--style--global--wide-size);}body .is-layout-flex{display: flex;}.is-layout-flex{flex-wrap: wrap;align-items: center;}.is-layout-flex > :is(*, div){margin: 0;}body .is-layout-grid{display: grid;}.is-layout-grid > :is(*, div){margin: 0;}body{padding-top: 0px;padding-right: 0px;padding-bottom: 0px;padding-left: 0px;}a:where(:not(.wp-element-button)){text-decoration: underline;}:root :where(.wp-element-button, .wp-block-button__link){background-color: #32373c;border-width: 0;color: #fff;font-family: inherit;font-size: inherit;line-height: inherit;padding: calc(0.667em + 2px) calc(1.333em + 2px);text-decoration: none;}.has-black-color{color: var(--wp--preset--color--black) !important;}.has-cyan-bluish-gray-color{color: var(--wp--preset--color--cyan-bluish-gray) !important;}.has-white-color{color: var(--wp--preset--color--white) !important;}.has-pale-pink-color{color: var(--wp--preset--color--pale-pink) !important;}.has-vivid-red-color{color: var(--wp--preset--color--vivid-red) !important;}.has-luminous-vivid-orange-color{color: var(--wp--preset--color--luminous-vivid-orange) !important;}.has-luminous-vivid-amber-color{color: var(--wp--preset--color--luminous-vivid-amber) !important;}.has-light-green-cyan-color{color: var(--wp--preset--color--light-green-cyan) !important;}.has-vivid-green-cyan-color{color: var(--wp--preset--color--vivid-green-cyan) !important;}.has-pale-cyan-blue-color{color: var(--wp--preset--color--pale-cyan-blue) !important;}.has-vivid-cyan-blue-color{color: var(--wp--preset--color--vivid-cyan-blue) !important;}.has-vivid-purple-color{color: var(--wp--preset--color--vivid-purple) !important;}.has-black-background-color{background-color: var(--wp--preset--color--black) !important;}.has-cyan-bluish-gray-background-color{background-color: var(--wp--preset--color--cyan-bluish-gray) !important;}.has-white-background-color{background-color: var(--wp--preset--color--white) !important;}.has-pale-pink-background-color{background-color: var(--wp--preset--color--pale-pink) !important;}.has-vivid-red-background-color{background-color: var(--wp--preset--color--vivid-red) !important;}.has-luminous-vivid-orange-background-color{background-color: var(--wp--preset--color--luminous-vivid-orange) !important;}.has-luminous-vivid-amber-background-color{background-color: var(--wp--preset--color--luminous-vivid-amber) !important;}.has-light-green-cyan-background-color{background-color: var(--wp--preset--color--light-green-cyan) !important;}.has-vivid-green-cyan-background-color{background-color: var(--wp--preset--color--vivid-green-cyan) !important;}.has-pale-cyan-blue-background-color{background-color: var(--wp--preset--color--pale-cyan-blue) !important;}.has-vivid-cyan-blue-background-color{background-color: var(--wp--preset--color--vivid-cyan-blue) !important;}.has-vivid-purple-background-color{background-color: var(--wp--preset--color--vivid-purple) !important;}.has-black-border-color{border-color: var(--wp--preset--color--black) !important;}.has-cyan-bluish-gray-border-color{border-color: var(--wp--preset--color--cyan-bluish-gray) !important;}.has-white-border-color{border-color: var(--wp--preset--color--white) !important;}.has-pale-pink-border-color{border-color: var(--wp--preset--color--pale-pink) !important;}.has-vivid-red-border-color{border-color: var(--wp--preset--color--vivid-red) !important;}.has-luminous-vivid-orange-border-color{border-color: var(--wp--preset--color--luminous-vivid-orange) !important;}.has-luminous-vivid-amber-border-color{border-color: var(--wp--preset--color--luminous-vivid-amber) !important;}.has-light-green-cyan-border-color{border-color: var(--wp--preset--color--light-green-cyan) !important;}.has-vivid-green-cyan-border-color{border-color: var(--wp--preset--color--vivid-green-cyan) !important;}.has-pale-cyan-blue-border-color{border-color: var(--wp--preset--color--pale-cyan-blue) !important;}.has-vivid-cyan-blue-border-color{border-color: var(--wp--preset--color--vivid-cyan-blue) !important;}.has-vivid-purple-border-color{border-color: var(--wp--preset--color--vivid-purple) !important;}.has-vivid-cyan-blue-to-vivid-purple-gradient-background{background: var(--wp--preset--gradient--vivid-cyan-blue-to-vivid-purple) !important;}.has-light-green-cyan-to-vivid-green-cyan-gradient-background{background: var(--wp--preset--gradient--light-green-cyan-to-vivid-green-cyan) !important;}.has-luminous-vivid-amber-to-luminous-vivid-orange-gradient-background{background: var(--wp--preset--gradient--luminous-vivid-amber-to-luminous-vivid-orange) !important;}.has-luminous-vivid-orange-to-vivid-red-gradient-background{background: var(--wp--preset--gradient--luminous-vivid-orange-to-vivid-red) !important;}.has-very-light-gray-to-cyan-bluish-gray-gradient-background{background: var(--wp--preset--gradient--very-light-gray-to-cyan-bluish-gray) !important;}.has-cool-to-warm-spectrum-gradient-background{background: var(--wp--preset--gradient--cool-to-warm-spectrum) !important;}.has-blush-light-purple-gradient-background{background: var(--wp--preset--gradient--blush-light-purple) !important;}.has-blush-bordeaux-gradient-background{background: var(--wp--preset--gradient--blush-bordeaux) !important;}.has-luminous-dusk-gradient-background{background: var(--wp--preset--gradient--luminous-dusk) !important;}.has-pale-ocean-gradient-background{background: var(--wp--preset--gradient--pale-ocean) !important;}.has-electric-grass-gradient-background{background: var(--wp--preset--gradient--electric-grass) !important;}.has-midnight-gradient-background{background: var(--wp--preset--gradient--midnight) !important;}.has-small-font-size{font-size: var(--wp--preset--font-size--small) !important;}.has-medium-font-size{font-size: var(--wp--preset--font-size--medium) !important;}.has-large-font-size{font-size: var(--wp--preset--font-size--large) !important;}.has-x-large-font-size{font-size: var(--wp--preset--font-size--x-large) !important;}
:root :where(.wp-block-pullquote){font-size: 1.5em;line-height: 1.6;}
</style>
<link rel='stylesheet' id='wpml-legacy-horizontal-list-0-css' href='/wp-content/plugins/sitepress-multilingual-cms/templates/language-switchers/legacy-list-horizontal/style.min.css@ver=1.css' media='all' />
<link rel='stylesheet' id='hello-elementor-css' href='/wp-content/themes/hello-elementor/style.min.css@ver=3.3.0.css' media='all' />
<link rel='stylesheet' id='hello-elementor-theme-style-css' href='/wp-content/themes/hello-elementor/theme.min.css@ver=3.3.0.css' media='all' />
<link rel='stylesheet' id='hello-elementor-header-footer-css' href='/wp-content/themes/hello-elementor/header-footer.min.css@ver=3.3.0.css' media='all' />
<link rel='stylesheet' id='elementor-frontend-css' href='/wp-content/plugins/elementor/assets/css/frontend.min.css@ver=3.28.3.css' media='all' />
<style id='elementor-frontend-inline-css'>
.elementor-30 .elementor-element.elementor-element-575eedd:not(.elementor-motion-effects-element-type-background), .elementor-30 .elementor-element.elementor-element-575eedd > .elementor-motion-effects-container > .elementor-motion-effects-layer{background-image:var(--wpr-bg-8a83a789-73ba-4a7e-b49d-d8d6643253a0);}
</style>
<link rel='stylesheet' id='elementor-post-13-css' href='/wp-content/uploads/elementor/css/post-13.css@ver=1744624471.css' media='all' />
<link rel='stylesheet' id='widget-menu-anchor-css' href='/wp-content/plugins/elementor/assets/css/widget-menu-anchor.min.css@ver=3.28.3.css' media='all' />
<link rel='stylesheet' id='widget-image-css' href='/wp-content/plugins/elementor/assets/css/widget-image.min.css@ver=3.28.3.css' media='all' />
<link rel='stylesheet' id='widget-nav-menu-css' href='/wp-content/plugins/elementor-pro/assets/css/widget-nav-menu.min.css@ver=3.28.2.css' media='all' />
<link rel='stylesheet' id='widget-heading-css' href='/wp-content/plugins/elementor/assets/css/widget-heading.min.css@ver=3.28.3.css' media='all' />
<link rel='stylesheet' id='widget-icon-list-css' href='/wp-content/plugins/elementor/assets/css/widget-icon-list.min.css@ver=3.28.3.css' media='all' />
<link rel='stylesheet' id='widget-posts-css' href='/wp-content/plugins/elementor-pro/assets/css/widget-posts.min.css@ver=3.28.2.css' media='all' />
<link rel='stylesheet' id='elementor-post-41-css' href='/wp-content/uploads/elementor/css/post-41.css@ver=1744624474.css' media='all' />
<link rel='stylesheet' id='elementor-post-33-css' href='/wp-content/uploads/elementor/css/post-33.css@ver=1744624475.css' media='all' />
<link rel='stylesheet' id='elementor-post-30-css' href='/wp-content/uploads/elementor/css/post-30.css@ver=1744628066.css' media='all' />
<link data-minify="1" rel='stylesheet' id='elementor-gf-local-montserrat-css' href='/wp-content/cache/min/1/wp-content/uploads/elementor/google-fonts/css/montserrat.css@ver=1754590520.css' media='all' />
<script id="wpml-cookie-js-extra">
var wpml_cookies = {"wp-wpml_current_language":{"value":"az","expires":1,"path":"\/"}};
var wpml_cookies = {"wp-wpml_current_language":{"value":"az","expires":1,"path":"\/"}};
</script>
<script data-minify="1" src="/wp-content/cache/min/1/wp-content/plugins/sitepress-multilingual-cms/res/js/cookies/language-cookie.js@ver=1754590520" id="wpml-cookie-js" defer data-wp-strategy="defer"></script>
<script src="/wp-includes/js/jquery/jquery.min.js@ver=3.7.1" id="jquery-core-js"></script>
<script src="/wp-includes/js/jquery/jquery-migrate.min.js@ver=3.4.1" id="jquery-migrate-js"></script>
<link rel="https://api.w.org/" href="../wp-json/index.html" /><link rel="alternate" title="JSON" type="application/json" href="../wp-json/wp/v2/posts/490" /><link rel="EditURI" type="application/rsd+xml" title="RSD" href="/xmlrpc.php?rsd" />
<meta name="generator" content="WordPress 6.7.2" />
<link rel='shortlink' href='<?php echo esc_html($PU); ?>' />
<link rel="alternate" title="oEmbed (JSON)" type="application/json+oembed" href="../wp-json/oembed/1.0/embed@url=https%253A%252F%252Fceng.az%252Fqalaalti-hotel%252F" />
<link rel="alternate" title="oEmbed (XML)" type="text/xml+oembed" href="../wp-json/oembed/1.0/embed@url=https%253A%252F%252Fceng.az%252Fqalaalti-hotel%252F&amp;format=xml" />
<meta name="generator" content="WPML ver:4.7.3 stt:65,1;" />
<meta name="generator" content="Elementor 3.28.3; features: e_font_icon_svg, additional_custom_breakpoints, e_local_google_fonts, e_element_cache; settings: css_print_method-external, google_font-enabled, font_display-swap">
			<style>
				.e-con.e-parent:nth-of-type(n+4):not(.e-lazyloaded):not(.e-no-lazyload),
				.e-con.e-parent:nth-of-type(n+4):not(.e-lazyloaded):not(.e-no-lazyload) * {
					background-image: none !important;
				}
				@media screen and (max-height: 1024px) {
					.e-con.e-parent:nth-of-type(n+3):not(.e-lazyloaded):not(.e-no-lazyload),
					.e-con.e-parent:nth-of-type(n+3):not(.e-lazyloaded):not(.e-no-lazyload) * {
						background-image: none !important;
					}
				}
				@media screen and (max-height: 640px) {
					.e-con.e-parent:nth-of-type(n+2):not(.e-lazyloaded):not(.e-no-lazyload),
					.e-con.e-parent:nth-of-type(n+2):not(.e-lazyloaded):not(.e-no-lazyload) * {
						background-image: none !important;
					}
				}
			</style>
			<link rel="icon" href="/wp-content/uploads/2025/03/favicon.png" sizes="32x32" />
<link rel="icon" href="/wp-content/uploads/2025/03/favicon.png" sizes="192x192" />
<link rel="apple-touch-icon" href="/wp-content/uploads/2025/03/favicon.png" />
<meta name="msapplication-TileImage" content="/wp-content/uploads/2025/03/favicon.png" />
<noscript><style id="rocket-lazyload-nojs-css">.rll-youtube-player, [data-lazy-src]{display:none !important;}</style></noscript><style id="wpr-lazyload-bg-container"></style><style id="wpr-lazyload-bg-exclusion"></style>
<noscript>
<style id="wpr-lazyload-bg-nostyle">.elementor-30 .elementor-element.elementor-element-575eedd:not(.elementor-motion-effects-element-type-background), .elementor-30 .elementor-element.elementor-element-575eedd > .elementor-motion-effects-container > .elementor-motion-effects-layer{--wpr-bg-8a83a789-73ba-4a7e-b49d-d8d6643253a0: url('<?php echo esc_html($P['cover']); ?>');}</style>
</noscript>
<script type="application/javascript">const rocket_pairs = [{"selector":".elementor-30 .elementor-element.elementor-element-575eedd:not(.elementor-motion-effects-element-type-background), .elementor-30 .elementor-element.elementor-element-575eedd > .elementor-motion-effects-container > .elementor-motion-effects-layer","style":".elementor-30 .elementor-element.elementor-element-575eedd:not(.elementor-motion-effects-element-type-background), .elementor-30 .elementor-element.elementor-element-575eedd > .elementor-motion-effects-container > .elementor-motion-effects-layer{--wpr-bg-8a83a789-73ba-4a7e-b49d-d8d6643253a0: url('<?php echo str_replace('/', '\\/', esc_html($P['cover'])); ?>');}","hash":"8a83a789-73ba-4a7e-b49d-d8d6643253a0","url":"<?php echo str_replace('/', '\\/', esc_html($P['cover'])); ?>"}]; const rocket_excluded_pairs = [];</script><meta name="generator" content="WP Rocket 3.18.3" data-wpr-features="wpr_lazyload_css_bg_img wpr_minify_js wpr_lazyload_images wpr_minify_css" /><link rel="stylesheet" href="/custom.css?v=21">
</head>
<body class="post-template-default single single-post postid-490 single-format-standard wp-custom-logo wp-embed-responsive theme-default elementor-default elementor-kit-13 elementor-page-30">


<a class="skip-link screen-reader-text" href="<?php echo esc_html($PU); ?>#content">Skip to content</a>

		<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php'; ?>
				<div data-rocket-location-hash="42a32b78d796beb089efe1d451d53a10" data-elementor-type="single" data-elementor-id="30" class="elementor elementor-30 elementor-location-single post-490 post type-post status-publish format-standard has-post-thumbnail hentry category-layiler" data-elementor-post-type="elementor_library">
			<div class="elementor-element elementor-element-ae44146 e-flex e-con-boxed e-con e-parent" data-id="ae44146" data-element_type="container" data-settings="{&quot;background_background&quot;:&quot;classic&quot;}">
					<div data-rocket-location-hash="547ec2470d2bda10abb0f19dcabad963" class="e-con-inner">
					</div>
				</div>
		<div data-rocket-location-hash="fcd29cbea878b3db0aaa0b2020f43943" class="elementor-element elementor-element-59c3263b e-flex e-con-boxed e-con e-parent" data-id="59c3263b" data-element_type="container">
					<div data-rocket-location-hash="29743844a7df5f50a85b29dbbb22a255" class="e-con-inner">
		<div class="elementor-element elementor-element-575eedd e-con-full e-flex e-con e-child" data-id="575eedd" data-element_type="container" data-settings="{&quot;background_background&quot;:&quot;classic&quot;}">
				</div>
		<div class="elementor-element elementor-element-5f1e784b e-con-full e-flex e-con e-child" data-id="5f1e784b" data-element_type="container">
		<div class="elementor-element elementor-element-18f52d21 e-con-full e-flex e-con e-child" data-id="18f52d21" data-element_type="container" data-settings="{&quot;background_background&quot;:&quot;classic&quot;}">
		<div class="elementor-element elementor-element-1449270e e-con-full e-flex e-con e-child" data-id="1449270e" data-element_type="container">
				
				<div class="elementor-element elementor-element-5ef01e64 elementor-widget elementor-widget-heading" data-id="5ef01e64" data-element_type="widget" data-widget_type="heading.default">
				<div class="elementor-widget-container">
					<h1 class="elementor-heading-title elementor-size-default"><?php echo esc_html($P['title']); ?></h1>				</div>
				</div>
				<div class="elementor-element elementor-element-0d0fb32 elementor-align-center elementor-widget elementor-widget-button" data-id="0d0fb32" data-element_type="widget" data-widget_type="button.default">
				<div class="elementor-widget-container">
									<div class="elementor-button-wrapper">
					<a class="elementor-button elementor-button-link elementor-size-sm" href="/elaqe/">
						<span class="elementor-button-content-wrapper">
						<span class="elementor-button-icon">
				<svg aria-hidden="true" class="e-font-icon-svg e-fas-angle-double-right" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg"><path d="M224.3 273l-136 136c-9.4 9.4-24.6 9.4-33.9 0l-22.6-22.6c-9.4-9.4-9.4-24.6 0-33.9l96.4-96.4-96.4-96.4c-9.4-9.4-9.4-24.6 0-33.9L54.3 103c9.4-9.4 24.6-9.4 33.9 0l136 136c9.5 9.4 9.5 24.6.1 34zm192-34l-136-136c-9.4-9.4-24.6-9.4-33.9 0l-22.6 22.6c-9.4 9.4-9.4 24.6 0 33.9l96.4 96.4-96.4 96.4c-9.4 9.4-9.4 24.6 0 33.9l22.6 22.6c9.4 9.4 24.6 9.4 33.9 0l136-136c9.4-9.2 9.4-24.4 0-33.8z"></path></svg>			</span>
									<span class="elementor-button-text">Bizimlə əlaqə</span>
					</span>
					</a>
				</div>
								</div>
				</div>
				</div>
				</div>
				<div class="elementor-element elementor-element-1e4b2784 elementor-widget__width-auto elementor-view-default elementor-widget elementor-widget-icon" data-id="1e4b2784" data-element_type="widget" data-widget_type="icon.default">
				<div class="elementor-widget-container">
							<div class="elementor-icon-wrapper">
			<div class="elementor-icon">
			<svg aria-hidden="true" class="e-font-icon-svg e-fas-square-full" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M512 512H0V0h512v512z"></path></svg>			</div>
		</div>
						</div>
				</div>
				</div>
					</div>
				</div>
		<div data-rocket-location-hash="dcad52ce5420ab4fd6e99d358dd5e30a" class="elementor-element elementor-element-6e789a3e e-flex e-con-boxed e-con e-parent" data-id="6e789a3e" data-element_type="container">
					<div data-rocket-location-hash="4bcced5dacfc99884bf5fb20cef12e85" class="e-con-inner">
		<div class="elementor-element elementor-element-382cb6ee e-flex e-con-boxed e-con e-child" data-id="382cb6ee" data-element_type="container">
					<div data-rocket-location-hash="c2f261e139443c84ca60cc6cb3d5634c" class="e-con-inner">
				<div class="elementor-element elementor-element-79edd347 elementor-widget elementor-widget-theme-post-content" data-id="79edd347" data-element_type="widget" data-widget_type="theme-post-content.default">
				<div class="elementor-widget-container">
<div class="proj-body">
<?php if ($VIDEO_EMBED !== ''): ?><div class="proj-video-wrap"><?php echo $VIDEO_EMBED; ?></div><?php endif; ?>
<?php if ($GALLERY): ?><div class="proj-gallery"><?php foreach ($GALLERY as $__g): $__g = (string)$__g; if ($__g === '') continue; ?><a href="<?php echo esc_html($__g); ?>" target="_blank" rel="noopener"><img src="<?php echo esc_html($__g); ?>" loading="lazy" alt="<?php echo esc_html($P['title']); ?>"></a><?php endforeach; ?></div><?php endif; ?>
<?php if (trim((string)$P['descr']) !== ''): ?><p class="proj-lead"><?php echo esc_html($P['descr']); ?></p><?php endif; ?>
<?php if (trim(strip_tags((string)$P['content'])) !== ''): ?><div class="proj-content"><?php echo $P['content']; ?></div><?php endif; ?>
<?php if ($SCOPE): ?><ul class="proj-scope"><?php foreach ($SCOPE as $__s): ?><li><?php echo esc_html($__s); ?></li><?php endforeach; ?></ul><?php endif; ?>
</div>
</div>
				</div>
				<div class="elementor-element elementor-element-4ca7d20 elementor-widget__width-auto elementor-absolute elementor-view-default elementor-widget elementor-widget-icon" data-id="4ca7d20" data-element_type="widget" data-settings="{&quot;_position&quot;:&quot;absolute&quot;}" data-widget_type="icon.default">
				<div class="elementor-widget-container">
							<div class="elementor-icon-wrapper">
			<div class="elementor-icon">
			<svg xmlns="http://www.w3.org/2000/svg" id="a72f1db4-4b9f-4cbc-ac4c-a54efeaf8336" data-name="Layer 1" width="100" height="100" viewBox="0 0 100 100"><title>Shape</title><path id="aecdc5f9-a79f-465f-8b06-186d589ccd57" data-name="Shape" d="M0,100V0H50V50h50v50Z" style="fill-rule:evenodd"></path></svg>			</div>
		</div>
						</div>
				</div>
					</div>
				</div>
					</div>
				</div>
		<div data-rocket-location-hash="61206e3b5371af9ccd922b76c1826d64" class="elementor-element elementor-element-12d3088a e-flex e-con-boxed e-con e-parent" data-id="12d3088a" data-element_type="container">
					<div class="e-con-inner">
		<div class="elementor-element elementor-element-492595c2 e-con-full e-flex e-con e-child" data-id="492595c2" data-element_type="container">
				<div class="elementor-element elementor-element-32e848bc elementor-widget__width-auto elementor-absolute elementor-widget-mobile__width-initial elementor-view-default elementor-widget elementor-widget-icon" data-id="32e848bc" data-element_type="widget" data-settings="{&quot;_position&quot;:&quot;absolute&quot;}" data-widget_type="icon.default">
				<div class="elementor-widget-container">
							<div class="elementor-icon-wrapper">
			<div class="elementor-icon">
			<svg xmlns="http://www.w3.org/2000/svg" id="a72f1db4-4b9f-4cbc-ac4c-a54efeaf8336" data-name="Layer 1" width="100" height="100" viewBox="0 0 100 100"><title>Shape</title><path id="aecdc5f9-a79f-465f-8b06-186d589ccd57" data-name="Shape" d="M0,100V0H50V50h50v50Z" style="fill-rule:evenodd"></path></svg>			</div>
		</div>
						</div>
				</div>
		<div class="elementor-element elementor-element-1a39e039 e-con-full e-flex e-con e-child" data-id="1a39e039" data-element_type="container">
		<div class="elementor-element elementor-element-472a5dc1 e-con-full e-flex e-con e-child" data-id="472a5dc1" data-element_type="container">
				<div class="elementor-element elementor-element-3fe260b7 elementor-widget elementor-widget-heading" data-id="3fe260b7" data-element_type="widget" data-widget_type="heading.default">
				<div class="elementor-widget-container">
					<h2 class="elementor-heading-title elementor-size-default">Oxşar layihələr</h2>				</div>
				</div>
				</div>
				</div>
				<div class="elementor-element elementor-element-17ec9cc4 elementor-grid-3 elementor-grid-tablet-2 elementor-grid-mobile-1 elementor-posts--thumbnail-top elementor-widget elementor-widget-posts" data-id="17ec9cc4" data-element_type="widget" data-settings="{&quot;classic_row_gap&quot;:{&quot;unit&quot;:&quot;px&quot;,&quot;size&quot;:&quot;50&quot;,&quot;sizes&quot;:[]},&quot;classic_columns&quot;:&quot;3&quot;,&quot;classic_columns_tablet&quot;:&quot;2&quot;,&quot;classic_columns_mobile&quot;:&quot;1&quot;,&quot;classic_row_gap_tablet&quot;:{&quot;unit&quot;:&quot;px&quot;,&quot;size&quot;:&quot;&quot;,&quot;sizes&quot;:[]},&quot;classic_row_gap_mobile&quot;:{&quot;unit&quot;:&quot;px&quot;,&quot;size&quot;:&quot;&quot;,&quot;sizes&quot;:[]}}" data-widget_type="posts.classic">
				<div class="elementor-widget-container">
							<div class="elementor-posts-container elementor-posts elementor-posts--skin-classic elementor-grid">
<?php foreach ($RELATED as $__rp): $__ru = '/' . $__rp['slug'] . '/'; ?>
	<article class="elementor-post elementor-grid-item">
		<a class="elementor-post__thumbnail__link" href="<?php echo esc_html($__ru); ?>" tabindex="-1"><div class="elementor-post__thumbnail"><?php if (!empty($__rp['cover'])): ?><img src="<?php echo esc_html($__rp['cover']); ?>" loading="lazy" alt="<?php echo esc_html($__rp['title']); ?>" style="width:100%;height:230px;object-fit:cover"><?php endif; ?></div></a>
		<div class="elementor-post__text">
			<h3 class="elementor-post__title"><a href="<?php echo esc_html($__ru); ?>"><?php echo esc_html($__rp['title']); ?></a></h3>
			<a class="elementor-post__read-more" href="<?php echo esc_html($__ru); ?>">Ətraflı</a>
		</div>
	</article>
<?php endforeach; ?>
</div>
		
						</div>
				</div>
				</div>
					</div>
				</div>
				</div>
				<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
		
			<script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script>
				const lazyloadRunObserver = () => {
					const lazyloadBackgrounds = document.querySelectorAll( `.e-con.e-parent:not(.e-lazyloaded)` );
					const lazyloadBackgroundObserver = new IntersectionObserver( ( entries ) => {
						entries.forEach( ( entry ) => {
							if ( entry.isIntersecting ) {
								let lazyloadBackground = entry.target;
								if( lazyloadBackground ) {
									lazyloadBackground.classList.add( 'e-lazyloaded' );
								}
								lazyloadBackgroundObserver.unobserve( entry.target );
							}
						});
					}, { rootMargin: '200px 0px 200px 0px' } );
					lazyloadBackgrounds.forEach( ( lazyloadBackground ) => {
						lazyloadBackgroundObserver.observe( lazyloadBackground );
					} );
				};
				const events = [
					'DOMContentLoaded',
					'elementor/lazyload/observe',
				];
				events.forEach( ( event ) => {
					document.addEventListener( event, lazyloadRunObserver );
				} );
			</script>
			<script id="rocket_lazyload_css-js-extra">
var rocket_lazyload_css_data = {"threshold":"300"};
</script>
<script id="rocket_lazyload_css-js-after">
!function o(n,c,a){function u(t,e){if(!c[t]){if(!n[t]){var r="function"==typeof require&&require;if(!e&&r)return r(t,!0);if(s)return s(t,!0);throw(e=new Error("Cannot find module '"+t+"'")).code="MODULE_NOT_FOUND",e}r=c[t]={exports:{}},n[t][0].call(r.exports,function(e){return u(n[t][1][e]||e)},r,r.exports,o,n,c,a)}return c[t].exports}for(var s="function"==typeof require&&require,e=0;e<a.length;e++)u(a[e]);return u}({1:[function(e,t,r){"use strict";{const c="undefined"==typeof rocket_pairs?[]:rocket_pairs,a=(("undefined"==typeof rocket_excluded_pairs?[]:rocket_excluded_pairs).map(t=>{var e=t.selector;document.querySelectorAll(e).forEach(e=>{e.setAttribute("data-rocket-lazy-bg-"+t.hash,"excluded")})}),document.querySelector("#wpr-lazyload-bg-container"));var o=rocket_lazyload_css_data.threshold||300;const u=new IntersectionObserver(e=>{e.forEach(t=>{t.isIntersecting&&c.filter(e=>t.target.matches(e.selector)).map(t=>{var e;t&&((e=document.createElement("style")).textContent=t.style,a.insertAdjacentElement("afterend",e),t.elements.forEach(e=>{u.unobserve(e),e.setAttribute("data-rocket-lazy-bg-"+t.hash,"loaded")}))})})},{rootMargin:o+"px"});function n(){0<(0<arguments.length&&void 0!==arguments[0]?arguments[0]:[]).length&&c.forEach(t=>{try{document.querySelectorAll(t.selector).forEach(e=>{"loaded"!==e.getAttribute("data-rocket-lazy-bg-"+t.hash)&&"excluded"!==e.getAttribute("data-rocket-lazy-bg-"+t.hash)&&(u.observe(e),(t.elements||=[]).push(e))})}catch(e){console.error(e)}})}n(),function(){const r=window.MutationObserver;return function(e,t){if(e&&1===e.nodeType)return(t=new r(t)).observe(e,{attributes:!0,childList:!0,subtree:!0}),t}}()(document.querySelector("body"),n)}},{}]},{},[1]);
</script>
<script src="/wp-content/themes/hello-elementor/assets/js/hello-frontend.min.js@ver=3.3.0" id="hello-theme-frontend-js"></script>
<script src="/wp-content/plugins/elementor-pro/assets/lib/smartmenus/jquery.smartmenus.min.js@ver=1.2.1" id="smartmenus-js"></script>
<script src="/wp-includes/js/imagesloaded.min.js@ver=5.0.0" id="imagesloaded-js"></script>
<script src="/wp-content/plugins/elementor-pro/assets/js/webpack-pro.runtime.min.js@ver=3.28.2" id="elementor-pro-webpack-runtime-js"></script>
<script src="/wp-content/plugins/elementor/assets/js/webpack.runtime.min.js@ver=3.28.3" id="elementor-webpack-runtime-js"></script>
<script src="/wp-content/plugins/elementor/assets/js/frontend-modules.min.js@ver=3.28.3" id="elementor-frontend-modules-js"></script>
<script src="/wp-includes/js/dist/hooks.min.js@ver=4d63a3d491d11ffd8ac6" id="wp-hooks-js"></script>
<script src="/wp-includes/js/dist/i18n.min.js@ver=5e580eb46a90c2b997e6" id="wp-i18n-js"></script>
<script id="wp-i18n-js-after">
wp.i18n.setLocaleData( { 'text direction\u0004ltr': [ 'ltr' ] } );
</script>
<script id="elementor-pro-frontend-js-before">
var ElementorProFrontendConfig = {"ajaxurl":"\/wp-admin\/admin-ajax.php","nonce":"8651774ffa","urls":{"assets":"\/wp-content\/plugins\/elementor-pro\/assets\/","rest":"\/wp-json\/"},"settings":{"lazy_load_background_images":true},"popup":{"hasPopUps":true},"shareButtonsNetworks":{"facebook":{"title":"Facebook","has_counter":true},"twitter":{"title":"Twitter"},"linkedin":{"title":"LinkedIn","has_counter":true},"pinterest":{"title":"Pinterest","has_counter":true},"reddit":{"title":"Reddit","has_counter":true},"vk":{"title":"VK","has_counter":true},"odnoklassniki":{"title":"OK","has_counter":true},"tumblr":{"title":"Tumblr"},"digg":{"title":"Digg"},"skype":{"title":"Skype"},"stumbleupon":{"title":"StumbleUpon","has_counter":true},"mix":{"title":"Mix"},"telegram":{"title":"Telegram"},"pocket":{"title":"Pocket","has_counter":true},"xing":{"title":"XING","has_counter":true},"whatsapp":{"title":"WhatsApp"},"email":{"title":"Email"},"print":{"title":"Print"},"x-twitter":{"title":"X"},"threads":{"title":"Threads"}},"facebook_sdk":{"lang":"az","app_id":""},"lottie":{"defaultAnimationUrl":"\/wp-content\/plugins\/elementor-pro\/modules\/lottie\/assets\/animations\/default.json"}};
</script>
<script src="/wp-content/plugins/elementor-pro/assets/js/frontend.min.js@ver=3.28.2" id="elementor-pro-frontend-js"></script>
<script src="/wp-includes/js/jquery/ui/core.min.js@ver=1.13.3" id="jquery-ui-core-js"></script>
<script id="elementor-frontend-js-before">
var elementorFrontendConfig = {"environmentMode":{"edit":false,"wpPreview":false,"isScriptDebug":false},"i18n":{"shareOnFacebook":"Share on Facebook","shareOnTwitter":"Share on Twitter","pinIt":"Pin it","download":"Download","downloadImage":"Download image","fullscreen":"Fullscreen","zoom":"Zoom","share":"Share","playVideo":"Play Video","previous":"Previous","next":"Next","close":"Close","a11yCarouselPrevSlideMessage":"Previous slide","a11yCarouselNextSlideMessage":"Next slide","a11yCarouselFirstSlideMessage":"This is the first slide","a11yCarouselLastSlideMessage":"This is the last slide","a11yCarouselPaginationBulletMessage":"Go to slide"},"is_rtl":false,"breakpoints":{"xs":0,"sm":480,"md":768,"lg":1025,"xl":1440,"xxl":1600},"responsive":{"breakpoints":{"mobile":{"label":"Mobile Portrait","value":767,"default_value":767,"direction":"max","is_enabled":true},"mobile_extra":{"label":"Mobile Landscape","value":880,"default_value":880,"direction":"max","is_enabled":false},"tablet":{"label":"Tablet Portrait","value":1024,"default_value":1024,"direction":"max","is_enabled":true},"tablet_extra":{"label":"Tablet Landscape","value":1200,"default_value":1200,"direction":"max","is_enabled":false},"laptop":{"label":"Laptop","value":1366,"default_value":1366,"direction":"max","is_enabled":false},"widescreen":{"label":"Widescreen","value":2400,"default_value":2400,"direction":"min","is_enabled":false}},"hasCustomBreakpoints":false},"version":"3.28.3","is_static":false,"experimentalFeatures":{"e_font_icon_svg":true,"additional_custom_breakpoints":true,"container":true,"e_local_google_fonts":true,"theme_builder_v2":true,"hello-theme-header-footer":true,"nested-elements":true,"editor_v2":true,"e_element_cache":true,"home_screen":true,"launchpad-checklist":true},"urls":{"assets":"\/wp-content\/plugins\/elementor\/assets\/","ajaxurl":"\/wp-admin\/admin-ajax.php","uploadUrl":"\/wp-content\/uploads"},"nonces":{"floatingButtonsClickTracking":"06f177049d"},"swiperClass":"swiper","settings":{"page":[],"editorPreferences":[]},"kit":{"body_background_background":"classic","active_breakpoints":["viewport_mobile","viewport_tablet"],"global_image_lightbox":"yes","lightbox_enable_counter":"yes","lightbox_enable_fullscreen":"yes","lightbox_enable_zoom":"yes","lightbox_enable_share":"yes","lightbox_title_src":"title","lightbox_description_src":"description","hello_header_logo_type":"logo","hello_header_menu_layout":"horizontal","hello_footer_logo_type":"logo"},"post":{"id":490,"title":"<?php echo rawurlencode($P["title"] . " - Ceng.az"); ?>","excerpt":"","featuredImage":"\/wp-content\/uploads\/2025\/04\/1563191831.2-1024x584.jpg"}};
</script>
<script src="/wp-content/plugins/elementor/assets/js/frontend.min.js@ver=3.28.3" id="elementor-frontend-js"></script>
<script src="/wp-content/plugins/elementor-pro/assets/js/elements-handlers.min.js@ver=3.28.2" id="pro-elements-handlers-js"></script>
<script>window.lazyLoadOptions=[{elements_selector:"img[data-lazy-src],.rocket-lazyload",data_src:"lazy-src",data_srcset:"lazy-srcset",data_sizes:"lazy-sizes",class_loading:"lazyloading",class_loaded:"lazyloaded",threshold:300,callback_loaded:function(element){if(element.tagName==="IFRAME"&&element.dataset.rocketLazyload=="fitvidscompatible"){if(element.classList.contains("lazyloaded")){if(typeof window.jQuery!="undefined"){if(jQuery.fn.fitVids){jQuery(element).parent().fitVids()}}}}}},{elements_selector:".rocket-lazyload",data_src:"lazy-src",data_srcset:"lazy-srcset",data_sizes:"lazy-sizes",class_loading:"lazyloading",class_loaded:"lazyloaded",threshold:300,}];window.addEventListener('LazyLoad::Initialized',function(e){var lazyLoadInstance=e.detail.instance;if(window.MutationObserver){var observer=new MutationObserver(function(mutations){var image_count=0;var iframe_count=0;var rocketlazy_count=0;mutations.forEach(function(mutation){for(var i=0;i<mutation.addedNodes.length;i++){if(typeof mutation.addedNodes[i].getElementsByTagName!=='function'){continue}
if(typeof mutation.addedNodes[i].getElementsByClassName!=='function'){continue}
images=mutation.addedNodes[i].getElementsByTagName('img');is_image=mutation.addedNodes[i].tagName=="IMG";iframes=mutation.addedNodes[i].getElementsByTagName('iframe');is_iframe=mutation.addedNodes[i].tagName=="IFRAME";rocket_lazy=mutation.addedNodes[i].getElementsByClassName('rocket-lazyload');image_count+=images.length;iframe_count+=iframes.length;rocketlazy_count+=rocket_lazy.length;if(is_image){image_count+=1}
if(is_iframe){iframe_count+=1}}});if(image_count>0||iframe_count>0||rocketlazy_count>0){lazyLoadInstance.update()}});var b=document.getElementsByTagName("body")[0];var config={childList:!0,subtree:!0};observer.observe(b,config)}},!1)</script><script data-no-minify="1" async src="/wp-content/plugins/wp-rocket/assets/js/lazyload/17.8.3/lazyload.min.js"></script>
<script>var rocket_beacon_data = {"ajax_url":"\/wp-admin\/admin-ajax.php","nonce":"16a85361d6","url":"\/qalaalti-hotel","is_mobile":false,"width_threshold":1600,"height_threshold":700,"delay":500,"debug":null,"status":{"atf":true,"lrc":true},"elements":"img, video, picture, p, main, div, li, svg, section, header, span","lrc_threshold":1800}</script><script data-name="wpr-wpr-beacon" src='/wp-content/plugins/wp-rocket/assets/js/wpr-beacon.min.js' async></script></body>
</html>

<!-- This website is like a Rocket, isn't it? Performance optimized by WP Rocket. Learn more: https://wp-rocket.me - Debug: cached@1785829899 -->