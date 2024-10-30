(function ($){
	var $block = $('.insys-comment-parent'),
		wait = false;
	
	$block.find('a').click(function(e){
		e.preventDefault();

		if (wait) {
			return false;
		}

		if (!window.insysCommentParent) {
			return false;
		}

		var child = $block.find('input[name=child]:checked').val(),
			parent = $block.find('input[name=parent]:checked').val(),
			$link = $(this),
			timer = setInterval(function(){
				if ($link.text().length > 9) {
					$link.text('apply.');
				} else {
					$link.text($link.text() + '.');
				}
			}, 100);

		wait = true;
		
		$.ajax({
			url: window.insysCommentParent.ajax_url,
			type: 'POST',
			data: {
				'action': 'insys_comment_parent',
				'parent': parent,
				'child': child,
			},
			dataType: 'json'
		}).done(function(data){
			if (data.status && data.status == 'ok') {
				$block.find('input[name=child]:checked').prop('checked', false).trigger('change');
				$block.find('input[name=parent]:checked').prop('checked', false).trigger('change');

				if (data.info) {
					alert(data.info);
				} else {
					alert('ok, reload page');
				}
			} else {
				if (data.error) {
					alert(data.error);
				} else {
					alert('error, reload page');
				}
			}
		}).fail(function(){
			alert('error, reload page');
		}).always(function(){
			wait = false;
			clearInterval(timer)
			$link.text('apply');
		});
	});

	$block.find('input[name=child],input[name=parent]').change(function() {
		var name = $(this).attr('name');

		$block
			.find('input[name=' + name + ']')
				.parent('label')
					.removeClass('active')
					.parent('div')
						.removeClass('active-' + name)
					.end()
				.end()
				.filter(':checked')
					.parent('label')
						.addClass('active')
						.parent('div')
							.addClass('active-' + name)
						.end()
					.end();

		$block.find('a').removeClass('active');
		
		if ($block.find('.active-parent').length && $block.find('.active-child').length) {
			$block.find('.active-child a').addClass('active');
		}
	});

	$block.find('label,input').click(function(e) {
		e.preventDefault();
		e.stopPropagation();

		if (wait) {
			return false;
		}

		var $input = $(this).find('input');

		if ($input.is(':checked')) {
			$input.prop('checked', false).trigger('change');
		} else {
			$input.prop('checked', true).trigger('change');
		}
	});
})(jQuery);