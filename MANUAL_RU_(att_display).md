# Справочник по функции `att_display()` плагина Attacher

Полное описание функции `att_display()` из плагина Attacher (Cotonti CMF): параметры, SQL-поведение, сценарии вызова в шаблонах и из PHP-кода, что именно рендерится и что видит пользователь в каждом случае. Функция выводит HTML-список вложений (файлов или изображений), привязанных к элементу модуля (страница, товар и т. п.), с фильтрацией по полю, типу, лимиту и сортировке; поддерживает фронт и админ-шаблоны.

## Для кого

Разработчик, верстальщик шаблонов, который работает с Cotonti v1.+, PHP 8.5+ и плагином Attacher: вызывает `att_display` в `.tpl` или из PHP и хочет понимать, что произойдёт в каждом варианте вызова, методично с различными параметрами.

## Структура

1. Таблица параметров функции.
2. Шестнадцать сценариев вызова — циклично в каждом:
   - пример кода,
   - что делает шаблонизатор Cotonti,
   - что вернёт функция и что увидит пользователь,
   - разбор каждого параметра.
3. Сводная таблица сценариев.
4. Таблица уязвимостей и защиты.
5. Правила CoTemplate, которые нужно знать перед вызовом

---

## 0. Справка по колонке `att_field`

`att_field` — колонка `varchar(255) DEFAULT '' COMMENT 'Item field'` в таблице `{prefix}attacher` (из `attacher.install.sql`). Значение по умолчанию — пустая строка.

**Где читается с фильтром (все с поддержкой `_all_`):**

- `att_display($area, $item, $field, ...)` — `AND att_field = quote($field)` при `$field !== '_all_'`
- `att_count($area, $item, $field, $type)` — `AND att_field = quote($field)` при `$field != '_all_'`
- `att_count_files($area, $item, $field)` — `AND att_field = quote($field)` при `$field != '_all_'`
- `att_get($area, $item, $field, ...)` — `AND att_field = quote($field)` при `$field != '_all_'`
- `att_get_all($area, $item, $field, ...)` — `AND att_field = quote($field)` при `$field != '_all_'`

**Где читается жёстко (без `_all_`):**

- `att_ajax_get($area, $item, $field, $filename)` — `WHERE att_field = ?` (AJAX-эндпоинт `a=display`, отдельный от `att_display`)

**Где пишется в БД:**

1. **Основной путь — AJAX-загрузка.**
   - `att_filebox($area, $item, $field, ...)` формирует URL для аплоадера, добавляя `&field=$field` при непустом `$field`.
   - `attacher.upload.php` читает `$field = (string)cot_import('field', 'R', 'TXT')`.
   - В `att_ajax_handle_file_upload()` (обычная POST-загрузка) и в `att_ajax_handle_url_upload()` (загрузка по URL) выполняется `$db->insert($db_attacher, [..., 'att_field' => $field, ...])` — то есть в колонку пишется **ровно то значение, что пришло GET-параметром `field`**.

2. **`att_add()`** — принимает `$area, $item, $input, $title, $allow_img`, **не принимает `$field`**. В `INSERT` ключа `'att_field'` нет → колонка получает дефолт схемы `''`. AJAX-контроллер эту функцию не вызывает, это отдельный путь.

**Практический вывод:**

- `$field = 'X'` в `att_display` даёт `AND att_field = 'X'`. Совпадут только записи, загруженные через `att_filebox(..., 'X', ...)` или через прямой AJAX-запрос с `&field=X`.
- `$field = ''` даёт `AND att_field = ''`. Совпадут записи с пустой строкой в `att_field` — то есть загруженные без явной передачи `field` (или через `att_add`).
- `$field = '_all_'` — фильтр по `att_field` снимается полностью.

Конкретные значения (`'gallery'`, `'docs'`, `'promo'`, …) — произвольные строки, которые передают в `att_filebox()` третьим аргументом (или напрямую в URL как `&field=`). Встроенного набора значений в плагине нет — ограничение только `varchar(255)`. В БД реально существуют только те значения `att_field`, которые кто-то раньше записал через загрузку. Проверить в своей БД:

```sql
SELECT DISTINCT att_area, att_field, COUNT(*) FROM cot_attacher GROUP BY att_area, att_field;
```

---

## 1. Таблица параметров функции `att_display()`

| # | Параметр | Тип | По умолчанию | Что контролирует | Что делает в SQL | Что делает в шаблоне |
|---|----------|-----|--------------|------------------|-------------------|----------------------|
| 1 | `$area` | `string` | — (обязательный) | Привязку вложения к модулю/плагину | `WHERE att_area = ?` — первый параметр prepared statement | `ATTACHER_AREA` в шаблоне |
| 2 | `$item` | `int\|array` | — (обязательный) | Привязку к конкретному элементу модуля | `att_item = ?` (int) или `att_item IN(...)` (array) | `ATTACHER_ITEM` в шаблоне |
| 3 | `$field` | `string` | `''` | Значение колонки `att_field`, с которым сравнивать | `AND att_field = '<quote>'` при `$field !== '_all_'`; без фильтра при `'_all_'` | `ATTACHER_FIELD` в шаблоне |
| 4 | `$tpl` | `string` | `'attacher.display'` | Имя шаблона, по которому рендерится HTML | Нет SQL | Передаётся в `cot_tplfile($tpl, 'plug', $admin)` |
| 5 | `$type` | `string` | `'all'` | Тип вложений: все / файлы / изображения | `'files'` → `AND att_img = 0`; `'images'` → `AND att_img = 1`; `'all'` → без фильтра | Нет прямого тега, влияет только на состав строк |
| 6 | `$limit` | `int\|string` | `0` | Сколько строк вернуть | `(int)$limit > 0` → `LIMIT N`; иначе без `LIMIT` | Нет тега, влияет на количество итераций `foreach` |
| 7 | `$order` | `string` | `''` | Порядок сортировки строк | Только из белого списка `['att_order ASC', 'att_order DESC', 'att_id ASC', 'att_id DESC']`; иначе дефолт `ORDER BY att_order ASC` | Нет тега, влияет на порядок вывода `ATTACHER_ROW_*` |
| 8 | `$admin` | `?bool` | `null` | Каталог поиска шаблона через `cot_tplfile()` | Нет SQL | `null` — авто-детект; `true` — админ-тема первой; `false` — только фронт |

Внутренние переменные, не видные в сигнатуре, но влияющие на работу:

| Переменная | Тип | Что означает |
|------------|-----|--------------|
| `$allowedOrders` | `array` | Белый список `ORDER BY`. Защита от SQL-инъекции через `$order`. |
| `$type_filter` | `string` | Накопленный SQL-фрагмент: `AND att_img = N` + `AND att_field = ...` |
| `$sqlLimit` | `string` | Строка `LIMIT N` или пустая строка |
| `$sqlOrder` | `string` | Итоговая строка `ORDER BY ...` |
| `$params` | `array` | Параметры prepared statement: `[$area]` или `[$area, $item]` |
| `$t` | `XTemplate` | Объект шаблонизатора, в который присваиваются `ATTACHER_ROW_*` |
| `$num` | `int` | Счётчик строк начиная с 1 |

Теги, доступные в шаблоне после вызова (для справки):

| Тег | Откуда |
|-----|--------|
| `{ATTACHER_AREA}` | `$area` |
| `{ATTACHER_ITEM}` | `$item` |
| `{ATTACHER_FIELD}` | `$field` |
| `{ATTACHER_ROW_NUM}` | `$num` |
| `{ATTACHER_ROW_ID}` | `att_id` |
| `{ATTACHER_ROW_USER}` | `att_user` |
| `{ATTACHER_ROW_PATH}` | `att_path` |
| `{ATTACHER_ROW_URL}` | `att_path` (картинка) либо `cot_url('index', 'r=attacher&a=dl&id=...')` (файл) |
| `{ATTACHER_ROW_THUMB_URL}` | `$cfg['mainurl'] . '/' . att_thumb(...)` |
| `{ATTACHER_ROW_BIGTHUMB_URL}` | `$cfg['mainurl'] . '/' . att_thumb(...)` с параметрами `thumb_big_*` |
| `{ATTACHER_ROW_FILENAME}` | `htmlspecialchars($row['att_filename'])` |
| `{ATTACHER_ROW_SHORTNAME}` | `prefix + att_id + '.' + att_ext` |
| `{ATTACHER_ROW_EXT}` | `htmlspecialchars($row['att_ext'])` |
| `{ATTACHER_ROW_IMG}` | `1` или `0` |
| `{ATTACHER_ROW_SIZE}` | `cot_build_filesize($row['att_size'])` |
| `{ATTACHER_ROW_TITLE}` | `htmlspecialchars($row['att_title'])` |
| `{ATTACHER_ROW_COUNT}` | `att_count` |
| `{ATTACHER_ROW_LASTMOD}` | `att_lastmod` |

---

## 2. Сценарии вызова функции в шаблонах

---

### Сценарий 1 — Вторая и последующие картинки: пример пользователя

**2.1 Пример**

```html
<!-- IF {MARKET_ID|att_count('market', $this, '', 'images')} > 1 -->

<div class="mb-3">
    {MARKET_ID|att_display('market', $this, '', 'attacher.display.grid.other', 'images', '')}
</div>
<!-- ENDIF -->
```

**2.2 Что проверяется шаблонизатором Cotonti**

В коде шаблона начинаем с проверки:

