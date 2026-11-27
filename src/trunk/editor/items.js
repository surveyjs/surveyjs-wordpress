(function () {
    const api = window.surveyjsEditor;
    if (!api) return;

    const { __, _n, sprintf } = wp.i18n;
    const config = api.config;
    const LOG_ROWS = 10;
    const SUMMARY_SEPARATOR = " · ";
    const CHOICE_TYPES = ["radiogroup", "checkbox", "dropdown", "tagbox", "imagepicker", "ranking", "buttongroup"];
    const QUESTION_FILTERS = {
        email: (question) => question.type === "text" && question.inputType === "email"
    };
    const ruleTemplate = document.getElementById("wp-sjs-condition-template");
    const questionListeners = [];
    let keyCounter = 0;
    let questions = [];
    let deliveryLog = Array.isArray(config.deliveryLog) ? config.deliveryLog : [];

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

    function randomId(prefix) {
        const alphabet = "abcdefghijklmnopqrstuvwxyz0123456789";
        return prefix + Array.from(crypto.getRandomValues(new Uint8Array(8)), (byte) => alphabet[byte % alphabet.length]).join("");
    }

    function emptyConditions() {
        return { logic: "all", rules: [] };
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

    function fillSelect(select, entries, value, emptyLabel, missingFormat) {
        const options = [option("", emptyLabel)].concat(entries.map((entry) => option(entry.value, entry.text)));
        if (value && !entries.some((entry) => entry.value === value)) {
            options.push(option(value, sprintf(missingFormat || __("%s (not in the form)", "surveyjs"), value)));
        }
        select.replaceChildren(...options);
        select.value = value || "";
    }

    function fillQuestionSelect(select, value) {
        const filter = QUESTION_FILTERS[select.dataset.questions];
        const matching = filter ? questions.filter(filter) : [];
        const list = matching.length ? matching : questions;
        fillSelect(select, list.map((question) => ({ value: question.name, text: questionLabel(question) })), value, __("— None —", "surveyjs"));
    }

    function showResult(target, type, message) {
        const notice = element("div", "notice notice-" + type + " inline");
        notice.append(element("p", "", message));
        target.replaceChildren(notice);
    }

    function post(url, fields) {
        return fetch(url, {
            method: "POST",
            body: new URLSearchParams(Object.assign({ Id: config.id, Settings: JSON.stringify(api.getSettings()), _wpnonce: config.saveNonce }, fields))
        }).then((response) => response.json());
    }

    function runAction(button, target, request) {
        button.disabled = true;
        target.replaceChildren();
        return request()
            .then((response) => {
                showResult(target, response.success ? "success" : "error", response.data?.message || __("The request failed.", "surveyjs"));
                if (Array.isArray(response.data?.log)) deliveryLog = response.data.log;
                return response;
            })
            .catch((error) => showResult(target, "error", error.message))
            .finally(() => { button.disabled = false; });
    }

    function createList(options) {
        const container = options.container;
        const valueInput = document.getElementById(options.valueId);
        const items = [];
        const open = new Set();

        function load() {
            const openIds = new Set(Array.from(open, (item) => item.id));
            const stored = api.getSettings()[options.setting];
            items.splice(0, items.length, ...(Array.isArray(stored) ? stored : []).filter((item) => item && typeof item === "object"));
            items.forEach((item) => {
                if (!item.conditions || !Array.isArray(item.conditions.rules)) item.conditions = emptyConditions();
                if (options.normalize) options.normalize(item);
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
                nodes.push(element("p", "description wp-sjs-items__empty", options.emptyText));
            }
            container.replaceChildren(...nodes);
            if (focusItem) {
                container.children[items.indexOf(focusItem)]?.querySelector('[data-field="name"]')?.focus();
            }
        }

        function renderItem(item) {
            const key = String(++keyCounter);
            const node = options.template(item).content.firstElementChild.cloneNode(true);
            node.querySelectorAll("[id], [for], [aria-controls]").forEach((target) => {
                ["id", "for", "aria-controls"].forEach((attribute) => {
                    const value = target.getAttribute(attribute);
                    if (value && value.includes("__KEY__")) target.setAttribute(attribute, value.replace("__KEY__", key));
                });
            });
            node.querySelectorAll("[data-field]").forEach((input) => bindField(item, node, input));
            const fieldTags = node.querySelector("[data-field-tags]");
            if (fieldTags) {
                fieldTags.hidden = questions.length === 0;
                fieldTags.replaceChildren(__("Tags of this form:", "surveyjs") + " ", ...questions.flatMap((question, index) => (index ? [" "] : []).concat(element("code", "", "{field:" + question.name + "}"))));
            }
            if (node.querySelector("[data-conditions]")) renderConditions(item, node);
            if (node.querySelector("[data-log]")) renderLog(item, node);
            setOpen(item, node, open.has(item));
            node.addEventListener("click", (event) => onAction(event, item, node));
            if (options.renderExtra) options.renderExtra(item, node, commit);
            refresh(item, node);
            return node;
        }

        function bindField(item, node, input) {
            const field = input.dataset.field;
            if (input.type === "checkbox") {
                input.checked = Boolean(item[field]);
            } else if (input.dataset.questions) {
                fillQuestionSelect(input, item[field]);
            } else if (input.tagName !== "SELECT" || input.options.length) {
                input.value = item[field] ?? "";
            }
            input.addEventListener(input.type === "checkbox" || input.tagName === "SELECT" ? "change" : "input", () => {
                item[field] = input.type === "checkbox" ? input.checked : input.value;
                if (options.onFieldChange) options.onFieldChange(item, node, field, commit);
                refresh(item, node);
                commit();
            });
        }

        function refresh(item, node) {
            node.classList.toggle("is-inactive", !item.enabled);
            node.querySelector("[data-name]").textContent = item.name || __("(no name)", "surveyjs");
            const rules = item.conditions.rules.length;
            const summary = options.summary(item).concat(rules ? [sprintf(_n("%d condition", "%d conditions", rules, "surveyjs"), rules)] : []);
            node.querySelector("[data-summary]").textContent = summary.join(SUMMARY_SEPARATOR);
            if (options.onRefresh) options.onRefresh(item, node);
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

        function renderLog(item, node) {
            const table = node.querySelector("[data-log]");
            const entries = deliveryLog.filter((entry) => entry.type === options.logType && entry.item === item.id).slice(0, LOG_ROWS);
            node.querySelector("[data-log-empty]").hidden = entries.length > 0;
            table.hidden = entries.length === 0;
            table.tBodies[0].replaceChildren(...entries.map((entry) => {
                const row = element("tr");
                let request = entry.event === "test" ? __("Test", "surveyjs") : entry.entry ? sprintf(__("Entry #%d", "surveyjs"), entry.entry) : __("Entry (not stored)", "surveyjs");
                if (entry.attempt > 1) request += " " + sprintf(__("(retry %d)", "surveyjs"), entry.attempt - 1);
                let result = entry.ok ? (entry.note || (entry.code ? sprintf(__("Delivered (HTTP %d)", "surveyjs"), entry.code) : __("Delivered", "surveyjs"))) : entry.error;
                if (entry.retry) result += " " + __("Will retry.", "surveyjs");
                const resultCell = element("td", entry.ok ? "" : "wp-sjs-webhook-log__error", result);
                if (!entry.ok && !entry.retry && entry.entry && entry.event !== "test") {
                    const resend = element("button", "button-link wp-sjs-webhook-log__resend", __("Send again", "surveyjs"));
                    resend.type = "button";
                    resend.addEventListener("click", (event) => {
                        event.stopPropagation();
                        runAction(resend, node.querySelector("[data-test-result]"), () => post(config.resendUrl, { Type: options.logType, Item: item.id, Entry: entry.entry }))
                            .then(() => renderLog(item, node));
                    });
                    resultCell.append(" ", resend);
                }
                row.append(element("td", "", entry.time), element("td", "", request), resultCell);
                return row;
            }));
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
            } else if (options.onAction) {
                options.onAction(action, button, item, node, commit);
            }
        }

        function add(item) {
            items.push(item);
            open.add(item);
            commit();
            render(item);
        }

        function reload() {
            load();
            render();
        }

        load();
        render();
        return {
            add: add,
            render: render,
            reload: reload,
            renderLog: renderLog,
            items: () => items.slice(),
            reveal: (item) => {
                open.add(item);
                render(item);
            }
        };
    }

    function refreshQuestions(reload) {
        questions = formQuestions();
        questionListeners.forEach((listener) => listener(reload));
    }

    document.addEventListener("surveyjs:editor-tab", (event) => {
        if (event.detail?.tab === "integrations") refreshQuestions(false);
    });
    document.addEventListener("surveyjs:editor-settings-filled", () => refreshQuestions(true));

    questions = formQuestions();

    window.surveyjsEditorItems = {
        createList: createList,
        element: element,
        fillSelect: fillSelect,
        randomId: randomId,
        emptyConditions: emptyConditions,
        localized: localized,
        questionLabel: questionLabel,
        questions: () => questions,
        isEmailQuestion: QUESTION_FILTERS.email,
        post: post,
        runAction: runAction,
        showResult: showResult,
        onQuestionsChanged: (listener) => questionListeners.push(listener),
        refreshQuestions: refreshQuestions
    };
})();
