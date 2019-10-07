/* jshint node:true */
/* jshint es3:false */
/* jshint esversion:6 */

const emoji = require( './emoji' );

exports.setGruntReference = _grunt => {
	emoji.setGruntReference( _grunt );
};

exports.replaceEmojiRegex = emoji.replaceEmojiRegex;
