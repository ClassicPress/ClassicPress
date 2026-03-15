/* global _cpFilepondLabels, FilePond */
/* exported i18nFilePondStrings */

// Translatable strings interface between ClassicPress and FilePond
var i18nFilePondStrings = {
	labelIdle: _cpFilepondLabels.labelIdle,
	labelInvalidField: _cpFilepondLabels.labelInvalidField,
	labelFileWaitingForSize: _cpFilepondLabels.labelFileWaitingForSize,
	labelFileSizeNotAvailable: _cpFilepondLabels.labelFileSizeNotAvailable,
	labelFileCountSingular: _cpFilepondLabels.labelFileCountSingular,
	labelFileCountPlural: _cpFilepondLabels.labelFileCountPlural,
	labelFileLoading: _cpFilepondLabels.labelFileLoading,
	labelFileAdded: _cpFilepondLabels.labelFileAdded, // assistive only
	labelFileLoadError: _cpFilepondLabels.labelFileLoadError,
	labelFileRemoved: _cpFilepondLabels.labelFileRemoved, // assistive only
	labelFileRemoveError: _cpFilepondLabels.labelFileRemoveError,
	labelFileProcessing: _cpFilepondLabels.labelFileProcessing,
	labelFileProcessingComplete: _cpFilepondLabels.labelFileProcessingComplete,
	labelFileProcessingAborted: _cpFilepondLabels.labelFileProcessingAborted,
	labelFileProcessingError: _cpFilepondLabels.labelFileProcessingError,
	labelFileProcessingRevertError: _cpFilepondLabels.labelFileProcessingRevertError,
	labelTapToCancel: _cpFilepondLabels.labelTapToCancel,
	labelTapToRetry: _cpFilepondLabels.labelTapToRetry,
	labelTapToUndo: _cpFilepondLabels.labelTapToUndo,
	labelButtonRemoveItem: _cpFilepondLabels.labelButtonRemoveItem,
	labelButtonAbortItemLoad: _cpFilepondLabels.labelButtonAbortItemLoad,
	labelButtonRetryItemLoad: _cpFilepondLabels.labelButtonRetryItemLoad,
	labelButtonAbortItemProcessing: _cpFilepondLabels.labelButtonAbortItemProcessing,
	labelButtonUndoItemProcessing: _cpFilepondLabels.labelButtonUndoItemProcessing,
	labelButtonRetryItemProcessing: _cpFilepondLabels.labelButtonRetryItemProcessing,
	labelButtonProcessItem: _cpFilepondLabels.labelButtonProcessItem
};

/*
 * Create a abstracted Filepond.create so i18n strings are always passed in from core
 */
const originalCreate = FilePond.create;

FilePond.create = function( element, options = {} ) {
	return originalCreate( element, {
		..._cpFilepondLabels,
		...options
	} );
};
