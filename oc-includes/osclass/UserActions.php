<?php if ( ! defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

    /**
     * OSClass – software for creating and publishing online classified advertising platforms
     *
     * Copyright (C) 2010 OSCLASS
     *
     * This program is free software: you can redistribute it and/or modify it under the terms
     * of the GNU Affero General Public License as published by the Free Software Foundation,
     * either version 3 of the License, or (at your option) any later version.
     *
     * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
     * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
     * See the GNU Affero General Public License for more details.
     *
     * You should have received a copy of the GNU Affero General Public
     * License along with this program. If not, see <http://www.gnu.org/licenses/>.
     */

     Class UserActions {
         var $is_admin ;
         var $manager ;


         function __construct($is_admin) {
             $this->is_admin = $is_admin ;
             $this->manager = User::newInstance() ;
         }

         //add...
         function add()
         {
            if ((osc_recaptcha_private_key() != '') && !$this->is_admin) {
                if(!$this->recaptcha()) {
                    return 4;
                }
            }

            $input = $this->prepareData(true) ;

            if(!osc_validate_email($input['s_email'])) {
                return 5;
            }
            
            $email_taken = $this->manager->findByEmail($input['s_email']) ;
            if($email_taken == null) {
                $this->manager->insert($input) ;
                $userId = $this->manager->getConnection()->get_last_id() ;

                if ( is_array( Params::getParam('s_info') ) ) {
                    foreach (Params::getParam('s_info') as $key => $value) {
                        $this->manager->updateDescription($userId, $key, $value) ;
                    }
                }
                Log::newInstance()->insertLog('user', 'add', $userId, $input['s_email'], $this->is_admin?'admin':'user', $this->is_admin?osc_logged_admin_id():$userId);

                osc_run_hook('user_register_completed', $userId) ;

                if( osc_user_validation_enabled() && !$this->is_admin ) {
                    
                    $user = $this->manager->findByPrimaryKey($userId) ;

                    $mPages = new Page() ;
                    $locale = osc_current_user_locale() ;

                    $aPage = $mPages->findByInternalName('email_user_validation') ;

                    $content = array() ;
                    if(isset($aPage['locale'][$locale]['s_title'])) {
                        $content = $aPage['locale'][$locale] ;
                    } else {
                        $content = current($aPage['locale']) ;
                    }
                    
                    if (!is_null($content)) {
                        $validation_url = osc_user_activate_url($user['pk_i_id'], $input['s_secret']);
                        $words   = array();
                        $words[] = array('{USER_NAME}', '{USER_EMAIL}', '{WEB_URL}', '{VALIDATION_LINK}', '{VALIDATION_URL}') ;
                        $words[] = array($user['s_name'], $user['s_email'], '<a href="'.osc_base_url().'" >'.osc_base_url().'</a>', '<a href="' . $validation_url . '" >' . $validation_url . '</a>', '<a href="' . $validation_url . '" >' . $validation_url . '</a>') ;
                        $title = osc_mailBeauty($content['s_title'], $words) ;
                        $body = osc_mailBeauty($content['s_text'], $words) ;

                        $emailParams = array('subject'  => $title
                                             ,'to'       => Params::getParam('s_email')
                                             ,'to_name'  => Params::getParam('s_name')
                                             ,'body'     => $body
                                             ,'alt_body' => $body
                        ) ;
                        osc_sendMail($emailParams) ;
                    }
                    
                    return 1 ;
                    
                } else {
                    User::newInstance()->update(
                                    array('b_active' => '1')
                                    ,array('pk_i_id' => $userId)
                    );
                    return 2 ;
                }
                return 0 ;
            } else {
                return 3 ;
            }
         }

        //edit...
        function edit($userId)
        {
            $input = $this->prepareData(false) ;
            $this->manager->update($input, array('pk_i_id' => $userId)) ;
            if($this->is_admin) {
                Item::newInstance()->update(array('s_contact_name' => $input['s_name'], 's_contact_email' => $input['s_email']), array('fk_i_user_id' => $userId));
                ItemComment::newInstance()->update(array('s_author_name' => $input['s_name'], 's_author_email' => $input['s_email']), array('fk_i_user_id' => $userId));
                Alerts::newInstance()->update(array('s_email' => $input['s_email']), array('fk_i_user_id' => $userId));
                Log::newInstance()->insertLog('user', 'edit', $userId, $input['s_email'], $this->is_admin?'admin':'user', $this->is_admin?osc_logged_admin_id():osc_logged_user_id());
            } else { 
                Item::newInstance()->update(array('s_contact_name' => $input['s_name']), array('fk_i_user_id' => $userId));
                ItemComment::newInstance()->update(array('s_author_name' => $input['s_name']), array('fk_i_user_id' => $userId));
                $user = $this->manager->findByPrimaryKey($userId);
                Log::newInstance()->insertLog('user', 'edit', $userId, $user['s_email'], $this->is_admin?'admin':'user', $this->is_admin?osc_logged_admin_id():osc_logged_user_id());
        }

            Session::newInstance()->_set('userName', $input['s_name']);
            $phone = ($input['s_phone_mobile'])? $input['s_phone_mobile'] : $input['s_phone_land'];
            Session::newInstance()->_set('userPhone', $phone);

            if ( is_array( Params::getParam('s_info') ) ) {
                foreach (Params::getParam('s_info') as $key => $value) {
                    $this->manager->updateDescription($userId, $key, $value) ;
                }
            }
            
            if ($this->is_admin) {
                $iUpdated = 0;
                if(Params::getParam("b_enabled") != '' && Params::getParam("b_enabled")==1) {
                    $iUpdated += $this->manager->update(array('b_enabled' => 1), array('pk_i_id' => $userId)) ;
                } else {
                    $iUpdated += $this->manager->update(array('b_enabled' => 0), array('pk_i_id' => $userId)) ;
                }

                if(Params::getParam("b_active") != '' && Params::getParam("b_active")==1) {
                    $iUpdated += $this->manager->update(array('b_active' => 1), array('pk_i_id' => $userId)) ;
                } else {
                    $iUpdated += $this->manager->update(array('b_active' => 0), array('pk_i_id' => $userId)) ;
                }

                if($iUpdated > 0) {
                    return 2;
                }
            }

            return 0;
        }
        
        function recover_password() {
            $user = User::newInstance()->findByEmail( Params::getParam('s_email') ) ;
            Session::newInstance()->_set('recover_time', time());

            if ((osc_recaptcha_private_key() != '') && Params::existParam("recaptcha_challenge_field")) {
                if(!$this->recaptcha()) {
                    return 2; // BREAK THE PROCESS, THE RECAPTCHA IS WRONG
                }
            }
            
            if( !$user || $user['b_enabled']==0) {
                return 1;
            }

            $code = osc_genRandomPassword(30);
            $date = date('Y-m-d H:i:s');
            $date2 = date('Y-m-d H:i:').'00';
            User::newInstance()->update(
                array('s_pass_code' => $code, 's_pass_date' => $date, 's_pass_ip' => $_SERVER['REMOTE_ADDR']),
                array('pk_i_id' => $user['pk_i_id'])
            );

            $password_url = osc_forgot_user_password_confirm_url($user['pk_i_id'], $code);
            $aPage = Page::newInstance()->findByInternalName('email_user_forgot_password');
            
            $content = array();
            $locale = osc_current_user_locale() ;
            if(isset($aPage['locale'][$locale]['s_title'])) {
                $content = $aPage['locale'][$locale];
            } else {
                $content = current($aPage['locale']);
            }

            if (!is_null($content)) {
                $words   = array();
                $words[] = array('{USER_NAME}', '{USER_EMAIL}', '{WEB_URL}', '{WEB_TITLE}', '{IP_ADDRESS}',
                                 '{PASSWORD_LINK}', '{PASSWORD_URL}', '{DATE_TIME}');
                $words[] = array($user['s_name'], $user['s_email'], '<a href="'.osc_base_url().'" >'.osc_base_url().'</a>', osc_page_title(),
                                 $_SERVER['REMOTE_ADDR'], '<a href="' . $password_url . '">' . $password_url . '</a>', $password_url, $date2);
                $title = osc_mailBeauty($content['s_title'], $words);
                $body = osc_mailBeauty($content['s_text'], $words);

                $emailParams = array('subject'  => $title,
                                     'to'       => $user['s_email'],
                                     'to_name'  => $user['s_name'],
                                     'body'     => $body,
                                     'alt_body' => $body);
                osc_sendMail($emailParams);
            }

            return 0;
        }
        

        public function recaptcha()
        {
            require_once osc_lib_path() . 'recaptchalib.php';
            if ( Params::getParam("recaptcha_challenge_field") != '') {
                $resp = recaptcha_check_answer (osc_recaptcha_private_key()
                                               ,$_SERVER["REMOTE_ADDR"]
                                               ,Params::getParam("recaptcha_challenge_field")
                                               ,Params::getParam("recaptcha_response_field"));

                return $resp->is_valid;
            }

            return false;
        }

        //   
        function prepareData($is_add)
        {
            $input = array() ;
            
            if ($is_add) {
                $input['s_secret'] = osc_genRandomPassword() ;
                $input['dt_reg_date'] = date('Y-m-d H:i:s') ;
            } else {
                $input['dt_mod_date'] = date('Y-m-d H:i:s') ;
            }

            //only for administration, in the public website this two params are edited separately
            if ($this->is_admin || $is_add) {
                $input['s_email'] = Params::getParam('s_email') ;

                if( Params::getParam('s_password') != Params::getParam('s_password2') ) {
                    return 1 ;
                }

                //if we want to change the password
                if( Params::getParam('s_password') != '') {
                    $input['s_password'] = sha1( Params::getParam('s_password') ) ;
                }
            }

            $input['s_name'] = Params::getParam('s_name') ;
            $input['s_website'] = Params::getParam('s_website') ;
            $input['s_phone_land'] = Params::getParam('s_phone_land') ;
            $input['s_phone_mobile'] = Params::getParam('s_phone_mobile') ;

            //locations...
            $country = Country::newInstance()->findByCode( Params::getParam('countryId') ) ;
            if(count($country) > 0) {
                $countryId = $country['pk_c_code'] ;
                $countryName = $country['s_name'] ;
            } else {
                $countryId = null ;
                $countryName = null ;
            }

            if( intval( Params::getParam('regionId') ) ) {
                $region = Region::newInstance()->findByPrimaryKey( Params::getParam('regionId') ) ;
                if( count($region) > 0 ) {
                    $regionId = $region['pk_i_id'] ;
                    $regionName = $region['s_name'] ;
                }
            } else {
                $regionId = null ;
                $regionName = Params::getParam('region') ;
            }

            if( intval( Params::getParam('cityId') ) ) {
                $city = City::newInstance()->findByPrimaryKey( Params::getParam('cityId') ) ;
                if( count($city) > 0 ) {
                    $cityId = $city['pk_i_id'] ;
                    $cityName = $city['s_name'] ;
                }
            } else {
                $cityId = null ;
                $cityName = Params::getParam('city') ;
            }

            $input['fk_c_country_code'] = $countryId ;
            $input['s_country'] = $countryName ;
            $input['fk_i_region_id'] = $regionId ;
            $input['s_region'] = $regionName ;
            $input['fk_i_city_id'] = $cityId ;
            $input['s_city'] = $cityName ;
            $input['s_city_area'] = Params::getParam('cityArea') ;
            $input['s_address'] = Params::getParam('address') ;
            $input['b_company'] = (Params::getParam('b_company') != '' && Params::getParam('b_company') != 0) ? 1 : 0;
            
            return($input) ;
        }
        
        
        public function activate($user_id) {
            $user = $this->manager->findByPrimaryKey($user_id);
            if($user) {
                $this->manager->update(array('b_active' => 1), array('pk_i_id' => $user_id));
                Log::newInstance()->insertLog('user', 'activate', $user_id, $user['s_email'], $this->is_admin?'admin':'user', $this->is_admin?osc_logged_admin_id():osc_logged_user_id());
                if($user['b_enabled']==1) {
                     $mItem = new ItemActions(true);
                     $items = Item::newInstance()->findByUserID($user_id);
                     foreach($items as $item) {
                         $mItem->enable($item['pk_i_id']);
                     }
                }
                return true;
            } else {
                return false;
            }
        }
        
        public function deactivate($user_id) {
            $user = $this->manager->findByPrimaryKey($user_id);
            if($user) {
                $this->manager->update(array('b_active' => 0), array('pk_i_id' => $user_id));
                Log::newInstance()->insertLog('user', 'deactivate', $user_id, $user['s_email'], $this->is_admin?'admin':'user', $this->is_admin?osc_logged_admin_id():osc_logged_user_id());
                if($user['b_enabled']==1) {
                     $mItem = new ItemActions(true);
                     $items = Item::newInstance()->findByUserID($user_id);
                     foreach($items as $item) {
                         $mItem->disable($item['pk_i_id']);
                     }
                }
                return true;
            } else {
                return false;
            }
        }
        
        public function enable($user_id) {
            $user = $this->manager->findByPrimaryKey($user_id);
            if($user) {
                $this->manager->update(array('b_enabled' => 1), array('pk_i_id' => $user_id));
                Log::newInstance()->insertLog('user', 'enable', $user_id, $user['s_email'], $this->is_admin?'admin':'user', $this->is_admin?osc_logged_admin_id():osc_logged_user_id());
                if($user['b_active']==1) {
                     $mItem = new ItemActions(true);
                     $items = Item::newInstance()->findByUserID($user_id);
                     foreach($items as $item) {
                         $mItem->enable($item['pk_i_id']);
                     }
                }
                return true;
            } else {
                return false;
            }
        }
        
        public function disable($user_id) {
            $user = $this->manager->findByPrimaryKey($user_id);
            if($user) {
                $this->manager->update(array('b_enabled' => 0), array('pk_i_id' => $user_id));
                Log::newInstance()->insertLog('user', 'disable', $user_id, $user['s_email'], $this->is_admin?'admin':'user', $this->is_admin?osc_logged_admin_id():osc_logged_user_id());
                if($user['b_active']==1) {
                     $mItem = new ItemActions(true);
                     $items = Item::newInstance()->findByUserID($user_id);
                     foreach($items as $item) {
                         $mItem->disable($item['pk_i_id']);
                     }
                }
                return true;
            } else {
                return false;
            }
        }

        public function bootstrap_login($user_id) {
            $user = User::newInstance()->findByPrimaryKey( $user_id ) ;
            if (!$user) {
                return 0;
            } else if(!$user['b_active']) {
                return 1;
            } else if(!$user['b_enabled']) {
                return 2;
            } else {
                //we are logged in... let's go!
                Session::newInstance()->_set('userId', $user['pk_i_id']) ;
                Session::newInstance()->_set('userName', $user['s_name']) ;
                Session::newInstance()->_set('userEmail', $user['s_email']) ;
                $phone = ($user['s_phone_mobile']) ? $user['s_phone_mobile'] : $user['s_phone_land'];
                Session::newInstance()->_set('userPhone', $phone) ;
                return 3;
            }

         }
        
     }
     

?>