```html
<!-- IF {MARKET_ID|att_count('market', $this, '', 'images')} > 1 -->
```

Cotonti-шаблонизатор разбирает это по шагам:

1. Видит конструкцию `<!-- IF <выражение> -->`. Всё между `IF` и `-->` — выражение, которое надо вычислить.
2. Выражение — `{MARKET_ID|att_count('market', $this, '', 'images')} > 1`. Это сравнение `>` двух операндов:
   - **левый операнд** — тег-функция `{MARKET_ID|att_count('market', $this, '', 'images')}`;
   - **правый операнд** — числовой литерал `1`.
3. Сначала вычисляется левый операнд. Внутри `{...|...}`:
   - до вертикальной черты `|` — тег `MARKET_ID`, его значение подставляется как первый аргумент функции;
   - после `|` — вызов `att_count('market', $this, '', 'images')`.
4. `$this` в аргументах функции — Cotonti-плейсхолдер, который в момент вычисления тега заменяется на значение `{MARKET_ID}` текущей строки списка. В `att_count()` это уходит как `$item`.
5. Итоговый вызов: `att_count('market', <ID текущей записи>, '', 'images')`. Функция выполняет подсчёт по БД и возвращает целое число — количество изображений с пустым `att_field` для записи `<ID>` в модуле `market`.
6. Это число подставляется вместо левого операнда. Получается сравнение вида `<число> > 1`.
7. Если результат сравнения — истина (число больше 1), блок между `<!-- IF -->` и `<!-- ENDIF -->` рендерится. Если ложь — блок пропускается целиком, включая `att_display`.

Только после этого, если блок рендерится, вычисляется второй тег — `{MARKET_ID|att_display(...)}` — и на его место подставляется HTML-фрагмент, отрендеренный функцией.

- Порядок вычисления:
  1. Разбирается `{MARKET_ID|att_count(...)}`. Слева до `|` — тег `MARKET_ID`. Справа — вызов функции.
  2. Значение `{MARKET_ID}` подставляется как первый аргумент `$item`. `MARKET_ID` — идентификатор записи в модуле Market для текущей итерации списка. В `att_count()` этот `$this` попадает как `$item`.
  3. Вызывается `att_count('market', <ID>, '', 'images')`. Параметры:
     - `'market'` — `$area`
     - `{MARKET_ID}` — `$item`
     - `''` — `$field` (пустое поле)
     - `'images'` — `$type`
  4. Функция `att_count()` в Cotonti от Attacher выполняет `SELECT COUNT(*) FROM {prefix}attacher WHERE att_area = 'market' AND att_item = <ID> AND att_field = '' AND att_img = 1` и возвращает количество изображений.
  5. Числовой результат подставляется в `IF`. Если `> 1` — блок рендерится.
  6. Если результат `0` или `1` — блок пропускается целиком, включая `att_display`.
- Внутри отрендеренного блока встречается второй тег `{MARKET_ID|att_display(...)}` — он уже не в условии, а как самостоятельный вывод. Вычисляется так:
  1. `{MARKET_ID}` → `$item` (ID записи).
  2. Вызывается `att_display('market', <ID>, '', 'attacher.display.grid.other', 'images', '')`.
  3. Возвращённая строка HTML подставляется вместо тега.
- Обёртка `<!-- IF ... -->...<!-- ENDIF -->` — это условный рендер: `att_display` вызывается **только** если `att_count > 1`. Если изображений 0 или 1, второй вызов вообще не выполняется, запрос к БД за списком вложений не идёт.

**2.3 Что подтягиваем этим вызовом**

- `att_display` выполняет:
  ```sql
  SELECT * FROM {prefix}attacher
  WHERE att_area = 'market' AND att_item = <ID>
    AND att_img = 1
    AND att_field = ''
  ORDER BY att_order ASC
  ```
  Без `LIMIT` — все строки, подходящие под условия.
- Для каждой строки рендерится блок `MAIN.ATTACHER_ROW` из шаблона `attacher.display.grid.other.tpl`.
- На выходе — HTML-фрагмент, вставляется вместо `{MARKET_ID|att_display(...)}`. Конкретная разметка задаётся файлом `attacher.display.grid.other.tpl` в теме.
- Соотношение этой пары (`attacher.display.grid.other` + `attacher.display.grid.first` из Сценария 2) задано в самих шаблонах темы, а не в коде `att_display`. Функция просто рендерит указанный шаблон для каждой найденной строки.

**2.3.1 Что вернётся и что увидит пользователь**

    Если att_count <= 1: второй тег не вызывается, пользователь не видит ничего на месте <div class="mb-3">…</div>.

    Если att_count > 1: вызывается att_display(...), возвращается HTML по шаблону attacher.display.grid.other.tpl — по одному блоку MAIN.ATTACHER_ROW на каждое изображение с пустым att_field для этой записи market. Конкретная разметка (сетка, миниатюры, что угодно) — определяется самим файлом .tpl в теме.

    Без LIMIT — все картинки, сколько их есть.

    Если шаблона attacher.display.grid.other.tpl нет ни в теме, ни в плагине — XTemplate(null) → ошибка.

**2.4 Разбор каждого параметра**

1. `'market'` — `$area`. Идёт в `WHERE att_area = ?`. Записи вложений в таблице `cot_attacher` разделены по модулям: `'page'`, `'market'`, `'folio'`. Здесь мы говорим: «бери вложения, которые привязаны к элементам модуля market».
2. `$this` — `$item`. Внутри `att_display` преобразуется в `(int) $this`. Cotonti подставляет вместо `$this` значение `{MARKET_ID}`. В SQL становится вторым параметром prepared statement: `att_item = ?` со значением `<ID>`.
3. `''` — `$field`. Пустая строка. Внутри: `if ($field !== '_all_')` — истина, поэтому добавляется `AND att_field = ''`. У этих строк в колонке `att_field` в БД пустая строка — дефолт схемы (`DEFAULT ''` в install.sql). Так хранятся записи, загруженные без явного указания `field` (или через `att_add`).
4. `'attacher.display.grid.other'` — `$tpl`. Уходит в `cot_tplfile($tpl, 'plug', null)`:
   - Разбивается на `['attacher','display','grid','other']`.
   - `$admin = null` → авто-детект: `[0]='attacher'` ≠ `'admin'`, `[1]='display'` ≠ `'admin'` → `$admin = false`.
   - Поиск идёт в: `themes/{theme}/plugins/attacher/`, `themes/{theme}/plugins/`, `plugins/attacher/tpl/`.
   - Находится файл `attacher.display.grid.other.tpl`.
5. `'images'` — `$type`. В `match` даёт `' AND att_img = 1'`. То есть только строки, где `att_img = 1`. Это изображения (в отличие от обычных файлов, где `att_img = 0`).
6. `''` — `$limit`. Пустая строка из старого шаблона (совместимость). Внутри: `$limit = (int) ''` = `0`. Условие `$limit > 0` ложно → `$sqlLimit = ''` → **без `LIMIT`**. Вернутся все оставшиеся картинки, сколько бы их ни было.
7. — `$order` не передан → дефолт `''`. Внутри: `in_array('', $allowedOrders, true)` = false → `$sqlOrder = ' ORDER BY att_order ASC'`. Сортировка по порядку, заданному при загрузке файлов.
8. — `$admin` не передан → дефолт `null`. Авто-детект уже разобран в п.4: `$admin = false`. Шаблон берётся из фронт-каталогов.

---

### Сценарий 2 — Первая картинка своим шаблоном

**2.1 Пример**

```html
<!-- IF {MARKET_ID|att_count('market', $this, '', 'images')} > 0 -->

<div class="mb-3">
    {MARKET_ID|att_display('market', $this, '', 'attacher.display.grid.first', 'images', 1)}
</div>

<!-- ENDIF -->
```

**2.2 Что проверяется шаблонизатором Cotonti**

- Внешний `IF` считает количество изображений через `att_count('market', <ID>, '', 'images')`.
- Если `> 0` — блок рендерится. Если 0 картинок — пропускается, `att_display` не вызывается.
- Внутри — самостоятельный тег `{MARKET_ID|att_display('market', <ID>, '', 'attacher.display.grid.first', 'images', 1)}`.
- Порядок:
  1. Вычисляется `{MARKET_ID}` → `$item`.
  2. Вызывается `att_display` с параметрами.
  3. Возвращённый HTML подставляется.
- Особенность — шестой аргумент `1`: это `$limit`. Не пустая строка, а явное число `1`.

**2.3 Что подтягиваем этим вызовом**

- SQL:
  ```sql
  SELECT * FROM {prefix}attacher
  WHERE att_area = 'market' AND att_item = <ID>
    AND att_img = 1
    AND att_field = ''
  ORDER BY att_order ASC
  LIMIT 1
  ```
- Один файл — первый по порядку `att_order`.
- Рендерится по шаблону `attacher.display.grid.first.tpl`. Содержимое этого шаблона задано в теме и в коде `att_display` не описано.

**2.3.1 Что вернётся и что увидит пользователь**

    att_count = 0: блок IF пропущен, пользователь не видит ничего.

    att_count > 0: att_display рендерит attacher.display.grid.first.tpl для первой по att_order ASC картинки с пустым att_field. Пользователь видит в <div class="mb-3"> ровно один блок — содержимое этого шаблона.

    Всегда ровно один блок (LIMIT 1), даже если картинок много.

**2.4 Разбор каждого параметра**

