//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class frontpage_hook_FriendlyUrl extends _HOOK_CLASS_
{
	/**
	 * Get FURL Definition
	 *
	 * @param	bool	$revert	If TRUE, ignores all customisations and reloads from json
	 * @return	array
	 */
	public static function furlDefinition( $revert=FALSE )
	{
		return array_merge( parent::furlDefinition( $revert ), array( 'content_fpage_path' => static::buildFurlDefinition( 'app=frontpage&module=fpages&controller=fpage', 'app=frontpage&module=fpages&controller=fpage', NULL, FALSE, NULL, FALSE, 'IPS\frontpage\Fpages\Router' ) ) );
	}
	
	/**
	 * Create a friendly URL from a full URL, working out friendly URL data
	 *
	 * This is overridden so that when we are examing a raw URL (such as from \IPS\Http\Url::createFromString()), Fpages
	 * can appropriately claim the URL as belonging to it
	 *
	 * @param	array		$components			An array of components as returned by componentsFromUrlString()
	 * @param	string		$potentialFurl		The potential FURL slug (e.g. "topic/1-test")
	 * @return	\IPS\Http\Url\Friendly|NULL
	 */
	public static function createFriendlyUrlFromComponents( $components, $potentialFurl )
	{		
		/* If the normal URL handling has it, or this is the root fpage, use normal handling, unless FrontPage is the default app, in which case we'll fallback to it */
		if ( $return = parent::createFriendlyUrlFromComponents( $components, $potentialFurl ) or !$potentialFurl )
		{
			if ( !\IPS\Application::load('frontpage')->default OR $potentialFurl )
			{
				return $return;
			}
		}
				
		/* Try to find a fpage */
		try
		{
			try
			{
				$fpage = \IPS\frontpage\Fpages\Fpage::loadFromPath( $potentialFurl );
			}
			catch( \Exception $e )
			{
				/* Try from furl */
				try
				{
					$fpage = \IPS\frontpage\Fpages\Fpage::load( \IPS\Db::i()->select( 'store_current_id', 'frontpage_url_store', array( 'store_type=? and store_path=?', 'fpage', $potentialFurl ) )->first() );
				}
				catch( \UnderflowException $e )
				{
					throw new \OutOfRangeException;
				}
			}

			return static::createFromComponents( $components[ static::COMPONENT_HOST ], $components[ static::COMPONENT_SCHEME ], $components[ static::COMPONENT_PATH ], $components[ static::COMPONENT_QUERY ], $components[ static::COMPONENT_PORT ], $components[ static::COMPONENT_USERNAME ], $components[ static::COMPONENT_PASSWORD ], $components[ static::COMPONENT_FRAGMENT ] )
			->setFriendlyUrlData( 'content_fpage_path', array( $potentialFurl ), array( 'path' => $potentialFurl ), $potentialFurl );
		}
		/* Couldn't find one? Don't accept responsibility */
		catch ( \OutOfRangeException $e )
		{
			return $return;
		}
		/* The table may not yet exist if we're using the parser in an upgrade */
		catch ( \Exception $e )
		{
			if( $e->getCode() == 1146 )
			{
				return $return;
			}

			throw $e;
		}
	}
	
	/**
	 * Create a friendly URL from a query string with known friendly URL data
	 *
	 * @param	string			$queryString	The query string
	 * @param	string			$seoTemplate	The key for making this a friendly URL
	 * @param	string|array	$seoTitles		The title(s) needed for the friendly URL
	 * @param	int				$protocol		Protocol (one of the PROTOCOL_* constants)
	 * @return	\IPS\Http\Url\Friendly
	 * @throws	\IPS\Http\Url\Exception
	 */
	public static function friendlyUrlFromQueryString( $queryString, $seoTemplate, $seoTitles, $protocol )
	{
		if ( $seoTemplate === 'content_fpage_path' )
		{
			/* Get the friendly URL component */
			$friendlyUrlComponent = static::buildFriendlyUrlComponentFromData( $queryString, $seoTemplate, $seoTitles );
			
			/* Return */
			return static::friendlyUrlFromComponent( $protocol, $friendlyUrlComponent, $queryString )->setFriendlyUrlData( $seoTemplate, $seoTitles, array( 'path' => $friendlyUrlComponent ), $friendlyUrlComponent );
		}
		
		return parent::friendlyUrlFromQueryString( $queryString, $seoTemplate, $seoTitles, $protocol );
	}
	
	/**
	 * Set friendly URL data
	 *
	 * This is overriden so when we are creating a friendly URL with known data (such as from \IPS\Http\Url::internal()),
	 * Fpages can set the data for it's URLs properly
	 *
	 * @param	string			$seoTemplate			The key for making this a friendly URL
	 * @param	string|array	$seoTitles				The title(s) needed for the friendly URL
	 * @param	array			$matchedParams			The values for hidden query string properties
	 * @param	string			$friendlyUrlComponent	The friendly URL component, which may be for the path or the query string (e.g. "topic/1-test")
	 * @return	\IPS\Http\Url\Friendly
	 * @throws	\IPS\Http\Url\Exception
	 */
	protected function setFriendlyUrlData( $seoTemplate, $seoTitles, $matchedParams=array(), $friendlyUrlComponent )
	{		
		if ( $seoTemplate === 'content_fpage_path' )
		{
			/* If we want to use a gateway script, adjust accordingly */
			if ( \IPS\Settings::i()->frontpage_use_different_gateway )
			{
				$baseUrl = static::baseUrl( $this->data[ static::COMPONENT_SCHEME ] );
				
				if ( mb_substr( $this->url, 0, mb_strlen( $baseUrl ) ) === $baseUrl ) // Only if it isn't already, such as from |IPS\Request::i()->url()
				{
					$this->url = \IPS\Settings::i()->frontpage_root_fpage_url . mb_substr( $this->url, mb_strlen( $baseUrl ) );
					$this->data = static::componentsFromUrlString( $this->url );
					$this->queryString = $this->data['query'];
					$this->data['query'] = static::convertQueryAsArrayToString( $this->queryString );
				}
			}
			
			/* Set basic properties */
			$this->seoTemplate = 'content_fpage_path';
			$this->seoTitles = \is_string( $seoTitles ) ? array( $seoTitles ) : $seoTitles;
			$this->friendlyUrlComponent = $friendlyUrlComponent;
			$this->seoPagination = true;
			
			/* Set hidden query string */
			$this->hiddenQueryString = array( 'app' => 'frontpage', 'module' => 'fpages', 'controller' => 'fpage' ) + $matchedParams;
			
			/* Return */
			return $this;
		}
		
		return parent::setFriendlyUrlData( $seoTemplate, $seoTitles, $matchedParams, $friendlyUrlComponent );
	}
	
	/**
	 * Get friendly URL data from a query string and SEO template
	 *
	 * This is overriden so when we are creating a friendly URL with known data (such as from \IPS\Http\Url::internal()),
	 * Fpages can set the data for it's URLs properly
	 * 
	 * @param	string			$queryString	The query string - is passed by reference and any parts used are removed, which can be used to detect extraneous parts
	 * @param	string			$seoTemplate	The key for making this a friendly URL
	 * @param	string|array	$seoTitles		The title(s) needed for the friendly URL
	 * @return	string
	 * @throws	\IPS\Http\Url\Exception
	 */
	public static function buildFriendlyUrlComponentFromData( &$queryString, $seoTemplate, $seoTitles )
	{
		if ( $seoTemplate === 'content_fpage_path' )
		{
			parse_str( $queryString, $queryString );
			unset( $queryString['app'] );
			unset( $queryString['module'] );
			unset( $queryString['controller'] );
			unset( $queryString['fpage'] );
			
			$return = $queryString['path'];
			unset( $queryString['path'] );
			
			return $return;
		}
		
		return parent::buildFriendlyUrlComponentFromData( $queryString, $seoTemplate, $seoTitles );
	}
	
}