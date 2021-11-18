<table>
    <thead>
    <tr>
        <td style="{$styleth}">{l s='No.' mod='dpdconnect'}</td>
        <td style="{$styleth}">{l s='Labelnumber' mod='dpdconnect'}</td>
        <td style="{$styleth}">{l s='Carrier' mod='dpdconnect'}</td>
        <td style="{$styleth}">{l s='Company and name' mod='dpdconnect'}</td>
        <td style="{$styleth}">{l s='Address' mod='dpdconnect'}</td>
        <td style="{$styleth}">{l s='Postal code' mod='dpdconnect'}</td>
        <td style="{$styleth}">{l s='Place' mod='dpdconnect'}</td>
        <td style="{$styleth}">{l s='Reference number' mod='dpdconnect'}</td>
    </tr>
    </thead>
    <tbody>
    {foreach from=$list item=parcel key=key}
        <tr>
            <td style="{$styletd}"> {$key + 1} </td>
            <td style="{$styletd}"> {$parcel.parcelLabelNumber} </td>
            <td style="{$styletd}"> {$parcel.carrierName} </td>
            <td style="{$styletd}"> {$parcel.customerName} </td>
            <td style="{$styletd}"> {$parcel.address} </td>
            <td style="{$styletd}"> {$parcel.postcode} </td>
            <td style="{$styletd}"> {$parcel.city} </td>
            <td style="{$styletd}"> {$parcel.referenceNumber} </td>
        </tr>
    {/foreach}
    </tbody>
</table>
{if empty($list)}
    {l s='No DPD shipment selected.' mod='dpdconnect'}
{/if}
