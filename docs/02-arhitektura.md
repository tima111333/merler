# Этап 2. Архитектура и схема данных

## 1. Дерево файлов

### Плагин `merler-menu` (данные и логика — не зависят от темы)

```
merler-menu/
├── merler-menu.php                  заголовок плагина, константы, подключение модулей, активация
├── uninstall.php                    чистка настроек и роли при удалении плагина
├── includes/
│   ├── functions.php                merler_option(), merler_price(), merler_get_dish(), punycode-адрес
│   ├── class-merler-post-type.php   типы записей merler_dish и merler_review, отключение Gutenberg
│   ├── class-merler-taxonomy.php    таксономия merler_section, поля терминов, дерево разделов
│   ├── class-merler-meta.php        register_post_meta и вся санитизация полей блюда
│   ├── class-merler-query.php       сборка меню одним запросом + кэш
│   ├── class-merler-settings.php    страница «Настройки меню» на Settings API
│   ├── class-merler-roles.php       роль «Менеджер меню», права администратора и редактора
│   ├── class-merler-importer.php    импорт и экспорт JSON, идемпотентность по slug
│   ├── class-merler-reviews.php     приём отзывов: nonce, honeypot, лимит по IP, письмо
│   ├── class-merler-schema.php      JSON-LD Restaurant → Menu → MenuSection → MenuItem, Open Graph
│   ├── class-merler-qr.php          страница QR-кода и таблички A6
│   └── class-merler-cache.php       версия кэша меню и сброс кэш-плагинов
├── admin/
│   ├── class-merler-admin.php       пункты меню админки, подключение стилей и скриптов
│   ├── class-merler-metabox.php     форма блюда: цена, вес, состав, статус, бейджи, порядок
│   ├── class-merler-columns.php     колонки списка, фильтры, сортировка, Quick Edit
│   ├── class-merler-bulk.php        массовая смена статуса и дублирование блюда
│   ├── class-merler-order.php       страница «Порядок блюд», сохранение перетаскивания по AJAX
│   ├── class-merler-dashboard.php   виджет консоли и страница «Инструкция»
│   ├── css/admin.css
│   └── js/
│       ├── settings.js              выбор картинок из медиатеки, палитра
│       ├── order.js                 перетаскивание на SortableJS
│       ├── quick-edit.js            подстановка значений в форму быстрого редактирования
│       ├── qr.js                    генерация QR и таблички, скачивание SVG и PNG
│       └── sortable.min.js          SortableJS 1.15.6 локально
├── assets/
│   ├── qrcode.min.js                qrcode-generator 1.4.4 локально, без внешних сервисов
│   └── logo-mark.png                символ «М» для заглушек в админке
└── data/
    └── menu-data.json               стартовое меню: 115 блюд из печатной версии
```

### Тема `merler-theme` (только внешний вид)

```
merler-theme/
├── style.css                        заголовок темы
├── functions.php                    подключение ассетов, CSS-переменные из настроек, заглушки
├── index.php  404.php               простые страницы
├── front-page.php                   меню на главной
├── page-menu.php                    шаблон «Меню ресторана» для отдельной страницы
├── header.php  footer.php
├── inc/
│   ├── setup.php                    поддержка возможностей темы, чистка лишнего из шапки
│   └── seo.php                      title, favicon, theme-color, предзагрузка шрифтов
├── template-parts/
│   ├── hero.php                     первый экран
│   ├── nav-categories.php           лента категорий и поиск
│   ├── section.php  subsection.php  разделы и подразделы
│   ├── dish-card.php                карточка блюда либо строка списка
│   ├── dish-modal.php               подробное окно и нижняя панель
│   ├── about.php                    блок «О нас и контакты»
│   └── rate.php                     кнопка «Оценить» и форма отзыва
└── assets/
    ├── css/menu.css                 один файл стилей
    ├── js/menu.js                   один файл, чистый JS, без jQuery
    ├── fonts/                       Montserrat Alternates и Inter, 12 файлов woff2 локально
    └── img/                         логотип, символ «М», орнамент, иллюстрация здания
```

## 2. Схема данных

### Тип записи `merler_dish` («Блюда»)

