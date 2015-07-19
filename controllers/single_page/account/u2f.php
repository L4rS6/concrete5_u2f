<?php

namespace Concrete\Package\U2f\Controller\SinglePage\Account;

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

defined('C5_EXECUTE') or die(_("Access Denied."));

use \Concrete\Core\Page\Controller\AccountPageController;
use UserInfo;
use Exception;
use User;
use UserAttributeKey;
use \Concrete\Package\U2f\Src\Client;
use Loader;

class U2f extends AccountPageController {

    public static function getClient() {
        return Client::withHttpAuth(\Config::get('auth.u2f.url'), \Config::get('auth.u2f.username'), \Config::get('auth.u2f.password'));
    }

    public function view() {
        $html = Loader::helper('html');
        $this->addHeaderItem($html->javascript('u2f-api.js', 'u2f'));
        $this->addHeaderItem($html->javascript('bowser.mini.js', 'u2f'));
        $this->addHeaderItem($html->javascript('jquery.js', 'u2f'));
        $this->addHeaderItem($html->javascript('jquery-ui.js', 'u2f'));
        $this->addHeaderItem($html->css('jquery-ui.css'));

        $user = new User();

        if (!$user->isLoggedIn()) {
            throw new Exception(t('Access denied!'));
        }

        $keys = $this->getClient()->list_devices($user->getUserName());
        $this->set('keys', $keys);

        $ui = UserInfo::getByUserName($user->getUserName());
        $this->set('u2f_enabled', $ui->getAttribute('U2F'));

        $this->set('username', $user->getUserName());
    }

    public function save() {
        $this->view();
        $ui = $this->get('profile');
        $aks = UserAttributeKey::getEditableInProfileList();

        if (!$this->error->has()) {
            $ui->saveUserAttributesForm($aks);
            $this->redirect("/account", "save_complete");
        }
    }

    public function u2fRegister() {
        $user = new User();
        if ($user->isLoggedIn()) {
            echo $this->getClient()->register_begin($user->getUserName());
            exit;
        } else {
            throw new Exception('Access denied!');
        }
    }

    public function u2fUnregister() {
        $post = $this->post();

        $user = new User();
        if ($user->isLoggedIn()) {
            $this->getClient()->unregister($user->getUserName(), $post['handle']);
        } else {
            throw new Exception('Access denied!');
        }

        $this->view();
    }

    public function u2fRegisterComplete() {
        $post = $this->post();

        $user = new User();
        if ($user->isLoggedIn()) {
            try {
                $this->getClient()->register_complete($user->getUserName(), $post['register']);
            } catch (Exception $ex) {
                throw new Exception('Register failed');
            }

            $this->view();
        } else {
            throw new Exception('Access denied!');
        }
    }

}
