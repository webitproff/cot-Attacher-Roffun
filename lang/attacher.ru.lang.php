<?php
/**
 * Attacher plugin: русский перевод
 *
 * @package Attacher
 * @author Roffun
 * @copyright Copyright (c) Roffun, 2018 - 2019 | https://github.com/Roffun
 * @license BSD License
 **/

defined('COT_CODE') or die('Wrong URL');

$L['info_name'] = 'Загрузка изображений и файлов';
$L['info_desc'] = 'Прикрепление файлов, генерация разных размеров и обработка «налету» изображений в статьях, вставка в визуальный редактор';

/**
* КОНФИГУРАЦИЯ
*/
$L['cfg_folder'] = 'Директория для файлов';
$L['cfg_prefix'] = 'Префикс имен файлов';
$L['cfg_exts'] = 'Разрешенные типы файлов (через запятую, без точек и пробелов)';
$L['cfg_items'] = 'Макс. число вложений в каждом сообщении';
$L['cfg_accept'] = array('Допустимые MIME-типы в окне выбора файлов, через запятую.', 'Пустое значение допускает все типы. Можно использовать специальные типы: image/*, audio/*, video/*');
$L['cfg_filesize'] = 'Макс. размер файлов в байтах';
$L['cfg_chunkSize'] = array('Загружать файлы чанками по (байт)', 'Большие файлы могут быть загружены небольшими частями.
    Это позволяет загружать файлы большего размера, чем указано в ограничениях на загрузку файлов через $_POST.
    (Оставте пустым для отключения)');
    $L['cfg_filespace'] = 'Общее дисковое пространство на каждого пользователя';
    $L['cfg_autoupload'] = 'Начинать закачку автоматически';
$L['cfg_sequential'] = 'Последовательная загрузка вместо параллельной';

/**
* НАСТРОЙКА ЗАГРУЗКИ ИЗОБРАЖЕНИЙ
*/
$L['cfg_sep_orig'] = 'НАСТРОЙКА ЗАГРУЗКИ ИЗОБРАЖЕНИЙ';
$L['cfg_img_resize'] = array('Уменьшать загруженные изображения', 'Загружаемые изображения будут пропорционально уменьшены
    в соотвествии со следующими параметрами');
$L['cfg_img_maxwidht']  = 'Уменьшать ширину изображения до';
$L['cfg_img_maxheight'] = 'Уменьшать высоту изображения до';
$L['cfg_imageconvert'] = 'Конвертировать все изображения в JPG при закачке';
$L['cfg_quality'] = 'Качество JPEG в %';

/**
* НАСТРОЙКА СОЗДАНИЯ КОПИЙ (миниатюр)
*/
$L['cfg_sep_thumbs'] = 'НАСТРОЙКА СОЗДАНИЯ КОПИЙ (миниатюр)';
$L['cfg_thumbs'] = 'Показывать миниатюры изображений?';
$L['cfg_upscale'] = 'Увеличивать изображения, которые меньше размеров миниатюры';
$L['cfg_thumb_x'] = 'Ширина миниатюры по умолчанию';
$L['cfg_thumb_y'] = 'Высота миниатюры по умолчанию';
$L['cfg_thumb_framing'] = 'Режим кадрирования миниатюры по умолчанию';
$L['cfg_thumb_framing_params'] = array(
    'height' => 'По высоте',
    'width'  => 'По ширине',
    'auto'   => 'Авто',
    'crop'   => 'Кадрировать'
);
$L['cfg_thumb_big_width'] = 'Ширина большой миниатюры';
$L['cfg_thumb_big_height'] = 'Высота большой миниатюры';
$L['cfg_thumb_big_framing'] = 'Метод обрезки большой миниатюры';

/**
* ВОДЯНОЙ ЗНАК И ФОНОВАЯ ТЕКСТУРА
*/
$L['cfg_sep_wmark_bg'] = 'ВОДЯНОЙ ЗНАК И ФОНОВАЯ ТЕКСТУРА';
$L['cfg_thumb_watermark'] = array('Водяной знак для миниатюр', 'Путь к файлу водяного знака. Поддерживаются png с прозрачностью.
    (Оставте пустым, чтобы не ставить водяные знаки)');
$L['cfg_thumb_wm_x'] = array('Водяной знак. Мин. ширина', 'Водяной знак будет поставлен на миниатюру только если ее ширина
    больше заданной');
$L['cfg_thumb_wm_y'] = array('Водяной знак. Мин. высота', 'Водяной знак будет поставлен на миниатюру только если ее высота
    больше заданной');
$L['cfg_bg_image'] = 'Путь к файлу фоновой текстуры';
$L['cfg_bg_space'] = 'Размер отступа для заполнения текстурой';
$L['cfg_bg_space_hint'] = '0 для отключения';

/**
* ОБРАБОТКА «НАЛЕТУ» В СТАТЬЯХ
*/
$L['cfg_sep_replacement'] = 'ОБРАБОТКА «НАЛЕТУ» В СТАТЬЯХ';
$L['cfg_thumb_clickable'] = 'Как выводить изображения в статьях';
$L['cfg_thumb_clickable_params'] = 'По умолчанию,Все ссылкой,Все изображениями';
$L['cfg_thumb_alt_to_title'] = array('Создать title для ссылок из содержимого alt','<i>работает только при включенном режиме вывода ссылкой</i>');
$L['cfg_thumb_strip_style'] = 'Вырезать атрибут style у изображений';
$L['cfg_thumb_to_picture'] = 'Преобразовать все изображения в адаптивный PICTURE';
$L['cfg_thumb_wrapper'] = 'Создать контейнер (span)';
$L['cfg_thumb_wrapper_class'] = 'Имена классов для контейнера span';

$L['att_add'] = 'Добавить файлы';
$L['att_attach'] = 'Прикрепить файлы';
$L['att_attachment'] = 'Прикрепленный файл';
$L['att_attachments'] = 'Вложения';
$L['att_cancel'] = 'Отменить закачку';
$L['att_cleanup'] = 'Уборка';
$L['att_allthumbs_remove'] = 'Удалить все миниатюры';
$L['att_cleanup_confirm'] = 'Это удалит все файлы, прикрепленные к более не существующим сообщениям. Продолжить?';
$L['att_allthumbs_remove_confirm'] = 'Это удалит все созданные миниатюры изображений, они автоматически сгенерируются из оригинала в процессе обращения. Продолжить?';
$L['att_delete'] = 'Удалить';
$L['att_downloads'] = 'Скачивания';
$L['att_ensure'] = 'Вы уверены?';
$L['att_free'] = 'свободно';
$L['att_filename'] = 'Имя файла';
$L['att_gallery'] = 'Галерея';
$L['att_processing'] = 'Обработка';
$L['att_info'] = 'Информация';
$L['att_item'] = 'Элемент';
$L['att_items_removed'] = 'Элементов удалено';
$L['att_thumbs_removed'] = 'Миниатюры удалены';
$L['att_kb'] = 'КБ';
$L['att_kb_left_of'] = 'КБ осталось, файлы не более';
$L['att_maxsize'] = 'макс. размер файла';
$L['att_of'] = 'из';
$L['att_remove_all'] = 'Удалить все';
$L['att_replace'] = 'Заменить';
$L['att_show_info'] = 'Показать информацию об элементе';
$L['att_size'] = 'Размер';
$L['att_slideshow'] = 'Слайдшоу';
$L['att_start'] = 'Начать';
$L['att_start_upload'] = 'Начать закачку';
$L['att_title'] = 'Название';
$L['att_title_here'] = 'Поместите название здесь';
$L['att_total'] = 'всего';
$L['att_type'] = 'Тип';
$L['att_used'] = 'использовано';
$L['att_user'] = 'Пользователь';
$L['att_your_space'] = 'Ваше пространство';
$L['att_drag_here'] = 'Перетащите файлы сюда';
$L['att_check'] = 'Отметить';
$L['att_check_all'] = 'Отметить все';

// Ошибки
$L['att_err_db'] = 'Ошибка базы данных';
$L['att_err_delete'] = 'Невозможно удалить вложение';
$L['att_err_move'] = 'Невозможно переместить загруженный файл';
$L['att_err_noitems'] = 'Не найдено ни одного элемента';
$L['att_err_nospace'] = 'Недостаточно персонального дискового пространства';
$L['att_err_perms'] = 'У вас недостаточно прав для данного действия';
$L['att_err_replace'] = 'Невозможно заменить файл';
$L['att_err_thumb'] = 'Невозможно создать предпросмотр изображения';
$L['att_err_title'] = 'Название файла оставлено пустым';
$L['att_err_toobig'] = 'Файл слишком велик';
$L['att_err_type'] = 'Такой тип файлов не разрешен';
$L['att_err_upload'] = 'Не удалось загрузить файл';
$L['att_err_count'] = 'Превышено максимальное число файлов';

$L['att_button_small_title'] = cot::$cfg['plugin']['attacher']['thumb_x'] . '*' . cot::$cfg['plugin']['attacher']['thumb_y'];
$L['att_button_big_title'] = cot::$cfg['plugin']['attacher']['thumb_big_width'] . '*' . cot::$cfg['plugin']['attacher']['thumb_big_height'];
$L['visual_only'] = 'Вставка доступна в визуальном режиме, выйдите из режима кода!';
