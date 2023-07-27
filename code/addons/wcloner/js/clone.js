// Add clone icon, but make sure this code only runs if on entries listings

if ($(location).attr('href').indexOf('/cp/publish/edit') !== -1 && $(location).attr('href').indexOf('/entry/') == -1) {
	$('.tbl-wrap ul.toolbar').each(function(i, obj) {
		var id = $(this).find('li.edit > a').first().attr('href');
		id = id.substr(id.lastIndexOf('/') + 1);
		id = id.split('&')[0];
		var title = $(this).closest('tr').find('td').eq(1);
		title = $(title).find('a').first().text();
		$(this).append('<li class="clone"><a href="" class="m-link clone-entry" title="Clone" data-confirm="' + title + '" data-content_id="' + id + '" rel="modal-confirm-clone"></a></li>');
	});
}


// Modal for when the tool is clicked

$('body').on('click', 'a.clone-entry', function (e) {
    var modalIs = $('.' + $(this).attr('rel'));
    $('.box', modalIs).html('').append('<h1>Confirm cloning...</h1><form method="post" id="willow-clone-form"><input type="hidden" id="clone-entry_id" value="' + $(this).data('content_id') + '"><div class="txt-wrap"><p>Are you sure you want to clone <strong>' + $(this).data('confirm') + '</strong> ?</p><fieldset class="form-ctrls"><button class="btn submit wl-clone-go">Clone</button></fieldset></form></div>');
    $('input[name="content_id"]', modalIs).val($(this).data('content_id'));
    e.preventDefault();
 });

// Sends cloning request to module

$(document).on('submit', '#willow-clone-form', function(e){
    e.preventDefault();
	$(this).find('.m-close').prop('disabled', true);
	$(this).find('.wl-clone-go').addClass('work');
	$(this).find('.wl-clone-go').text('Cloning...');
	var entry_id = $('#clone-entry_id').val();
	var post_data = 'ACT=' + wClonerACT + '&entry_id=' + entry_id + '&csrf=' + EE.CSRF_TOKEN;
	$.ajax({
	    url: '/',
	    type: "POST",
	    dataType: "json",
	    data: post_data,
	    success: function(data)
	    {
	    	var json = $.parseJSON(data);
	    	if (json.cloned == true)
	    	{
	    		// Regular Entries

	    		if ($(location).attr('href').indexOf('/cp/publish/edit') !== -1)
	    		{
	    			var new_url =  window.location.href.replace('/edit', '/edit/entry/' + json.entry)
	    			window.location = new_url;
	    		}

	    		// Zenbu

	    		if ($(location).attr('href').indexOf('/cp/addons/settings/zenbu') !== -1)
	    		{
	    			var new_url =  window.location.href.replace('/cp/addons/settings/zenbu', '/cp/publish/edit/entry/' + json.entry)
	    			window.location = new_url;
	    		}

	    	}
	    },
		error: function(XMLHttpRequest, textStatus, errorThrown) {
     		console.log(XMLHttpRequest + textStatus + errorThrown);
     	}
	});

});

//Listens for ajax pagination success and adds icon to toolbar

$(document).ajaxSuccess(function( event, xhr, settings ) {
	if (settings.url.indexOf('/cp/publish/edit') !== -1 && settings.url.indexOf('/cp/publish/edit/entry') == -1) {
		$('.tbl-wrap ul.toolbar').each(function(i, obj) {
			var id = $(this).find('li.edit > a').attr('href');
			id = id.substr(id.lastIndexOf('/') + 1);
			var title = $(this).closest('tr').find('td').eq(1);
			title = $(title).find('a').first().text();
			$(this).append('<li class="clone"><a href="" class="m-link clone-entry" title="Clone" data-confirm="' + title + '" data-content_id="' + id + '" rel="modal-confirm-clone"></a></li>');
		});
	}

// Add clone icon to Zenbu!

	if (settings.url.indexOf('/cp/addons/settings/zenbu') !== -1) {
		console.log('here');
		$('.resultsTable tbody > tr').each(function(i,obj) {
			var title = $(this).find('td').eq(1).find('a').first().text();
			var id = $(this).find('td').eq(0).find('input[type=checkbox]').attr('value');
			if ($(this).find('td').eq(1).find('li.clone').length == 0) {
				$(this).find('td').eq(1).prepend('<ul class="toolbar wcloner-toolbar"><li class="clone"><a href="" class="m-link clone-entry" title="Clone" data-confirm="' + title + '" data-content_id="' + id + '" rel="modal-confirm-clone"></a></li></ul>');
			}
		});
	}
});

// Changes the URL title when editing cloned entries

if ($(this).find('form input[name=title]').length > 0 && $(location).attr('href').indexOf('/cp/publish/edit/entry') !== -1)
{
	if ($("form input[name=title]").val().indexOf('[Cloned]') == 0)
	{
	    $("form input[name=title]").bind("keyup blur", function() {
	            $("form input[name=title]").ee_url_title($("form input[name=url_title], form input[name=structure__uri]"));
	    });
	}
}