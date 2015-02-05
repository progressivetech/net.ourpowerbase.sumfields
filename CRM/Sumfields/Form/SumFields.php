<?php

require_once 'CRM/Core/Form.php';

class CRM_Sumfields_Form_SumFields extends CRM_Core_Form {
  function buildQuickForm() {
    $custom = sumfields_get_custom_field_definitions();
    $options = array();
    $field_options = array();
    while(list($k,$v) = each($custom['fields'])) {
      $display = $v['display'];
      $field_options[$display][$k] = $v['label'];
    }
    if(count($field_options) == 0) {
      // This means neither CiviEvent or CiviContribute are enabled.
      $session = CRM_Core_Session::singleton();
      $session->setStatus(ts("Summary Fields is not particularly useful if CiviContribute and CiviEvent are both disabled. Try enabling at least one.", array('domain' => 'net.ourpowerbase.sumfields')));
      return;
    }
    // Evaluate the status of form changes and report to the user
    $apply_settings_status = sumfields_get_setting('generate_schema_and_data', FALSE);

    if(empty($apply_settings_status)) {
      $display_status = ts('The settings have never been saved (newly enabled)', array('domain' => 'net.ourpowerbase.sumfields'));
    }
    elseif(!preg_match('/^(scheduled|running|success|failed):([0-9 :\-]+)$/', $apply_settings_status, $matches)) {
      $display_status = ts("Unable to determine status (%1).", array(1 => $apply_settings_status, 'domain' => 'net.ourpowerbase.sumfields'));
    }
    else {
      $display_status = NULL;
      $status = $matches[1];
      $date = $matches[2];
      switch($status) {
      case 'scheduled':
        $display_status = ts("Setting changes were saved on %1, but not yet applied; they should be applied shortly.", array(1 => $date, 'domain' => 'net.ourpowerbase.sumfields'));
        break;
      case 'running':
        $display_status = ts("Setting changes are in the process of being applied; the process started on %1.", array(1 => $date, 'domain' => 'net.ourpowerbase.sumfields'));
        break;
      case 'success':
        $display_status = ts("Setting changes were successfully applied on %1.", array(1 => $date, 'domain' => 'net.ourpowerbase.sumfields'));
        break;
      case 'failed':
        $display_status = ts("Setting changes failed to apply; the failed attempt happend on %1.", array(1 => $date, 'domain' => 'net.ourpowerbase.sumfields'));
        break;
      }
    }

    $this->Assign('display_status', $display_status);

    // Evaluate status of the triggers and report to the user.
    if(sumfields_get_update_trigger('civicrm_contribution')) {
      $contribution_table_trigger_status = 'Enabled';
    }
    else {
      $contribution_table_trigger_status = 'Not Enabled';
    }
    $this->Assign(
      'contribution_table_trigger_status', $contribution_table_trigger_status
    );

    if(sumfields_get_update_trigger('civicrm_participant')) {
      $participant_table_trigger_status = 'Enabled';
    }
    else {
      $participant_table_trigger_status = 'Not Enabled';
    }
    $this->Assign(
      'participant_table_trigger_status', $participant_table_trigger_status
    );

    // Add active fields
    if(array_key_exists('fundraising', $field_options)) {
      $this->Assign('sumfields_active_fundraising', TRUE);
      $name = 'active_fundraising_fields';
      $label = ts('Fundraising Fields', array('domain' => 'net.ourpowerbase.sumfields'));
      $this->addCheckBox(
        $name, $label, array_flip($field_options['fundraising'])
      );
    }
    
    if(sumfields_component_enabled('CiviMember') && 
      array_key_exists('membership', $field_options)) {

      $this->Assign('sumfields_active_membership', TRUE);
      $name = 'active_membership_fields';
      $label = ts('Membership Fields', array('domain' => 'net.ourpowerbase.sumfields'));
      $this->addCheckBox(
        $name, $label, array_flip($field_options['membership'])
      );
    }

    if(array_key_exists('event_standard', $field_options)) {
      $this->Assign('sumfields_active_event_standard', TRUE);
      $name = 'active_event_standard_fields';
      $label = ts('Standard Event Fields', array('domain' => 'net.ourpowerbase.sumfields'));
      $this->addCheckBox(
        $name, $label, array_flip($field_options['event_standard'])
      );
    }
    if(array_key_exists('event_turnout', $field_options)) {
      $this->Assign('sumfields_active_event_turnout', TRUE);
      $name = 'active_event_turnout_fields';
      $label = ts('Turnout Event Fields', array('domain' => 'net.ourpowerbase.sumfields'));
      $this->addCheckBox(
        $name, $label, array_flip($field_options['event_turnout'])
      );
    }

    if(sumfields_component_enabled('CiviMember')) {
      $this->assign('sumfields_member', TRUE);
      $name = 'membership_financial_type_ids';
      $label = ts('Membership Financial Types', array('domain' => 'net.ourpowerbase.sumfields'));
      $this->addCheckBox(
        $name, $label, array_flip(sumfields_get_all_financial_types())
      );
      $this->addRule($name, ts('%1 is a required field.', array(1 => $label, 'domain' => 'net.ourpowerbase.sumfields')), 'required');
    }
    if(sumfields_component_enabled('CiviContribute')) {
      $this->assign('sumfields_contribute', TRUE);
      $name = 'financial_type_ids';
      $label = ts('Financial Types', array('domain' => 'net.ourpowerbase.sumfields'));
      $this->addCheckBox(
        $name, $label, array_flip(sumfields_get_all_financial_types())
      );
      $this->addRule($name, ts('%1 is a required field.', array(1 => $label, 'domain' => 'net.ourpowerbase.sumfields')), 'required');
    }
    if(sumfields_component_enabled('CiviEvent')) {
      $this->assign('sumfields_event', TRUE);
      $name = 'event_type_ids';
      $label = ts('Event Types', array('domain' => 'net.ourpowerbase.sumfields'));
      $this->addCheckBox(
        $name, $label, array_flip(sumfields_get_all_event_types())
      );
      $this->addRule($name, ts('%1 is a required field.', array(1 => $label, 'domain' => 'net.ourpowerbase.sumfields')), 'required');

      $label = ts('Participant Status (attended)', array('domain' => 'net.ourpowerbase.sumfields'));
      $name = 'participant_status_ids';
      $this->addCheckBox(
        $name, 
        $label,
        array_flip(sumfields_get_all_participant_status_types())
      );
      $this->addRule($name, ts('%1 is a required field.', array(1 => $label, 'domain' => 'net.ourpowerbase.sumfields')), 'required');
      $label = ts('Participant Status (did not attend)', array('domain' => 'net.ourpowerbase.sumfields'));
      $name = 'participant_noshow_status_ids';
      $this->addCheckBox(
        $name,
        $label,
        array_flip(sumfields_get_all_participant_status_types())
      );
      $this->addRule($name, ts('%1 is a required field.', array(1 => $label, 'domain' => 'net.ourpowerbase.sumfields')), 'required');
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
    $active_fundraising_fields = array();
    $active_membership_fields = array();
    $active_event_standard_fields = array();
    $active_event_turnout_fields = array();
    while(list($field,$field_info) = each($custom['fields'])) {
      if(in_array($field, $active_fields)) {
        if($field_info['display'] == 'fundraising') {
          $active_fundraising_fields[] = $field;
        }
        elseif($field_info['display'] == 'membership') {
          $active_membership_fields[] = $field;
        }
        elseif($field_info['display'] == 'event_standard') {
          $active_event_standard_fields[] = $field;
        }
        elseif($field_info['display'] == 'event_turnout') {
          $active_event_turnout_fields[] = $field;
        }
      }
    } 
    $defaults['active_fundraising_fields'] = $this->array_to_options($active_fundraising_fields);
    $defaults['active_membership_fields'] = $this->array_to_options($active_membership_fields);
    $defaults['active_event_standard_fields'] = $this->array_to_options($active_event_standard_fields);
    $defaults['active_event_turnout_fields'] = $this->array_to_options($active_event_turnout_fields);


    $defaults['active_fields'] = $this->array_to_options(sumfields_get_setting('active_fields', array()));
    $defaults['financial_type_ids'] = $this->array_to_options(sumfields_get_setting('financial_type_ids', array()));
    $defaults['membership_financial_type_ids'] = $this->array_to_options(sumfields_get_setting('membership_financial_type_ids', array()));
    $defaults['event_type_ids'] = $this->array_to_options(sumfields_get_setting('event_type_ids', array()));
    $defaults['participant_status_ids'] = $this->array_to_options(sumfields_get_setting('participant_status_ids', array()));
    $defaults['participant_noshow_status_ids'] = $this->array_to_options(sumfields_get_setting('participant_noshow_status_ids', array()));
    $defaults['when_to_apply_change'] = 'via_cron';
    return $defaults;
  }

  function postProcess() {
    $values = $this->controller->exportValues($this->_name);

    // Combine all fields into on active_fields array for easier processing.
    $active_fields = array();
    if(array_key_exists('active_fundraising_fields', $values)) {
      $active_fields = $active_fields + $values['active_fundraising_fields'];
    }
    if(array_key_exists('active_membership_fields', $values)) {
      $active_fields = $active_fields + $values['active_membership_fields'];
    }
    if(array_key_exists('active_event_standard_fields', $values)) {
      $active_fields = $active_fields + $values['active_event_standard_fields'];
    }
    if(array_key_exists('active_event_turnout_fields', $values)) {
      $active_fields = $active_fields + $values['active_event_turnout_fields'];
    }
    if(count($active_fields) > 0) {
      $current_active_fields = sumfields_get_setting('active_fields', array());
      $new_active_fields = $this->options_to_array($active_fields);
      if($current_active_fields != $new_active_fields) {
        // Setting 'new_active_fields' will alert the system that we have 
        // field changes to be applied.
        sumfields_save_setting('new_active_fields', $new_active_fields);
      }
    }
    if(array_key_exists('financial_type_ids', $values)) {
      sumfields_save_setting('financial_type_ids', $this->options_to_array($values['financial_type_ids']));
    }
    if(array_key_exists('membership_financial_type_ids', $values)) {
      sumfields_save_setting('membership_financial_type_ids', $this->options_to_array($values['membership_financial_type_ids']));
    }
    if(array_key_exists('event_type_ids', $values)) {
      sumfields_save_setting('event_type_ids', $this->options_to_array($values['event_type_ids']));
    }
    if(array_key_exists('participant_status_ids', $values)) {
      sumfields_save_setting('participant_status_ids', $this->options_to_array($values['participant_status_ids']));
    }
    if(array_key_exists('participant_noshow_status_ids', $values)) {
      sumfields_save_setting('participant_noshow_status_ids', $this->options_to_array($values['participant_noshow_status_ids']));
    }
    $session = CRM_Core_Session::singleton();

    sumfields_save_setting('generate_schema_and_data', 'scheduled:'. date('Y-m-d H:i:s'));
    if($values['when_to_apply_change'] == 'on_submit') {
      $returnValues = array();
      if(!sumfields_gen_data($returnValues)) {
        $msg = ts("There was an error applying your changes.", array('domain' => 'net.ourpowerbase.sumfields'));
      }
      else {
        $msg = ts("Changes were applied successfully.", array('domain' => 'net.ourpowerbase.sumfields'));
      }
    }
    else {
      $session->setStatus(ts("Your summary fields will begin being generated on the next scheduled job. It may take up to an hour to complete.", array('domain' => 'net.ourpowerbase.sumfields')));
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

  /**
   * The form api wants options in the form of:
   * array( 'value1' => 1, 'value2' => 1 );
   * We want to save it as array('value1' , 'value2');
   **/
  function array_to_options($array) {
    $ret = array();
    while(list(,$v) = each($array)) {
      $ret[$v] = 1;
    }
    return $ret;
  }
}
