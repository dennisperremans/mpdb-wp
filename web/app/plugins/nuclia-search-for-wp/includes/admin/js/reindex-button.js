(function($) {
	
	$(
		function() {
			var $reindexButtons = $( '.nuclia-reindex-button' );
			$reindexButtons.on( 'click', handleReindexButtonClick );
		}
	);

	var ongoing = 0;

	$( window ).on(
		'beforeunload', function() {
			if (ongoing > 0) {
				return 'If you leave now, re-indexing tasks in progress will be aborted';
			}
		}
	);

	function handleReindexButtonClick(e) {

		$clickedButton = $( e.currentTarget );
		var index      = $clickedButton.data( 'index' );
		if ( ! index) {
			throw new Error( 'Clicked button has no "data-index" set.' );
		}
		ongoing++;

		$clickedButton.attr( 'disabled', 'disabled' );
		$clickedButton.data( 'originalText', $clickedButton.text() );
		updateIndexingPourcentage( $clickedButton, 0 );

		reIndex( $clickedButton, index );
	}

	function updateIndexingPourcentage($clickedButton, amount) {
		$clickedButton.text( 'Processing, please be patient ... ' + amount + '%' );
	}

	function reIndex($clickedButton, index ) {

		var totalPosts = $clickedButton.data( 'total' );
		
		var data = {
			'action': 'nuclia_re_index',
			'post_type': index,
		};
		
		$.post(
			ajaxurl, data, function(response) {
				if (typeof response.nbPosts === 'undefined') {
					alert( 'An error occurred' );
					resetButton( $clickedButton );
					return;
				}

				if (response.nbPosts === 0) {
					resetButton( $clickedButton );
					return;
				}
				progress = Math.round( (( totalPosts - response.nbPosts ) / totalPosts) * 100 );
				updateIndexingPourcentage( $clickedButton, progress );

				reIndex( $clickedButton, index );

			}
		).fail(
			function(response) {
				alert( 'An error occurred: ' + response.error );
				resetButton( $clickedButton );
			}
		);
	}

	function resetButton($clickedButton) {
		ongoing--;
		$clickedButton.text( 'Done' );
		//$clickedButton.removeAttr( 'disabled' );
	}

})( jQuery );
