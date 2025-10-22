<?php

use CRM_Sumfields_ExtensionUtil as E;

class CRM_Sumfields_Form_SumFields extends CRM_Core_Form {
  function buildQuickForm() {
    $custom = sumfields_get_custom_field_definitions();
    if (empty($custom['fields'])) {
      // This means neither CiviEvent or CiviContribute are enabled.
      CRM_Core_Session::setStatus(E::ts("Summary Fields is not particularly useful if CiviContribute and CiviEvent are both disabled. Try enabling at least one."));
      return;
    }
    $trigger_tables = $fieldsets = $field_options = array();
    foreach ($custom['fields'] as $k => $v) {
      $optgroup = $v['optgroup'];
      $fieldsets[$custom['optgroups'][$optgroup]['fieldset']]["active_{$optgroup}_fields"] = $custom['optgroups'][$optgroup]['description'] ?? NULL;
      $field_options[$optgroup][$k] = $v['label'];
      $trigger_tables[$v['trigger_table']] = FALSE;
    }
    // Evaluate the status of form changes and report to the user
    $apply_settings_status = sumfields_get_setting('generate_schema_and_data', FALSE);
    $data_update_method = sumfields_get_setting('data_update_method','default');
    $status_icon = 'fa-times';

    if (empty($apply_settings_status)) {
      $display_status = E::ts('The settings have never been saved (newly enabled)');
      $status_icon = 'fa-minus-circle';
    }
    else {
      preg_match('/^(scheduled|running|success|failed):([0-9 :\-]+)$/', $apply_settings_status, $matches);
      $status = $matches[1];
      $date = $matches[2];
      if ($status == 'scheduled') {
        // Status of scheduled could mean one of two things, depending
        // on the update method.
        if ($data_update_method == 'via_triggers') {
          $status = 'scheduled-triggers';
        }
        else {
          $status = 'scheduled-cron';
        }
      }

      switch($status) {
        case 'scheduled-triggers':
          $display_status = E::ts("Setting changes were saved on %1, but not yet applied; they should be applied shortly.", array(1 => $date));
          $status_icon = 'fa-hourglass-start';
          break;
        case 'scheduled-cron':
          $display_status = E::ts("Setting changes were saved on %1, data calculation will be performed on every cron run.", array(1 => $date));
          $status_icon = 'fa-hourglass-start';
          break;
        case 'running':
          $display_status = E::ts("Setting changes are in the process of being applied; the process started on %1.", array(1 => $date));
          $status_icon = 'fa-hourglass-end';
          break;
        case 'success':
          $display_status = E::ts("Setting changes were successfully applied on %1.", array(1 => $date));
          $status_icon = 'fa-check';
          break;
        case 'failed':
          $display_status = E::ts("Setting changes failed to apply; the failed attempt happend on %1.", array(1 => $date));
          break;
        default:
          $display_status = E::ts("Unable to determine status (%1).", array(1 => $apply_settings_status));
      }
    }

    $this->assign('display_status', $display_status);
    $this->assign('status_icon', $status_icon);
    if ($data_update_method == 'via_cron') {
      $this->assign('data_update_method', 'Cron job');
    } else {
      $this->assign('data_update_method', 'Triggers (Default)');
    }

    // Evaluate status of the triggers and report to the user.
    foreach ($trigger_tables as $table_name => &$status) {
      $status = sumfields_get_update_trigger($table_name);
    }
    $this->assign('trigger_table_status', $trigger_tables);

    // Allows to show a simplified list of fields
    $this->add('checkbox', 'show_simplified', E::ts('Show simplified fields'));
    // Add active fields and change the separator
    foreach ($field_options as $optgroup => $options) {
      $this->addCheckBox(
        "active_{$optgroup}_fields", $custom['optgroups'][$optgroup]['title'], array_flip($options),NULL, NULL, NULL, NULL, "<div></div>"
      );
    }

    // Add extra settings to fieldsets
    if (sumfields_component_enabled('CiviContribute')) {
      $label = E::ts('Financial Types');
      $this->add('select', 'financial_type_ids', $label, sumfields_get_all_financial_types(), TRUE, array('multiple' => TRUE, 'class' => 'crm-select2 huge'));
      $fieldsets[$custom['optgroups']['fundraising']['fieldset']]['financial_type_ids'] = E::ts("Financial types to include when calculating contribution related summary fields.");
    }

    if (sumfields_component_enabled('CiviMember')) {
      $label = E::ts('Financial Types');
      $this->add('select', 'membership_financial_type_ids', $label, sumfields_get_all_financial_types(), TRUE, array('multiple' => TRUE, 'class' => 'crm-select2 huge'));
      $fieldsets[$custom['optgroups']['membership']['fieldset']]['membership_financial_type_ids'] = E::ts("Financial types to include when calculating membership related summary fields.");
    }

    if (sumfields_component_enabled('CiviEvent')) {
      $label = E::ts('Event Types');
      $this->add('select', 'event_type_ids', $label, sumfields_get_all_event_types(), TRUE, array('multiple' => TRUE, 'class' => 'crm-select2 huge'));

      $label = E::ts('Participant Status (attended)');
      $this->add('select', 'participant_status_ids', $label, sumfields_get_all_participant_status_types(), TRUE, array('multiple' => TRUE, 'class' => 'crm-select2 huge'));

      $label = E::ts('Participant Status (did not attend)');
      $this->add('select', 'participant_noshow_status_ids', $label, sumfields_get_all_participant_status_types(), TRUE, array('multiple' => TRUE, 'class' => 'crm-select2 huge'));

      $fieldsets[$custom['optgroups']['event_standard']['fieldset']] += array(
        'event_type_ids' => 'Event types to include when calculating participant summary fields',
        'participant_status_ids' => '',
        'participant_noshow_status_ids' => '',
      );
    }

    $this->assign('fieldsets', $fieldsets);

    $bd_label = E::ts('How often should summary data be updated?');
    $bd_options = array(
      'via_triggers' => E::ts("Instantly"),
      'via_cron' => E::ts("When ever the cron job is run (increases performance on large installation)")
    );
    $this->addRadio('data_update_method', $bd_label, $bd_options);

    $label = E::ts('When should these changes be applied?');
    $options = array(
      'via_cron' => E::ts("On the next scheduled job (cron)"),
      'on_submit' => E::ts("When I submit this form")
    );
    $this->addRadio('when_to_apply_change', $label, $options);

    $this->addButtons(array(
          array(
            'type' => 'next',
            'name' => E::ts('Save'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'cancel',
            'name' => E::ts('Cancel'),
          ),
        )
      );
    $this->addYesNo('exclude_from_logging', 'Exclude Summary Fields from logging?');
  }

  function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    $custom = sumfields_get_custom_field_definitions();
    $active_fields = sumfields_get_setting('active_fields', array());

    foreach ($custom['fields'] as $name => $info) {
      if (in_array($name, $active_fields)) {
        $defaults["active_{$info['optgroup']}_fields"][$name] = 1;
      }
    }
    $defaults['show_simplified'] = sumfields_get_setting('show_simplified', FALSE);
    $defaults['financial_type_ids'] = sumfields_get_setting('financial_type_ids', array());
    $defaults['membership_financial_type_ids'] = sumfields_get_setting('membership_financial_type_ids', array());
    $defaults['event_type_ids'] = sumfields_get_setting('event_type_ids', array());
    $defaults['participant_status_ids'] = sumfields_get_setting('participant_status_ids', array());
    $defaults['participant_noshow_status_ids'] = sumfields_get_setting('participant_noshow_status_ids', array());
    $defaults['when_to_apply_change'] = sumfields_get_setting('when_to_apply_change','via_cron');
    $defaults['data_update_method'] = sumfields_get_setting('data_update_method','via_triggers');
    $defaults['exclude_from_logging'] = sumfields_get_setting('exclude_from_logging', 0);
    return $defaults;
  }

