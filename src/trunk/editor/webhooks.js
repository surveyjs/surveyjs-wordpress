(function () {
    const api = window.surveyjsEditor;
    const items = window.surveyjsEditorItems;
    const container = document.getElementById("wp-sjs-webhooks");
    if (!api || !items || !container) return;

    const { __ } = wp.i18n;
    const headerTemplate = document.getElementById("wp-sjs-header-template");

    function webhookHost(url) {
        try {
            return new URL(url).host;
        } catch (error) {
            return "";
        }
    }

    function renderHeaders(item, node, commit) {
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
                renderHeaders(item, node, commit);
                commit();
            });
            return row;
        }));
    }

    const list = items.createList({
        container: container,
        valueId: "wp-sjs-webhooks-value",
        template: () => document.getElementById("wp-sjs-webhook-template"),
        setting: "webhooks",
        logType: "webhook",
        emptyText: __("No webhooks yet.", "surveyjs"),
        summary: (item) => [webhookHost(item.url) || __("No URL yet", "surveyjs")],
        normalize: (item) => {
            if (!Array.isArray(item.headers)) item.headers = [];
        },
        renderExtra: renderHeaders,
        onAction: (action, button, item, node, commit) => {
            if (action === "test-webhook") {
                items.runAction(button, node.querySelector("[data-test-result]"), () => items.post(api.config.testWebhookUrl, { Webhook: item.id }))
                    .then(() => list.renderLog(item, node));
            } else if (action === "add-header") {
                item.headers.push({ name: "", value: "" });
                renderHeaders(item, node, commit);
                commit();
                node.querySelector("[data-headers]").lastElementChild?.querySelector('[data-header="name"]').focus();
            }
        }
    });

    document.getElementById("wp-sjs-webhook-add").addEventListener("click", () => {
        list.add({
            id: items.randomId("w"),
            name: __("New webhook", "surveyjs"),
            enabled: true,
            url: "",
            headers: [],
            conditions: items.emptyConditions()
        });
    });

    items.onQuestionsChanged((reload) => (reload ? list.reload() : list.render()));
})();
