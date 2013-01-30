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
  _cff_create_custom_fields();
  _cff_generate_data_based_on_current_contributions();
  return _cff_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function cff_civicrm_uninstall() {
  _cff_delete_custom_fields();
  return _cff_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function cff_civicrm_enable() {
  return _cff_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function cff_civicrm_disable() {
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

  // Get the actual table name for calculated fundraising fields
  $calc_fr_table_base = 'calculated_fundraising_fields';
  $calc_fr_table_info = _cff_get_custom_table_info($calc_fr_table_base);
  list($calc_fr_table_id, $calc_fr_table_name) = $calc_fr_table_info; 

  // Iterate over all the fields in this table
  $custom_fields = _cff_get_custom_field_definitions();
  while(list($column_name) = each($custom_fields['Calculated_Fundraising_Fields'])) {
    $name_var = $column_name . '_field';
    $field_info = _cff_get_custom_field_info($column_name, $calc_fr_table_id);

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
 * Generate calculated fields for all contacts.
 * This function is designed to be run once when
 * the extension is installed. 
 **/
function _cff_generate_data_based_on_current_contributions() {
  $calc_fr_table_base = 'calculated_fundraising_fields';
  $calc_fr_table_info = _cff_get_custom_table_info($calc_fr_table_base);
  list($id, $table_name) = $calc_fr_table_info; 

  $sql = "TRUNCATE TABLE `$table_name`";
  $dao = CRM_Core_DAO::executeQuery($sql);

  // NOTE: MySQL ignores the AS spec - the rows are assigned based on order, not 
  // based on column name. I'm keeping the AS statements for reference so we know
  // which calcs are for which fields - however keep in mind, the column names may
  // not properly match the actual column names (which will have IDs appended to them).
  $sql = "INSERT INTO `$table_name` 
    SELECT
      NULL,
      contact_id AS entity_id,
      SUM(total_amount) AS total_lifetime,
      (SELECT CASE WHEN SUM(total_amount) IS NULL THEN 0 ELSE SUM(total_amount) END 
        FROM `civicrm_contribution` AS t2 
        WHERE SUBSTR(receive_date,1,4)=YEAR(curdate()) AND t2.contact_id=t1.contact_id AND t2.contribution_status_id = 1)
        AS total_this_year,
      (SELECT CASE WHEN SUM(total_amount)IS NULL THEN 0 ELSE SUM(total_amount) END 
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
 * Create custom fields - should be called on install.
 **/
function _cff_create_custom_fields() {
  $xml_file = __DIR__ . '/CustomFields.xml';
  require_once 'CRM/Utils/Migrate/Import.php';
  $import = new CRM_Utils_Migrate_Import();
  $import->run($xml_file);
}

/**
 * Delete custom fields. All data should be 
 * generated data, so no worry about deleting
 * anything that should be kept
 **/
function _cff_delete_custom_fields() {
  $session = CRM_Core_Session::singleton();
  $custom_fields = _cff_get_custom_field_definitions();
  while(list($table, $fields) = each($custom_fields)) {
    $table_info = _cff_get_custom_table_info($table);
    list($table_id, $table_name) = $table_info;
    while(list($field, $label) = each($fields)) {
      $field_info = _cff_get_custom_field_info($field, $table_id);      
      list($column_name, $column_id) = $field_info; 
      $result = civicrm_api('CustomField', 'delete', $params);
      if($result['is_error'] == 1) {
        $session->setStatus("Error deleting $column_name.");
      }
    }
    $params = array('version' => 3, 'id' => $table_id);
    $result = civicrm_api('CustomGroup', 'delete', $params);
    if($result['is_error'] == 1) {
      $session->setStatus("Error deleting $table_name.");
    }
  }
}


/**
 * Create an array of fields and field definitions from
 * the xml file
 **/

function _cff_get_custom_field_definitions() {
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
function _cff_get_custom_table_info($name_like) {
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
function _cff_get_custom_field_info($name_like, $option_group_id = NULL) {
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
