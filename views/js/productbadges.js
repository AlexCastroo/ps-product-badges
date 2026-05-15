(function () {
    function applyBadges(container, badges) {
        container.style.position = 'relative';
        badges.forEach(function (b) {
            var el = document.createElement('span');
            el.className = 'product-badge product-badge--' + b.position;
            el.style.backgroundColor = b.bgcolor;
            el.style.color = b.textcolor;
            el.textContent = b.text;
            container.appendChild(el);
        });
    }

    function processQueue() {
        var queue = window._pbQueue || [];
        queue.forEach(function (item) {
            var container;
            if (item.type === 'listing') {
                container = document.querySelector('[data-id-product="' + item.id_product + '"] .thumbnail-top');
            } else if (item.type === 'product') {
                container = document.querySelector('.product-cover');
            }
            if (container) {
                applyBadges(container, item.badges);
            }
        });
        window._pbQueue = [];
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', processQueue);
    } else {
        processQueue();
    }
}());
