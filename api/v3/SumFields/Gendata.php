<?php

/**
 * SumFields.Gendata API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_sum_fields_gendata_spec(&$spec) {
  // Nothing in spec. 
}

/**
 * SumFields.Gendata API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_sum_fields_gendata($params) {
  // run_gen_data variable can be set to any of the following:
  // 
  // NULL It has never been run, no need to run
  // scheduled:YYYY-MM-DD HH:MM:SS -> it should be run
  // running:YYYY-MM-DD HH:MM:SS -> it is currently running, and started at the given date/time
  // success:YYYY-MM-DD HH:MM:SS -> It completed successfully on the last run at the given date/time
  // failed:YYYY-MM-DD HH:MM:SS -> It failed on the last run at the given date/time
  //
  $status = $new_status = sumfields_get_setting('run_gen_data', FALSE);
  $date = date('Y-m-d H:i:s');
  $exception = FALSE;
  if (preg_match('/^scheduled:/', $status)) {
    $new_status = 'running:' . $date;
    sumfields_save_setting('run_gen_data', $new_status);

    // Check to see if the new_active_fields setting is set. This means we have to alter the fields
    // from the current setting.
    $new_active_fields = sumfields_get_setting('new_active_fields', NULL);
    if(!is_null($new_active_fields)) {
      if(!sumfields_alter_table()) {
        // If we fail to properly alter the table, bail and record that we had an error.
        $date = date('Y-m-d H:i:s');
        $new_status = 'failed:' . $date;
        $exception = TRUE;
      }
      else {
        // Set new active fields to NULL to indicate that they no longer
        // need to be updated
        sumfields_save_setting('new_active_fields', NULL);
      }
    }
    if(!$exception) {
      if(sumfields_generate_data_based_on_current_data()) {
        // Note: triggers will be rebuilt automatically, calling that here will
        // simply cause them to be rebuilt twice.
        // CRM_Core_DAO::triggerRebuild();
        $date = date('Y-m-d H:i:s');
        $new_status = 'success:' . $date;
      }
      else {
        $date = date('Y-m-d H:i:s');
        $new_status = 'fail:' . $date;
        $exception = TRUE;
      }
    }
  }
  $returnValues = array("Original Status: $status, New Status: $new_status");
  sumfields_save_setting('run_gen_data', $new_status);
  if(!$exception) {
    // Spec: civicrm_api3_create_success($values = 1, $params = array(), $entity = NULL, $action = NULL)
    return civicrm_api3_create_success($returnValues, $params, 'SumFields', 'gendata');
  } else {
    throw new API_Exception(/*errorMessage*/ 'Generating data returned an error.', /*errorCode*/ 1234);
  }
}

