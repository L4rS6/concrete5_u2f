<?php

namespace Concrete\Package\U2f\Controller\U2f;

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

use \Concrete\Package\U2f\Src\Client;
use Concrete\Core\Controller\Controller;
use Exception;
use User;

class U2fclient extends Controller {

    public static function getClient() {
        return Client::withHttpAuth(\Config::get('auth.u2f.url'), \Config::get('auth.u2f.username'), \Config::get('auth.u2f.password'));
    }

    public function u2fAuthenticate() {
        $get = $this->get();
        try {
            echo $this->getClient()->auth_begin($get["username"]);
        } catch (Exception $ex) {
            // Return challenge from dummyuser if there is given an unexisting username 
            echo $this->getClient()->auth_begin("dummyuser");
        }
    }

    public function u2fRegister() {
        $user = new User();
        if ($user->isLoggedIn()) {
            echo $this->getClient()->register_begin('dummyuser');
            exit;
        } else {
            throw new Exception('Access denied!');
        }
    }

    public function u2fRegisterComplete() {
        $post = $this->post();
        $user = new User();
        if ($user->isLoggedIn()) {
            try {
                echo $this->getClient()->register_complete('dummyuser', $post['register']);
            } catch (Exception $ex) {
                throw new Exception('Register failed');
            }
        } else {
            throw new Exception('Access denied!');
        }
    }

}
