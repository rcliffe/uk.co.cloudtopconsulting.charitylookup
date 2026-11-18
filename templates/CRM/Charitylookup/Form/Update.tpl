<div class="crm-block crm-form-block crm-charitylookup-update-form-block">
  {if !$hasApiKey}
    <div class="messages status no-popup">
      {icon icon="fa-exclamation-triangle"}{/icon}
      {ts domain="uk.co.cloudtopconsulting.charitylookup"}The Charity Commission API key has not been set.{/ts}
      {if $isAdmin}
        <a href="{$settingsUrl}">{ts domain="uk.co.cloudtopconsulting.charitylookup"}Configure it here.{/ts}</a>
      {/if}
    </div>
  {/if}
  <p class="description">
    {ts domain="uk.co.cloudtopconsulting.charitylookup"}Looks the number up on the Register of Charities (England &amp; Wales) and updates this organisation's legal name, Charity Commission details and, if enabled, its address, phone, email and website.{/ts}
  </p>
  <table class="form-layout-compressed">
    <tr>
      <td class="label">{$form.registered_number.label}</td>
      <td>{$form.registered_number.html}
        <div class="description">{ts domain="uk.co.cloudtopconsulting.charitylookup"}For a linked charity use the form 1234567-1.{/ts}</div>
      </td>
    </tr>
    {if $lastLookup}
      <tr>
        <td class="label">{ts domain="uk.co.cloudtopconsulting.charitylookup"}Last checked{/ts}</td>
        <td>{$lastLookup|crmDate}</td>
      </tr>
    {/if}
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
