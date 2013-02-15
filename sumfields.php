<?php

require_once 'sumfields.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function sumfields_civicrm_config(&$config) {
  _sumfields_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function sumfields_civicrm_xmlMenu(&$files) {
  _sumfields_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function sumfields_civicrm_install() {
  return _sumfields_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function sumfields_civicrm_uninstall() {
  return _sumfields_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function sumfields_civicrm_enable() {
  sumfields_initialize_user_settings();
  if(FALSE === sumfields_initialize_custom_data()) return FALSE;
  return _sumfields_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function sumfields_civicrm_disable() {
  sumfields_deinitialize_custom_data(); 
  sumfields_delete_user_settings();
  return _sumfields_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function sumfields_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _sumfields_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function sumfields_civicrm_managed(&$entities) {
  return _sumfields_civix_civicrm_managed($entities);
}

/**
 * Replace %variable with the actual
 * values that the user has configured to limit to.
 **/
function sumfields_sql_rewrite($sql) {
  $ids = sumfields_get_setting('contribution_type_ids', array());
  $str_ids = implode(',', $ids);
  $sql = str_replace('%contribution_type_ids', $str_ids, $sql);

  $ids = sumfields_get_setting('membership_contribution_type_ids', array());
  $str_ids = implode(',', $ids);
  $sql = str_replace('%membership_contribution_type_ids', $str_ids, $sql);

  $ids = sumfields_get_setting('participant_status_ids', array());
  $str_ids = implode(',', $ids);
  $sql = str_replace('%participant_status_ids', $str_ids, $sql);

  $ids = sumfields_get_setting('event_type_ids', array());
  $str_ids = implode(',', $ids);
  $sql = str_replace('%event_type_ids', $str_ids, $sql);

  return $sql;
}

/**
 * hook_civicrm_triggerInfo()
 *
 * Add triggers for our tables
 **/

function sumfields_civicrm_triggerInfo(&$info, $tableName) {
  
  // Our triggers all use custom fields. CiviCRM, when generating
  // custom fields, sometimes gives them different names (appending
  // the id in most cases) to avoid name collisions. 
  //
  // So, we have to retrieve the actual name of each field that is in 
  // use.

  $table_name = _sumfields_get_custom_table_name(); 
  $custom_fields = _sumfields_get_custom_field_parameters();

  // Load the field and group definitions because we need the trigger
  // clause that is stored here. 
  $custom = sumfields_get_custom_field_definitions();

  // We create a trigger sql statement for each table that should
  // have a trigger
  $tables = array();
  $generic_sql = "REPLACE INTO `$table_name` SET ";
  $sql_field_parts = array();
 
  $active_fields = sumfields_get_setting('active_fields', array());

  // Iterate over all our fields, and build out a sql parts array
  while(list($base_column_name, $params) = each($custom_fields)) {
    if(!in_array($base_column_name, $active_fields)) continue;

    $trigger = $custom['fields'][$base_column_name]['trigger_sql'];
    $sql_field_parts[] = '`' . $params['column_name'] . '` = ' . 
      sumfields_sql_rewrite($trigger);
    // Keep track of which tables we need to build triggers for.
    $table = $custom['fields'][$base_column_name]['trigger_table'];
    if(!in_array($table, $tables)) $tables[] = $table;
  } 

  $sql_field_parts[] = 'entity_id = NEW.contact_id;';
  // Iterate over each table that needs a trigger, build the trigger's
  // sql clause.
  while(list(, $table) = each($tables)) {
    $parts = implode(',', $sql_field_parts);
    $sql = $generic_sql . $parts;

    // We want to fire this trigger on insert, update and delete.
    $info[] = array(
      'table' => $table,
      'when' => 'AFTER',
      'event' => 'INSERT',
      'sql' => $sql,
     );
    $info[] = array(
      'table' => $table,
      'when' => 'AFTER',
      'event' => 'UPDATE',
      'sql' => $sql,
    );
    // For delete, we reference OLD.contact_id instead of NEW.contact_id
    $sql = str_replace('NEW.contact_id', 'OLD.contact_id', $sql);
    $info[] = array(
      'table' => $table,
      'when' => 'AFTER',
      'event' => 'DELETE',
      'sql' => $sql,
    );
  }
}

/**
 * Generate calculated fields for all contacts.
 * This function is designed to be run once when
 * the extension is installed or initialized. 
 **/
function sumfields_generate_data_based_on_current_data() {
  // Get the actual table name for summary fields and the actual field names.
  $table_name = _sumfields_get_custom_table_name(); 
  $custom_fields = _sumfields_get_custom_field_parameters();

  // In theory we shouldn't have to truncate the table, but we
  // are doing it just to be sure it's empty.
  $sql = "TRUNCATE TABLE `$table_name`";
  $dao = CRM_Core_DAO::executeQuery($sql);

  // Load the field and group definitions because we need the trigger
  // clause that is stored here. 
  $custom = sumfields_get_custom_field_definitions();

  // Most fields are based on contribution records, so build
  // one big sql INSERT table to populate our new custom with
  // summary info based on existing contribution data.
  $tables = array();
  $generic_sql = "INSERT INTO `$table_name` SELECT ";
  $active_fields = sumfields_get_setting('active_fields', array());
  while(list($base_column_name, $params) = each($custom_fields)) {
    if(!in_array($base_column_name, $active_fields)) continue;
    $table = $custom['fields'][$base_column_name]['trigger_table'];

    // We only handle civicrm_contribution fields, we'll get the
    // other fields later. Insert NULL value which will get updated
    // below.
    if($table != 'civicrm_contribution') {
      $trigger = 'NULL'; 
    }
    else {
      $trigger = $custom['fields'][$base_column_name]['trigger_sql'];
      // We replace NEW.contact_id with t2.contact_id to reflect the difference
      // between the trigger sql statement and the initial sql statement
      // to load the data.
      $trigger = str_replace('NEW.contact_id', 't2.contact_id', $trigger);
      $trigger = sumfields_sql_rewrite($trigger);
    }
    $sql_field_parts['civicrm_contribution'][] = $trigger;
  }
  // The first and second fields inserted should be null and contact_id
  array_unshift($sql_field_parts['civicrm_contribution'], 'contact_id');
  array_unshift($sql_field_parts['civicrm_contribution'], 'NULL');

  $parts = implode(",\n", $sql_field_parts['civicrm_contribution']);
  $sql = $generic_sql . $parts;
  $sql .= ' FROM `civicrm_contribution` AS t2';
  if($table == 'civicrm_contribution') {
    $sql .= ' WHERE t2.contribution_status_id = 1';
  }
  $sql .= ' GROUP BY contact_id';
  $dao = CRM_Core_DAO::executeQuery($sql);

  // Update the table with data from the civicrm_participant table
  // We iterate over every contact_id in the participant table
  // and update them one by one... optimization ideas?
  $sql = "SELECT DISTINCT contact_id FROM civicrm_participant";
  $dao = CRM_Core_DAO::executeQuery($sql);
  while($dao->fetch()) {
    $contact_id = $dao->contact_id;
    reset($custom_fields);
    // Iterate over our custom fields looking for ones using the
    // civicrm_participant table.
    while(list($base_column_name, $column_settings) = each($custom_fields)) {
      if(!in_array($base_column_name, $active_fields)) continue;
      $table = $custom['fields'][$base_column_name]['trigger_table'];
      if($table != 'civicrm_participant') continue;

      $trigger = $custom['fields'][$base_column_name]['trigger_sql'];
      // We replace NEW.contact_id with the value of $contact_id to reflect the difference
      // between the trigger sql statement and the initial sql statement
      // to load the data.
      $trigger = str_replace('NEW.contact_id', '%1', $trigger);
      $params = array( 1 => array($contact_id, 'Integer'));
      $trigger = sumfields_sql_rewrite($trigger);

      // Retrieve the summary value by executing the trigger sql
      $trigger_dao = CRM_Core_DAO::executeQuery($trigger, $params);
      $trigger_dao->fetch();
      if(!property_exists($trigger_dao, 'summary_value')) {
        continue;
      }
      $summary_value = $trigger_dao->summary_value;

      if(empty($summary_value)) continue;
      // Update the summary table with this new value
      // REPLACE INTO will wipe out the existing data, so we
      // have to check if the record exists first...
      $sql = "SELECT id FROM `$table_name` WHERE entity_id = %1";
      $params = array( 1 => array($contact_id, 'Integer'));
      $exists_dao = CRM_Core_DAO::executeQuery($sql, $params);
      $exists_dao->fetch();
      $column_name = $column_settings['column_name'];
      if($exists_dao->N == 0) {
        $sql = "INSERT INTO `$table_name` SET `$column_name` = %1, entity_id = %2";
      }
      else {
        $sql = "UPDATE `$table_name` SET `$column_name` = %1 WHERE entity_id = %2";
      }
      $params = array(
        1 => array($summary_value, 'String'),
        2 => array($contact_id, 'Integer' ),
      );
      CRM_Core_DAO::executeQuery($sql, $params);
    }
  }
}

/**
 * Create custom fields - should be called on enable.
 **/
function sumfields_create_custom_fields_and_table() {
  $session = CRM_Core_Session::singleton();

  // Load the field and group definitions.
  $custom = sumfields_get_custom_field_definitions();
  
  // Create the custom group first.
  $params = array_pop($custom['groups']);
  $params['version'] = 3;
  $result = civicrm_api('CustomGroup', 'create', $params);
  if($result['is_error'] == 1) {
    // Bail. No point in continuing if we can't get the table built.
    return FALSE;
  }
  // We need the id for creating the fields below.
  $value = array_pop($result['values']);
  $custom_group_id = $value['id'];
  
  // Save the info so we can delete it when uninstalling.
  $custom_table_parameters = array(
    'id' => $custom_group_id,
    'table_name' => $value['table_name'],
  );
  sumfields_save_setting('custom_table_parameters', $custom_table_parameters);
  $custom_field_parameters = array();

  // Get an array of fields that the user wants to use.
  $active_fields = sumfields_get_setting('active_fields', array());
  // Now create the fields.
  while(list($name, $field) = each($custom['fields'])) {
    // Skip fields not selected by the user.
    if(!in_array($name, $active_fields)) continue;

    $params = $field;
    $params['version'] = 3;
    $params['custom_group_id'] = $custom_group_id;
    $result = civicrm_api('CustomField', 'create', $params);
    if($result['is_error'] == 1) {
      $session->setStatus("Error creating custom field $name");
      $session->setStatus(print_r($result, TRUE));
      continue;
    }
    $value = array_pop($result['values']);
    $custom_field_parameters[$name] = array(
      'id' => $value['id'],
      'column_name' => $value['column_name']
    );
  }
  sumfields_save_setting('custom_field_parameters', $custom_field_parameters);
  return TRUE;
}

/**
 * Helper function for storing persistant data
 * for this extension.
 **/
function sumfields_save_setting($key, $value) {
  $group = 'Summary Fields';
  CRM_Core_BAO_Setting::setItem($value, $group, $key);
}

/**
 * Helper function for getting persistant data
 * for this extension.
 **/
function sumfields_get_setting($key, $default = NULL) {
  $group = 'Summary Fields';
  $ret = CRM_Core_BAO_Setting::getItem($group, $key);
  if(empty($ret)) return $default;
  return $ret;
}

/**
 * Delete custom fields. All data should be 
 * generated data, so no worry about deleting
 * anything that should be kept.
 **/
function sumfields_delete_custom_fields_and_table() {
  $session = CRM_Core_Session::singleton();
  $custom_field_parameters = _sumfields_get_custom_field_parameters();

  $active_fields = sumfields_get_setting('active_fields', array());
  while(list($key, $field) = each($custom_field_parameters)) {
    // Skip fields not active (they should not have been created so
    // should not exist.
    if(!in_array($key, $active_fields)) continue;

    $params = array(
      'id' => $field['id'],
      'params' => 3,
      'version' => 3
    );
    $result = civicrm_api('CustomField', 'delete', $params);
    if($result['is_error'] == 1) {
      $column_name = $field['column_name'];
      $session->setStatus("Error deleting $column_name.");
      $session->setStatus(print_r($result,TRUE));
    }
  }
  $custom_table_parameters = _sumfields_get_custom_table_parameters();
  $id = $custom_table_parameters['id'];
  $params = array('version' => 3, 'id' => $id);
  $result = civicrm_api('CustomGroup', 'delete', $params);
  if($result['is_error'] == 1) {
    $session->setStatus("Error deleting $table_name.");
  }
}

/**
 * Remove our values from civicrm_setting table
 **/
function sumfields_delete_user_settings() {
  $sql = "DELETE FROM civicrm_setting WHERE group_name = 'Summary Fields'";
  CRM_Core_DAO::executeQuery($sql);
}

/**
 * Helper helper to get just the table name out of 
 * table parameters 
 *
 **/
function _sumfields_get_custom_table_name() {
  $table_info = _sumfields_get_custom_table_parameters(); 
  return $table_info['table_name'];
}

/**
 * Since CiviCRM may give our fields a different name on different
 * sites, we store the actual name and id that was used.
 *
 **/
function _sumfields_get_custom_field_parameters() {
  return sumfields_get_setting('custom_field_parameters', array());
}

/**
 * Since CiviCRM may give our table a different name on different
 * sites, we store the actual name and id that was used.
 *
 **/
function _sumfields_get_custom_table_parameters() {
  return sumfields_get_setting('custom_table_parameters', array());
}

/**
 * Return the $custom array with all the custom field
 * definitions.
 **/
function sumfields_get_custom_field_definitions() {
  static $custom = NULL;
  if(is_null($custom)) {
    // The custom.php file defines the $custom array of field
    // definitions. Only require if necessary.
    require 'custom.php';
  }
  return $custom;
}

/**
 * Initialize all user settings. 
 *
 * The user has the option to choose which fields they want,
 * which contribution types to include, which event types, 
 * etc. When initialize, we choose all available fields and
 * types and let users de-select the ones they don't want. 
 **/
function sumfields_initialize_user_settings() {

  // Which of the available fields does the user want to activate?
  $values = sumfields_get_all_custom_fields();
  sumfields_save_setting('active_fields', $values);

  // Which contribution_type_ids are used to calculate the general contribution
  // summary fields?
  $values = CRM_Contribute_PseudoConstant::contributionType();
  sumfields_save_setting('contribution_type_ids', array_keys($values));

  // Which contribution_type_ids are used to calculate the last membership
  // payment fields?
  sumfields_save_setting('membership_contribution_type_ids', array_keys($values));

  // Which event ids are used when calculating last event attended fields?
  $values = CRM_Event_PseudoConstant::eventType();
  sumfields_save_setting('event_type_ids', array_keys($values));

  // Which participant status ids are used to calculate last event attended
  // fields?
  $values = CRM_Event_PseudoConstant::participantStatus();
  sumfields_save_setting('participant_status_ids', array_keys($values));
}

/**
 * Get all available active fields
 **/
function sumfields_get_all_custom_fields() {
  $custom = sumfields_get_custom_field_definitions();
  $values = array();
  while(list($k) = each($custom['fields'])) {
    $values[] = $k;
  }
  return $values;
}

/**
 * Helper function to setup all data and tables
 **/
function sumfields_initialize_custom_data() {
  if(FALSE === sumfields_create_custom_fields_and_table()) return FALSE;
  sumfields_generate_data_based_on_current_data();
}

/**
 * Helper function to clean up
 **/
function sumfields_deinitialize_custom_data() {
  sumfields_delete_custom_fields_and_table();
}
