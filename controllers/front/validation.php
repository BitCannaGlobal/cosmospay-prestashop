<?php

class cosmosSql extends ModuleFrontController {

  public function addTransaction($data) {    
    $result = Db::getInstance()->insert('cosmos_transaction', [
        'id_order' => (int) $data["cartId"],
        'memo' => pSQL($data["memo"]),
        'status' => pSQL("new"),
        'coin_amount' => pSQL($data["coinTotal"]),        
        'fiat_amount' => pSQL($data["realValue"]),
        'date_add' => date('Y-m-d H:i:s'),
    ]);   
    return $result;
  }
  public function getTransaction($id) {
    $getTrans = Db::getInstance()->ExecuteS(
      'SELECT * FROM `'._DB_PREFIX_.'cosmos_transaction` WHERE `id_order` = ' . pSQL($id)
    );    
    return $getTrans;
  }
  public function updateTransaction($data) {
    $result = Db::getInstance()->update('cosmos_transaction', array(
        'chain_pay' => pSQL($data['current_chain']),
        'lcd_pay' => pSQL($data['lcd_pay']),
        'coin_amount' => pSQL($data['coinTotal']),  
        'target_address' => pSQL($data['target_address']),   
        'viewDenom' => pSQL($data['viewDenom']),
    ), 'id_order = ' . pSQL($data['cartId']), 1, true);
    return $result; 
  } 
  public function updateMethod($data) {
    $result = Db::getInstance()->update('cosmos_transaction', array(
        'method' => pSQL($data['switchMethod'])    
    ), 'id_order = ' . pSQL($data['cartId']), 1, true);
    return $result; 
  }
  public function updateStatus($data) {
    $result = Db::getInstance()->update('cosmos_transaction', array(
        'status' => pSQL($data['to'])    
    ), 'id_order = ' . pSQL($data['cartId']), 1, true);
    return $result; 
  }  
  public function validateOrder($data) {
    $result = Db::getInstance()->update('cosmos_transaction', array(
        'status' => pSQL($data['to']),
        'tx_hash' => pSQL($data['txHash'])
    ), 'id_order = ' . pSQL($data['cartId']), 1, true);
    return $result; 
  }   
}

class cosmospayvalidationModuleFrontController extends ModuleFrontController
{
    function url_exist($url) {
            $urlheaders = get_headers($url);
            //print_r($urlheaders);
            $urlmatches  = preg_grep('/200 ok/i', $urlheaders);
            if(!empty($urlmatches)){
              return true;
            }else{
              return false;
            }
    }
    public function valid_donnees($donnees){
        $donnees = trim($donnees);
        $donnees = stripslashes($donnees);
        $donnees = htmlspecialchars($donnees);
        return $donnees;
    }
    
