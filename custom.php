<?php

// Define a few trigger sql queries first - because they need to be
// referenced first for a total number and a second time for the
// percent.
$event_attended_trigger_sql =
  '(SELECT COUNT(e.id) AS summary_value FROM civicrm_participant t1 JOIN civicrm_event e ON
  t1.event_id = e.id WHERE t1.contact_id = NEW.contact_id AND t1.status_id IN (%participant_status_ids)
  AND e.event_type_id IN (%event_type_ids))';
$event_total_trigger_sql =
 '(SELECT COUNT(t1.id) AS summary_value FROM civicrm_participant t1 JOIN civicrm_event e ON
 t1.event_id = e.id WHERE contact_id = NEW.contact_id AND e.event_type_id IN (%event_type_ids))';
$event_noshow_trigger_sql =
  '(SELECT COUNT(e.id) AS summary_value FROM civicrm_participant t1 JOIN civicrm_event e ON
  t1.event_id = e.id WHERE t1.contact_id = NEW.contact_id AND t1.status_id IN (%participant_noshow_status_ids)
  AND e.event_type_id IN (%event_type_ids))';
$event_turnout_attempts_trigger_sql =
  '(SELECT COUNT(t1.id) AS summary_value FROM %civicrm_value_participant_info t1 JOIN civicrm_participant p
  ON t1.entity_id = p.id JOIN civicrm_event e ON p.event_id = e.id
  WHERE contact_id = NEW.contact_id AND ((%reminder_response IS NOT NULL AND %reminder_response != "")
  OR (%invitation_response IS NOT NULL AND %invitation_response != "")) AND e.event_type_id IN (%event_type_ids))';
$event_turnout_attended_trigger_sql =
  '(SELECT COUNT(t1.id) AS summary_value FROM %civicrm_value_participant_info t1 JOIN civicrm_participant p
  ON t1.entity_id = p.id JOIN civicrm_event e ON p.event_id = e.id
  WHERE contact_id = NEW.contact_id AND ((%reminder_response IS NOT NULL AND %reminder_response != "")
  OR (%invitation_response IS NOT NULL AND %invitation_response != "")) AND p.status_id IN (%participant_status_ids)
  AND e.event_type_id IN (%event_type_ids))';
$event_turnout_noshow_trigger_sql =
  '(SELECT COUNT(t1.id) AS summary_value FROM %civicrm_value_participant_info t1 JOIN civicrm_participant p
  ON t1.entity_id = p.id JOIN civicrm_event e ON p.event_id = e.id
  WHERE contact_id = NEW.contact_id AND ((%reminder_response IS NOT NULL AND %reminder_response != "") OR
  (%invitation_response IS NOT NULL AND %invitation_response != "")) AND p.status_id IN (%participant_noshow_status_ids)
  AND e.event_type_id IN (%event_type_ids))';

