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

<?php defined('C5_EXECUTE') or die('Access denied.'); ?>

<h3><?php echo t('U2F Server settings') ?></h3>

<div class='form-group'>
    <?php echo $form->label('url', t('URL')) ?>
    <?php echo $form->text('url', $url) ?>
</div>

<div class='form-group'>
    <?php echo $form->label('username', t('Username')) ?>
    <?php echo $form->text('username', $username) ?>
</div>

<div class='form-group'>
    <?php echo $form->label('password', t('Password')) ?>
    <div class="input-group">
        <?php echo $form->password('password', $password) ?>
        <span class="input-group-btn">
            <button id="showpassword" class="btn btn-warning" type="button"><?php echo t('Show password') ?></button>
        </span>
    </div>
</div>

<div class='form-group'>
    <h3><?php echo t('U2F Dummyuser') ?></h3>
    <?php echo t('This user is required to return a challenge even if a user does not exist. This is just for security reasons, that an attacker can not find out if an U2F user exists or not.') ?>
    <label>&nbsp;</label>
    <br>
    <button id="registerDummyuser" class="btn btn-primary" type="button" onclick="registerKey()"><?php echo t('Add dummyuser') ?></button>
</div>

<div id="u2fdialog_perform_action" style="display: none;">
    <h2><?php echo t('Performing U2F action') ?></h2>
    <?php echo t('Please touch the flashing U2F device now.') ?> <br><br>
    <?php echo t('You may be prompted to allow the site permission to access your security keys. After granting permission, the device will start to blink.') ?>
</div>

<div id="u2fdialog_device_successfully_registered" style="display: none;">
    <h2><?php echo t('U2F: Device registered') ?></h2>
    <?php echo t('Device has successfully been registered. You can login with that device or register additional devices.') ?>
</div>

<div id="u2fdialog_device_deleted" style="display: none;">
    <h2><?php echo t('U2F: Device deleted') ?></h2>
    <?php echo t('Device has been successfully deleted. You can not login with that device any longer.') ?>
</div>

<div id="u2fdialog_other_error" style="display: none;">
    <h2><?php echo t('U2F: Other error') ?></h2>
    <?php echo t('An unknown error occurred.') ?>
</div>

<div id="u2fdialog_bad_request" style="display: none;">
    <h2><?php echo t('U2F: Bad request') ?></h2>
    <?php echo t('One of the following reasons:') ?> <br>
    <?php echo t('- The visited URL doesn’t match the AppID. ') ?> <br>
    <?php echo t('- You are not using HTTPS.') ?>
</div>

<div id="u2fdialog_config_unsupported" style="display: none;">
    <h2><?php echo t('U2F: Unsupported configuration') ?></h2>
    <?php echo t('Client configuration is not supported.') ?>
</div>

<div id="u2fdialog_device_already_registered" style="display: none;">
    <h2><?php echo t('U2F: Device already registered') ?></h2>
    <?php echo t('This device has already been registered. You can login with that device or register an other device.') ?>
</div>

<div id="u2fdialog_timeout" style="display: none;">
    <h2><?php echo t('U2F: Timeout') ?></h2>
    <?php echo t('Timeout reached before request could be satisfied.') ?>
</div>

<script>
    var button = $('#showpassword');
    button.click(function () {
        var password = $('#password');
        if (password.attr('type') === 'password') {
            password.attr('type', 'text');
            button.html('<?php echo addslashes(t('Hide password')) ?>');
        } else {
            password.attr('type', 'password');
            button.html('<?php echo addslashes(t('Show password')) ?>');
        }
    });

    function registerKey() {
        setTimeout(function () {
            $.ajax({
                type: 'GET',
                url: '<?php echo URL::to('/u2fclient/u2f_register') ?>',
                success: function (data) {
                    showPerformAction();
                    data = JSON.parse(data);
                    u2f.register(data.registerRequests, data.authenticateRequests, function (resp) {
                        closePerformAction();
                        if (resp.errorCode) {
                            showError(resp.errorCode);
                            return;
                        } else {
                            u2fRegisterComplete(resp);
                        }
                    });
                }
            });
        }, 1000);
    }

    function showPerformAction() {
        $('div#u2fdialog_perform_action').dialog({
            buttons: [
                {
                    click: function () {
                        $(this).dialog('close');
                    },
                    text: '<?php echo t('Close') ?>'
                }
            ],
            height: 400,
            width: 400,
            modal: true,
            closeOnEscape: false
        });
    }

    function closePerformAction() {
        $('div#u2fdialog_perform_action').dialog('close');
    }

    function showDeviceSuccessfullyRegistered() {
        showDialog('u2fdialog_device_successfully_registered');
    }

    function showDeviceAlreadyRegistered() {
        showDialog('u2fdialog_device_already_registered');
    }

    function showDeviceDeleted() {
        showDialog('u2fdialog_device_deleted');
    }

    function showRegistrationFailed() {
        showDialog('u2fdialog_reg_failed');
    }

    function showOtherError() {
        showDialog('u2fdialog_other_error');
    }

    function showBadRequest() {
        showDialog('u2fdialog_bad_request');
    }

    function showError(errorCode) {
        switch (errorCode) {
            case 1:
                showDialog('u2fdialog_other_error');
                break;
            case 2:
                showDialog('u2fdialog_bad_request');
                break;
            case 3:
                showDialog('u2fdialog_config_unsupported');
                break;
            case 4:
                showDialog('u2fdialog_device_already_registered');
                break;
            case 5:
                showDialog('u2fdialog_timeout');
                break;
        }
    }


    function showDialog(divName) {
        $('div#' + divName).dialog({
            buttons: [
                {
                    click: function () {
                        $(this).dialog('close');
                        location.reload();
                    },
                    text: '<?php echo t('Ok') ?>'
                }
            ],
            height: 400,
            width: 400,
            modal: true,
            closeOnEscape: false
        });
    }

    function u2fRegisterComplete(resp) {
        $.ajax({
            type: 'POST',
            url: "<?php echo URL::to('/u2fclient/u2f_register_complete') ?>",
            data: {register: JSON.stringify(resp)},
            error: function () {
                showRegistrationFailed();
            },
            success: function () {
                showDeviceSuccessfullyRegistered();
            }
        });
    }
</script>
