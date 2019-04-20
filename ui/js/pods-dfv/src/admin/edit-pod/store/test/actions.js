import {
	uiConstants,
	podMetaConstants,
	labelConstants
} from '../constants';

import {
	setLabelValue,
	setPodName,
	setSaveStatus,
	setActiveTab
} from '../actions.js';

describe( 'actions', () => {

	// UI
	describe( 'ui actions', () => {
		const { actions } = uiConstants;

		describe( 'setActiveTab', () => {
			const action = actions.SET_ACTIVE_TAB;

			it( `Should return ${action} action`, () => {
				const activeTab = uiConstants.tabNames.LABELS;
				const expected = {
					type: action,
					activeTab: activeTab
				};
				expect( setActiveTab( activeTab ) ).toEqual( expected );
			} );
		} );

		describe( 'setSaveStatus', () => {
			const action = actions.SET_SAVE_STATUS;

			it ( `Should return ${action} action`, () => {
				const saveStatus = uiConstants.saveStatuses.SAVE_SUCCESS;
				const expected = {
					type: action,
					saveStatus: saveStatus
				};
				expect( setSaveStatus( saveStatus ) ).toEqual( expected );
			} );
		} );
	} );

	// Pod meta
	describe( 'pod meta actions', () => {
		const { actions } = podMetaConstants;

		describe( 'setPodName', () => {
			const action = actions.SET_POD_NAME;

			it( `Should return ${action} action`, () => {
				const podName = 'xyzzyy';
				const expected = {
					type: action,
					podName: podName
				};
				expect( setPodName( podName ) ).toEqual( expected );
			} );
		} );
	} );

	// Labels
	describe( 'label actions', () => {
		const { actions } = labelConstants;

		describe( 'setLavelValue', () => {
			const action = actions.SET_LABEL_VALUE;

			it( `Should return ${action} action`, () => {
				const labelName = 'xxx';
				const newValue = 'yyy';
				const expected = {
					type: action,
					labelName: labelName,
					newValue: newValue
				};
				expect( setLabelValue( labelName, newValue ) ).toEqual( expected );
			} );
		} );
	} );
} );
