<?php

namespace simialbi\yii2\schemaorg\models;

/**
 * Model for SendAction
 *
 * @package simialbi\yii2\schemaorg\models
 * @see http://schema.org/SendAction
 */
class SendAction extends TransferAction {
	/**
	* @var DeliveryMethod A sub property of instrument. The method of delivery.
	*/
	public $deliveryMethod;

	/**
	* @var Audience|Organization|Person A sub property of participant. The participant who is at the receiving end of the action.
	*/
	public $recipient;

}