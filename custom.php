<?php

/**
 * @file custom.php
 *
 * This file defines the summary fields that will be made available
 * on your site. If you want to add your own summary fields, see the
 * README.md file for information on how you can use a hook to add your
 * own definitions in your own extension.
 *
 * Defining a summary field requires a specially crafted sql query that
 * can be used both in the definition of a SQL trigger and also can be
 * used to create a query that initializes the summary fields for your
 * existing records.
 *
 * In addition, the name and trigger table have to be defined, as well as
 * details on how the field should be displayed.
 *
 * Since all summary fields are totaled for a given contact, this extension
 * expects the table that triggers a summary field to be calculated to have
 * contact_id as one of the fields.
 *
 * However, if that is not the case (for example, civicrm_line_item does
 * not have contact_id, but it does have contribution_id which then
 * leads to civicrm_contribution which does have contact_id), you can
 * tell sumfields how to calculate the contact_id using the 'tables'
 * array of data.
 *
 **/


/**
 * Define a few trigger sql queries first - because they need to be
 * referenced first for a total number and a second time for the
 * percent.
 **/
$event_attended_trigger_sql =
  '(SELECT COUNT(e.id) AS summary_value FROM civicrm_participant t1 JOIN civicrm_event e ON
  t1.event_id = e.id WHERE t1.contact_id = NEW.contact_id AND t1.status_id IN (%participant_status_ids)
  AND e.event_type_id IN (%event_type_ids) AND t1.is_test = 0)';
$event_total_trigger_sql =
 '(SELECT COUNT(t1.id) AS summary_value FROM civicrm_participant t1 JOIN civicrm_event e ON
 t1.event_id = e.id WHERE contact_id = NEW.contact_id AND e.event_type_id IN (%event_type_ids) AND
 t1.is_test = 0)';
$event_total_trigger_sql_null =
 '(SELECT COUNT(t1.id) AS summary_value FROM civicrm_participant t1 JOIN civicrm_event e ON
 t1.event_id = e.id WHERE e.event_type_id IN (%event_type_ids) AND t1.is_test = 0 GROUP BY contact_id
 HAVING contact_id = NEW.contact_id)';
$event_noshow_trigger_sql =
  '(SELECT COUNT(e.id) AS summary_value FROM civicrm_participant t1 JOIN civicrm_event e ON
  t1.event_id = e.id WHERE t1.contact_id = NEW.contact_id AND t1.status_id IN (%participant_noshow_status_ids)
  AND e.event_type_id IN (%event_type_ids) AND t1.is_test = 0)';

