{extends "$layout"}


{block name="content"}
 
<link rel="stylesheet" 
          href=
"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" 
          href=
"{$urls.base_url}modules/bitcanna/css/bitcanna.css">
<form name="sendForm"> 
<div class="bcna-card" id="mainTransaction">
<br />
   <div class="bcna-payinfo">
      <div class="bcna-card-title">
         <img src="{$urls.base_url}modules/bitcanna/img/BCNA-icon.svg" width="25" height="25"> 
         <h3>{$totalAmount} bcna</h3>
      </div>
      <div class="bcna-card-amount">
         {$orderSymbol}{$orderValue}
      </div>
   </div>
   <div class="bcna-content">
      <p>Please send the <b>exact</b> same amount of coins to the following address 
        <div class="input-wrapper" id="copyRecep">
         <input type="text" id="recipient" name="recipient" value="{$bcnaAddress}"  aria-label="readonly input example" readonly>
         </div>
         <span style="display: none; color: green;" style="copyAddress" id="copyAddress">Address copied</span>
      </p>
       Add the following ID to the <b>MEMO</b> field in your transaction
         <div class="input-wrapper" id="copyMemo">
         <input value="{$memo}" type="text" id="memo" name="memo" aria-label="readonly input example" readonly> 
         </div>
         <span style="display: none; color: green;" id="copyMemoMessage">Memo copied</span>
       
      <p>Make sure to send your transaction to the <b>correct address</b> with the <b>precise amount</b> and the <b>correct MEMO</b>. If you need help, contact customer support.</p>
      <div class="bcna-separator"><span>OR</span></div>
 
      <br />
      <input id="prodId" name="amount" type="hidden" value="{$totalAmount}">
      <button class="bcna-butkeplr" type="submit" id="submit">
      <img src="{$urls.base_url}modules/bitcanna/img/keplr.jpeg" width="20" height="20"> 
      Pay with keplr
      </button>
      <button class="bcna-butcancel" type="submit" id="submit" onclick="window.location.href='{$urls.base_url}';">Cancel payment</button>
      <br />
      <script src="{$urls.base_url}/modules/bitcanna/js/bundle.js"></script>
 
   </div>
</div>
</form> 


<br /> 

<div style="display: none;" id="acceptedPayement" class="bcna-step">   
  <div>
    <div class="bcna-card-title">
      <img src="{$urls.base_url}modules/bitcanna/img/BCNA-icon.svg" width="60" height="60"> 
    </div>
    <br />
    <div class="bcna-card-amount" style="color:green;">
     <h3>Payment accepted!</h3>
    </div>
  </div> 
</div> 
<br /> 
<div style="display: none;" id="validateTxAmount" class="bcna-step">   
  <div>
    <div class="bcna-card-title">
      <img src="{$urls.base_url}modules/bitcanna/img/BCNA-icon.svg" width="25" height="25"> 
      <h3>{$totalAmount} bcna</h3>
    </div>
    <div class="bcna-card-amount">
      €{$orderValue}
    </div>
  </div> 
</div> 
 
<br />
<!--style="display: none;"-->
<div style="display: none;" id="validateTx" class="bcna-step"> 

  <h4 id="stage1">Payment detected</h4>
  <h4 id="stage2" style="display: none;"><p class="text-success"> <i class="bi bi-check-circle text-success"></i> <font color="green">Accepted payment</font></p>  </h4>
  <h4 id="stage3" style="display: none;"><p class="text-danger"> <i class="bi bi-x-circle"></i> <font color="red">Refused payment</font></p>  </h4>
<ul style="list-style-type:none;">
  <li >
    <i class="fa fa-check" style="color:#31BF91" aria-hidden="true"></i>
    Checking memo
  </li>
  <li>
 
    <i class="bi bi-x-circle text-danger" id="badCheckAdresse" style="display: none;"></i>
    <i class="fa fa-check" style="color:black" id="checkAdresse" aria-hidden="true"></i>
    Checking receiving address
  </li>
  <li>    
    <div class="spinner-border spinner-border-sm" id="waitingcheckAmount"></div>
    <i class="bi bi-x-circle text-danger" id="badCheckAmount" style="display: none;"></i>
    <i class="fa fa-check" id="checkAmount" aria-hidden="true"></i>
    Checking amount
  </li>
</ul>
 