1. `'market'` — `$area`. `WHERE att_area = ?` → `'market'`.
2. `$this` → `{MARKET_ID}` — `$item`. `att_item = ?` → `<ID>`.
3. `''` — `$field`. `AND att_field = ''` — только строки с пустым `att_field`.
4. `'attacher.display.grid.first'` — `$tpl`. В `cot_tplfile` → `['attacher','display','grid','first']`. `$admin` → авто-детект → `false` → ищет во фронт-каталогах `attacher.display.grid.first.tpl`.
5. `'images'` — `$type`. `AND att_img = 1`.
6. `1` — `$limit`. `(int) 1` = `1` > `0` → `LIMIT 1`. Возвращается **ровно одна** строка (первая по `att_order ASC`).
7. — `$order` не передан → `''` → не из белого списка (пустая строка) → дефолт `ORDER BY att_order ASC`.
8. — `$admin` не передан → `null` → авто-детект → `false` → фронт-каталоги.

---

### Сценарий 3 — Минимум во фронте: только обязательные параметры

**2.1 Пример**

```html
{PAGE_ID|att_display('page', $this)}
```

**2.2 Что проверяется шаблонизатором Cotonti**

- Никаких `IF` нет — тег самостоятельный.
- `{PAGE_ID}` подставляется как `$item`. Из Cotonti-контекста в цикле страниц это ID текущей страницы.
- Вызывается `att_display('page', <ID>)`. Остальные параметры — дефолтные.
- Возвращённая строка подставляется на место тега.

**2.3 Что подтягиваем этим вызовом**

- SQL:
  ```sql
  SELECT * FROM {prefix}attacher
  WHERE att_area = 'page' AND att_item = <ID>
    AND att_field = ''
  ORDER BY att_order ASC
  ```
- Без `LIMIT`, без фильтра по `att_img` — все вложения (и файлы, и картинки) для страницы с пустым `att_field`.
- Рендер по `attacher.display.tpl` (дефолтный шаблон плагина Attacher, из `plugins/attacher/tpl/` если не переопределён в теме).

**2.3.1 Что вернётся и что увидит пользователь**

    Вложений с пустым att_field нет (все загружены с непустым field): цикл не выполняется, MAIN.ATTACHER_ROW не рендерится. На месте тега — то, что рендерит attacher.display.tpl при пустом MAIN.ATTACHER_ROW: обычно пустая строка, максимум — статичная обёртка из .tpl. Пользователь не видит ничего.

    Одно вложение: один блок MAIN.ATTACHER_ROW. Пользователь видит то, что выводит attacher.display.tpl для одной строки.

    Несколько вложений: N блоков подряд в порядке att_order ASC. Пользователь видит N элементов — как описано в attacher.display.tpl.

    Вложения есть, шаблона нет: cot_tplfile = null → XTemplate(null) → 500-я ошибка или пустой блок (в зависимости от display_errors).

**2.4 Разбор каждого параметра**

1. `'page'` — `$area`. `WHERE att_area = ?` → `'page'`.
2. `$this` → `{PAGE_ID}` — `$item`. `att_item = ?` → `<ID>`.
3. `$field` не передан → дефолт `''`. `if ($field !== '_all_')` истина → `AND att_field = ''`.
4. `$tpl` не передан → `'attacher.display'`. `cot_tplfile('attacher.display', 'plug', null)` → `['attacher','display']`, `$admin = false` (авто-детект). Ищет `attacher.display.tpl` в `themes/{theme}/plugins/attacher/`, `themes/{theme}/plugins/`, `plugins/attacher/tpl/`.
5. `$type` не передан → `'all'`. `match($type)` → default `''` → в SQL нет `AND att_img`.
6. `$limit` не передан → `0`. `(int) 0 = 0`, `0 > 0` = false → `$sqlLimit = ''` → без `LIMIT`.
7. `$order` не передан → `''` → не из белого списка → дефолт `ORDER BY att_order ASC`.
8. `$admin` не передан → `null` → авто-детект → `false` → фронт.

---

### Сценарий 4 — Только изображения, дефолтный шаблон

**2.1 Пример**

```html
{PAGE_ID|att_display('page', $this, '', 'attacher.display', 'images')}
```

**2.2 Что проверяется шаблонизатором Cotonti**

- Тег без `IF`, вызывается всегда.
- `{PAGE_ID}` → `$item`.
- Остальные параметры: 3-й — `''`, 4-й — `'attacher.display'`, 5-й — `'images'`. 6-8 — дефолты.
- Возвращённый HTML подставляется.

**2.3 Что подтягиваем этим вызовом**

- SQL:
  ```sql
  SELECT * FROM {prefix}attacher
  WHERE att_area = 'page' AND att_item = <ID>
    AND att_img = 1
    AND att_field = ''
  ORDER BY att_order ASC
  ```
- Только изображения (`att_img = 1`), без `LIMIT`.
- Рендер по `attacher.display.tpl`.

**2.3.1 Что вернётся и что увидит пользователь**

    Изображений с att_field='' нет: пусто на месте тега.

    Одно изображение: один блок MAIN.ATTACHER_ROW, пользователь видит то, что выводит attacher.display.tpl для строки-изображения.

    Несколько: N блоков в порядке att_order ASC. Файлы (att_img=0) отфильтрованы — только картинки.

    Изображения с att_field ≠ '' (любое непустое значение) — не попадут, потому что в SQL стоит AND att_field = ''.

**2.4 Разбор каждого параметра**

1. `'page'` — `$area` → `WHERE att_area = ?` → `'page'`.
2. `$this` → `{PAGE_ID}` — `$item` → `att_item = ?`.
3. `''` — `$field` → `AND att_field = ''`.
4. `'attacher.display'` — `$tpl` явно (совпадает с дефолтом, но передан 4-м аргументом, чтобы дотащить 5-й). `$admin = false` через авто-детект.
5. `'images'` — `$type` → `AND att_img = 1`.
6. `$limit` не передан → `0` → без `LIMIT`.
7. `$order` не передан → `''` → `ORDER BY att_order ASC`.
8. `$admin` не передан → `null` → авто-детект → `false`.

Особенность: явное указание `''` и `'attacher.display'` — дань позиционному порядку аргументов PHP. В Cotonti-тегах пропустить аргумент через `,` нельзя — надо явно передавать `''` или писать все предыдущие. Поэтому при вызове функции в шаблоне, если нужен 5-й аргумент, приходится передавать и 3-й, и 4-й.

---

### Сценарий 5 — Только файлы, дефолтный шаблон

**2.1 Пример**

```html
{PAGE_ID|att_display('page', $this, '', 'attacher.display', 'files')}
```

**2.2 Что проверяется шаблонизатором Cotonti**

- Как в Сценарии 4, но 5-й аргумент `'files'`.
- Никакой проверки в IF нет — вызывается всегда.

**2.3 Что подтягиваем этим вызовом**

- SQL:
  ```sql
  SELECT * FROM {prefix}attacher
  WHERE att_area = 'page' AND att_item = <ID>
    AND att_img = 0
    AND att_field = ''
  ORDER BY att_order ASC
  ```
- Только файлы (`att_img = 0`).
- Рендер по `attacher.display.tpl`.

**2.3.1 Что вернётся и что увидит пользователь**

    Файлов (att_img=0) с att_field='' нет: пусто.

    Один файл: один блок, пользователь видит то, что выводит attacher.display.tpl для строки-файла.

    Несколько: N блоков в порядке att_order ASC. Картинки (att_img=1) отфильтрованы.

**2.4 Разбор каждого параметра**

1. `'page'` — `$area` → `WHERE att_area = ?`.
2. `$this` → `{PAGE_ID}` — `$item` → `att_item = ?`.
3. `''` — `$field` → `AND att_field = ''`.
4. `'attacher.display'` — `$tpl`.
5. `'files'` — `$type` → `AND att_img = 0`.
6. `$limit` не передан → `0` → без `LIMIT`.
7. `$order` не передан → `''` → `ORDER BY att_order ASC`.
8. `$admin` не передан → `null` → авто-детект → `false`.

---

### Сценарий 6 — Лимит N

**2.1 Пример**

```html
{PAGE_ID|att_display('page', $this, '', 'attacher.display', 'images', 5)}
```

**2.2 Что проверяется шаблонизатором Cotonti**

- Тег самостоятельный, вызывается всегда.
- 6-й аргумент — `5` (int). Cotonti передаёт его как int-литерал.
- Внутри функции `(int) 5 = 5`, `5 > 0` = true → `LIMIT 5`.

**2.3 Что подтягиваем этим вызовом**

- SQL:
  ```sql
  SELECT * FROM {prefix}attacher
  WHERE att_area = 'page' AND att_item = <ID>
    AND att_img = 1
    AND att_field = ''
  ORDER BY att_order ASC
  LIMIT 5
  ```
- Пять первых по `att_order` изображений.
- Рендер по `attacher.display.tpl` в цикле `foreach` — ровно 5 итераций, если в БД столько и больше.

**2.3.1 Что вернётся и что увидит пользователь**

    0 картинок: пусто.

    1..5 картинок: все, блоков столько же, сколько картинок.

    > 5 картинок: ровно 5 блоков — первые пять по att_order ASC. Остальные не выводятся.

**2.4 Разбор каждого параметра**

1. `'page'` — `$area`.
2. `$this` — `$item`.
3. `''` — `$field`.
4. `'attacher.display'` — `$tpl`.
5. `'images'` — `$type` → `AND att_img = 1`.
6. `5` — `$limit` → `LIMIT 5`.
7. `$order` не передан → `''` → `ORDER BY att_order ASC`.
8. `$admin` не передан → `null` → авто-детект → `false`.

