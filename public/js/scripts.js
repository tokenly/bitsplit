

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

$(document).ready(function(){
	$('.fancy').fancybox();
	$('.datetimepicker').datetimepicker();
	
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
});
