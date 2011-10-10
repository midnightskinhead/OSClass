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
     * 
     */
    class Widget extends DAO
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
            $this->set_table_name('t_widget') ;
            $this->set_primary_key('pk_i_id') ;
            $this->set_fields( array('pk_i_id','s_description','s_location','e_kind','s_content') ) ;
        }

        /**
         *
         * @param type $location
         * @return array
         */
        function findByLocation($location)
        {
            $this->dao->select('*') ;
            $this->dao->from($this->table_name) ;
            $this->dao->where('s_location', $location) ;
            $result = $this->dao->get() ;
            return $result->result();
        }

    }

?>