</div>
<!-- style="display: none;"-->
<div style="display: none;" id="viewFinalTx" class="bcna-step">  
    <span style="font-size: 1.3em; color: green;">
    <i class="fa fa-eye" aria-hidden="true"></i>
    <a id="finalUrlTx" href="https://cosmos-explorer.bitcanna.io/" target="_blank" style="color:green;">
        &nbsp;&nbsp;View transaction
    <i class="fa fa-arrow-right" style="color:green; float:right;" aria-hidden="true"></i>
    </a>  
    </span> 
</div> 


<br /><br />
<script>

function bitcannaClick() { 
window.onload = function() {
  //YOUR JQUERY CODE

  $(document).on('click', '#copyRecep', function () {
    console.log('Click')
    $("#copyAddress").show();  
    /*setTimeout(function(){
      $("#copyAddress").fadeOut(1500);
    }, 1500);*/


  });
  $(document).on('click', '#copyMemo', function () {
    console.log('Click')
    $("#copyMemoMessage").show(); 
    /*setTimeout(function(){
      $("#copyMemoMessage").fadeOut(1500);
    }, 1500);    */
  });
  }
} 

function myTimer() {
 
  var mainDomain = "{$urls.base_url}";
  $.get(mainDomain+"index.php?fc=module&module=bitcanna&controller=validation&check", function(data, status) { 
    
        var randomMemo = "{$memo}";
        var sendTo = "{$bcnaAddress}";
        var finalAmount = "{$totalAmount}";
    $.each(data.tx_responses, function(i, item) {
      if(item.tx.body.memo === randomMemo) {
      
        $("#mainTransaction").hide();
        //console.log('Transaction found!\n Memo: ' +item.tx.body.memo+ '\n Txhash: '+item.txhash)
        clearInterval(myVar);
        $("#validateTxAmount").show(1000);
        $("#validateTx").show(1000);
        $("#verificationList").show(1000);
        $("#waitingSpinner").hide(1000);
        $("#waiting").hide(1000);

        setTimeout(
          function() 
          {
            console.log(item.tx.body.messages[0].to_address);
            if (item.tx.body.messages[0].to_address === sendTo) {
              $("#checkAdresse").show();
              $("#waitingcheckAdresse").hide();
              $("#checkAdresse").css("color", "#31BF91");
            } else {
              $("#badCheckAdresse").show();
              $("#waitingcheckAdresse").hide();
            }
          }, 4000);
        setTimeout(
          function() 
          {
            console.log(Number(item.tx.body.messages[0].amount[0].amount), Number(finalAmount * 1000000));
            if (Number(item.tx.body.messages[0].amount[0].amount) === Number(finalAmount * 1000000)) {
              if (item.tx.body.messages[0].to_address === sendTo) {
              console.log('start update order');
                $("#stage1").hide();
                
                $("#mainTransaction").hide();
                $("#firstAmount").hide();
                $("#checkAmount").css("color", "#31BF91");
              }
              $("#checkAmount").show();
              $("#waitingcheckAmount").hide();
                var ordirID = "{$orderId}" 
                $.get(mainDomain+"/index.php?fc=module&module=bitcanna&controller=validation&check&tx="+item.txhash+"&order_id="+ordirID, function(returnFinal, status) { 
                console.log('Check Tx from php');
                console.log(returnFinal.message);
                //$("#validateTxAmount").hide('slow');
                $("#validateTx").hide('slow');
                $("#finalUrlTx").attr("href", "https://cosmos-explorer.bitcanna.io/transactions/"+item.txhash)
                $("#viewFinalTx").show(1000);
                $("#acceptedPayement").show(1000);
                

                //$("#stage2").show();
              }); 
            } else {
              $("#badCheckAmount").show();
              $("#waitingcheckAmount").hide();
            }
          }, 8000);
      }
    });
  }); 
 
}

var myVar = setInterval(myTimer, 3000);
bitcannaClick()

function copyRecipient() {
  var copyText = document.querySelector("#recipient");
  copyText.select();
  document.execCommand("copy");
}
function copyMemo() {
  var copyText = document.querySelector("#memo");
  copyText.select();
  document.execCommand("copy");
}
document.querySelector("#copyRecep").addEventListener("click", copyRecipient);
document.querySelector("#copyMemo").addEventListener("click", copyMemo);

</script>

  
{/block}
