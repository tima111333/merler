/**
 * Сборка HTML-прототипа меню «Мерлер» из data/menu-data.json.
 * Запуск: node tools/build-prototype.js
 */
const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..');
const data = JSON.parse(fs.readFileSync(path.join(ROOT, 'data', 'menu-data.json'), 'utf8'));

const esc = (s) => String(s == null ? '' : s)
  .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

const price = (n) => String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽';

const BADGES = {
  chef: 'От шефа',
  spicy: 'Острое',
  hit: 'Хит',
  new: 'Новинка',
  veg: 'Вегетарианское'
};

const STATUS_LABEL = {
  soon: 'Скоро',
  out_of_stock: 'Нет в наличии'
};

/* Демо-статусы — только для прототипа, чтобы показать клиенту внешний вид. */
const DEMO = {
  'kare-yagnenka-ot-shefa': { status: 'soon' },
  'dorado-na-grile': { status: 'out_of_stock' },
  'hinkal-avarskiy': { badges: ['hit'] },
  'sup-piti': { badges: ['hit'] },
  'chudu-tonkoe-s-tykvoy': { badges: ['veg'] },
  'kurica-po-meksikanski': { badges: ['spicy'] },
  'funchoza-s-kuricey': { badges: ['spicy', 'new'] },
  'so-strachatelloy-i-tomatom': { badges: ['veg'] }
};

const ORNAMENT = '<span class="orn" aria-hidden="true"></span>';

const LOGO = '<img class="logo" src="../assets/img/logo.webp" width="354" height="240" alt="Мерлер — бутик-отель">';
const LOGO_MARK = '<img class="mark" src="../assets/img/logo-mark.webp" width="140" height="104" alt="" aria-hidden="true">';

const PLACEHOLDER = '<span class="ph" aria-hidden="true"><img src="../assets/img/logo-mark.webp" width="140" height="104" alt=""></span>';

const BUILDING = '<img class="building" src="../assets/img/building.webp" width="707" height="670" alt="Акварельная иллюстрация здания бутик-отеля «Мерлер»" loading="lazy" decoding="async">';

function dishCard(d) {
  const demo = DEMO[d.slug] || {};
  const badges = (d.badges && d.badges.length ? d.badges : []).slice();
  const demoBadges = (demo.badges || []).filter((b) => !badges.includes(b));
  const status = d.status;
  const demoStatus = demo.status || '';

  const badgeHtml = badges.map((b) => '<span class="badge b-' + b + '">' + esc(BADGES[b] || b) + '</span>').join('')
    + demoBadges.map((b) => '<span class="badge b-' + b + ' demo-only">' + esc(BADGES[b] || b) + '</span>').join('');

  const statusHtml = demoStatus
    ? '<span class="status-flag demo-only">' + esc(STATUS_LABEL[demoStatus]) + '</span>'
    : (STATUS_LABEL[status] ? '<span class="status-flag">' + esc(STATUS_LABEL[status]) + '</span>' : '');

  return '<article class="card no-photo" data-slug="' + esc(d.slug) + '"'
    + (demoStatus ? ' data-demo-status="' + esc(demoStatus) + '"' : '')
    + ' data-status="' + esc(status) + '"'
    + ' data-title="' + esc(d.title) + '"'
    + ' data-weight="' + esc(d.weight) + '"'
    + ' data-price="' + esc(price(d.price)) + '"'
    + ' data-desc="' + esc(d.description) + '"'
    + ' data-search="' + esc((d.title + ' ' + d.description).toLowerCase()) + '"'
    + ' tabindex="0" role="button" aria-label="' + esc(d.title) + ', ' + esc(price(d.price)) + '">'
    + '<div class="card-media">' + PLACEHOLDER + statusHtml + '</div>'
    + '<div class="card-body">'
    + (badgeHtml ? '<div class="badges">' + badgeHtml + '</div>' : '')
    + '<h3 class="card-title">' + esc(d.title) + '</h3>'
    + (d.description ? '<p class="card-desc">' + esc(d.description) + '</p>' : '')
    + '<div class="card-foot">'
    + '<span class="card-price">' + price(d.price) + '</span>'
    + (d.weight ? '<span class="card-weight">' + esc(d.weight) + '</span>' : '')
    + '</div>'
    + '</div>'
    + '</article>';
}

