<?php

namespace simialbi\yii2\schemaorg\models;

/**
 * Model for TransferAction
 *
 * @package simialbi\yii2\schemaorg\models
 * @see http://schema.org/TransferAction
 */
class TransferAction extends Action {
	/**
	* @var Place A sub property of location. The original location of the object or the agent before the action.
	*/
	public $fromLocation;

	/**
	* @var Place A sub property of location. The final location of the object or the agent after the action.
	*/
	public $toLocation;

}