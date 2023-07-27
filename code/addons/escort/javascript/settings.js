$(document).ready(function()
{
	$('.escort-service-list').sortable(
	{
		axis: 'y',
		opacity: 0.5,
		update: function()
		{
			var serviceOrder = [];
			$('.escort-service-list li').each(function()
			{
				serviceOrder.push($(this).data('escortService'));
			});
			$.post(
				$('.escort-service-list').data('actionUrl'),
				{
					service_order: serviceOrder.toString(),
					CSRF_TOKEN: EE.CSRF_TOKEN
				}
			);
		}
	});
});