$custom = [
  'groups' => [
    'summary_fields' => [
      'name' => 'Summary_Fields',
      'title' => ts('Summary Fields', ['domain' => 'net.ourpowerbase.sumfields']),
      'extends' => 'Contact',
      'style' => 'Tab',
      'collapse_display' => '0',
      'help_pre' => '',
      'help_post' => '',
      'weight' => '30',
      'is_active' => '1',
      'is_multiple' => '0',
      'collapse_adv_display' => '0',
      'optgroup' => 'fundraising',
    ],
  ],
  // Any trigger table that does not have contact_id should be listed here, along with
  // a sql statement that can be used to calculate the contact_id from a field that is
  // in the table. You should also specify the trigger_field - the field in the table
  // that will help you determine the contact_id, and also a JOIN statement to use
  // when initializing the data.
  'tables' => [
    'civicrm_line_item' => [
      'calculated_contact_id' => '(SELECT contact_id FROM civicrm_contribution WHERE id = NEW.contribution_id)',
      'trigger_field' => 'contribution_id',
      'initialize_join' => 'JOIN civicrm_contribution AS c ON trigger_table.contribution_id = c.id',
    ],
  ],
  'fields' => [
    'contribution_total_lifetime' => [
      'label' => ts('Total Lifetime Contributions', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '10',
      'text_length' => '32',
      'trigger_sql' => '(SELECT IF(SUM(line_total) IS NULL, 0, SUM(line_total))
      FROM civicrm_contribution t1 JOIN
      civicrm_line_item t2 ON t1.id = t2.contribution_id
      WHERE t1.contact_id = (SELECT contact_id FROM civicrm_contribution cc WHERE cc.id = NEW.contribution_id) AND
      t1.contribution_status_id = 1 AND t1.is_test = 0 AND
      t2.financial_type_id IN (%financial_type_ids))',
      'trigger_table' => 'civicrm_line_item',
      'optgroup' => 'fundraising',
    ],
    'contribution_total_lifetime_simplified' => [
      'label' => ts('Total Lifetime Contributions (Simplified)', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '10',
      'text_length' => '32',
      'trigger_sql' => '(SELECT IF(SUM(total_amount) IS NULL, 0, SUM(total_amount))
      FROM civicrm_contribution t1 WHERE t1.contact_id = NEW.contact_id AND t1.is_test = 0 
      AND t1.contribution_status_id = 1 AND t1.financial_type_id IN (%financial_type_ids))',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ],
    'contribution_total_this_year' => [
      'label' => ts('Total Contributions this Fiscal Year', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '15',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(line_total),0)
      FROM civicrm_contribution t1
      JOIN civicrm_line_item t2 ON t1.id = t2.contribution_id
      WHERE CAST(receive_date AS DATE) BETWEEN "%current_fiscal_year_begin"
      AND "%current_fiscal_year_end" AND t1.contact_id = (SELECT contact_id FROM civicrm_contribution cc WHERE cc.id = NEW.contribution_id) AND
      t1.contribution_status_id = 1 AND t2.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_line_item',
      'optgroup' => 'fundraising',
    ],
    'contribution_total_this_year_simplified' => [
      'label' => ts('Total Contributions this Fiscal Year (Simplified)', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '15',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(total_amount),0)
      FROM civicrm_contribution t1 WHERE CAST(receive_date AS DATE) BETWEEN "%current_fiscal_year_begin"
      AND "%current_fiscal_year_end" AND t1.contact_id = NEW.contact_id AND
      t1.contribution_status_id = 1 AND t1.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ],
    'contribution_total_twelve_months' => [
      'label' => ts('Total Contributions in the Last 12 Months', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '20',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(line_total),0)
      FROM civicrm_contribution t1
      JOIN civicrm_line_item t2 ON t1.id = t2.contribution_id
      WHERE CAST(receive_date AS DATE) BETWEEN DATE_SUB(NOW(), INTERVAL 12 MONTH) AND NOW()
      AND t1.contact_id = (SELECT contact_id FROM civicrm_contribution cc WHERE cc.id = NEW.contribution_id) AND
      t1.contribution_status_id = 1 AND t2.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_line_item',
      'optgroup' => 'fundraising',
    ],
    'contribution_total_twelve_months_simplified' => [
      'label' => ts('Total Contributions in the Last 12 Months (Simplified)', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '20',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(total_amount),0)
      FROM civicrm_contribution t1 WHERE CAST(receive_date AS DATE) BETWEEN DATE_SUB(NOW(), INTERVAL 12 MONTH) AND NOW()
      AND t1.contact_id = NEW.contact_id AND
      t1.contribution_status_id = 1 AND t1.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ],

    'contribution_total_deductible_this_year' => [
      'label' => ts('Total Deductible Contributions this Fiscal Year', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '25',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(line_total),0) - COALESCE(SUM(qty * COALESCE(t3.non_deductible_amount, 0)), 0)
      FROM civicrm_contribution t1 JOIN civicrm_financial_type t2 ON
      t1.financial_type_id = t2.id AND is_deductible = 1
      JOIN civicrm_line_item t3 ON t1.id = t3.contribution_id
      WHERE CAST(receive_date AS DATE) BETWEEN "%current_fiscal_year_begin" AND
      "%current_fiscal_year_end" AND t1.contact_id IN (SELECT contact_id FROM civicrm_contribution cc WHERE cc.id = NEW.contribution_id) AND
      t1.contribution_status_id = 1 AND t3.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_line_item',
      'optgroup' => 'fundraising',
    ],
    'contribution_total_deductible_this_year_simplified' => [
      'label' => ts('Total Deductible Contributions this Fiscal Year (Simplified)', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '25',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(total_amount),0)
      FROM civicrm_contribution t1 JOIN civicrm_financial_type t2 ON
      t1.financial_type_id = t2.id AND is_deductible = 1
      WHERE CAST(receive_date AS DATE) BETWEEN "%current_fiscal_year_begin" AND
      "%current_fiscal_year_end" AND t1.contact_id = NEW.contact_id AND
      t1.contribution_status_id = 1 AND t1.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ],
    'contribution_total_last_year' => [
      'label' => ts('Total Contributions last Fiscal Year', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '30',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(line_total),0)
      FROM civicrm_contribution t1
      JOIN civicrm_line_item t2 ON t1.id = t2.contribution_id
      WHERE CAST(receive_date AS DATE) BETWEEN "%last_fiscal_year_begin"
      AND "%last_fiscal_year_end" AND t1.contact_id = (SELECT contact_id FROM civicrm_contribution cc WHERE cc.id = NEW.contribution_id) AND
      t1.contribution_status_id = 1 AND t2.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_line_item',
      'optgroup' => 'fundraising',
    ],
    'contribution_total_last_year_simplified' => [
      'label' => ts('Total Contributions last Fiscal Year (Simplified)', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '30',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(total_amount),0)
      FROM civicrm_contribution t1 WHERE CAST(receive_date AS DATE) BETWEEN "%last_fiscal_year_begin"
      AND "%last_fiscal_year_end" AND t1.contact_id = NEW.contact_id AND
      t1.contribution_status_id = 1 AND t1.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ],
    'contribution_total_deductible_last_year' => [
      'label' => ts('Total Deductible Contributions last Fiscal Year', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '35',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(line_total),0) - COALESCE(SUM(qty * COALESCE(t3.non_deductible_amount, 0)), 0)
      FROM civicrm_contribution t1 JOIN civicrm_financial_type t2 ON
      t1.financial_type_id = t2.id AND is_deductible = 1
      JOIN civicrm_line_item t3 ON t1.id = t3.contribution_id
      WHERE CAST(receive_date AS DATE) BETWEEN "%last_fiscal_year_begin" AND
      "%last_fiscal_year_end" AND t1.contact_id IN (SELECT contact_id FROM civicrm_contribution cc WHERE cc.id = NEW.contribution_id) AND
      t1.contribution_status_id = 1 AND t3.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_line_item',
      'optgroup' => 'fundraising',
    ],
    'contribution_total_deductible_last_year_simplified' => [
      'label' => ts('Total Deductible Contributions last Fiscal Year (Simplified)', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '35',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(total_amount),0)
      FROM civicrm_contribution t1 JOIN civicrm_financial_type t2 ON
      t1.financial_type_id = t2.id AND is_deductible = 1
      WHERE CAST(receive_date AS DATE) BETWEEN "%last_fiscal_year_begin" AND
      "%last_fiscal_year_end" AND t1.contact_id = NEW.contact_id AND
      t1.contribution_status_id = 1 AND t1.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ],
    'contribution_total_year_before_last' => [
      'label' => ts('Total Contributions Fiscal Year Before Last', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '40',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(line_total),0)
      FROM civicrm_contribution t1
      JOIN civicrm_line_item t2 ON t1.id = t2.contribution_id
      WHERE CAST(receive_date AS DATE) BETWEEN "%year_before_last_fiscal_year_begin"
      AND "%year_before_last_fiscal_year_end" AND t1.contact_id = (SELECT contact_id FROM civicrm_contribution cc WHERE cc.id = NEW.contribution_id) AND
      t1.contribution_status_id = 1 AND t2.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_line_item',
      'optgroup' => 'fundraising',
    ],
    'contribution_total_year_before_last_simplified' => [
      'label' => ts('Total Contributions Fiscal Year Before Last (Simplified)', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '40',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(total_amount),0)
      FROM civicrm_contribution t1 WHERE CAST(receive_date AS DATE) BETWEEN "%year_before_last_fiscal_year_begin"
      AND "%year_before_last_fiscal_year_end" AND t1.contact_id = NEW.contact_id AND
      t1.contribution_status_id = 1 AND t1.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ],
    'contribution_total_deductible_year_before_last_year' => [
      'label' => ts('Total Deductible Contributions Fiscal Year Before Last', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '45',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(line_total),0) - COALESCE(SUM(qty * COALESCE(t3.non_deductible_amount, 0)), 0)
      FROM civicrm_contribution t1 JOIN civicrm_financial_type t2 ON
      t1.financial_type_id = t2.id AND is_deductible = 1
      JOIN civicrm_line_item t3 ON t1.id = t3.contribution_id
      WHERE CAST(receive_date AS DATE) BETWEEN "%year_before_last_fiscal_year_begin" AND
      "%year_before_last_fiscal_year_end" AND t1.contact_id IN (SELECT contact_id FROM civicrm_contribution cc WHERE cc.id = NEW.contribution_id) AND
      t1.contribution_status_id = 1 AND t3.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_line_item',
      'optgroup' => 'fundraising',
    ],
    'contribution_total_deductible_year_before_last_year_simplified' => [
      'label' => ts('Total Deductible Contributions Fiscal Year Before Last (Simplified)', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '45',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(total_amount),0)
      FROM civicrm_contribution t1 JOIN civicrm_financial_type t2 ON
      t1.financial_type_id = t2.id AND is_deductible = 1
      WHERE CAST(receive_date AS DATE) BETWEEN "%year_before_last_fiscal_year_begin" AND
      "%year_before_last_fiscal_year_end" AND t1.contact_id = NEW.contact_id AND
      t1.contribution_status_id = 1 AND t1.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ],
    'contribution_count_this_year' => [
      'label' => ts('Count of Contributions this Fiscal Year', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '50',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(COUNT(t1.id),0)
      FROM civicrm_contribution t1
      JOIN civicrm_line_item t2 ON t1.id = t2.contribution_id
      WHERE CAST(receive_date AS DATE) BETWEEN "%current_fiscal_year_begin"
      AND "%current_fiscal_year_end" AND t1.contact_id = (SELECT contact_id FROM civicrm_contribution cc WHERE cc.id = NEW.contribution_id) AND
      t1.contribution_status_id = 1 AND t2.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_line_item',
      'optgroup' => 'fundraising',
    ],
    'contribution_count_this_year_simplified' => [
      'label' => ts('Count of Contributions this Fiscal Year (Simplified)', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '50',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(COUNT(t1.id),0)
      FROM civicrm_contribution t1 WHERE CAST(receive_date AS DATE) BETWEEN "%current_fiscal_year_begin"
      AND "%current_fiscal_year_end" AND t1.contact_id = NEW.contact_id AND
      t1.contribution_status_id = 1 AND t1.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ],

    'contribution_count_last_year' => [
      'label' => ts('Count of Contributions last Fiscal Year', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '55',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(COUNT(t1.id),0)
      FROM civicrm_contribution t1
      JOIN civicrm_line_item t2 ON t1.id = t2.contribution_id
      WHERE CAST(receive_date AS DATE) BETWEEN "%last_fiscal_year_begin"
      AND "%last_fiscal_year_end" AND t1.contact_id = (SELECT contact_id FROM civicrm_contribution cc WHERE cc.id = NEW.contribution_id) AND
      t1.contribution_status_id = 1 AND t2.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_line_item',
      'optgroup' => 'fundraising',
    ],
    'contribution_count_last_year_simplified' => [
      'label' => ts('Count of Contributions last Fiscal Year (Simplified)', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '55',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(COUNT(t1.id),0)
      FROM civicrm_contribution t1 WHERE CAST(receive_date AS DATE) BETWEEN "%last_fiscal_year_begin"
      AND "%last_fiscal_year_end" AND t1.contact_id = NEW.contact_id AND
      t1.contribution_status_id = 1 AND t1.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ],
    'contribution_count_year_before_last' => [
      'label' => ts('Count of Contributions Fiscal Year Before Last', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '60',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(COUNT(t1.id),0)
      FROM civicrm_contribution t1
      JOIN civicrm_line_item t2 ON t1.id = t2.contribution_id
      WHERE CAST(receive_date AS DATE) BETWEEN "%year_before_last_fiscal_year_begin"
      AND "%year_before_last_fiscal_year_end" AND t1.contact_id = (SELECT contact_id FROM civicrm_contribution cc WHERE cc.id = NEW.contribution_id) AND
      t1.contribution_status_id = 1 AND t2.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_line_item',
      'optgroup' => 'fundraising',
    ],
    'contribution_count_year_before_last_simplified' => [
      'label' => ts('Count of Contributions Fiscal Year Before Last (Simplified)', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '60',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(COUNT(t1.id),0)
      FROM civicrm_contribution t1 WHERE CAST(receive_date AS DATE) BETWEEN "%year_before_last_fiscal_year_begin"
      AND "%year_before_last_fiscal_year_end" AND t1.contact_id = NEW.contact_id AND
      t1.contribution_status_id = 1 AND t1.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ],
    'contribution_amount_last' => [
      'label' => ts('Amount of last contribution', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '65',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(total_amount,0)
      FROM civicrm_contribution t1
      JOIN civicrm_line_item t2 ON t1.id = t2.contribution_id
      WHERE t1.contact_id = (SELECT contact_id FROM civicrm_contribution cc WHERE cc.id = NEW.contribution_id)
      AND t1.contribution_status_id = 1 AND t2.financial_type_id IN
      (%financial_type_ids) AND t1.is_test = 0 ORDER BY t1.receive_date DESC LIMIT 1)',
      'trigger_table' => 'civicrm_line_item',
      'optgroup' => 'fundraising',
    ],
    'contribution_amount_last_simplified' => [
      'label' => ts('Amount of last contribution (Simplified)', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '65',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(total_amount,0)
      FROM civicrm_contribution t1 WHERE t1.contact_id = NEW.contact_id
      AND t1.contribution_status_id = 1  AND t1.financial_type_id IN
      (%financial_type_ids) AND t1.is_test = 0 ORDER BY t1.receive_date DESC LIMIT 1)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ],
    'contribution_date_last' => [
      'label' => ts('Date of Last Contribution', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'weight' => '70',
      'text_length' => '32',
      'trigger_sql' => '(SELECT MAX(receive_date) FROM civicrm_contribution t1
      JOIN civicrm_line_item t2 ON t1.id = t2.contribution_id
      WHERE t1.contact_id = (SELECT contact_id FROM civicrm_contribution cc WHERE cc.id = NEW.contribution_id) AND t1.contribution_status_id = 1 AND
      t2.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_line_item',
      'optgroup' => 'fundraising',
    ],
    'contribution_date_last_simplified' => [
      'label' => ts('Date of Last Contribution (Simplified)', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'weight' => '70',
      'text_length' => '32',
      'trigger_sql' => '(SELECT MAX(receive_date) FROM civicrm_contribution t1
      WHERE t1.contact_id = NEW.contact_id AND t1.contribution_status_id = 1 AND
      t1.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ],
    'contribution_amount_first' => [
      'label' => ts('Amount of first contribution', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '75',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(total_amount,0)
      FROM civicrm_contribution t1
      JOIN civicrm_line_item t2 ON t1.id = t2.contribution_id
      WHERE t1.contact_id = (SELECT contact_id FROM civicrm_contribution cc WHERE cc.id = NEW.contribution_id)
      AND t1.contribution_status_id = 1 AND t2.financial_type_id IN
      (%financial_type_ids) AND t1.is_test = 0 ORDER BY t1.receive_date ASC LIMIT 1)',
      'trigger_table' => 'civicrm_line_item',
      'optgroup' => 'fundraising',
    ],
    'contribution_amount_first_simplified' => [
      'label' => ts('Amount of first contribution (Simplified)', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '75',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(total_amount,0)
      FROM civicrm_contribution t1 WHERE t1.contact_id = NEW.contact_id
      AND t1.contribution_status_id = 1  AND t1.financial_type_id IN
      (%financial_type_ids) AND t1.is_test = 0 ORDER BY t1.receive_date ASC LIMIT 1)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ],
    'contribution_date_first' => [
      'label' => ts('Date of First Contribution', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'weight' => '80',
      'text_length' => '32',
      'trigger_sql' => '(SELECT MIN(receive_date) FROM civicrm_contribution t1
      JOIN civicrm_line_item t2 ON t1.id = t2.contribution_id
      WHERE t1.contact_id = (SELECT contact_id FROM civicrm_contribution cc WHERE cc.id = NEW.contribution_id) AND t1.contribution_status_id = 1 AND
      t2.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_line_item',
      'optgroup' => 'fundraising',
    ],
    'contribution_date_first_simplified' => [
      'label' => ts('Date of First Contribution (Simplified)', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'weight' => '80',
      'text_length' => '32',
      'trigger_sql' => '(SELECT MIN(receive_date) FROM civicrm_contribution t1
      WHERE t1.contact_id = NEW.contact_id AND t1.contribution_status_id = 1 AND
      t1.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ],
    'contribution_date_largest' => [
      'label' => ts('Date of Largest Contribution', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'weight' => '85',
      'text_length' => '32',
      'trigger_sql' => '(SELECT receive_date 
      FROM civicrm_contribution t1
      JOIN civicrm_line_item t2 ON t1.id = t2.contribution_id
      WHERE t1.contact_id = (SELECT contact_id FROM civicrm_contribution cc WHERE cc.id = NEW.contribution_id) AND
      t1.contribution_status_id = 1 AND t2.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0
      ORDER BY t1.total_amount DESC, t1.receive_date DESC LIMIT 1)',
      'trigger_table' => 'civicrm_line_item',
      'optgroup' => 'fundraising',
    ],
    'contribution_date_largest_simplified' => [
      'label' => ts('Date of Largest Contribution (Simplified)', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'weight' => '85',
      'text_length' => '32',
      'trigger_sql' => '(SELECT receive_date 
      FROM civicrm_contribution t1 WHERE t1.contact_id = NEW.contact_id AND
      t1.contribution_status_id = 1 AND t1.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0
      ORDER BY total_amount DESC, receive_date DESC LIMIT 1)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ],
    'contribution_largest' => [
      'label' => ts('Largest Contribution', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '90',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(MAX(total_amount), 0)
      FROM civicrm_contribution t1
      JOIN civicrm_line_item t2 ON t1.id = t2.contribution_id
      WHERE t1.contact_id = (SELECT contact_id FROM civicrm_contribution cc WHERE cc.id = NEW.contribution_id) AND
      t1.contribution_status_id = 1 AND t2.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_line_item',
      'optgroup' => 'fundraising',
    ],
    'contribution_largest_simplified' => [
      'label' => ts('Largest Contribution (Simplified)', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '90',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(MAX(total_amount), 0)
      FROM civicrm_contribution t1 WHERE t1.contact_id = NEW.contact_id AND
      t1.contribution_status_id = 1 AND t1.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ],
    'contribution_total_number' => [
      'label' => ts('Count of Contributions', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '95',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(COUNT(DISTINCT t1.id), 0) FROM civicrm_contribution t1
      JOIN civicrm_line_item t2 ON t1.id = t2.contribution_id
      WHERE t1.contact_id = (SELECT contact_id FROM civicrm_contribution cc WHERE cc.id = NEW.contribution_id) AND t1.contribution_status_id = 1 AND
      t2.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_line_item',
      'optgroup' => 'fundraising',
    ],
    'contribution_total_number_simplified' => [
      'label' => ts('Count of Contributions (Simplified)', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '95',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(COUNT(id), 0) FROM civicrm_contribution t1
      WHERE t1.contact_id = NEW.contact_id AND t1.contribution_status_id = 1 AND
      t1.financial_type_id IN (%financial_type_ids) AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ],
    'contribution_average_annual_amount' => [
      'label' => ts('Average Annual (Calendar Year) Contribution', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '100',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(line_total),0) / (SELECT NULLIF(COUNT(DISTINCT SUBSTR(receive_date, 1, 4)), 0)
      FROM civicrm_contribution t0 
      JOIN civicrm_line_item t1 ON t0.id = t1.contribution_id
      WHERE t0.contact_id = (SELECT contact_id FROM civicrm_contribution cc WHERE cc.id = NEW.contribution_id) AND t1.financial_type_id
      IN (%financial_type_ids) AND t0.contribution_status_id = 1 AND is_test = 0) FROM civicrm_contribution t2
      JOIN civicrm_line_item t3 ON t2.id = t3.contribution_id
      WHERE t2.contact_id = (SELECT contact_id FROM civicrm_contribution cc WHERE cc.id = NEW.contribution_id) AND t3.financial_type_id IN (%financial_type_ids)
      AND t2.contribution_status_id = 1 AND t2.is_test = 0)',
      'trigger_table' => 'civicrm_line_item',
      'optgroup' => 'fundraising',
    ],
    'contribution_average_annual_amount_simplified' => [
      'label' => ts('Average Annual (Calendar Year) Contribution (Simplified)', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '100',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(total_amount),0) / (SELECT NULLIF(COUNT(DISTINCT SUBSTR(receive_date, 1, 4)), 0)
      FROM civicrm_contribution t0 WHERE t0.contact_id = NEW.contact_id AND t0.financial_type_id
      IN (%financial_type_ids) AND t0.contribution_status_id = 1 and t0.is_test = 0) FROM civicrm_contribution t1
      WHERE t1.contact_id = NEW.contact_id AND t1.financial_type_id IN (%financial_type_ids)
      AND t1.contribution_status_id = 1 AND t1.is_test = 0)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ],
    'soft_total_lifetime' => [
      'label' => ts('Total Lifetime Soft Credits', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '200',
      'text_length' => '32',
      'trigger_sql' => '(SELECT IF(SUM(amount) IS NULL, 0, SUM(amount))
      FROM civicrm_contribution_soft t1 WHERE t1.contact_id = NEW.contact_id
      AND t1.contribution_id IN (SELECT id FROM civicrm_contribution WHERE contribution_status_id = 1 AND financial_type_id IN (%financial_type_ids) AND is_test = 0))',
      'trigger_table' => 'civicrm_contribution_soft',
      'optgroup' => 'soft',
    ],
    'soft_total_this_year' => [
      'label' => ts('Total Soft Credits this Fiscal Year', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '210',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(amount),0)
      FROM civicrm_contribution_soft t1 WHERE t1.contact_id = NEW.contact_id
      AND t1.contribution_id IN (
        SELECT id FROM civicrm_contribution WHERE contribution_status_id = 1 AND financial_type_id IN (%financial_type_ids)
        AND CAST(receive_date AS DATE) BETWEEN "%current_fiscal_year_begin" AND "%current_fiscal_year_end" AND is_test = 0
      ))',
      'trigger_table' => 'civicrm_contribution_soft',
      'optgroup' => 'soft',
    ],
    'soft_total_twelve_months' => [
      'label' => ts('Total Soft Credits in the Last 12 Months', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '215',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(amount),0)
      FROM civicrm_contribution_soft t1 WHERE t1.contact_id = NEW.contact_id
      AND t1.contribution_id IN (
        SELECT id FROM civicrm_contribution WHERE contribution_status_id = 1 AND financial_type_id IN (%financial_type_ids)
        AND CAST(receive_date AS DATE) BETWEEN DATE_SUB(NOW(), INTERVAL 12 MONTH) AND NOW() AND is_test = 0
      ))',
      'trigger_table' => 'civicrm_contribution_soft',
      'optgroup' => 'soft',
    ],
    'contribution_date_last_membership_payment' => [
      'label' => ts('Date of Last Membership Payment', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'weight' => '150',
      'text_length' => '32',
      'trigger_sql' => '(SELECT MAX(receive_date) FROM civicrm_contribution t1
      JOIN civicrm_line_item t2 ON t1.id = t2.contribution_id
      WHERE t1.contact_id = (SELECT contact_id FROM civicrm_contribution cc WHERE cc.id = NEW.contribution_id) AND t1.contribution_status_id = 1 AND
      t2.financial_type_id IN (%membership_financial_type_ids) AND is_test = 0 ORDER BY
      receive_date DESC LIMIT 1)',
      'trigger_table' => 'civicrm_line_item',
      'optgroup' => 'membership',
    ],
    'contribution_date_last_membership_payment_simplified' => [
      'label' => ts('Date of Last Membership Payment (simplified)', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'weight' => '155',
      'text_length' => '32',
      'trigger_sql' => '(SELECT MAX(receive_date) FROM civicrm_contribution t1 WHERE
       t1.contact_id = NEW.contact_id AND t1.contribution_status_id = 1 AND
       t1.financial_type_id IN (%membership_financial_type_ids) AND t1.is_test = 0 ORDER BY
       receive_date DESC LIMIT 1)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'membership',
    ],
    'contribution_amount_last_membership_payment' => [
      'label' => ts('Amount of Last Membership Payment', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '160',
      'text_length' => '32',
      'trigger_sql' => '(SELECT total_amount FROM civicrm_contribution t1
      JOIN civicrm_line_item t2 ON t1.id = t2.contribution_id
      WHERE t1.contact_id = (SELECT contact_id FROM civicrm_contribution cc WHERE cc.id = NEW.contribution_id) AND t1.contribution_status_id = 1 AND
      t2.financial_type_id IN (%membership_financial_type_ids) AND t1.is_test = 0 ORDER BY
      receive_date DESC LIMIT 1)',
      'trigger_table' => 'civicrm_line_item',
      'optgroup' => 'membership',
    ],
    'contribution_amount_last_membership_payment_simplified' => [
      'label' => ts('Amount of Last Membership Payment (simplified)', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '160',
      'text_length' => '32',
      'trigger_sql' =>'(SELECT total_amount FROM civicrm_contribution t1 WHERE
       t1.contact_id = NEW.contact_id AND t1.contribution_status_id = 1 AND
       t1.financial_type_id IN (%membership_financial_type_ids) AND t1.is_test = 0 ORDER BY 
      receive_date DESC LIMIT 1)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'membership',
    ],
    'membership_join_date' => [
      'label' => ts('First membership join date', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'weight' => '170',
      'text_length' => '32',
      'trigger_sql' =>'(SELECT join_date FROM civicrm_membership t1 WHERE
       t1.contact_id = NEW.contact_id AND t1.is_test = 0 ORDER BY
      join_date ASC LIMIT 1)',
      'trigger_table' => 'civicrm_membership',
      'optgroup' => 'membership',
    ],
    'membership_end_date' => [
      'label' => ts('Latest membership end date', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'weight' => '175',
      'text_length' => '32',
      'trigger_sql' =>'(SELECT end_date FROM civicrm_membership t1 WHERE
       t1.contact_id = NEW.contact_id AND t1.is_test = 0 ORDER BY
      join_date DESC LIMIT 1)',
      'trigger_table' => 'civicrm_membership',
      'optgroup' => 'membership',
    ],
    'event_last_attended_name' => [
      'label' => ts('Name of the last attended event', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'String',
      'html_type' => 'Text',
      'weight' => '200',
      'text_length' => '255',
      'is_search_range' => '0',
      'trigger_sql' => sumfields_multilingual_rewrite('(SELECT civicrm_event.title AS summary_value
      FROM civicrm_participant t1 JOIN civicrm_event ON t1.event_id = civicrm_event.id
      WHERE t1.contact_id = NEW.contact_id AND t1.status_id IN (%participant_status_ids)
      AND civicrm_event.event_type_id IN (%event_type_ids) AND t1.is_test = 0
      ORDER BY start_date DESC LIMIT 1)'),
      'trigger_table' => 'civicrm_participant',
      'optgroup' => 'event_standard',
    ],
    'event_last_attended_date' => [
      'label' => ts('Date of the last attended event', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'weight' => '205',
      'text_length' => '32',
      'trigger_sql' => '(SELECT e.start_date AS summary_value FROM civicrm_participant t1 JOIN civicrm_event e ON
      t1.event_id = e.id WHERE t1.contact_id = NEW.contact_id AND t1.status_id IN (%participant_status_ids)
      AND e.event_type_id IN (%event_type_ids) AND t1.is_test = 0 ORDER BY start_date DESC LIMIT 1)',
      'trigger_table' => 'civicrm_participant',
      'optgroup' => 'event_standard',
    ],

    'event_total' => [
      'label' => ts('Total Number of events', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '210',
      'text_length' => '8',
      'trigger_sql' => $event_total_trigger_sql,
      'trigger_table' => 'civicrm_participant',
      'optgroup' => 'event_standard',
    ],
    'event_attended' => [
      'label' => ts('Number of events attended', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '215',
      'text_length' => '8',
      'trigger_sql' => $event_attended_trigger_sql,
      'trigger_table' => 'civicrm_participant',
      'optgroup' => 'event_standard',
    ],
    'event_attended_percent_total' => [
      'label' => ts('Events attended as percent of total', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '220',
      'text_length' => '8',
      // Divide event_attended_total_lifetime / event_total, substituting 0 if either field is NULL. Then, only
      // take two decimal places and multiply by 100, so .8000 becomes 80.
      'trigger_sql' => '(SELECT FORMAT(IFNULL(' . $event_attended_trigger_sql .
        ', 0)' . ' / ' .  'IFNULL(' . $event_total_trigger_sql_null . ', 1), 2) * 100 AS summary_value)',
      'trigger_table' => 'civicrm_participant',
      'optgroup' => 'event_standard',
    ],
    'event_noshow' => [
      'label' => ts('Number of no-show events', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '225',
      'text_length' => '8',
      'trigger_sql' => $event_noshow_trigger_sql,
      'trigger_table' => 'civicrm_participant',
      'optgroup' => 'event_standard',
    ],
    'event_noshow_percent_total' => [
      'label' => ts('No-shows as percent of total events', ['domain' => 'net.ourpowerbase.sumfields']),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '230',
      'text_length' => '8',
      'trigger_sql' => '(SELECT FORMAT(IFNULL(' . $event_noshow_trigger_sql .
         ', 0)' . ' / ' .  'IFNULL(' . $event_total_trigger_sql_null . ', 1), 2) * 100 AS summary_value)',
      'trigger_table' => 'civicrm_participant',
      'optgroup' => 'event_standard',
    ],
  ],
  'optgroups' => [
    'fundraising' => [
      'title' => ts('Contribution Fields', ['domain' => 'net.ourpowerbase.sumfields']),
      'component' => 'CiviContribute',
      'fieldset' => ts('Fundraising', ['domain' => 'net.ourpowerbase.sumfields']),
    ],
    'soft' => [
      'title' => ts('Soft Credit Fields', ['domain' => 'net.ourpowerbase.sumfields']),
      'component' => 'CiviContribute',
      'fieldset' => ts('Fundraising', ['domain' => 'net.ourpowerbase.sumfields']),
    ],
    'membership' => [
      'title' => ts('Membership Fields', ['domain' => 'net.ourpowerbase.sumfields']),
      'component' => 'CiviMember',
      'fieldset' => ts('Membership', ['domain' => 'net.ourpowerbase.sumfields']),
    ],
    'event_standard' => [
      'title' => ts('Standard Event Fields', ['domain' => 'net.ourpowerbase.sumfields']),
      'component' => 'CiviEvent',
      'fieldset' => ts('Events', ['domain' => 'net.ourpowerbase.sumfields']),
    ],
    'event_turnout' => [
      'title' => ts('Event Turnout Fields', ['domain' => 'net.ourpowerbase.sumfields']),
      'component' => 'CiviEvent',
      'fieldset' => ts('Events', ['domain' => 'net.ourpowerbase.sumfields']),
    ],
  ]
];
