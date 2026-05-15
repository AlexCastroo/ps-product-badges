<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'productbadges/productbadges.php';

class AdminProductBadgesController extends AdminController
{
    public function __construct()
    {
        $this->table      = 'product_badge';
        $this->className  = 'ProductBadge';
        $this->identifier = 'id_badge';
        $this->bootstrap  = true;
        $this->lang       = true;

        parent::__construct();

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->fields_list = [
            'id_badge' => [
                'title'  => $this->l('ID'),
                'align'  => 'center',
                'class'  => 'fixed-width-xs',
            ],
            'text' => [
                'title' => $this->l('Text'),
            ],
            'bgcolor' => [
                'title'           => $this->l('Background'),
                'callback'        => 'renderColorSwatch',
                'search'          => false,
                'orderby'         => false,
            ],
            'textcolor' => [
                'title'           => $this->l('Text color'),
                'callback'        => 'renderColorSwatch',
                'search'          => false,
                'orderby'         => false,
            ],
            'position' => [
                'title'  => $this->l('Position'),
                'align'  => 'center',
            ],
            'active' => [
                'title'   => $this->l('Active'),
                'align'   => 'text-center',
                'active'  => 'status',
                'type'    => 'bool',
                'orderby' => false,
            ],
        ];
    }

    public function renderColorSwatch(string $color): string
    {
        $safe = htmlspecialchars($color, ENT_QUOTES);

        return '<span style="display:inline-block;width:20px;height:14px;background:' . $safe
             . ';border:1px solid #aaa;vertical-align:middle;border-radius:2px;"></span> ' . $safe;
    }

    public function ajaxProcessSaveBadges(): void
    {
        $id_product = (int) Tools::getValue('id_product');
        if (!$id_product) {
            die(json_encode(['success' => false]));
        }

        $module    = Module::getInstanceByName('productbadges');
        $badge_ids = (array) Tools::getValue('badge_product_ids', []);
        $module->saveBadgesForProduct($id_product, $badge_ids);

        die(json_encode(['success' => true]));
    }

    public function renderForm(): string
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Badge'),
                'icon'  => 'icon-tag',
            ],
            'input' => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Text'),
                    'name'     => 'text',
                    'lang'     => true,
                    'required' => true,
                    'hint'     => $this->l('Label shown over the product image.'),
                ],
                [
                    'type'     => 'color',
                    'label'    => $this->l('Background color'),
                    'name'     => 'bgcolor',
                    'required' => true,
                ],
                [
                    'type'     => 'color',
                    'label'    => $this->l('Text color'),
                    'name'     => 'textcolor',
                    'required' => true,
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Position'),
                    'name'    => 'position',
                    'options' => [
                        'query' => [
                            ['id' => 'top-left',  'name' => $this->l('Top left')],
                            ['id' => 'top-right', 'name' => $this->l('Top right')],
                        ],
                        'id'   => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type'   => 'switch',
                    'label'  => $this->l('Active'),
                    'name'   => 'active',
                    'values' => [
                        ['id' => 'active_on',  'value' => 1, 'label' => $this->l('Yes')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        return parent::renderForm();
    }
}