---

### Сценарий 7 — Старый шаблон с `''` в `$limit` (500-я ошибка)

**2.1 Пример**

```html
{MARKET_ID|att_display('market', $this, '', 'attacher.display.grid.other', 'images', '')}
```

**2.2 Что проверяется шаблонизатором Cotonti**

- Тег самостоятельный.
- 6-й аргумент — пустая строка `''` (старый синтаксис, когда в шаблонах передавали `''` вместо `0`).
- До правки функции это вызывало `TypeError: Argument #6 ($limit) must be of type int, string given` — потому что в сигнатуре было строго `int $limit = 0`, а Cotonti передавал string `''`.
- После правки сигнатура — `int|string $limit = 0`. PHP принимает и строку. Внутри `$limit = (int) ''` = `0` — совместимо.

**2.3 Что подтягиваем этим вызовом**

- SQL:
  ```sql
  SELECT * FROM {prefix}attacher
  WHERE att_area = 'market' AND att_item = <ID>
    AND att_img = 1
    AND att_field = ''
  ORDER BY att_order ASC
  ```
- Без `LIMIT` (`(int)'' = 0`).
- Рендер по `attacher.display.grid.other.tpl`.

**2.3.1 Что вернётся и что увидит пользователь**

    0 картинок: пусто.

    N картинок: N блоков attacher.display.grid.other.tpl — без ограничения количества (потому что (int)''=0 → без LIMIT). Пользователь видит то, что рендерит этот шаблон, для всех картинок.

**2.4 Разбор каждого параметра**

1. `'market'` — `$area`.
2. `$this` → `{MARKET_ID}` — `$item`.
3. `''` — `$field` → `AND att_field = ''`.
4. `'attacher.display.grid.other'` — `$tpl`. В `cot_tplfile` — 4 части, `[1]='display'` → `$admin = false` → фронт-каталоги.
5. `'images'` — `$type` → `AND att_img = 1`.
6. `''` — `$limit`. Совместимость: `(int)'' = 0` → без `LIMIT`. В SQL `$sqlLimit` пустой.
7. `$order` не передан → `''` → `ORDER BY att_order ASC`.
8. `$admin` не передан → `null` → авто-детект → `false`.

---

### Сценарий 8 — Конкретное значение `att_field`

**2.1 Пример**

```html
{PAGE_ID|att_display('page', $this, 'gallery', 'attacher.display', 'images')}
```

**2.2 Что проверяется шаблонизатором Cotonti**

- Тег самостоятельный.
- 3-й аргумент — `'gallery'` — строка, которая идёт в SQL как значение для сравнения с колонкой `att_field`.
- Внутри `if ($field !== '_all_')` — истина (не `'_all_'`), выполняется `AND att_field = 'gallery'`.

**2.3 Что подтягиваем этим вызовом**

- SQL:
  ```sql
  SELECT * FROM {prefix}attacher
  WHERE att_area = 'page' AND att_item = <ID>
    AND att_img = 1
    AND att_field = 'gallery'
  ORDER BY att_order ASC
  ```
- Только изображения, у которых в колонке `att_field` записана строка `'gallery'`, для этой страницы.
- `att_field` — колонка `varchar(255) DEFAULT ''` в `{prefix}attacher`. Значение `'gallery'` попадает в неё так: `att_filebox('page', <ID>, 'gallery')` формирует URL аплоадера с `&field=gallery`, затем `attacher.upload.php` читает GET-параметр `field` (`$field = (string)cot_import('field', 'R', 'TXT')`), и в `att_ajax_handle_file_upload()` / `att_ajax_handle_url_upload()` при `$db->insert($db_attacher, [...])` пишется `'att_field' => $field`. То есть в колонку попадает ровно та строка, что была передана в `att_filebox()` третьим аргументом (или напрямую в URL как `&field=`). `att_add()` в присланном коде `att_field` в INSERT не включает — это альтернативный путь, AJAX-контроллер его не использует.
- Значение `'gallery'` — произвольная строка. Встроенного набора значений в плагине нет: ограничение только `varchar(255)`. В вашей БД реально существуют только те значения `att_field`, которые кто-то раньше записал через загрузку. Проверить:

  ```sql
  SELECT DISTINCT att_area, att_field, COUNT(*) FROM cot_attacher GROUP BY att_area, att_field;
  ```

**2.3.1 Что вернётся и что увидит пользователь**

    Строк с att_field = 'gallery' для этой страницы в БД нет: пусто на месте тега.

    Строки есть: N блоков attacher.display.tpl в порядке att_order ASC — только те, у которых att_field ровно 'gallery'. Строки с пустым att_field и с любым другим значением не попадут.

**2.4 Разбор каждого параметра**

1. `'page'` — `$area`.
2. `$this` → `{PAGE_ID}` — `$item`.
3. `'gallery'` — `$field` → `AND att_field = 'gallery'`. Экранируется через `$db->quote()` — защита от инъекции.
4. `'attacher.display'` — `$tpl`.
5. `'images'` — `$type` → `AND att_img = 1`.
6. `$limit` не передан → `0` → без `LIMIT`.
7. `$order` не передан → `''` → `ORDER BY att_order ASC`.
8. `$admin` не передан → `null` → авто-детект → `false`.

---

### Сценарий 9 — Все значения `att_field` (`'_all_'`)

**2.1 Пример**

```html
{PAGE_ID|att_display('page', $this, '_all_', 'attacher.display', 'images')}
```

**2.2 Что проверяется шаблонизатором Cotonti**

- Тег самостоятельный.
- 3-й аргумент — `'_all_'` — специальный маркер.
- Внутри `if ($field !== '_all_')` — **ложь** (равно `'_all_'`). Условие не выполняется, `AND att_field` не добавляется.
- В `$t->assign(['ATTACHER_FIELD' => $field])` уходит `'_all_'` — в шаблоне, если используется `{ATTACHER_FIELD}`, выведется `_all_`.

**2.3 Что подтягиваем этим вызовом**

- SQL:
  ```sql
  SELECT * FROM {prefix}attacher
  WHERE att_area = 'page' AND att_item = <ID>
    AND att_img = 1
  ORDER BY att_order ASC
  ```
- **Без фильтра по `att_field`**. То есть все изображения этой страницы, независимо от значения в колонке `att_field` — какие бы значения там ни были в БД.

**2.3.1 Что вернётся и что увидит пользователь**

    Изображений нет: пусто.

    Изображения есть: N блоков attacher.display.tpl в порядке att_order ASC — все картинки страницы, вне зависимости от att_field. В одном списке окажутся картинки с пустым att_field, с 'gallery', с 'docs' — с любым значением.

**2.4 Разбор каждого параметра**

1. `'page'` — `$area`.
2. `$this` — `$item`.
3. `'_all_'` — `$field` → блок `if ($field !== '_all_')` пропускается → нет `AND att_field`.
4. `'attacher.display'` — `$tpl`.
5. `'images'` — `$type` → `AND att_img = 1`.
6. `$limit` не передан → `0` → без `LIMIT`.
7. `$order` не передан → `''` → `ORDER BY att_order ASC`.
8. `$admin` не передан → `null` → авто-детект → `false`.

Принципиальное отличие от Сценария 3: там `$field = ''` фильтрует по **пустому** `att_field`. Здесь `$field = '_all_'` **вообще не фильтрует** по колонке — вернутся все записи.

---

### Сценарий 10 — Лимит + пользовательская сортировка

**2.1 Пример**

```html
{PAGE_ID|att_display('page', $this, '', 'attacher.display', 'files', 10, 'att_order DESC')}
```

**2.2 Что проверяется шаблонизатором Cotonti**

- Тег самостоятельный.
- 6-й аргумент — `10`, 7-й — `'att_order DESC'`.
- Внутри `(int) 10 = 10` → `LIMIT 10`. Строка `'att_order DESC'` проверяется через `in_array('att_order DESC', $allowedOrders, true)` → true → `$sqlOrder = ' ORDER BY att_order DESC'`.

**2.3 Что подтягиваем этим вызовом**

- SQL:
  ```sql
  SELECT * FROM {prefix}attacher
  WHERE att_area = 'page' AND att_item = <ID>
    AND att_img = 0
    AND att_field = ''
  ORDER BY att_order DESC
  LIMIT 10
  ```
- Десять файлов, начиная с самых последних по `att_order`. Обратный порядок относительно дефолта.

**2.3.1 Что вернётся и что увидит пользователь**

    0 файлов: пусто.

    1..10 файлов: все, в порядке att_order DESC.

    > 10 файлов: ровно 10 блоков — первые 10 по att_order DESC. Остальные не выводятся.

**2.4 Разбор каждого параметра**

1. `'page'` — `$area`.
2. `$this` — `$item`.
3. `''` — `$field`.
4. `'attacher.display'` — `$tpl`.
5. `'files'` — `$type` → `AND att_img = 0`.
6. `10` — `$limit` → `LIMIT 10`.
7. `'att_order DESC'` — `$order`. Из белого списка → `ORDER BY att_order DESC`.
8. `$admin` не передан → `null` → авто-детект → `false`.

Если передать `$order`, которого нет в белом списке (`'att_ext ASC'`, `'att_id; DROP TABLE ...'` и т. п.), функция подставит дефолт `ORDER BY att_order ASC`. Это защита от инъекции: значение `$order` не может привести к выполнению произвольного SQL.