function dishRow(d) {
  return '<div class="row" data-search="' + esc((d.title + ' ' + d.description).toLowerCase()) + '">'
    + '<span class="row-name">' + esc(d.title) + '</span>'
    + '<span class="row-dots"></span>'
    + (d.weight ? '<span class="row-weight">' + esc(d.weight) + '</span>' : '')
    + '<span class="row-price">' + price(d.price) + '</span>'
    + '</div>';
}

function dishes(list) {
  if (!list.length) return '';
  return '<div class="grid">' + list.map(dishCard).join('') + '</div>'
    + '<div class="list">' + list.map(dishRow).join('') + '</div>';
}

function subsection(sub) {
  return '<div class="subsection" id="sub-' + esc(sub.slug) + '">'
    + '<h3 class="subsection-title">' + esc(sub.name)
    + (sub.description ? ' <span class="subsection-note">' + esc(sub.description) + '</span>' : '')
    + '</h3>'
    + dishes(sub.dishes)
    + '</div>';
}

function section(s) {
  return '<section class="section" id="sec-' + esc(s.slug) + '" aria-labelledby="h-' + esc(s.slug) + '">'
    + '<h2 class="section-title" id="h-' + esc(s.slug) + '"><span>' + esc(s.name) + '</span>' + ORNAMENT + '</h2>'
    + (s.description ? '<p class="section-note">' + esc(s.description) + '</p>' : '')
    + dishes(s.dishes)
    + (s.subsections || []).map(subsection).join('')
    + '</section>';
}

const sections = data.sections.slice().sort((a, b) => a.order - b.order);

const navHtml = sections.map((s, i) =>
  '<a class="chip' + (i === 0 ? ' is-active' : '') + '" href="#sec-' + esc(s.slug) + '">' + esc(s.name) + '</a>'
).join('');

const total = sections.reduce((n, s) =>
  n + s.dishes.length + (s.subsections || []).reduce((m, x) => m + x.dishes.length, 0), 0);

