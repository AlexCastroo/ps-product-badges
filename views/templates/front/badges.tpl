{if $pb_badges}
<div class="productbadges-wrapper">
    {foreach from=$pb_badges item=badge}
    <span class="product-badge product-badge--{$badge.position|escape:'html'}"
          style="background-color:{$badge.bgcolor|escape:'html'};color:{$badge.textcolor|escape:'html'};">
        {$badge.text|escape:'html'}
    </span>
    {/foreach}
</div>
{/if}
