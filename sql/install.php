<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = [];

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'product_badge` (
    `id_badge`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bgcolor`    VARCHAR(7)   NOT NULL DEFAULT \'#000000\',
    `textcolor`  VARCHAR(7)   NOT NULL DEFAULT \'#ffffff\',
    `position`   VARCHAR(20)  NOT NULL DEFAULT \'top-left\',
    `active`     TINYINT(1)   UNSIGNED NOT NULL DEFAULT 1,
    `date_add`   DATETIME     NOT NULL,
    `date_upd`   DATETIME     NOT NULL,
    PRIMARY KEY (`id_badge`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'product_badge_lang` (
    `id_badge`   INT UNSIGNED NOT NULL,
    `id_lang`    INT UNSIGNED NOT NULL,
    `text`       VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id_badge`, `id_lang`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'product_badge_product` (
    `id_badge`   INT UNSIGNED NOT NULL,
    `id_product` INT UNSIGNED NOT NULL,
    `id_shop`    INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_badge`, `id_product`, `id_shop`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

$db = Db::getInstance();
foreach ($sql as $query) {
    if (!$db->execute($query)) {
        return false;
    }
}

return true;
