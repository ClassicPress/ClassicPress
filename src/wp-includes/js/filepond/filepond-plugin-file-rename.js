/*!
 * FilePondPluginFileRename 1.1.8
 * Licensed under MIT, https://opensource.org/licenses/MIT/
 * Please visit https://pqina.nl/filepond/ for details.
 */

/* eslint-disable */

(function(global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined'
    ? (module.exports = factory())
    : typeof define === 'function' && define.amd
    ? define(factory)
    : ((global = global || self),
      (global.FilePondPluginFileRename = factory()));
})(this, function() {
  'use strict';

  var plugin = function plugin(_ref) {
    var addFilter = _ref.addFilter,
      utils = _ref.utils;

    // get quick reference to Type utils
    var Type = utils.Type,
      renameFile = utils.renameFile,
      isFile = utils.isFile,
      getExtensionFromFilename = utils.getExtensionFromFilename,
      getFilenameWithoutExtension = utils.getFilenameWithoutExtension;

    // called for each file that is loaded
    // right before it is set to the item state
    // should return a promise
    addFilter('LOAD_FILE', function(file, _ref2) {
      var query = _ref2.query;
      return new Promise(function(resolve, reject) {
        // reject
        var allowFileRename = query('GET_ALLOW_FILE_RENAME');
        var renameFunction = query('GET_FILE_RENAME_FUNCTION');
        if (!isFile(file) || !allowFileRename || !renameFunction) {
          resolve(file);
          return;
        }

        // can either return a name or a promise
        var newFilename = renameFunction({
          name: file.name,
          basename: getFilenameWithoutExtension(file.name),
          extension: '.'.concat(getExtensionFromFilename(file.name))
        });

        // renames the file and resolves
        var rename = function rename(name) {
          resolve(renameFile(file, name));
        };

        // has returned new filename immidiately
        if (typeof newFilename === 'string') {
          rename(newFilename);
          return;
        }

        // is promise
        newFilename.then(rename);
      });
    });

    return {
      options: {
        // Enable or disable file renaming
        allowFileRename: [true, Type.BOOLEAN],

        // Rename function to run for this
        fileRenameFunction: [null, Type.FUNCTION]
      }
    };
  };

  // fire pluginloaded event if running in browser, this allows registering the plugin when using async script tags
  var isBrowser =
    typeof window !== 'undefined' && typeof window.document !== 'undefined';
  if (isBrowser) {
    document.dispatchEvent(
      new CustomEvent('FilePond:pluginloaded', { detail: plugin })
    );
  }

  return plugin;
});