---

### Сценарий 11 — Только сортировка, без лимита

**2.1 Пример**

```html
{PAGE_ID|att_display('page', $this, '', 'attacher.display', 'images', 0, 'att_id ASC')}
```

**2.2 Что проверяется шаблонизатором Cotonti**

- 6-й аргумент — `0` → `$sqlLimit = ''`, без `LIMIT`.
- 7-й — `'att_id ASC'` → из белого списка → `ORDER BY att_id ASC`.

**2.3 Что подтягиваем этим вызовом**

- SQL:
  ```sql
  SELECT * FROM {prefix}attacher
  WHERE att_area = 'page' AND att_item = <ID>
    AND att_img = 1
    AND att_field = ''
  ORDER BY att_id ASC
  ```
- Все изображения, отсортированные по возрастанию `att_id`.

**2.3.1 Что вернётся и что увидит пользователь**

    Изображений нет: пусто.

    Изображения есть: N блоков в порядке att_id ASC. Без LIMIT — выводятся все.

**2.4 Разбор каждого параметра**

1. `'page'` — `$area`.
2. `$this` — `$item`.
3. `''` — `$field`.
4. `'attacher.display'` — `$tpl`.
5. `'images'` — `$type`.
6. `0` — `$limit` → без `LIMIT`.
7. `'att_id ASC'` — `$order` → `ORDER BY att_id ASC`.
8. `$admin` не передан → `null` → авто-детект → `false`.

Важно: чтобы передать 7-й аргумент, надо явно указать 6-й. Поэтому здесь стоит `0`, а не пусто.

---

### Сценарий 12 — Админка, свой шаблон, `$admin = true`

**2.1 Пример**

```html
{ADMIN_PAGE_ID|att_display('page', $this, '', 'attacher.display.cotcp-admin.list', 'images', 1, '', true)}
```

**2.2 Что проверяется шаблонизатором Cotonti**

- Тег самостоятельный, вызывается всегда.
- 8-й аргумент — `true` (bool). Cotonti передаёт его как литерал `true`.
- Внутри `$admin = true`.
- `cot_tplfile('attacher.display.cotcp-admin.list', 'plug', true)` — режим админ-шаблона.

**2.3 Что подтягиваем этим вызовом**

- SQL:
  ```sql
  SELECT * FROM {prefix}attacher
  WHERE att_area = 'page' AND att_item = <ID>
    AND att_img = 1
    AND att_field = ''
  ORDER BY att_order ASC
  LIMIT 1
  ```
- Одна первая картинка.
- Рендер шаблона ищется в порядке:
  1. `themes/admin/{admintheme}/plugins/attacher/attacher.display.cotcp-admin.list.tpl`
  2. `themes/admin/{admintheme}/plugins/attacher.display.cotcp-admin.list.tpl`
  3. `themes/{theme}/admin/plugins/attacher/attacher.display.cotcp-admin.list.tpl`
  4. `themes/{theme}/admin/plugins/attacher.display.cotcp-admin.list.tpl`
  5. `themes/{theme}/plugins/attacher/attacher.display.cotcp-admin.list.tpl`
  6. `themes/{theme}/plugins/attacher.display.cotcp-admin.list.tpl`
  7. `plugins/attacher/tpl/attacher.display.cotcp-admin.list.tpl`
- Если тема админки `cotcp`, файл найдётся на шаге 1.

**2.3.1 Что вернётся и что увидит пользователь (администратор)**

    Картинок нет: пусто в ячейке.

    Картинки есть: одна картинка (LIMIT 1, первая по att_order ASC). Пользователь-администратор видит в ячейке то, что выводит attacher.display.cotcp-admin.list.tpl для одной строки.

    Шаблона нет ни в админ-теме, ни во фронт-каталогах: XTemplate(null) → 500-я ошибка.

**2.4 Разбор каждого параметра**

1. `'page'` — `$area`.
2. `$this` → `{ADMIN_PAGE_ID}` — `$item`. По имени тега — ID текущей записи в списке админки Cotonti.
3. `''` — `$field`.
4. `'attacher.display.cotcp-admin.list'` — `$tpl`. Разбирается в `['attacher','display','cotcp-admin','list']`. `$admin = true` передан явно.
5. `'images'` — `$type` → `AND att_img = 1`.
6. `1` — `$limit` → `LIMIT 1`. Только первая картинка.
7. `''` — `$order`. Не из белого списка → дефолт `ORDER BY att_order ASC`.
8. `true` — `$admin` → поиск по админ-каталогам первым делом. Без этого параметра шаблон не найдётся (см. Сценарий 13).

Важность 8-го параметра: только `$admin = true` переключает `cot_tplfile` в режим админ-темы. Авто-детект в имени `'attacher.display.cotcp-admin.list'` не сработает, потому что `'admin'` стоит не в позиции `[0]`/`[1]`, а внутри `cotcp-admin` на позиции `[2]`.

---

### Сценарий 13 — Админка без `$admin` — ошибка

**2.1 Пример**

```html
{ADMIN_PAGE_ID|att_display('page', $this, '', 'attacher.display.cotcp-admin.list', 'images', 1)}
```

**2.2 Что проверяется шаблонизатором Cotonti**

- Тег самостоятельный, вызывается всегда.
- 8-й аргумент отсутствует → `$admin = null`.
- В `cot_tplfile('attacher.display.cotcp-admin.list', 'plug', null)`:
  - `$base = ['attacher','display','cotcp-admin','list']`
  - `$extensionCode = 'attacher'`
  - `$baseParts = []` (нет точки в `'attacher'`)
  - Авто-детект `$admin`:
    - `$extensionCode === 'admin'` → `'attacher' === 'admin'` → false
    - `isset($base[1]) && $base[1] === 'admin'` → `'display' === 'admin'` → false
    - `isset($baseParts[1]) && $baseParts[1] === 'admin'` → `$baseParts` пуст → false
  - Итог: `$admin = false`.
- Поиск только во фронт-каталогах. Файла `attacher.display.cotcp-admin.list.tpl` там нет (он в `themes/admin/cotcp/plugins/attacher/`).
- `cot_tplfile` возвращает `null`.
- `new XTemplate(null)` — конструктор получает `null` вместо строки с путём. Поведение зависит от реализации `XTemplate` в вашей версии Cotonti: либо ошибка типов (в PHP 8.5 при строгой типизации параметра конструктора), либо пустой объект без парсинга.

**2.3 Что подтягиваем этим вызовом**

- Ничего. Вызов падает до SQL-запроса на этапе инициализации шаблонизатора.
- Если бы ошибка была проигнорирована — вернулся бы пустой HTML без строк.

**2.4 Разбор каждого параметра**

1-7 — те же, что в Сценарии 12.
8. `$admin` — **не передан**, дефолт `null`. Авто-детект не срабатывает (см. выше). Шаблон ищется во фронт-каталогах. Не найден → падение.

Единственный способ исправить — либо явно передать `true` (Сценарий 12), либо переименовать шаблон так, чтобы `'admin'` был на позиции `[1]` (`attacher.admin.list` → тогда авто-детект даст `$admin = true`).

---

### Сценарий 14 — PHP: одиночный ID через `$t->assign()`

**2.1 Пример**

```php
$t->assign('PAGE_GALLERY', att_display(
    'page',
    (int) $item['page_id'],
    '',
    'attacher.display',
    'images',
    5
));
```

**2.2 Что проверяется Cotonti в PHP-контексте**

- Никакого тега нет — прямой вызов функции из PHP-кода модуля/плагина.
- `$item['page_id']` — ID страницы, полученный из выборки в PHP-коде модуля.
- `(int)` — приведение к целому, чтобы гарантировать тип `int` и не словить `TypeError` на `int|array $item`.
- Результат присваивается в переменную шаблона `PAGE_GALLERY`, которая потом выводится в `.tpl` как `{PAGE_GALLERY}`.

**2.3 Что подтягиваем этим вызовом**

- SQL:
  ```sql
  SELECT * FROM {prefix}attacher
  WHERE att_area = 'page' AND att_item = <page_id>
    AND att_img = 1
    AND att_field = ''
  ORDER BY att_order ASC
  LIMIT 5
  ```
- Пять изображений.
- Рендер по `attacher.display.tpl` → HTML → строка в `{PAGE_GALLERY}` в шаблоне.

**2.3.1 Что вернётся и что увидит пользователь**

    Изображений нет: пусто в месте, где в шаблоне стоит {PAGE_GALLERY}.

    Изображения есть: до 5 блоков attacher.display.tpl (LIMIT 5). Пользователь видит то, что рендерит этот шаблон.

**2.4 Разбор каждого параметра**

1. `'page'` — `$area`. Явно `'page'`, потому что вызов из модуля Page.
2. `(int) $item['page_id']` — `$item`. Приведение к int — важно, если `$item['page_id']` пришёл как numeric string из PDO (зависит от `PDO::ATTR_EMULATE_PREPARES`). Без `(int)` можно получить `TypeError` на строгом `int|array $item` в PHP 8.5.
3. `''` — `$field`.
4. `'attacher.display'` — `$tpl`.
5. `'images'` — `$type`.
6. `5` — `$limit` → `LIMIT 5`.
7. `$order` не передан → `''` → `ORDER BY att_order ASC`.
8. `$admin` не передан → `null` → авто-детект → `false` → фронт.

Замечание: **не** `echo att_display(...)`. В Cotonti вывод идёт через шаблонизатор. `echo` напечатает строку до или после шаблона в обход логики `$t->output()`, что сломает страницу.

