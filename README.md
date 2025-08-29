# Attacher Plugin for Cotonti Siena 0.9.26+, PHP 8.4+

**Attacher** is a powerful and versatile extension for Cotonti CMF (version 0.9.26+, PHP 8.4+), designed for managing the upload, processing, and display of files and images. The plugin allows attaching files to various system objects, such as articles, forum posts, product cards, or custom modules, and provides flexible tools for working with media files. It supports asynchronous uploads, image processing, integration with visual editors, and display as galleries or download lists.

<img src="https://raw.githubusercontent.com/webitproff/cot-Attacher-Roffun/refs/heads/main/attacher.png" alt="Attacher Plugin for Cotonti Siena 0.9.26+, PHP 8.4+">


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

# Плагин attache for CMF Cotonti Siena 0.9.26+, PHP 8.4+


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
