$(document).ready(function()
{
	$('.da-auth-link').on('click', function(e)
	{
		e.preventDefault();
		window.open($(this).attr('href'), 'google_oauth', 'width=600,height=600,location=no,toolbar=no,scrollbars=no');
	});
});