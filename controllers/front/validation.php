<?php

class bitcannaValidationModuleFrontController extends ModuleFrontController
{

    public function valid_donnees($donnees){
        $donnees = trim($donnees);
        $donnees = stripslashes($donnees);
        $donnees = htmlspecialchars($donnees);
        return $donnees;
    }
     public function test()
    {
        header('Content-Type: application/json');
        $cart = $this->context->cart;
        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer))
            Tools::redirect('index.php?controller=order&step=1');        
        
        $lcdUrl = Configuration::get('BITCANNA_LCD');
        
        if (isset($_GET['tx']) && isset($_GET['order_id'])) {     
            $getTxVar = $this->valid_donnees($_GET['tx']);        
            $dbMemo = Configuration::get('BITCANNA_MEMO_'.$cart->id);
            $dbPrice = round(Configuration::get('BITCANNA_TOTAL_'.$cart->id), 4);
            $dbAdresse = Configuration::get('BITCANNA_ADDRESS');

            $getTx = file_get_contents($lcdUrl . '/txs/' . $getTxVar);
            $getTx = json_decode($getTx);
            
            $ubcnaAmount = $dbPrice * 1000000;

            if ($dbMemo === $getTx->tx->value->memo) {
                if ($ubcnaAmount === (float) $getTx->tx->value->msg[0]->value->amount[0]->amount) {
                    if ($dbAdresse === $getTx->tx->value->msg[0]->value->to_address) {
                        // Validate order!
                        $this->module->validateOrder($cart->id, '2', $total, $this->module->displayName, NULL, NULL, (int)$currency->id, false, $customer->secure_key);

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
            
        } else {
            $json = file_get_contents($lcdUrl.'/cosmos/tx/v1beta1/txs?events=message.action=%27/cosmos.bank.v1beta1.MsgSend%27&order_by=ORDER_BY_DESC');
            $obj = json_decode($json);

            echo $json;
        }
    }
    public function postProcess()
    {
    if (isset($_GET['check'])) {
    
      $this->test();
      exit(0);  
      
    } else {
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

      if (!Configuration::get('BITCANNA_MEMO_'.$cart->id)) {
        Configuration::updateValue('BITCANNA_MEMO_'.$cart->id, $randomletter);
      }
      if (!Configuration::get('BITCANNA_TOTAL_'.$cart->id) || Configuration::get('BITCANNA_TOTAL_'.$cart->id) !== $bitcannaTotal) {
        Configuration::updateValue('BITCANNA_TOTAL_'.$cart->id, $bitcannaTotal);
      }       
      if (!Configuration::get('BITCANNA_PRICE_'.$cart->id)) {
        Configuration::updateValue('BITCANNA_PRICE_'.$cart->id, $finalRealValue);
      }       
      
      $authorized = false;
      foreach (Module::getPaymentModules() as $module) {
        if ($module['name'] == 'bitcanna') {
          $authorized = true;
          break;
        }
      }

      if (!$authorized) {
        die($this->module->l('This payment method is not available.', 'validation'));
      }

      $this->context->smarty->assign([
        'params' => $_REQUEST,
      ]);
  
      $this->context->smarty->assign('totalAmount', round(Configuration::get('BITCANNA_TOTAL_'.$cart->id), 4));
      $this->context->smarty->assign('memo', Configuration::get('BITCANNA_MEMO_'.$cart->id));
      $this->context->smarty->assign('bcnaAddress', Configuration::get('BITCANNA_ADDRESS'));
      $this->context->smarty->assign('orderId', $cart->id);
      $this->context->smarty->assign('orderValue', $cart->getOrderTotal());
      $this->context->smarty->assign('orderSymbol', $this->context->currency->symbol);
      
      $this->setTemplate('module:bitcanna/views/templates/front/payment_return.tpl');


      $customer = new Customer($cart->id_customer);
      if (!Validate::isLoadedObject($customer))
        Tools::redirect('index.php?controller=order&step=1');
    }            
  }
}
