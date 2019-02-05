Summary Fields make it easier to search for contacts (or insert tokens) based
on a summary or other calculation of the contact's previous interactions.

For example, you can access a contact's total lifetime contribution amount,
last membership contribution date, number of attended events, total of last
year's deductible contributions, and more.

Summary Fields extends your CiviCRM data by creating a tab of fields that total
up and summarize the fields you choose.

Once you've completed set-up, a new tab will appear alongside the other tabs in
contact records showing the totals for each individual. The fields in this tab
will appears in Advanced Search and will be available as tokens for emails or
PDF merges.

![Admin Screen](AdminScreen.png)

Getting Started
-----------------

After installing the extension, you must configure it before any summary fields
are calculated.

You can configure the extension by going to `Adminster -> Customize Data and
Screens -> Summary Fields.`

Choose the fields you want to enable. Every field you enable will slow down the
performance of your database just a little, so only enable fields you really
need. You can always come back and enable additional fields later.

In addition, you can configure how Summary Fields works. For example, you can
choose which financial types should be included when calculating contribution
amounts. And you can dedide which participant status types should be considered
"attended" and which should be a considered a "no-show".

Want more summary fields?
------------------

Do you want more summary fields? Check out [Joinery's More Summary
Fields](https://github.com/twomice/com.joineryhq.jsumfields) for an extension
providing yet more fields.

Wnat *still more fields*?

You can write your own extension. It's easy!

This extension provides `hook_civicrm_sumfields_definitions` which allows you
to add additional summary fields of your own.

Example:

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
      // If we don't want to add our fields to the existing optgroups or fieldsets on the admin form, we can make new ones
      $custom['optgroups']['mycustom'] = array(
        'title' => 'My group of checkboxes',
        'fieldset' => 'Custom summary fields', // Could add this to an existing fieldset by naming it here
        'component' => 'CiviContribute',
      );
    }

When writing your own summary fields, here are some tips:

 * Pick the table with the data you are calculating. This is the trigger_table
   - when a change is made to this table, the summary field is updated.
 * If this table has the contact_id field, then you are all set. Your
   trigger_sql should reference NEW.contact_id 
 * If this table does not have a contact_id field, then it must contain a field
   that can be used to calculate a contact_id (see the civicrm_line_item
   examples in custom.php) 

