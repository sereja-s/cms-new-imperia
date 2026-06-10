# Структура проекта

## Документация

docs/

Хранит техническую документацию проекта.

---

## Тема

wp-content/themes/

Содержит активную тему проекта.

Текущая тема:

Blocksy Free

---

## Плагины

wp-content/plugins/

Содержит плагины проекта.

Основной собственный плагин:

imperia-core

---

## Imperia Core

Структура:

imperia-core/
├── imperia-core.php
├── inc/
├── assets/
├── modules/
└── languages/

Описание каталогов:

inc/
Общие классы, функции, загрузчики.

assets/
CSS, JavaScript, изображения.

modules/
Отдельные функциональные модули проекта.

languages/
Файлы локализации и переводов.

imperia-core.php
Точка входа плагина.

---

## Архитектура ядра

inc/Core/

Bootstrap.php
Главная точка инициализации плагина.

Constants.php
Глобальные константы проекта.

Autoloader.php
PSR-4 автозагрузка классов.

ModuleManager.php
Система подключения модулей.
