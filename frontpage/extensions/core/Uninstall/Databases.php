<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief		File Storage Extension: Records
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4+
 * @subpackage	FrontPage
 * @version     1.0.0 RC
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     22 MAY 2019
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

namespace IPS\frontpage\extensions\core\Uninstall;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * Remove custom databases
 */
class _Databases
{
    /**
     * Constructor
     *
     *
     */
    public function __construct()
    {
    }

    /**
     * Uninstall custom databases
     *
     * @return void
     */
    public function preUninstall( )
    {
        if ( \IPS\Db::i()->checkForTable( 'frontpage_databases' ) )
        {
            foreach ( \IPS\Db::i()->select( '*', 'frontpage_databases') as $db )
            {
                /* The content router only returns databases linked to fpages. In theory, you may have linked a database and then removed it,
                    so the method to remove all app content from the search index fails, so we need to account for that here: */
                \IPS\Content\Search\Index::i()->removeClassFromSearchIndex( 'IPS\frontpage\Records' . $db['database_id'] );
            }
        }
    }

    /**
     * Uninstall custom databases
     *
     * @return void
     */
    public function postUninstall()
    {
        /* frontpage_databases has been removed */
        $tables = array();
        try
        {
            $databaseTables = \IPS\Db::i()->query("SHOW TABLES LIKE '" . \IPS\Db::i()->prefix . "frontpage_custom_database_%'" )->fetch_assoc();
            if ( $databaseTables )
            {
                foreach( $databaseTables as $row )
                {
                    if( \is_array( $row ) )
                    {
                        $tables[] = array_pop($row);
                    }
                    else
                    {
                        $tables[] = $row;
                    }
                }
            }

        }
        catch( \IPS\Db\Exception $ex ) { }

        foreach( $tables as $table )
        {
            if ( \IPS\Db::i()->checkForTable( $table ) )
            {
                \IPS\Db::i()->dropTable( $table );
            }
        }

        if ( isset( \IPS\Data\Store::i()->frontpage_menu ) )
        {
            unset( \IPS\Data\Store::i()->frontpage_menu );
        }
    }
}