$custom = array(
  'groups' => array(
    'summary_fields' => array(
      'name' => 'Summary_Fields',
      'title' => ts('Summary Fields', array('domain' => 'net.ourpowerbase.sumfields')),
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
    ),
  ),
  'fields' => array(
    'contribution_total_lifetime' => array(
      'label' => ts('Total Lifetime Contributions', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '10',
      'text_length' => '32',
      'trigger_sql' => '(SELECT IF(SUM(total_amount) IS NULL, 0, SUM(total_amount))
      FROM civicrm_contribution t1 WHERE t1.contact_id = NEW.contact_id
      AND t1.contribution_status_id = 1 AND t1.financial_type_id IN (%financial_type_ids))',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ),
    'contribution_total_this_year' => array(
      'label' => ts('Total Contributions this Year', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '15',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(total_amount),0)
      FROM civicrm_contribution t1 WHERE CAST(receive_date AS DATE) BETWEEN "%current_fiscal_year_begin"
      AND "%current_fiscal_year_end" AND t1.contact_id = NEW.contact_id AND
      t1.contribution_status_id = 1 AND t1.financial_type_id IN (%financial_type_ids))',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ),
    'contribution_total_twelve_months' => array(
      'label' => ts('Total Contributions in the Last 12 Months', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '15',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(total_amount),0)
      FROM civicrm_contribution t1 WHERE CAST(receive_date AS DATE) BETWEEN DATE_SUB(NOW(), INTERVAL 12 MONTH) AND NOW()
      AND t1.contact_id = NEW.contact_id AND
      t1.contribution_status_id = 1 AND t1.financial_type_id IN (%financial_type_ids))',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ),
    'contribution_total_deductible_this_year' => array(
      'label' => ts('Total Deductible Contributions this Year', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '15',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(total_amount),0)
      FROM civicrm_contribution t1 JOIN civicrm_financial_type t2 ON
      t1.financial_type_id = t2.id AND is_deductible = 1
      WHERE CAST(receive_date AS DATE) BETWEEN "%current_fiscal_year_begin" AND
      "%current_fiscal_year_end" AND t1.contact_id = NEW.contact_id AND
      t1.contribution_status_id = 1 AND t1.financial_type_id IN (%financial_type_ids))',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ),
    'contribution_total_last_year' => array(
      'label' => ts('Total Contributions last Year', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '20',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(total_amount),0)
      FROM civicrm_contribution t1 WHERE CAST(receive_date AS DATE) BETWEEN "%last_fiscal_year_begin"
      AND "%last_fiscal_year_end" AND t1.contact_id = NEW.contact_id AND
      t1.contribution_status_id = 1 AND t1.financial_type_id IN (%financial_type_ids))',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ),
    'contribution_total_deductible_last_year' => array(
      'label' => ts('Total Deductible Contributions last Year', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '15',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(total_amount),0)
      FROM civicrm_contribution t1 JOIN civicrm_financial_type t2 ON
      t1.financial_type_id = t2.id AND is_deductible = 1
      WHERE CAST(receive_date AS DATE) BETWEEN "%last_fiscal_year_begin" AND
      "%last_fiscal_year_end" AND t1.contact_id = NEW.contact_id AND
      t1.contribution_status_id = 1 AND t1.financial_type_id IN (%financial_type_ids))',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ),
    'contribution_total_year_before_last' => array(
      'label' => ts('Total Contributions Year Before Last', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '20',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(total_amount),0)
      FROM civicrm_contribution t1 WHERE CAST(receive_date AS DATE) BETWEEN "%year_before_last_fiscal_year_begin"
      AND "%year_before_last_fiscal_year_end" AND t1.contact_id = NEW.contact_id AND
      t1.contribution_status_id = 1 AND t1.financial_type_id IN (%financial_type_ids))',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ),
    'contribution_total_deductible_year_before_last_year' => array(
      'label' => ts('Total Deductible Contributions Year Before Last', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '15',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(total_amount),0)
      FROM civicrm_contribution t1 JOIN civicrm_financial_type t2 ON
      t1.financial_type_id = t2.id AND is_deductible = 1
      WHERE CAST(receive_date AS DATE) BETWEEN "%year_before_last_fiscal_year_begin" AND
      "%year_before_last_fiscal_year_end" AND t1.contact_id = NEW.contact_id AND
      t1.contribution_status_id = 1 AND t1.financial_type_id IN (%financial_type_ids))',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ),
    'contribution_amount_last' => array(
      'label' => ts('Amount of last contribution', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '25',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(total_amount,0)
      FROM civicrm_contribution t1 WHERE t1.contact_id = NEW.contact_id
      AND t1.contribution_status_id = 1  AND t1.financial_type_id IN
      (%financial_type_ids) ORDER BY t1.receive_date DESC LIMIT 1)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ),
    'contribution_date_last' => array(
      'label' => ts('Date of Last Contribution', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'weight' => '30',
      'text_length' => '32',
      'trigger_sql' => '(SELECT MAX(receive_date) FROM civicrm_contribution t1
      WHERE t1.contact_id = NEW.contact_id AND t1.contribution_status_id = 1 AND
      t1.financial_type_id IN (%financial_type_ids))',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ),
    'contribution_date_first' => array(
      'label' => ts('Date of First Contribution', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'weight' => '35',
      'text_length' => '32',
      'trigger_sql' => '(SELECT MIN(receive_date) FROM civicrm_contribution t1
      WHERE t1.contact_id = NEW.contact_id AND t1.contribution_status_id = 1 AND
      t1.financial_type_id IN (%financial_type_ids))',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',

    ),
    'contribution_largest' => array(
      'label' => ts('Largest Contribution', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '40',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(MAX(total_amount), 0)
      FROM civicrm_contribution t1 WHERE t1.contact_id = NEW.contact_id AND
      t1.contribution_status_id = 1 AND t1.financial_type_id IN (%financial_type_ids))',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ),
    'contribution_total_number' => array(
      'label' => ts('Count of Contributions', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '45',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(COUNT(id), 0) FROM civicrm_contribution t1
      WHERE t1.contact_id = NEW.contact_id AND t1.contribution_status_id = 1 AND
      t1.financial_type_id IN (%financial_type_ids))',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ),
    'contribution_average_annual_amount' => array(
      'label' => ts('Average Annual (Calendar Year) Contribution', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '50',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(total_amount),0) / (SELECT NULLIF(COUNT(DISTINCT SUBSTR(receive_date, 1, 4)), 0)
      FROM civicrm_contribution t0 WHERE t0.contact_id = NEW.contact_id AND t0.financial_type_id
      IN (%financial_type_ids) AND t0.contribution_status_id = 1) FROM civicrm_contribution t1
      WHERE t1.contact_id = NEW.contact_id AND t1.financial_type_id IN (%financial_type_ids)
      AND t1.contribution_status_id = 1)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'fundraising',
    ),
    'soft_total_lifetime' => array(
      'label' => ts('Total Lifetime Soft Credits', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '10',
      'text_length' => '32',
      'trigger_sql' => '(SELECT IF(SUM(amount) IS NULL, 0, SUM(amount))
      FROM civicrm_contribution_soft t1 WHERE t1.contact_id = NEW.contact_id
      AND t1.contribution_id IN (SELECT id FROM civicrm_contribution WHERE contribution_status_id = 1 AND financial_type_id IN (%financial_type_ids)))',
      'trigger_table' => 'civicrm_contribution_soft',
      'optgroup' => 'soft',
    ),
    'soft_total_this_year' => array(
      'label' => ts('Total Soft Credits this Year', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '15',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(amount),0)
      FROM civicrm_contribution_soft t1 WHERE t1.contact_id = NEW.contact_id
      AND t1.contribution_id IN (
        SELECT id FROM civicrm_contribution WHERE contribution_status_id = 1 AND financial_type_id IN (%financial_type_ids)
        AND CAST(receive_date AS DATE) BETWEEN "%current_fiscal_year_begin" AND "%current_fiscal_year_end"
      ))',
      'trigger_table' => 'civicrm_contribution_soft',
      'optgroup' => 'soft',
    ),
    'soft_total_twelve_months' => array(
      'label' => ts('Total Soft Credits in the Last 12 Months', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '15',
      'text_length' => '32',
      'trigger_sql' => '(SELECT COALESCE(SUM(amount),0)
      FROM civicrm_contribution_soft t1 WHERE t1.contact_id = NEW.contact_id
      AND t1.contribution_id IN (
        SELECT id FROM civicrm_contribution WHERE contribution_status_id = 1 AND financial_type_id IN (%financial_type_ids)
        AND CAST(receive_date AS DATE) BETWEEN DATE_SUB(NOW(), INTERVAL 12 MONTH) AND NOW()
      ))',
      'trigger_table' => 'civicrm_contribution_soft',
      'optgroup' => 'soft',
    ),
    'contribution_date_last_membership_payment' => array(
      'label' => ts('Date of Last Membership Payment', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'weight' => '55',
      'text_length' => '32',
      'trigger_sql' => '(SELECT MAX(receive_date) FROM civicrm_contribution t1 WHERE
      t1.contact_id = NEW.contact_id AND t1.contribution_status_id = 1 AND
      t1.financial_type_id IN (%membership_financial_type_ids) ORDER BY
      receive_date DESC LIMIT 1)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'membership',
    ),
    'contribution_amount_last_membership_payment' => array(
      'label' => ts('Amount of Last Membership Payment', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Money',
      'html_type' => 'Text',
      'weight' => '60',
      'text_length' => '32',
      'trigger_sql' => '(SELECT total_amount FROM civicrm_contribution t1 WHERE
      t1.contact_id = NEW.contact_id AND t1.contribution_status_id = 1 AND
      t1.financial_type_id IN (%membership_financial_type_ids) ORDER BY
      receive_date DESC LIMIT 1)',
      'trigger_table' => 'civicrm_contribution',
      'optgroup' => 'membership',
    ),
    'event_last_attended_name' => array(
      'label' => ts('Name of the last attended event', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'String',
      'html_type' => 'Text',
      'weight' => '65',
      'text_length' => '128',
      'is_search_range' => '0',
      'trigger_sql' => sumfields_multilingual_rewrite('(SELECT civicrm_event.title AS summary_value
      FROM civicrm_participant t1 JOIN civicrm_event ON t1.event_id = civicrm_event.id
      WHERE t1.contact_id = NEW.contact_id AND t1.status_id IN (%participant_status_ids)
      AND civicrm_event.event_type_id IN (%event_type_ids) ORDER BY start_date DESC LIMIT 1)'),
      'trigger_table' => 'civicrm_participant',
      'optgroup' => 'event_standard',
    ),
    'event_last_attended_date' => array(
      'label' => ts('Date of the last attended event', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'weight' => '70',
      'text_length' => '32',
      'trigger_sql' => '(SELECT e.start_date AS summary_value FROM civicrm_participant t1 JOIN civicrm_event e ON
      t1.event_id = e.id WHERE t1.contact_id = NEW.contact_id AND t1.status_id IN (%participant_status_ids)
      AND e.event_type_id IN (%event_type_ids) ORDER BY start_date DESC LIMIT 1)',
      'trigger_table' => 'civicrm_participant',
      'optgroup' => 'event_standard',
    ),
    
    'event_total' => array(
      'label' => ts('Total Number of events', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '75',
      'text_length' => '8',
      'trigger_sql' => $event_total_trigger_sql, 
      'trigger_table' => 'civicrm_participant',
      'optgroup' => 'event_standard',
    ),
    'event_attended' => array(
      'label' => ts('Number of events attended', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '80',
      'text_length' => '8',
      'trigger_sql' => $event_attended_trigger_sql,
      'trigger_table' => 'civicrm_participant',
      'optgroup' => 'event_standard',
    ),
    'event_attended_percent_total' => array(
      'label' => ts('Events attended as percent of total', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '85',
      'text_length' => '8',
      // Divide event_attended_total_lifetime / event_total, substituting 0 if either field is NULL. Then, only
      // take two decimal places and multiply by 100, so .8000 becomes 80.
      'trigger_sql' => '(SELECT FORMAT(IFNULL(' . $event_attended_trigger_sql .
        ', 0)' . ' / ' .  'IFNULL(' . $event_total_trigger_sql . ', 0), 2) * 100 AS summary_value)',
      'trigger_table' => 'civicrm_participant',
      'optgroup' => 'event_standard',
    ),
    'event_noshow' => array(
      'label' => ts('Number of no-show events', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '90',
      'text_length' => '8',
      'trigger_sql' => $event_noshow_trigger_sql,
      'trigger_table' => 'civicrm_participant',
      'optgroup' => 'event_standard',
    ),
    'event_noshow_percent_total' => array(
      'label' => ts('No-shows as percent of total events', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '95',
      'text_length' => '8',
      'trigger_sql' => '(SELECT FORMAT(IFNULL(' . $event_noshow_trigger_sql .
         ', 0)' . ' / ' .  'IFNULL(' . $event_total_trigger_sql . ', 0), 2) * 100 AS summary_value)',
      'trigger_table' => 'civicrm_participant',
      'optgroup' => 'event_standard',
    ),
    'event_turnout_attempts' => array(
      'label' => ts('Number of turnout attempts', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '100',
      'text_length' => '8',
      'trigger_sql' => $event_turnout_attempts_trigger_sql,
      'trigger_table' => 'civicrm_participant',
      'optgroup' => 'event_turnout',
    ),
    'event_turnout_attended' => array(
      'label' => ts('Number attended from turnout attempts', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '105',
      'text_length' => '8',
      'trigger_sql' => $event_turnout_attended_trigger_sql,
      'trigger_table' => 'civicrm_participant',
      'optgroup' => 'event_turnout',
    ),
    'event_turnout_noshow' => array(
      'label' => ts('Number noshow from turnout attempts', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '110',
      'text_length' => '8',
      'trigger_sql' => $event_turnout_noshow_trigger_sql,
      'trigger_table' => 'civicrm_participant',
      'optgroup' => 'event_turnout',
    ),
    'event_attended_percent_turnout' => array(
      'label' => ts('Attended as percent of turn out attempts', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '115',
      'text_length' => '8',
      'trigger_sql' => '(SELECT FORMAT(IFNULL(' . $event_turnout_attended_trigger_sql .
         ', 0)' . ' / ' .  'IFNULL(' . $event_turnout_attempts_trigger_sql . ', 0), 2) * 100 AS summary_value)',
      'trigger_table' => 'civicrm_participant',
      'optgroup' => 'event_turnout',
    ),
    'event_noshow_percent_turnout' => array(
      'label' => ts('No-shows as percent of turn out attempts', array('domain' => 'net.ourpowerbase.sumfields')),
      'data_type' => 'Int',
      'html_type' => 'Text',
      'weight' => '120',
      'text_length' => '8',
      'trigger_sql' => '(SELECT FORMAT(IFNULL(' . $event_turnout_noshow_trigger_sql .
         ', 0)' . ' / ' .  'IFNULL(' . $event_turnout_attempts_trigger_sql . ', 0), 2) * 100 AS summary_value)',
      'trigger_table' => 'civicrm_participant',
      'optgroup' => 'event_turnout',
    ),
  ),
  'optgroups' => array(
    'fundraising' => array(
      'title' => ts('Contribution Fields', array('domain' => 'net.ourpowerbase.sumfields')),
      'component' => 'CiviContribute',
      'fieldset' => ts('Fundraising', array('domain' => 'net.ourpowerbase.sumfields')),
    ),
    'soft' => array(
      'title' => ts('Soft Credit Fields', array('domain' => 'net.ourpowerbase.sumfields')),
      'component' => 'CiviContribute',
      'fieldset' => ts('Fundraising', array('domain' => 'net.ourpowerbase.sumfields')),
    ),
    'membership' => array(
      'title' => ts('Membership Fields', array('domain' => 'net.ourpowerbase.sumfields')),
      'component' => 'CiviMember',
      'fieldset' => ts('Membership', array('domain' => 'net.ourpowerbase.sumfields')),
    ),
    'event_standard' => array(
      'title' => ts('Standard Event Fields', array('domain' => 'net.ourpowerbase.sumfields')),
      'component' => 'CiviEvent',
      'fieldset' => ts('Events', array('domain' => 'net.ourpowerbase.sumfields')),
    ),
    'event_turnout' => array(
      'title' => ts('Event Turnout Fields', array('domain' => 'net.ourpowerbase.sumfields')),
      'component' => 'CiviEvent',
      'fieldset' => ts('Events', array('domain' => 'net.ourpowerbase.sumfields')),
    ),
  )
);
