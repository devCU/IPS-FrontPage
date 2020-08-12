<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief		Nav Menu Model
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4.10 FINAL
 * @subpackage	FrontPage
 * @version     1.0.5 Stable
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     12 AUG 2020
 *
 *                    GNU General Public License v3.0
 *    This program is free software: you can redistribute it and/or modify       
 *    it under the terms of the GNU General Public License as published by       
 *    the Free Software Foundation, either version 3 of the License, or          
 *    (at your option) any later version.                                        
 *                                                                               
 *    This program is distributed in the hope that it will be useful,            
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of             
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *                                                                               
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see http://www.gnu.org/licenses/
 */

namespace IPS\frontpage\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Key/Value input class for social profile links
 */
class _NavMenu extends \IPS\Helpers\Form\KeyValue
{
	/**
	 * @brief	Default Options
	 * @see		\IPS\Helpers\Form\Date::$defaultOptions
	 * @code
	 	$defaultOptions = array(
	 		'start'			=> array( ... ),
	 		'end'			=> array( ... ),
	 	);
	 * @endcode
	 */
	protected $defaultOptions = array(
		'key'		=> array(
		),
		'value'		=> array(
		),
	);

	/**
	 * @brief	Key Object
	 */
	public $keyField = NULL;
	
	/**
	 * @brief	Value Object
	 */
	public $valueField = NULL;
	
	/**
	 * Constructor
	 * Creates the two date objects
	 *
	 * @param	string		$name			Form helper name
	 * @param	mixed		$defaultValue	Default value for the helper
	 * @param	bool		$required		Helper is required (TRUE) or not (FALSE)
	 * @param	array		$options		Options for the helper instance
	 * @see		\IPS\Helpers\Form\Abstract::__construct
	 * @return	void
	 */
	public function __construct( $name, $defaultValue=NULL, $required=FALSE, $options=array() )
	{
		$options = array_merge( $this->defaultOptions, $options );

		$options = $this->addNavMenu( $options );

		parent::__construct( $name, $defaultValue, $required, $options );
		
		$this->keyField = new \IPS\Helpers\Form\Text( "{$name}[key]", isset( $defaultValue['key'] ) ? $defaultValue['key'] : NULL, FALSE, isset( $options['key'] ) ? $options['key'] : array() );
		$this->valueField = new \IPS\Helpers\Form\Select( "{$name}[value]", isset( $defaultValue['value'] ) ? $defaultValue['value'] : NULL, FALSE, isset( $options['value'] ) ? $options['value'] : array() );
	}

	/**
	 * Add menu items to the options array
	 *
	 * @note	Abstracted so third parties can extend as needed
	 * @param	array 	$options	Options array
	 * @return	array
	 */
	protected function addNavMenu( $options )
	{
		$options['value']['options'] = array(
			'ABOUT'		=> "sitelink_about",
			'FEATURES'		=> "sitelink_features",
			'CLIENTS'		=> "sitelink_clients",
			'HOME'		=> "sitelink_home",
			'MEMBERS'		=> "sitelink_members",
			'PRICING'		=> "sitelink_pricing",
			'SERVICES'		=> "sitelink_services",
			'SUPPORT'		=> "sitelink_support",
			'TEAM'		=> "sitelink_team",
			'TRENDING'		=> "sitelink_trending",
			'TESTIMONIALS'		=> "sitelink_testimonials",
		);

		return $options;
	}
	
	/**
	 * Format Value
	 *
	 * @return	array
	 */
	public function formatValue()
	{
		return array(
			'key'	=> $this->keyField->formatValue(),
			'value'	=> $this->valueField->formatValue()
		);
	}
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		return \IPS\Theme::i()->getTemplate( 'forms', 'core', 'admin' )->socialProfiles( $this->keyField->html(), $this->valueField->html() );
	}
	
	/**
	 * Validate
	 *
	 * @throws	\InvalidArgumentException
	 * @throws	\LengthException
	 * @return	TRUE
	 */
	public function validate()
	{
		$this->keyField->validate();
		$this->valueField->validate();
		
		if( $this->customValidationCode !== NULL )
		{
			$validationCode = $this->customValidationCode;
			$validationCode( $this->value );
		}
	}
}