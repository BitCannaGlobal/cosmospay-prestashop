 
<fieldset>
{if $disclaimer_check}
   {if isset($confirmation)}
   <div class="alert alert-success">Settings updated</div>
   {/if}
 {* {$configCosmos|@var_dump} *}
 
   <div class="panel">
      <div class="panel-heading">
         <legend><img src="../modules/cosmospay/img/logo.png" alt="" width="32" /> Configuration</legend>
      </div>
      <div class="form-wrapper">
      <form action="" method="post">
      <div class="form-group">
 
         <legend>Title</legend>
         <div  >
          <input type="text" value="Cosmos Pay" class="form-control" name="moduleTitle" id="title" aria-describedby="titleHelp" placeholder="Title of module" disabled>
          <small id="titleHelp" class="form-text text-muted">This is the title of the payment method that shows up in the list of payment methods to pay for the order.</small>
         </div>
         <legend>Description</legend>
         <div  >
          <input type="text" value="{$moduleDesc}" class="form-control" name="moduleDesc" id="title" aria-describedby="titleHelp" placeholder="Description of module">
          <small id="titleHelp" class="form-text text-muted">This is the payment method description that the customer will see on your website.</small>
         </div>         
      </div>
 
      <legend>Select your cryptocurrencies to accept</legend>

        {foreach from=$configCosmos key=k item=v}            
          <!--<option value="{$v->name}" {if $v->name|in_array:$selectChain}selected="selected"{/if}> {$v->name}</option>-->
          <div>
            <input type="checkbox" id="chainsSelected[]" name="checkChains[]" value="{$v->name}" {if $v->name|in_array:$selectChain}checked{/if}>
            <label>{$v->name}</label>
          </div>
        {/foreach}  

        <br />
        <legend>Your receiving addresses</legend>
        {foreach from=$configCosmos key=k item=v}      
        {if $inputActive}
          {foreach from=$inputActive key=dbk item=dbv} 
            {if $dbk eq $v->name}
              <div>
                <span id="{$dbk}">
                  <br />Your {$v->name} address <input type="text" name="input[{$dbk}]" id="input_{$v->name}" value="{$dbv}" size="10" required>
                      <div id="goodAddr_{$v->name}" style="display: none; color:green;">This is a valid address.</div>
                      <div id="badAddr_{$v->name}" style="display: none; color:red;">This is an invalid address. Please double-check.</div>
                      <div id="badAddrPrefix_{$v->name}" style="display: none; color:red;">This address does not belong to this chain. Please update to the right address.</div>     
                    <button id="target" value="{$v->name}" name="get_chain" class="button button-primary" type="button">
                      Connect {$v->name}
                    </button>                    
                </span>
              </div>  
            {/if}
          {/foreach}  
        {else}  
          <div>
            <span id="{$v->name}">
              <br />Your {$v->name} address<input type="text" name="input[{$v->name}]" value="" required>
                      <div id="goodAddr_{$v->name}" style="display: none; color:green;">This is a valid address.</div>
                      <div id="badAddr_{$v->name}" style="display: none; color:red;">This is an invalid address. Please double-check.</div>
                      <div id="badAddrPrefix_{$v->name}" style="display: none; color:red;">Bad address prefiix.</div>              
              
                    <button id="target" value="{$v->name}" name="get_chain" class="button button-primary" type="button">
                      Connect {$v->name}
                    </button>                 
            </span>
          </div>         
        {/if}           
        {/foreach}          
        
        <div id="addInput"></div>  
        <!--{if $inputActive}
          {foreach from=$inputActive key=k item=v}        
            <div>
              <span id="{$k}"><br />Address {$k}<input type="text" name="input[{$k}]" value="{$v}"></span>  
            </div> 
          {/foreach}   
        {/if}  -->  
        <br />
        <button type="submit" id="sendConfig" name="mymod_pc_form" >
          <i class="process-icon-save"></i> Save Changes
        </button>   
       <!-- {$configCosmos|@var_dump}-->
 
      </form>
      </div>
   </div> 
{else}
   {if isset($confirmation)}
   <div class="alert alert-success">Settings updated</div>
   {/if}
 {* {$configCosmos|@var_dump} *}
 
   <div class="panel">
 
      <div class="form-wrapper">
      <form action="" method="post">
      <div class="form-group">
 
         <legend>Disclaimer</legend>
         <div>
          <textarea id="story" name="story" rows="15" cols="33">{$disclaimer_data}</textarea>
                <div>
                  <input type="checkbox" id="disclamer1" name="disclamer1">
                  <label for="disclamer1">I accept this disclaimer.        
                    {if $disclaimer_error}
                      <font color="red">You must check the box to accept the disclaimer.</font>
                    {/if}
                  </label>
                </div>
         </div>
      </div>

        <br />
        <button type="submit" name="accept_disclaimer" >
          <i class="process-icon-save"></i> Save Changes
        </button>   
      </form>
      </div>
   </div> 
{/if}    
</fieldset>

