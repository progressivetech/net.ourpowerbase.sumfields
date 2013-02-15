<h3>Summary Fields Settings</h3>

<table class="form-layout-compressed">
  <tr>
    <td colspan="2" class="description">Please indicate which of the available summary fields you would like to enable.</td>
  </tr>
  <tr class="crm-sumfields-form-block-sumfields_active_fields">
    <td class="label">{$form.active_fields.label}</td>
    <td>{$form.active_fields.html}</td>
  </tr> 
  <tr>
    <td colspan="2" class="description">Please indicate the contribution types you would like included when calculating contribution related summary fields.</td>
  </tr>
  <tr class="crm-sumfields-form-block-sumfields_contribution_type_ids">
    <td class="label">{$form.contribution_type_ids.label}</td>
    <td>{$form.contribution_type_ids.html}</td>
  </tr> 
  <tr>
    <td colspan="2" class="description">Please indicate the contribution types you would like included when calculating membership payment related summary fields.</td>
  </tr>
  <tr class="crm-sumfields-form-block-sumfields_membership_contribution_type_ids">
    <td class="label">{$form.membership_contribution_type_ids.label}</td>
    <td>{$form.membership_contribution_type_ids.html}</td>
  </tr> 
  <tr>
    <td colspan="2" class="description">Please indicate the event types you would like included when calculating event-related summary fields.</td>
  </tr>
  <tr class="crm-sumfields-form-block-sumfields_event_type_ids">
    <td class="label">{$form.event_type_ids.label}</td>
    <td>{$form.event_type_ids.html}</td>
  </tr>
  <tr>
    <td colspan="2" class="description">Please indicate the participat status you would like included when calculating event-related summary fields.</td>
  </tr>
  <tr class="crm-sumfields-form-block-sumfields_participant_status_ids">
    <td class="label">{$form.participant_status_ids.label}</td>
    <td>{$form.participant_status_ids.html}</td>
  </tr>

</table>
 <div class="description">Please be patient - when saving these settings, the summary table is re-generated from scratch. This procedure may take a few minutes.</div>
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

