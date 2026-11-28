(function () {
    const shared = window.surveyjsImport;
    const settings = window.surveyjsImportJson;
    const i18n = settings.i18n;
    const MAX_FILE_BYTES = 100 * 1024 * 1024;
    const form = document.querySelector("form[data-sjs-import-json]");
    if (!form || !shared || !shared.run) {
        return;
    }
    const fileInput = form.querySelector("#wp-sjs-import-json-files");
    const textarea = form.querySelector("#wp-sjs-import-json-text");
    const list = form.querySelector("[data-sjs-import-json-list]");
    const summary = form.querySelector("[data-sjs-import-json-summary]");
    const tbody = list.querySelector("tbody");
    const items = [];

    function isObject(value) {
        return !!value && typeof value === "object" && !Array.isArray(value);
    }

    function isSurvey(value) {
        return isObject(value) && ["pages", "elements", "questions"].some((key) => Array.isArray(value[key]) && value[key].length > 0);
    }

    function parse(value) {
        if (typeof value !== "string") {
            return value;
        }
        try {
            return JSON.parse(value);
        } catch (error) {
            return null;
        }
    }

    function title(json) {
        const value = isObject(json.title) ? json.title.default || Object.values(json.title)[0] : json.title;
        return typeof value === "string" ? value.trim() : "";
    }

    function megabytes(bytes) {
        return `${Math.round(bytes / 1024 / 102.4) / 10} MB`;
    }

    function byteLength(text) {
        return new TextEncoder().encode(text).length;
    }

    function formItem(name, json, theme, formSettings, file, fallbackName) {
        const item = {
            name: (typeof name === "string" && name.trim()) || title(json) || fallbackName || i18n.untitled,
            file: file,
            json: JSON.stringify(json),
            theme: isObject(theme) && Object.keys(theme).length ? JSON.stringify(theme) : "",
            settings: isObject(formSettings) ? JSON.stringify(formSettings) : "",
            error: ""
        };
        if (byteLength(item.json) + byteLength(item.theme) > settings.maxBytes) {
            item.error = shared.format(i18n.tooLarge, [megabytes(settings.maxBytes)]);
        }
        return item;
    }

    function errorItem(name, file, error) {
        return { name: name || i18n.untitled, file: file, error: error };
    }

    // A SurveyJS Forms export, a list of forms, a {name, json, theme, settings} entry, a My Surveys survey
    // ({name, definition, theme}) or a bare form JSON.
    function collect(value, file, fallbackName) {
        if (Array.isArray(value)) {
            return value.flatMap((entry) => collect(entry, file, ""));
        }
        if (!isObject(value)) {
            return [errorItem(fallbackName, file, i18n.notAForm)];
        }
        if (Array.isArray(value.forms) || Array.isArray(value.surveys)) {
            return collect(value.forms || value.surveys, file, fallbackName);
        }
        const wrapped = parse(value.json !== undefined ? value.json : value.definition);
        if (isSurvey(wrapped)) {
            return [formItem(value.name, wrapped, parse(value.theme), parse(value.settings), file, fallbackName)];
        }
        if (isSurvey(value)) {
            return [formItem("", value, null, null, file, fallbackName)];
        }
        return [errorItem((typeof value.name === "string" && value.name) || fallbackName, file, i18n.notAForm)];
    }

    function collectText(text, file, fallbackName) {
        const value = parse(text);
        return value === null ? [errorItem(fallbackName, file, i18n.invalidJson)] : collect(value, file, fallbackName);
    }

    function baseName(path) {
        return path.split("/").pop().replace(/\.json$/i, "");
    }

    async function inflate(bytes, limit) {
        const reader = new Blob([bytes]).stream().pipeThrough(new DecompressionStream("deflate-raw")).getReader();
        const chunks = [];
        let total = 0;
        for (;;) {
            const { done, value } = await reader.read();
            if (done) {
                break;
            }
            total += value.length;
            if (total > limit) {
                await reader.cancel();
                return null;
            }
            chunks.push(value);
        }
        return new Blob(chunks).text();
    }

    // Only stored and deflated entries of a regular (not ZIP64, not encrypted) archive, read from its central directory.
    async function readZip(file) {
        if (typeof DecompressionStream === "undefined") {
            return [errorItem(file.name, file.name, i18n.noZipSupport)];
        }
        const buffer = await file.arrayBuffer();
        const view = new DataView(buffer);
        let end = -1;
        for (let index = buffer.byteLength - 22; index >= Math.max(0, buffer.byteLength - 65557); index--) {
            if (view.getUint32(index, true) === 0x06054b50) {
                end = index;
                break;
            }
        }
        if (end < 0) {
            return [errorItem(file.name, file.name, i18n.badZip)];
        }
        const count = view.getUint16(end + 10, true);
        let offset = view.getUint32(end + 16, true);
        const decoder = new TextDecoder();
        const result = [];
        try {
            for (let entry = 0; entry < count; entry++) {
                if (view.getUint32(offset, true) !== 0x02014b50) {
                    throw new Error(i18n.badZip);
                }
                const flags = view.getUint16(offset + 8, true);
                const method = view.getUint16(offset + 10, true);
                const compressedSize = view.getUint32(offset + 20, true);
                const size = view.getUint32(offset + 24, true);
                const nameLength = view.getUint16(offset + 28, true);
                const local = view.getUint32(offset + 42, true);
                const path = decoder.decode(new Uint8Array(buffer, offset + 46, nameLength));
                offset += 46 + nameLength + view.getUint16(offset + 30, true) + view.getUint16(offset + 32, true);
                if (!/\.json$/i.test(path) || /(^|\/)(__MACOSX|\.)/.test(path)) {
                    continue;
                }
                const label = `${file.name} › ${path}`;
                if ((flags & 1) || (method !== 0 && method !== 8) || view.getUint32(local, true) !== 0x04034b50) {
                    result.push(errorItem(baseName(path), label, i18n.badZip));
                    continue;
                }
                if (size > MAX_FILE_BYTES) {
                    result.push(errorItem(baseName(path), label, shared.format(i18n.tooLarge, [megabytes(MAX_FILE_BYTES)])));
                    continue;
                }
                const start = local + 30 + view.getUint16(local + 26, true) + view.getUint16(local + 28, true);
                const bytes = new Uint8Array(buffer, start, compressedSize);
                const text = method === 0 ? decoder.decode(bytes) : await inflate(bytes, MAX_FILE_BYTES);
                result.push(...(text === null ? [errorItem(baseName(path), label, shared.format(i18n.tooLarge, [megabytes(MAX_FILE_BYTES)]))] : collectText(text, label, baseName(path))));
            }
        } catch (error) {
            result.push(errorItem(file.name, file.name, i18n.badZip));
        }
        return result.length ? result : [errorItem(file.name, file.name, i18n.emptyZip)];
    }

    async function readFile(file) {
        if (file.size > MAX_FILE_BYTES) {
            return [errorItem(file.name, file.name, shared.format(i18n.tooLarge, [megabytes(MAX_FILE_BYTES)]))];
        }
        if (/\.zip$/i.test(file.name)) {
            return readZip(file);
        }
        if (!/\.json$/i.test(file.name)) {
            return [errorItem(file.name, file.name, i18n.notJsonFile)];
        }
        return collectText(await file.text(), file.name, baseName(file.name));
    }

    function cell(tag, className, text) {
        const element = document.createElement(tag);
        if (className) {
            element.className = className;
        }
        if (text) {
            element.textContent = text;
        }
        return element;
    }

    function render() {
        const names = new Set(settings.names.map((name) => name.toLowerCase()));
        tbody.textContent = "";
        let skipped = 0;
        items.forEach((item, index) => {
            const id = `wp-sjs-import-json-${index}`;
            const row = cell("tr");
            const check = cell("th", "check-column");
            check.scope = "row";
            const checkbox = cell("input");
            checkbox.type = "checkbox";
            checkbox.id = id;
            checkbox.value = String(index);
            checkbox.checked = !item.error;
            checkbox.disabled = !!item.error;
            check.appendChild(checkbox);
            const name = cell("td", "column-primary");
            const label = cell("label", "", item.name);
            label.htmlFor = id;
            name.appendChild(label);
            let status;
            if (item.error) {
                skipped++;
                status = cell("td", "wp-sjs-import__status wp-sjs-import__error", item.error);
            } else {
                const duplicate = names.has(item.name.toLowerCase());
                status = cell("td", "wp-sjs-import__status" + (duplicate ? "" : " wp-sjs-import__new"), duplicate ? i18n.duplicate : i18n.newForm);
                if (item.theme) {
                    status.textContent += ` · ${i18n.withTheme}`;
                }
                names.add(item.name.toLowerCase());
            }
            row.append(check, name, cell("td", "wp-sjs-import__file", item.file), status);
            tbody.appendChild(row);
        });
        summary.textContent = shared.format(i18n.summary, [items.length - skipped, skipped]);
        list.hidden = !items.length;
    }

    fileInput.addEventListener("change", async () => {
        const files = Array.from(fileInput.files || []);
        fileInput.value = "";
        for (const file of files) {
            items.push(...(await readFile(file)));
        }
        render();
    });

    form.querySelector("[data-sjs-import-json-add]").addEventListener("click", () => {
        if (!textarea.value.trim()) {
            return;
        }
        items.push(...collectText(textarea.value, i18n.pasted, ""));
        textarea.value = "";
        render();
    });

    form.querySelector("[data-sjs-import-json-clear]").addEventListener("click", () => {
        items.length = 0;
        render();
    });

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        const selected = Array.from(tbody.querySelectorAll("input[type=checkbox]:checked:enabled")).map((checkbox) => items[Number(checkbox.value)]);
        if (!selected.length || !window.ajaxurl) {
            return;
        }
        form.querySelectorAll("input, button, textarea").forEach((input) => {
            input.disabled = true;
        });
        shared.run(form, selected, (item) => {
            const body = new FormData();
            body.append("action", "surveyjs_import_json");
            body.append("_ajax_nonce", settings.nonce);
            body.append("name", item.name);
            body.append("json", item.json);
            body.append("theme", item.theme);
            body.append("settings", item.settings);
            return shared.request(body);
        });
    });
})();
