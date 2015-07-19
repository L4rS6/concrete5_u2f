<?php

namespace Concrete\Package\U2f\Authentication\U2f;

//namespace Application\Authentication\Concrete;

/*
 *
 * Copyright (C) 2015 Lars Wagner
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 */

defined('C5_EXECUTE') or die('Access Denied');

use \Concrete\Package\U2f\Src\Client;
use Config;
use Exception;
use Loader;
use User;
use UserInfo;

class Controller extends \Concrete\Authentication\Concrete\Controller {

    // Override
    public function getHandle() {
        return 'u2f';
    }

    // Override
    public function getAuthenticationTypeIconHTML() {
        return '<i class="fa fa-gears"></i>';
    }

    // Override
    public function view() {
        $html = Loader::helper('html');

        $this->addHeaderItem($html->javascript('jquery.js'));
        $this->addHeaderItem($html->javascript('jquery-ui.js'));
        $this->addHeaderItem($html->javascript('u2f-api.js', 'u2f'));
        $this->addHeaderItem($html->javascript('bowser.mini.js', 'u2f'));
        $this->addHeaderItem($html->css('jquery-ui.css'));
    }

    // Override
    public function saveAuthenticationType($args) {
        \Config::save('auth.u2f.url', $args['url']);
        \Config::save('auth.u2f.username', $args['username']);
        \Config::save('auth.u2f.password', $args['password']);
    }

    // Override
    public function authenticate() {
        $post = $this->post();

        if (!isset($post['uName']) || !isset($post['uPassword'])) {
            throw new Exception(t('Please provide both username and password.'));
        }

        $uName = $post['uName'];
        $uPassword = $post['uPassword'];
        $uAuthenticate = $post['authenticate'];
        $uU2fCheckbox = $post['u2fCheckbox'];

        $ui = UserInfo::getByUserName($uName);
        if (!is_null($ui)) {
            $u2fRequired = $ui->getAttribute('U2F');
        }

        if ($u2fRequired && isset($uU2fCheckbox)) {
            try {
                $this->getClient()->auth_complete($uName, $uAuthenticate);
            } catch (\Exception $ex) {
                throw new \Exception(t('Invalid username or password.'));
            }
        } else if ($u2fRequired || isset($uU2fCheckbox)) {
            throw new \Exception(t('Invalid username or password.'));
        }

        $user = new User($uName, $uPassword);
        if (!is_object($user) || !($user instanceof User) || $user->isError()) {
            switch ($user->getError()) {
                case USER_SESSION_EXPIRED:
                    throw new \Exception(t('Your session has expired. Please sign in again.'));
                    break;
                case USER_NON_VALIDATED:
                    throw new \Exception(
                    t(
                            'This account has not yet been validated. Please check the email associated with this account and follow the link it contains.'));
                    break;
                case USER_INVALID:
                    if (Config::get('concrete.user.registration.email_registration')) {
                        throw new \Exception(t('Invalid email address or password.'));
                    } else {
                        throw new \Exception(t('Invalid username or password.'));
                    }
                    break;
                case USER_INACTIVE:
                    throw new \Exception(t('This user is inactive. Please contact us regarding this account.'));
                    break;
            }
        }

        if ($post['uMaintainLogin']) {
            $user->setAuthTypeCookie('concrete');
        }

        return $user;
    }

    // Override
    public function edit() {
        $this->set('form', \Loader::helper('form'));

        $this->set('url', \Config::get('auth.u2f.url'));
        $this->set('username', \Config::get('auth.u2f.username'));
        $this->set('password', \Config::get('auth.u2f.password'));
        
        $this->view();
    }

    public static function getClient() {
        return Client::withHttpAuth(\Config::get('auth.u2f.url'), \Config::get('auth.u2f.username'), \Config::get('auth.u2f.password'));
    }

}
