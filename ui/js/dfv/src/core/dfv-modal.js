
/**
 * A frame for displaying a modal popup with iframe content
 *
 * @augments wp.media.view.Frame
 */
export const PodsDFVModal = wp.media.view.Modal.extend( {

	/**
	 * @param {Object} options
	 * @return {wp.media.view.Modal} Returns itself to allow chaining
	 */
	close( options ) {
		const retVal = wp.media.view.Modal.prototype.close.apply( this, options );

		// Alert the listening control when we've been cancelled
		if ( options && options.escape ) {
			window.parent.jQuery( window.parent ).trigger( 'dfv:modal:cancel' );
		}

		return retVal;
	},

} );
