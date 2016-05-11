

function createCookie(name,value,days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        var expires = "; expires="+date.toGMTString();
    }
    else var expires = "";
    document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}
function eraseCookie(name) {
    createCookie(name,"",-1);
}


function numberWithCommas(x) {
    return number_format(x, 8).replace(/0+$/, "").replace(/\.+$/, "");
}


function number_format(number, decimals, dec_point, thousands_sep) {
    var n = !isFinite(+number) ? 0 : +number, 
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        toFixedFix = function (n, prec) {
            // Fix for IE parseFloat(0.55).toFixed(0) = 0;
            var k = Math.pow(10, prec);
            return Math.round(n * k) / k;
        },
        s = (prec ? toFixedFix(n, prec) : Math.round(n)).toString().split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(dec);
}

function getStageText(stage){
    stage = parseInt(stage);
    var text = 'Unknown';
    switch(stage){
        case -1:
            text = '<span class="text-success completed">Complete</span>';
            break;
        case -2:
            text = '<strong>HOLD</strong>';
            break;
        case 0:
            text = '<span class="text-warning">Initializing</span>';
            break;
        case 1:
            text = '<span class="text-warning">Collecting Tokens</span>';
            break;
        case 2:
            text = '<span class="text-warning">Collecting Fuel</span>';
            break;
        case 3:
            text = '<span class="text-info">Priming Inputs</span>';
            break;
        case 4:
            text = '<span class="text-info">Preparing Transactions</span>';
            break;
        case 5:
            text = '<span class="text-info">Broadcasting Transactions</span>';
            break;
        case 6:
            text = '<span class="text-info">Confirming Broadcasts</span>';
            break;
        case 7: 
            text = '<span class="text-success">Performing Cleanup</span>';
            break;
        case 8:
            text = '<span class="text-success">Finalizing Cleanup</span>';
            break;        
    }
    return text;
    
}

$(document).ready(function(){
	$('.fancy').fancybox();
	$('.datetimepicker').datetimepicker();
    
    $(function () {
      $('[data-toggle="tooltip"]').tooltip()
    })    
	
	$('select#value_type').change(function(e){
		var val = $(this).val();
		if(val == 'percent'){
			$('#percent_asset_total').slideDown();
		}
		else{
			$('#percent_asset_total').slideUp();
		}
		
	});
	
	
	$('body').delegate('.delete', 'click', function(e){
		var check = confirm('Are you sure you want to delete?');
		if(!check || check == null){
			e.preventDefault();
			return false;
		}
	});			
	
	window.setTimeout(function(){
		
		var pocketsurl = $('.pockets-url').text(); //Pockets extension url
		var pocketsimage = $('.pockets-image-blue').text(); //Pockets icon
		if(pocketsurl != ''){
			createCookie('pockets-url-value', pocketsurl, 30);
			createCookie('pockets-icon-value', pocketsimage, 30);
		}
		
		$('.dynamic-payment-button').each(function(){
			var amount = $(this).data('amount');
			var address = $(this).data('address');
			var label = $(this).data('label');
			var tokens = $(this).data('tokens');
			
			if(pocketsurl == ''){
				pocketsurl = readCookie('pockets-url-value');
				pocketsimage = readCookie('pockets-icon-value');
			}
			if(!pocketsurl || pocketsurl == null || pocketsurl == ''){
				return false;
			}

			var label_encoded = encodeURIComponent(label).replace(/[!'()*]/g, escape); //URI encode label and remove special characters
			var urlattributes = "?address="+address+"&label="+label_encoded+"&tokens="+tokens+"&amount="+amount;
			$(this).html("<a href='"+pocketsurl+urlattributes+"' target='_blank'><img src='"+pocketsimage+"' width='100px'></a>");
		});
	}, 300);	
    
    window.setInterval(function(){
        if($('#distro-dashboard').length > 0){
            var url = '/distribute/_status-info';
            $.get(url, function(data){
                
                $('#distro-total-count').html(numberWithCommas(data.stats.distro_count));
                $('#distro-total-completed').html(numberWithCommas(data.stats.distro_complete));
                $('#distro-total-txs').html(numberWithCommas(data.stats.distro_txs_complete));
                $.each(data.stats.distros, function(idx, val){
                    if(val.complete == 1){
                        val.stage = -1;
                    }
                    else if(val.hold == 1){
                        val.stage = -2;
                    }
                    $('.distro-' + val.id + '-status-text').html(getStageText(val.stage));
                    if(val.tx_confirmed >= val.tx_total){
                        $('#distro-' + val.id + '-table-complete-count-cont').html('<i class="fa fa-check text-success" title="Complete"></i> ' + val.tx_total);
                    }
                    else{
                        $('.distro-' + val.id + '-complete-count').html(val.tx_confirmed);
                    }
                    if(val.complete != 1 || val.asset_received > 0){
                        $('.distro-' + val.id + '-table-actions').find('.delete').hide();
                    }
                });
                
            });
        }
        
        if($('#distro-details').length > 0){
            var address = $('#distro-details').data('address');
            var url = '/distribute/' + address + '/_info';
            $.get(url, function(data){
                $('.distro-' + data.distro.id + '-complete-count').html(numberWithCommas(parseInt(data.tx_complete)));
                if(data.distro.complete == 1){
                    $('.distro-' + data.distro.id + '-status-text').html('<span class="text-success">Complete</span>');
                    $('#distro-' + data.distro.id + '-complete-cont').show();
                    $('#distro-hold-input-cont').hide();
                }
                else{
                    $('#distro-' + data.distro.id + '-complete-cont').hide();
                    $('#distro-hold-input-cont').show();
                    if(data.distro.hold == 1){
                        data.distro.stage = -2;
                    }                    
                    $('.distro-' + data.distro.id + '-status-text').html(getStageText(data.distro.stage));
                }  
                if(data.distro.stage_message != null && data.distro.stage_message.trim() != ''){
                    $('.status-message-cont').show();
                    $('#distro-' + data.distro.id + '-stage-message').html(data.distro.stage_message);
                }
                else{
                    $('.status-message-cont').hide();
                }
                $('#distro-' + data.distro.id + '-token-received').html(numberWithCommas(parseInt(data.distro.asset_received)/100000000));
                $('#distro-' + data.distro.id + '-fee-received').html(numberWithCommas(parseInt(data.distro.fee_received)/100000000));
                $('#distro-' + data.distro.id + '-last-update').html(data.distro.last_update);
                $('#distro-' + data.distro.id + '-complete-date').html(data.distro.complete_date);
                $.each(data.txs, function(idx, val){
                    var tx_status = '<i class="fa fa-cog" title="Awaiting fuel availability" style="color: #ccc;"></i>';
                    if(val.txid != null && val.txid.trim() != ''){
                        if(val.confirmed == 1){
                            tx_status = '<a href="https://blocktrail.com/BTC/tx/' + val.txid + '" target="_blank" title="View complete transaction"><i class="fa fa-check text-success"></i></a>';
                        }
                        else{
                            tx_status = '<a href="https://blocktrail.com/BTC/tx/' + val.txid + '" target="_blank" title="View transaction (in progress)"><i class="fa fa-spinner fa-spin"></i></a>';
                        }
                    }
                    else{
                        if(val.utxo){
                            tx_status = '<i class="fa fa-cog fa-spin" title="Preparing transaction"></i>';
                        }
                    }
                    $('#distro-tx-' + val.id + '-status').html(tx_status);
                });
            });
        }
    }, 10000);
});
