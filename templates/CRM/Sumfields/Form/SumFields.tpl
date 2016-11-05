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

{foreach from=$fieldsets key="title" item="fields"}
  <fieldset>
    <legend>{$title}</legend>
    <table class="form-layout-compressed">
      {foreach from=$fields key="name" item="description"}
        <tr class="crm-sumfields-form-block-sumfields_{$name}">
          <td class="label">{$form.$name.label}</td>
          <td>
            {$form.$name.html}
            {if $description}<div class="description">{$description}</div>{/if}
          </td>
        </tr>
      {/foreach}
    </table>
  </fieldset>
{/foreach}

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

