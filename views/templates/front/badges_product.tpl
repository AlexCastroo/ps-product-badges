<script>
window._pbQueue = window._pbQueue || [];
window._pbQueue.push({
    type: 'product',
    badges: [
        {foreach from=$pb_badges item=badge name=loop}
        {
            position: '{$badge.position|escape:'javascript'}',
            bgcolor:  '{$badge.bgcolor|escape:'javascript'}',
            textcolor:'{$badge.textcolor|escape:'javascript'}',
            text:     '{$badge.text|escape:'javascript'}'
        }{if !$smarty.foreach.loop.last},{/if}
        {/foreach}
    ]
});
</script>