---

### Сценарий 15 — PHP: массив ID

**2.1 Пример**

```php
$ids = [12, 34, 56];
$t->assign('BATCH_GALLERY', att_display(
    'page',
    $ids,
    '',
    'attacher.display',
    'images',
    20,
    '',
    false
));
```

**2.2 Что проверяется Cotonti в PHP-контексте**

- `$ids` — массив `int`, сформированный в PHP.
- Функция получает `int|array $item` → `is_array($item)` → true.
- Внутри:
  - `array_map('intval', $item)` — гарантирует целые числа.
  - `implode(',', $item)` → `'12,34,56'`.
  - `$item = 'IN(12,34,56)'`.
- В SQL подставляется как фрагмент, **не** как параметр prepared statement. Параметры — только `$area` и `$item`. Защита от инъекции — предварительное `intval` каждого элемента.

**2.3 Что подтягиваем этим вызовом**

- SQL:
  ```sql
  SELECT * FROM {prefix}attacher
  WHERE att_area = 'page' AND att_item IN(12,34,56)
    AND att_img = 1
    AND att_field = ''
  ORDER BY att_order ASC
  LIMIT 20
  ```
- Все изображения для трёх страниц с пустым `att_field`, до 20 штук суммарно.
- Рендер в одном цикле — `MAIN.ATTACHER_ROW` повторяется до 20 раз. Теги `ATTACHER_ROW_*` не содержат тега с текущим ID элемента, к которому относится строка. В `$t->assign([...])` присваивается только `ATTACHER_ITEM` = `$item` (массив в исходном виде).

**2.3.1 Что вернётся и что увидит пользователь**

    Изображений для этих страниц нет: пусто в месте, где стоит {BATCH_GALLERY}.

    Изображения есть: до 20 блоков attacher.display.tpl в одном общем списке. Пользователь видит общий список, не разделённый по страницам.

**2.4 Разбор каждого параметра**

1. `'page'` — `$area`.
2. `$ids` — `$item`. Массив → `IN(...)`. Приведение каждого элемента к int через `array_map`.
3. `''` — `$field`.
4. `'attacher.display'` — `$tpl`.
5. `'images'` — `$type`.
6. `20` — `$limit` → `LIMIT 20`.
7. `''` — `$order` → не из белого списка → дефолт.
8. `false` — `$admin`. Явно `false` = «только фронт-каталоги». Отличие от `null`: `null` включает авто-детект по имени шаблона. Если бы `$tpl` был `'attacher.admin.foo'`, при `null` детект дал бы `true` (потому что `[1]='admin'`), а с `false` — гарантированно фронт. Для шаблона `'attacher.display'` результат одинаков — оба дадут `false`, но `false` явно документирует намерение.

---

### Сценарий 16 — PHP: админ-контекст

**2.1 Пример**

```php
$t->assign('ADMIN_LIST', att_display(
    'page',
    $ids,
    '',
    'attacher.display.cotcp-admin.list',
    'images',
    1,
    '',
    true
));
```

**2.2 Что проверяется Cotonti в PHP-контексте**

- Вызов из админ-кода модуля/плагина.
- `$admin = true` явно.
- `cot_tplfile('attacher.display.cotcp-admin.list', 'plug', true)` — режим админ-темы первым делом.
- Файл найдётся в `themes/admin/{admintheme}/plugins/attacher/`.
- `$ids` — массив int.

**2.3 Что подтягиваем этим вызовом**

- SQL:
  ```sql
  SELECT * FROM {prefix}attacher
  WHERE att_area = 'page' AND att_item IN(...)
    AND att_img = 1
    AND att_field = ''
  ORDER BY att_order ASC
  LIMIT 1
  ```
- `LIMIT 1` ограничивает **весь** результат до **одной** строки, не «по одной на каждый ID». При массиве `$ids` вернётся только первая картинка из всего набора.

**2.3.1 Что вернётся и что увидит пользователь**

    Изображений нет: пусто в месте, где стоит {ADMIN_LIST}.

    Изображения есть: одна картинка — первый блок attacher.display.cotcp-admin.list.tpl по att_order ASC из всей группы. Пользователь-администратор видит в {ADMIN_LIST} ровно один блок.

**2.4 Разбор каждого параметра**

1. `'page'` — `$area`.
2. `$ids` — `$item` массив → `IN(...)`.
3. `''` — `$field`.
4. `'attacher.display.cotcp-admin.list'` — `$tpl`.
5. `'images'` — `$type`.
6. `1` — `$limit` → `LIMIT 1`.
7. `''` — `$order` → дефолт.
8. `true` — `$admin`. Поиск шаблона по админ-каталогам первым. Без этого шаблон не найдётся (см. Сценарий 13).

---

## Сводная таблица сценариев

| # | Контекст | Ключевое в вызове | SQL-фильтры | Каталог шаблона |
|---|----------|-------------------|-------------|-----------------|
| 1 | Шаблон, фронт, пример пользователя | `IF att_count > 1` + `$limit=''` | `att_img=1 AND att_field=''` без LIMIT | фронт |
| 2 | Шаблон, фронт, первая картинка | `$limit=1` | `att_img=1 AND att_field='' LIMIT 1` | фронт |
| 3 | Шаблон, фронт, минимум | только `'page'` и `$this` | `att_field=''` без фильтра типа | фронт |
| 4 | Шаблон, фронт, только картинки | `$type='images'` | `att_img=1 AND att_field=''` | фронт |
| 5 | Шаблон, фронт, только файлы | `$type='files'` | `att_img=0 AND att_field=''` | фронт |
| 6 | Шаблон, фронт, с лимитом | `$limit=5` | `att_img=1 AND att_field='' LIMIT 5` | фронт |
| 7 | Шаблон, старый, `''` в limit | `$limit=''` | без LIMIT | фронт |
| 8 | Шаблон, фронт, конкретное значение att_field | `$field='gallery'` | `att_img=1 AND att_field='gallery'` | фронт |
| 9 | Шаблон, фронт, все значения att_field | `$field='_all_'` | `att_img=1` без `att_field` | фронт |
| 10 | Шаблон, фронт, лимит + сортировка | `$limit=10, $order='att_order DESC'` | `LIMIT 10 ORDER BY att_order DESC` | фронт |
| 11 | Шаблон, фронт, только сортировка | `$limit=0, $order='att_id ASC'` | `ORDER BY att_id ASC` | фронт |
| 12 | Шаблон, админ, `$admin=true` | `$admin=true` | `LIMIT 1` | админ-каталоги первыми |
| 13 | Шаблон, админ без `$admin` | `$admin=null` | — | не найден → ошибка |
| 14 | PHP, одиночный | `$t->assign()` | `LIMIT 5` | фронт |
| 15 | PHP, массив | `$item=[12,34,56]` | `IN(12,34,56) LIMIT 20` | явно фронт |
| 16 | PHP, админ | `$admin=true` | `IN(...) LIMIT 1` | админ-каталоги |

## Уязвимости и защита

| Параметр | Кто может влиять | В текущем коде | Рекомендация |
|----------|------------------|----------------|--------------|
| `$area` | Только код/шаблон | Параметр prepared statement — безопасно | — |
| `$item` (int) | Только код/шаблон | `(int) $item` + `att_item = ?` — безопасно | — |
| `$item` (array) | Только код | `array_map('intval', $item)` + `implode` — безопасно | — |
| `$field` | Только код/шаблон | `$db->quote($field)` — безопасно | — |
| `$type` | Только код/шаблон | `match` — только 3 значения | — |
| `$limit` | Только код/шаблон | `(int) $limit` — безопасно | — |
| `$order` | **Потенциально пользовательский** | **Белый список** `$allowedOrders` через `in_array(..., true)` | Использовать как есть — уже защищено |
| `$admin` | Только код/шаблон | `?bool` — приводится типом | — |



## Правила CoTemplate, которые нужно знать перед вызовом

1. **`$this` в аргументах функции** — это значение тега, стоящего до `|`. В `{PAGE_ID|att_count('page', $this, ...)}` подставляется значение `{PAGE_ID}`.
2. **В `<!-- IF -->` пробелы вокруг оператора обязательны.** `{X} > 0` — работает корректно. `{X}>0` — выражение вычисляется **всегда как true**, независимо от значения (подробнее в антипаттернах).
3. **`<!-- IF ... -->`, `<!-- ELSE -->`, `<!-- ENDIF -->`** — стандартные условные блоки. Вложенные `IF` разрешены.
4. **`att_count()` кеширует** результат (`static $a_cache` в функции). Кеш живёт **в пределах одного HTTP-запроса**. Повторный вызов с теми же аргументами в том же запросе — без SQL. Между запросами кеш сбрасывается.
5. **`att_display()` не кеширует** — каждый вызов = один `SELECT`.

---

## Публикация (одиночная страница)

В контексте `page.tpl` (или аналогичного `.tpl` для одиночного элемента) доступен тег `{PAGE_ID}` (или `{MARKET_ID}` и т. п.). Шаблон рендерится один раз для одного элемента.

### Сценарий 1. Галерея изображений — проверка через `att_count` + `ELSE`-фолбэк

```html
<!-- IF {PAGE_ID|att_count('page', $this, '', 'images')} > 0 -->
    <div class="gallery">
        {PAGE_ID|att_display('page', $this, '', 'attacher.gallery', 'images')}
    </div>
<!-- ELSE -->
    <div class="gallery-empty text-muted">
        <p>Изображений нет.</p>
    </div>
<!-- ENDIF -->
```

