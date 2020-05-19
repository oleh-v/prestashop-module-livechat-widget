<?php
/**
* 2007-2018 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for you
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2018 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Livechat extends Module
{
    /**
     * @var string
     */
    protected $html = '';

    /**
     * @var string
     */
    protected $confirm = '';

    /**
     * @var string
     */
    protected $inform = '';

    /**
     * @var string
     */
    protected $warn = '';

    /**
     * @var string
     */
    protected $error = '';

    /**
     * @var bool
     */
    protected $config_form = false;

    /**
     * Livechat constructor.
     */
    public function __construct()
    {
        $this->name = 'livechat';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Oleh Vasylyev';
        $this->need_instance = 0;
        $this->html = '';

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Live Chat');
        $this->description = $this->l('This module place Live Chat widget like LiveZilla, Zopim, Kayako, ClickDesk, etc. on your web-site');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall Live Chat module?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        $multistore = Shop::isFeatureActive();

        if ($multistore == true) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install() ||
            !$this->registerHook('displayFooter') ||
            !Configuration::updateValue('LIVECHAT_STATUS', false) ||
            !Configuration::updateValue('LIVECHAT_CODE', '')
        ) {
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        if (!parent::uninstall() ||
            !Configuration::deleteByName('LIVECHAT_STATUS') ||
            !Configuration::deleteByName('LIVECHAT_CODE')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitLivechatModule')) == true) {
            $this->postProcess();
            $this->html .= $this->confirm;
            $this->html .= $this->inform;
            $this->html .= $this->warn;
            $this->html .= $this->error;
        }

        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->smarty->assign('widget', $this->widget('ps_livechat_free'));

        $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        $this->html .= $this->renderForm();
        $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/widget.tpl');

        return $this->html;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitLivechatModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Status'),
                        'name' => 'LIVECHAT_STATUS',
                        'is_bool' => true,
                        'desc' => $this->l('Enable or disable module'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 6,
                        'rows' => 8,
                        'type' => 'textarea',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Please paste yours LiveChat code above and press Save button'),
                        'name' => 'LIVECHAT_CODE',
                        'label' => $this->l('Chat code'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'LIVECHAT_STATUS' => Configuration::get('LIVECHAT_STATUS', true),
            'LIVECHAT_CODE' => Configuration::get('LIVECHAT_CODE', true),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        if (Tools::isSubmit('submitLivechatModule')) {
            Configuration::updateValue('LIVECHAT_STATUS', Tools::getValue('LIVECHAT_STATUS'), false);
            Configuration::updateValue('LIVECHAT_CODE', Tools::getValue('LIVECHAT_CODE'), true);

            return $this->confirm = $this->displayConfirmation($this->l('The settings have been updated.'));
        }

        return false;
    }

    /**
     * @return string
     */
    public function hookDisplayFooter()
    {
        if (Configuration::get('LIVECHAT_STATUS', true) == true) {
            $this->smarty->assign(
                array(
                    'livechat_status' => Configuration::get('LIVECHAT_STATUS'),
                    'livechat_code' => Configuration::get('LIVECHAT_CODE')
                )
            );
        }
        return $this->display(__FILE__, 'livechat.tpl');
    }

    public function widget($param){
        $send['widget'] = $param;
        $send['http_host'] = $_SERVER['HTTP_HOST'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://tobiksoft.com/market/widget/api.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $send);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 5);
        $output = curl_exec ($ch);
        curl_close ($ch);


        return $output;
    }

}
