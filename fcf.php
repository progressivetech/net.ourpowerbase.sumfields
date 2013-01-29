<?php

require_once 'fcf.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function fcf_civicrm_config(&$config) {
  _fcf_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function fcf_civicrm_xmlMenu(&$files) {
  _fcf_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function fcf_civicrm_install() {
  _fcf_create_custom_fields();
  return _fcf_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function fcf_civicrm_uninstall() {
  _fcf_delete_custom_fields_if_empty();
  return _fcf_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function fcf_civicrm_enable() {
  return _fcf_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function fcf_civicrm_disable() {
  return _fcf_civix_civicrm_disable();
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
function fcf_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _fcf_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function fcf_civicrm_managed(&$entities) {
  return _fcf_civix_civicrm_managed($entities);
}

/**
 * hook_civicrm_triggerInfo()
 *
 * Add triggers for our tables
 **/

function fcf_civicrm_triggerInfo(&$info, $tableName) {
  // Our triggers all use custom fields. CiviCRM, when generating
  // custom fields, sometimes gives them different names (appending
  // the id in most cases) to avoid name collisions. 
  //
  // So, we have to retrieve the actual name of each field that is in 
  // use.

  // Get the actual table name for calculated fundraising fields
  $calc_fr_table_base = 'calculated_fundraising_fields';
  $calc_fr_table_info = _fcf_get_custom_table_info($calc_fr_table_base);
  list($calc_fr_table_id, $calc_fr_table_name) = $calc_fr_table_info; 

  // Iterate over all the fields in this table
  $custom_fields = _fcf_get_custom_field_definitions();
  while(list($column_name) = each($custom_fields['Calculated_Fundraising_Fields'])) {
    $name_var = $column_name . '_field';
    $field_info = _fcf_get_custom_field_info($column_name, $calc_fr_table_id);

    // This line creates a variable (e.g. $total_amount_field) that is populated
    // with the name of the total_amount field
    list($id, $$name_var) = $field_info;
  } 

  // Mamba jamba sql trigger sql statement that hopefully won't
  // be a performance killer.
  $sql = "REPLACE INTO `$calc_fr_table_name` SET 
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
 * Create custom fields - should be called on install.
 **/
function _fcf_create_custom_fields() {
  $xml_file = __DIR__ . '/CustomFields.xml';
  require_once 'CRM/Utils/Migrate/Import.php';
  $import = new CRM_Utils_Migrate_Import();
  $import->run($xml_file);
}

/**
 * Delete custom fields if there are not 
 * valid records
 **/
function _fcf_delete_custom_fields_if_empty() {
  $custom_fields = _fcf_get_custom_field_definitions();
  while(list($table, $fields) = each($custom_fields)) {
    $table_info = _fcf_get_custom_table_info($table);
    list($table_id, $table_name) = $table_info;
    $base_sql = "SELECT count(c.id) FROM civicrm_contact c JOIN 
    $table_name cv ON c.id = cv.entity_id WHERE is_deleted = 0";

    $delete_table = TRUE;
    while(list($field, $label) = each($fields)) {
      $field_info = _fcf_get_custom_field_info($field, $table_id);      
      list($column_name, $column_id) = $field_info; 
      $active_sql = $base_sql . " AND `$column_name` IS NOT NULL AND `$column_name` != ''";
      $dao = CRM_Core_DAO::executeQuery($active_sql);
      $dao->fetch();
      $params = array('version' => 3, 'id' => $column_id);
      if($dao->_N == 0) {
        $result = civicrm_api('CustomField', 'delete', $params);
      } 
      else {
        $delete_table = FALSE;
        $session = CRM_Core_Session::singleton();
        $session->setStatus("$column_name contains data, not removed.");
      } 
    }
    if($delete_table) {
      $params = array('version' => 3, 'id' => $table_id);
      civicrm_api('CustomGroup', 'delete', $params);
    }
  }
}


/**
 * Create an array of fields and field definitions from
 * the xml file
 **/

function _fcf_get_custom_field_definitions() {
  $xml_file = __DIR__ . '/CustomFields.xml';
  $xml = file_get_contents($xml_file);
  $data = new SimpleXMLElement($xml);

  // Rebuild data into a more useful array
  $custom_fields = array();
  foreach($data as $elements) {
    foreach($elements as $key => $element) {
      if($key == 'CustomGroup') {
        $group_name = (string) $element->name; 
        if(!empty($group_name)) {
          if(!array_key_exists($group_name, $custom_fields)) {
            $custom_fields[$group_name] = array();
          }
        }
      } 
      else {
        $custom_group_name = (string) $element->custom_group_name;
        $column_name = (string) $element->column_name;
        $label = (string) $element->label;
        $custom_fields[$custom_group_name][$column_name] = $label;
      }
    }
  }
  return $custom_fields;
}


/**
 * Since CiviCRM may give our table a different name on different
 * sites, we need to search for the actual name.
 *
 * @param name_like - the base table name to look for
 *
 * @return array - two element array, the first element is the
 * id and the second is the table name.
 *
 **/
function _fcf_get_custom_table_info($name_like) {
  $sql = "SELECT id, table_name FROM civicrm_custom_group WHERE table_name 
      LIKE %1";
  $params = array(1 => array('civicrm_value_' . $name_like . '%', 'String'));
  $dao = CRM_Core_DAO::executeQuery($sql, $params);
  $dao->fetch();
  if(empty($dao->table_name)) {
    // bail...
    echo "bailing... $sql $name_like\n";
    return FALSE;
  }
  return array(intval($dao->id), addslashes($dao->table_name));
}

/**
 * Since CiviCRM may give our fields a different name on different
 * sites, we need to search for the actual name.
 *
 * @param name_like - the base field name to look for
 *
 * @return array - two element array, the first element is the
 * id and the second is the column name.
 *
 **/
function _fcf_get_custom_field_info($name_like, $option_group_id = NULL) {
  $sql = "SELECT id, column_name FROM civicrm_custom_field WHERE 
    column_name LIKE %1";
  $params = array(1 => array($name_like . '%', 'String'));
  if(!is_null($option_group_id)) {
    $sql .= " AND custom_group_id = %2";
    $params[2] = array($option_group_id, 'Integer');
  }
  $dao = CRM_Core_DAO::executeQuery($sql, $params);
  
  $dao->fetch();
  if(empty($dao->column_name)) {
    return FALSE;
  }
  return array($dao->id, $dao->column_name);
}
