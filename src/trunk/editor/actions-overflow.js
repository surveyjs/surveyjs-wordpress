/* Form editor header: the action buttons stay on the title's line. When they don't fit next to the title, they
   go one by one into a "…" menu before Save, least needed first: Save as Template, Embed, Full screen (Add-on),
   Full preview, AI assistant. Save always stays. A hidden button keeps working: its menu item clicks it.
   The title gets at least 200px (or its full width when shorter) and ends with an ellipsis below that. */
(function () {
    const { __ } = wp.i18n;
    const actions = document.querySelector(".wp-sjs-editor__actions");
    const crumbs = document.querySelector(".wp-sjs-editor__crumbs");
    if (!actions || !crumbs) {
        return;
    }
    const TITLE_MIN = 200;
    const PRIORITY = [
        "#wp-sjs-save-template",
        "#wp-sjs-embed",
        ".wp-sjs-pro-toolbar-button",
        "#wp-sjs-full-preview",
        ".wp-sjs-chat-toggle"
    ];

    const more = document.createElement("div");
    more.className = "wp-sjs-editor__more";
    more.hidden = true;
    const toggle = document.createElement("button");
    toggle.type = "button";
    toggle.className = "button wp-sjs-button-icon wp-sjs-editor__more-toggle";
    toggle.setAttribute("aria-haspopup", "menu");
    toggle.setAttribute("aria-expanded", "false");
    toggle.setAttribute("aria-label", __("More actions", "surveyjs"));
    toggle.innerHTML = '<span class="dashicons dashicons-ellipsis" aria-hidden="true"></span>';
    const menu = document.createElement("ul");
    menu.className = "wp-sjs-editor__more-menu";
    menu.setAttribute("role", "menu");
    menu.hidden = true;
    more.append(toggle, menu);

    let hidden = [];
    let busy = false;

    function buttons() {
        return Array.from(actions.children).filter(function (node) {
            return node !== more && !node.classList.contains("wp-sjs-editor__save");
        });
    }

    function ranked() {
        const list = buttons();
        const order = [];
        PRIORITY.forEach(function (selector) {
            list.forEach(function (node) {
                if (node.matches(selector) && !order.includes(node)) {
                    order.push(node);
                }
            });
        });
        // Buttons added by others and not listed go first.
        return list.filter(function (node) {
            return !order.includes(node);
        }).concat(order);
    }

    const header = document.querySelector(".wp-sjs-editor__header") || actions.parentElement;
    // The form name (the inline-rename button) measures the title: it gets the room it needs, or at least 200px.
    function fits() {
        const title = document.getElementById("wp-sjs-name-edit") || crumbs;
        const shown = title.hidden ? crumbs : title;
        return shown.clientWidth >= Math.min(shown.scrollWidth, TITLE_MIN) && header.scrollWidth <= header.clientWidth + 1;
    }

    function layout() {
        if (busy) {
            return;
        }
        busy = true;
        closeMenu();
        hidden.forEach(function (node) {
            node.classList.remove("wp-sjs-is-overflowed");
        });
        hidden = [];
        more.hidden = true;
        if (!fits()) {
            more.hidden = false;
            const queue = ranked();
            while (!fits() && queue.length) {
                const node = queue.shift();
                node.classList.add("wp-sjs-is-overflowed");
                hidden.push(node);
            }
        }
        busy = false;
    }

    function label(node) {
        const text = node.querySelector(".wp-sjs-pro-toolbar-button__label, span:not(.dashicons):not(.screen-reader-text)");
        return (text && !text.classList.contains("wp-sjs-chat__sparkle") ? text.textContent : node.textContent).trim();
    }

    function fillMenu() {
        menu.replaceChildren();
        buttons().filter(function (node) {
            return hidden.includes(node);
        }).forEach(function (node) {
            const item = document.createElement("li");
            item.setAttribute("role", "none");
            const action = document.createElement("button");
            action.type = "button";
            action.className = "wp-sjs-editor__more-item";
            action.setAttribute("role", "menuitem");
            const icon = node.querySelector(".dashicons, svg");
            if (icon) {
                action.append(icon.cloneNode(true));
            }
            action.append(document.createTextNode(label(node)));
            action.addEventListener("click", function () {
                closeMenu();
                node.click();
            });
            item.append(action);
            menu.append(item);
        });
    }

    function openMenu() {
        fillMenu();
        menu.hidden = false;
        toggle.setAttribute("aria-expanded", "true");
        const first = menu.querySelector("button");
        if (first) {
            first.focus();
        }
    }

    function closeMenu(focusToggle) {
        if (menu.hidden) {
            return;
        }
        menu.hidden = true;
        toggle.setAttribute("aria-expanded", "false");
        if (focusToggle) {
            toggle.focus();
        }
    }

    toggle.addEventListener("click", function () {
        if (menu.hidden) {
            openMenu();
        } else {
            closeMenu();
        }
    });
    document.addEventListener("click", function (event) {
        if (!more.contains(event.target)) {
            closeMenu();
        }
    });
    menu.addEventListener("keydown", function (event) {
        const items = Array.from(menu.querySelectorAll("button"));
        const index = items.indexOf(document.activeElement);
        if (event.key === "Escape") {
            event.preventDefault();
            closeMenu(true);
        } else if (event.key === "ArrowDown" || event.key === "ArrowUp") {
            event.preventDefault();
            const step = event.key === "ArrowDown" ? 1 : -1;
            items[(index + step + items.length) % items.length].focus();
        }
    });

    const save = actions.querySelector(".wp-sjs-editor__save");
    actions.insertBefore(more, save);

    let frame = 0;
    function schedule() {
        window.cancelAnimationFrame(frame);
        frame = window.requestAnimationFrame(layout);
    }
    new ResizeObserver(schedule).observe(header);
    // The Add-on (Full screen) and the AI chat (AI assistant) add their buttons later; labels change too.
    // Changes inside the "…" menu itself (filling it) are not a reason to lay out again.
    new MutationObserver(function (records) {
        if (!busy && records.some(function (record) {
            return !more.contains(record.target);
        })) {
            schedule();
        }
    }).observe(actions, { childList: true, subtree: true, characterData: true });
    schedule();
})();
