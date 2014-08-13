<h2>Summary Fields Administration</h2>

<table class="form-layout-compressed">
  <tr>
    <td colspan="2" class="description"><h3>{ts}Extension Status{/ts}</h3></td>
  </tr>
  <tr>
    <td colspan="2" class="description">{ts}Status of current settings:{/ts} {$display_status}</td>
  </tr>
  <tr>
    <td colspan="2" class="description">{ts}Contribution Table Triggers:{/ts} {$contribution_table_trigger_status}</td>
  </tr>
  <tr>
    <td colspan="2" class="description">{ts}Participant Table Triggers:{/ts} {$participant_table_trigger_status}</td>
  </tr>
  <tr>
    <td colspan="2" class="description"><h3>{ts}Settings{/ts}</h3></td>
  </tr>

  <tr>
    <td colspan="2" class="description">{ts}Please indicate which of the available summary fields you would like to enable.{/ts}</td>
  </tr>
  {if $sumfields_active_fundraising }
  <tr class="crm-sumfields-form-block-sumfields_active_fundraising_fields">
    <td class="label">{$form.active_fundraising_fields.label}</td>
    <td>{$form.active_fundraising_fields.html}</td>
  </tr> 
  {/if}
  {if $sumfields_active_membership }
  <tr class="crm-sumfields-form-block-sumfields_active_membership_fields">
    <td class="label">{$form.active_membership_fields.label}</td>
    <td>{$form.active_membership_fields.html}</td>
  </tr> 
  {/if}
  {if $sumfields_active_event_standard }
  <tr class="crm-sumfields-form-block-sumfields_active_event_standard_fields">
    <td class="label">{$form.active_event_standard_fields.label}</td>
    <td>{$form.active_event_standard_fields.html}</td>
  </tr> 
  {/if}
  {if $sumfields_active_event_turnout }
  <tr class="crm-sumfields-form-block-sumfields_active_event_turnout_fields">
    <td class="label">{$form.active_event_turnout_fields.label}</td>
    <td>{$form.active_event_turnout_fields.html}</td>
  </tr> 
  {/if}
  {if $sumfields_contribute}
    <tr>
      <td colspan="2" class="description">{ts}Please indicate the financial types you would like included when calculating contribution related summary fields.{/ts}</td>
    </tr>
    <tr class="crm-sumfields-form-block-sumfields_financial_type_ids">
      <td class="label">{$form.financial_type_ids.label}</td>
      <td>{$form.financial_type_ids.html}</td>
    </tr> 
  {/if}
  {if $sumfields_event && $sumfields_member}
    <tr>
      <td colspan="2" class="description">{ts}Please indicate the financial types you would like included when calculating membership payment related summary fields.{/ts}</td>
    </tr>
    <tr class="crm-sumfields-form-block-sumfields_membership_financial_type_ids">
      <td class="label">{$form.membership_financial_type_ids.label}</td>
      <td>{$form.membership_financial_type_ids.html}</td>
    </tr> 
  {/if}
  {if $sumfields_event}
    <tr>
      <td colspan="2" class="description">{ts}Please indicate the event types you would like included when calculating event-related summary fields.{/ts}</td>
    </tr>
    <tr class="crm-sumfields-form-block-sumfields_event_type_ids">
      <td class="label">{$form.event_type_ids.label}</td>
      <td>{$form.event_type_ids.html}</td>
    </tr>
    <tr>
      <td colspan="2" class="description">{ts}Please indicate the participat status you would like included when calculating event-related summary fields to indicate attendance or non-attendance.{/ts}</td>
    </tr>
    <tr class="crm-sumfields-form-block-sumfields_participant_status_ids">
      <td class="label">{$form.participant_status_ids.label}</td>
      <td>{$form.participant_status_ids.html}</td>
    </tr>
    <tr class="crm-sumfields-form-block-sumfields_participant_noshow_status_ids">
      <td class="label">{$form.participant_noshow_status_ids.label}</td>
      <td>{$form.participant_noshow_status_ids.html}</td>
    </tr>
  {/if}
</table>
 <div id="when_to_apply_chyange">
   <div class="description">{ts}Applying these settings via this form may cause your web server to time out. Applying changes on next scheduled job is recommended.{/ts}</div>
   <div class="label">{$form.when_to_apply_change.label}</div>
   <span>{$form.when_to_apply_change.html}</span>
 </div>

 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

