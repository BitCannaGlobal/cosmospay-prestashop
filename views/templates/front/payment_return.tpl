{extends "$layout"}


{block name="content"}
 <section>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
 
 
<div class="cosmos-card" id="mainTransaction">
<br />
<div id="mainPay">
   <div class="cosmos-payinfo">
      <div class="cosmos-card-title">
         <img style="vertical-align:middle" src="{$urls.base_url}modules/cosmospay/img/BCNA-icon.svg" id="chainIcon" width="25" height="25"> 
         <div class="cosmos-h3" id="finalAmount"></div> 
      </div>
      <div class="cosmos-card-amount">
         {$orderSymbol}{$orderValue}
      </div>
   </div>
 
   <div class="cosmos-content">
      <p>Select your cryptocurrency:</p>
      <div class="box">     
        <select id="selectChain">
          {foreach from=$unserializeChains key=k item=v}
            <option value="{$v}">{$v}</option>
          {/foreach}
        </select>
      </div>   
      <br /><br /><br />
      <p>Select your prefered way to pay:</p>
      <div class="box">
        <select id="selectMethod">
          <option value="keplr">Pay automatically with keplr</option>
          <option value="another">Pay with another wallet</option>
        </select>
      </div>
      <br /><br /><br />
      <button class="buttonSend" id="sendStep2">Next</button>
      <div align="center"><br />
        <a href="" id="cancel" style="color: red;"><u>Cancel</u></a>
      </div>
    </div>
    </div>
    <div id="cancelTx1" align="center" style="display: none;">
      <img src="{$mainDomain}modules/cosmospay/img/cancel.png" width="75" height="75">
      <br /><br />
      <div class="cosmos-h5">Your payment has been canceled</div>
      <br /><br />
    </div> 
  </div> 
</div>  


<div class="cosmos-card" id="mainTransaction2" style="display: none;">
  <br />
  <div class="cosmos-payinfo">
    <div class="cosmos-card-title">
      <img id="chainIcon2" src="" width="25" height="25"> 
      <div class="cosmos-h5">
        <div id="finalAmount2"></div>
      </div>
    </div>
    <div class="cosmos-card-amount">
      {$orderSymbol}{$orderValue}
    </div>
  </div>
  <div class="cosmos-content">
    <div  align="center"> 
      <div class="loader" id="spinner"></div>
      <div id="cancelTx" align="center" style="display: none;">
        <img src="{$urls.base_url}modules/cosmospay/img/cancel.png" width="75" height="75">
        <br /><br />
        <div class="cosmos-h5">Keplr canceled</div>
        <div id="keplrError" style="color: red;"></div><br />
        <button class="buttonRetry" id="retry">Retry</button> 
        <div align="center"><br />
          <a href="" id="cancel2" style="color: red;"><u>Cancel</u></a>
        </div>
      </div>
      <div id="AcceptedTx" align="center" style="display: none;">
        <img src="{$urls.base_url}modules/cosmospay/img/accepted.png" width="75" height="75">
        <br /><br />
        <div class="cosmos-h5">Payment accepted</div>
        <a href="" id="finalUrlTx" target="_blank">View transaction</a>
      </div>
    </div>
  </div>
</div>
<div class="cosmos-card" id="mainTransaction3" style="display: none;">
  <br />
  <div class="cosmos-payinfo">
    <div class="cosmos-card-title">
      <img id="chainIcon3" src="" width="25" height="25"> 
 
        <div class="cosmos-h5" id="finalAmount3"></div>
 
    </div>
    <div class="cosmos-card-amount">
      {$orderSymbol}{$orderValue}
    </div>
  </div>
  <div class="d-flex justify-content-center">
    <div class="cosmos-content" id="manualFinal">
      <div id="phase1">
        <p>Please send the <b>exact</b> same amount of coins to the following address 
        <div class="input-wrapper" id="copyRecep">
          <input type="text" id="recipient" name="recipient" value=""  aria-label="readonly input example" readonly>
        </div>
        <span style="display: none; color: green;" style="copyAddress" id="copyAddress">Address copied</span>
        </p>
        <p>Add the following ID to the <b>MEMO</b> field in your transaction</p>
        <div class="input-wrapper" id="copyMemo">
          <input value="" type="text" id="memo" name="memo" aria-label="readonly input example" readonly> 
        </div>
        <span style="display: none; color: green;" id="copyMemoMessage">Memo copied</span>
        <br />
        <p>Make sure to send your transaction to the <b>correct address</b> with the <b>precise amount</b> and the <b>correct MEMO</b>. If you need help, contact customer support.</p>
        <div class="cancelTx" align="center"> 
          <a href="" id="cancel2" style="color: red;"><u>Cancel</u></a>
        </div>            
      </div>
      <div id="phase2" align="center" style="display: none;">
        <br />
        <div class="loader" id="spinnerManual"></div>
        <hr>
        <div class="cosmos-h5" align="center">Checking</div>
      </div>
      <div id="phase3" style="display: none;">
        <div id="AcceptedTx" align="center">
          <img src="{$urls.base_url}modules/cosmospay/img/accepted.png" width="75" height="75">
          <br /><br />
          <div class="cosmos-h5">Payment accepted</div>
          <a href="" id="finalUrlTx" target="_blank">View transaction</a>
        </div>
      </div>
      <div id="errorManual" style="display: none;">
        <div align="center">
          <div class="cosmos-h5">Error</div>
          <div id="errorMessage"></div>
        </div>
      </div>
    </div>
  </div>
</div>
 
<div class="timerCard" id="timer">
Time left: <b><span id="minutes"></span>:<span id="seconds"></span></b>
</div>

     <div id="cosmos-footer-text">
         <div>Powered by <b>BitCanna</b></div>
     </div>



<br /><br />
<script>
 
window.onload = function() {
console.log(exportCosmosConfig)
  
    var order_id = "{$orderId}";
    var mainDomain = "{$mainDomain}";
    var memo = "{$memo}";
    var isBlocked = "false";
    var nonceSelectChain = ""
    var nonceDeleteOrder = ""
    var nonceSwitchMethod = ""
    var nonceSwitchMethod = ""
    var setDefault = "{$unserializeChains[0]}"
    startChecking( 
      order_id, 
      mainDomain, 
      memo, 
      isBlocked, 
      nonceSelectChain, 
      nonceDeleteOrder, 
      nonceSwitchMethod,
      setDefault
    );
} 
</script>

</section>  
{/block}
