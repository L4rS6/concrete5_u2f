<!--
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
-->

<?php
defined('C5_EXECUTE') or die('Access denied.');
?>

<form id="login-form" method="post"
      action='<?php echo View::url('/login', 'authenticate', $this->getAuthenticationTypeHandle()) ?>'>
    <div class="form-group concrete-login">
        <span><?php echo t('Sign in with a U2F account.') ?> </span>
        <hr>
    </div>
    <div class="form-group">
        <input id="uName" name="uName" class="form-control col-sm-12"
               placeholder="<?php echo Config::get('concrete.user.registration.email_registration') ? t('Email Address') : t('Username') ?>" />
    </div>

    <div class="form-group">
        <label>&nbsp;</label>
        <input id="uPassword" name="uPassword" class="form-control" type="password"
               placeholder="<?php echo t('Password') ?>" />
    </div>

    <div class="form-group">
        <input id="authenticate" name="authenticate" class="form-control" type="hidden">
    </div>

    <div class="form-group">
        <input id="u2fCheckbox" name="u2fCheckbox" type="checkbox" onclick=checkBrowser()> <?php echo t('Enable U2F') ?>
    </div>

    <div class="checkbox">
        <label style="font-weight:normal">
            <input id="uMaintainLogin" type="checkbox" name="uMaintainLogin" value="1">
            <?php echo t('Stay signed in for two weeks') ?>
        </label>
    </div>

    <?php
    if (isset($locales) && is_array($locales) && count($locales) > 0) {
        ?>
        <div class="form-group">
            <label for="USER_LOCALE" class="control-label"><?php echo t('Language') ?></label>
            <?php echo $form->select('USER_LOCALE', $locales) ?>
        </div>
        <?php
    }
    ?>

    <div class="form-group">
        <button name="login" class="btn btn-primary" onclick="doU2f()" type="button"><?php echo t('Log in') ?></button>
        <a href="<?php echo View::url('/login', 'concrete', 'forgot_password') ?>" class="btn pull-right"><?php echo t('Forgot Password') ?></a>
    </div>

    <script type="text/javascript">
        document.querySelector('input[name=uName]').focus();
        $('#uName').keypress(function (e) {
            if (e.which === 13) {
                $('button[name=login]').trigger('click');
            }
        });
        $('#uPassword').keypress(function (e) {
            if (e.which === 13) {
                $('button[name=login]').trigger('click');
            }
        });
        $('#u2fCheckbox').keypress(function (e) {
            if (e.which === 13) {
                $('button[name=login]').trigger('click');
            }
        });
        $('#uMaintainLogin').keypress(function (e) {
            if (e.which === 13) {
                $('button[name=login]').trigger('click');
            }
        });
    </script>

    <?php Loader::helper('validation/token')->output('login_' . $this->getAuthenticationTypeHandle()); ?>

    <?php if (Config::get('concrete.user.registration.enabled')) { ?>
        <br/>
        <hr/>
        <a href="<?php echo URL::to('/register') ?>" class="btn btn-block btn-success"><?php echo t('Not a member? Register') ?></a>
    <?php } ?>
</form>

<div id="u2f_dialog" style="display: none;">
    <h2>Performing U2F action</h2>
    Please touch the flashing U2F device now. <br><br>
    You may be prompted to allow the site permission to access your security keys. After granting permission, the device will start to blink.
</div>

<script type="text/javascript">
    function getU2fChecked() {
        return $('#u2fCheckbox').prop('checked');
    }

    function doU2f() {
        if (!getU2fChecked()) {
            submitForm();
            return;
        }

        doU2fSign($('#uName').val());
    }

    function showPerformAction() {
        $('div#u2f_dialog').dialog({
            buttons: [
                {
                    click: function () {
                        $(this).dialog('close');
                    },
                    text: '<?php echo t('Cancel') ?>'
                }
            ],
            height: 400,
            width: 400,
            modal: true,
            closeOnEscape: false
        });
    }

    function doU2fSign(username) {
        setTimeout(function () {
            $.ajax({
                type: 'GET',
                url: '<?php echo URL::to('/u2fclient/u2f_authenticate') ?>',
                data: "username=" + username,
                success: function (data) {
                    showPerformAction();
                    data = JSON.parse(data);
                    u2f.sign(data.authenticateRequests, function (resp) {
                        doU2fComplete(JSON.stringify(resp));
                    });
                }
            });
        }, 1000)
    }

    function doU2fComplete(resp) {
        $('#authenticate').val(resp);
        submitForm();
    }

    function submitForm() {
        $('#login-form').submit();
    }

    function checkBrowser() {
        if (!(location.protocol !== 'https:') && !(bowser.chrome) && !(bowser.version >= 41)) {
            alert('Chrome is required to do U2F actions!');
        }
    }
</script>
