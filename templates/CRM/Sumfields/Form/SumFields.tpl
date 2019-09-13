<h3>{ts}Extension Status{/ts}</h3>

<table class="form-layout-compressed">
  <tr>
    <td class="description">
      {ts}Status of current settings:{/ts}
    </td>
    <td>
      <span class="crm-i {$status_icon}"></span>
      {$display_status}
    </td>
  </tr>
  <tr>
    <td class="description">
      {ts}Data update method:{/ts}
    </td>
    <td>
      <span class="crm-i {$status_icon}"></span>
      {$data_update_method}
    </td>
  </tr>
  {foreach from=$trigger_table_status key="tableName" item="enabled"}
    <tr>
      <td class="description {if $enabled}sumfield-status-enabled{else}sumfield-status-disabled{/if}">
        {ts 1=$tableName}Triggers for %1:{/ts}
      </td>
      <td>
        <span class="crm-i fa-{if $enabled}check{else}circle-o{/if}"></span>
        {if $enabled}{ts}Enabled{/ts}{else}{ts}Not Enabled{/ts}{/if}
      </td>
    </tr>
  {/foreach}
</table>

<h3>{ts}Field Settings{/ts}</h3>
<div>
  <span class="label">{$form.show_simplified.label}</span>
  <span>{$form.show_simplified.html}</span>
  <div class="description">{ts}Show simplified contribution fields. By default, contribution fields are calculated using the line items table, which provides the most accurate accounting if you use price sets with different financial types. Simplified contribution fields are calculated using the contribution table, which is more efficient and will work better on large installations but won't accurately count a single contribution split between two line items (e.g. an event registration and donation).{/ts}</div>
</div>
{foreach from=$fieldsets key="title" item="fields"}
  <fieldset>
    <legend>{$title}</legend>
    <table class="form-layout-compressed">
      {foreach from=$fields key="name" item="description"}
        {if $name == 'active_fundraising_fields'}
          <tr><div class="help">{ts}Fiscal Year can be set at <a href="/civicrm/admin/setting/date?action=reset=1">Administer &gt; Localization &gt; Date Formats</a>{/ts}</div></tr>
        {/if}
        <tr class="crm-sumfields-form-block-sumfields_{$name}">
          <td class="label">{$form.$name.label}</td>
          <td class="value">
            {$form.$name.html}
            {if $description}<div class="description">{$description}</div>{/if}
          </td>
        </tr>
      {/foreach}
    </table>
  </fieldset>
{/foreach}

  <fieldset>
  <legend>Performance Settings</legend>
    <table class="form-layout-compressed">
      <tr id="performance_settings">
        <td class="label">{$form.data_update_method.label}</td>
        <td>{$form.data_update_method.html}
        <div class="description">{ts}If 'Instantly' is selected, data will be more accurate but you might face some performance issues on large installations. <br/> If 'Whenever the cron job is run' is selected, Summary Fields will rely on each CiviCRM Cron job to process all calculations needed for all contacts.{/ts}</div></td>
      </tr>
      <tr id="exclude_from_logging">
        <td class="label">{$form.exclude_from_logging.label}</td>
        <td>{$form.exclude_from_logging.html}
        <div class="description">{ts}When advanced logging is turned on, you can exclude Summary Fields from being logged to increase performance and reduce clutter.{/ts}</div></td>
      </tr>
      <tr id="when_to_apply_change">
        <td class="label">{$form.when_to_apply_change.label}</td>
        <td>{$form.when_to_apply_change.html}
        <div class="description">{ts}Applying these settings via this form may cause your web server to time out. Applying changes on next scheduled job is recommended.{/ts}</div></td>
      </tr>
    </table>
 </fieldset>

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

{literal}
<style type="text/css">
  #crm-container fieldset {
    border: 1px solid #CFCEC3;
    border-radius: 4px;
  }
</style>

<script type="text/javascript">
  CRM.$(window).load(function() {
    if (CRM.$('#show_simplified').prop("checked") === true){
      switch_simplified();
    } else {
      switch_normal();
    }
    CRM.$('#show_simplified').change(function(){
      if(this.checked) {
        switch_simplified();
      } else {
        switch_normal();
      }
    });
  });
  function switch_normal() {
      CRM.$('.CRM_Sumfields_Form_SumFields tr.crm-sumfields-form-block-sumfields_active_fundraising_fields td.value input[id$="simplified"]').hide();
      CRM.$('.CRM_Sumfields_Form_SumFields tr.crm-sumfields-form-block-sumfields_active_fundraising_fields td.value label[for$="simplified"]').hide();
      CRM.$('.CRM_Sumfields_Form_SumFields tr.crm-sumfields-form-block-sumfields_active_fundraising_fields td.value input').not('[id$="simplified"]').show();
      CRM.$('.CRM_Sumfields_Form_SumFields tr.crm-sumfields-form-block-sumfields_active_fundraising_fields td.value label').not('[for$="simplified"]').show();
  }
  function switch_simplified() {
      CRM.$('.CRM_Sumfields_Form_SumFields tr.crm-sumfields-form-block-sumfields_active_fundraising_fields td.value input[id$="simplified"]').show();
      CRM.$('.CRM_Sumfields_Form_SumFields tr.crm-sumfields-form-block-sumfields_active_fundraising_fields td.value label[for$="simplified"]').show();
      CRM.$('.CRM_Sumfields_Form_SumFields tr.crm-sumfields-form-block-sumfields_active_fundraising_fields td.value input').not('[id$="simplified"]').show();
      CRM.$('.CRM_Sumfields_Form_SumFields tr.crm-sumfields-form-block-sumfields_active_fundraising_fields td.value label').not('[for$="simplified"]').show();
  }
</script>
{/literal}