  function postProcess() {
    $values = $this->controller->exportValues($this->_name);

    // show_simplified is a checkbox so won't exist if not checked but we want to avoid missing index errors.
    if (!array_key_exists('show_simplified', $values)) {
      $values['show_simplified'] = 0;
    }

    // Combine all fields into on active_fields array for easier processing.
    $active_fields = array();
    foreach ($values as $key => $val) {
      if (strpos($key, 'active_') === 0 && substr($key, -7) == '_fields') {
        $active_fields += $val;
      }
    }
    if ($active_fields) {
      $current_active_fields = sumfields_get_setting('active_fields', array());
      $new_active_fields = $this->options_to_array($active_fields);
      if ($current_active_fields != $new_active_fields) {
        // Setting 'new_active_fields' will alert the system that we have
        // field changes to be applied.
        sumfields_save_setting('new_active_fields', $new_active_fields);
      }
    }
    $settings = array('financial_type_ids', 'membership_financial_type_ids', 'event_type_ids', 'participant_status_ids', 'participant_noshow_status_ids', 'show_simplified');
    foreach ($settings as $setting) {
      if (array_key_exists($setting, $values)) {
        sumfields_save_setting($setting, $values[$setting]);
      }
    }

    $session = CRM_Core_Session::singleton();

    sumfields_save_setting('generate_schema_and_data', 'scheduled:'. date('Y-m-d H:i:s'));
    // Save our form page settings
    sumfields_save_setting('data_update_method', $values['data_update_method']);
    sumfields_save_setting('when_to_apply_change', $values['when_to_apply_change']);
    sumfields_save_setting('show_simplified', $values['show_simplified']);
    sumfields_save_setting('exclude_from_logging', $values['exclude_from_logging']);

    if ($values['when_to_apply_change'] == 'on_submit') {
      $returnValues = array();
      if (!sumfields_gen_data($returnValues)) {
        $session::setStatus(E::ts("There was an error applying your changes."), E::ts('Error'), 'error');
      }
      else {
        $session::setStatus(E::ts("Changes were applied successfully."), E::ts('Saved'), 'success');
      }
    }
    else {
      $session::setStatus(E::ts("Your summary fields will begin being generated on the next scheduled job. It may take up to an hour to complete."), E::ts('Saved'), 'success');
    }
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/setting/sumfields'));
  }

  /**
   * The form api wants options in the form of:
   * array( 'value1' => 1, 'value2' => 1 );
   * We want to save it as array('value1' , 'value2');
   **/
  function options_to_array($options) {
    $ret = array();
    foreach ($options as $k => $v) {
      $ret[] = $k;
    }
    return $ret;
  }

}
