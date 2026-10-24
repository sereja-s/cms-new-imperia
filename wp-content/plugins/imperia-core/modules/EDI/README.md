# Инструкция: восстановление кастомизации EDI после обновления плагина

## Назначение

Эта инструкция описывает, какие изменения необходимо восстановить в оригинальном плагине **E-commerce Data Interchange / EDI** после его обновления.

### Важно

Оригинальный плагин находится примерно здесь:

```text
wp-content/plugins/e-commerce-data-interchange/
```

Наш собственный код находится в:

```text
wp-content/plugins/imperia-core/modules/EDI/
```

При обновлении оригинального EDI его файлы могут быть полностью заменены.

Поэтому **нельзя рассчитывать на сохранение изменений, внесённых непосредственно в оригинальный плагин**.

---

# 1. Кастомизация файла Request.php

## Файл оригинального EDI

```text
wp-content/plugins/e-commerce-data-interchange/src/Request.php
```

В этом файле мы внесли собственное изменение, разрешающее 1С передавать изображения и другие файлы из каталога:

```text
import_files/
```

Стандартный EDI разрешает только определённые типы файлов:

```text
import*.xml
offers*.xml
*.zip
orders-*.xml
```

Но 1С передаёт изображения отдельными запросами:

```text
mode=file
filename=import_files/...
```

Поэтому после обновления EDI необходимо снова добавить эту кастомизацию.

---

# 2. Что именно добавить в Request.php

В классе:

```php
BytePerfect\EDI\Request
```

найти блок констант с масками файлов:

```php
const XML_BITRIX_FILENAME_MASK = ...;
const XML_IMPORT_FILENAME_MASK = ...;
const ZIP_IMPORT_FILENAME_MASK = ...;
const SALE_IMPORT_FILENAME_MASK = ...;
```

После них добавить:

```php
// ==========================================================
// IMPERIA CUSTOM CODE
// ==========================================================
// Разрешаем EDI принимать файлы, которые 1С передаёт
// внутри каталога import_files/.
//
// Пример:
// import_files/123456/image.jpg
// import_files/123456/image.jpeg
// import_files/123456/image.png
//
// Эта константа только разрешает путь файла.
// Загрузкой файла занимается оригинальный EDI.
// ==========================================================
const IMPORT_FILES_MASK = '/^import_files\/.+$/i';
```

---

# 3. Изменение метода set_filename()

В том же файле:

```text
wp-content/plugins/e-commerce-data-interchange/src/Request.php
```

найти метод:

```php
protected function set_filename(): void
```

Внутри проверки допустимого имени файла должен появиться дополнительный элемент:

```php
! preg_match(self::IMPORT_FILES_MASK, $this->filename)
```

Итоговая проверка должна выглядеть примерно так:

```php
if (
    'test' !== $this->type
    &&
    ! empty($this->filename)
    &&
    ! preg_match(self::XML_BITRIX_FILENAME_MASK, $this->filename)
    &&
    ! preg_match(self::XML_IMPORT_FILENAME_MASK, $this->filename)
    &&
    ! preg_match(self::ZIP_IMPORT_FILENAME_MASK, $this->filename)
    &&
    ! preg_match(self::SALE_IMPORT_FILENAME_MASK, $this->filename)

    // IMPERIA:
    // разрешаем файлы из import_files/,
    // передаваемые 1С в режиме mode=file.
    &&
    ! preg_match(self::IMPORT_FILES_MASK, $this->filename)
) {
    throw new Exception('Unexpected file name: ' . $this->filename);
}
```

---

# 4. Что эта модификация НЕ делает

Очень важно не переносить лишнюю логику.

Наш код в `Request.php`:

```php
const IMPORT_FILES_MASK = '/^import_files\/.+$/i';
```

и:

```php
! preg_match(self::IMPORT_FILES_MASK, $this->filename)
```

**НЕ загружает изображения самостоятельно.**

Он только разрешает EDI принять имя файла.

После этого работает стандартный механизм EDI:

```text
1С
 ↓
mode=file
 ↓
Request
 ↓
AbstractInterchange::action_file()
 ↓
EDI::filesystem()->receive_file()
 ↓
DirectFileSystem::receive_file()
```

Поэтому после обновления EDI **не нужно создавать отдельную систему загрузки изображений в Imperia**.

---

# 5. Кастомный код EDI должен оставаться в Imperia

Наши собственные изменения, связанные с логикой обмена, не должны переноситься обратно в оригинальный EDI без необходимости.

Они должны находиться здесь:

