jQuery(document).ready(function($) {
	var dcOutput;
	
	initDCForm = function(cf7form, tlds, url) {
		cf7form.find('.wpcf7-response-output').remove();
		
		if(typeof dcOutput === 'undefined') {
			form = $('<form>').addClass('dc-form').attr({
				'action': _dc.redirectTo,
				'method': 'post',
			});
			form.append(
				$('<input>').attr({
					'type': 'hidden',
					'name': 'sld',
					'value': url,
				})
			);
	
			dcOutput = $('<ul>').addClass('dc-output');
			
			form.append(dcOutput);
			form.append($('<input>').addClass('wpcf7-submit').attr({
				'type': 'submit',
				'value': 'Vraag aan',
			}));
			
			cf7form.after(form);
		}
		
		dcOutput.empty();
			
		$.each(tlds, function(i, tld) {
			row = $('<li>').attr('id', 'tld-' + i);
			row.append($('<input>').addClass('checkbox').attr('name', 'tld[' + tld + ']').attr('type', 'checkbox'));
			row.append($('<p>').addClass('sld').text(url));
			row.append($('<p>').addClass('tld').text('.' + tld));
			row.append($('<img>').addClass('ajax-loader').prop('src', _wpcf7.loaderUrl));
			row.append($('<span>').addClass('message'));
			
			row.click(function() {
				checkBox = $(this).find('input.checkbox');
				checkBox.attr('checked', !checkBox.attr('checked'));
			});
			row.find('input.checkbox').click(function(e) {
				e.stopPropagation();
			});
			
			dcOutput.append(row);
			
			data = {
				'action': 'check_domain',
				'sld': url,
				'tld': tld,
				'id': i,
			};
			
			handleResponse = function(response) {
				row = $('.dc-output #tld-' + response.id);
				
				row.find('.message').text(response.message);
				row.find('.ajax-loader').remove();

				if(!response.isAvailable) {
					row.addClass('unavailable');
				}
			};
			
			$.post(_dc.ajaxurl, data, handleResponse);
		});
	};
});