const CSS = `
:root{
  --bg:#FFF9EB; --card:#FFFFFF; --line:#EFE2C8; --text:#14120E;
  --accent:#7A4514; --muted:#605A54; --ornament:#B4A28A;
  --ochre:#D9A64A; --gold:#EBC77F; --smoke:#BFD0DA;
  --radius:14px; --shadow:0 2px 10px rgba(122,69,20,.07);
  --font-head:'Montserrat Alternates','Segoe UI',sans-serif;
  --font-body:'Inter','Segoe UI',sans-serif;
}
html[data-font="2"]{ --font-head:'Comfortaa','Segoe UI',sans-serif; --font-body:'Manrope','Segoe UI',sans-serif; }
*{box-sizing:border-box}
html{scroll-behavior:smooth; scroll-padding-top:76px}
body{margin:0;background:var(--bg);color:var(--text);font-family:var(--font-body);font-size:15px;line-height:1.45;
  -webkit-font-smoothing:antialiased}
body::before{content:"";position:fixed;inset:0;pointer-events:none;z-index:0;
  background:
    radial-gradient(60% 40% at 12% 4%, rgba(191,208,218,.20), transparent 70%),
    radial-gradient(50% 35% at 92% 22%, rgba(235,199,127,.22), transparent 70%),
    radial-gradient(45% 30% at 50% 98%, rgba(217,166,74,.14), transparent 70%);}
.wrap{position:relative;z-index:1;max-width:1200px;margin:0 auto;padding:0 16px}
a{color:inherit}

/* ── Первый экран ───────────────────────────── */
.hero{position:relative;display:grid;grid-template-columns:1fr 300px;gap:16px;align-items:end;
  padding:16px 0 10px;max-height:58vh}
.hero .building{max-height:210px;width:auto;justify-self:end}
.hero-logo .logo{width:176px;height:auto;display:block}
.hero-kicker{margin:10px 0 0;font-family:var(--font-head);font-size:17px;color:var(--accent);font-weight:600}
.hero-greet{grid-column:1/2;margin:2px 0 0;color:var(--muted);font-size:14px;max-width:52ch}
.building{width:100%;height:auto;display:block}
.about .building{max-width:260px;margin:0 auto}

/* ── Лента категорий ───────────────────────── */
.navbar{position:sticky;top:0;z-index:30;background:rgba(255,249,235,.94);backdrop-filter:blur(8px);
  border-bottom:1px solid var(--line)}
.navbar-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;gap:8px;padding:8px 16px}
.chips{display:flex;gap:8px;overflow-x:auto;scrollbar-width:none;-webkit-overflow-scrolling:touch;scroll-behavior:smooth;flex:1;
  padding-right:10px;-webkit-mask-image:linear-gradient(90deg,#000 88%,transparent);mask-image:linear-gradient(90deg,#000 88%,transparent)}
.chips::-webkit-scrollbar{display:none}
.chip{flex:0 0 auto;padding:7px 14px;border-radius:999px;border:1px solid var(--line);background:var(--card);
  color:var(--muted);text-decoration:none;font-size:14px;white-space:nowrap;transition:.18s}
.chip.is-active{background:var(--accent);border-color:var(--accent);color:#FFFDF7;font-weight:600}
.icon-btn{flex:0 0 auto;width:36px;height:36px;border-radius:50%;border:1px solid var(--line);background:var(--card);
  color:var(--accent);display:grid;place-items:center;cursor:pointer}
.icon-btn svg{width:18px;height:18px}
.searchbar{display:none;padding:0 16px 10px;max-width:1200px;margin:0 auto}
.searchbar.is-open{display:block}
.searchbar input{width:100%;padding:10px 14px;border-radius:999px;border:1px solid var(--line);
  background:var(--card);font:inherit;color:var(--text)}
.searchbar input:focus{outline:2px solid var(--gold);outline-offset:1px}
.no-results{display:none;padding:24px 0;color:var(--muted);text-align:center}

/* ── Разделы ───────────────────────────────── */
.section{padding:22px 0 6px;scroll-margin-top:72px}
.section-title{display:flex;align-items:center;gap:10px;margin:0 0 12px;font-family:var(--font-head);
  font-size:21px;font-weight:700;color:var(--accent)}
.section-title .orn{flex:0 0 auto;width:78px;height:14px;opacity:.95;
  background:url(../assets/img/ornament.png) repeat-x left center/auto 14px}
.section-note{margin:-6px 0 12px;color:var(--muted);font-size:13px}
.subsection{margin:16px 0 4px}
.subsection-title{font-family:var(--font-head);font-size:16px;font-weight:600;color:var(--text);margin:0 0 10px;
  padding-left:12px;border-left:3px solid var(--gold)}
.subsection-note{font-family:var(--font-body);font-weight:400;font-size:13px;color:var(--muted)}

/* ── Сетка и карточки ──────────────────────── */
.grid{display:grid;gap:12px;grid-template-columns:repeat(3,1fr)}
.card{display:flex;flex-direction:column;background:var(--card);border:1px solid var(--line);
  border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow);cursor:pointer;transition:.18s}
.card:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(138,90,46,.12)}
.card:focus-visible{outline:2px solid var(--accent);outline-offset:2px}
.card.no-photo .card-media{aspect-ratio:16/5}
.card.no-photo .ph img{width:54px}
.card-media{position:relative;aspect-ratio:4/3;background:linear-gradient(160deg,#FFFDF7,#F6EDD9);
  display:grid;place-items:center;border-bottom:1px solid var(--line)}
.ph img{width:72px;height:auto;display:block;opacity:.16}
.card-body{padding:10px 12px 12px;display:flex;flex-direction:column;gap:5px;flex:1}
.card-title{margin:0;font-family:var(--font-head);font-size:16px;font-weight:600;line-height:1.25}
.card-desc{margin:0;color:var(--muted);font-size:13px;line-height:1.35;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.card-foot{margin-top:auto;display:flex;align-items:baseline;gap:8px;padding-top:4px}
.card-price{font-family:var(--font-head);font-size:16px;font-weight:700}
.card-weight{font-size:13px;color:var(--muted)}
.badges{display:flex;flex-wrap:wrap;gap:4px}
.badge{font-size:10px;letter-spacing:.04em;text-transform:uppercase;padding:2px 7px;border-radius:999px;
  background:rgba(235,199,127,.35);color:var(--accent);font-weight:600}
.badge.b-spicy{background:rgba(196,74,43,.14);color:#B4472B}
.badge.b-veg{background:rgba(120,158,106,.18);color:#4F7343}
.badge.b-new{background:rgba(191,208,218,.4);color:#4B6674}
.status-flag{position:absolute;left:8px;top:8px;background:rgba(31,26,21,.72);color:#FFFDF7;
  font-size:11px;padding:3px 9px;border-radius:999px}
.card[data-status="soon"],.card[data-demo-status="soon"]{opacity:.62}
.card[data-status="out_of_stock"],.card[data-demo-status="out_of_stock"]{opacity:.45}
.card[data-status="hidden"]{display:none}

/* ── Режим без фото ────────────────────────── */
.list{display:none}
.row{display:flex;align-items:baseline;gap:8px;padding:9px 2px;border-bottom:1px dashed var(--line)}
.row-name{font-family:var(--font-head);font-size:16px}
.row-dots{flex:1;border-bottom:1px dotted var(--line);transform:translateY(-4px)}
.row-weight{font-size:13px;color:var(--muted)}
.row-price{font-family:var(--font-head);font-weight:700;font-size:16px;white-space:nowrap}
html[data-photos="off"] .grid{display:none}
html[data-photos="off"] .list{display:block}

/* ── Демо-режим ────────────────────────────── */
html:not([data-demo="on"]) .demo-only{display:none}
html:not([data-demo="on"]) .card[data-demo-status]{opacity:1}

/* ── Подробное окно ────────────────────────── */
.overlay{position:fixed;inset:0;z-index:60;background:rgba(31,26,21,.45);opacity:0;pointer-events:none;transition:.2s}
.overlay.is-open{opacity:1;pointer-events:auto}
.sheet{position:fixed;z-index:61;background:var(--card);border:1px solid var(--line);
  left:50%;top:50%;transform:translate(-50%,-46%) scale(.97);opacity:0;pointer-events:none;
  width:min(520px,calc(100vw - 32px));max-height:86vh;overflow:auto;border-radius:18px;transition:.22s}
.sheet.is-open{opacity:1;pointer-events:auto;transform:translate(-50%,-50%) scale(1)}
.sheet-media{aspect-ratio:4/3;background:linear-gradient(160deg,#FFFDF7,#F4E9D2);display:grid;place-items:center}
.sheet-media .ph img{width:118px}
.sheet-body{padding:16px 18px 22px}
.sheet-title{font-family:var(--font-head);font-size:22px;font-weight:700;margin:6px 0 6px}
.sheet-foot{display:flex;align-items:baseline;gap:10px;margin:8px 0 10px}
.sheet-price{font-family:var(--font-head);font-size:22px;font-weight:700}
.sheet-weight{color:var(--muted)}
.sheet-desc{color:var(--muted);margin:0;line-height:1.5}
.sheet-close{position:absolute;right:12px;top:12px;z-index:2;width:34px;height:34px;border-radius:50%;border:none;
  background:rgba(251,246,234,.9);color:var(--text);font-size:20px;line-height:1;cursor:pointer}
.sheet-grab{display:none}

/* ── О нас и подвал ────────────────────────── */
.about{margin:28px 0 0;padding:22px 18px;background:var(--card);border:1px solid var(--line);border-radius:18px;
  display:grid;grid-template-columns:1fr 220px;gap:18px;align-items:center}
.about h2{font-family:var(--font-head);color:var(--accent);margin:0 0 8px;font-size:20px}
.about p{margin:0 0 10px;color:var(--muted)}
.contacts{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.contacts a{display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border-radius:999px;
  border:1px solid var(--line);text-decoration:none;font-size:14px;background:var(--bg)}
.contacts a.primary{background:var(--accent);color:#FFFDF7;border-color:var(--accent)}
footer{padding:22px 0 90px;text-align:center;color:var(--muted);font-size:13px}
.footer-mark img{width:52px;height:auto;opacity:.3;margin:0 auto 8px;display:block}

/* ── Плавающие кнопки ──────────────────────── */
.fab{position:fixed;right:14px;z-index:40;border-radius:999px;border:1px solid var(--line);
  background:var(--card);box-shadow:var(--shadow);cursor:pointer;font:inherit;color:var(--text)}
.fab-top{bottom:132px;width:44px;height:44px;display:none;place-items:center;font-size:18px;color:var(--accent)}
.fab-top.is-visible{display:grid}
.fab-rate{bottom:80px;padding:10px 16px;font-family:var(--font-head);font-weight:600;font-size:14px}

/* ── Панель прототипа ──────────────────────── */
.proto{position:fixed;left:14px;bottom:14px;z-index:80;background:var(--card);border:1px solid var(--accent);
  border-radius:14px;padding:10px 12px;box-shadow:0 8px 24px rgba(31,26,21,.18);font-size:12px;max-width:calc(100vw - 28px)}
.proto b{display:block;font-family:var(--font-head);color:var(--accent);margin-bottom:6px;font-size:12px}
.proto-row{display:flex;align-items:center;gap:6px;margin-bottom:5px;flex-wrap:wrap}
.proto-row span{color:var(--muted);min-width:74px}
.proto button{border:1px solid var(--line);background:var(--bg);border-radius:8px;padding:4px 9px;cursor:pointer;font:inherit}
.proto button.on{background:var(--accent);color:#FFFDF7;border-color:var(--accent)}
.proto-toggle{position:fixed;left:14px;bottom:14px;z-index:81}

/* ── Планшет ───────────────────────────────── */
@media (max-width:900px){
  .grid{grid-template-columns:repeat(2,1fr)}
  .about{grid-template-columns:1fr}
}

/* ── Телефон ───────────────────────────────── */
@media (max-width:599px){
  body{font-size:15px}
  .hero{grid-template-columns:1fr 46%;gap:0;padding:8px 0 6px;padding-right:0;max-height:60vh;align-items:end}
  .hero .building{width:100%;max-width:none}
  .hero-greet{grid-column:1/-1;margin-top:4px;padding-right:16px}
  .hero-word{font-size:24px}
  .hero-kicker{font-size:15px;margin:8px 0 2px}
  .hero-greet{font-size:13px}
  .section-title{font-size:19px}
  .section-title .orn{width:54px;height:12px;background-size:auto 12px}

  /* Вариант A — одна колонка, горизонтальные карточки */
  html[data-layout="a"] .grid{grid-template-columns:1fr;gap:10px}
  html[data-layout="a"] .card{flex-direction:row}
  html[data-layout="a"] .card-media,html[data-layout="a"] .card.no-photo .card-media{flex:0 0 104px;width:104px;aspect-ratio:1/1;border-bottom:none;border-right:1px solid var(--line)}
  html[data-layout="a"] .ph img{width:52px}
  html[data-layout="a"] .card-body{padding:9px 11px}

  /* Вариант B — две колонки, вертикальные карточки */
  html[data-layout="b"] .grid{grid-template-columns:repeat(2,1fr);gap:10px}
  html[data-layout="b"] .card-title{font-size:15px}
  html[data-layout="b"] .card-desc{-webkit-line-clamp:2;font-size:12px}
  html[data-layout="b"] .ph img{width:52px}

  /* Подробное окно превращается в нижнюю панель */
  .sheet{left:0;right:0;top:auto;bottom:0;width:100%;max-height:88vh;border-radius:20px 20px 0 0;
    transform:translateY(16px);transition:transform .24s,opacity .2s}
  .sheet.is-open{transform:translateY(0)}
  .sheet-grab{display:block;width:44px;height:4px;border-radius:2px;background:var(--line);margin:9px auto 0}
  .fab-rate{bottom:74px}
  .fab-top{bottom:126px}
}
@media (prefers-reduced-motion:reduce){*{transition:none!important;scroll-behavior:auto!important}}
`;