```text
wp-content/plugins/imperia-core/modules/EDI/
```

В частности, среди текущих компонентов:

```text
EDI/
├── Hooks/
│   └── Product/
│       └── ProductPrintNameParser.php
│
├── ...
├── ParserRegistry
├── ProductGalleryHook
├── DisablePendingStatus
└── ...
```

Названия и расположение конкретных классов необходимо проверять по текущей версии модуля.

---

# 6. ProductPrintNameParser.php

Наш код:

```text
wp-content/plugins/imperia-core/modules/EDI/Hooks/Product/ProductPrintNameParser.php
```

необходимо сохранять в Imperia.

Он отвечает за использование:

```text
Название для печати
```

из 1С.

При обновлении оригинального EDI этот файл **не нужно копировать обратно в оригинальный EDI**.

Он должен продолжать работать через хуки EDI.

### Важное замечание

Нельзя без необходимости менять момент выполнения этого обработчика.

В частности, нужно помнить, что оригинальный EDI использует:

```text
GUID
 ↓
SKU
 ↓
NAME
```

для сопоставления товара.

Поэтому изменение `name` слишком рано может повлиять на механизм:

```php
ProductMatcher::locate(
    $guid,
    $sku,
    $name
);
```

Название для печати не должно ломать исходное имя, используемое для сопоставления.

---

# 7. ProductGalleryHook.php

Наш обработчик галереи:

```text
wp-content/plugins/imperia-core/modules/EDI/...
```

также должен оставаться в Imperia.

Он отвечает за нашу дополнительную логику изображений.

После обновления оригинального EDI необходимо проверить:

1. главный thumbnail;
2. галерею;
3. товар с одной картинкой;
4. товар с несколькими картинками;
5. повторный обмен существующего товара.

---

# 8. DisablePendingStatus

Наш класс:

```text
DisablePendingStatus
```

также находится в Imperia и не должен переноситься в оригинальный EDI.

В текущем обмене он даёт диагностическую запись:

```text
[Imperia] DisablePendingStatus: skipping mass pending status update.
```

Это означает, что наш модуль отключает стандартное массовое изменение статуса товаров на:

```text
pending
```

После обновления EDI необходимо проверить, что этот обработчик снова зарегистрирован.

---

# 9. ParserRegistry

Наш:

```text
ParserRegistry
```

также должен находиться в:

```text
imperia-core/modules/EDI/
```

В логе успешной загрузки модуля сейчас видно:

```text
[Imperia] ParserRegistry, ProductPrintNameParser, ProductGalleryHook, DisablePendingStatus->register
```

После обновления EDI эта строка должна снова появляться при EDI-запросах.

Если она появляется — модуль Imperia зарегистрировал свои обработчики.

---

# 10. Диагностический код в Request.php

В текущем `Request.php` также был временный диагностический код:

```php
error_log(
    sprintf(
        '[IMPERIA EDI DIAGNOSTIC] EDI Request started | URI=%s | mode=%s | type=%s | filename=%s',
        $_SERVER['REQUEST_URI'] ?? '',
        $_REQUEST['mode'] ?? '',
        $_REQUEST['type'] ?? '',
        $_REQUEST['filename'] ?? ''
    )
);
```

Сейчас он закомментирован.

После обновления EDI **не обязательно восстанавливать его**, если диагностика больше не нужна.

Это временный диагностический код, а не функциональная часть интеграции.

---

# 11. Порядок действий после обновления EDI

## Шаг 1. Сделать резервную копию

Перед обновлением сохранить:

```text
wp-content/plugins/e-commerce-data-interchange/
```

и:

```text
wp-content/plugins/imperia-core/modules/EDI/
```

---

## Шаг 2. Обновить оригинальный EDI

Обновить:

```text
e-commerce-data-interchange
```

После обновления считать оригинальный EDI чистым.

Не рассчитывать на сохранение ручных изменений.

---

## Шаг 3. Проверить Request.php

Открыть:

```text
wp-content/plugins/e-commerce-data-interchange/src/Request.php
```

Проверить наличие:

```php
const IMPORT_FILES_MASK = '/^import_files\/.+$/i';
```

и:

```php
! preg_match(self::IMPORT_FILES_MASK, $this->filename)
```

Если их нет — восстановить.

---

## Шаг 4. Не переносить Imperia-классы обратно в EDI

Не копировать в:

```text
e-commerce-data-interchange/src/
```

наши:

```text
ProductPrintNameParser
ProductGalleryHook
DisablePendingStatus
ParserRegistry
```

