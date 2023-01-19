function startChecking( order_id, mainDomain, memo, isBlocked, nonceSelectChain, nonceDeleteOrder, nonceSwitchMethod, setDefault ) {

  $( '#chainIcon' ).hide()
  $( '#chainIcon2' ).hide()
  $( '#mainTransaction' ).hide() 
  
  if ( isBlocked === 'true' ) {    
    $.getJSON( mainDomain+"/api-cosmos/?order_id="+order_id+"&finalData", async function ( result ) {    
      let foundChain = await exportCosmosConfig.initConfig.find( element => element.name === result.current_chain )
      $( '#finalAmount2, #finalAmount3' ).html( result.OrderPrice + ' ' + foundChain.coinLookup.viewDenom )  
      $( '#chainIcon2, #chainIcon3' ).attr( 'src', foundChain.coinLookup.icon )
      $( '#chainIcon2, #chainIcon3' ).show( )
      timerOrder( result.startTime ) 
      
      if( result.method === 'keplr' ) { 
        $( "#mainTransaction2" ).fadeIn( 500 )      
        keplrData = await exportCosmosConfig.initKeplr.addKeplrChain( result.current_chain )
        exportCosmosConfig.initsend.sendByChain( result.current_chain, result.adressToPay, result.OrderPrice, order_id, memo, $ )  
      } else {
        $( "#mainTransaction2" ).fadeOut( 500 )
        $( "#mainTransaction3" ).fadeIn( 500 )
        $( "#spinner" ).hide()
        $( "#recipient" ).val( result.adressToPay )
        $( "#memo" ).val( result.memo )   
        myTimer( result.adressToPay, result.OrderPrice, result.memo )
      }
    }) 
  } else {
    $('#mainTransaction').show();
    $.post( mainDomain+"/index.php?fc=module&module=cosmospay&controller=validation", { switch: setDefault, order_id: order_id, nonce: nonceSelectChain }, async function( result ) {
      let foundChain = await exportCosmosConfig.initConfig.find( element => element.name === setDefault )

      if(result.status === "canceled") {
        window.location.replace(mainDomain + "index.php?fc=module&module=cosmospay&controller=validation&cancel")        
      } else {
        $( "#returnChain" ).html( result.current_chain )
        $( "#returnLcd" ).html( result.lcd )
        $( "#finalAmount" ).html( result.CosmosPrice + ' ' + result.chainDenom )
        $( "#inputAmount" ).val( result.OrderPrice ) 
        $( "#inputAddress" ).val( result.adressToPay )
        $( "#tableInputAddress").html( result.adressToPay )
        $( "#chainIcon" ).attr( 'src', foundChain.coinLookup.icon )
        $( "#chainIcon" ).show()
        timerOrder( result.startTime )    
      }    
    })
  }
    
  
  $( '#sendStep2' ).click(function () {
      $( '#mainTransaction' ).hide()
      $.getJSON( mainDomain+"/index.php?fc=module&module=cosmospay&controller=validation&finalData", async function ( result ) {    
        let foundChain = exportCosmosConfig.initConfig.find( element => element.name === result.current_chain )
        $( '#finalAmount2, #finalAmount3' ).html( result.CosmosPrice + ' ' + foundChain.coinLookup.viewDenom )
        $( '#chainIcon2, #chainIcon3' ).attr('src', foundChain.coinLookup.icon)
        $( '#chainIcon2, #chainIcon3' ).show()

        timerOrder( result.startTime )
        if( result.method === 'keplr' ) {
          $( '#mainTransaction' ).fadeOut( 500 )
          $( '#mainTransaction2' ).fadeIn( 500 )
          keplrData = await exportCosmosConfig.initKeplr.addKeplrChain( result.current_chain )
          exportCosmosConfig.initsend.sendByChain(result.current_chain, result.adressToPay, result.CosmosPrice, order_id, memo, $)  
        } else {
          $( '#mainTransaction2' ).fadeOut( 500 )
          $( '#mainTransaction3' ).fadeIn( 500 )
          $( "#spinner" ).hide()
          $( "#recipient" ).val( result.adressToPay )
          $( "#memo" ).val( result.memo )
          myTimer( result.memo )
        }          
      })
  })
  
  $( '#retry' ).click(function () {
    $( "#spinner" ).show()
    $( "#cancelTx" ).hide()
    
    $.getJSON( mainDomain+"index.php?fc=module&module=cosmospay&controller=validation&finalData", async function ( result ) {    
      let foundChain = exportCosmosConfig.initConfig.find( element => element.name === result.current_chain )
      
      keplrData = await exportCosmosConfig.initKeplr.addKeplrChain( result.current_chain )
      exportCosmosConfig.initsend.sendByChain( result.current_chain, result.adressToPay, result.CosmosPrice, order_id, memo, $ )
    }) 
  })
  
  $( '#selectChain' ).change( function() {

      let foundChain = exportCosmosConfig.initConfig.find( element => element.name === $(this).val() )    
      $.post( mainDomain+"/index.php?fc=module&module=cosmospay&controller=validation", { switch: $(this).val(), order_id: order_id }, function( result ) {
        $( "#returnChain" ).html( result.current_chain )
        $( "#returnLcd" ).html( result.lcd )
        $( "#finalAmount" ).html( result.CosmosPrice + ' ' + result.chainDenom )
        $( "#inputAmount" ).val( result.CosmosPrice )  
        $( "#inputAddress" ).val( result.adressToPay )
        $( "#tableInputAddress" ).html( result.adressToPay )
      });    
      $( '#chainIcon' ).attr( 'src', foundChain.coinLookup.icon ) 
      $( '#chainIcon' ).show()
  })

  $( '#selectMethod' ).change( function() {
      $.post( mainDomain+"index.php?fc=module&module=cosmospay&controller=validation", { switchMethod: $(this).val()}, function( result ) {
      })  
  })
 
  $( '#cancel, #cancel2' ).click( function () { 
    $.post( mainDomain + "index.php?fc=module&module=cosmospay&controller=validation", { cancel: 'true' }, function( result ) {
      window.location.replace(mainDomain + "index.php?fc=module&module=cosmospay&controller=validation&cancel")
      // window.location.reload()
    })  
  }) 

  function timerOrder( time ) {
    
    const dt = new Date(time).getTime();  
    
    var timestamp = ( dt * 1000 )      
    var date = new Date( dt )
    const countDownDateTime = dt  + 3600000 // 1 hour = 3600000 // 10 mn = 600000
    
    const minutesValue = document.querySelector( "#minutes" )
    const secondsValue = document.querySelector( "#seconds" )

    // run this function every 1000 ms or 1 second
    let cosmosTime = setInterval( function () {
      const dateTimeNow = new Date( ).getTime( )
      let difference = countDownDateTime - dateTimeNow
      // calculating time and assigning values
      minutesValue.innerHTML = Math.floor(
        ( difference % ( 1000 * 60 * 60 ) ) / ( 1000 * 60 )
      );
      secondsValue.innerHTML = Math.floor( (difference % (1000 * 60)) / 1000 )
      if ( difference < 0 ) {
        clearInterval( cosmosTime )
        $( "#spinner" ).hide()
        $( "#cancelTx" ).show()
        $( "#cancelTx1" ).show()      
        $( "#retry" ).hide()
        $( "#mainPay" ).hide()
        
        minutesValue.innerHTML = '00'
        secondsValue.innerHTML = '00'
        $.post( mainDomain + "index.php?fc=module&module=cosmospay&controller=validation", { cancel: 'true' }, function( result ) {
          // console.log(result)
          window.location.replace(mainDomain + "index.php?fc=module&module=cosmospay&controller=validation&cancel")
        })        
      }
    }, 1000 )
  }
  
  function myTimer( memo ) {
    
    var myVar = setInterval( () => {
      $.get( mainDomain+"index.php?fc=module&module=cosmospay&controller=validation&check=manual", function( data, status ) {     
        $.each( data.tx_responses, function( i, item ) {
          if( item.tx.body.memo === memo ) {        
            clearInterval( myVar )      
            $( "#phase1" ).hide( )
            $( "#phase2" ).show( )
            
            console.log( 'Transaction found!\n Memo: ' +item.tx.body.memo+ '\n Txhash: ' + item.txhash )
  
            setTimeout( function() {
              $.get( mainDomain+"index.php?fc=module&module=cosmospay&controller=validation&check&tx_hash=" + item.txhash, function( final_data, final_status ) {
                if ( final_data.error === true )  {
                  $( "#phase2" ).hide( )
                  $( "#errorManual" ).show( )
                  $( "#errorMessage" ).html( final_data.message )  
                  return
                } else {
                  $( "#phase2" ).hide( )
                  $( "#phase3" ).show( )
                  $( ".woocommerce-thankyou-order-received" ).css( "border-color", "#20c005" )
                  $( ".woocommerce-thankyou-order-received" ).css( "color", "#20c005" )
                  $( ".woocommerce-thankyou-order-received" ).html( "Payment accepted!" )               
                }
              })   
            }, 4000 )       
          }
        })
      })   
    }, 10000 )    
  }  
  
}

 
