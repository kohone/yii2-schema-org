<?php

namespace simialbi\yii2\schemaorg\models;

/**
 * Model for SportsTeam
 *
 * @package simialbi\yii2\schemaorg\models
 * @see http://schema.org/SportsTeam
 */
class SportsTeam extends SportsOrganization {
	/**
	* @var Person A person that acts as performing member of a sports team; a player as opposed to a coach.
	*/
	public $athlete;

	/**
	* @var Person A person that acts in a coaching role for a sports team.
	*/
	public $coach;

}