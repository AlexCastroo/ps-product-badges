<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductBadges extends Module
{
    public function __construct()
    {
        $this->name          = 'productbadges';
        $this->tab           = 'front_office_features';
        $this->version       = '1.0.0';
        $this->author        = 'AlexCastro';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.8.0',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->l('Product Badges');
        $this->description = $this->l('Assign custom badges to products and display them over product images.');
    }

    public function install(): bool
    {
        if (!parent::install()) {
            return false;
        }

        if (!include(dirname(__FILE__) . '/sql/install.php')) {
            return false;
        }

        if (!$this->installTab()) {
            return false;
        }

        $hooks = [
            'displayAdminProductsExtra',
            'displayProductListItem',
            'displayProduct',
            'displayHeader',
            'displayBackOfficeHeader',
            'actionProductAdd',
            'actionProductUpdate',
        ];

        foreach ($hooks as $hook) {
            if (!$this->registerHook($hook)) {
                return false;
            }
        }

        Configuration::updateValue('PRODUCTBADGES_ENABLED', 1);
        Configuration::updateValue('PRODUCTBADGES_SHOW_LISTING', 1);
        Configuration::updateValue('PRODUCTBADGES_SHOW_PRODUCT', 1);
        Configuration::updateValue('PRODUCTBADGES_MAX_BADGES', 3);

        return true;
    }

    public function uninstall(): bool
    {
        if (!include(dirname(__FILE__) . '/sql/uninstall.php')) {
            return false;
        }

        $this->uninstallTab();

        Configuration::deleteByName('PRODUCTBADGES_ENABLED');
        Configuration::deleteByName('PRODUCTBADGES_SHOW_LISTING');
        Configuration::deleteByName('PRODUCTBADGES_SHOW_PRODUCT');
        Configuration::deleteByName('PRODUCTBADGES_MAX_BADGES');

        return parent::uninstall();
    }

    private function installTab(): bool
    {
        $tab = new Tab();
        $tab->active      = 1;
        $tab->class_name  = 'AdminProductBadges';
        $tab->id_parent   = (int) Tab::getIdFromClassName('AdminCatalog');
        $tab->module      = $this->name;

        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[$lang['id_lang']] = 'Product Badges';
        }

        return $tab->add();
    }

    private function uninstallTab(): bool
    {
        $id_tab = (int) Tab::getIdFromClassName('AdminProductBadges');
        if (!$id_tab) {
            return true;
        }

        $tab = new Tab($id_tab);

        return $tab->delete();
    }

    public function getContent(): string
    {
        return '';
    }

    // -------------------------------------------------------------------------
    // DB helpers
    // -------------------------------------------------------------------------

    public function getBadgesByProduct(int $id_product): array
    {
        $id_lang = (int) $this->context->language->id;
        $max     = (int) Configuration::get('PRODUCTBADGES_MAX_BADGES');

        $sql = 'SELECT b.`id_badge`, b.`bgcolor`, b.`textcolor`, b.`position`, bl.`text`
                FROM `' . _DB_PREFIX_ . 'product_badge` b
                INNER JOIN `' . _DB_PREFIX_ . 'product_badge_lang` bl
                    ON b.`id_badge` = bl.`id_badge` AND bl.`id_lang` = ' . $id_lang . '
                INNER JOIN `' . _DB_PREFIX_ . 'product_badge_product` bp
                    ON b.`id_badge` = bp.`id_badge`
                WHERE bp.`id_product` = ' . (int) $id_product . '
                AND b.`active` = 1
                LIMIT ' . $max;

        return Db::getInstance()->executeS($sql) ?: [];
    }

    public function getActiveBadges(int $id_lang): array
    {
        $sql = 'SELECT b.`id_badge`, b.`bgcolor`, b.`textcolor`, b.`position`, bl.`text`
                FROM `' . _DB_PREFIX_ . 'product_badge` b
                INNER JOIN `' . _DB_PREFIX_ . 'product_badge_lang` bl
                    ON b.`id_badge` = bl.`id_badge` AND bl.`id_lang` = ' . (int) $id_lang . '
                WHERE b.`active` = 1
                ORDER BY b.`id_badge` ASC';

        return Db::getInstance()->executeS($sql) ?: [];
    }

    public function getAssignedBadgeIds(int $id_product): array
    {
        $sql = 'SELECT `id_badge`
                FROM `' . _DB_PREFIX_ . 'product_badge_product`
                WHERE `id_product` = ' . (int) $id_product;

        $rows = Db::getInstance()->executeS($sql) ?: [];

        return array_column($rows, 'id_badge');
    }

    public function saveBadgesForProduct(int $id_product, array $badge_ids): void
    {
        $id_shop = (int) $this->context->shop->id;

        Db::getInstance()->delete(
            'product_badge_product',
            '`id_product` = ' . (int) $id_product . ' AND `id_shop` = ' . $id_shop
        );

        if (empty($badge_ids)) {
            return;
        }

        $rows = [];
        foreach ($badge_ids as $id_badge) {
            $rows[] = [
                'id_badge'   => (int) $id_badge,
                'id_product' => (int) $id_product,
                'id_shop'    => $id_shop,
            ];
        }

        Db::getInstance()->insert('product_badge_product', $rows);
    }

    // -------------------------------------------------------------------------
    // Hooks — display front
    // -------------------------------------------------------------------------

    public function hookDisplayProductListItem(array $params): string
    {
        return '';
    }

    public function hookDisplayProduct(array $params): string
    {
        return '';
    }

    public function hookDisplayHeader(): void
    {
    }

    // -------------------------------------------------------------------------
    // Hooks — display back office
    // -------------------------------------------------------------------------

    public function hookDisplayBackOfficeHeader(): void
    {
    }

    public function hookDisplayAdminProductsExtra(array $params): string
    {
        return '';
    }

    // -------------------------------------------------------------------------
    // Hooks — product save
    // -------------------------------------------------------------------------

    public function hookActionProductAdd(array $params): void
    {
        $id_product = (int) $params['product']->id;
        $badge_ids  = (array) Tools::getValue('badge_product_ids', []);
        $this->saveBadgesForProduct($id_product, $badge_ids);
    }

    public function hookActionProductUpdate(array $params): void
    {
        $id_product = (int) $params['product']->id;
        $badge_ids  = (array) Tools::getValue('badge_product_ids', []);
        $this->saveBadgesForProduct($id_product, $badge_ids);
    }
}