const JS = `
(function(){
  var root=document.documentElement;
  var saved={layout:'a',font:'1',photos:'on',demo:'off'};
  try{ var s=JSON.parse(localStorage.getItem('merler-proto')||'{}'); Object.assign(saved,s); }catch(e){}
  var q=new URLSearchParams(location.search);
  ['layout','font','photos','demo'].forEach(function(k){ if(q.get(k)) saved[k]=q.get(k); });
  function apply(){
    root.setAttribute('data-layout',saved.layout);
    root.setAttribute('data-font',saved.font);
    root.setAttribute('data-photos',saved.photos);
    root.setAttribute('data-demo',saved.demo);
    document.querySelectorAll('[data-set]').forEach(function(b){
      var p=b.getAttribute('data-set'), v=b.getAttribute('data-val');
      b.classList.toggle('on', saved[p]===v);
    });
    try{ localStorage.setItem('merler-proto',JSON.stringify(saved)); }catch(e){}
  }
  document.addEventListener('click',function(e){
    var b=e.target.closest('[data-set]');
    if(!b) return;
    saved[b.getAttribute('data-set')]=b.getAttribute('data-val');
    apply();
  });
  apply();

  var panel=document.querySelector('.proto'), pt=document.querySelector('.proto-toggle');
  if(q.get('panel')==='off'){ panel.setAttribute('hidden',''); pt.setAttribute('hidden',''); }
  pt.addEventListener('click',function(){
    var hidden=panel.hasAttribute('hidden');
    if(hidden){panel.removeAttribute('hidden');pt.setAttribute('hidden','');}
  });
  document.querySelector('.proto-hide').addEventListener('click',function(){
    panel.setAttribute('hidden','');pt.removeAttribute('hidden');
  });

  /* ── Scroll-spy ── */
  var chips=[].slice.call(document.querySelectorAll('.chip'));
  var strip=document.querySelector('.chips');
  var secs=[].slice.call(document.querySelectorAll('.section'));
  var map={};
  chips.forEach(function(c){ map[c.getAttribute('href').slice(1)]=c; });
  function setActive(id){
    chips.forEach(function(c){ c.classList.remove('is-active'); });
    var c=map[id]; if(!c) return;
    c.classList.add('is-active');
    var r=c.getBoundingClientRect(), sr=strip.getBoundingClientRect();
    if(r.left<sr.left+12||r.right>sr.right-12){
      strip.scrollTo({left:strip.scrollLeft+r.left-sr.left-16,behavior:'smooth'});
    }
  }
  if('IntersectionObserver' in window){
    var visible={};
    var io=new IntersectionObserver(function(entries){
      entries.forEach(function(en){ visible[en.target.id]=en.isIntersecting?en.intersectionRatio:0; });
      var best='',bv=0;
      secs.forEach(function(s){ var v=visible[s.id]||0; if(v>bv){bv=v;best=s.id;} });
      if(best) setActive(best);
    },{rootMargin:'-78px 0px -55% 0px',threshold:[0,.15,.35,.6,1]});
    secs.forEach(function(s){ io.observe(s); });
  }
  chips.forEach(function(c){
    c.addEventListener('click',function(){ setActive(c.getAttribute('href').slice(1)); });
  });

  /* ── Поиск ── */
  var sb=document.querySelector('.searchbar'), si=sb.querySelector('input');
  document.querySelector('.js-search').addEventListener('click',function(){
    sb.classList.toggle('is-open');
    if(sb.classList.contains('is-open')) si.focus(); else { si.value=''; filter(''); }
  });
  var nores=document.querySelector('.no-results');
  function filter(q){
    q=q.trim().toLowerCase();
    var shown=0;
    document.querySelectorAll('[data-search]').forEach(function(el){
      var hit=!q||el.getAttribute('data-search').indexOf(q)>-1;
      el.style.display=hit?'':'none';
      if(hit&&el.classList.contains('card')) shown++;
    });
    secs.forEach(function(s){
      var any=s.querySelector('.card:not([style*="display: none"])');
      s.style.display=(q&&!any)?'none':'';
    });
    s_subs();
    nores.style.display=(q&&shown===0)?'block':'none';
  }
  function s_subs(){
    document.querySelectorAll('.subsection').forEach(function(sub){
      var any=sub.querySelector('.card:not([style*="display: none"])');
      sub.style.display=any?'':'none';
      if(!si.value.trim()) sub.style.display='';
    });
  }
  si.addEventListener('input',function(){ filter(si.value); });

  /* ── Подробное окно ── */
  var ov=document.querySelector('.overlay'), sheet=document.querySelector('.sheet'), opened=false;
  function open(card){
    sheet.querySelector('.sheet-title').textContent=card.getAttribute('data-title');
    sheet.querySelector('.sheet-price').textContent=card.getAttribute('data-price');
    var w=card.getAttribute('data-weight');
    sheet.querySelector('.sheet-weight').textContent=w||'';
    var d=card.getAttribute('data-desc');
    sheet.querySelector('.sheet-desc').textContent=d||'';
    ov.classList.add('is-open'); sheet.classList.add('is-open');
    document.body.style.overflow='hidden';
    opened=true;
    history.pushState({merler:1},'');
    sheet.querySelector('.sheet-close').focus();
  }
  function close(fromPop){
    if(!opened) return;
    ov.classList.remove('is-open'); sheet.classList.remove('is-open');
    document.body.style.overflow='';
    opened=false;
    if(!fromPop && history.state && history.state.merler) history.back();
  }
  document.addEventListener('click',function(e){
    var c=e.target.closest('.card'); if(c){ open(c); return; }
    if(e.target.closest('.sheet-close')||e.target===ov) close();
  });
  document.addEventListener('keydown',function(e){
    if(e.key==='Escape') close();
    var c=e.target.closest&&e.target.closest('.card');
    if(c&&(e.key==='Enter'||e.key===' ')){ e.preventDefault(); open(c); }
  });
  window.addEventListener('popstate',function(){ close(true); });
  var y0=null;
  sheet.addEventListener('touchstart',function(e){ y0=e.touches[0].clientY; },{passive:true});
  sheet.addEventListener('touchmove',function(e){
    if(y0===null||sheet.scrollTop>0) return;
    var dy=e.touches[0].clientY-y0;
    if(dy>70){ y0=null; close(); }
  },{passive:true});

  /* ── Наверх ── */
  var top=document.querySelector('.fab-top');
  window.addEventListener('scroll',function(){
    top.classList.toggle('is-visible',window.scrollY>600);
  },{passive:true});
  top.addEventListener('click',function(){ window.scrollTo({top:0,behavior:'smooth'}); });

  document.querySelector('.fab-rate').addEventListener('click',function(){
    alert('Прототип: здесь откроется окно с пятью звёздами, ссылкой на отзыв в картах и формой «Написать нам напрямую».');
  });
})();
`;

