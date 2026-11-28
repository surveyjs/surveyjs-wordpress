(function () {
    const settings = window.surveyjsImport;

    function format(text, values) {
        return text.replace(/%(\d+)\$s|%s/g, (match, position) => String(values[position ? position - 1 : 0]));
    }

    async function request(params) {
        const response = await fetch(window.ajaxurl, {
            method: "POST",
            credentials: "same-origin",
            body: params instanceof FormData ? params : new URLSearchParams(params)
        });
        let body = null;
        try {
            body = await response.json();
        } catch (error) {
            throw new Error(response.ok ? settings.i18n.unknownError : `${response.status} ${response.statusText}`.trim());
        }
        if (!body || !body.success) {
            throw new Error((body && body.data && body.data.message) || (response.ok ? settings.i18n.unknownError : `${response.status} ${response.statusText}`.trim()));
        }
        return body.data;
    }

    function createElement(tag, className, text) {
        const element = document.createElement(tag);
        if (className) {
            element.className = className;
        }
        if (text) {
            element.textContent = text;
        }
        return element;
    }

    function errorLine(name, message) {
        const line = createElement("li");
        line.appendChild(createElement("strong", "", name));
        line.appendChild(document.createTextNode(` — ${format(settings.i18n.failed, [message])}`));
        return line;
    }

    async function run(form, items, importItem) {
        const status = createElement("div", "notice notice-info inline wp-sjs-import-status");
        const statusLine = createElement("p");
        const spinner = createElement("span", "spinner is-active");
        spinner.style.cssText = "float:none;margin:0 8px 0 0";
        const statusText = createElement("span");
        statusText.setAttribute("role", "status");
        statusLine.append(spinner, statusText);
        const progress = createElement("progress");
        progress.max = items.length;
        progress.value = 0;
        progress.style.cssText = "display:block;width:100%;max-width:400px;margin:0 0 8px";
        status.append(statusLine, progress);
        const results = createElement("ul", "ul-disc wp-sjs-import-results");
        form.after(status, results);

        let errors = 0;

        for (let index = 0; index < items.length; index++) {
            const item = items[index];
            statusText.textContent = format(settings.i18n.progress, [index + 1, items.length, item.name]);
            try {
                const data = await importItem(item, index, (text) => {
                    statusText.textContent = text;
                });
                if (data.error) {
                    errors++;
                }
                results.insertAdjacentHTML("beforeend", data.html);
            } catch (error) {
                errors++;
                results.appendChild(errorLine(item.name, error.message));
            }
            progress.value = index + 1;
        }

        spinner.remove();
        progress.remove();
        status.classList.replace("notice-info", errors ? "notice-warning" : "notice-success");
        statusText.textContent = errors ? format(settings.i18n.doneWithErrors, [items.length, errors]) : format(settings.i18n.done, [items.length]);
        form.hidden = true;
        const link = createElement("a", "button button-primary", settings.i18n.allForms);
        link.href = settings.listUrl;
        const linkLine = createElement("p");
        linkLine.appendChild(link);
        results.after(linkLine);
    }

    function importForms(form, forms) {
        const source = form.getAttribute("data-sjs-import");
        const nonce = form.querySelector('input[name="_wpnonce"]').value;
        const withEntries = form.querySelector('input[name="entries"]:checked') ? "1" : "";
        const limit = form.getAttribute("data-entries-per-request") || "";
        return run(form, forms, async (item, index, setStatus) => {
            let offset = 0;
            for (;;) {
                const data = await request({ action: "surveyjs_import", _ajax_nonce: nonce, source: source, form: item.id, entries: withEntries, offset: offset, limit: limit });
                if (data.done) {
                    return data;
                }
                offset = data.next;
                setStatus(format(settings.i18n.entriesProgress, [index + 1, forms.length, item.name, data.next, data.total]));
            }
        });
    }

    settings.run = run;
    settings.request = request;
    settings.format = format;

    document.addEventListener("submit", (event) => {
        const form = event.target;
        if (!form.matches || !form.matches("form[data-sjs-import]") || !window.ajaxurl) {
            return;
        }
        const forms = Array.from(form.querySelectorAll('input[name="forms[]"]:checked')).map((checkbox) => ({
            id: checkbox.value,
            name: checkbox.dataset.name || checkbox.closest("tr, label").textContent.trim()
        }));
        if (!forms.length) {
            return;
        }
        event.preventDefault();
        form.querySelectorAll("input, button").forEach((input) => {
            input.disabled = true;
        });
        importForms(form, forms);
    });
})();
