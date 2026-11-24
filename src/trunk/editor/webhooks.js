(function () {
    const api = window.surveyjsEditor;
    const container = document.getElementById("wp-sjs-webhooks");
    if (!api || !container) return;

    const { __, _n, sprintf } = wp.i18n;
    const config = api.config;
    const LOG_ROWS = 10;
    const SUMMARY_SEPARATOR = " · ";
    const CHOICE_TYPES = ["radiogroup", "checkbox", "dropdown", "tagbox", "imagepicker", "ranking", "buttongroup"];
    const valueInput = document.getElementById("wp-sjs-webhooks-value");
    const itemTemplate = document.getElementById("wp-sjs-webhook-template");
    const ruleTemplate = document.getElementById("wp-sjs-condition-template");
    const headerTemplate = document.getElementById("wp-sjs-header-template");
    const items = [];
    const open = new Set();
    let keyCounter = 0;
    let questions = [];
    let webhookLog = Array.isArray(config.webhookLog) ? config.webhookLog : [];

    function element(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function option(value, text) {
        const node = element("option", "", text);
        node.value = value;
        return node;
    }

    function randomString(length, alphabet) {
        const bytes = crypto.getRandomValues(new Uint8Array(length));
        return Array.from(bytes, (byte) => alphabet[byte % alphabet.length]).join("");
    }

    function randomId() {
        return "w" + randomString(8, "abcdefghijklmnopqrstuvwxyz0123456789");
    }

    function localized(text) {
        if (typeof text === "string") return text;
        if (text && typeof text === "object") return typeof text.default === "string" ? text.default : Object.values(text).find((value) => typeof value === "string") || "";
        return "";
    }

    function formQuestions() {
        const json = api.getSurvey();
        const found = [];
        const collect = (elements) => (Array.isArray(elements) ? elements : []).forEach((item) => {
            if (!item || typeof item !== "object") return;
            if (!item.type || item.type === "panel") return collect(item.elements);
            if (["html", "image"].includes(item.type) || typeof item.name !== "string" || !item.name) return;
            found.push(item);
        });
        if (json) {
            collect(json.pages);
            collect(json.elements);
        }
        return found;
    }

    function questionLabel(question) {
        const title = localized(question.title).trim();
        if (!title || title === question.name) return question.name;
        return (title.length > 40 ? title.slice(0, 39) + "…" : title) + " (" + question.name + ")";
    }

    function questionChoices(question) {
        if (!question) return null;
        if (question.type === "boolean") {
            return [
                { value: String(question.valueTrue ?? "true"), text: localized(question.labelTrue) || __("Yes", "surveyjs") },
                { value: String(question.valueFalse ?? "false"), text: localized(question.labelFalse) || __("No", "surveyjs") }
            ];
        }
        if (!CHOICE_TYPES.includes(question.type) || !Array.isArray(question.choices) || question.choices.length === 0) return null;
        return question.choices.map((choice) => {
            if (choice && typeof choice === "object") {
                const value = String(choice.value ?? "");
                return { value: value, text: localized(choice.text) || value };
            }
            return { value: String(choice), text: String(choice) };
        });
    }

    function fillSelect(select, entries, value, emptyLabel) {
        const options = [option("", emptyLabel)].concat(entries.map((entry) => option(entry.value, entry.text)));
        if (value && !entries.some((entry) => entry.value === value)) {
            options.push(option(value, sprintf(__("%s (not in the form)", "surveyjs"), value)));
        }
        select.replaceChildren(...options);
        select.value = value || "";
    }

    function webhookHost(url) {
        try {
            return new URL(url).host;
        } catch (error) {
            return "";
        }
    }

    function load() {
        const openIds = new Set(Array.from(open, (item) => item.id));
        const stored = api.getSettings().webhooks;
        items.splice(0, items.length, ...(Array.isArray(stored) ? stored : []).filter((item) => item && typeof item === "object"));
        items.forEach((item) => {
            if (!item.conditions || !Array.isArray(item.conditions.rules)) item.conditions = { logic: "all", rules: [] };
            if (!Array.isArray(item.headers)) item.headers = [];
        });
        open.clear();
        items.filter((item) => openIds.has(item.id)).forEach((item) => open.add(item));
    }

    function commit() {
        valueInput.value = JSON.stringify(items);
        valueInput.dispatchEvent(new Event("change", { bubbles: true }));
    }

    function render(focusItem) {
        const nodes = items.map(renderItem);
        if (nodes.length === 0) {
            nodes.push(element("p", "description wp-sjs-items__empty", __("No webhooks yet.", "surveyjs")));
        }
        container.replaceChildren(...nodes);
        if (focusItem) {
            container.children[items.indexOf(focusItem)]?.querySelector('[data-field="name"]')?.focus();
        }
    }

    function renderItem(item) {
        const key = String(++keyCounter);
        const node = itemTemplate.content.firstElementChild.cloneNode(true);
        node.querySelectorAll("[id], [for], [aria-controls]").forEach((target) => {
            ["id", "for", "aria-controls"].forEach((attribute) => {
                const value = target.getAttribute(attribute);
                if (value && value.includes("__KEY__")) target.setAttribute(attribute, value.replace("__KEY__", key));
            });
        });
        node.querySelectorAll("[data-field]").forEach((input) => bindField(item, node, input));
        renderConditions(item, node);
        renderHeaders(item, node);
        renderLog(item, node);
        setOpen(item, node, open.has(item));
        node.addEventListener("click", (event) => onAction(event, item, node));
        refresh(item, node);
        return node;
    }

    function bindField(item, node, input) {
        const field = input.dataset.field;
        if (input.type === "checkbox") {
            input.checked = Boolean(item[field]);
        } else {
            input.value = item[field] ?? "";
        }
        input.addEventListener(input.type === "checkbox" ? "change" : "input", () => {
            item[field] = input.type === "checkbox" ? input.checked : input.value;
            refresh(item, node);
            commit();
        });
    }

    function refresh(item, node) {
        node.classList.toggle("is-inactive", !item.enabled);
        node.querySelector("[data-name]").textContent = item.name || __("(no name)", "surveyjs");
        const rules = item.conditions.rules.length;
        const summary = [webhookHost(item.url) || __("No URL yet", "surveyjs")].concat(rules ? [sprintf(_n("%d condition", "%d conditions", rules, "surveyjs"), rules)] : []);
        node.querySelector("[data-summary]").textContent = summary.join(SUMMARY_SEPARATOR);
    }

    function setOpen(item, node, isOpen) {
        if (isOpen) open.add(item); else open.delete(item);
        node.querySelector(".wp-sjs-item__body").hidden = !isOpen;
        node.querySelector('[data-action="toggle"]').setAttribute("aria-expanded", String(isOpen));
        node.classList.toggle("is-open", isOpen);
    }

    function renderConditions(item, node) {
        const box = node.querySelector("[data-conditions]");
        const rules = item.conditions.rules;
        box.querySelector("[data-when-empty]").hidden = rules.length > 0;
        box.querySelector("[data-when-rules]").hidden = rules.length === 0;
        const logic = box.querySelector("[data-condition-logic]");
        logic.value = item.conditions.logic;
        logic.onchange = () => {
            item.conditions.logic = logic.value;
            commit();
        };
        box.querySelector("[data-rules]").replaceChildren(...rules.map((rule, index) => renderRule(item, node, rule, index)));
    }

    function renderRule(item, node, rule, index) {
        const row = ruleTemplate.content.firstElementChild.cloneNode(true);
        const questionSelect = row.querySelector('[data-rule="question"]');
        const operatorSelect = row.querySelector('[data-rule="operator"]');
        const textInput = row.querySelector('[data-rule="value"]');
        const choiceSelect = row.querySelector('[data-rule="choice"]');
        fillSelect(questionSelect, questions.map((question) => ({ value: question.name, text: questionLabel(question) })), rule.question, __("— Question —", "surveyjs"));
        operatorSelect.value = rule.operator;
        const choices = questionChoices(questions.find((question) => question.name === rule.question));
        const noValue = ["empty", "notempty"].includes(rule.operator);
        textInput.hidden = noValue || Boolean(choices);
        choiceSelect.hidden = noValue || !choices;
        textInput.value = rule.value;
        if (choices) fillSelect(choiceSelect, choices, rule.value, __("— Choose —", "surveyjs"));
        const update = (field, value, rerender) => {
            rule[field] = value;
            if (rerender) renderConditions(item, node);
            refresh(item, node);
            commit();
        };
        questionSelect.addEventListener("change", () => {
            rule.value = "";
            update("question", questionSelect.value, true);
        });
        operatorSelect.addEventListener("change", () => update("operator", operatorSelect.value, true));
        textInput.addEventListener("input", () => update("value", textInput.value, false));
        choiceSelect.addEventListener("change", () => update("value", choiceSelect.value, false));
        row.querySelector('[data-action="remove-rule"]').addEventListener("click", (event) => {
            event.stopPropagation();
            item.conditions.rules.splice(index, 1);
            renderConditions(item, node);
            refresh(item, node);
            commit();
        });
        return row;
    }

    function renderHeaders(item, node) {
        node.querySelector("[data-headers]").replaceChildren(...item.headers.map((header, index) => {
            const row = headerTemplate.content.firstElementChild.cloneNode(true);
            row.querySelectorAll("[data-header]").forEach((input) => {
                input.value = header[input.dataset.header] ?? "";
                input.addEventListener("input", () => {
                    header[input.dataset.header] = input.value;
                    commit();
                });
            });
            row.querySelector('[data-action="remove-header"]').addEventListener("click", (event) => {
                event.stopPropagation();
                item.headers.splice(index, 1);
                renderHeaders(item, node);
                commit();
            });
            return row;
        }));
    }

    function renderLog(item, node) {
        const table = node.querySelector("[data-log]");
        const entries = webhookLog.filter((entry) => entry.webhook === item.id).slice(0, LOG_ROWS);
        node.querySelector("[data-log-empty]").hidden = entries.length > 0;
        table.hidden = entries.length === 0;
        table.tBodies[0].replaceChildren(...entries.map((entry) => {
            const row = element("tr");
            let request = entry.event === "test" ? __("Test", "surveyjs") : entry.entry ? sprintf(__("Entry #%d", "surveyjs"), entry.entry) : __("Entry (not stored)", "surveyjs");
            if (entry.attempt > 1) request += " " + sprintf(__("(retry %d)", "surveyjs"), entry.attempt - 1);
            let result = entry.ok ? sprintf(__("Delivered (HTTP %d)", "surveyjs"), entry.code) : entry.error;
            if (entry.retry) result += " " + __("Will retry.", "surveyjs");
            row.append(element("td", "", entry.time), element("td", "", request), element("td", entry.ok ? "" : "wp-sjs-webhook-log__error", result));
            return row;
        }));
    }

    function showResult(target, type, message) {
        const notice = element("div", "notice notice-" + type + " inline");
        notice.append(element("p", "", message));
        target.replaceChildren(notice);
    }

    function sendTest(button, item, node) {
        const result = node.querySelector("[data-test-result]");
        button.disabled = true;
        result.replaceChildren();
        fetch(config.testWebhookUrl, {
            method: "POST",
            body: new URLSearchParams({ Id: config.id, Settings: JSON.stringify(api.getSettings()), Webhook: item.id, _wpnonce: config.saveNonce })
        })
            .then((response) => response.json())
            .then((response) => {
                showResult(result, response.success ? "success" : "error", response.data?.message || __("The request failed.", "surveyjs"));
                if (Array.isArray(response.data?.log)) {
                    webhookLog = response.data.log;
                    renderLog(item, node);
                }
            })
            .catch((error) => showResult(result, "error", error.message))
            .finally(() => { button.disabled = false; });
    }

    function onAction(event, item, node) {
        const button = event.target.closest("[data-action]");
        if (!button || !node.contains(button)) return;
        const action = button.dataset.action;
        if (action === "toggle") {
            setOpen(item, node, !open.has(item));
        } else if (action === "delete") {
            if (!window.confirm(container.dataset.deleteConfirm)) return;
            items.splice(items.indexOf(item), 1);
            open.delete(item);
            commit();
            render();
        } else if (action === "add-rule") {
            item.conditions.rules.push({ question: "", operator: "equal", value: "" });
            renderConditions(item, node);
            refresh(item, node);
            commit();
            node.querySelector("[data-rules]").lastElementChild?.querySelector('[data-rule="question"]').focus();
        } else if (action === "add-header") {
            item.headers.push({ name: "", value: "" });
            renderHeaders(item, node);
            commit();
            node.querySelector("[data-headers]").lastElementChild?.querySelector('[data-header="name"]').focus();
        } else if (action === "test-webhook") {
            sendTest(button, item, node);
        }
    }

    document.getElementById("wp-sjs-webhook-add").addEventListener("click", () => {
        const item = {
            id: randomId(),
            name: __("New webhook", "surveyjs"),
            enabled: true,
            url: "",
            headers: [],
            conditions: { logic: "all", rules: [] }
        };
        items.push(item);
        open.add(item);
        commit();
        render(item);
    });

    document.addEventListener("surveyjs:editor-tab", (event) => {
        if (event.detail?.tab !== "webhooks") return;
        questions = formQuestions();
        render();
    });
    document.addEventListener("surveyjs:editor-settings-filled", () => {
        questions = formQuestions();
        load();
        render();
    });

    questions = formQuestions();
    load();
    render();
})();
