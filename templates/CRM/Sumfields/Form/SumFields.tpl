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
    <td colspan="2" class="description">Please indicate the contribution types you would like included when calculating contribution-related summary fields.</td>
  </tr>
  <tr class="crm-sumfields-form-block-sumfields_contribution_type_ids">
    <td class="label">{$form.contribution_type_ids.label}</td>
    <td>{$form.contribution_type_ids.html}</td>
  </tr> 
</table>
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

