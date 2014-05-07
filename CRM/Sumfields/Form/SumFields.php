<?php

require_once 'CRM/Core/Form.php';

class CRM_Sumfields_Form_SumFields extends CRM_Core_Form {
  function buildQuickForm() {
    $custom = sumfields_get_custom_field_definitions();
    $options = array();
    $field_options = array();
    while(list($k,$v) = each($custom['fields'])) {
      $field_options[$k] = $v['label'];
    }
    if(count($field_options) == 0) {
      // This means neither CiviEvent or CiviContribute are enabled.
      $session = CRM_Core_Session::singleton();
      $session->setStatus(ts("Summary Fields is not particularly useful if
        CiviContribute and CiviEvent are both disabled. Try enabling at least
        one."));
      return;
    }

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
    $name = 'active_fields';
    $label = ts('Active Fields');
    $this->addCheckBox(
      $name, $label, array_flip($field_options)
    );
    $this->addRule($name, ts('You must define at least one active field'), 'required');

    if(sumfields_component_enabled('CiviMember')) {
      $this->assign('sumfields_member', TRUE);
      $name = 'membership_financial_type_ids';
      $label = ts('Membership Financial Types');
      $this->addCheckBox(
        $name, $label, array_flip(sumfields_get_all_financial_types())
      );
      $this->addRule($name, ts('%1 is a required field.', array(1 => $label)), 'required');
    }
    if(sumfields_component_enabled('CiviContribute')) {
      $this->assign('sumfields_contribute', TRUE);
      $name = 'financial_type_ids';
      $label = ts('Financial Types');
      $this->addCheckBox(
        $name, $label, array_flip(sumfields_get_all_financial_types())
      );
      $this->addRule($name, ts('%1 is a required field.', array(1 => $label)), 'required');
    }
    if(sumfields_component_enabled('CiviEvent')) {
      $this->assign('sumfields_event', TRUE);
      $name = 'event_type_ids';
      $label = ts('Event Types');
      $this->addCheckBox(
        $name, $label, array_flip(sumfields_get_all_event_types())
      );
      $this->addRule($name, ts('%1 is a required field.', array(1 => $label)), 'required');

      $label = ts('Participant Status (attended)');
      $name = 'participant_status_ids';
      $this->addCheckBox(
        $name, 
        $label,
        array_flip(sumfields_get_all_participant_status_types())
      );
      $this->addRule($name, ts('%1 is a required field.', array(1 => $label)), 'required');
      $label = ts('Participant Status (did not attend)');
      $name = 'participant_noshow_status_ids';
      $this->addCheckBox(
        $name,
        $label,
        array_flip(sumfields_get_all_participant_status_types())
      );
      $this->addRule($name, ts('%1 is a required field.', array(1 => $label)), 'required');
    }

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
    $defaults['active_fields'] = $this->array_to_options(sumfields_get_setting('active_fields', array()));
    $defaults['financial_type_ids'] = $this->array_to_options(sumfields_get_setting('financial_type_ids', array()));
    $defaults['membership_financial_type_ids'] = $this->array_to_options(sumfields_get_setting('membership_financial_type_ids', array()));
    $defaults['event_type_ids'] = $this->array_to_options(sumfields_get_setting('event_type_ids', array()));
    $defaults['participant_status_ids'] = $this->array_to_options(sumfields_get_setting('participant_status_ids', array()));
    $defaults['participant_noshow_status_ids'] = $this->array_to_options(sumfields_get_setting('participant_noshow_status_ids', array()));
    return $defaults;
  }

  function postProcess() {
    $values = $this->controller->exportValues($this->_name);

    // Keep track of whether or not active_fields have changed so we know whether or not
    // to alter the table.
    $active_fields_have_changed = FALSE;
    if(array_key_exists('active_fields', $values)) {
      $current_active_fields = sumfields_get_setting('active_fields');
      $new_active_fields = $this->options_to_array($values['active_fields']);
      if($current_active_fields != $new_active_fields) {
        $active_fields_have_changed = TRUE;
        sumfields_save_setting('active_fields', $new_active_fields);
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

    if($active_fields_have_changed) {
      // Now we have add/remove fields 
      sumfields_alter_table($current_active_fields, $new_active_fields);
    }
    if(sumfields_generate_data_based_on_current_data($session)) {
      $session->setStatus(ts("All summary fields have been updated."));
      CRM_Core_DAO::triggerRebuild();
    }
    else {
      $session->setStatus(ts("There was an error re-generating the data."));
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
