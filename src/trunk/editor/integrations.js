(function () {
    const api = window.surveyjsEditor;
    const items = window.surveyjsEditorItems;
    const container = document.getElementById("wp-sjs-integrations");
    if (!api || !items || !container || !api.config.integrations) return;

    const { __, sprintf } = wp.i18n;
    const config = api.config.integrations;
    const services = Object.fromEntries(config.services.map((service) => [service.id, service]));
    const handlers = {};
    const targetsCache = new Map();
    const CUSTOM = "__custom__";
    const CONSENT_NAME = "newsletter_consent";
    const consentNotices = new WeakMap();

    function connectionsFor(serviceId) {
        return config.connections.filter((connection) => connection.service === serviceId);
    }

    function connectionLabel(connection) {
        return connection.status === "error" ? sprintf(__("%s (needs attention)", "surveyjs"), connection.label) : connection.label;
    }

    function showSingleChoice(select, entries) {
        const text = select.parentElement.querySelector("[data-single-choice]");
        const single = entries.length === 1 && entries[0].value === select.value ? entries[0] : null;
        select.hidden = Boolean(single);
        if (!text) return;
        text.hidden = !single;
        text.textContent = single ? single.text : "";
    }

    function fillConnections(item, node, commit) {
        const select = node.querySelector("[data-connections]");
        if (!select) return;
        const list = connectionsFor(item.service);
        if (!item.connection && list.length === 1) {
            item.connection = list[0].id;
            commit();
        }
        const entries = list.map((connection) => ({ value: connection.id, text: connectionLabel(connection) }));
        items.fillSelect(select, entries, item.connection, list.length ? __("— Choose a connection —", "surveyjs") : __("— Not connected —", "surveyjs"), __("Disconnected (%s)", "surveyjs"));
        showSingleChoice(select, entries);
        node.querySelector("[data-no-connection]").hidden = list.length > 0;
    }

    function loadTargets(item, target, args, refresh) {
        const key = [item.connection, target, JSON.stringify(args)].join("|");
        if (!refresh && targetsCache.has(key)) return targetsCache.get(key);
        const request = fetch(config.targetsUrl, {
            method: "POST",
            body: new URLSearchParams({ Id: api.config.id, _wpnonce: api.config.saveNonce, Connection: item.connection, Target: target, Args: JSON.stringify(args), Refresh: refresh ? "1" : "" })
        }).then((response) => response.json()).then((response) => {
            if (!response.success) throw new Error(response.data?.message || __("The list could not be loaded.", "surveyjs"));
            return response.data.items;
        });
        targetsCache.set(key, request);
        request.catch(() => targetsCache.delete(key));
        return request;
    }

    const contacts = (function () {
        function simplified(value) {
            return String(value || "").toLowerCase().replace(/[^a-z0-9]/g, "");
        }

        function questionMatches(question, field) {
            const names = [field.tag, field.name].map(simplified);
            return [question.name, items.localized(question.title)].map(simplified).some((candidate) => candidate && names.includes(candidate));
        }

        function mappedValue(item, tag) {
            return item.fields.find((pair) => pair.field === tag)?.value || "";
        }

        function setMapped(item, tag, value) {
            item.fields = item.fields.filter((pair) => pair.field !== tag);
            if (value) item.fields.push({ field: tag, value: value });
        }

        function fieldRow(item, field, commit) {
            const row = items.element("tr");
            const label = items.element("th", "", field.name + " (" + field.tag + ")" + (field.required ? " *" : ""));
            label.scope = "row";
            const cell = items.element("td");
            if (!field.supported) {
                cell.append(items.element("span", "description", __("This field type is not supported yet.", "surveyjs")));
                row.append(label, cell);
                return row;
            }
            const select = items.element("select");
            select.setAttribute("aria-label", sprintf(__("Value of %s", "surveyjs"), field.name));
            const input = items.element("input", "regular-text");
            input.type = "text";
            input.setAttribute("aria-label", sprintf(__("Custom value of %s", "surveyjs"), field.name));
            input.placeholder = "{field:name}";
            const value = mappedValue(item, field.tag);
            const match = value.match(/^\{field:([^}]+)\}$/);
            items.fillSelect(select, items.questions().map((question) => ({ value: question.name, text: items.questionLabel(question) })), match ? match[1] : "", __("— Not sent —", "surveyjs"));
            select.append(new Option(__("Custom value…", "surveyjs"), CUSTOM));
            if (value && !match) select.value = CUSTOM;
            input.value = value;
            input.hidden = select.value !== CUSTOM;
            select.addEventListener("change", () => {
                input.hidden = select.value !== CUSTOM;
                if (select.value === CUSTOM) {
                    input.focus();
                    return;
                }
                setMapped(item, field.tag, select.value ? "{field:" + select.value + "}" : "");
                input.value = mappedValue(item, field.tag);
                commit();
            });
            input.addEventListener("input", () => {
                setMapped(item, field.tag, input.value);
                commit();
            });
            cell.append(select, " ", input);
            row.append(label, cell);
            return row;
        }

        function renderFields(item, node, commit, fields, autoMap) {
            const table = node.querySelector("[data-field-map]");
            const empty = node.querySelector("[data-field-map-empty]");
            if (fields && autoMap) {
                fields.filter((field) => field.supported && !mappedValue(item, field.tag)).forEach((field) => {
                    const question = items.questions().find((candidate) => questionMatches(candidate, field));
                    if (question) setMapped(item, field.tag, "{field:" + question.name + "}");
                });
                commit();
            }
            const rows = (fields || []).map((field) => fieldRow(item, field, commit));
            table.hidden = rows.length === 0;
            empty.hidden = rows.length > 0;
            empty.textContent = fields ? __("This list has no fields besides the email.", "surveyjs") : __("Choose a list to map its fields.", "surveyjs");
            table.tBodies[0].replaceChildren(...rows);
        }

        function showError(node, error) {
            items.showResult(node.querySelector("[data-targets-status]"), "error", error.message);
        }

        function loadFields(item, node, commit, refresh, autoMap) {
            if (!item.list) {
                renderFields(item, node, commit, null, false);
                return;
            }
            loadTargets(item, "fields", { list: item.list }, refresh)
                .then((fields) => renderFields(item, node, commit, fields, autoMap))
                .catch((error) => showError(node, error));
        }

        function render(item, node, commit, refresh) {
            if (!Array.isArray(item.fields)) item.fields = [];
            const select = node.querySelector("[data-target-list]");
            node.querySelector("[data-targets-status]").replaceChildren();
            if (!item.connection) {
                items.fillSelect(select, [], item.list, __("— Choose a connection first —", "surveyjs"));
                showSingleChoice(select, []);
                select.disabled = true;
                loadFields(item, node, commit, false, false);
                return;
            }
            select.disabled = false;
            items.fillSelect(select, [], item.list, __("Loading…", "surveyjs"), "%s");
            showSingleChoice(select, []);
            loadTargets(item, "lists", {}, refresh).then((lists) => {
                const chosen = !item.list && lists.length === 1;
                if (chosen) {
                    item.list = lists[0].value;
                    commit();
                }
                items.fillSelect(select, lists, item.list, __("— Choose —", "surveyjs"), __("%s (not found)", "surveyjs"));
                showSingleChoice(select, lists);
                loadFields(item, node, commit, refresh, chosen);
            }).catch((error) => showError(node, error));
        }

        function hasConsent(item) {
            return Boolean(item.signupForm) || item.conditions.rules.some((rule) => rule.question);
        }

        function consentError(item) {
            if (hasConsent(item)) return "";
            return sprintf(
                __('The integration "%1$s" would add every respondent to %2$s without their consent. Add a consent question and a condition on it (the button under Conditions does both), or check "%3$s".', "surveyjs"),
                item.name,
                services[item.service].label,
                __("This form is a newsletter signup (submitting it means consent)", "surveyjs")
            );
        }

        function showConsent(item, node) {
            const status = node.querySelector("[data-consent-status]");
            if (!status) return;
            const added = consentNotices.get(item);
            consentNotices.delete(item);
            const error = consentError(item);
            if (added) items.showResult(status, "success", added);
            else if (error) items.showResult(status, "warning", error);
            else status.replaceChildren();
        }

        function questionNames(node, names = new Set()) {
            if (Array.isArray(node)) {
                node.forEach((child) => questionNames(child, names));
            } else if (node && typeof node === "object") {
                if (typeof node.name === "string") names.add(node.name);
                Object.values(node).forEach((child) => questionNames(child, names));
            }
            return names;
        }

        function addConsentQuestion(button, item, commit) {
            const json = api.getSurvey();
            if (!json) {
                items.showResult(button.closest("td").querySelector("[data-consent-status]"), "error", __("Fix the errors in the form JSON first.", "surveyjs"));
                return;
            }
            const existing = items.questions().find((question) => question.type === "boolean" && question.valueTrue === undefined && items.localized(question.title) === button.dataset.title);
            let name = existing ? existing.name : "";
            if (!existing) {
                const names = questionNames(json);
                name = CONSENT_NAME;
                for (let index = 2; names.has(name); index++) name = CONSENT_NAME + index;
                const page = Array.isArray(json.pages) && json.pages.length ? json.pages[json.pages.length - 1] : json;
                if (!Array.isArray(page.elements)) page.elements = [];
                const last = page.elements[page.elements.length - 1];
                const position = last && last.name === "gdpr_agreement" ? page.elements.length - 1 : page.elements.length;
                page.elements.splice(position, 0, { type: "boolean", name: name, title: button.dataset.title, displayMode: "checkbox" });
                api.setSurvey(json);
            }
            if (!item.conditions.rules.some((rule) => rule.question === name && rule.operator === "equal" && rule.value === "true")) {
                item.conditions.rules = item.conditions.rules.filter((rule) => rule.question);
                item.conditions.rules.push({ question: name, operator: "equal", value: "true" });
            }
            commit();
            consentNotices.set(item, existing
                ? sprintf(__('Entries are now sent only when "%s" is checked. Press Save to keep the change.', "surveyjs"), button.dataset.title)
                : sprintf(__('"%s" was added at the end of the form, and entries are sent only when it is checked. Press Save to keep the changes.', "surveyjs"), button.dataset.title));
            items.refreshQuestions(false);
        }

        return {
            renderExtra: (item, node, commit) => render(item, node, commit, false),
            onRefresh: showConsent,
            error: consentError,
            onFieldChange: (item, node, field, commit) => {
                if (field === "connection") {
                    item.list = "";
                    item.fields = [];
                    render(item, node, commit, false);
                } else if (field === "list") {
                    item.fields = [];
                    node.querySelector("[data-targets-status]").replaceChildren();
                    loadFields(item, node, commit, false, true);
                }
            },
            onAction: (action, button, item, node, commit) => {
                if (action === "refresh-targets") render(item, node, commit, true);
                if (action === "add-consent-question") addConsentQuestion(button, item, commit);
            }
        };
    })();

    const kinds = { contacts: contacts };

    function handlerFor(item) {
        return handlers[item.service] || kinds[services[item.service]?.kind] || {};
    }

    function summary(item) {
        const service = services[item.service];
        if (!service) return [sprintf(__("%s (not available)", "surveyjs"), item.service)];
        const connection = config.connections.find((candidate) => candidate.id === item.connection);
        return [service.label, connection ? connectionLabel(connection) : __("no connection", "surveyjs")];
    }

    const list = items.createList({
        container: container,
        valueId: "wp-sjs-integrations-value",
        template: (item) => document.getElementById("wp-sjs-integration-template-" + item.service) || document.getElementById("wp-sjs-integration-template-missing"),
        setting: "integrations",
        logType: "integration",
        emptyText: __("No integrations yet.", "surveyjs"),
        summary: summary,
        renderExtra: (item, node, commit) => {
            fillConnections(item, node, commit);
            handlerFor(item).renderExtra?.(item, node, commit);
        },
        onRefresh: (item, node) => handlerFor(item).onRefresh?.(item, node),
        onFieldChange: (item, node, field, commit) => handlerFor(item).onFieldChange?.(item, node, field, commit),
        onAction: (action, button, item, node, commit) => {
            if (action === "test-integration") {
                items.runAction(button, node.querySelector("[data-test-result]"), () => items.post(config.testUrl, { Integration: item.id }))
                    .then(() => list.renderLog(item, node));
                return;
            }
            handlerFor(item).onAction?.(action, button, item, node, commit);
        }
    });

    document.getElementById("wp-sjs-integration-add")?.addEventListener("click", () => {
        const service = services[document.getElementById("wp-sjs-integration-service").value];
        if (!service) return;
        const connections = connectionsFor(service.id);
        const item = Object.assign({
            id: items.randomId("i"),
            name: service.label,
            enabled: true,
            service: service.id,
            connection: connections.length === 1 ? connections[0].id : "",
            conditions: items.emptyConditions()
        }, JSON.parse(JSON.stringify(service.defaults)));
        if ("emailQuestion" in item && !item.emailQuestion) {
            const emailQuestion = items.questions().find(items.isEmailQuestion);
            item.emailQuestion = emailQuestion ? emailQuestion.name : "";
        }
        list.add(item);
    });

    items.onQuestionsChanged((reload) => (reload ? list.reload() : list.render()));

    api.addSaveCheck?.(() => {
        for (const item of list.items()) {
            const error = services[item.service] ? handlerFor(item).error?.(item) : "";
            if (error) {
                api.switchTab("integrations");
                list.reveal(item);
                return error;
            }
        }
        return "";
    });

    window.surveyjsEditorItems.integrations = {
        register: (serviceId, handler) => {
            handlers[serviceId] = handler;
            list.render();
        }
    };
})();
