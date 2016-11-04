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

{if !empty($form.active_fundraising_fields)}
  <fieldset>
    <legend>{ts}Fundraising{/ts}</legend>
    <table class="form-layout-compressed">
      <tr class="crm-sumfields-form-block-sumfields_active_fundraising_fields">
        <td class="label">{$form.active_fundraising_fields.label}</td>
        <td>{$form.active_fundraising_fields.html}</td>
      </tr>
      <tr class="crm-sumfields-form-block-sumfields_active_soft_fields">
        <td class="label">{$form.active_soft_fields.label}</td>
        <td>{$form.active_soft_fields.html}</td>
      </tr>
      <tr class="crm-sumfields-form-block-sumfields_financial_type_ids">
        <td class="label">{$form.financial_type_ids.label}</td>
        <td>
          {$form.financial_type_ids.html}
          <div class="description">{ts}Financial types to include when calculating contribution related summary fields.{/ts}</div>
        </td>
      </tr>
    </table>
  </fieldset>
{/if}

{if !empty($form.active_membership_fields)}
  <fieldset>
    <legend>{ts}Membership{/ts}</legend>
    <table class="form-layout-compressed">
      <tr class="crm-sumfields-form-block-sumfields_active_membership_fields">
        <td class="label">{$form.active_membership_fields.label}</td>
        <td>{$form.active_membership_fields.html}</td>
      </tr>
      <tr class="crm-sumfields-form-block-sumfields_membership_financial_type_ids">
        <td class="label">{$form.membership_financial_type_ids.label}</td>
        <td>
          {$form.membership_financial_type_ids.html}
          <div class="description">{ts}Financial types to include when calculating membership related summary fields.{/ts}</div>
        </td>
      </tr>
    </table>
  </fieldset>
{/if}

{if !empty($form.active_event_standard_fields)}
  <fieldset>
    <legend>{ts}Events{/ts}</legend>
    <table class="form-layout-compressed">
      <tr class="crm-sumfields-form-block-sumfields_active_event_standard_fields">
        <td class="label">{$form.active_event_standard_fields.label}</td>
        <td>{$form.active_event_standard_fields.html}</td>
      </tr>
      <tr class="crm-sumfields-form-block-sumfields_active_event_turnout_fields">
        <td class="label">{$form.active_event_turnout_fields.label}</td>
        <td>{$form.active_event_turnout_fields.html}</td>
      </tr>
      <tr class="crm-sumfields-form-block-sumfields_event_type_ids">
        <td class="label">{$form.event_type_ids.label}</td>
        <td>
          {$form.event_type_ids.html}
          <div class="description">{ts}Event types to include when calculating participant summary fields.{/ts}</div>
        </td>
      </tr>
      <tr class="crm-sumfields-form-block-sumfields_participant_status_ids">
        <td class="label">{$form.participant_status_ids.label}</td>
        <td>{$form.participant_status_ids.html}</td>
      </tr>
      <tr class="crm-sumfields-form-block-sumfields_participant_noshow_status_ids">
        <td class="label">{$form.participant_noshow_status_ids.label}</td>
        <td>{$form.participant_noshow_status_ids.html}</td>
      </tr>
    </table>
  </fieldset>
{/if}

 <div id="when_to_apply_change">
   <div class="description">{ts}Applying these settings via this form may cause your web server to time out. Applying changes on next scheduled job is recommended.{/ts}</div>
   <div class="label">{$form.when_to_apply_change.label}</div>
   <span>{$form.when_to_apply_change.html}</span>
 </div>

 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

{literal}
<style type="text/css">
  #crm-container fieldset {
    border: 1px solid #CFCEC3;
    border-radius: 4px;
  }
</style>
{/literal}

