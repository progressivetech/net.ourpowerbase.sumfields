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
      'financial_type_ids', 
      ts('Financial Types'),
      array_flip(sumfields_get_all_financial_types())
    );
    $this->addCheckBox(
      'membership_financial_type_ids', 
      ts('Membership Financial Types'),
      array_flip(sumfields_get_all_financial_types())
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
    $this->addCheckBox(
      'participant_noshow_status_ids', 
      ts('Participant No Show Status'),
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
    sumfields_generate_data_based_on_current_data();
    $session->setStatus(ts("All summary fields have been updated."));
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
