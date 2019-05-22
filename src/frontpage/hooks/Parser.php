//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class frontpage_hook_Parser extends _HOOK_CLASS_
{
	/**
	 * Get URL bases (whout schema) that we'll allow iframes from
	 *
	 * @return	array
	 */
	protected static function allowedIFrameBases()
	{
		$return = parent::allowedIFrameBases();
		
		/* If the Frontpage root URL is not inside the IPS4 directory, then embeds will fails as the src will not be allowed */
		if ( \IPS\Settings::i()->frontpage_root_fpage_url )
		{
			$fpages = iterator_to_array( \IPS\Db::i()->select( 'database_fpage_id', 'frontpage_databases', array( 'database_fpage_id > 0' ) ) );

			foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'frontpage_fpages', array( \IPS\Db::i()->in( 'fpage_id', $fpages ) ) ), 'IPS\frontpage\Fpages\Fpage' ) as $fpage )
			{
				$return[] = str_replace( array( 'http://', 'https://' ), '', $fpage->url() );
			}
		}

		return $return;
	}
}