<script type="importmap">
  //"bech32": "https://unpkg.com/bech32@2.0.0/dist/index.js" 
  {
    "imports": {
      "bech32": "/modules/cosmospay/js/bech32.js"
    }
  }
</script>
<script type="module">
import bech32 from "bech32";

$(document).ready(function() {
   $( 'input[name="checkChains[]"]' ).click(function () { 
     if ($(this).is(':checked')) {
       $("#addInput").append(
          '<input type="hidden" name="input['+$(this).val()+']">' 
       ); 
       //$("#"+$(this).val()).show();
     } else {
       $("#"+$(this).val()).remove();
       // $("#"+$(this).val()).hide();
     }
   }); 
 

  $.getJSON( "https://store-api.bitcanna.io", async function( result ) {
    result.forEach((element) => {
      
      // $("#"+element.name).change(function() {
      $("#input_"+element.name).on('input', function() {   
        console.log($(this).val()) 
        try {
          let bech32Decode = bech32.decode($(this).val())
          console.log(bech32Decode) 
          if (bech32Decode.prefix === element.coinLookup.addressPrefix) {
            $("#goodAddr_"+element.name).show();
            $("#badAddr_"+element.name).hide();    
            $("#badAddrPrefix_"+element.name).hide();
            $('#sendConfig').prop('disabled', false);
          } else {
            $("#goodAddr_"+element.name).hide();
            $("#badAddr_"+element.name).hide(); 
            $("#badAddrPrefix_"+element.name).show();
            $('#sendConfig').prop('disabled', true);
          }

        } catch (error) {
          console.error(error);
          $("#goodAddr_"+element.name).hide();
          $("#badAddrPrefix_"+element.name).hide();
          $("#badAddr_"+element.name).show();
          $('#sendConfig').prop('disabled', true);
        }      
      });        
    });         
  }); 
  
  
});
 jQuery(function($){  
  $( "button[name='get_chain']" ).click( async function() {
    var chainCall = $(this).val()
    if (!window.keplr) {
      alert( "Please install keplr extension" );
    } else {
      $.getJSON( "https://store-api.bitcanna.io", async function( result ) {
        let foundChain = result.find(element => element.name === chainCall);        
        
        const chainId = foundChain.chainId
        await window.keplr.enable(chainId)  
        const offlineSigner = window.keplr.getOfflineSigner(chainId)
        const accounts = await offlineSigner.getAccounts()         
        console.log(accounts[0].address)
        $( '#input_' + foundChain.name ).val(accounts[0].address)
            $("#goodAddr_"+foundChain.name).show();
            $("#badAddr_"+foundChain.name).hide();    
            $("#badAddrPrefix_"+foundChain.name).hide();  
            $('#sendConfig').prop('disabled', false);
      });     
    } 
  });  
});
</script>
 