| Что | Где хранится | Тип / значения |
|---|---|---|
| Название | `post_title` | строка |
| Slug | `post_name` | строка, ключ идемпотентного импорта |
| Фото | `_thumbnail_id` | ID вложения |
| Порядок | `menu_order` | int, шаг 10 |
| Вес / выход | `_merler_weight` | строка: «330 г», «8 шт», «0,5 л» |
| Цена | `_merler_price` | int, рубли |
| Состав | `_merler_description` | текст (без HTML) |
| Бейджи | `_merler_badges` | массив: `chef`, `spicy`, `hit`, `new`, `veg` |
| Статус | `_merler_status` | `in_stock` / `soon` / `out_of_stock` / `hidden` |
| Раздел | таксономия `merler_section` | один терм (обычно самый глубокий) |

Блочный редактор для этого типа отключён (`show_in_rest` без Gutenberg-поддержки, `supports` без `editor`) — форма простая и понятная.

### Таксономия `merler_section` («Разделы меню»)

Иерархическая, 2 уровня: раздел → подраздел.

| Что | Где хранится |
|---|---|
| Название | `name` |
| Slug | `slug` |
| Родитель | `parent` |
| Порядок | мета терма `merler_order` |
| Показывать | мета терма `merler_visible` (1/0) |
| Короткая подпись | мета терма `merler_note` («к хинкалу») |

### Тип записи `merler_review` («Отзывы»)

`post_title` — автозаголовок, `post_content` — текст отзыва, мета: `_merler_rating` (1–5), `_merler_phone`, `_merler_consent`, `_merler_ip_hash`, `_merler_ua`. Только чтение в админке, ответ по телефону.

### Опции (одна запись `merler_settings`, массив)

`logo_id`, `building_id`, `hero_title`, `greeting`, `about_title`, `about_text`, `phone`, `whatsapp`, `instagram`, `address`, `map_url`, `hours`, `wifi_show`, `wifi_name`, `wifi_pass`, `review_url`, `review_email`, `privacy_url`, `menu_url`, `currency`, `no_photo_mode`, `enable_search`, `enable_rating`, `color_bg`, `color_card`, `color_accent`, `color_text`, `color_muted`, `color_line`.

## 3. Формат `menu-data.json`

```
{ version, restaurant, currency, note, sections: [ section ] }

section  = { name, slug, order, description, dishes: [dish], subsections: [section] }
dish     = { slug, title, weight, price, description, badges: [], status, order }
```

ДОПУЩЕНИЕ: в схему из ТЗ добавлено поле `slug` у блюда — без него импорт не может быть идемпотентным (повторный запуск обновляет запись по slug, а не плодит дубли). Slug — транслитерация названия с префиксом раздела там, где названия повторяются (`kurze-s-myasom` и `darginskoe-chudu-s-myasom`).

## 4. Прототип

Файл `prototype/index.html` — один самодостаточный файл на реальных данных (115 блюд), собирается скриптом `tools/build-prototype.js` из `data/menu-data.json`.

Панель прототипа в правом нижнем углу переключает:

- **Карточки A** — одна колонка, горизонтальные карточки, фото слева 104 px;
- **Карточки B** — две колонки, вертикальные карточки, фото 4:3;
- **Шрифт 1** — Montserrat Alternates + Inter;
- **Шрифт 2** — Comfortaa + Manrope;
- **Режим без фото** — компактный список, как в печатном меню;
- **Демо-статусы** — показывает, как выглядят «Скоро», «Нет в наличии» и бейджи (только в прототипе).

Варианты переключаются и через адрес: `?layout=b&font=2&photos=off` — так удобно отправить ссылку на конкретный вариант.

### Что выбрано в реализации

**Вариант A + Montserrat Alternates.** Обоснование: в варианте B на экран телефона помещается два блюда вместо четырёх, а пока фотографий нет — это две пустые плитки; Montserrat Alternates повторяет характерные «т» и «и» из печатного меню (сравните «Ризоmmо» в макете), Comfortaa мягче и выглядит менее премиально. Решение принято за клиента и подлежит пересмотру — переключение раскладки стоит один CSS-блок.

Шрифты подключены локально из `assets/fonts/` — как и будет в теме, никакого CDN.
