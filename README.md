
# Плагин Attacher for Cotonti CMF v1.0.0+, PHP 8.5+

**Attacher** — расширение для Cotonti CMF, предназначенное для управления загрузкой, обработкой и отображением файлов и изображений. Плагин прикрепляет файлы к любым объектам системы: страницам, постам форума, карточкам товаров, пользовательским модулям. Поддерживает асинхронную загрузку, обработку изображений, интеграцию с визуальными редакторами, вывод в виде галерей или списков для скачивания.


[![Version](https://img.shields.io/badge/version-2.0.0-green.svg)](https://github.com/webitproff/cot-Attacher-Roffun/releases)
[![Cotonti Compatibility](https://img.shields.io/badge/Cotonti-1.0-orange.svg)](https://github.com/Cotonti/Cotonti)
[![PHP](https://img.shields.io/badge/PHP-8.5-purple.svg)](https://www.php.net/releases/8_5_6.php)
[![MySQL](https://img.shields.io/badge/MySQL-8.4-blue.svg)](https://www.mysql.com/)
[![Bootstrap v5.3.8](https://img.shields.io/badge/Bootstrap-v5.3.8-blueviolet.svg)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/license-BSD-blue.svg)](https://github.com/webitproff/cot-Attacher-Roffun/blob/main/LICENSE)

![Плагин Attacher для Cotonti CMF v1.0.0+, PHP 8.5+](https://raw.githubusercontent.com/webitproff/cot-Attacher-Roffun/refs/heads/main/attacher.png)

**Модернизация и обновление плагина в 2026 году:** [webitproff](https://github.com/webitproff)

**Ссылки по теме:**
- Репозиторий: [github.com/webitproff/cot-Attacher-Roffun](https://github.com/webitproff/cot-Attacher-Roffun)
- Маркетплейс расширений Cotonti: [abuyfile.com/ru/market/cotonti/plugs/attacher-files-new](https://abuyfile.com/ru/market/cotonti/plugs/attacher-files-new)
- Форум поддержки: [abuyfile.com/ru/forums/cotonti/custom/plugs/attacher](https://abuyfile.com/ru/forums/cotonti/custom/plugs/attacher)
- Автор: [webitproff](https://github.com/webitproff)
- **[Справочник по функции `att_display()` плагина Attacher](https://github.com/webitproff/cot-Attacher-Roffun/blob/main/MANUAL_RU_(att_display).md)**

## Содержание

- [Что нового в модернизации](#chto-novogo)
- [Введение](#vvedenie)
- [Основные возможности](#osnovnye-vozmozhnosti)
- [Системные требования](#sistemnye-trebovaniya)
- [Установка](#ustanovka)
- [Конфигурация](#konfiguraciya)
- [Структура данных](#struktura-dannyh)
- [Шаблоны плагина](#shablony-plagina)
- [Интеграция в шаблоны](#integraciya-v-shablony)
- [Функции API](#funkcii-api)
- [Параметры ключевых функций](#parametry-klyuchevyh-funkciy)
- [BB-код для изображений](#bb-kod)
- [Совместимость с галереями](#sovmestimost-s-galereyami)
- [Управление через админ-панель](#upravlenie-cherez-admin-panel)
- [Правила вызова в шаблонах CoTemplate](#pravila-cotemplate)
- [Хардненинг `attacher.upload.php`](#hardening)
- [Антипаттерны](#antipatterny)
- [Примечания и ограничения](#primechaniya)

## Что нового в модернизации {#chto-novogo}

- Приведение плагина к Cotonti CMF v1.0.0.
- Требование PHP 8.5+.
- Обновление совместимости библиотек и шаблонов.
- Удалены устаревшие упоминания IE8+ и PHP 7.1+.
- Актуализированы ссылки на репозиторий, маркетплейс, форум, автора.
- Переработана структура документации и якорные ссылки.
- **`att_display()`**: параметр `$limit` расширен до `int|string` — обратная совместимость со старыми шаблонами, где передавалось `''` (бывший `TypeError` → HTTP 500). Добавлен параметр `$admin` (`?bool`) для поиска шаблона в админ-темах. `$order` валидируется белым списком (`att_order ASC/DESC`, `att_id ASC/DESC`).
- **`attacher.upload.php`**: переписан под PHP 8.5+. Добавлены SSRF-защита в URL-загрузке, CSRF-токен на всех мутациях, безопасный `unserialize` с `allowed_classes: false`, санитизация имён файлов, отключение CORS-wildcard, проверки прав до сетевого запроса, ограничение размера при потоковом скачивании, отключение redirect в cURL, включение `SSL_VERIFYPEER`.
- **Исправлено**: удаление файлов из формы редактирования (`_method=DELETE`) — параметр `_method` принимается из GET-строки, а не только из POST.

## Введение {#vvedenie}

Attacher хранит метаданные вложений в отдельной таблице `{prefix}attacher`. Каждая запись содержит:

- `att_id` — уникальный ID вложения (автоинкремент);
- `att_user` — ID пользователя, загрузившего файл;
- `att_area` — код модуля/плагина, к которому привязан файл (например, `page`, `market`, `forums`);
- `att_item` — ID объекта в этом модуле (ID страницы, ID товара, ID поста);
- `att_field` — произвольная строковая метка поля (например, `''`, `'gallery'`, `'docs'`);
- `att_path` — путь к файлу на диске;
- `att_filename` — оригинальное имя файла;
- `att_ext` — расширение;
- `att_img` — флаг «изображение» (1/0);
- `att_size` — размер в байтах;
- `att_title` — заголовок вложения;
- `att_count` — счётчик скачиваний;
- `att_order` — порядок сортировки;
- `att_lastmod` — дата последнего изменения;
- `att_unikey` — временный ключ для файлов, загруженных до создания объекта.

Плагин предоставляет набор функций, которые вызываются из шаблонов `.tpl` через Cotonti-шаблонизатор CoTemplate. Основные сценарии использования: вывод загрузчика в формы создания/редактирования объекта, вывод изображений и файлов на страницах объекта, вывод превью в списках.

## Основные возможности {#osnovnye-vozmozhnosti}

### Загрузка файлов

- Выбор файлов через стандартный диалог.
- Drag-and-drop в область загрузки.
- Загрузка по URL внешнего сайта — изображение скачивается на сервер, минуя сохранение на устройство пользователя. Реализовано через JavaScript Load Image.
- Множественная загрузка за один раз с настраиваемым лимитом.
- Чанковая загрузка (по частям) для обхода ограничений `$_POST` на стороне сервера.
- Проверка расширения и MIME-типа.
- Поддержка изображений, аудио, видео, архивов, документов.

### Управление вложениями

- Привязка к любому объекту Cotonti с указанием модуля, ID объекта и метки поля.
- Редактирование заголовка каждого файла.
- Замена файла без потери привязки к объекту — старый файл удаляется, новый получает тот же `att_id`, привязка сохраняется.
- Сортировка вложений через drag-and-drop (библиотека TableDnD).
- Учёт количества скачиваний.
- Сохранение оригинальных имён файлов.
- Удаление файлов из формы редактирования — `_method=DELETE` в GET-строке URL обрабатывается корректно.

### Обработка изображений

- Создание двух миниатюр: малой и большой, с настраиваемыми размерами.
- Режимы кадрирования миниатюр: `crop`, `width`, `height`, `auto`.
- Опциональное увеличение маленьких изображений.
- Конвертация загружаемых изображений в JPEG с настраиваемым качеством.
- Наложение водяного знака с минимальными размерами изображения для применения.
- Наложение фоновой текстуры с настраиваемым отступом.
- Адаптивный вывод через `<picture>` с двумя источниками (`<source>` для десктопа, `<img>` для мобильных).
- Обработка «на лету»:
  - удаление атрибута `style`;
  - обёртка в `<a>` со ссылкой на большую миниатюру;
  - обёртка в `<span>` с настраиваемым CSS-классом;
  - замена `alt` в `title` у ссылки.
- BB-код `[att_image?...]` для вставки изображений в текст статьи, товара, поста.

### Интеграция с формами и редакторами

- Виджет `att_filebox` — форма загрузки для вставки в страницы создания/редактирования объектов.
- Загрузка по URL прямо из формы.
- Предпросмотр изображений до загрузки.
- Кнопки вставки изображений в визуальный редактор с учётом настроек размера.
- Вывод галереи (`att_gallery`), списка скачивания (`att_downloads`), общего списка (`att_display`).

### Безопасность и ограничения

- Проверка прав на загрузку, редактирование, удаление.
- Лимиты: размер одного файла, общий объём на пользователя, число вложений на объект.
- Автоочистка временных файлов (garbage collection) для несохранённых форм.
- Токен `x` для защиты операций загрузки и удаления.
- CSRF-защита на всех state-changing операциях AJAX-эндпоинта (`x` проверяется для POST/PUT/PATCH/DELETE).
- SSRF-защита URL-загрузки: только `http`/`https`, только публичные IP, пиннинг разрешённого IP через `CURLOPT_RESOLVE`, отключение redirect, включение проверки TLS-сертификата.
- Безопасный `unserialize` с `allowed_classes: false` — блокирует PHP object injection через поле `param`.
- Санитизация имён файлов — вычищаются `..`, слэши, управляющие символы, лидирующие точки.

### Кастомизация

- Настраиваемые шаблоны интерфейса загрузки и вывода файлов.
- Настройки в админ-панели: типы файлов, лимиты, миниатюры, водяные знаки, текстуры, режимы вывода.
- Локализация через файлы `attacher.<lang>.lang.php`.
- Совместимость с внешними галереями (например, Highslide) через шаблоны.

## Системные требования {#sistemnye-trebovaniya}

- **Cotonti CMF**: v1.0.0+.
- **PHP**: 8.5+.
- **Расширения PHP**: `pdo_mysql`, `gd` (или `imagick`), `fileinfo`, `mbstring`, `curl`.
- **Библиотеки**:
  - jQuery и jQuery UI — интерфейс загрузчика.
  - jQuery File Upload — асинхронная загрузка файлов, мультизагрузка, чанки.
  - JavaScript Load Image — обработка изображений и загрузка по URL.
  - JavaScript Templates — динамический рендеринг шаблонов в процессе загрузки.
  - Canvas-to-Blob — преобразование изображений.
  - TableDnD — сортировка вложений через drag-and-drop.
- **Браузеры**: современные (Chrome, Firefox, Safari, Edge).
- **БД**: отдельная таблица `{prefix}attacher`.
- **Конфликты**: несовместим с плагином `attach2` и модулем `file`. Отключить перед использованием.

## Установка {#ustanovka}

1. Скачать архив плагина, распаковать.
2. Переименовать папку в `attacher`, поместить в директорию `plugins/` сайта.
3. В админ-панели Cotonti: `Управление сайтом → Расширения → Загрузка изображений и файлов` — установить плагин.
4. Создать папку для загрузок (по умолчанию `datas/attacher`), выставить права `CHMOD 755` или `777`. Если используется другой каталог, настроить его в конфигурации.
5. Настроить параметры в `Управление сайтом → Конфигурация → Плагины → Attacher`.

## Конфигурация {#konfiguraciya}

### Основные параметры

- **Директория для файлов** — путь к папке загрузок (по умолчанию `datas/attacher`).
- **Префикс имён файлов** — приставка к именам файлов на диске (например, `att_`).
- **Разрешённые типы файлов** — список расширений через запятую (например, `jpg,png,gif,pdf,zip,mp3,mp4`).
- **Макс. число вложений** — лимит на количество файлов в одном объекте (5, 10, неограниченно).
- **Допустимые MIME-типы** — для фильтрации в диалоге выбора файлов (например, `image/*,application/pdf`).
- **Загрузка чанками** — размер чанка в байтах (например, 1 МБ) для обхода ограничений `$_POST`.
- **Макс. размер файла** — ограничение на размер одного файла в байтах (например, `10485760` для 10 МБ).
- **Общее дисковое пространство** — лимит хранилища на пользователя в байтах (например, `104857600` для 100 МБ).
- **Автоматическая загрузка** — включение/выключение автоматической загрузки после выбора файлов (скрывает кнопки «Начать» и «Отменить»).
- **Последовательная загрузка** — последовательно или параллельно загружать файлы.
- **Подключение CSS** — включение стилей плагина (`fileupload.css` и jQuery UI).

### Настройка изображений

- **Уменьшение изображений** — включение масштабирования загружаемых изображений.
- **Ширина/высота уменьшения** — максимальные размеры (например, 1920×1080).
- **Конвертация в JPEG** — автоматическое преобразование всех изображений в JPEG.
- **Качество JPEG** — процент сжатия (например, 80%).

### Настройка миниатюр

- **Показывать миниатюры** — вкл/выкл отображения в интерфейсе.
- **Увеличение маленьких изображений** — увеличение изображений, меньших заданных размеров миниатюр.
- **Ширина/высота миниатюр** — размеры маленькой (например, 200×160) и большой (например, 800×600) миниатюр.
- **Режим кадрирования** — `crop` (обрезка), `width` (по ширине), `height` (по высоте), `auto` (сохранение пропорций).

### Водяные знаки и текстуры

- **Водяной знак** — путь к PNG-файлу водяного знака (например, `datas/attacher/watermark.png`).
- **Мин. ширина/высота** — минимальные размеры изображения для наложения знака (например, 300×200).
- **Фоновая текстура** — путь к файлу текстуры (например, `datas/attacher/texture.jpg`).
- **Отступ для текстуры** — размер отступа вокруг изображения для заполнения текстурой (например, 10 пикселей).

### Обработка изображений в статьях

- **Режим вывода** — ссылка, `<picture>`, чистое изображение.
- **Title из alt** — создание атрибута `title` из `alt` для ссылок.
- **Удаление style** — удаление атрибута `style` для выравнивания.
- **Адаптивный `<picture>`** — использование большой и маленькой миниатюр для разных устройств.
- **Контейнер `<span>`** — создание родительского контейнера с настраиваемым CSS-классом.

## Структура данных {#struktura-dannyh}

Таблица `{prefix}attacher` создаётся при установке плагина:

```sql
CREATE TABLE IF NOT EXISTS `cot_attacher` (
    `att_id` INT NOT NULL AUTO_INCREMENT,
    `att_user` INT NOT NULL,
    `att_area` VARCHAR(64) NOT NULL,
    `att_item` INT NOT NULL,
    `att_field` varchar(255) DEFAULT '' COMMENT 'Item field',
    `att_path` VARCHAR(255) DEFAULT '',
    `att_filename` VARCHAR(255) NOT NULL,
    `att_ext` VARCHAR(16) DEFAULT '',
    `att_img` TINYINT(1) DEFAULT 0,
    `att_size` INT DEFAULT 0,
    `att_title` VARCHAR(255) DEFAULT '',
    `att_count` INT DEFAULT 0,
    `att_order` SMALLINT DEFAULT 0,
    `att_lastmod` datetime DEFAULT NULL,
    `att_unikey` varchar(255) DEFAULT '',
    PRIMARY KEY(`att_id`),
    KEY (`att_area`, `att_item`)
);
```

Колонка `att_field` — произвольная текстовая метка, по которой можно фильтровать вложения. Значение попадает в неё через `att_filebox($area, $item, $field, ...)`: при загрузке через AJAX-контроллер плагин записывает `'att_field' => $field` в INSERT. Если `$field` не передан, колонка получает значение по умолчанию `''`. Значения `'gallery'`, `'docs'`, `'promo'` — произвольные; встроенного списка значений нет, ограничение — только `varchar(255)`.

Проверить, какие значения `att_field` реально есть в БД:

```sql
SELECT DISTINCT att_area, att_field, COUNT(*) FROM cot_attacher GROUP BY att_area, att_field;
```

## Шаблоны плагина {#shablony-plagina}

Плагин использует три основных шаблона для интерфейса и отображения файлов.

### attacher.filebox.tpl

- **Назначение**: форма загрузки файлов, встраиваемая в страницы создания/редактирования объектов.
- **Содержимое**:
  - поле выбора файлов и область drag-and-drop;
  - поле ввода URL для загрузки изображения с внешнего сайта и кнопка «Загрузить по URL»;
  - кнопки управления: «Добавить файл», «Начать загрузку», «Отменить», «Удалить», «Выделить все»;
  - прогресс-бар;
  - таблица предпросмотра загружаемых файлов с миниатюрами.
- **Конфигурация**: подключает объект `attConfig` с параметрами загрузки (расширения, MIME-типы, лимиты).
- **Библиотеки**: jQuery File Upload, JavaScript Load Image, TableDnD.

### attacher.files.tpl

- **Назначение**: полноценная страница управления загрузками (iframe или отдельный интерфейс).
- **Содержимое**:
  - форма загрузки (выбор файлов, drag-and-drop, поле URL);
  - список загруженных файлов с возможностью редактирования заголовков, замены и удаления;
  - поддержка мультизагрузки, отображение в таблице.
- **Стили и скрипты**: `fileupload.css`, jQuery UI, jQuery, JavaScript Templates, Canvas-to-Blob.

### attacher.templates.tpl

- **Назначение**: шаблоны для динамического рендеринга файлов в процессе загрузки/скачивания.
- **Содержимое**:
  - `template-upload` — отображает файлы в процессе загрузки с прогресс-баром, именем и кнопкой отмены;
  - `template-download` — отображает загруженные файлы с миниатюрами, заголовками, кнопками удаления/замены и вставки в редактор.
- **Формат**: `text/x-tmpl` для рендеринга через JavaScript Templates.

Все шаблоны настраиваемы и локализуются через `{PHP.L.<key>}`.

## Интеграция в шаблоны {#integraciya-v-shablony}

### Загрузчик в форму создания страницы

```html
<!-- page.add.tpl -->
<!-- IF {PHP|cot_auth('plug', 'attacher', 'W')} -->
<div>
    {PHP|att_filebox('page', 0)}
</div>
<!-- ENDIF -->
```

### Загрузчик в форму редактирования

```html
<!-- page.edit.tpl -->
<!-- IF {PHP|cot_auth('plug', 'attacher', 'W')} -->
<div>
    {PAGEEDIT_FORM_ID|att_filebox('page', $this)}
</div>
<!-- ENDIF -->
```

### Загрузчик с меткой поля (слотом)

```html
<!-- market.edit.tpl: отдельный слот "gallery" -->
<!-- IF {PHP|cot_auth('plug', 'attacher', 'W')} -->
<div>
    {MARKETEDIT_FORM_ID|att_filebox('market', $this, 'gallery')}
</div>
<!-- ENDIF -->
```

### Превью в списке страниц (простой вывод)

```html
<!-- IF {LIST_ROW_ID|att_count('page', $this, '', 'images')} > 0 -->
<div>
    <a href="{LIST_ROW_URL}" title="{LIST_ROW_SHORTTITLE}">
        <img src="{LIST_ROW_ID|att_get('page', $this, '')|att_thumb($this, 200, 160, 'crop', false)}"
             alt="{LIST_ROW_SHORTTITLE}">
    </a>
</div>
<!-- ENDIF -->
```

### Превью через att_display

```html
<!-- IF {LIST_ROW_ID|att_count('page', $this, '', 'images')} > 0 -->
<div>
    <a href="{LIST_ROW_URL}" title="{LIST_ROW_SHORTTITLE}">
        {LIST_ROW_ID|att_display('page', $this, '', 'attacher.display.thumb', 'images', 1)}
    </a>
</div>
<!-- ENDIF -->
```

### Все файлы на странице

```html
<!-- IF {PAGE_ID|att_count('page', $this)} > 0 -->
<div data-att-display="all">
    <h3>{PHP.L.att_attachments}</h3>
    {PAGE_ID|att_display('page', $this, '', 'attacher.display', 'all')}
</div>
<!-- ENDIF -->
```

### Галерея изображений

```html
<!-- IF {PAGE_ID|att_count('page', $this, '', 'images')} > 0 -->
<div data-att-gallery="yourgallery">
    <h3>{PHP.L.att_gallery}</h3>
    {PAGE_ID|att_gallery('page', $this, '', 'attacher.gallery')}
</div>
<!-- ENDIF -->
```

### Список файлов для скачивания

```html
<!-- IF {PAGE_ID|att_count('page', $this, '', 'files')} > 0 -->
<div data-att-downloads="download">
    <h3>{PHP.L.att_downloads}</h3>
    {PAGE_ID|att_downloads('page', $this)}
</div>
<!-- ENDIF -->
```

### Виджет через iframe

```html
<!-- В page.edit.tpl -->
<!-- IF {PHP|cot_auth('plug', 'attacher', 'W')} -->
<div>
    {PAGEEDIT_FORM_ID|att_widget('page', $this)}
</div>
<!-- ENDIF -->

<!-- Ссылка на всплывающий виджет -->
<!-- IF {PHP|cot_auth('plug', 'attacher', 'W')} -->
<div>
    {PAGEADD_FORM_ID|att_widget('page', $this, '', 'attacher.link')}
</div>
<!-- ENDIF -->
```

### Прикрепление к постам форума

```html
<!-- forums.posts.tpl -->
<!-- IF {PHP|cot_auth('plug', 'attacher', 'W')} AND {FORUMS_POSTS_ROW_USERID} == {PHP.usr.id} -->
{FORUMS_POSTS_ROW_ID|att_widget('forums', $this, '', 'attacher.link')}
<!-- ENDIF -->

<!-- Вывод файлов -->
<!-- IF {FORUMS_POSTS_ROW_USERID} == {PHP.usr.id} -->
{FORUMS_POSTS_ROW_ID|att_display('forums', $this)}
<!-- ENDIF -->
```

## Функции API {#funkcii-api}

### Регистрация нового объекта

```php
att_fixNewPath($area, $item);
```

Привязывает ранее загруженные файлы к созданному объекту после его сохранения. Пример для модуля `page`:

```php
if (cot_auth('plug', 'attacher', 'W')) {
    if ($id) {
        att_fixNewPath('page', $id);
    }
}
```

### Удаление всех вложений

```php
att_remove_all($user_id = null, $area = null, $item_id = null);
```

Удаляет все файлы, связанные с объектом или пользователем. Пример для модуля `page`:

```php
if (cot_auth('plug', 'attacher', 'W')) {
    att_remove_all(null, 'page', $id);
}
```

### Создание загрузчика

```php
att_filebox($area, $item, $field = '', $type = 'all', $limit = -1, $tpl = 'attacher.filebox');
```

### Виджет в iframe

```php
att_widget($area, $item, $field = '', $tpl = 'attacher.widget', $width = '100%', $height = '200');
```

### Вывод файлов

```php
att_display(
    string $area,
    int|array $item,
    string $field = '',
    string $tpl = 'attacher.display',
    string $type = 'all',
    int|string $limit = 0,
    string $order = '',
    ?bool $admin = null
);
```

- `$limit` — `int|string`: `0` (без LIMIT), `N > 0` (LIMIT N), `''` из старых шаблонов приводится к `0` через `(int) $limit`.
- `$order` — только из белого списка `['att_order ASC', 'att_order DESC', 'att_id ASC', 'att_id DESC']`. Любая другая строка заменяется на `'att_order ASC'`.
- `$admin` — `true` (искать шаблон в админ-каталогах первым), `false` (только фронт), `null` (авто-детект по имени шаблона).

### Вывод галереи

```php
att_gallery($area, $item, $field = '', $tpl = 'attacher.gallery', $limit = 0, $order = '');
```

### Список загрузок

```php
att_downloads($area, $item, $field = '', $tpl = 'attacher.downloads', $limit = 0, $order = '');
```

### Подсчёт вложений

```php
att_count($area, $item, $field = '', $type = 'all');
```

Возвращает количество файлов, привязанных к объекту. `$field = '_all_'` — без фильтра по полю.

### Получение данных файла

```php
att_get($area, $item, $field = '', $column = '', $number = 'first');
```

Возвращает одну строку вложений. `$number` может быть `'first'`, `'rand'`, `'last'` или числом — выбирает одно из нескольких вложений.

### Получение всех данных

```php
att_get_all($area, $item, $field = '', $column = '');
```

Возвращает массив вложений.

### Создание миниатюры

```php
att_thumb($id, $width = 0, $height = 0, $frame = '', $watermark = true);
```

Возвращает путь к миниатюре. Параметр `$id` — ID вложения или массив (строка из `att_get`).

### Генерация HTML из изображения

```php
att_customizable_thumb($m);
```

Публичная функция обратного вызова, преобразует `<img>` в настраиваемый сниппет `<span><a title><picture><source><img></picture></a></span>`.

### Генерация HTML из BB-кода

```php
att_customizable_thumb_bbcode($m);
```

Преобразует `[att_image?...]` в HTML с поддержкой адаптивного `<picture>`.

## Параметры ключевых функций {#parametry-klyuchevyh-funkciy}

### att_filebox

| Параметр | Тип | По умолчанию | Что контролирует |
|----------|-----|--------------|------------------|
| `$area` | `string` | — | код модуля/плагина (`page`, `forums`, `market`) |
| `$item` | `int` | — | ID объекта (0 для новых объектов) |
| `$field` | `string` | `''` | метка поля для группировки файлов |
| `$type` | `string` | `'all'` | `all`, `image`, `file` (может быть списком через запятую) |
| `$limit` | `int` | `-1` | лимит файлов: `-1` — из настроек, `0` — без ограничения |
| `$tpl` | `string` | `'attacher.filebox'` | шаблон формы |

### att_display

| Параметр | Тип | По умолчанию | Влияние на SQL / шаблон |
|----------|-----|--------------|-------------------------|
| `$area` | `string` | — | `WHERE att_area = ?` |
| `$item` | `int\|array` | — | `att_item = ?` или `att_item IN(...)` |
| `$field` | `string` | `''` | `AND att_field = quote($field)`; `'_all_'` — без фильтра |
| `$tpl` | `string` | `'attacher.display'` | имя шаблона |
| `$type` | `string` | `'all'` | `'files'` → `AND att_img = 0`; `'images'` → `AND att_img = 1` |
| `$limit` | `int\|string` | `0` | `(int)$limit > 0` → `LIMIT N`; `''` → `0` |
| `$order` | `string` | `''` | только из белого списка: `att_order ASC/DESC`, `att_id ASC/DESC` |
| `$admin` | `?bool` | `null` | `true` — искать шаблон в админ-каталогах первым делом |

### att_count

| Параметр | Тип | По умолчанию | Влияние на SQL |
|----------|-----|--------------|----------------|
| `$area` | `string` | — | `WHERE att_area = ?` |
| `$item` | `int` | — | `att_item = ?` |
| `$field` | `string` | `''` | `AND att_field = quote($field)`; `'_all_'` — без фильтра |
| `$type` | `string` | `'all'` | `'files'` → `AND att_img = 0`; `'images'` → `AND att_img = 1` |

Результат кешируется в `static $a_cache` в пределах одного HTTP-запроса. Повторный вызов с теми же аргументами — без SQL.

### att_thumb

| Параметр | Тип | По умолчанию | Что контролирует |
|----------|-----|--------------|------------------|
| `$id` | `int\|array` | — | ID вложения или строка из `att_get` |
| `$width` | `int` | `0` (из настроек) | ширина миниатюры |
| `$height` | `int` | `0` (из настроек) | высота миниатюры |
| `$frame` | `string` | `''` (из настроек) | `crop`, `width`, `height`, `auto` |
| `$watermark` | `bool` | `true` | накладывать ли водяной знак |

## BB-код для изображений {#bb-kod}

Используйте `[att_image?...]` для вставки изображений в текст статьи, товара, поста:

```
[att_image?id=15]
[att_image?id=11&width=350]
[att_image?id=2&width=350&height=350]
[att_image?id=7&width=320&height=240&alt=Image alt]
[att_image?id=88&width=320&height=240&alt=Image alt&class=myclass]
```

Параметры:

- `id` — ID изображения (обязательный);
- `width`, `height` — размеры;
- `frame` — режим кадрирования (`crop`, `width`, `height`, `auto`);
- `alt` — альтернативный текст;
- `class` — CSS-класс для изображения.

BB-код обрабатывается функцией `att_customizable_thumb_bbcode()`, которая учитывает настройки режима вывода (ссылка, `<picture>`, чистое изображение), `title из alt`, удаление `style`, обёртку `<span>`.

## Совместимость с галереями {#sovmestimost-s-galereyami}

Плагин поддерживает внешние библиотеки галерей через настраиваемые шаблоны. Пример с Highslide:

```html
<!-- IF {PAGE_ID|att_count('page', $this, '', 'images')} > 0 -->
<div data-att-gallery="highslide" data-att-group="mygroup">
    <h3>{PHP.L.att_gallery}</h3>
    {PAGE_ID|att_gallery('page', $this, '', 'highslide.attacher.gallery')}
</div>
<!-- ENDIF -->
```

Шаблон `highslide.attacher.gallery.tpl` должен быть размещён в теме или в директории Highslide. Разметка внутри шаблона — на усмотрение разработчика темы, плагин передаёт в шаблон стандартные теги `ATTACHER_ROW_*`.

## Управление через админ-панель {#upravlenie-cherez-admin-panel}

- **Удаление копий** — удаление всех миниатюр для пересоздания с новыми настройками (например, после смены водяного знака).
- **Очистка** — удаление неприкреплённых файлов (garbage collection).
- **Управление файлами** — просмотр, редактирование, удаление загруженных файлов.

## Правила вызова в шаблонах CoTemplate {#pravila-cotemplate}

1. **`$this` в аргументах функции** — значение тега до вертикальной черты `|`. В `{PAGE_ID|att_count('page', $this, ...)}` подставляется значение `{PAGE_ID}`.
2. **`att_count()` кеширует результат** в `static $a_cache` в пределах одного HTTP-запроса. Повторный вызов с теми же аргументами — без SQL.
3. **`att_display()` не кеширует** — каждый вызов = один `SELECT`.
4. **В `<!-- IF -->` пробелы вокруг оператора обязательны.** `{X|att_count(...)} > 0` — работает по значению. `{X|att_count(...)}>0` — выражение вычисляется всегда как `true` (см. антипаттерны).
5. **`<!-- IF ... -->`, `<!-- ELSE -->`, `<!-- ENDIF -->`** — стандартные условные блоки CoTemplate. Вложенные `IF` разрешены.
6. **`$admin = true`** переключает `cot_tplfile` на поиск в админ-каталогах первым делом. Нужен, если `'admin'` в имени шаблона стоит не в позиции `[0]`/`[1]`.
7. **`LIMIT` в списках.** `att_display` без `$limit` в цикле `<!-- BEGIN: ROW -->` даёт один `SELECT` без ограничения на каждую строку списка. Передавайте `1`, `3`, `6` — сколько нужно в ячейку.
8. **`$order` — только из белого списка.** Значения: `'att_order ASC'`, `'att_order DESC'`, `'att_id ASC'`, `'att_id DESC'`. Любая другая строка молча заменится на `'att_order ASC'`.

## Хардненинг `attacher.upload.php` {#hardening}

Файл переписан под PHP 8.5+. Ниже — что закрыто и как.

### SSRF в URL-загрузке

Функция `att_ajax_handle_url_upload()` раньше принимала любой URL и делала `curl_exec()` до проверки прав. Закрыто:

- **Проверка прав** (`cot_auth('plug', 'attacher', 'W')`) — до любого сетевого запроса.
- **Проверка URL** через `att_is_safe_remote_url()`:
  - только схемы `http`/`https`;
  - запрет `userinfo` в URL;
  - резолвинг хоста через `gethostbynamel()`;
  - отсев IP из приватных (`10/8`, `172.16/12`, `192.168/16`), loopback (`127/8`), link-local (`169.254/16`), резервных и multicast-диапазонов через `FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE`.
- **Пиннинг IP** через `CURLOPT_RESOLVE`: между проверкой `gethostbynamel` и запросом cURL не перерезолвит хост (защита от DNS-rebinding).
- **Отключён follow redirect**: `CURLOPT_FOLLOWLOCATION => 0`. Redirect на `http://127.0.0.1/` больше не сработает.
- **Включена проверка TLS**: `CURLOPT_SSL_VERIFYPEER => 1`, `CURLOPT_SSL_VERIFYHOST => 2`. Раньше было `0`.
- **Ограничение протоколов**: `CURLOPT_PROTOCOLS` и `CURLOPT_REDIR_PROTOCOLS` — только `CURLPROTO_HTTP | CURLPROTO_HTTPS`.
- **Потоковая запись с ограничением размера**: `CURLOPT_WRITEFUNCTION` считает байты и прерывает загрузку при превышении `filesize` (или 100 MiB, если лимит в конфиге не задан). Раньше `curl_exec` тянул весь файл в память (`CURLOPT_RETURNTRANSFER => 1`).
- **Ошибки — только в `error_log`**, пользователю возвращается обезличенное «Failed to download file». Раньше текст cURL с URL уходил в JSON — раскрывал внутренние адреса и состояние портов.

### CSRF на мутациях AJAX-эндпоинта

Все `POST`/`PUT`/`PATCH`/`DELETE` требуют валидный Cotonti x-token `x` (`att_check_csrf()` через `hash_equals($sys['xk'], $given)`). Токен передаётся в URL (`?x=...`) или как POST-поле. Раньше проверки не было вовсе — любой сайт мог инициировать DELETE/POST от имени залогиненного пользователя.

### PHP object injection

Поле `param` содержит `base64_encode(serialize(...))`. Раньше `unserialize($decoded)` без ограничений — при контроле над `param` можно было подсунуть объект с `__wakeup`/`__destruct` и выполнить код. Сейчас:

```php
$params = @unserialize($decoded, ['allowed_classes' => false]);
if (!is_array($params)) {
    $params = [];
}
```

`allowed_classes: false` — `unserialize` вернёт только массивы/скаляры, объекты заменяются на `__PHP_Incomplete_Class`. Эксплойт невозможен.

### Path traversal в именах файлов

Раньше `mb_basename(stripslashes($name))` — не защищало от `..%2F` (после `stripslashes` `..%2F` станет `../`, `basename` уже не применится — имя уже прошло). Сейчас `att_safe_filename()`:

- убирает `/` и `\`;
- убирает управляющие символы и null-байты;
- в цикле вычищает `..` (пока не останется ни одного);
- убирает лидирующие точки (скрытые файлы, `.htaccess`);
- ограничивает длину 200 символами.

Применяется во всех точках записи имени: `att_ajax_get`, `att_ajax_handle_file_upload`, `att_ajax_handle_url_upload`.

### Обход Content-Range

Раньше при чанковой загрузке `Content-Range` читался как `preg_split(...)[3]` — бралось только `total`, а `append_file` вычислялся по `$file->size > filesize($uploaded_file)`. При манипуляции заголовком можно было записать больше байт, чем заявлено. Сейчас:

- `$contentRange` парсится регэкспом `/\/(\d+)$/` — берётся только число после `/`;
- проверка `$file_size > $file->size` — файл на диске не должен превышать заявленный total;
- повторная проверка `$file_size > $maxFileSize` — вне зависимости от `Content-Range`.

При превышении — файл удаляется, возвращается `error = 'abort'`.

### CORS-wildcard

Убраны заголовки:

```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: ...');
header('Access-Control-Allow-Headers: ...');
```

Эндпоинт аутентифицированный, `Allow-Origin: *` позволял любому сайту делать cross-origin запросы от имени пользователя (вместе с CSRF-отсутствием это давало полный произвол). Если действительно нужен cross-origin — добавляйте с конкретным доверенным Origin и `Access-Control-Allow-Credentials: true`.

### Проверка возврата `move_uploaded_file` / `rename`

Раньше результат `move_uploaded_file()` и `rename()` не проверялся. Сейчас при неудаче:

- файл удаляется из временного каталога;
- запись в БД откатывается (`$db->delete(...)`);
- пользователю возвращается `error`.

### Прочее

- `$_REQUEST['_method']` заменён на `$_POST['_method'] ?? $_GET['_method'] ?? $method` — не подхватывает cookie.
- GET-запрос списка вложений (`a=upload` без `_method`) требует авторизации (`cot_auth('plug', 'attacher', 'W')`). Раньше гость мог получить список чужих файлов, зная `area` + `item`.
- `$_POST['url']` проверяется как `string`.
- Все `$usr['id']` приводятся к `int` перед подстановкой в SQL.
- `att_path()` может вернуть `false` — все места использования проверяют результат.

## Антипаттерны {#antipatterny}

### att_display без IF

```html
<div class="gallery">
    {PAGE_ID|att_display('page', $this, '', 'attacher.gallery', 'images')}
</div>
```

Если у элемента нет вложений с `att_field=''` и `att_img=1`, `att_display` вернёт пустую строку. Обёртка `<div class="gallery">` останется в выводе пустой. Правильно — обернуть в `<!-- IF {PAGE_ID|att_count('page', $this, '', 'images')} > 0 -->…<!-- ENDIF -->`.

### att_display в списке без LIMIT

```html
<!-- BEGIN: PAGE_ROW -->
{PAGE_ROW_ID|att_display('page', $this, '', 'attacher.preview', 'images')}
<!-- END: PAGE_ROW -->
```

При 50 рядах выполнится 50 запросов, каждый вернёт все изображения. Правильно — передавать `$limit = 1` (или 3, 6).

### att_display с 'gallery' без предварительной загрузки через att_filebox

```html
{PAGE_ID|att_display('page', $this, 'gallery', 'attacher.gallery', 'images')}
```

В БД будут только те записи, которые загружены через `att_filebox('page', <ID>, 'gallery', ...)`. Если такой загрузки не было — пусто. Значения `att_field` попадают в БД только через `att_filebox` → AJAX upload (`'att_field' => $field` в `att_ajax_handle_file_upload` / `att_ajax_handle_url_upload`).

### echo att_display в PHP-коде модуля

```php
echo att_display('page', $id, '', 'attacher.display', 'images');
```

`echo` пишет в output buffer синхронно, в момент вызова. Модуль затем вызывает `$t->parse()/out()` — и содержимое шаблона выводится позже. Строка от `echo` окажется в HTML **раньше**, чем разметка шаблона, вне её — до `<!DOCTYPE>`, в `<head>`, между блоками, в зависимости от места вызова. На месте `{VAR}` в шаблоне — пусто. Правильно:

```php
$t->assign('PAGE_GALLERY', att_display('page', $id, '', 'attacher.display', 'images'));
// далее: $t->parse('MAIN'); $t->out('MAIN');  или return $t->text('MAIN');
```

### Разные параметры в att_count и att_display

```html
<!-- IF {X|att_count('page', $this, '_all_', 'images')} > 0 -->
    {X|att_display('page', $this, '', 'attacher.gallery', 'images')}
<!-- ENDIF -->
```

`att_count` считает по `att_field='_all_'`, `att_display` — по `att_field=''`. Условие может дать `true`, а `att_display` — пусто. Передавайте одинаковые `$field` и `$type`.

### att_display вместо att_get_all

Функция `att_display` возвращает готовый HTML (string), а не массив. Для получения массива — `att_get_all($area, $item, $field, $column)`.

### Отсутствие пробелов вокруг оператора в IF

```html
<!-- IF {PAGE_ID|att_count('page', $this, '', 'images')}>0 -->
```

`Cotpl_expr` токенизирует выражение по пробелам. `{PAGE_ID|att_count(...)}>0` без пробелов — один токен. `cotpl_parseArgument` возвращает его как строку, `Cotpl_expr::evaluate` подставляет значение → получается `"5>0"` или `"0>0"` — непустая строка. `(bool)` от неё = `true`. Ветка `IF` рендерится всегда. Правильно — с пробелами: `... > 0 -->`. Пробел перед `-->` не обязателен — `\s*` в регулярке `Cotpl_block::compile` допускает ноль символов.

### $order не из белого списка

```php
att_display(..., 'att_ext ASC');
```

Молча подставится дефолт `ORDER BY att_order ASC`. Никакой ошибки нет, но ожидаемого порядка тоже нет. Допустимые значения: `att_order ASC`, `att_order DESC`, `att_id ASC`, `att_id DESC`.

### $admin = true во фронт-шаблоне

`cot_tplfile` с `$admin=true` сканирует админ-каталоги (`themes/admin/{admintheme}/plugins/attacher/`) первыми. Если там есть шаблон с нужным именем — он и будет использован на фронте. Разметка админ-шаблона почти всегда отличается от фронт-шаблона — на фронте будет чужой блок. `$admin=true` — только в админ-шаблонах. Для фронта не передавать (`null` → авто-детект → `false` для имён без `admin` в `[0]`/`[1]`).

### att_display для админ-шаблона без $admin=true

`cot_tplfile` при `$admin=null` проверяет только `$base[0]` и `$base[1]`. Для `'attacher.display.cotcp-admin.list'` `'admin'` стоит на позиции `[2]` (внутри `cotcp-admin`) — авто-детект его не увидит. Если админ-шаблон с таким именем, нужно передать `$admin=true` 8-м аргументом.

## Примечания и ограничения {#primechaniya}

- **Несовместимость**: плагин несовместим с `attach2` и модулем `file`. Отключить оба перед установкой Attacher.
- **Галереи**: сторонние библиотеки (Highslide и т. п.) поддерживаются через кастомные шаблоны.
- **Загрузка по URL**: реализована через JavaScript Load Image на клиенте + `att_ajax_handle_url_upload` на сервере. С хардненингом 2026 работает только с публичными HTTP(S)-адресами; адреса из локальной сети, loopback и link-local отклоняются.
- **Локализация**: файлы `attacher.<lang>.lang.php`.
- **Значения `att_field`**: произвольные строки, попадающие в БД через `att_filebox($area, $item, $field, ...)`. Встроенного списка значений нет.
- **Проверка существующих `att_field` в БД**:
  ```sql
  SELECT DISTINCT att_area, att_field, COUNT(*) FROM cot_attacher GROUP BY att_area, att_field;
  ```
- **`$limit = ''` в старых шаблонах**: работает благодаря `int|string $limit = 0` и `(int) '' = 0`. Совместимость сохранена. В новом коде лучше передавать `0` явно — читается чище.
- **Удаление файлов из формы**: URL удаления, который отдаёт плагин, содержит `&_method=DELETE&x=TOKEN` в GET-строке. Обработчик `attacher.upload.php` читает `_method` сначала из `$_POST`, затем из `$_GET`. CSRF-токен `x` проверяется для всех мутаций.

---

**webitproff, 2026**


___
> ⚠️ Всё, что изложено ниже, - это старое описание.

> ⚠️ На данный момент нет времени сверять и актуализировать.

> ⚠️ Пока оставляю для общего понимания, но не принимать за чистую монету. 
___


___
> ⚠️ Everything below is an old description.

> ⚠️ At the moment, there is no time to check and update.

> ⚠️ For now, I'm leaving it for general understanding, but not to take it at face value.
___


# Attacher Plugin for Cotonti Siena 0.9.26+, PHP 8.4+

**Attacher** is a powerful and versatile extension for Cotonti CMF (version 0.9.26+, PHP 8.4+), designed for managing the upload, processing, and display of files and images. The plugin allows attaching files to various system objects, such as articles, forum posts, product cards, or custom modules, and provides flexible tools for working with media files. It supports asynchronous uploads, image processing, integration with visual editors, and display as galleries or download lists.

<img src="https://raw.githubusercontent.com/webitproff/cot-Attacher-Roffun/refs/heads/main/attacher.png" alt="Attacher Plugin for Cotonti Siena 0.9.26+, PHP 8.4+">

## [Help & Support](https://abuyfile.com/ru/forums/cotonti/custom/plugs/attacher)

## Key Features

1. **Flexible File Upload**:
   - **Multiple Upload Methods**: Supports file uploads via selection, drag-and-drop, or URL. Images can be uploaded directly from external websites by specifying their URL, which is especially convenient when creating or editing articles, product cards, forum posts, or other objects.
   - **Multi-Upload**: Simultaneous upload of multiple files with configurable limits on quantity.
   - **Chunked Uploads**: File uploads in chunks to bypass server `$_POST` limits (default 2 MB).
   - **File Validation**: Filtering by file extensions (e.g., `jpg`, `png`, `gif`, `webp`, `pdf`) and MIME types.
   - **Supported Formats**: Images, audio, video, archives, documents.

2. **Attachment Management**:
   - **Object Binding**: Attach files to any Cotonti objects (pages, posts, products, comments) with module, object, and field specification.
   - **Title Editing**: Users can set titles for each file via the interface.
   - **File Replacement**: Replace existing files without breaking bindings.
   - **Sorting**: Reorder attachments using drag-and-drop.
   - **Statistics**: Track download counts and preserve original file names.

3. **Image Processing**:
   - **Thumbnails**: Automatic creation of small and large thumbnails with customizable sizes and framing modes (`crop`, `width`, `height`, `auto`).
   - **JPEG Conversion**: Optional conversion of all uploaded images to JPEG with adjustable compression quality.
   - **Watermarks and Textures**: Apply watermarks and background textures with the ability to regenerate copies upon configuration changes.
   - **Adaptive Output**: Transform images into `<picture>` tags for responsive display (large thumbnail for desktops, small for mobiles).
   - **On-the-Fly Processing**: Remove `style` attributes, convert images to links, or wrap in `<span>` containers with customizable classes.
   - **BB-Code**: Support for `[att_image?...]` to embed images in content with size, framing, and class parameters.

4. **Form and Editor Integration**:
   - **Upload Widget**: Interactive widget (`att_filebox`) for forms used in creating/editing objects like articles, product cards, or forum posts.
   - **URL Upload**: Direct image upload from external sites via a URL input field, simplifying media addition without downloading to the device.
   - **Preview**: Display image previews before uploading.
   - **Editor Integration**: Auto-generate buttons to insert images into visual editors with size settings applied.
   - **Galleries and Lists**: Display files as galleries (`att_gallery`), download lists (`att_downloads`), or general lists (`att_display`).

5. **Security and Limits**:
   - **Permission Checks**: Restrict upload, edit, and delete actions based on user permissions.
   - **Limits**: Configurable restrictions on file size, total storage space, and number of attachments.
   - **Cleanup**: Automatic removal of temporary files (garbage collection) for unsaved forms.
   - **Security Token**: Use of `x` token to secure upload and delete operations.

6. **Flexibility and Customization**:
   - **Templates**: Customizable templates for upload interface and file display.
   - **Configuration**: Detailed settings via admin panel for file types, sizes, thumbnails, and watermarks.
   - **Localization**: Support for multiple languages via language files (`attacher.<lang>.lang.php`).
   - **Gallery Compatibility**: Integration with external gallery libraries (e.g., Highslide) via customizable templates.

## System Requirements

- **Cotonti**: Siena 0.9.26+ (latest version recommended).
- **PHP**: 8.4+ (compatible with 7.1+ for earlier versions).
- **Libraries**:
  - jQuery and jQuery UI for the interface.
  - jQuery File Upload for asynchronous file uploads.
  - JavaScript Load Image for image processing and URL uploads.
  - JavaScript Templates for dynamic template rendering.
  - Canvas-to-Blob for image conversion.
  - TableDnD for drag-and-drop attachment sorting.
- **Browsers**: Modern browsers (Chrome, Firefox, Safari, Edge). Limited support for IE8+ via iframe transport.
- **Conflicts**: Incompatible with `attach2` plugin and `file` module—disable them before use.
- **Database**: Uses a dedicated table for file metadata.

## Installation

1. Download the plugin archive and extract it.
2. Rename the folder to `attacher` and place it in the `plugins` directory of your site.
3. Go to the Cotonti admin panel: `Administration → Extensions → Image and File Upload` and install the plugin.
4. Create a folder for file uploads (default: `datas/attacher`) and set permissions to `CHMOD 755` or `777`. If using a custom directory, configure it accordingly.
5. Configure plugin settings in the admin panel: `Administration → Configuration → Plugins → Attacher`.

## Configuration

### General Settings
- **File Directory**: Path to the upload folder (default: `datas/attacher`).
- **File Name Prefix**: Prefix for file names (e.g., `att_`).
- **Allowed File Types**: List of extensions (e.g., `jpg,png,gif,pdf,zip,mp3,mp4`).
- **Max Attachments**: Limit on the number of files per object (e.g., 5, 10, or unlimited).
- **Allowed MIME Types**: MIME types for file selection filtering (e.g., `image/*,application/pdf`).
- **Chunked Upload**: Chunk size in bytes (e.g., 1 MB) to bypass `$_POST` limits.
- **Max File Size**: Limit on individual file size in bytes (e.g., 10485760 for 10 MB).
- **Total Storage Space**: Storage limit per user in bytes (e.g., 104857600 for 100 MB).
- **Auto-Upload**: Enable/disable automatic uploads after file selection (hides "Start" and "Cancel" buttons).
- **Sequential Upload**: Choose between sequential or parallel file uploads.
- **CSS Inclusion**: Enable plugin styles (`fileupload.css` and jQuery UI).

### Image Settings
- **Image Scaling**: Enable scaling of uploaded images to specified sizes.
- **Max Width/Height**: Maximum image dimensions (e.g., 1920x1080).
- **JPEG Conversion**: Convert all images to JPEG.
- **JPEG Quality**: Compression level in percentage (e.g., 80%).

### Thumbnail Settings
- **Show Thumbnails**: Enable/disable thumbnail display in the interface.
- **Upscale Small Images**: Enlarge images smaller than thumbnail sizes.
- **Thumbnail Width/Height**: Dimensions for small (e.g., 200x160) and large (e.g., 800x600) thumbnails.
- **Framing Mode**: `crop` (crop), `width` (fit width), `height` (fit height), `auto` (preserve aspect ratio).

### Watermarks and Textures
- **Watermark**: Path to the watermark file (e.g., `datas/attacher/watermark.png`).
- **Min Width/Height**: Minimum image dimensions for watermark application (e.g., 300x200).
- **Background Texture**: Path to the texture file (e.g., `datas/attacher/texture.jpg`).
- **Texture Padding**: Padding size around images for texture fill (e.g., 10 pixels).

### Article Image Processing
- **Output Mode**: Display images as links, `<picture>`, or plain images.
- **Title from Alt**: Create `title` attribute from `alt` for links.
- **Remove Style**: Strip `style` attribute for image alignment.
- **Adaptive `<picture>`**: Use large and small thumbnails for different devices.
- **`<span>` Container**: Wrap images in a `<span>` with customizable CSS classes.

## Plugin Templates

The plugin uses three main templates for the interface and file display:

1. **attacher.filebox.tpl**:
   - **Purpose**: File upload form integrated into create/edit pages (e.g., articles, product cards).
   - **Content**:
     - File selection field and drag-and-drop area.
     - URL input field for uploading images from external sites (e.g., `https://example.com/image.jpg`) with an "Upload by URL" button.
     - Control buttons: "Add File," "Start Upload," "Cancel," "Delete," "Select All."
     - Progress bar for upload status.
     - Table for previewing uploaded files with thumbnails.
   - **Configuration**: Includes `attConfig` JavaScript object with upload settings (extensions, MIME types, limits).
   - **Libraries**: Integrates jQuery File Upload, JavaScript Load Image, and TableDnD.

2. **attacher.files.tpl**:
   - **Purpose**: Full-fledged file management page, used in iframes or standalone interfaces.
   - **Content**:
     - Upload form with file selection, drag-and-drop, and URL input.
     - List of uploaded files with options to edit titles, replace, or delete.
     - Support for multi-uploads and file table display.
   - **Styles and Scripts**: Includes `fileupload.css`, jQuery UI, jQuery, JavaScript Templates, Canvas-to-Blob, and other libraries.

3. **attacher.templates.tpl**:
   - **Purpose**: Templates for dynamic rendering of files during upload/download.
   - **Content**:
     - `template-upload`: Displays files during upload with a progress bar, file name, and cancel button.
     - `template-download`: Shows uploaded files with thumbnails, titles, delete/replace buttons, and editor insertion options.
     - Supports image previews and integration with visual editors.
   - **Format**: Uses `text/x-tmpl` for rendering via JavaScript Templates.

All templates are customizable and support localization via language variables (`{PHP.L.<key>}`).

## Template Integration

### Adding the Upload Widget
Use the `att_filebox` function to add the upload form to create/edit pages (e.g., articles, product cards):

```html
<!-- In page.add.tpl (for new pages) -->
<!-- IF {PHP|cot_auth('plug', 'attacher', 'W')} -->
<div>
    {PHP|att_filebox('page', 0)}
</div>
<!-- ENDIF -->

<!-- In page.edit.tpl (for existing pages) -->
<!-- IF {PHP|cot_auth('plug', 'attacher', 'W')} -->
<div>
    {PAGEEDIT_FORM_ID|att_filebox('page', $this)}
</div>
<!-- ENDIF -->
```

### Displaying Images in Lists
For page lists (`page.list.tpl` or `page.enum.tpl`):

```html
<!-- Simple image output -->
<!-- IF {LIST_ROW_ID|att_count('page', $this, '', 'images')} > 0 -->
<div>
    <a href="{LIST_ROW_URL}" title="{LIST_ROW_SHORTTITLE}">
        <img src="{LIST_ROW_ID|att_get('page', $this, '')|att_thumb($this, 200, 160, 'crop', false)}" alt="{LIST_ROW_SHORTTITLE}">
    </a>
</div>
<!-- ENDIF -->

<!-- Using att_display -->
<!-- IF {LIST_ROW_ID|att_count('page', $this, '', 'images')} > 0 -->
<div>
    <a href="{LIST_ROW_URL}" title="{LIST_ROW_SHORTTITLE}">
        {LIST_ROW_ID|att_display('page', $this, '', 'attacher.display.thumb', 'images', 1)}
    </a>
</div>
<!-- ENDIF -->
```

### Displaying on Pages (`page.tpl`)
- All files:
  ```html
  <!-- IF {PAGE_ID|att_count('page', $this)} > 0 -->
  <div data-att-display="all">
      <h3>{PHP.L.att_attachments}</h3>
      {PAGE_ID|att_display('page', $this, '', 'attacher.display', 'all')}
  </div>
  <!-- ENDIF -->
  ```
- Image gallery:
  ```html
  <!-- IF {PAGE_ID|att_count('page', $this, '', 'images')} > 0 -->
  <div data-att-gallery="yourgallery">
      <h3>{PHP.L.att_gallery}</h3>
      {PAGE_ID|att_gallery('page', $this, '', 'attacher.gallery')}
  </div>
  <!-- ENDIF -->

  <!-- With Highslide (separate plugin) -->
  <!-- IF {PAGE_ID|att_count('page', $this, '', 'images')} > 0 -->
  <div data-att-gallery="highslide" data-att-group="mygroup">
      <h3>{PHP.L.att_gallery}</h3>
      {PAGE_ID|att_gallery('page', $this, '', 'highslide.attacher.gallery')}
  </div>
  <!-- ENDIF -->
  ```
- Download list:
  ```html
  <!-- IF {PAGE_ID|att_count('page', $this, '', 'files')} > 0 -->
  <div data-att-downloads="download">
      <h3>{PHP.L.att_downloads}</h3>
      {PAGE_ID|att_downloads('page', $this)}
  </div>
  <!-- ENDIF -->
  ```

### Iframe Widget
Use the `att_widget` function for iframe-based uploads:

```html
<!-- In page.edit.tpl -->
<!-- IF {PHP|cot_auth('plug', 'attacher', 'W')} -->
<div>
    {PAGEEDIT_FORM_ID|att_widget('page', $this)}
</div>
<!-- ENDIF -->

<!-- Link to popup widget -->
<!-- IF {PHP|cot_auth('plug', 'attacher', 'W')} -->
<div>
    {PAGEADD_FORM_ID|att_widget('page', $this, '', 'attacher.link')}
</div>
<!-- ENDIF -->
```

### Attaching to Forum Posts (`forums.posts.tpl`)
```html
<!-- IF {PHP|cot_auth('plug', 'attacher', 'W')} AND {FORUMS_POSTS_ROW_USERID} == {PHP.usr.id} -->
{FORUMS_POSTS_ROW_ID|att_widget('forums', $this, '', 'attacher.link')}
<!-- ENDIF -->

<!-- File display -->
<!-- IF {FORUMS_POSTS_ROW_USERID} == {PHP.usr.id} -->
{FORUMS_POSTS_ROW_ID|att_display('forums', $this)}
<!-- ENDIF -->
```

### BB-Code for Images
Use the `[att_image?...]` BB-code to embed images in content (e.g., articles or product cards):

```
[att_image?id=15]
[att_image?id=11&width=350]
[att_image?id=2&width=350&height=350]
[att_image?id=7&width=320&height=240&alt=Image alt]
[att_image?id=88&width=320&height=240&alt=Image alt&class=myclass]
```

Parameters:
- `id`: Image ID (required).
- `width`, `height`: Image dimensions.
- `frame`: Framing mode (`crop`, `width`, `height`, `auto`).
- `alt`: Alternative text.
- `class`: CSS class for the image.

## API Functions

1. **Register New Object**:
   ```php
   att_fixNewPath($area, $item);
   ```
   Binds files to a new object after creation.
   Example for `page` module:
   ```php
   if (cot_auth('plug', 'attacher', 'W')) {
       if ($id) {
           att_fixNewPath('page', $id);
       }
   }
   ```

2. **Remove All Attachments**:
   ```php
   att_remove_all($user_id = null, $area = null, $item_id = null);
   ```
   Deletes all files associated with an object or user.
   Example for `page` module:
   ```php
   if (cot_auth('plug', 'attacher', 'W')) {
       att_remove_all(null, 'page', $id);
   }
   ```

3. **Create Upload Widget**:
   ```php
   att_filebox($area, $item, $field = '', $type = 'all', $limit = -1, $tpl = 'attacher.filebox');
   ```
   Parameters:
   - `$area`: Module/plugin (e.g., `page`, `forums`).
   - `$item`: Object ID (or 0 for new objects).
   - `$field`: Field name for file grouping.
   - `$type`: File type (`all`, `image`, `file`).
   - `$limit`: File quantity limit (-1 for group settings, 0 for unlimited).
   - `$tpl`: Template (`attacher.filebox` by default).

4. **Iframe Widget**:
   ```php
   att_widget($area, $item, $field = '', $tpl = 'attacher.widget', $width = '100%', $height = '200');
   ```
   Creates an iframe or link to a popup uploader.

5. **Display Files**:
   ```php
   att_display($area, $item, $field = '', $tpl = 'attacher.display', $type = 'all', $limit = 0, $order = '');
   ```
   Displays all files or a specific type (e.g., images only).

6. **Display Gallery**:
   ```php
   att_gallery($area, $item, $field = '', $tpl = 'attacher.gallery', $limit = 0, $order = '');
   ```
   Creates an image gallery with support for external libraries (e.g., Highslide).

7. **Count Attachments**:
   ```php
   att_count($area, $item, $field = '', $type = 'all');
   ```
   Returns the number of files attached to an object. Use `$field = '_all_'` for total count.

8. **Download List**:
   ```php
   att_downloads($area, $item, $field = '', $tpl = 'attacher.downloads', $limit = 0, $order = '');
   ```
   Generates a list of files for downloading.

9. **Create Thumbnail**:
   ```php
   att_thumb($id, $width = 0, $height = 0, $frame = '', $watermark = true);
   ```
   Generates a thumbnail URL with specified parameters.

10. **Get File Data**:
    ```php
    att_get($area, $item, $field = '', $column = '', $number = 'first');
    ```
    Retrieves file data (e.g., path, name, size).

11. **Generate Image HTML**:
    ```php
    att_customizable_thumb($m);
    ```
    Transforms an `<img>` tag into a customizable HTML snippet (`<span><a><picture><source><img></picture></a></span>`).

12. **Generate HTML from BB-Code**:
    ```php
    att_customizable_thumb_bbcode($m);
    ```
    Converts `[att_image?...]` BB-code into HTML with support for adaptive `<picture>`.

## Admin Panel Management
- **Delete Copies**: Remove all thumbnails to regenerate with new settings (e.g., updated watermark).
- **Cleanup**: Delete unattached files (garbage collection) via the admin panel.
- **File Management**: View, edit, and delete uploaded files.

## Notes
- **Conflicts**: Incompatible with `attach2` and `file` module. Disable them before use.
- **Galleries**: Supports external libraries (e.g., Highslide) via custom templates.
- **URL Upload**: Implemented via JavaScript Load Image, allowing image uploads from external sites without downloading to the device.
- **Localization**: Supported via language files (`attacher.<lang>.lang.php`).
___

# Плагин attacher for CMF Cotonti Siena 0.9.26+, PHP 8.4+


**Attacher** — мощное и универсальное расширение для Cotonti CMF (версия 0.9.26+, PHP 8.4+), разработанное для управления загрузкой, обработкой и отображением файлов и изображений. Плагин позволяет прикреплять файлы к различным объектам системы, таким как статьи, посты форума, карточки товаров или пользовательские модули, и предоставляет гибкие инструменты для работы с медиафайлами. Поддерживает асинхронную загрузку, обработку изображений, интеграцию с визуальными редакторами и отображение в виде галерей или списков для скачивания.

## Основные возможности

1. **Гибкая загрузка файлов**:
   - **Множественные способы загрузки**: Поддержка загрузки через выбор файлов, drag-and-drop или по URL. Возможность загружать изображения напрямую с других сайтов, указав их URL, что особенно удобно при создании или редактировании статей, карточек товаров, постов форума и других объектов.
   - **Мультизагрузка**: Одновременная загрузка нескольких файлов с настраиваемыми ограничениями по количеству.
   - **Чанки**: Загрузка файлов частями (чанками) для обхода серверных ограничений `$_POST` (по умолчанию 2 МБ).
   - **Проверка файлов**: Фильтрация по расширениям (например, `jpg`, `png`, `gif`, `webp`, `pdf`) и MIME-типам.
   - **Поддерживаемые форматы**: Изображения, аудио, видео, архивы, документы.

2. **Управление вложениями**:
   - **Привязка к объектам**: Возможность прикреплять файлы к любым объектам Cotonti (страницы, посты, товары, комментарии) с указанием модуля, объекта и поля.
   - **Редактирование заголовков**: Пользователи могут задавать заголовки для каждого файла через интерфейс.
   - **Замена файлов**: Поддержка замены существующих файлов новыми без удаления привязки.
   - **Сортировка**: Переупорядочивание вложений с помощью drag-and-drop.
   - **Статистика**: Подсчёт количества загрузок и сохранение оригинальных имён файлов.

3. **Обработка изображений**:
   - **Миниатюры**: Автоматическое создание маленьких и больших миниатюр с настраиваемыми размерами и режимами кадрирования (`crop`, `width`, `height`, `auto`).
   - **Конвертация в JPEG**: Опциональное преобразование всех загружаемых изображений в JPEG с настройкой качества сжатия.
   - **Водяные знаки и текстуры**: Наложение водяных знаков и фоновых текстур с возможностью пересоздания копий при изменении настроек.
   - **Адаптивный вывод**: Преобразование изображений в тег `<picture>` для адаптивного отображения (большая миниатюра для десктопов, маленькая для мобильных).
   - **Обработка "налету"**: Удаление атрибута `style`, преобразование изображений в ссылки или создание контейнеров `<span>` с настраиваемыми классами.
   - **BB-код**: Поддержка `[att_image?...]` для встраивания изображений в текст с параметрами размера, кадрирования и классов.

4. **Интеграция с формами и редакторами**:
   - **Виджет загрузки**: Интерактивный виджет (`att_filebox`) для форм создания/редактирования объектов, таких как статьи, карточки товаров, посты форума.
   - **Загрузка по URL**: Прямая загрузка изображений с внешних сайтов через поле ввода URL, упрощающая добавление медиа без скачивания на устройство.
   - **Предпросмотр**: Отображение предварительного просмотра изображений перед загрузкой.
   - **Вставка в редактор**: Автоматическое создание кнопок для вставки изображений в визуальный редактор с учётом настроек размера.
   - **Галереи и списки**: Вывод файлов в виде галерей (`att_gallery`), списков для скачивания (`att_downloads`) или общего списка (`att_display`).

5. **Безопасность и ограничения**:
   - **Проверка прав**: Ограничение доступа к загрузке, редактированию и удалению файлов на основе прав пользователя.
   - **Лимиты**: Настраиваемые ограничения на размер одного файла, общее дисковое пространство и количество вложений.
   - **Очистка**: Автоматическое удаление временных файлов (garbage collection) для несохранённых форм.
   - **Токен безопасности**: Использование токена `x` для защиты операций загрузки и удаления.

6. **Гибкость и кастомизация**:
   - **Шаблоны**: Настраиваемые шаблоны для интерфейса загрузки и отображения файлов.
   - **Конфигурация**: Подробные настройки через админ-панель для управления типами файлов, размерами, качеством и стилями.
   - **Локализация**: Поддержка многоязычности через языковые файлы (`attacher.<lang>.lang.php`).
   - **Совместимость с галереями**: Интеграция с внешними библиотеками галерей (например, Highslide) через настраиваемые шаблоны.

## Системные требования

- **Cotonti**: Siena 0.9.26+ (рекомендуется последняя версия).
- **PHP**: 8.4+ 
- **Библиотеки**:
  - jQuery и jQuery UI для интерфейса.
  - jQuery File Upload для асинхронной загрузки файлов.
  - JavaScript Load Image для обработки изображений и загрузки по URL.
  - JavaScript Templates для динамического рендеринга шаблонов.
  - Canvas-to-Blob для преобразования изображений.
  - TableDnD для сортировки вложений через drag-and-drop.
- **Браузеры**: Современные браузеры (Chrome, Firefox, Safari, Edge). Ограниченная поддержка IE8+ через iframe-транспорт.
- **Конфликты**: Плагин несовместим с `attach2` и модулем `file` — их необходимо отключить.
- **База данных**: Плагин использует отдельную таблицу для хранения информации о файлах.

## Установка

1. Скачайте архив плагина и распакуйте его.
2. Переименуйте папку в `attacher` и поместите её в директорию `plugins` вашего сайта.
3. Перейдите в админ-панель Cotonti: `Управление сайтом → Расширения → Загрузка изображений и файлов` и установите плагин.
4. Создайте папку для загрузки файлов (по умолчанию `datas/attacher`) и установите права `CHMOD 755` или `777`. Если используется другой каталог, настройте его в конфигурации.
5. Настройте параметры плагина в админ-панели: `Управление сайтом → Конфигурация → Плагины → Attacher`.

## Конфигурация

### Основные параметры
- **Директория для файлов**: Путь к папке загрузки (по умолчанию `datas/attacher`).
- **Префикс имён файлов**: Приставка для имён файлов (например, `att_`).
- **Разрешённые типы файлов**: Список расширений (например, `jpg,png,gif,pdf,zip,mp3,mp4`).
- **Макс. число вложений**: Ограничение на количество файлов в одном объекте (например, 5, 10 или неограниченно).
- **Допустимые MIME-типы**: MIME-типы для фильтрации в окне выбора файлов (например, `image/*,application/pdf`).
- **Загрузка чанками**: Размер чанка в байтах (например, 1 МБ) для обхода ограничений `$_POST`.
- **Макс. размер файла**: Ограничение на размер одного файла в байтах (например, 10485760 для 10 МБ).
- **Общее дисковое пространство**: Лимит хранилища для пользователя в байтах (например, 104857600 для 100 МБ).
- **Автоматическая загрузка**: Включение/выключение автоматической загрузки после выбора файлов (скрывает кнопки "Начать" и "Отменить").
- **Последовательная загрузка**: Выбор между последовательной и параллельной загрузкой файлов.
- **Подключение CSS**: Включение стилей плагина (`fileupload.css` и jQuery UI).

### Настройка изображений
- **Уменьшение изображений**: Включение масштабирования загружаемых изображений.
- **Ширина/высота уменьшения**: Максимальные размеры изображения (например, 1920x1080).
- **Конвертация в JPEG**: Автоматическое преобразование всех изображений в JPEG.
- **Качество JPEG**: Степень сжатия в процентах (например, 80%).

### Настройка миниатюр
- **Показывать миниатюры**: Включение/выключение отображения миниатюр в интерфейсе.
- **Увеличение маленьких изображений**: Увеличение изображений, меньших заданных размеров миниатюр.
- **Ширина/высота миниатюр**: Размеры маленькой (например, 200x160) и большой (например, 800x600) миниатюр.
- **Режим кадрирования**: `crop` (обрезка), `width` (по ширине), `height` (по высоте), `auto` (сохранение пропорций).

### Водяные знаки и текстуры
- **Водяной знак**: Путь к файлу водяного знака (например, `datas/attacher/watermark.png`).
- **Мин. ширина/высота**: Минимальные размеры изображения для наложения водяного знака (например, 300x200).
- **Фоновая текстура**: Путь к файлу текстуры (например, `datas/attacher/texture.jpg`).
- **Отступ для текстуры**: Размер отступа вокруг изображения для заполнения текстурой (например, 10 пикселей).

### Обработка изображений в статьях
- **Режим вывода**: Варианты отображения изображений (как ссылка, `<picture>`, чистое изображение).
- **Title из alt**: Создание атрибута `title` из `alt` для ссылок.
- **Удаление style**: Удаление атрибута `style` для выравнивания изображений.
- **Адаптивный `<picture>`**: Использование большой и маленькой миниатюр для разных устройств.
- **Контейнер `<span>`**: Создание родительского контейнера с настраиваемыми CSS-классами.

## Шаблоны плагина

Плагин использует три основных шаблона для интерфейса и отображения файлов:

1. **attacher.filebox.tpl**:
   - **Назначение**: Форма загрузки файлов, интегрируемая в страницы создания/редактирования (например, статьи, карточки товаров).
   - **Содержимое**:
     - Поле выбора файлов и область drag-and-drop.
     - Поле ввода URL для загрузки изображений с других сайтов (например, `https://example.com/image.jpg`) с кнопкой "Загрузить по URL".
     - Кнопки управления: "Добавить файл", "Начать загрузку", "Отменить", "Удалить", "Выделить все".
     - Прогресс-бар для отображения процесса загрузки.
     - Таблица для предпросмотра загружаемых файлов с миниатюрами.
   - **Конфигурация**: Подключает объект `attConfig` с параметрами загрузки (расширения, MIME-типы, лимиты).
   - **Библиотеки**: Интегрирует jQuery File Upload, JavaScript Load Image и TableDnD.

2. **attacher.files.tpl**:
   - **Назначение**: Полноценная страница управления загрузками, используемая в iframe или отдельном интерфейсе.
   - **Содержимое**:
     - Форма загрузки с аналогичным интерфейсом: выбор файлов, drag-and-drop, поле URL.
     - Список загруженных файлов с возможностью редактирования заголовков, замены и удаления.
     - Поддержка мультизагрузки и отображения файлов в таблице.
   - **Стили и скрипты**: Подключает `fileupload.css`, jQuery UI, jQuery, JavaScript Templates, Canvas-to-Blob и другие библиотеки.

3. **attacher.templates.tpl**:
   - **Назначение**: Шаблоны для динамического рендеринга файлов в процессе загрузки/скачивания.
   - **Содержимое**:
     - Шаблон `template-upload`: Отображает файлы в процессе загрузки с прогресс-баром, именем файла и кнопками отмены.
     - Шаблон `template-download`: Показывает загруженные файлы с миниатюрами, заголовками, кнопками удаления/замены и вставки в редактор.
     - Поддержка предварительного просмотра изображений и интеграции с визуальными редакторами.
   - **Формат**: Использует `text/x-tmpl` для рендеринга через JavaScript Templates.

Все шаблоны настраиваемы и поддерживают локализацию через языковые переменные (`{PHP.L.<key>}`).

## Интеграция в шаблоны

### Добавление загрузчика
Функция `att_filebox` добавляет форму загрузки в страницы создания/редактирования (например, статьи, карточки товаров):

```html
<!-- В page.add.tpl (для новых страниц) -->
<!-- IF {PHP|cot_auth('plug', 'attacher', 'W')} -->
<div>
    {PHP|att_filebox('page', 0)}
</div>
<!-- ENDIF -->

<!-- В page.edit.tpl (для существующих страниц) -->
<!-- IF {PHP|cot_auth('plug', 'attacher', 'W')} -->
<div>
    {PAGEEDIT_FORM_ID|att_filebox('page', $this)}
</div>
<!-- ENDIF -->
```

### Вывод изображений в списках
Для списков страниц (`page.list.tpl` или `page.enum.tpl`):

```html
<!-- Простой вывод изображения -->
<!-- IF {LIST_ROW_ID|att_count('page', $this, '', 'images')} > 0 -->
<div>
    <a href="{LIST_ROW_URL}" title="{LIST_ROW_SHORTTITLE}">
        <img src="{LIST_ROW_ID|att_get('page', $this, '')|att_thumb($this, 200, 160, 'crop', false)}" alt="{LIST_ROW_SHORTTITLE}">
    </a>
</div>
<!-- ENDIF -->

<!-- Через att_display -->
<!-- IF {LIST_ROW_ID|att_count('page', $this, '', 'images')} > 0 -->
<div>
    <a href="{LIST_ROW_URL}" title="{LIST_ROW_SHORTTITLE}">
        {LIST_ROW_ID|att_display('page', $this, '', 'attacher.display.thumb', 'images', 1)}
    </a>
</div>
<!-- ENDIF -->
```

### Вывод на странице (`page.tpl`)
- Все файлы:
  ```html
  <!-- IF {PAGE_ID|att_count('page', $this)} > 0 -->
  <div data-att-display="all">
      <h3>{PHP.L.att_attachments}</h3>
      {PAGE_ID|att_display('page', $this, '', 'attacher.display', 'all')}
  </div>
  <!-- ENDIF -->
  ```
- Галерея изображений:
  ```html
  <!-- IF {PAGE_ID|att_count('page', $this, '', 'images')} > 0 -->
  <div data-att-gallery="yourgallery">
      <h3>{PHP.L.att_gallery}</h3>
      {PAGE_ID|att_gallery('page', $this, '', 'attacher.gallery')}
  </div>
  <!-- ENDIF -->

  <!-- С Highslide (отдельный плагин) -->
  <!-- IF {PAGE_ID|att_count('page', $this, '', 'images')} > 0 -->
  <div data-att-gallery="highslide" data-att-group="mygroup">
      <h3>{PHP.L.att_gallery}</h3>
      {PAGE_ID|att_gallery('page', $this, '', 'highslide.attacher.gallery')}
  </div>
  <!-- ENDIF -->
  ```
- Список файлов для скачивания:
  ```html
  <!-- IF {PAGE_ID|att_count('page', $this, '', 'files')} > 0 -->
  <div data-att-downloads="download">
      <h3>{PHP.L.att_downloads}</h3>
      {PAGE_ID|att_downloads('page', $this)}
  </div>
  <!-- ENDIF -->
  ```

### Виджет в iframe
Функция `att_widget` для загрузки через iframe:

```html
<!-- В page.edit.tpl -->
<!-- IF {PHP|cot_auth('plug', 'attacher', 'W')} -->
<div>
    {PAGEEDIT_FORM_ID|att_widget('page', $this)}
</div>
<!-- ENDIF -->

<!-- Ссылка на всплывающий виджет -->
<!-- IF {PHP|cot_auth('plug', 'attacher', 'W')} -->
<div>
    {PAGEADD_FORM_ID|att_widget('page', $this, '', 'attacher.link')}
</div>
<!-- ENDIF -->
```

### Прикрепление к постам форума (`forums.posts.tpl`)
```html
<!-- IF {PHP|cot_auth('plug', 'attacher', 'W')} AND {FORUMS_POSTS_ROW_USERID} == {PHP.usr.id} -->
{FORUMS_POSTS_ROW_ID|att_widget('forums', $this, '', 'attacher.link')}
<!-- ENDIF -->

<!-- Вывод файлов -->
<!-- IF {FORUMS_POSTS_ROW_USERID} == {PHP.usr.id} -->
{FORUMS_POSTS_ROW_ID|att_display('forums', $this)}
<!-- ENDIF -->
```

### BB-код для изображений
Используйте BB-код `[att_image?...]` для встраивания изображений в текст (например, в статьях или карточках товаров):

```
[att_image?id=15]
[att_image?id=11&width=350]
[att_image?id=2&width=350&height=350]
[att_image?id=7&width=320&height=240&alt=Image alt]
[att_image?id=88&width=320&height=240&alt=Image alt&class=myclass]
```

Параметры:
- `id`: ID изображения (обязательный).
- `width`, `height`: Размеры изображения.
- `frame`: Режим кадрирования (`crop`, `width`, `height`, `auto`).
- `alt`: Альтернативный текст.
- `class`: CSS-класс для изображения.

## Функции API

1. **Регистрация нового объекта**:
   ```php
   att_fixNewPath($area, $item);
   ```
   Используется для привязки файлов к новому объекту после его создания.
   Пример для модуля `page`:
   ```php
   if (cot_auth('plug', 'attacher', 'W')) {
       if ($id) {
           att_fixNewPath('page', $id);
       }
   }
   ```

2. **Удаление всех вложений**:
   ```php
   att_remove_all($user_id = null, $area = null, $item_id = null);
   ```
   Удаляет все файлы, связанные с объектом или пользователем.
   Пример для модуля `page`:
   ```php
   if (cot_auth('plug', 'attacher', 'W')) {
       att_remove_all(null, 'page', $id);
   }
   ```

3. **Создание загрузчика**:
   ```php
   att_filebox($area, $item, $field = '', $type = 'all', $limit = -1, $tpl = 'attacher.filebox');
   ```
   Параметры:
   - `$area`: Модуль/плагин (например, `page`, `forums`).
   - `$item`: ID объекта (или 0 для новых объектов).
   - `$field`: Имя поля для группировки файлов.
   - `$type`: Тип файлов (`all`, `image`, `file`).
   - `$limit`: Ограничение на количество файлов (-1 для групповых настроек, 0 для неограниченного).
   - `$tpl`: Шаблон (`attacher.filebox` по умолчанию).

4. **Вывод виджета в iframe**:
   ```php
   att_widget($area, $item, $field = '', $tpl = 'attacher.widget', $width = '100%', $height = '200');
   ```
   Создаёт iframe или ссылку на всплывающий загрузчик.

5. **Вывод файлов**:
   ```php
   att_display($area, $item, $field = '', $tpl = 'attacher.display', $type = 'all', $limit = 0, $order = '');
   ```
   Выводит все файлы или определённый тип (например, только изображения).

6. **Вывод галереи**:
   ```php
   att_gallery($area, $item, $field = '', $tpl = 'attacher.gallery', $limit = 0, $order = '');
   ```
   Создаёт галерею изображений с поддержкой сторонних библиотек (например, Highslide).

7. **Подсчёт вложений**:
   ```php
   att_count($area, $item, $field = '', $type = 'all');
   ```
   Возвращает количество файлов, привязанных к объекту. `$field = '_all_'` возвращает общее количество.

8. **Вывод списка загрузок**:
   ```php
   att_downloads($area, $item, $field = '', $tpl = 'attacher.downloads', $limit = 0, $order = '');
   ```
   Формирует список файлов для скачивания.

9. **Создание миниатюры**:
   ```php
   att_thumb($id, $width = 0, $height = 0, $frame = '', $watermark = true);
   ```
   Генерирует URL миниатюры с заданными параметрами.

10. **Получение данных элемента**:
    ```php
    att_get($area, $item, $field = '', $column = '', $number = 'first');
    ```
    Возвращает данные файла (например, путь, имя, размер).

11. **Генерация HTML для изображения**:
    ```php
    att_customizable_thumb($m);
    ```
    Преобразует тег `<img>` в настраиваемый HTML-сниппет (`<span><a><picture><source><img></picture></a></span>`).

12. **Генерация HTML из BB-кода**:
    ```php
    att_customizable_thumb_bbcode($m);
    ```
    Преобразует BB-код `[att_image?...]` в HTML с поддержкой адаптивного `<picture>`.

## Управление через админ-панель
- **Удаление копий**: Удаление всех миниатюр для пересоздания с новыми настройками (например, новым водяным знаком).
- **Очистка**: Удаление неприкреплённых файлов (garbage collection) через админ-панель.
- **Управление файлами**: Просмотр, редактирование и удаление загруженных файлов.

## Примечания
- **Конфликты**: Плагин несовместим с `attach2` и модулем `file`. Отключите их перед использованием.
- **Галереи**: Поддержка сторонних библиотек (например, Highslide) через настраиваемые шаблоны.
- **Загрузка по URL**: Реализована через JavaScript Load Image, позволяет загружать изображения с внешних сайтов без скачивания на устройство.
- **Локализация**: Поддерживается через языковые файлы (`attacher.<lang>.lang.php`).
_____

## [Help & Support](https://abuyfile.com/ru/forums/cotonti/custom/plugs/attacher)

**webitproff 2025-08-29**
