<div align="center">
  <img src="https://i.imgur.com/fponyFg.png">
</div>


BitCanna Pay - Prestashop
===


## Table of Contents

*   [Requirements](#requirements "Requirements")
*   [Installation](#installation "Installation")
*   [Configuration](#configuration "Configuration")
*   [Use of the module](#use-of-the-module "Use of the module")
*   [Backend](#backend "Backend")

## Requirements

* Last version of prestashop
* Your Bitcanna Wallet 

## Installation

Download our script and install it.

1. In admin panel, in the left menu, click on Module Manager
From the Module Manager, click on the "upload module" button and add the zip file of our prestashop module.
The installation will be done automatically.

![](https://i.imgur.com/eX3YPun.png)


2. Once the installation is complete, you must configure the plugin
![](https://i.imgur.com/9Guc9XJ.png)



## Configuration

In this part, we will configure the module to be connected to our blockchain, the configuration is very simple!
In the setting part of the payment module, here are the different points to edit:

![](https://i.imgur.com/qPe5xZ5.png)


1. Title of the payment module that appears in the list of payment choices
2. The payment receipt address, make sure you are the owner of this address
3. The lcd url for the verification of your payment. Leave it as it is, if one day the url must be changed, you will be warned


> Read more about Bitcanna here: https://www.bitcanna.io/payments/

## Use of the module
Once the configuration is done, you can start using your payment module!
To do your test, add a product for a few cents and go through the nominal process to buy the product.
When selecting payment, you will see the bitcanna option, like this:

![](https://i.imgur.com/vAitmuS.png)


Here the verification of the payment is made.
For this, the customer must use keplr or directly from our webwallet to make the payment and validate the order.

![](https://i.imgur.com/mrpUWhr.png)

Once the payment has been sent, a few seconds later, our system will detect the payment using the memo.
3 checks are made:

1. Verification of the memo
2. Verification of the receiving address
3. Verification of the amount.

as below

![](https://i.imgur.com/7YuzbLx.png)

Once the payment has been verified and validated, a confirmation message with the link to the transaction will appear!

![](https://i.imgur.com/JV9c5tW.png)

## Backend

The order will be validated in the backend, you can now check

![](https://i.imgur.com/Rbsyj5V.png)

If you wish to modify the configuration of the payment module, in the left tab, click on 
**Payment -> Preferences**

![](https://i.imgur.com/XBUqsih.png)


 
