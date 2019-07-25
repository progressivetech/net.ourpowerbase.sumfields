{literal}
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
  function switch_simplified() {
      CRM.$('.CRM_Sumfields_Form_SumFields tr.crm-sumfields-form-block-sumfields_active_fundraising_fields td.value input[id$="simplified"]').show();
      CRM.$('.CRM_Sumfields_Form_SumFields tr.crm-sumfields-form-block-sumfields_active_fundraising_fields td.value label[for$="simplified"]').show();
      CRM.$('.CRM_Sumfields_Form_SumFields tr.crm-sumfields-form-block-sumfields_active_fundraising_fields td.value input').not('[id$="simplified"]').prop( "checked", false ).hide();
      CRM.$('.CRM_Sumfields_Form_SumFields tr.crm-sumfields-form-block-sumfields_active_fundraising_fields td.value label').not('[for$="simplified"]').hide();
  }
  function switch_normal() {
      CRM.$('.CRM_Sumfields_Form_SumFields tr.crm-sumfields-form-block-sumfields_active_fundraising_fields td.value input[id$="simplified"]').prop( "checked", false ).hide();
      CRM.$('.CRM_Sumfields_Form_SumFields tr.crm-sumfields-form-block-sumfields_active_fundraising_fields td.value label[for$="simplified"]').hide();
      CRM.$('.CRM_Sumfields_Form_SumFields tr.crm-sumfields-form-block-sumfields_active_fundraising_fields td.value input').not('[id$="simplified"]').show();
      CRM.$('.CRM_Sumfields_Form_SumFields tr.crm-sumfields-form-block-sumfields_active_fundraising_fields td.value label').not('[for$="simplified"]').show();
  }
</script>
{/literal}
