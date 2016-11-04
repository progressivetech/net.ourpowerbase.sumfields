<?php

require_once 'CRM/Core/Form.php';

class CRM_Sumfields_Form_SumFields extends CRM_Core_Form {
  function buildQuickForm() {
    $custom = sumfields_get_custom_field_definitions();
    $field_options = $trigger_tables = array();
    while(list($k,$v) = each($custom['fields'])) {
      $display = $v['display'];
      $field_options[$display][$k] = $v['label'];
      $trigger_tables[$v['trigger_table']] = FALSE;
    }
    if(count($field_options) == 0) {
      // This means neither CiviEvent or CiviContribute are enabled.
      $session = CRM_Core_Session::singleton();
      $session->setStatus(ts("Summary Fields is not particularly useful if CiviContribute and CiviEvent are both disabled. Try enabling at least one.", array('domain' => 'net.ourpowerbase.sumfields')));
      return;
    }
    // Evaluate the status of form changes and report to the user
    $apply_settings_status = sumfields_get_setting('generate_schema_and_data', FALSE);
    $status_icon = 'fa-times';

    if (empty($apply_settings_status)) {
      $display_status = ts('The settings have never been saved (newly enabled)', array('domain' => 'net.ourpowerbase.sumfields'));
      $status_icon = 'fa-minus-circle';
    }
    else {
      preg_match('/^(scheduled|running|success|failed):([0-9 :\-]+)$/', $apply_settings_status, $matches);
      $date = $matches[2];
      switch($matches[1]) {
        case 'scheduled':
          $display_status = ts("Setting changes were saved on %1, but not yet applied; they should be applied shortly.", array(1 => $date, 'domain' => 'net.ourpowerbase.sumfields'));
          $status_icon = 'fa-hourglass-start';
          break;
        case 'running':
          $display_status = ts("Setting changes are in the process of being applied; the process started on %1.", array(1 => $date, 'domain' => 'net.ourpowerbase.sumfields'));
          $status_icon = 'fa-hourglass-end';
          break;
        case 'success':
          $display_status = ts("Setting changes were successfully applied on %1.", array(1 => $date, 'domain' => 'net.ourpowerbase.sumfields'));
          $status_icon = 'fa-check';
          break;
        case 'failed':
          $display_status = ts("Setting changes failed to apply; the failed attempt happend on %1.", array(1 => $date, 'domain' => 'net.ourpowerbase.sumfields'));
          break;
        default:
          $display_status = ts("Unable to determine status (%1).", array(1 => $apply_settings_status, 'domain' => 'net.ourpowerbase.sumfields'));
      }
    }

    $this->assign('display_status', $display_status);
    $this->assign('status_icon', $status_icon);

    // Evaluate status of the triggers and report to the user.
    foreach ($trigger_tables as $table_name => &$status) {
      $status = sumfields_get_update_trigger($table_name);
    }
    $this->assign('trigger_table_status', $trigger_tables);

    // Add active fields
    if (sumfields_component_enabled('CiviContribute')) {
      $label = ts('Contribution Fields', array('domain' => 'net.ourpowerbase.sumfields'));
      $this->addCheckBox(
        'active_fundraising_fields', $label, array_flip($field_options['fundraising'])
      );
      $label = ts('Soft Credit Fields', array('domain' => 'net.ourpowerbase.sumfields'));
      $this->addCheckBox(
        'active_soft_fields', $label, array_flip($field_options['soft'])
      );
      $label = ts('Financial Types', array('domain' => 'net.ourpowerbase.sumfields'));
      $this->add('select', 'financial_type_ids', $label, sumfields_get_all_financial_types(), TRUE, array('multiple' => TRUE, 'class' => 'crm-select2 huge'));
    }

    if (sumfields_component_enabled('CiviMember')) {
      $name = 'active_membership_fields';
      $label = ts('Membership Fields', array('domain' => 'net.ourpowerbase.sumfields'));
      $this->addCheckBox(
        $name, $label, array_flip($field_options['membership'])
      );
      $label = ts('Financial Types', array('domain' => 'net.ourpowerbase.sumfields'));
      $this->add('select', 'membership_financial_type_ids', $label, sumfields_get_all_financial_types(), TRUE, array('multiple' => TRUE, 'class' => 'crm-select2 huge'));
    }

    if (sumfields_component_enabled('CiviEvent')) {
      if (array_key_exists('event_standard', $field_options)) {
        $label = ts('Standard Event Fields', array('domain' => 'net.ourpowerbase.sumfields'));
        $this->addCheckBox(
          'active_event_standard_fields', $label, array_flip($field_options['event_standard'])
        );
      }
      if (array_key_exists('event_turnout', $field_options)) {
        $label = ts('Turnout Event Fields', array('domain' => 'net.ourpowerbase.sumfields'));
        $this->addCheckBox(
          'active_event_turnout_fields', $label, array_flip($field_options['event_turnout'])
        );
      }

      $label = ts('Event Types', array('domain' => 'net.ourpowerbase.sumfields'));
      $this->add('select', 'event_type_ids', $label, sumfields_get_all_event_types(), TRUE, array('multiple' => TRUE, 'class' => 'crm-select2 huge'));

      $label = ts('Participant Status (attended)', array('domain' => 'net.ourpowerbase.sumfields'));
      $this->add('select', 'participant_status_ids', $label, sumfields_get_all_participant_status_types(), TRUE, array('multiple' => TRUE, 'class' => 'crm-select2 huge'));

      $label = ts('Participant Status (did not attend)', array('domain' => 'net.ourpowerbase.sumfields'));
      $this->add('select', 'participant_noshow_status_ids', $label, sumfields_get_all_participant_status_types(), TRUE, array('multiple' => TRUE, 'class' => 'crm-select2 huge'));
    }

    $name = 'when_to_apply_change';
    $label = ts('When should these changes be applied?', array('domain' => 'net.ourpowerbase.sumfields'));
    $options = array(
      'via_cron' => ts("On the next scheduled job (cron)", array('domain' => 'net.ourpowerbase.sumfields')),
      'on_submit' => ts("When I submit this form", array('domain' => 'net.ourpowerbase.sumfields'))
    );
    $this->addRadio(
      $name, $label, $options
    );

    $this->addButtons(array(
          array(
            'type' => 'next',
            'name' => ts('Save'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
  }

  function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    $custom = sumfields_get_custom_field_definitions();
    $active_fields = sumfields_get_setting('active_fields', array());

    foreach ($custom['fields'] as $name => $info) {
      if (in_array($name, $active_fields)) {
        $defaults["active_{$info['display']}_fields"][$name] = 1;
      }
    }

    $defaults['financial_type_ids'] = sumfields_get_setting('financial_type_ids', array());
    $defaults['membership_financial_type_ids'] = sumfields_get_setting('membership_financial_type_ids', array());
    $defaults['event_type_ids'] = sumfields_get_setting('event_type_ids', array());
    $defaults['participant_status_ids'] = sumfields_get_setting('participant_status_ids', array());
    $defaults['participant_noshow_status_ids'] = sumfields_get_setting('participant_noshow_status_ids', array());
    $defaults['when_to_apply_change'] = 'via_cron';
    return $defaults;
  }

  function postProcess() {
    $values = $this->controller->exportValues($this->_name);

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
    $settings = array('financial_type_ids', 'membership_financial_type_ids', 'event_type_ids', 'participant_status_ids', 'participant_noshow_status_ids');
    foreach ($settings as $setting) {
      if (array_key_exists($setting, $values)) {
        sumfields_save_setting($setting, $values[$setting]);
      }
    }

    $session = CRM_Core_Session::singleton();

    sumfields_save_setting('generate_schema_and_data', 'scheduled:'. date('Y-m-d H:i:s'));
    if ($values['when_to_apply_change'] == 'on_submit') {
      $returnValues = array();
      if (sumfields_gen_data($returnValues)) {
        $session::setStatus(ts("There was an error applying your changes.", array('domain' => 'net.ourpowerbase.sumfields')), ts('Error'), 'error');
      }
      else {
        $session::setStatus(ts("Changes were applied successfully.", array('domain' => 'net.ourpowerbase.sumfields')), ts('Saved'), 'success');
      }
    }
    else {
      $session::setStatus(ts("Your summary fields will begin being generated on the next scheduled job. It may take up to an hour to complete.", array('domain' => 'net.ourpowerbase.sumfields')), ts('Saved'), 'success');
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
    while(list($k) = each($options)) {
      $ret[] = $k;
    }
    return $ret;
  }

}
