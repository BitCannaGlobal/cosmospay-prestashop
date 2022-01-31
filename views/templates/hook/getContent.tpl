

<fieldset>
   {if isset($confirmation)}
   <div class="alert alert-success">Settings updated</div>
   {/if}
 
   <div class="panel">
      <div class="panel-heading">
         <legend><img src="../modules/bitcanna/img/logo.png" alt="" width="32" /> Configuration</legend>
      </div>
      <form action="" method="post">
      <div class="form-group">
         <label for="title">Title</label>
         <input type="text" value="{$moduleTitle}" class="form-control" name="moduleTitle" id="title" aria-describedby="titleHelp" placeholder="Title of module">
         <small id="titleHelp" class="form-text text-muted">The title of the module appear in the list of payments.</small>
      </div>
      <div class="form-group">
         <label for="bcnaAddress">Your Bitcanna address</label>
         <input type="text" value="{$bcnaAddress}" class="form-control" name="bcnaAddress" id="bcnaAddress" aria-describedby="yourAddressHelp" placeholder="Your Bitcanna address">
         <small id="yourAddressHelp" class="form-text text-muted">The bitcanna address where you will receive payments.</small>
      </div>
      <div class="form-group">
         <label for="lcdUrl">LCD url</label>
         <input type="text" value="{$lcdUrl}" class="form-control" name="lcdUrl" id="lcdUrl" aria-describedby="yourAddressHelp" placeholder="Your Bitcanna address">
         <small id="yourAddressHelp" class="form-text text-muted">The url of bitcanna LCD.</small>
      </div>
      <div class="panel-footer">
         <button type="submit" name="mymod_pc_form" class="btn btn-default pull-right">
         <i class="process-icon-save"></i> Enregistrer
         </button>
      </div>
      </form>
   </div>
</fieldset>

