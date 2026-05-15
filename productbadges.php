<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductBadge extends ObjectModel
{
    public $bgcolor;
    public $textcolor;
    public $position;
    public $active;
    public $date_add;
    public $date_upd;

    /** @var string Multilang */
    public $text;

    public function delete(): bool
    {
        Db::getInstance()->delete('product_badge_product', '`id_badge` = ' . (int) $this->id);
        Db::getInstance()->delete('product_badge_lang',    '`id_badge` = ' . (int) $this->id);
        return parent::delete();
    }

    public static $definition = [
        'table'     => 'product_badge',
        'primary'   => 'id_badge',
        'multilang' => true,
        'fields'    => [
            'bgcolor'   => ['type' => self::TYPE_STRING, 'validate' => 'isColor',       'size' => 7,   'required' => true],
            'textcolor' => ['type' => self::TYPE_STRING, 'validate' => 'isColor',       'size' => 7,   'required' => true],
            'position'  => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 20,  'required' => true],
            'active'    => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'],
            'date_add'  => ['type' => self::TYPE_DATE,   'validate' => 'isDate'],
            'date_upd'  => ['type' => self::TYPE_DATE,   'validate' => 'isDate'],
            'text'      => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255, 'required' => true, 'lang' => true],
        ],
    ];
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
            'displayProductPriceBlock',
            'displayFooterProduct',
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
        $output = '';

        if (Tools::isSubmit('submit_productbadges_config')) {
            Configuration::updateValue('PRODUCTBADGES_ENABLED',      (int) Tools::getValue('PRODUCTBADGES_ENABLED'));
            Configuration::updateValue('PRODUCTBADGES_SHOW_LISTING', (int) Tools::getValue('PRODUCTBADGES_SHOW_LISTING'));
            Configuration::updateValue('PRODUCTBADGES_SHOW_PRODUCT', (int) Tools::getValue('PRODUCTBADGES_SHOW_PRODUCT'));
            Configuration::updateValue('PRODUCTBADGES_MAX_BADGES',   max(1, (int) Tools::getValue('PRODUCTBADGES_MAX_BADGES')));
            $output .= $this->displayConfirmation($this->l('Settings saved.'));
        }

        $badge_list_url = $this->context->link->getAdminLink('AdminProductBadges');
        $output .= '<a href="' . $badge_list_url . '" class="btn btn-default" style="margin-bottom:15px;">'
                 . '<i class="icon-tag"></i> ' . $this->l('Manage badges') . '</a>';

        return $output . $this->renderConfigForm();
    }

    private function renderConfigForm(): string
    {
        $fields_form = [
            'legend' => [
                'title' => $this->l('Settings'),
                'icon'  => 'icon-cogs',
            ],
            'input' => [
                [
                    'type'   => 'switch',
                    'label'  => $this->l('Enable module'),
                    'name'   => 'PRODUCTBADGES_ENABLED',
                    'values' => [
                        ['id' => 'enabled_on',  'value' => 1, 'label' => $this->l('Yes')],
                        ['id' => 'enabled_off', 'value' => 0, 'label' => $this->l('No')],
                    ],
                ],
                [
                    'type'   => 'switch',
                    'label'  => $this->l('Show on product listings'),
                    'name'   => 'PRODUCTBADGES_SHOW_LISTING',
                    'values' => [
                        ['id' => 'listing_on',  'value' => 1, 'label' => $this->l('Yes')],
                        ['id' => 'listing_off', 'value' => 0, 'label' => $this->l('No')],
                    ],
                ],
                [
                    'type'   => 'switch',
                    'label'  => $this->l('Show on product page'),
                    'name'   => 'PRODUCTBADGES_SHOW_PRODUCT',
                    'values' => [
                        ['id' => 'product_on',  'value' => 1, 'label' => $this->l('Yes')],
                        ['id' => 'product_off', 'value' => 0, 'label' => $this->l('No')],
                    ],
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Max badges per product'),
                    'name'  => 'PRODUCTBADGES_MAX_BADGES',
                    'class' => 'fixed-width-xs',
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'name'  => 'submit_productbadges_config',
            ],
        ];

        $helper                            = new HelperForm();
        $helper->module                    = $this;
        $helper->name_controller           = $this->name;
        $helper->token                     = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex              = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language     = (int) $this->context->language->id;
        $helper->allow_employee_form_lang  = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->submit_action             = 'submit_productbadges_config';
        $helper->tpl_vars                  = [
            'fields_value' => [
                'PRODUCTBADGES_ENABLED'      => (int) Configuration::get('PRODUCTBADGES_ENABLED'),
                'PRODUCTBADGES_SHOW_LISTING' => (int) Configuration::get('PRODUCTBADGES_SHOW_LISTING'),
                'PRODUCTBADGES_SHOW_PRODUCT' => (int) Configuration::get('PRODUCTBADGES_SHOW_PRODUCT'),
                'PRODUCTBADGES_MAX_BADGES'   => (int) Configuration::get('PRODUCTBADGES_MAX_BADGES'),
            ],
            'languages'   => $this->context->controller->getLanguages(),
            'id_language' => (int) $this->context->language->id,
        ];

        return $helper->generateForm([['form' => $fields_form]]);
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
        if (!Configuration::get('PRODUCTBADGES_ENABLED') || !Configuration::get('PRODUCTBADGES_SHOW_LISTING')) {
            return '';
        }

        $id_product = (int) ($params['product']['id_product'] ?? $params['product']['id'] ?? 0);

        return $this->renderFrontBadges($id_product);
    }

    public function hookDisplayProductPriceBlock(array $params): string
    {
        if (($params['type'] ?? '') !== 'weight') {
            return '';
        }
        if ($this->context->controller->php_self === 'product') {
            return '';
        }
        if (!Configuration::get('PRODUCTBADGES_ENABLED') || !Configuration::get('PRODUCTBADGES_SHOW_LISTING')) {
            return '';
        }

        $id_product = (int) ($params['product']['id_product'] ?? $params['product']['id'] ?? 0);
        if (!$id_product) {
            return '';
        }

        $badges = $this->getBadgesByProduct($id_product);
        if (empty($badges)) {
            return '';
        }

        $this->context->smarty->assign([
            'pb_badges'     => $badges,
            'pb_id_product' => $id_product,
        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'productbadges/views/templates/front/badges_listing.tpl'
        );
    }

    public function hookDisplayFooterProduct(array $params): string
    {
        if (!Configuration::get('PRODUCTBADGES_ENABLED') || !Configuration::get('PRODUCTBADGES_SHOW_PRODUCT')) {
            return '';
        }

        $id_product = (int) ($params['product']['id_product'] ?? $params['product']['id'] ?? 0);
        if (!$id_product) {
            return '';
        }

        $badges = $this->getBadgesByProduct($id_product);
        if (empty($badges)) {
            return '';
        }

        $this->context->smarty->assign('pb_badges', $badges);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'productbadges/views/templates/front/badges_product.tpl'
        );
    }

    private function renderFrontBadges(int $id_product): string
    {
        if (!$id_product) {
            return '';
        }

        $badges = $this->getBadgesByProduct($id_product);
        if (empty($badges)) {
            return '';
        }

        $this->context->smarty->assign('pb_badges', $badges);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'productbadges/views/templates/front/badges.tpl'
        );
    }

    public function hookDisplayHeader(): void
    {
        if (!Configuration::get('PRODUCTBADGES_ENABLED')) {
            return;
        }

        $this->context->controller->addCSS($this->_path . 'views/css/productbadges.css');
        $this->context->controller->addJS($this->_path . 'views/js/productbadges.js');
    }

    // -------------------------------------------------------------------------
    // Hooks — display back office
    // -------------------------------------------------------------------------

    public function hookDisplayBackOfficeHeader(): void
    {
    }

    public function hookDisplayAdminProductsExtra(array $params): string
    {
        $id_product   = (int) ($params['id_product'] ?? Tools::getValue('id_product'));
        $id_lang      = (int) $this->context->language->id;
        $assigned_ids = $id_product ? $this->getAssignedBadgeIds($id_product) : [];
        $badges       = $this->getActiveBadges($id_lang);

        foreach ($badges as &$badge) {
            $badge['is_assigned'] = in_array($badge['id_badge'], $assigned_ids);
        }
        unset($badge);

        $this->context->smarty->assign([
            'pb_badges'     => $badges,
            'pb_id_product' => $id_product,
            'pb_ajax_url'   => $this->context->link->getAdminLink('AdminProductBadges') . '&action=saveBadges',
        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'productbadges/views/templates/admin/product_badges_tab.tpl'
        );
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
