<?php
use CRM_Sumfields_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Sumfields_Upgrader extends CRM_Sumfields_Upgrader_Base {

  /**
   * Re-build triggers and re-generate data.
   *
   * Since version 4.0.0 now tracks contributions by line items, we need
   * to rebuild triggers and rebuild data to ensure we are current. 
   *
   * @return TRUE on success
   * @throws Exception
   **/
  public function upgrade_4000() {
    // Only trigger rebuild if we are being upgraded (e.g. we have rebuilt before)
    if (sumfields_get_setting('generate_schema_and_data', FALSE) != FALSE) {
      $this->ctx->log->info('Planning update 4000'); // PEAR Log interface
      $this->addTask(E::ts('Regenerate Data'), 'regenerateData');
      $this->addTask(E::ts('Rebuild Triggers'), 'triggerRebuild');
    }
    return TRUE;
  }

  public function triggerRebuild() { 
    CRM_Core_DAO::triggerRebuild();
    return TRUE;
  }

  public function regenerateData() { 
    return sumfields_generate_data_based_on_current_data();
  }
}
