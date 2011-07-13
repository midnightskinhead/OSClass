<?php if ( ! defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

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

    class FieldForm extends Form {

        static public function primary_input_hidden($field = null) {
            if(isset($field['pk_i_id'])) {
                parent::generic_input_hidden("id", $field["pk_i_id"]) ;
            }
        }

        static public function name_input_text($field = null) {
            parent::generic_input_text("s_name", (isset($field) && isset($field["s_name"])) ? $field["s_name"] : "", null, false) ;
            return true ;
        }

        static public function type_select($field = null) {
            ?>
            <select name="field_type" id="field_type">
                <option value="TEXT" <?php if($field['e_type']=="TEXT") { echo 'selected="selected"';};?>>TEXT</option>
                <option value="TEXTAREA" <?php if($field['e_type']=="TEXTAREA") { echo 'selected="selected"';};?>>TEXTAREA</option>
            </select>
            <?php
            return true;
        }
        
        static public function meta($field = null) {
            if($field!=null) {
                echo '<label for="'.$field['s_name'].'">'.$field['s_name'].': </label>';
                if($field['e_type']=="TEXTAREA") {
                    parent::generic_textarea("meta[".$field['pk_i_id']."]",(isset($field) && isset($field["s_value"])) ? $field["s_value"] : "") ;
                } else {
                    parent::generic_input_text("meta[".$field['pk_i_id']."]", (isset($field) && isset($field["s_value"])) ? $field["s_value"] : "", null, false) ;
                }
            }
        }

        static public function meta_fields_input($catId = null, $itemId = null) {
            $fields = Field::newInstance()->findByCategoryItem($catId, $itemId);
            if(count($fields)>0) {
                echo '<ul class="meta_list">';
                foreach($fields as $field) {
                    echo '<li class="meta">';
                        FieldForm::meta($field);
                    echo '</li>';
                }
            }
        }
        
    }

?>