<?php
/*
* 2007-2015 PrestaShop
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
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

// https://github.com/PrestaShop/paymentexample

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class bitcanna extends PaymentModule
{
    protected $_html = '';
    protected $_postErrors = array();

    public $details;
    public $owner;
    public $address;
    public $extra_mail_vars;

    public function __construct()
    {
        $this->name = 'bitcanna';
        $this->tab = 'payments_gateways';
        $this->version = '0.1.1';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->author = 'Bitcanna';
        $this->controllers = array('validation');
        $this->is_eu_compatible = 1;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Bitcanna Payment');
        $this->description = $this->l('Allows bitcanna payments from Prestashop');

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('paymentOptions') || !$this->registerHook('paymentReturn')) {
            return false;
        }
        return true;
    }
    public function processConfiguration()
    {
      if (Tools::isSubmit('mymod_pc_form')) {
        
        $moduleTitle = Tools::getValue('moduleTitle');
        $bcnaAddress = Tools::getValue('bcnaAddress');
        $lcdUrl = Tools::getValue('lcdUrl');
        Configuration::updateValue('BITCANNA_TITLE', $moduleTitle);
        Configuration::updateValue('BITCANNA_ADDRESS', $bcnaAddress);
        Configuration::updateValue('BITCANNA_LCD', $lcdUrl);
        
        $this->context->smarty->assign('confirmation', 'ok');
      }		
    }
    public function assignConfiguration()
    {
      $moduleTitle = Configuration::get('BITCANNA_TITLE');
      $bcnaAddress = Configuration::get('BITCANNA_ADDRESS');
      $lcdUrl = Configuration::get('BITCANNA_LCD');
      $this->context->smarty->assign('moduleTitle', $moduleTitle);
      $this->context->smarty->assign('bcnaAddress', $bcnaAddress);
      $this->context->smarty->assign('lcdUrl', $lcdUrl);
    }
    public function getContent()
    {
      $this->processConfiguration();
      $this->assignConfiguration();
      //var_dump(Configuration::get('MYMOD_GRADES'));
      return $this->display(__FILE__, 'getContent.tpl');
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $payment_options = [
            $this->getOfflinePaymentOption(),
            //$this->getExternalPaymentOption(),
            //$this->getEmbeddedPaymentOption(),
            //$this->getIframePaymentOption(),
        ];
		
        return $payment_options;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getOfflinePaymentOption()
    {
        $offlineOption = new PaymentOption();
        $offlineOption->setCallToActionText($this->l('Pay by Bitcanna'))
                      ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))                  
                      ->setAdditionalInformation($this->context->smarty->fetch('module:bitcanna/views/templates/front/payment_infos.tpl'))
                      ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/logo.png'));

        return $offlineOption;
    }

    public function getExternalPaymentOption()
    {
        $externalOption = new PaymentOption();
        $externalOption->setCallToActionText($this->l('Pay external'))
                       ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
                       ->setInputs([
                            'token' => [
                                'name' =>'token',
                                'type' =>'hidden',
                                'value' =>'12345689',
                            ],
                        ])
                       ->setAdditionalInformation($this->context->smarty->fetch('module:bitcanna/views/templates/front/payment_infos.tpl'))
                       ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/payment.jpg'));

        return $externalOption;
    }

    public function getEmbeddedPaymentOption()
    {
        $embeddedOption = new PaymentOption();
        $embeddedOption->setCallToActionText($this->l('Pay by Bitcanna'))
                       ->setForm($this->generateForm())
                       ->setAdditionalInformation($this->context->smarty->fetch('module:bitcanna/views/templates/front/payment_infos.tpl'))
                       ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/logo.png'));

        return $embeddedOption;
    }

    public function getIframePaymentOption()
    {
        $iframeOption = new PaymentOption();
        $iframeOption->setCallToActionText($this->l('Pay iframe'))
                     ->setAction($this->context->link->getModuleLink($this->name, 'iframe', array(), true))
                     ->setAdditionalInformation($this->context->smarty->fetch('module:bitcanna/views/templates/front/payment_infos.tpl'))
                     ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/payment.jpg'));

        return $iframeOption;
    }

    protected function generateForm()
    {
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = sprintf("%02d", $i);
        }

        $years = [];
        for ($i = 0; $i <= 10; $i++) {
            $years[] = date('Y', strtotime('+'.$i.' years'));
        }

        $this->context->smarty->assign([
            'action' => $this->context->link->getModuleLink($this->name, 'validation', array(), true),
            'months' => $months,
            'years' => $years,
        ]);

        return $this->context->smarty->fetch('module:bitcanna/views/templates/front/payment_form.tpl');
    }
}
