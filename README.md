# Компонент Oasis для Joomla + VirtueMart

Компонент импорта товаров из [oasiscatalog.com](https://www.oasiscatalog.com/) в cms Joomla + VirtueMart

## Требования

PHP 7.4+

Joomla 4.1.0

VirtueMart 4.0.12

## Как установить

Пожалуйста, прочтите инструкцию по установке, включенную в репозиторий.

## Настройка компонента

- В настройках компонента Oasis введите API ключ и ID пользователя из личного кабинета https://www.oasiscatalog.com/cabinet/integrations.
- Необходимо выставить опции импорта и сохранить настройки модуля, для того чтобы задания в CRON работали по данным настройкам.
- Поле установки и настройки модуля появится возможность выгрузить заказ в Oasiscatalog, для этого необходимо воспользоваться кнопкой «Выгрузить в Oasis» на странице компонента во вкладке "Заказы".

## Запуск импорта

Импорт товаров, пример:

```
php /home/m/myaccount/site.ru/public_html/cli/joomla.php oasis:import --key=YOUR_KEY
```

Обновление остатков, пример:

```
php /home/m/myaccount/site.ru/public_html/cli/joomla.php oasis:import --key=YOUR_KEY --up
```

## Лицензия

Стандартная общественная лицензия GNU версии 3 (GPLv3)

## Ссылки

- [Oasiscatalog](https://www.oasiscatalog.com/)
- [Forum oasiscatalog](https://forum.oasiscatalog.com/)
