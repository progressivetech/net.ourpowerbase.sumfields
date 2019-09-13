Developing with Summary Fields
==============================
This file provides information on how to build on this extension.

Create a new summary field
--------------------------

This extension provides `hook_civicrm_sumfields_definitions` which allows you to add additional summary fields of your
own.

Example:

```
/**
 * Implements hook_civicrm_sumfields_definitions()
 *
 * Change "mycustom" to the name of your own extension.
 */
function mycustom_civicrm_sumfields_definitions(&$custom) {
  $custom['fields']['hard_and_soft'] = array(
    // Choose which group you want this field to appear with.
    'optgroup' => 'mycustom', // could just add this to the existing "fundraising" optgroup
    'label' => 'All contributions + soft credits',
    'data_type' => 'Money',
    'html_type' => 'Text',
    'weight' => '15',
    'text_length' => '32',
    // A change in the "trigger_table" should cause the field to be re-calculated.
    'trigger_table' => 'civicrm_contribution',
    // A parentheses enclosed SQL statement with a function to ensure a single
    // value is returned. The value should be restricted to a single
    // contact_id using the NEW.contact_id field
    'trigger_sql' => '(
      SELECT COALESCE(SUM(cont1.total_amount), 0)
      FROM civicrm_contribution cont1
      LEFT JOIN civicrm_contribution_soft soft
        ON soft.contribution_id = cont1.id
      WHERE (cont1.contact_id = NEW.contact_id OR soft.contact_id = NEW.contact_id)
        AND cont1.contribution_status_id = 1 AND cont1.financial_type_id IN (%financial_type_ids)
      )',
  );
  // If we don't want to add our fields to the existing optgroups or fieldsets on the admin form,
  // we can make new ones
  $custom['optgroups']['mycustom'] = array(
    'title' => 'My group of checkboxes',
    'fieldset' => 'Custom summary fields', // Could add this to an existing fieldset by naming it here
    'component' => 'CiviContribute',
  );
}
```

**When writing your own summary fields, here are some tips:**

The two most important settings are `trigger_table` and `trigger_sql`.
`trigger_table` is the table that is monitored for changes. When a change in
the data in that table is made, `trigger_sql` is executed. `trigger_sql` should
be a SQL statement with a single column SELECT statement. The column should be
an aggregate column that produces a value that will be inserted into the
summary fields table.

`trigger_sql` must minimally reference `NEW.contact_id` in its WHERE clause.
`New.contact_id` will be replaced with the value of the `contact_id` field in
the `trigger_table` row that is being changed or inserted.

What if the trigger table does not have a `contact_id` field? We can work with
that too.  If there isn't a `contact_id` in your table, then you need to add a
value to the `$custom['tables']` array (`civicrm_line_item` is a good example
of how to do that). This value will indicate how summary fields should
derive the `contact_id` from the fields available in the `trigger_table`.

In addition, there are several variables you can use in your `trigger_sql` that
are mapped to user-defined settings. All variables are replaced with comma
separated list of values chosen by users to restrict how summary fields are
calculated.

 * `%financial_type_ids`
 * `%participant_status_ids`
 * `%event_type_ids`
 * `%participant_noshow_status_ids`
 * `%current_fiscal_year_begin`
 * `%current_fiscal_year_end`
 * `%year_before_last_fiscal_year_begin`
 * `%year_before_last_fiscal_year_end`
 * `%membership_financial_type_ids`

If you change the query in the hook, you can simply go to the Summary Fields admin screen and resave. All data 
will be updated.

General information on triggers
-------------------------------

* [MySQL docs on triggers](https://dev.mysql.com/doc/refman/5.7/en/triggers.html)
* [CiviCRM trigger info hook](https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_triggerInfo/)
