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
  if(FALSE === _sumfields_create_custom_fields_and_table()) return FALSE;
  _sumfields_generate_data_based_on_current_contributions();
  return _sumfields_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function sumfields_civicrm_disable() {
  _sumfields_delete_custom_fields_and_table();
  _sumfields_delete_settings();
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
function sumfields_civicrm_sql_rewrite_contribution_type_ids($sql) {
  $ids = sumfields_civicrm_get_contribution_type_ids();
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
  // clause that is stored here. custom.php defines a variable called
  // $custom. This can (and will need to) be required more than since this 
  // function is called more than once. 
  require 'custom.php';

  // We create a trigger sql statement for each table that should
  // have a trigger
  $tables = array();
  $generic_sql = "REPLACE INTO `$table_name` SET ";
  $sql_field_parts = array();

  // Iterate over all our fields, and build out a sql parts array for each
  // table that needs a trigger.
  while(list($base_column_name, $params) = each($custom_fields)) {
    $trigger = $custom['fields'][$base_column_name]['trigger_sql'];
    $table = $custom['fields'][$base_column_name]['trigger_table'];
    $sql_field_parts[$table][] = '`' . $params['column_name'] . '` = ' . 
      sumfields_civicrm_sql_rewrite_contribution_type_ids($trigger);
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
function _sumfields_generate_data_based_on_current_contributions() {
  // Get the actual table name for summary fields and the actual field names.
  $table_name = _sumfields_get_custom_table_name(); 
  $custom_fields = _sumfields_get_custom_field_parameters();

  // In theory we shouldn't have to truncate the table, but we
  // are doing it just to be sure it's empty.
  $sql = "TRUNCATE TABLE `$table_name`";
  $dao = CRM_Core_DAO::executeQuery($sql);

  // Load the field and group definitions because we need the trigger
  // clause that is stored here. custom.php defines a variable called
  // $custom. This can (and will need to) be required more than since this 
  // function is called more than once. 
  require 'custom.php';

  // We run an INSERT statement for each source table that is
  // going to be triggered. The first field (the id) is NULL.
  $tables = array();
  $generic_sql = "INSERT INTO `$table_name` SELECT ";
  $sql_field_parts = array('NULL');

  // Iterate over all our fields, and build out a sql parts array for each
  // table that needs a trigger.
  while(list($base_column_name, $params) = each($custom_fields)) {
    $trigger = $custom['fields'][$base_column_name]['trigger_sql'];
    // We replace NEW.contact_id with t2.contact_id to reflect the difference
    // between the trigger sql statement and the initial sql statement
    // to load the data.
    $trigger = str_replace('NEW.contact_id', 't2.contact_id', $trigger);
    $trigger = sumfields_civicrm_sql_rewrite_contribution_type_ids($trigger);
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
function _sumfields_create_custom_fields_and_table() {
  // Load the field and group definitions.
  require_once('custom.php');
  
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
  _sumfields_save_setting('custom_table_parameters', $custom_table_parameters);
  $custom_field_parameters = array();

  // Now create the fields.
  while(list($name, $field) = each($custom['fields'])) {
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
  _sumfields_save_setting('custom_field_parameters', $custom_field_parameters);
  return TRUE;
}

/**
 * Helper function for storing persistant data
 * for this extension.
 **/
function _sumfields_save_setting($key, $value) {
  $group = 'Summary Fields';
  CRM_Core_BAO_Setting::setItem($value, $group, $key);
}

/**
 * Helper function for getting persistant data
 * for this extension.
 **/
function _sumfields_get_setting($key) {
  $group = 'Summary Fields';
  return CRM_Core_BAO_Setting::getItem($group, $key);
}

/**
 * Delete custom fields. All data should be 
 * generated data, so no worry about deleting
 * anything that should be kept.
 **/
function _sumfields_delete_custom_fields_and_table() {
  $session = CRM_Core_Session::singleton();
  $custom_field_parameters = _sumfields_get_custom_field_parameters();
  
  while(list($key, $field) = each($custom_field_parameters)) {
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
function _sumfields_delete_settings() {
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
  return _sumfields_get_setting('custom_field_parameters');
}

/**
 * Since CiviCRM may give our table a different name on different
 * sites, we store the actual name and id that was used.
 *
 **/
function _sumfields_get_custom_table_parameters() {
  return _sumfields_get_setting('custom_table_parameters');
}

/**
 * Return the contribution types that should be used in the 
 * contribution summary fields.
 **/
function sumfields_civicrm_get_contribution_type_ids() {
  return array(1);
}
