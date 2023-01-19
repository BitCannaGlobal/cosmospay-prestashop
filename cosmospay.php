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

class cosmospay extends PaymentModule
{
    protected $_html = '';
    protected $_postErrors = array();

    public $details;
    public $owner;
    public $address;
    public $extra_mail_vars;

    public function __construct()
    {
        $this->name = 'cosmospay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.15';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->author = 'Bitcanna';
        $this->controllers = array('validation');
        $this->is_eu_compatible = 1;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Cosmos Pay');
        $this->description = $this->l('The Cosmos Pay Module allows you to accept cryptocurrency payments on your Prestashop site.');

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }
      
    }

    public function install()
    {

        $sqlCreate = "CREATE TABLE `" . _DB_PREFIX_ . "cosmos_transaction` (
          `id_order` int UNSIGNED NOT NULL,
          `chain_pay` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
          `lcd_pay` varchar(200) DEFAULT NULL,
          `viewDenom` varchar(10) NOT NULL DEFAULT 'BCNA',
          `target_address` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
          `memo` varchar(20) DEFAULT NULL,
          `status` enum('new','unconfirmed','complete','canceled','timeout') DEFAULT NULL,
          `tx_hash` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
          `coin_amount` decimal(16,3) DEFAULT NULL,
          `fiat_amount` decimal(16,2) DEFAULT NULL,
          `date_add` datetime NOT NULL,
          `method` enum('keplr','another') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'keplr'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci; ";               
                
                
                
        Db::getInstance()->execute($sqlCreate);  
        
        if (!parent::install() || !$this->registerHook('paymentOptions') || !$this->registerHook('paymentReturn')) {
            return false;
        }
        return true;
    }

    public function uninstall(){
        $sql = "DROP TABLE `" . _DB_PREFIX_ . "cosmos_transaction`";
        Db::getInstance()->execute($sql); 
        // TODO delete config
        // $sqlConfig1 = "DELETE FROM `" . _DB_PREFIX_ . "configuration` WHERE `name` = 'COSMOS_PAY_DISCLAIMER'";    
        return parent::uninstall();
    }    
    
    public function processConfiguration()
    {
      if (Tools::isSubmit('accept_disclaimer')) {   
      
        $disclamer1 = Tools::getValue('disclamer1'); 
        var_dump($disclamer1);
        $this->context->smarty->assign('disclaimer_error', false);
        if($disclamer1 === 'on') {
          Configuration::updateValue('COSMOS_PAY_DISCLAIMER', true);      
        } else 
          $this->context->smarty->assign('disclaimer_error', true);
      }    
      if (Tools::isSubmit('mymod_pc_form')) {   
        
        $moduleTitle = Tools::getValue('moduleTitle'); 
        $moduleDesc = Tools::getValue('moduleDesc'); 
        $selectChain = Tools::getValue('selectChain');
        $chainsAddr = Tools::getValue('selectChainAddr');
        
        $checkChains = Tools::getValue('checkChains');
        $input = Tools::getValue('input');
        
        //var_dump($input);
 
        Configuration::updateValue('CONF_COSMOS_TITLE', $moduleTitle); 
        Configuration::updateValue('CONF_COSMOS_DESC', $moduleDesc);
        Configuration::updateValue('CONF_COSMOS_CHAINS', serialize($checkChains));
        Configuration::updateValue('CONF_COSMOS_CHAINS_ADDR', serialize($input));  
          
        /*if (!$checkChains) {
          Configuration::updateValue('CONF_COSMOS_CHAINS', '');
          Configuration::updateValue('CONF_COSMOS_CHAINS_ADDR', '');        
        } else {
          Configuration::updateValue('CONF_COSMOS_CHAINS', serialize($checkChains));
          Configuration::updateValue('CONF_COSMOS_CHAINS_ADDR', serialize($input));        
        }*/

          
        $this->context->smarty->assign('confirmation', 'ok');
      }     
    }
    public function assignConfiguration()
    {
    
      $CosmosPayDisclaimer = Configuration::get('COSMOS_PAY_DISCLAIMER');
      $this->context->smarty->assign('disclaimer_check', $CosmosPayDisclaimer);
      if ($CosmosPayDisclaimer === false) {
        $getDisclamer = file_get_contents('https://store-api.bitcanna.io/disclaimer');
        $this->context->smarty->assign('disclaimer_data', $getDisclamer);
      }
      

      $json = file_get_contents('https://store-api.bitcanna.io');
      //$json = file_get_contents('https://raw.githubusercontent.com/BitCannaGlobal/cosmospay-api/main/cosmos.config.test.json');
      //$array = (array)json_decode(json_encode($json));
      
      
      $obj = json_decode($json);
      
      $this->context->smarty->assign('configCosmos', $obj);
      
      
      $moduleTitle = Configuration::get('CONF_COSMOS_TITLE');
      $moduleDesc = Configuration::get('CONF_COSMOS_DESC');
      $selectChain = Configuration::get('CONF_COSMOS_CHAINS');
      $selectChainAddr = Configuration::get('CONF_COSMOS_CHAINS_ADDR');
      
      $unserializeChains = unserialize($selectChain);
      $unserializeChainsAddr = unserialize($selectChainAddr);
      
      
      $this->context->smarty->assign('moduleTitle', $moduleTitle);
      $this->context->smarty->assign('moduleDesc', $moduleDesc);
      
      if (!$unserializeChains) {
        $this->context->smarty->assign('selectChain', []); 
      } else
        $this->context->smarty->assign('selectChain', $unserializeChains);
        
      if (!$unserializeChainsAddr) {
        $this->context->smarty->assign('inputActive', []); 
      } else
        $this->context->smarty->assign('inputActive', $unserializeChainsAddr);   
        
        
      $this->context->smarty->assign('disclaimer_error', true);  
      
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
        $moduleDesc = Configuration::get('CONF_COSMOS_DESC');
        $this->context->smarty->assign('moduleDesc', $moduleDesc);  
        $offlineOption = new PaymentOption();
        $offlineOption->setCallToActionText($this->l('Pay with Cosmos Pay'))
                      ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))                  
                      ->setAdditionalInformation($this->context->smarty->fetch('module:cosmospay/views/templates/front/payment_infos.tpl'));
                      // ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/logo.png'));

        return $offlineOption;
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

        return $this->context->smarty->fetch('module:cosmospay/views/templates/front/payment_form.tpl');
    }
}
