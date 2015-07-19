<?php

namespace Concrete\Package\U2f;

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

use Package;
use AuthenticationType;
use SinglePage;
use Route;
use UserAttributeKey;

class Controller extends Package {

    protected $pkgHandle = 'u2f';
    protected $appVersionRequired = '5.7';
    protected $pkgVersion = '0.9.5';

    public function getPackageName() {
        return t('U2F Authentication');
    }

    public function getPackageDescription() {
        return t('Adds FIDO U2F login functionality');
    }

    public function on_start() {
        Route::register('/u2fclient/u2f_authenticate', 'Concrete\Package\U2f\Controller\U2f\U2fclient::u2fAuthenticate');
        Route::register('/u2fclient/u2f_register', 'Concrete\Package\U2f\Controller\U2f\U2fclient::u2fRegister');
        Route::register('/u2fclient/u2f_register_complete', 'Concrete\Package\U2f\Controller\U2f\U2fclient::u2fRegisterComplete');
    }

    public function install() {
        $pkg = parent::install();

        SinglePage::add('/account/u2f', $pkg);

        AuthenticationType::add('u2f', 'U2F', 1, $pkg);
        $authenticationType = AuthenticationType::getByHandle('concrete');
        $authenticationType->delete();

        UserAttributeKey::add('boolean', array(
            'akHandle' => 'U2F',
            'akName' => 'Enable U2F',
            'uakProfileDisplay' => true,
            'uakProfileEdit' => true
        ));
    }

    public function uninstall() {
        parent::uninstall();

        $uak = UserAttributeKey::getByHandle('U2F');
        $uak->delete();

        AuthenticationType::add('concrete', 'Standard', 1);
    }

}
