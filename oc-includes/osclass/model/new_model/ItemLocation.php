<?php

    /*
     *      OSCLass – software for creating and publishing online classified
     *                           advertising platforms
     *
     *                        Copyright (C) 2010 OSCLASS
     *
     *       This program is free software: you can redistribute it and/or
     *     modify it under the terms of the GNU Affero General Public License
     *     as published by the Free Software Foundation, either version 3 of
     *            the License, or (at your option) any later version.
     *
     *     This program is distributed in the hope that it will be useful, but
     *         WITHOUT ANY WARRANTY; without even the implied warranty of
     *        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     *             GNU Affero General Public License for more details.
     *
     *      You should have received a copy of the GNU Affero General Public
     * License along with this program.  If not, see <http://www.gnu.org/licenses/>.
     */

    /**
     * ItemLocation DAO
     */
    class ItemLocation extends DAO
    {
        /**
         *
         * @var type 
         */
        private static $instance ;

        public static function newInstance()
        {
            if( !self::$instance instanceof self ) {
                self::$instance = new self ;
            }
            return self::$instance ;
        }

        /**
         * 
         */
        function __construct()
        {
            parent::__construct();
            $this->setTableName('t_item_location') ;
            $this->setPrimaryKey('fk_i_item_id') ;
            $array_fields = array(
                'fk_i_item_id',
                'fk_c_country_code',
                's_country',
                's_address',
                's_zip',
                'fk_i_region_id',
                's_region',
                'fk_i_city_id',
                's_city',
                'fk_i_city_area_id',
                's_city_area',
                'd_coord_lat',
                'd_coord_long'
                );
            $this->setFields($array_fields) ;
        }
    }
?>