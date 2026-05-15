<script>
(function () {
    var id = {$pb_id_product|intval};
    var badges = [
        {foreach from=$pb_badges item=badge name=loop}
        {
            position: '{$badge.position|escape:'javascript'}',
            bgcolor:  '{$badge.bgcolor|escape:'javascript'}',
            textcolor:'{$badge.textcolor|escape:'javascript'}',
            text:     '{$badge.text|escape:'javascript'}'
        }{if !$smarty.foreach.loop.last},{/if}
        {/foreach}
    ];

    var container = document.querySelector('[data-id-product="' + id + '"] .thumbnail-top');
    if (!container) { return; }
    container.style.position = 'relative';

    badges.forEach(function (b) {
        var el = document.createElement('span');
        el.className = 'product-badge product-badge--' + b.position;
        el.style.backgroundColor = b.bgcolor;
        el.style.color = b.textcolor;
        el.textContent = b.text;
        container.appendChild(el);
    });
}());
</script>
