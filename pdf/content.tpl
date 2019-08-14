<table>
    <thead>
    <tr>
        <td style="{$styleth}">Nr.</td>
        <td style="{$styleth}">Pakket Nr.</td>
        <td style="{$styleth}">Type verzending</td>
        <td style="{$styleth}">Bedrijf en naam</td>
        <td style="{$styleth}">Adres</td>
        <td style="{$styleth}">Postcode</td>
        <td style="{$styleth}">Plaats</td>
        <td style="{$styleth}">Referentienummer</td>
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
    Geen DPD verzending geselecteerd.
{/if}
