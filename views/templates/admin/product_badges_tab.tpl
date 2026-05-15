<script>
function pbSaveBadges() {
    var checked = [].slice.call(document.querySelectorAll('input[name="badge_product_ids[]"]:checked'))
                    .map(function(cb) { return cb.value; });
    var data = new FormData();
    data.append('id_product', '{$pb_id_product|intval}');
    checked.forEach(function(id) { data.append('badge_product_ids[]', id); });
    fetch('{$pb_ajax_url|escape:"javascript"}', { method: 'POST', body: data });
}
</script>
<div class="panel">
    <div class="panel-heading">
        <i class="icon-tag"></i>
        {l s='Product Badges' mod='productbadges'}
    </div>
    <div class="panel-body">
        {if $pb_badges}
            {foreach from=$pb_badges item=badge}
            <div class="checkbox">
                <label>
                    <input type="checkbox"
                           name="badge_product_ids[]"
                           value="{$badge.id_badge|intval}"
                           {if $badge.is_assigned}checked="checked"{/if}
                           onchange="pbSaveBadges()">
                    <span style="display:inline-block;
                                 background:{$badge.bgcolor|escape:'html'};
                                 color:{$badge.textcolor|escape:'html'};
                                 padding:2px 8px;
                                 border-radius:3px;
                                 font-size:12px;
                                 margin-left:6px;">
                        {$badge.text|escape:'html'}
                    </span>
                    <small class="text-muted" style="margin-left:6px;">
                        {$badge.position|escape:'html'}
                    </small>
                </label>
            </div>
            {/foreach}
        {else}
            <p class="text-muted">
                {l s='No active badges. Create them in Catalog > Product Badges.' mod='productbadges'}
            </p>
        {/if}
    </div>
</div>
