jQuery( document ).ready( function( $ ) {
	
	function populateTbody( tbody, rows ) {
		$(tbody).empty( );
		for( var i = 0; i < rows.length; i++ ) {
			name = rows[i]['name'];
			txt = rows[i]['text'];
			links = "<a href='#' class='report_up_action'>Move Up</a> | ";
			links += "<a href='#' class='report_down_action'>Move Down</a> | ";
			links += "<a href='#' class='report_delete_action'>Delete</a>";
			$(tbody).append( "<tr index='" + i + "'><th><strong>" + name + '</strong></th><td>' + txt + '</td><td>' + links + '</td></tr>' );
			$(tbody).append( "<input style='display:none' name='eordem_plugin_options[csv_fields][" + i + "][name]' value='" + name + "'/>");
			$(tbody).append( "<input style='display:none' name='eordem_plugin_options[csv_fields][" + i + "][text]' value='" + txt + "'/>");
		}
	}

	$( '#eordem_settings_add_button' ).click( function( ) {
		field_val = $( '#eordem_settings_field_select' ).val( );
		field = $( '#eordem_settings_field_select option[value="' + field_val + '"]' ).text( );
		txt = $( '#eordem_settings_display_text' ).val( );
		tbody = $( '#eordem_settings_report_tbody' );
		if ( txt == '' ) txt = field;
		rows.push( {'name': field, 'text': txt} ); 
		populateTbody( tbody, rows );

	});

	$( '.report_up_action' ).live( 'click', function( ) {
		index = $(this).parents( 'tr' ).attr( 'index' );
		if ( index > 0 && rows.length > 1 ) {
			first = rows.splice( index, 1 );
			second = rows.splice( parseInt(index) - 1, 1 );
			rows.splice( parseInt(index)-1, 0, first[0], second[0] );
			tbody = $( '#eordem_settings_report_tbody' );
			populateTbody( tbody, rows );
		}
	});

	$( '.report_down_action' ).live( 'click', function( ) {
		index = $(this).parents( 'tr' ).attr( 'index' );
		if ( index < rows.length - 1 && rows.length > 1 ) {
			first = rows.splice( index, 1 );
			second = rows.splice( index, 1 );
			rows.splice( index, 0, second[0], first[0] );
			tbody = $( '#eordem_settings_report_tbody' );
			populateTbody( tbody, rows );
		}
	});

	$( '.report_delete_action' ).live( 'click', function( ) {
		index = $(this).parents( 'tr' ).attr( 'index' );
		rows.splice( index, 1 );
		populateTbody( tbody, rows );
	});

	tbody = $( '#eordem_settings_report_tbody' );
	populateTbody( tbody, rows );

});
