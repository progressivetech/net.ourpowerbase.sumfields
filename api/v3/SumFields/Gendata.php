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
  // NULL: It has never been run, no need to run
  // Scheduled: it should be run
  // Running since YYYY-MM-DD HH:MM:SS: it is currently running
  // Success YYYY-MM-DD HH:MM:SS: It completed successfully
  // Fail YYYY-MM-DD HH:MM:SS: It failed. 
  //
  $status = $new_status = sumfields_get_setting('run_gen_data', FALSE);
  $date = date('Y-m-d H:i:s');
  $exception = FALSE;
  if ($status == 'Scheduled') {
    $new_status = 'Running since ' . $date;
    sumfields_set_setting('run_gen_data', $new_status);
    if(sumfields_generate_data_based_on_current_data()) {
       // Now rebuild the trigers
      CRM_Core_DAO::triggerRebuild();
      $date = date('Y-m-d H:i:s');
      $new_status = 'Success ' . $date;
    }
    else {
      $date = date('Y-m-d H:i:s');
      $new_status = 'FAIL ' . $date;
      $exception = TRUE;
    }
  }
  $returnValues = array("Original Status: $status, New Status: $new_status");
  sumfields_set_setting('run_gen_data', $new_status);
  if(!$exception) {
    // Spec: civicrm_api3_create_success($values = 1, $params = array(), $entity = NULL, $action = NULL)
    return civicrm_api3_create_success($returnValues, $params, 'SumFields', 'gendata');
  } else {
    throw new API_Exception(/*errorMessage*/ 'Generating data returned an error.', /*errorCode*/ 1234);
  }
}

