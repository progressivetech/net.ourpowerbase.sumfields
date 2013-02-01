<?php

require_once 'cff.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function cff_civicrm_config(&$config) {
  _cff_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function cff_civicrm_xmlMenu(&$files) {
  _cff_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function cff_civicrm_install() {
  return _cff_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function cff_civicrm_uninstall() {
  return _cff_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function cff_civicrm_enable() {
  if(FALSE === _cff_create_custom_fields_and_table()) return FALSE;
  _cff_generate_data_based_on_current_contributions();
  return _cff_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function cff_civicrm_disable() {
  _cff_delete_custom_fields_and_table();
  _cff_delete_settings();
  return _cff_civix_civicrm_disable();
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
function cff_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _cff_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function cff_civicrm_managed(&$entities) {
  return _cff_civix_civicrm_managed($entities);
}

/**
 * hook_civicrm_triggerInfo()
 *
 * Add triggers for our tables
 **/

function cff_civicrm_triggerInfo(&$info, $tableName) {
  // Our triggers all use custom fields. CiviCRM, when generating
  // custom fields, sometimes gives them different names (appending
  // the id in most cases) to avoid name collisions. 
  //
  // So, we have to retrieve the actual name of each field that is in 
  // use.

  $table_name = _cff_get_custom_table_name(); 
  $custom_fields = _cff_get_custom_field_parameters();
  while(list($column_name, $params) = each($custom_fields)) {
    $name_var = $column_name . '_field';
    // This line creates a variable (e.g. $total_amount_field) that is populated
    // with the name of the total_amount field
    $$name_var = $params['column_name'];
  } 

  // Mamba jamba trigger sql statement that hopefully won't
  // be a performance killer.
  $sql = "REPLACE INTO `$table_name` SET 
    `$total_lifetime_field` = 
    (SELECT IF(SUM(total_amount) IS NULL, 0, SUM(total_amount)) 
    FROM civicrm_contribution WHERE contact_id = NEW.contact_id 
    AND contribution_status_id = 1), 
    `$total_this_year_field` = 
    (SELECT IF(SUM(total_amount) IS NULL, 0, SUM(total_amount)) 
    FROM civicrm_contribution WHERE SUBSTR(receive_date,1,4)=YEAR(curdate()) 
    AND contact_id = NEW.contact_id AND contribution_status_id = 1), 
    `$total_last_year_field` = 
    (SELECT IF(SUM(total_amount) IS NULL, 0, SUM(total_amount)) 
    FROM civicrm_contribution WHERE SUBSTR(receive_date,1,4)=YEAR(curdate())-1 
    AND contact_id = NEW.contact_id AND contribution_status_id = 1), 
    `$amount_last_field` = 
    (SELECT IF(total_amount IS NULL, 0, total_amount) 
    FROM civicrm_contribution WHERE contact_id = NEW.contact_id 
    AND contribution_status_id = 1 ORDER BY receive_date DESC LIMIT 1), 
    `$date_last_field` = 
    (SELECT MAX(receive_date) FROM civicrm_contribution 
    WHERE contact_id = NEW.contact_id AND contribution_status_id = 1), 
    `$date_first_field` = 
    (SELECT MIN(receive_date) FROM civicrm_contribution 
    WHERE contact_id = NEW.contact_id AND contribution_status_id = 1), 
    `$largest_field` = 
    (SELECT IF(MAX(total_amount) IS NULL, 0, MAX(total_amount)) 
    FROM civicrm_contribution 
    WHERE contact_id = NEW.contact_id AND contribution_status_id = 1), 
    `$total_number_field` = 
    (SELECT IF(COUNT(id) IS NULL, 0, COUNT(id)) FROM civicrm_contribution 
    WHERE contact_id = NEW.contact_id AND contribution_status_id = 1), 
    entity_id = NEW.contact_id;";

  // We want to fire this trigger on both insert and update.
  $info[] = array(
    'table' => 'civicrm_contribution',
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

/**
 * Generate calculated fields for all contacts.
 * This function is designed to be run once when
 * the extension is installed. 
 **/
function _cff_generate_data_based_on_current_contributions() {
  // Get the actual table name for calculated fundraising fields
  $table_name = _cff_get_custom_table_name(); 

  // In theory we shouldn't have to truncate the table, but we
  // are doing it just to be sure it's empty.
  $sql = "TRUNCATE TABLE `$table_name`";
  $dao = CRM_Core_DAO::executeQuery($sql);

  // NOTE: MySQL ignores the AS spec - the rows are assigned based on order, not 
  // based on column name. I'm keeping the AS statements for reference so we know
  // which calcs are for which fields - however keep in mind, the column names may
  // not properly match the actual column names (which may have IDs appended to them).
  // If we change the order in which the columns are created, we'll need to change
  // the order here too.
  $sql = "INSERT INTO `$table_name` 
    SELECT
      NULL,
      contact_id AS entity_id,
      SUM(total_amount) AS total_lifetime,
      (SELECT IF(SUM(total_amount) IS NULL,0,SUM(total_amount))
        FROM `civicrm_contribution` AS t2 
        WHERE SUBSTR(receive_date,1,4)=YEAR(curdate()) AND t2.contact_id=t1.contact_id AND t2.contribution_status_id = 1)
        AS total_this_year,
      (SELECT IF(SUM(total_amount)IS NULL,0,SUM(total_amount))
        FROM `civicrm_contribution` AS t2
        WHERE SUBSTR(receive_date,1,4)=YEAR(curdate())-1 AND t2.contact_id=t1.contact_id and t2.contribution_status_id = 1)
        AS total_last_year,
      (SELECT total_amount 
        FROM `civicrm_contribution` AS t2
        WHERE t2.contribution_status_id = 1 AND t2.contact_id=t1.contact_id 
        ORDER BY t2.receive_date DESC LIMIT 1)
        AS amount_last,
      MAX(receive_date) AS date_last,
      MIN(receive_date) AS date_first,
      MAX(total_amount) AS largest,
      COUNT(id) AS total_number
    FROM `civicrm_contribution` AS t1 
    WHERE contribution_status_id = 1
    GROUP BY entity_id";
  $dao = CRM_Core_DAO::executeQuery($sql);
}

/**
 * Create custom fields - should be called on enable.
 **/
function _cff_create_custom_fields_and_table() {
  // Load the field and group definitions.
  require_once('custom.php');
  
  // Create the custom group first.
  $params = $custom['groups']['fundraising_summary'];
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
  _cff_save_setting('custom_table_parameters', $custom_table_parameters);
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
  _cff_save_setting('custom_field_parameters', $custom_field_parameters);
  return TRUE;
}

/**
 * Helper function for storing persistant data
 * for this extension.
 **/
function _cff_save_setting($key, $value) {
  $group = 'Calculated Fundraising Fields';
  CRM_Core_BAO_Setting::setItem($value, $group, $key);
}

/**
 * Helper function for getting persistant data
 * for this extension.
 **/
function _cff_get_setting($key) {
  $group = 'Calculated Fundraising Fields';
  return CRM_Core_BAO_Setting::getItem($group, $key);
}

/**
 * Delete custom fields. All data should be 
 * generated data, so no worry about deleting
 * anything that should be kept.
 **/
function _cff_delete_custom_fields_and_table() {
  $session = CRM_Core_Session::singleton();
  $custom_field_parameters = _cff_get_custom_field_parameters();
  
  while(list($key, $field) = each($custom_field_parameters)) {
    $params = array(
      'id' => $field['id'],
      'params' => 3
    );
    $result = civicrm_api('CustomField', 'delete', $params);
    if($result['is_error'] == 1) {
      $column_name = $field['column_name'];
      $session->setStatus("Error deleting $column_name.");
    }
  }
  $custom_table_parameters = _cff_get_custom_table_parameters();
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
function _cff_delete_settings() {
  $sql = "DELETE FROM civicrm_setting WHERE group_name = 'Calculated Fundraising Fields'";
  CRM_Core_DAO::executeQuery($sql);
}

/**
 * Helper helper to get just the table name out of 
 * table parameters 
 *
 **/
function _cff_get_custom_table_name() {
  $table_info = _cff_get_custom_table_parameters(); 
  return $table_info['table_name'];
}

/**
 * Since CiviCRM may give our fields a different name on different
 * sites, we store the actual name and id that was used.
 *
 **/
function _cff_get_custom_field_parameters() {
  return _cff_get_setting('custom_field_parameters');
}

/**
 * Since CiviCRM may give our table a different name on different
 * sites, we store the actual name and id that was used.
 *
 **/
function _cff_get_custom_table_parameters() {
  return _cff_get_setting('custom_table_parameters');
}