const HTML = `<!doctype html>
<html lang="ru" data-layout="a" data-font="1" data-photos="on" data-demo="off">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Меню ресторана «Мерлер» — прототип</title>
<meta name="description" content="Меню ресторана бутик-отеля «Мерлер»: европейская, арабская и дагестанская кухня.">
<link rel="stylesheet" href="../assets/fonts/montserrat-alternates.css">
<link rel="stylesheet" href="../assets/fonts/comfortaa.css">
<link rel="stylesheet" href="../assets/fonts/inter.css">
<link rel="stylesheet" href="../assets/fonts/manrope.css">
<style>${CSS}</style>
</head>
<body>

<header class="wrap hero">
  <div class="hero-left">
    <div class="hero-logo">${LOGO}</div>
    <p class="hero-kicker">Меню ресторана</p>
  </div>
  ${BUILDING}
  <p class="hero-greet">Европейская, арабская и дагестанская кухня. Всё готовится после заказа — спасибо за терпение.</p>
</header>

<nav class="navbar" aria-label="Разделы меню">
  <div class="navbar-inner">
    <div class="chips" role="tablist">${navHtml}</div>
    <button class="icon-btn js-search" type="button" aria-label="Поиск по меню">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
    </button>
  </div>
  <div class="searchbar"><input type="search" placeholder="Найти блюдо или ингредиент…" aria-label="Поиск по меню"></div>
</nav>

<main class="wrap">
  <p class="no-results">Ничего не нашлось. Попробуйте другое слово.</p>
  ${sections.map(section).join('\n')}

  <section class="about" id="about">
    <div>
      <h2>О ресторане</h2>
      <p>Ресторан бутик-отеля «Мерлер». Завтраки для гостей отеля, обеды и ужины — для всех. Кухня работает с 8:00 до 23:00.</p>
      <p>Адрес уточняется. Заказ в номер — по телефону.</p>
      <div class="contacts">
        <a class="primary" href="tel:+79886413234">+7 988 641-32-34</a>
        <a href="https://wa.me/79886413234">WhatsApp</a>
        <a href="https://instagram.com/merler.hotel">Instagram</a>
        <a href="#">Яндекс Карты</a>
      </div>
    </div>
    ${BUILDING}
  </section>
</main>

<footer>
  <div class="footer-mark">${LOGO_MARK}</div>
  <div>Бутик-отель «Мерлер» · Мерлер.рф</div>
  <div>Прототип на реальных данных: ${total} блюд, ${sections.length} разделов</div>
</footer>

<button class="fab fab-top" type="button" aria-label="Наверх">↑</button>
<button class="fab fab-rate" type="button">⭐ Оценить</button>

<div class="overlay"></div>
<aside class="sheet" role="dialog" aria-modal="true" aria-label="Блюдо">
  <div class="sheet-grab"></div>
  <button class="sheet-close" type="button" aria-label="Закрыть">×</button>
  <div class="sheet-media">${PLACEHOLDER}</div>
  <div class="sheet-body">
    <h2 class="sheet-title"></h2>
    <div class="sheet-foot"><span class="sheet-price"></span><span class="sheet-weight"></span></div>
    <p class="sheet-desc"></p>
  </div>
</aside>

<button class="fab proto-toggle" type="button" hidden>⚙</button>
<div class="proto">
  <b>Панель прототипа</b>
  <div class="proto-row"><span>Карточки</span>
    <button data-set="layout" data-val="a" type="button">A · список</button>
    <button data-set="layout" data-val="b" type="button">B · плитка</button>
  </div>
  <div class="proto-row"><span>Шрифт</span>
    <button data-set="font" data-val="1" type="button">1 · Montserrat Alt</button>
    <button data-set="font" data-val="2" type="button">2 · Comfortaa</button>
  </div>
  <div class="proto-row"><span>Фото</span>
    <button data-set="photos" data-val="on" type="button">с фото</button>
    <button data-set="photos" data-val="off" type="button">без фото</button>
  </div>
  <div class="proto-row"><span>Демо</span>
    <button data-set="demo" data-val="off" type="button">выкл</button>
    <button data-set="demo" data-val="on" type="button">статусы и бейджи</button>
  </div>
  <div class="proto-row"><button class="proto-hide" type="button">Свернуть панель</button></div>
</div>

<script>${JS}</script>
</body>
</html>
`;

fs.mkdirSync(path.join(ROOT, 'prototype'), { recursive: true });
fs.writeFileSync(path.join(ROOT, 'prototype', 'index.html'), HTML);
console.log('prototype/index.html — ' + total + ' блюд, ' + sections.length + ' разделов, '
  + Math.round(Buffer.byteLength(HTML) / 1024) + ' КБ');
