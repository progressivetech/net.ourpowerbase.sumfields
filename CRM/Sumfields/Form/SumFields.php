<?php

require_once 'CRM/Core/Form.php';

class CRM_Sumfields_Form_SumFields extends CRM_Core_Form {
  function buildQuickForm() {
    
    

    $custom = sumfields_get_custom_field_definitions();
    $options = array();
    while(list($k,$v) = each($custom['fields'])) {
      $field_options[$k] = $v['label'];
    }
    $this->addCheckBox(
      'active_fields', 
      ts('Active Fields'),
      array_flip($field_options)
    );
    $this->addCheckBox(
      'contribution_type_ids', 
      ts('Contribution Types'),
      array_flip(sumfields_get_all_contribution_types())
    );
    $this->addCheckBox(
      'membership_contribution_type_ids', 
      ts('Membership Contribution Types'),
      array_flip(sumfields_get_all_contribution_types())
    );
    $this->addCheckBox(
      'event_type_ids', 
      ts('Event Types'),
      array_flip(sumfields_get_all_event_types())
    );
    $this->addCheckBox(
      'participant_status_ids', 
      ts('Participant Status'),
      array_flip(sumfields_get_all_participant_status_types())
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
    $defaults['active_fields'] = $this->array_to_options(sumfields_get_setting('active_fields', array()));
    $defaults['contribution_type_ids'] = $this->array_to_options(sumfields_get_setting('contribution_type_ids', array()));
    $defaults['membership_contribution_type_ids'] = $this->array_to_options(sumfields_get_setting('membership_contribution_type_ids', array()));
    $defaults['event_type_ids'] = $this->array_to_options(sumfields_get_setting('event_type_ids', array()));
    $defaults['participant_status_ids'] = $this->array_to_options(sumfields_get_setting('participant_status_ids', array()));
    return $defaults;
  }

  function postProcess() {
    $values = $this->controller->exportValues($this->_name);

    // Keep track of whether or not active_fields have changed. If they have changed, we have to
    // re-create the table below. If they haven't change, don't re-create the table. We want to 
    // avoid re-creating the table because it causes custom fields assigned to profiles to be 
    // deleted.
    $active_fields_have_changed = FALSE;
    if(array_key_exists('active_fields', $values)) {
      $current_active_fields = sumfields_get_setting('active_fields');
      $new_active_fields = $this->options_to_array($values['active_fields']);
      if($current_active_fields != $new_active_fields) {
        $active_fields_have_changed = TRUE;
        sumfields_save_setting('active_fields', $new_active_fields);
      }
    }
    if(array_key_exists('contribution_type_ids', $values)) {
      sumfields_save_setting('contribution_type_ids', $this->options_to_array($values['contribution_type_ids']));
    }
    if(array_key_exists('membership_contribution_type_ids', $values)) {
      sumfields_save_setting('membership_contribution_type_ids', $this->options_to_array($values['membership_contribution_type_ids']));
    }
    if(array_key_exists('event_type_ids', $values)) {
      sumfields_save_setting('event_type_ids', $this->options_to_array($values['event_type_ids']));
    }
    if(array_key_exists('participant_status_ids', $values)) {
      sumfields_save_setting('participant_status_ids', $this->options_to_array($values['participant_status_ids']));
    }
    $session = CRM_Core_Session::singleton();

    if($active_fields_have_changed) {
      // Now we have to re-initialize the custom table and fields...
      $session->setStatus("Active fields have changed, re-building the table. WARNING: fields assigned to profiles will need to be re-assigned.");
      if(FALSE === sumfields_deinitialize_custom_data()) {
        $session->setStatus("Error deninitializing.");
        return;
      }
      if(FALSE === sumfields_initialize_custom_data()) {
        $session->setStatus("Error initializing.");
        return;
      }
    }
    else {
      sumfields_generate_data_based_on_current_data();
    }
    CRM_Core_DAO::triggerRebuild();
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
