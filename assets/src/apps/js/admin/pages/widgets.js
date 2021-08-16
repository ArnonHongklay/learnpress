const $ = jQuery;

function formatCourse( repo ) {
	if ( repo.loading ) {
		return repo.text;
	}
	const markup = "<div class='select2-result-course_title'>" + repo.id + ' - ' + repo.title.rendered + '</div>';
	return markup;
}

function formatCourseSelection( repo ) {
	return repo.title.rendered || repo.text;
}

function autocompleteWidget( widget = null ) {
	const searchs = widget ? $( widget ).find( '.lp-widget_select_course' ) : $( '.lp-widget_select_course' );

	searchs.select2( {
		ajax: {
			method: 'GET',
			url: '/wp-json/wp/v2/lp_course',
			dataType: 'json',
			delay: 250,
			data( params ) {
				return {
					search: params.term,
				};
			},
			processResults( data, params ) {
				params.page = params.page || 1;

				return {
					results: data,
				};
			},
			cache: true,
		},
		escapeMarkup( markup ) {
			return markup;
		},
		minimumInputLength: 2,
		templateResult: formatCourse, // omitted for brevity, see the source of this page
		templateSelection: formatCourseSelection, // omitted for brevity, see the source of this page
	} );
}

document.addEventListener( 'DOMContentLoaded', function( event ) {
	if ( document.querySelector( '#widgets-editor' ) ) {
		$( document ).on( 'widget-added', function( event, widget ) {
			autocompleteWidget( widget );
		} );
	} else {
		autocompleteWidget();
	}
} );
