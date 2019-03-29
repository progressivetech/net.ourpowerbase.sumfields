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

With version 4+, you can now run updates based on any table, provided there is a way to link it back to a `contact_id`. 
If there is a `contact_id` in the table you want to be the trigger, it's easy because you can reference `NEW.contact_id`
to get the right reference for the summary field row. If there isn't a `contact_id` in your table, then you need to add 
a record to the `$custom['tables']` array (`civicrm_line_item` is a good example of how to do that).

Pick the table with the data you are calculating. This is the trigger_table. When a change is made to this table,
the summary field is updated. If this table has the `contact_id` field, then you are all set. Your trigger_sql should
reference `NEW.contact_id`. If this table does not have a `contact_id` field, then it must contain a field that
can be used to calculate a contact_id (see the `civicrm_line_item` examples in `custom.php`).

If you change the query in the hook, you can simply go to the Summary Fields admin screen and resave. All data 
will be updated.

General information on triggers
-------------------------------

* [MySQL docs on triggers](https://dev.mysql.com/doc/refman/5.7/en/triggers.html)
* [CiviCRM trigger info hook](https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_triggerInfo/)

Triggers apply to update, delete and insert. When writing a trigger the NEW key word indicates the record that is inserted
or changed and OLD indicates the record that was deleted. A simplified example of a trigger would be if you had a contact 
table with a total_contributions field and a contributions table with a foreign key contact_id that included all the 
contributions.

Then, you could create trigger that would execute on INSERT or UPDATE for the contribution table that was:

`UPDATE contact SET contact.total_contributions = (SELECT SUM(amount) FROM contribution WHERE contact_id = NEW.contact_id)`

And a DELETE trigger that was:

`UPDATE contact SET contact.total_contributions = (SELECT SUM(amount) FROM contribution WHERE contact_id = OLD.contact_id)`

With these examples `NEW.contact_id` and `OLD.contact_id` would always be equal to the value of `contribution.contact_id` 
for the row that was inserted, updated or deleted.

With these triggers the `total_contributions` would always be accurate.