- `att_count` с `$field = ''` считает **только** записи с пустым `att_field` — то есть загруженные без явного `field`.
- Ветка `ELSE` рендерится только если `att_count = 0`. В этом случае `att_display` **не вызывается** — лишнего SQL-запроса нет.

### Сценарий 2. Первая картинка отдельно, остальные — отдельным шаблоном

```html
<!-- IF {PAGE_ID|att_count('page', $this, '', 'images')} > 0 -->
    <div class="gallery-first">
        {PAGE_ID|att_display('page', $this, '', 'attacher.grid.first', 'images', 1)}
    </div>
    <!-- IF {PAGE_ID|att_count('page', $this, '', 'images')} > 1 -->
        <div class="gallery-other">
            {PAGE_ID|att_display('page', $this, '', 'attacher.grid.other', 'images')}
        </div>
    <!-- ENDIF -->
<!-- ELSE -->
    <p class="text-muted">Изображений нет.</p>
<!-- ENDIF -->
```

- Внешний `IF` — есть ли хотя бы одно изображение. Если 0 — только `ELSE`, `att_display` не вызывается.
- Внутренний `IF` — есть ли больше одного. Если 1 — выводится только `grid.first`, `grid.other` не рендерится.
- **Важно:** `att_display` не поддерживает `OFFSET` — только `LIMIT` с начала. Второй `att_display` без `$limit` вернёт **все** изображения, включая первое. Чтобы не дублировать первую картинку, в `attacher.grid.other.tpl` нужно обернуть блок `MAIN.ATTACHER_ROW` в `<!-- IF {ATTACHER_ROW_NUM} > 1 -->...<!-- ENDIF -->` — `{ATTACHER_ROW_NUM}` в `att_display` начинается с 1. Шаблон **не может** фильтровать строки по SQL-фильтрам — он получает уже отфильтрованный набор, но счётчик строк в его распоряжении есть.

### Сценарий 3. Документы (файлы) отдельным блоком

```html
<!-- IF {PAGE_ID|att_count('page', $this, '', 'files')} > 0 -->
    <div class="downloads">
        <h4>Файлы</h4>
        {PAGE_ID|att_display('page', $this, '', 'attacher.downloads', 'files')}
    </div>
<!-- ELSE -->
    <p class="text-muted">Файлов нет.</p>
<!-- ENDIF -->
```

`'files'` даёт `AND att_img = 0` — только не-изображения.

### Сценарий 4. Вложения всех типов одним списком

```html
<!-- IF {PAGE_ID|att_count('page', $this, '_all_', 'all')} > 0 -->
    <div class="attachments">
        {PAGE_ID|att_display('page', $this, '_all_', 'attacher.display', 'all')}
    </div>
<!-- ELSE -->
    <p class="text-muted">Вложений нет.</p>
<!-- ENDIF -->
```

- `'_all_'` в `att_count` — счёт без фильтра по `att_field`.
- `'all'` в `$type` — без фильтра по `att_img`.
- `att_count` и `att_display` получают **одинаковый** набор параметров `$field` / `$type` — иначе количество и список разойдутся.

### Сценарий 5. Конкретное значение `att_field` (например, `gallery`)

```html
<!-- IF {PAGE_ID|att_count('page', $this, 'gallery', 'images')} > 0 -->
    <div class="gallery-field">
        {PAGE_ID|att_display('page', $this, 'gallery', 'attacher.gallery', 'images')}
    </div>
<!-- ELSE -->
    <p class="text-muted">Галерея не заполнена.</p>
<!-- ENDIF -->
```

Совпадут только записи, загруженные через `att_filebox('page', <ID>, 'gallery', ...)`. Если такой загрузки не было — `ELSE`.

### Сценарий 6. С лимитом и сортировкой

```html
<!-- IF {PAGE_ID|att_count('page', $this, '', 'images')} > 0 -->
    <div class="gallery-top">
        {PAGE_ID|att_display('page', $this, '', 'attacher.grid', 'images', 6, 'att_order DESC')}
    </div>
<!-- ENDIF -->
```

`6` — `LIMIT 6`. `'att_order DESC'` — из белого списка; иначе дефолт.

### Сценарий 7. Админ-шаблон в списке страниц

```html
<!-- IF {ADMIN_PAGE_ID|att_count('page', $this, '', 'images')} > 0 -->
    {ADMIN_PAGE_ID|att_display('page', $this, '', 'attacher.display.cotcp-admin.list', 'images', 1, '', true)}
<!-- ELSE -->
    <i class="fa-regular fa-image text-muted" title="Нет изображений"></i>
<!-- ENDIF -->
```

- `$admin = true` (8-й аргумент) — иначе `cot_tplfile` не найдёт шаблон в админ-теме (см. почему — в разборе `cot_tplfile` выше).
- `$limit = 1` — превью одно.
- Внутри `IF` `att_count` использует `$this` = `{ADMIN_PAGE_ID}`.

---

## Списки (страницы, товары, любые наборы)

В списках работа идёт внутри `<!-- BEGIN: XXX_ROW -->...<!-- END: XXX_ROW -->`. Внутри блока доступны теги строки: `{PAGE_ROW_ID}`, `{PAGE_ROW_TITLE}`, `{PAGE_ROW_URL}` и т. п. — как их присвоил модуль (обычно через `cot_generate_pagetags()`).

### Сценарий 8. Превью в списке

```html
<!-- BEGIN: PAGE_ROW -->
<tr>
    <td class="preview">
        <!-- IF {PAGE_ROW_ID|att_count('page', $this, '', 'images')} > 0 -->
            {PAGE_ROW_ID|att_display('page', $this, '', 'attacher.preview', 'images', 1)}
        <!-- ELSE -->
            <img src="themes/{PHP.cfg.theme}/img/no-image.png"
                 width="64" height="64" alt="">
        <!-- ENDIF -->
    </td>
    <td>{PAGE_ROW_TITLE}</td>
    <td>{PAGE_ROW_DATE}</td>
</tr>
<!-- END: PAGE_ROW -->
```

- `$this` в `att_count` = `{PAGE_ROW_ID}` текущей строки.
- `LIMIT 1` — одна миниатюра. Без `LIMIT` в списке из 100 строк выведется весь набор картинок каждой страницы — визуально ломает таблицу.
- `ELSE` — плейсхолдер, чтобы ячейка не схлопнулась.

### Сценарий 9. Счётчик в тексте

```html
<!-- BEGIN: PAGE_ROW -->
<tr>
    <td>{PAGE_ROW_TITLE}</td>
    <td>
        <!-- IF {PAGE_ROW_ID|att_count('page', $this, '', 'images')} > 0 -->
            {PAGE_ROW_ID|att_count('page', $this, '', 'images')} фото
        <!-- ELSE -->
            &mdash;
        <!-- ENDIF -->
    </td>
</tr>
<!-- END: PAGE_ROW -->
```

- Тег-функция `{PAGE_ROW_ID|att_count(...)}` без `IF` сама выводит число.
- `att_count` вызывается дважды (в `IF` и в тексте) — но это дёшево: результат первого вызова кешируется в `static $a_cache`, второй берёт из кеша без SQL. Кеш живёт в пределах одного HTTP-запроса.

### Сценарий 10. Индикаторы «есть картинки / есть файлы»

```html
<!-- BEGIN: PAGE_ROW -->
<tr>
    <td>
        <!-- IF {PAGE_ROW_ID|att_count('page', $this, '', 'images')} > 0 -->
            <i class="fa-solid fa-image text-primary" title="Есть изображения"></i>
        <!-- ELSE -->
            <i class="fa-regular fa-image text-muted" title="Нет изображений"></i>
        <!-- ENDIF -->
    </td>
    <td>
        <!-- IF {PAGE_ROW_ID|att_count('page', $this, '', 'files')} > 0 -->
            <i class="fa-solid fa-paperclip text-primary" title="Есть файлы"></i>
        <!-- ENDIF -->
    </td>
    <td>{PAGE_ROW_TITLE}</td>
</tr>
<!-- END: PAGE_ROW -->
```

- Два разных `att_count` — разные ключи кеша (`'images'` vs `'files'`), поэтому два SQL в первый раз, дальше из кеша.

### Сценарий 11. Показать картинки прямо в списке (не в превью)

```html
<!-- BEGIN: PAGE_ROW -->
<div class="card">
    <div class="card-body">
        <h5>{PAGE_ROW_TITLE}</h5>
        <!-- IF {PAGE_ROW_ID|att_count('page', $this, '', 'images')} > 0 -->
            <div class="row g-2">
                {PAGE_ROW_ID|att_display('page', $this, '', 'attacher.grid.small', 'images', 3)}
            </div>
        <!-- ENDIF -->
    </div>
</div>
<!-- END: PAGE_ROW -->
```

- `LIMIT 3` — три миниатюры в карточке. Без `IF` на месте `row g-2` был бы пустой `<div>`.

---

## Рекомендуемые сочетания параметров

