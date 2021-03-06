<?php

namespace simialbi\yii2\schemaorg\models;

/**
 * Model for NoteDigitalDocument
 *
 * @package simialbi\yii2\schemaorg\models
 * @see http://schema.org/NoteDigitalDocument
 */
class NoteDigitalDocument extends DigitalDocument {
	/**
	* @var DigitalDocumentPermission A permission related to the access to this document (e.g. permission to read or write an electronic document). For a public document, specify a grantee with an Audience with audienceType equal to "public".
	*/
	public $hasDigitalDocumentPermission;

}