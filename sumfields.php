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
  sumfields_initialize_contribution_type_ids();
  sumfields_initialize_active_fields();
  if(FALSE === sumfields_initialize()) return FALSE;
  return _sumfields_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function sumfields_civicrm_disable() {
  sumfields_deinitialize(); 
  sumfields_delete_settings();
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
 * Replace %contribution_type_ids with the actual
 * ids that the user has configured to limit to.
 **/
function sumfields_sql_rewrite_contribution_type_ids($sql) {
  $ids = sumfields_get_setting('contribution_type_ids', array());
  $str_ids = implode(',', $ids);
  return str_replace('%contribution_type_ids', $str_ids, $sql);
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

  // Iterate over all our fields, and build out a sql parts array for each
  // table that needs a trigger.
  while(list($base_column_name, $params) = each($custom_fields)) {
    if(!in_array($base_column_name, $active_fields)) continue;

    $trigger = $custom['fields'][$base_column_name]['trigger_sql'];
    $table = $custom['fields'][$base_column_name]['trigger_table'];
    $sql_field_parts[$table][] = '`' . $params['column_name'] . '` = ' . 
      sumfields_sql_rewrite_contribution_type_ids($trigger);
    // Keep track of which tables we need to build triggers for.
    if(!in_array($table, $tables)) $tables[] = $table;
  } 

  // Iterate over each table that needs a trigger, build the trigger's
  // sql clause.
  while(list(, $table) = each($tables)) {
    $sql_field_parts[$table][] = 'entity_id = NEW.contact_id;';
    $parts = implode(',', $sql_field_parts[$table]);
    $sql = $generic_sql . $parts;

    // We want to fire this trigger on both insert and update.
    $info[] = array(
      'table' => $table,
      'when' => 'AFTER',
      'event' => 'INSERT',
      'sql' => $sql,
     );
    $info[] = array(
      'table' => 'civicrm_contribution',
      'when' => 'AFTER',
      'event' => 'UPDATE',
      'sql' => $sql,
    );
  }
}

/**
 * Generate calculated fields for all contacts.
 * This function is designed to be run once when
 * the extension is installed. 
 **/
function sumfields_generate_data_based_on_current_contributions() {
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

  // We run an INSERT statement for each source table that is
  // going to be triggered. The first field (the id) is NULL.
  $tables = array();
  $generic_sql = "INSERT INTO `$table_name` SELECT ";
  $sql_field_parts = array('NULL');

  $active_fields = sumfields_get_setting('active_fields', array());

  // Iterate over all our fields, and build out a sql parts array for each
  // table that needs a trigger.
  while(list($base_column_name, $params) = each($custom_fields)) {
    if(!in_array($base_column_name, $active_fields)) continue;
    $trigger = $custom['fields'][$base_column_name]['trigger_sql'];
    // We replace NEW.contact_id with t2.contact_id to reflect the difference
    // between the trigger sql statement and the initial sql statement
    // to load the data.
    $trigger = str_replace('NEW.contact_id', 't2.contact_id', $trigger);
    $trigger = sumfields_sql_rewrite_contribution_type_ids($trigger);
    $table = $custom['fields'][$base_column_name]['trigger_table'];
    $sql_field_parts[$table][] = $trigger;
    // Keep track of which tables we need to build triggers for.
    if(!in_array($table, $tables)) $tables[] = $table;
  }

  while(list(,$table) = each($tables)) {
    // The first and second fields inserted should be null and contact_id
    array_unshift($sql_field_parts[$table], 'contact_id');
    array_unshift($sql_field_parts[$table], 'NULL');

    $parts = implode(",\n", $sql_field_parts[$table]);
    $sql = $generic_sql . $parts;
    $sql .= ' FROM `' . $table . '` AS t2';
    if($table == 'civicrm_contribution') {
      $sql .= ' WHERE t2.contribution_status_id = 1';
    }
    $sql .= ' GROUP BY contact_id';
    $dao = CRM_Core_DAO::executeQuery($sql);
  }
}

/**
 * Create custom fields - should be called on enable.
 **/
function sumfields_create_custom_fields_and_table() {
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
function sumfields_delete_settings() {
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
 * Initialize the contribution_type_ids used when 
 * calculating the summary fields. Use all available
 * contribution_type_ids.
 **/
function sumfields_initialize_contribution_type_ids() {
  $values = CRM_Contribute_PseudoConstant::contributionType();
  sumfields_save_setting('contribution_type_ids', array_keys($values));
}

/**
 * Initialize the active fields * with all available fields.
 **/
function sumfields_initialize_active_fields() {
  $values = sumfields_get_all_custom_fields();
  sumfields_save_setting('active_fields', $values);
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
function sumfields_initialize() {

  if(FALSE === sumfields_create_custom_fields_and_table()) return FALSE;
  sumfields_generate_data_based_on_current_contributions();
}

/**
 * Helper function to clean up
 **/
function sumfields_deinitialize() {
  sumfields_delete_custom_fields_and_table();
}