    public function setMedia()
    {
        parent::setMedia();

            $this->registerStylesheet(
                'module-cosmospay-style',
                'modules/'.$this->module->name.'/css/cosmos.css',
                [
                  'media' => 'all',
                  'priority' => 200,
                ]
            );

            $this->registerJavascript(
                'module-cosmospay-bundle',
                'modules/'.$this->module->name.'/js/bundle.js',
                [
                  'priority' => 200,
                  'attribute' => 'async',
                ]
            );
            
            $this->registerJavascript(
                'module-cosmospay-mainscript',
                'modules/'.$this->module->name.'/js/mainscript.js',
                [
                  'priority' => 200,
                  'attribute' => 'async',
                ]
            ); 
    }   
     public function checkByTxHash() {
     
        header('Content-Type: application/json');
        $cart = $this->context->cart;
        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $returnTrans = cosmosSql::getTransaction($cart->id);
        
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer))
            Tools::redirect('index.php?controller=order&step=1');        
        
        

        if (isset($_GET['tx_hash'])) {     
            $getTxVar = $this->valid_donnees($_GET['tx_hash']);             
            $dbMemo = $returnTrans[0]['memo'];
            $dbPrice = $returnTrans[0]['coin_amount'];
            $dbAdresse = $returnTrans[0]['target_address'];
            $lcdUrl = $returnTrans[0]['lcd_pay'];
            
            
            $getTx = file_get_contents($lcdUrl . '/cosmos/tx/v1beta1/txs/' . $getTxVar);
            $getTx = json_decode($getTx);
            $finalAmount = $dbPrice * 1000000;

            if ($dbMemo === $getTx->tx->body->memo) {
                if ($finalAmount === (float) $getTx->tx->body->messages[0]->amount[0]->amount) {
                    if ($dbAdresse === $getTx->tx->body->messages[0]->to_address) {
                        // Validate order!
                        $this->module->validateOrder($cart->id, '2', $total, $this->module->displayName, NULL, NULL, (int)$currency->id, false, $customer->secure_key);
                        
                        // Update cosmos_transaction table
                        $updateMethodData['cartId'] = $cart->id;
                        $updateMethodData['to'] = 'complete';
                        $updateMethodData['txHash'] = $getTxVar;
                          
                        cosmosSql::validateOrder($updateMethodData);

                        $message = 'Memo: ' . $dbMemo . '<br> Tx order: ' . $getTxVar;
                        $msg = new Message();
                        $message = strip_tags($message, '<br>');
                        if (Validate::isCleanHtml($message)) {
 
                            $msg->message = $message;
                            $msg->id_cart = (int) ($cart->id);
                            $msg->id_customer = (int) $this->context->cart->id_customer;
                            $msg->id_order = (int) $this->module->currentOrder;
                            $msg->private = 0;
                            $msg->add();                       
                        }
                        // Specify order id for message
                        $old_message = Message::getMessageByCartId((int) $this->context->cart->id);
                        if ($old_message && !$old_message['private']) {
                            $update_message = new Message((int) $old_message['id_message']);
                            $update_message->id_order = (int) $this->module->currentOrder;
                            $update_message->update();

                            // Add this message in the customer thread
                            $customer_thread = new CustomerThread();
                            $customer_thread->id_contact = 0;
                            $customer_thread->id_customer = (int) $this->context->cart->id_customer;
                            $customer_thread->id_shop = (int) $this->context->shop->id;
                            $customer_thread->id_order = (int) $this->module->currentOrder;
                            $customer_thread->id_lang = (int) $this->context->language->id;
                            $customer_thread->email = $this->context->customer->email;
                            $customer_thread->status = 'open';
                            $customer_thread->token = Tools::passwdGen(12);
                            $customer_thread->add();

                            $customer_message = new CustomerMessage();
                            $customer_message->id_customer_thread = $customer_thread->id;
                            $customer_message->id_employee = 0;
                            $customer_message->message = $update_message->message;
                            $customer_message->private = 0;

                            if (!$customer_message->add()) {
                                $this->errors[] = $this->trans('An error occurred while saving message', [], 'Admin.Payment.Notification');
                            }
                        }                      
                          echo '{"error":false, "message":"Payement accepted!"}';
                    } else
                        echo '{"error":true, "message":"Bad adresse"}';
                } else
                    echo '{"error":true, "message":"Bad amount"}';
            } else
                echo '{"error":true, "message":"Bad memo"}';
            
        }
    }
    public function checkByMemo() {
      header('Content-Type: application/json');
      $cart = $this->context->cart;
      $returnTrans = cosmosSql::getTransaction($cart->id);
        
      $json = file_get_contents($returnTrans[0]['lcd_pay'].'/cosmos/tx/v1beta1/txs?events=message.action=%27/cosmos.bank.v1beta1.MsgSend%27&order_by=ORDER_BY_DESC&pagination.limit=10');
      $obj = json_decode($json);
        
      echo $json;     
    }
    public function postProcess()
    {
    if (isset($_GET['check'])) {
        
      if ($_GET['check'] !== "manual")
        $this->checkByTxHash();
      else
        $this->checkByMemo();
        
      exit(0);  
      
    } elseif (isset($_POST['switch']) OR isset($_GET['switch'])) { 
      header('Content-Type: application/json');
      $switchId = $_POST['switch'];
      $cart = $this->context->cart;
      
      // Call configuration file from our server
      $string = file_get_contents( "https://store-api.bitcanna.io" );
      if ( $string === false ) {
        wp_die( 'Unable to call configuration', 'Error' );
      }
      // Decode configuration file from our server
      $json_a = json_decode( $string, true );
      if ($json_a === null) {
        wp_die('Error in json configuration', 'Error');
      }
      // echo $json_a;
      // Foreach configuration file and compare selected chain
      // And select it for get good price!! 
      foreach ($json_a as $chains_data => $chain) {
        if ($chain['name'] === $switchId) {        
          $dataChain = $chain;
        }        
      }     
      
      // Check the currency from prestashop and convert it to price chain
      $currencyList = "usd,aed,ars,aud,bdt,bhd,bmd,brl,cad,chf,clp,cny,czk,dkk,eur,gbp,hkd,huf,idr,ils,inr,jpy,krw,kwd,lkr,mmk,mxn,myr,ngn,nok,nzd,php,pkr,pln,rub,sar,sek,sgd,thb,try,twd,uah,vef,vnd,zar,xdr";
      $dataValueCoin = file_get_contents('https://api.coingecko.com/api/v3/simple/price?ids='.$dataChain['coingeckoId'].'&vs_currencies='.$currencyList);
      $decodedData = json_decode($dataValueCoin);
      $currencyNow = strtolower($this->context->currency->iso_code);  
      // And display good price!
      // datachain->coingeckoId->currency
      $coinPriceGeeko = $decodedData->{$dataChain['coingeckoId']}->$currencyNow;
      
      $finalCoinValue = $cart->getOrderTotal() / $coinPriceGeeko;
      $finalCoinValueTronc = number_format($finalCoinValue, 3, '.', '');   
      
      // Get address to pay
      $selectChain = Configuration::get('CONF_COSMOS_CHAINS_ADDR');
      $unserializeChains = unserialize($selectChain);
      //var_dump($unserializeChains[$dataChain['name']]);

      // Update transaction db
      $updateData['cartId'] = $cart->id;
      $updateData['current_chain'] = $dataChain['name'];
      $updateData['viewDenom'] = $dataChain['coinLookup']['viewDenom'];
      $updateData['coinTotal'] = $finalCoinValueTronc;
      $updateData['realValue'] = $coinPriceGeeko;
      $updateData['lcd_pay'] = $dataChain['apiURL'];
      $updateData['target_address'] = $unserializeChains[$dataChain['name']];
        
      cosmosSql::updateTransaction($updateData);
      $returnTrans = cosmosSql::getTransaction($cart->id);
      
      echo '{ 
        "current_chain": "'.$dataChain['name'].'", 
        "lcd": "'.$dataChain['apiURL'].'",
        "CosmosPrice": "'. $finalCoinValueTronc .'",
        "OrderPrice": "'. $cart->getOrderTotal() .'",
        "chainDenom": "'. $dataChain['coinLookup']['viewDenom'] .'",
        "startTime": "'.$returnTrans[0]['date_add'].'",
        "status": "'.$returnTrans[0]['status'].'" 
      }'; 
      // TODO add this variables
      /* 
        "adressToPay": "'.esc_attr( $wc_cosmos_options[$getChainPay] ).'",
        "fee": "'.esc_attr( $dataChain['fee']['amount'] ).'",
        "gas": "'.esc_attr( $dataChain['fee']['gas'] ).'",
        "startTime": "'.esc_attr( $getstartTime ).'"  */
        
        
      // var_dump($dataChain);
      exit(0); 
    } elseif (isset($_POST['switchMethod']) ) {
        $cart = $this->context->cart;
        $updateMethodData['cartId'] = $cart->id;
        $updateMethodData['switchMethod'] = $_POST['switchMethod'];
        
        cosmosSql::updateMethod($updateMethodData);
      echo '{ 
        "error": "false" 
      }'; 
      die();
    } elseif (isset($_GET['finalData']) ) {   
      header('Content-Type: application/json');
      $cart = $this->context->cart;
      $returnTrans = cosmosSql::getTransaction($cart->id);
 
      echo '{ 
        "current_chain": "'.$returnTrans[0]['chain_pay'].'", 
        "lcd": "'.$returnTrans[0]['lcd_pay'].'", 
        "CosmosPrice": "'.$returnTrans[0]['coin_amount'].'", 
        "OrderPrice": "'.$returnTrans[0]['fiat_amount'].'",
        "adressToPay": "'.$returnTrans[0]['target_address'].'",
        "memo": "'.$returnTrans[0]['memo'].'",
        "method": "'.$returnTrans[0]['method'].'",
        "startTime": "'.$returnTrans[0]['date_add'].'" 
      }';  
      die();
    } elseif (isset($_POST['cancel']) ) {
      $this->context->cart->delete();
      $this->context->cookie->id_cart = 0;
      $cart = $this->context->cart;
      $updateMethodData['cartId'] = $cart->id;
      $updateMethodData['to'] = 'canceled';
        
      cosmosSql::updateStatus($updateMethodData);      
      die(1);
    } elseif (isset($_GET['cancel']) ) { 
    
      $this->context->smarty->assign('mainDomain', Context::getContext()->shop->getBaseURL(true));
      $this->setTemplate('module:cosmospay/views/templates/front/payment_cancel.tpl');
      
    } else {
    
      $this->setMedia();
      $cart = $this->context->cart;
      if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
        Tools::redirect('index.php?controller=order&step=1');
      }
      $randomletter = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, 10);
      
      
      $getBcnaReal = file_get_contents('https://api.coingecko.com/api/v3/simple/price?ids=bitcanna&vs_currencies=usd,gbp,eur');
      $getBcnaReal = json_decode($getBcnaReal); 

      switch($this->context->currency->iso_code) {
        case 'EUR':
          $finalRealValue = $getBcnaReal->bitcanna->eur;
          break;
        case 'USD':
          $finalRealValue = $getBcnaReal->bitcanna->usd;
          break;
        case 'GBP':
          $finalRealValue = $getBcnaReal->bitcanna->gbp;
          break;
      }      
      
      $bitcannaTotal = ($cart->getOrderTotal()/$finalRealValue);    

      
      if($cart->id) {

        $returnTrans = cosmosSql::getTransaction($cart->id);      
        if (empty($returnTrans)) {
          $insertData['cartId'] = $cart->id;
          $insertData['memo'] = $randomletter;
          $insertData['coinTotal'] = $bitcannaTotal;
          $insertData['realValue'] = $finalRealValue;
          
          cosmosSql::addTransaction($insertData);
          $returnTrans = cosmosSql::getTransaction($cart->id);
        } 
      }      
      
      
      /*$authorized = false;
      foreach (Module::getPaymentModules() as $module) {
        if ($module['name'] == 'bitcanna') {
          $authorized = true;
          break;
        }
      }

      if (!$authorized) {
        die($this->module->l('This payment method is not available.', 'validation'));
      } */

      $this->context->smarty->assign([
        'params' => $_REQUEST,
      ]);
      
      $selectChain = Configuration::get('CONF_COSMOS_CHAINS');
      $unserializeChains = unserialize($selectChain);
      
      if($cart->id) {
        $this->context->smarty->assign('totalAmount', round(Configuration::get('BITCANNA_TOTAL_'.$cart->id), 4));
        $this->context->smarty->assign('memo', $returnTrans[0]["memo"]);
        $this->context->smarty->assign('viewDenom', $returnTrans[0]["viewDenom"]);
        $this->context->smarty->assign('orderId', $cart->id);
        $this->context->smarty->assign('orderValue', $cart->getOrderTotal());
        $this->context->smarty->assign('orderSymbol', $this->context->currency->symbol);
        $this->context->smarty->assign('unserializeChains', $unserializeChains);
        $this->context->smarty->assign('mainDomain', Context::getContext()->shop->getBaseURL(true));
        $this->setTemplate('module:cosmospay/views/templates/front/payment_return.tpl');        
      } else {
        $this->context->smarty->assign('mainDomain', Context::getContext()->shop->getBaseURL(true));
        $this->setTemplate('module:cosmospay/views/templates/front/payment_cancel.tpl');  
      }

      $customer = new Customer($cart->id_customer);
      if (!Validate::isLoadedObject($customer))
        Tools::redirect('index.php?controller=order&step=1');
    }            
  }
}
