/**
 * @license Copyright (c) 2003-2015, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here.
	// For complete reference see:
	// http://docs.ckeditor.com/#!/api/CKEDITOR.config

	// The toolbar groups arrangement, optimized for a single toolbar row.
	config.toolbarGroups = [
		{ name: 'document',	   groups: [ 'mode', 'document', 'doctools' ] },
		{ name: 'clipboard',   groups: [ 'clipboard', 'undo' ] },
		{ name: 'editing',     groups: [ 'find', 'selection', 'spellchecker' ] },
		{ name: 'forms' },
		{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
		{ name: 'paragraph',   groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ] },
		{ name: 'links' },
		{ name: 'insert' },
		{ name: 'styles' },
		{ name: 'colors' },
		{ name: 'tools' },
		{ name: 'others' },
		{ name: 'about' }
	];

	// The default plugins included in the basic setup define some buttons that
	// are not needed in a basic editor. They are removed here.
	config.removeButtons = 'Cut,Copy,Paste,Undo,Redo,Anchor,Strike,Subscript,Superscript';

    // Dialog windows are also simplified.
	config.removeDialogTabs = 'link:advanced';
    config.extraPlugins = 'dynamic,sourcearea';
    config.allowedContent = true;
};

CKEDITOR.on( 'instanceReady', function( ev )
{
    var writer = ev.editor.dataProcessor.writer;
    // The character sequence to use for every indentation step.
    writer.indentationChars = '    ';

    var dtd = CKEDITOR.dtd;
    // Elements taken as an example are: block-level elements (div or p), list items (li, dd), and table elements (td, tbody).
    for ( var e in CKEDITOR.tools.extend( {}, dtd.$block, dtd.$listItem, dtd.$tableContent ) )
    {
        ev.editor.dataProcessor.writer.setRules( e, {
            // Indicates that an element creates indentation on line breaks that it contains.
            indent : false,
            // Inserts a line break before a tag.
            breakBeforeOpen : true,
            // Inserts a line break after a tag.
            breakAfterOpen : false,
            // Inserts a line break before the closing tag.
            breakBeforeClose : false,
            // Inserts a line break after the closing tag.
            breakAfterClose : true
        });
    }

    for ( var e in CKEDITOR.tools.extend( {}, dtd.$list, dtd.$listItem, dtd.$tableContent ) )
    {
        ev.editor.dataProcessor.writer.setRules( e, {
            indent : true,
        });
    }

    // You can also apply the rules to a single element.
    ev.editor.dataProcessor.writer.setRules( 'table',
        {
            indent : true
        });

    ev.editor.dataProcessor.writer.setRules( 'form',
        {
            indent : true
        });
});