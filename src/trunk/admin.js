/* Shared behaviour of the SurveyJS Forms admin pages. */
(function () {
    // Snackbars: short messages about something the user just did (Form saved, Entries deleted), over the page in
    // the bottom left corner instead of banners in the layout. Success and info close by themselves after 5 seconds
    // (the timer waits while the pointer or focus is on the snackbar); errors and warnings stay until closed.
    // Other scripts use window.surveyjsSnackbar.show({ type, message, actions: [{ label, onClick }], sticky }).
    const SNACKBAR_MS = 5000;
    const snackbarRegion = document.createElement("div");
    snackbarRegion.className = "wp-sjs-snackbars";
    snackbarRegion.setAttribute("aria-live", "polite");
    document.body.append(snackbarRegion);

    function showSnackbar(options) {
        const type = options.type || "success";
        const sticky = options.sticky !== undefined ? options.sticky : type === "error" || type === "warning";
        const node = document.createElement("div");
        node.className = "wp-sjs-snackbar is-" + type;
        if (type === "error") {
            node.setAttribute("role", "alert");
        }
        if (type !== "info") {
            const icon = document.createElement("span");
            icon.className = "wp-sjs-snackbar__icon dashicons dashicons-" + (type === "success" ? "yes-alt" : "warning");
            icon.setAttribute("aria-hidden", "true");
            node.append(icon);
        }
        const text = document.createElement("span");
        text.className = "wp-sjs-snackbar__text";
        text.textContent = options.message;
        node.append(text);
        (options.actions || []).forEach(function (action) {
            const button = document.createElement("button");
            button.type = "button";
            button.className = "wp-sjs-snackbar__action";
            button.textContent = action.label;
            button.addEventListener("click", function () {
                close();
                action.onClick();
            });
            node.append(button);
        });
        const dismiss = document.createElement("button");
        dismiss.type = "button";
        dismiss.className = "wp-sjs-snackbar__close";
        dismiss.setAttribute("aria-label", (window.surveyjsSnackbarL10n || {}).dismiss || "Dismiss this notice.");
        dismiss.innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path fill="currentColor" d="m13.06 12 6.47-6.47-1.06-1.06L12 10.94 5.53 4.47 4.47 5.53 10.94 12l-6.47 6.47 1.06 1.06L12 13.06l6.47 6.47 1.06-1.06L13.06 12Z"/></svg>';
        dismiss.addEventListener("click", close);
        node.append(dismiss);

        let timer = 0;
        let closed = false;
        function start() {
            if (!sticky && !closed) {
                window.clearTimeout(timer);
                timer = window.setTimeout(close, SNACKBAR_MS);
            }
        }
        function stop() {
            window.clearTimeout(timer);
        }
        function close() {
            if (closed) {
                return;
            }
            closed = true;
            stop();
            node.classList.add("is-leaving");
            window.setTimeout(function () {
                node.remove();
            }, 150);
        }
        node.addEventListener("mouseenter", stop);
        node.addEventListener("mouseleave", start);
        node.addEventListener("focusin", stop);
        node.addEventListener("focusout", function (event) {
            if (!node.contains(event.relatedTarget)) {
                start();
            }
        });
        snackbarRegion.append(node);
        start();
        return { close: close, element: node };
    }
    window.surveyjsSnackbar = { show: showSnackbar };

    // Notices that the server renders after an action (data-sjs-snackbar) become snackbars. Without JavaScript they
    // stay ordinary notices.
    document.querySelectorAll(".notice[data-sjs-snackbar]").forEach(function (notice) {
        const type = ["error", "warning", "info"].find(function (name) {
            return notice.classList.contains("notice-" + name);
        }) || "success";
        showSnackbar({ type: type, message: (notice.querySelector("p") || notice).textContent.trim().replace(/\s+/g, " ") });
        notice.remove();
    });

    // WordPress moves notices to .wp-header-end but leaves the "WordPress X is available" nag at the very top,
    // above the form's header bar. Move it to the same place as the other notices.
    const headerEnd = document.querySelector(".wrap .wp-header-end");
    if (headerEnd) {
        document.querySelectorAll("#wpbody-content > .update-nag").forEach(function (nag) {
            headerEnd.after(nag);
        });
    }

    // Form switcher (<details>): close it on a click outside or on Escape.
    document.addEventListener("click", function (event) {
        document.querySelectorAll(".wp-sjs-form-switcher[open]").forEach(function (node) {
            if (!node.contains(event.target)) {
                node.open = false;
            }
        });
    });
    document.addEventListener("keydown", function (event) {
        if (event.key !== "Escape") {
            return;
        }
        document.querySelectorAll(".wp-sjs-form-switcher[open]").forEach(function (node) {
            node.open = false;
            node.querySelector("summary").focus();
        });
    });

    // Live search for the list tables (All Forms, Entries): results update while typing, no Search button.
    // The page is fetched with the new "s" and its rows, pagination and entry data replace the current ones,
    // so the search field keeps focus. Without JS the Search button stays and the form submits as before.
    document.querySelectorAll(".wrap form .search-box").forEach(function (box) {
        const input = box.querySelector('input[type="search"]');
        const button = box.querySelector('input[type="submit"], button[type="submit"]');
        const form = box.closest("form");
        if (!input || !form) {
            return;
        }
        if (button) {
            if (!input.placeholder) {
                input.placeholder = button.value || button.textContent.trim();
            }
            button.hidden = true;
        }
        let timer = 0;
        let controller = null;
        const swap = [
            "#the-list",
            ".tablenav.top .tablenav-pages",
            ".tablenav.bottom .tablenav-pages",
            "#wp-sjs-results-data",
            "#wp-sjs-entry-panel"
        ];
        function run() {
            const url = new URL(window.location.href);
            const term = input.value.trim();
            if (term) {
                url.searchParams.set("s", term);
            } else {
                url.searchParams.delete("s");
            }
            url.searchParams.delete("paged");
            if (controller) {
                controller.abort();
            }
            controller = new AbortController();
            fetch(url, { credentials: "same-origin", signal: controller.signal })
                .then(function (response) {
                    return response.ok ? response.text() : Promise.reject(new Error(response.statusText));
                })
                .then(function (html) {
                    const next = new DOMParser().parseFromString(html, "text/html");
                    swap.forEach(function (selector) {
                        const current = document.querySelector(selector);
                        const fresh = next.querySelector(selector);
                        if (current && fresh) {
                            current.replaceWith(document.importNode(fresh, true));
                        }
                    });
                    window.history.replaceState(window.history.state, "", url);
                    document.dispatchEvent(new CustomEvent("surveyjs:list-updated"));
                })
                .catch(function (error) {
                    if (error.name !== "AbortError") {
                        form.submit();
                    }
                });
        }
        input.addEventListener("input", function () {
            window.clearTimeout(timer);
            timer = window.setTimeout(run, 300);
        });
        input.addEventListener("keydown", function (event) {
            if (event.key === "Enter") {
                event.preventDefault();
                window.clearTimeout(timer);
                run();
            }
        });
    });
})();
