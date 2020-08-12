<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief		Youtube input class for Form Builder
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

namespace IPS\frontpage\Fields;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Youtube input class for Form Builder
 */
class _Youtube extends \IPS\Helpers\Form\Text
{
	/**
	 * @brief	Default Options
	 */
	public $childDefaultOptions = array(
		'parameters'  => array()
	);
	
	/**
	 * Constructor
	 *
	 * @param	string			$name					Name
	 * @param	mixed			$defaultValue			Default value
	 * @param	bool|NULL		$required				Required? (NULL for not required, but appears to be so)
	 * @param	array			$options				Type-specific options
	 * @param	callback		$customValidationCode	Custom validation code
	 * @param	string			$prefix					HTML to show before input field
	 * @param	string			$suffix					HTML to show after input field
	 * @param	string			$id						The ID to add to the row
	 * @return	void
	 */
	public function __construct( $name, $defaultValue=NULL, $required=FALSE, $options=array(), $customValidationCode=NULL, $prefix=NULL, $suffix=NULL, $id=NULL )
	{
		$this->childDefaultOptions['placeholder'] = \IPS\Member::loggedIn()->language()->addToStack('field_placeholder_youtube');
		
		/* Call parent constructor */
		parent::__construct( $name, $defaultValue, $required, $options, $customValidationCode, $prefix, $suffix, $id );
		
		$this->formType = 'text';
	}
	
	/**
	 * Get the display value
	 * 
	 * @param	mixed			$value			Stored value from form
	 * @param	\IPS\frontpage\Field	$customField	Custom Field Object
	 * @return	string
	 */
	public static function displayValue( $value, $customField )
	{
		if( !$value )
		{
			return '';
		}
		
		$url = new \IPS\Http\Url( $value );
	
		if ( isset( $url->queryString['v'] ) )
		{
			$url = 'https://www.youtube.com/embed/' . $url->queryString['v'];
		}
		else if ( $url->data['host'] === 'youtu.be' and ! mb_strpos( $url->data['path'], 'embed' ) )
		{
			$url = 'https://www.youtube.com/embed/' . trim( $url->data['path'], '/' );
		}
		else
		{
			$url = $value;
		}
		
		$params = $customField->extra;
		
		if ( ! isset( $params['width'] ) )
		{
			$params['width'] = 640;
		}
		
		if ( ! isset( $params['height'] ) )
		{
			$params['height'] = 390;
		}
		
		$url = \IPS\Http\Url::external( $url )->setQueryString( $params );
		
		return \IPS\Theme::i()->getTemplate( 'records', 'frontpage', 'global' )->youtube( $url, array( 'width' => $params['width'], 'height' => $params['height'] ) );
	}
	
	/**
	 * Validate
	 *
	 * @throws	\InvalidArgumentException
	 * @throws	\DomainException
	 * @return	TRUE
	 */
	public function validate()
	{
		parent::validate();
						
		if ( $this->value )
		{
			/* Check the URL is valid */
			if ( !( $this->value instanceof \IPS\Http\Url ) )
			{
				throw new \InvalidArgumentException('form_url_bad');
			}
			
			/* Check its a valid Youtube URL */
			if ( ! mb_stristr( $this->value->data['host'], 'youtube.' ) and ! mb_stristr( $this->value->data['host'], 'youtu.be' ) )
			{
				throw new \InvalidArgumentException('form_url_bad');
			}
		}
	}
	
	/**
	 * Get Value
	 *
	 * @return	string
	 */
	public function getValue()
	{
		$val = parent::getValue();
		if ( $val and !mb_strpos( $val, '://' ) )
		{
			$val = "http://{$val}";
		}
		
		return $val;
	}
	
	/**
	 * Format Value
	 *
	 * @return	\IPS\Http\Url|string
	 */
	public function formatValue()
	{
		if ( $this->value and !( $this->value instanceof \IPS\Http\Url ) )
		{
			try
			{
				return new \IPS\Http\Url( $this->value );
			}
			catch ( \InvalidArgumentException $e )
			{
				return $this->value;
			}
		}
		
		return $this->value;
	}
}