и другие классы Imperia.

Они должны загружаться самим:

```text
Imperia Core
```

---

# 12. Проверка после обновления

Запустить тестовый обмен 1С.

Проверить в `debug.log`:

```text
[Imperia] ParserRegistry, ProductPrintNameParser, ProductGalleryHook, DisablePendingStatus->register
```

Затем проверить:

```text
mode=checkauth
mode=init
mode=file
mode=import
```

Особенно для изображений:

```text
filename=import_files/...
```

Если появляется:

```text
Unexpected file name:
```

значит в `Request.php` не восстановлена:

```php
IMPORT_FILES_MASK
```

---

# 13. Проверка дополнительных реквизитов

После обмена проверить, что в диагностике присутствуют:

```text
[EDI DIAG][ATTRIBUTES RECEIVED]
```

и:

```text
[EDI DIAG][ATTRIBUTES SAVE]
```

Например:

```text
[АРТИКУЛ ДЛЯ САЙТА] => LAM-WOOD-DUB-DEL-8-V4
```

Это означает:

```text
1С
 ↓
XML
 ↓
EDI parser
 ↓
дополнительный реквизит
 ↓
Imperia
 ↓
сохранение
```

работает.

---

# 14. Отдельно проверить offers.xml

После обновления обязательно смотреть `debug.log` на ошибки в:

```text
src/Parsers/OffersParser.php
```

В текущем тесте была обнаружена ошибка:

```text
PHP Warning:
Undefined variable $product_id
```

в:

```text
OffersParser.php on line 49
```

Это отдельная проблема.

Не следует автоматически считать её связанной с:

```text
АРТИКУЛ ДЛЯ САЙТА
```

или:

```text
Название для печати
```

Сначала необходимо проверить оригинальный код новой версии EDI.

---

# 15. Главный принцип поддержки

## Оригинальный EDI

```text
wp-content/plugins/e-commerce-data-interchange/
```

Здесь стараемся держать **минимум изменений**.

В настоящее время известная обязательная кастомизация:

```text
Request.php
└── разрешение import_files/*
```

---

## Imperia Core

```text
wp-content/plugins/imperia-core/modules/EDI/
```

Здесь находится наша бизнес-логика:

```text
ProductPrintNameParser
ProductGalleryHook
DisablePendingStatus
ParserRegistry
другие EDI-сервисы и обработчики
```

Эта логика должна переживать обновления оригинального EDI без копирования её внутрь плагина.

---

# 16. Краткая памятка

После обновления EDI:

```text
[1] Обновить e-commerce-data-interchange

[2] Открыть:
    e-commerce-data-interchange/src/Request.php

[3] Проверить:
    const IMPORT_FILES_MASK = '/^import_files\/.+$/i';

[4] Проверить:
    ! preg_match(self::IMPORT_FILES_MASK, $this->filename)

[5] Если отсутствуют — добавить.

[6] НЕ переносить обратно в EDI:
    ProductPrintNameParser
    ProductGalleryHook
    DisablePendingStatus
    ParserRegistry
    другие классы Imperia.

[7] Проверить лог:
    [Imperia] ParserRegistry,
    ProductPrintNameParser,
    ProductGalleryHook,
    DisablePendingStatus->register

[8] Выполнить тестовый обмен.

[9] Проверить:
    import.xml
    offers.xml
    изображения
    дополнительные реквизиты
    название для печати
    сопоставление существующих товаров.

[10] Если появляются ошибки —
     сначала сравнить новую версию оригинального EDI
     со старой версией, а не сразу менять Imperia.
```

---

## Важное правило на будущее

**Любое изменение, которое мы вынуждены делать непосредственно в оригинальном EDI, нужно сразу фиксировать здесь.**

Если после обновления обнаружено новое ручное изменение в:

```text
e-commerce-data-interchange/
```

его необходимо добавить в эту инструкцию.

Цель архитектуры:

```text
ОРИГИНАЛЬНЫЙ EDI
        │
        │ стандартный механизм
        ▼
┌─────────────────────┐
│      EDI Plugin     │
└─────────────────────┘
        ▲
        │ hooks / filters
        │
┌─────────────────────┐
│    Imperia EDI      │
│                     │
│ ProductPrintName    │
│ ProductGallery      │
│ Attributes          │
│ Matching            │
│ PendingStatus       │
└─────────────────────┘
```

Чем меньше изменений непосредственно в `e-commerce-data-interchange`, тем проще и безопаснее обновлять EDI.
