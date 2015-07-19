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


<h3><?php echo t('Registered U2F Keys') ?></h3>
<label>&nbsp;</label>
<div> 
    <table class="table table-striped">
        <thead>
            <tr>
                <th class="subheader"><?php echo t('Keyhandle') ?></th>  
                <th class="subheader"><?php echo t('Name') ?></th> 
                <th class="subheader"><?php echo t('Created') ?></th> 
                <th class="subheader"><?php echo t('Last used') ?></th> 
                <th class="subheader"><?php echo t('Action') ?></th> 
            </tr>
        </thead>
        <?php foreach ($keys as $key) { ?>
            <tr>
                <td><?php
                    echo $key['handle'];
                    ?> 
                </td>
                <td>
                    <?php
                    if (!is_null($key['metadata']['device']['displayName'])) {
                        echo $key['metadata']['device']['displayName'];
                    } else {
                        echo "N/A";
                    }
                    ?>
                </td>
                <td><?php
                    if (!is_null($key['created'])) {
                        // TODO: datetime i18n
                        echo Loader::helper('date')->date('d.m.Y - H:i:s', $key['created']);
                    } else {
                        echo t('Never');
                    }
                    ?></td>
                <td><?php
                    if (!is_null($key['lastUsed'])) {
                        // TODO: datetime i18n
                        echo Loader::helper('date')->date('d.m.Y - H:i:s', $key['lastUsed']);
                    } else {
                        echo t('Never');
                    }
                    ?></td>
                <td>
                    <button class="btn btn-danger" type="button" onclick="removeKey('<?php echo $key['handle']; ?>')"><?php echo t('Remove') ?></button>
                </td>
            </tr>
        <?php } ?>
    </table>
</div> 

<form method="post" action="<?php echo $view->action('save') ?>" enctype="multipart/form-data">
    <?php
    $attribs = UserAttributeKey::getEditableInProfileList();
    $af = Loader::helper('form/attribute');
    $af->setAttributeObject($profile);
    foreach ($attribs as $ak) {
        if (get_object_vars($ak)['akHandle'] == 'U2F') {
            print $af->display($ak, $ak->isAttributeKeyRequiredOnProfile());
        }
    }
    ?>

    <div>
        <button id="add_key" class="btn btn-primary" type="button"><?php echo t('Add U2F device') ?></button>
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

    <label>&nbsp;</label>

    <div class="form-actions">
        <a href="<?php echo URL::to('/account') ?>" class="btn btn-default" /><?php echo t('Back to Account') ?></a>
        <input type="submit" name="save" value="<?php echo t('Save') ?>" class="btn btn-primary pull-right" />
    </div>
</form>

<script>
    var button = $('#add_key');
    button.click(function () {
        registerKey();
    });

    function removeKey(handle) {
        $.ajax({
            type: 'POST',
            url: "<?php echo str_replace('&amp;', '&', $this->action('u2fUnregister')); ?>",
            data: {handle: handle},
            success: function () {
                showDeviceDeleted();
            }
        });
    }

    function registerKey() {
        setTimeout(function () {
            $.ajax({
                type: 'GET',
                url: "<?php echo str_replace('&amp;', '&', $this->action('u2fRegister')); ?>",
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
            url: "<?php echo str_replace('&amp;', '&', $this->action('u2fRegisterComplete')); ?>",
            data: {register: JSON.stringify(resp)},
            error: function () {
                showRegistrationFailed();
            },
            success: function () {
                showDeviceSuccessfullyRegistered();
            }
        });
    }


    $(document).ready(function () {
        if (!(location.protocol !== 'https:') && !(bowser.chrome) && !(bowser.version >= 41)) {
            alert('Chrome is required to do U2F actions!');
        }
    });
</script>
