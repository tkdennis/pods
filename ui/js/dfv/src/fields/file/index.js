// @todo add tests
import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';

import apiFetch from '@wordpress/api-fetch';

import MarionetteAdapter from 'dfv/src/fields/marionette-adapter';
import { File as FileView } from './file-upload';
import { FIELD_COMPONENT_BASE_PROPS } from 'dfv/src/config/prop-types';

const getMediaItemData = async ( mediaID ) => {
	try {
		const result = await apiFetch( { path: `/wp/v2/media/${ mediaID }` } );

		return {
			id: mediaID,
			icon: result?.media_details?.sizes?.thumbnail?.source_url,
			name: result.title.rendered,
			// @todo This should be based on the adminurl instead of hardcoded to /wp-admin/ -- fix this later.
			edit_link: `/wp-admin/post.php?post=${ mediaID }&action=edit`,
			link: result.link,
			download: result.source_url,
		};
	} catch ( e ) {
		return {
			id: mediaID,
		};
	}
};

const File = ( props ) => {
	const {
		fieldConfig = {},
		htmlAttr = {},
		value,
		setValue,
		setHasBlurred,
	} = props;

	const {
		fieldItemData = [],
	} = fieldConfig;

	const [ collectionData, setCollectionData ] = useState( [] );

	const setValueFromModels = ( models ) => {
		if ( Array.isArray( models ) ) {
			setValue( models.map( ( model ) => model.id ).join( ',' ) );

			setCollectionData( models.map( ( model ) => model.attributes ) );
		} else {
			setValue( models.get( 'id' ) );

			setCollectionData( models.get( 'attributes' ) );
		}

		setHasBlurred( true );
	};

	// Force the limit to 1 if this the field only allows a single upload.
	const correctedLimit = fieldConfig.file_format_type === 'single'
		? '1'
		: fieldConfig.file_limit;

	// The `value` prop will be a comma-separated string of media post IDs,
	// but we need to pass an array of objects with data about the media
	// to the Backbone view/model. Only make the expensive API requests if
	// we don't have data about a media post on initial page load.
	useEffect( () => {
		if ( ! value ) {
			setCollectionData( [] );
			return;
		}

		const getAndSetMediaData = async ( mediaIDs ) => {
			// Check first if all items are available in fieldItemData,
			// if not we need to do a REST API request to get the data.
			let areAllItemsAvailableFromFieldItemData = true;

			const dataFromFieldItemData = mediaIDs.map( ( mediaID ) => {
				const matchingFieldItemData = fieldItemData.find(
					( fieldItem ) => Number( fieldItem.id ) === Number( mediaID ),
				);

				if ( ! matchingFieldItemData ) {
					areAllItemsAvailableFromFieldItemData = false;
					return null;
				}

				return matchingFieldItemData;
			} );

			if ( areAllItemsAvailableFromFieldItemData ) {
				setCollectionData( dataFromFieldItemData );
				return;
			}

			// If we didn't find everything, fall back to the API request.
			const results = await Promise.all( mediaIDs.map( getMediaItemData ) );
			setCollectionData( results );
		};

		if ( Array.isArray( value ) ) {
			getAndSetMediaData( value );
		} else if ( 'object' === typeof value ) {
			setCollectionData( value );
		} else if ( 'string' === typeof value ) {
			getAndSetMediaData( value.split( ',' ) );
		} else if ( 'number' === typeof value ) {
			getAndSetMediaData( [ value ] );
		} else {
			// eslint-disable-next-line no-console
			console.error( `Invalid value type for file field: ${ fieldConfig.name }` );
		}
	}, [] );

	return (
		<MarionetteAdapter
			{ ...props }
			htmlAttr={ htmlAttr }
			fieldConfig={ {
				...fieldConfig,
				file_limit: correctedLimit,
			} }
			View={ FileView }
			value={ collectionData }
			setValue={ setValueFromModels }
		/>
	);
};

File.propTypes = {
	...FIELD_COMPONENT_BASE_PROPS,
	value: PropTypes.oneOfType( [
		PropTypes.arrayOf(
			PropTypes.oneOfType( [
				PropTypes.string,
				PropTypes.number,
			] )
		),
		PropTypes.string,
		PropTypes.number,
	] ),
};

export default File;