| Задача | `att_count` / `att_display` параметры |
|--------|---------------------------------------|
| Галерея изображений (пустое поле) | `$field = ''`, `$type = 'images'` |
| Все изображения страницы, включая именованные поля | `$field = '_all_'`, `$type = 'images'` |
| Только документы | `$field = ''`, `$type = 'files'` |
| Всё вперемешку | `$field = '_all_'`, `$type = 'all'` |
| Конкретный слот (например, `gallery`) | `$field = 'gallery'`, `$type = 'images'` |
| Превью в списке | `$limit = 1`, `$type = 'images'` |
| Свой шаблон рендера | `$tpl = 'attacher.ИМЯ'` (файл `attacher.ИМЯ.tpl` в теме или в `plugins/attacher/tpl/`) |
| Админ-список | `$admin = true`, `$tpl = 'attacher.display.cotcp-admin.list'`, `$limit = 1` |
| Обратный порядок | `$order = 'att_order DESC'` (только из белого списка) |
| По порядку загрузки | `$order = 'att_id ASC'` |

---

## Правила, которые нельзя нарушать

1. **`att_count` и `att_display` с одинаковыми параметрами.** Если в `att_count` передаёшь `'_all_'`, то и в `att_display` тоже. Иначе `IF > 0` пропустит, а `att_display` вернёт пустоту (или наоборот).
2. **`att_count` внутри `IF` — всегда перед `att_display`.** Это единственный способ не рендерить пустые обёртки `<div>` вокруг пустого контента.
3. **`''` в `$field` ≠ «все поля».** `''` — только записи с пустым `att_field`. Для «все поля» — `'_all_'`.
4. **В `<!-- IF -->` пробелы вокруг оператора обязательны.** `{X|att_count(...)} > 0` — выражение вычисляется по значению. `{X|att_count(...)}>0` — **выражение становится всегда true** (см. антипаттерны).
5. **`$admin = true` в админ-шаблонах.** Без него `cot_tplfile` не найдёт файл в `themes/admin/{admintheme}/plugins/attacher/` и упадёт на `XTemplate(null)`.
6. **`LIMIT` в списках.** `att_display` без `$limit` в цикле `<!-- BEGIN: ROW -->` — потенциально медленно и визуально неверно. Передавай `1`, `3`, `6` — сколько нужно в ячейку.
7. **`$order` только из белого списка.** Значения: `'att_order ASC'`, `'att_order DESC'`, `'att_id ASC'`, `'att_id DESC'`. Любая другая строка молча заменится на `'att_order ASC'`.

---

В классе `XTemplate` методы `out()`, `text()`, `parse()`. 

Правильная передача, если пишем свои расширения:

```php
// ПЛОХО: echo пишет в output buffer синхронно, в момент вызова функции.
// Модуль затем вызывает $t->parse() / $t->out() — и содержимое шаблона выводится позже или даже дважды.
// Значит, строка от echo окажется в HTML раньше, чем разметка шаблона — до <!DOCTYPE>,
// в <head>, между блоками, где угодно, в зависимости от места вызова. На месте {VAR} — пусто.
echo att_display('page', $id, '', 'attacher.display', 'images');
```

```php
// ПРАВИЛЬНО: положить в переменную шаблона, которую потом выведет XTemplate
$t->assign('PAGE_GALLERY', att_display('page', $id, '', 'attacher.display', 'images'));
// ... далее в модуле: $t->parse('MAIN'); $t->out('MAIN');   или return $t->text('MAIN');
```

---

## Антипаттерны

| Плохо | Что именно произойдёт | Почему | Как правильно |
|-------|-----------------------|--------|---------------|
| `{PAGE_ID\|att_display('page', $this, '', 'attacher.gallery', 'images')}` без `<!-- IF -->` | Если у элемента нет вложений с `att_field=''` и `att_img=1`, `att_display` вернёт пустую строку. HTML-обёртка вокруг тега (`<div class="gallery">…</div>`) останется в выводе пустой | `att_display` возвращает только то, что рендерит шаблон для найденных строк. Если строк 0, цикл `foreach` не выполняется, `MAIN.ATTACHER_ROW` не парсится. Обёртка в родительском `.tpl` от этого не исчезает | Обернуть тег в `<!-- IF {PAGE_ID\|att_count('page', $this, '', 'images')} > 0 -->…<!-- ENDIF -->` |
| `{PAGE_ROW_ID\|att_display(...)}` внутри `<!-- BEGIN: PAGE_ROW -->` без `$limit` | В каждом ряду списка выполнится отдельный `SELECT` без `LIMIT`. При 50 рядах — 50 запросов, каждый вернёт **все** изображения этого элемента. Один ряд может вывести 30 превью в ячейку | `att_display` не кеширует, `LIMIT` по умолчанию = 0 (без ограничения). В списках это и нагрузка, и визуальный хаос | Передавать `$limit = 1` (или 3, 6 — сколько влезает в ячейку) |
| `att_display(..., 'gallery', ...)`, но `att_filebox('page', <ID>, 'gallery', ...)` никогда не вызывался для этих элементов | Всегда пустой результат. В БД нет строк с `att_field='gallery'` — фильтр `AND att_field = 'gallery'` ничего не найдёт | Значение `att_field` пишется только через `att_filebox` → AJAX upload (см. `att_ajax_handle_file_upload` / `att_ajax_handle_url_upload` в коде плагина: `'att_field' => $field`). Без загрузки через этот слот в БД его нет | Сначала убедиться, что загрузка идёт через `att_filebox(..., 'gallery', ...)`, либо использовать `$field = ''` / `'_all_'` |
| `echo att_display(...)` в PHP-коде модуля | Строка HTML попадёт в output buffer в момент вызова функции — то есть **раньше**, чем модуль вызовет `$t->parse()/out()`. Окажется в HTML вне разметки шаблона: до `<!DOCTYPE>`, в `<head>`, между блоками — в зависимости от места вызова. На месте `{VAR}` в шаблоне — пусто | `XTemplate` выводит только значения, присвоенные через `assign()` и отрендеренные через `parse()/text()/out()`. `echo` пишет в буфер напрямую, минуя шаблонизатор, и синхронно — раньше вывода шаблона | `$t->assign('VAR_NAME', att_display(...));` — а потом штатный `$t->parse()/out()` модуля выведет `{VAR_NAME}` |
| `<!-- IF {X\|att_count('page', $this, '_all_', 'images')} > 0 -->` + `{X\|att_display('page', $this, '', 'attacher.gallery', 'images')}` | Условие может дать `true` (есть картинки с любым `att_field`), а `att_display` вернёт пусто (потому что фильтр `att_field=''` их отсекает). Пустая обёртка в выводе | `att_count` и `att_display` — **разные** фильтры по `$field`. `'_all_'` в счётчике ≠ `''` в рендере | В `att_count` и `att_display` передавать **одинаковый** `$field` и одинаковый `$type` |
| Использовать `att_display` для получения данных (массива строк) | Не работает: `att_display` возвращает готовый HTML (string), а не массив. Попытка `foreach` или `array_*` по результату даст ошибку или мусор | Функция только рендерит шаблон через `XTemplate`. Прямой доступ к строкам БД — у `att_get_all` и `att_get` | Если нужен массив — `att_get_all($area, $item, $field, $column)` |
| `<!-- IF {PAGE_ID\|att_count('page', $this, '', 'images')}>0 -->` (без пробелов вокруг оператора) | Выражение **всегда true**, независимо от значения `att_count`. Ветка `IF` рендерится всегда, ветка `ELSE` — никогда | `Cotpl_expr::__construct` токенизирует выражение по пробелам. `{PAGE_ID\|att_count(...)}>0` — один токен (нет пробелов). `cotpl_parseArgument` не распознаёт его как оператор — возвращает как строку. В `evaluate` подставляется значение: `"5>0"` или `"0>0"` — непустая строка, `(bool)` от неё всегда `true` | Пробелы с обеих сторон оператора: `... > 0 -->`. Пробел перед `-->` **не обязателен** — `\s*` в регулярке `Cotpl_block::compile` допускает ноль символов |
| `$order` со значением не из белого списка (`'att_ext ASC'`, `'att_id; DROP …'`) | Молча подставится дефолт `ORDER BY att_order ASC`. Никакой ошибки нет — просто другой порядок | В `att_display` проверка `in_array($order, $allowedOrders, true)`; допустимые значения: `att_order ASC`, `att_order DESC`, `att_id ASC`, `att_id DESC` | Использовать только эти 4 строки |
| `$admin = true` во фронт-шаблоне | На фронте будет использован **админ-шаблон** — `cot_tplfile` с `$admin=true` сканирует `themes/admin/{admintheme}/plugins/attacher/` **первым**, а не вторым. Если файл там есть, во фронт-каталоги поиск не пойдёт. Если нет — фолбэк на фронт-каталоги. Если нигде нет — `cot_tplfile` = `null` → `XTemplate(null)` падает | `cot_tplfile` при `$admin=true` ставит админ-каталоги в начало очереди поиска. Разметка админ-шаблона почти всегда отличается от фронт-шаблона — на фронте будет визуально чужой блок | `$admin = true` только в админ-шаблонах (списки страниц в админке и т. п.). Для фронта — не передавать вовсе (`null` → авто-детект → `false` для имён без `admin` в `[0]`/`[1]`) |
| `att_display` для админ-шаблона с `'admin'` не на позиции `[0]`/`[1]` без `$admin = true` | `cot_tplfile` не найдёт файл в админ-теме (авто-детект не сработает) → `null` → `XTemplate(null)` падает | Авто-детект в `cot_tplfile` смотрит только `$base[0]` и `$base[1]`. Для `'attacher.display.cotcp-admin.list'` `'admin'` на `[2]` (внутри `cotcp-admin`) — невидим для детекта | Явно передать `$admin = true` 8-м аргументом |
