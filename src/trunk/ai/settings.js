(function () {
    const { __, sprintf } = wp.i18n;
    const settings = window.surveyjsAiSettings;
    const COPIED_LABEL_MS = 2000;
    const POLL_MS = 4000;
    const POLL_LIMIT_MS = 15 * 60 * 1000;

    const STEPS = {
        "claude-desktop": [
            __("Install Node.js (version 22 or later) from nodejs.org, if it is not on your computer yet: Claude Desktop needs it to reach your site.", "surveyjs"),
            __("In Claude Desktop open Settings > Developer and click Edit Config. A folder opens with the file claude_desktop_config.json; open that file in a text editor such as Notepad or TextEdit.", "surveyjs"),
            __("Click Copy settings below. If the file is empty or has only {}, replace everything in it with what you copied. If it already has \"mcpServers\", add the \"%s\": { … } part inside it, separated by a comma.", "surveyjs"),
            __("Save the file, quit Claude Desktop completely (on Windows: right-click its icon near the clock > Quit) and start it again.", "surveyjs"),
            __("In a new chat, open the tools menu (the slider icon under the message box): \"%s\" is listed. Ask \"List my SurveyJS forms\". On Windows, if the server does not start, change \"command\": \"npx\" to \"command\": \"cmd\" and put \"/c\", \"npx\" first in \"args\".", "surveyjs")
        ],
        cursor: [
            __("In Cursor open Settings (the gear icon at the top right) > MCP & Integrations (Tools & MCP in some versions) and click Add Custom MCP or New MCP Server. Cursor opens the file mcp.json.", "surveyjs"),
            __("Click Copy settings below. If the file is empty or has only {}, replace everything in it with what you copied. If it already has \"mcpServers\", add the \"%s\": { … } part inside it, separated by a comma.", "surveyjs"),
            __("Save the file. Back in the MCP settings, \"%s\" shows a green dot when it is connected. Then ask in the Cursor chat (Agent mode): \"List my SurveyJS forms\".", "surveyjs")
        ],
        vscode: [
            __("In VS Code press Ctrl+Shift+P (Cmd+Shift+P on a Mac), type \"MCP: Open User Configuration\" and press Enter. VS Code opens the file mcp.json.", "surveyjs"),
            __("Click Copy settings below. If the file is empty or has only {}, replace everything in it with what you copied. If it already has \"servers\", add the \"%s\": { … } part inside it, separated by a comma.", "surveyjs"),
            __("Save the file and click Start above \"%s\" in the file. Then open the Chat view, switch it to Agent mode and ask: \"List my SurveyJS forms\".", "surveyjs")
        ],
        "claude-code": [
            __("Open a terminal: Terminal on a Mac, PowerShell or Git Bash on Windows.", "surveyjs"),
            __("Click Copy settings below, paste the command into the terminal and press Enter.", "surveyjs"),
            __("Start Claude Code and type /mcp: \"%s\" is connected. Then ask: \"List my SurveyJS forms\".", "surveyjs")
        ],
        codex: [
            __("Open the file config.toml in the .codex folder of your home folder (on Windows: %USERPROFILE%\\.codex) in a text editor. If the file or the folder doesn't exist, create it.", "surveyjs"),
            __("Click Copy settings below and paste them at the end of the file. Save it.", "surveyjs"),
            __("Start a new Codex session and type /mcp: \"%s\" is listed. Then ask: \"List my SurveyJS forms\".", "surveyjs")
        ],
        other: [
            __("Find where your AI app adds MCP servers (often called Connectors, Integrations or MCP servers).", "surveyjs"),
            __("Add a server with the Streamable HTTP transport, the server URL and the Authorization header below. ChatGPT (Business and Enterprise, developer mode) needs a site that is reachable over HTTPS from the internet.", "surveyjs"),
            __("Save, then ask the app: \"List my SurveyJS forms\".", "surveyjs")
        ]
    };

    function element(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function post(action, data, nonce) {
        const body = new URLSearchParams(Object.assign({ action: action, _ajax_nonce: nonce || settings.nonce }, data));
        return fetch(settings.ajaxUrl, { method: "POST", credentials: "same-origin", body: body })
            .then((response) => response.json().catch(() => ({ success: false, data: { message: response.statusText } })))
            .then((result) => {
                if (!result.success) {
                    const error = new Error((result.data && result.data.message) || __("Something went wrong. Reload the page and try again.", "surveyjs"));
                    error.manual = Boolean(result.data && result.data.manual);
                    throw error;
                }
                return result.data;
            });
    }

    function setStatus(node, type, message) {
        node.className = "wp-sjs-ai__progress" + (type ? " is-" + type : "");
        node.textContent = message || "";
    }

    function copy(text, buttonNode) {
        const label = buttonNode.dataset.label || buttonNode.textContent;
        buttonNode.dataset.label = label;
        const done = () => {
            buttonNode.textContent = buttonNode.dataset.copied || __("Copied!", "surveyjs");
            setTimeout(() => {
                buttonNode.textContent = label;
            }, COPIED_LABEL_MS);
        };
        const fallback = () => {
            const area = element("textarea");
            area.value = text;
            document.body.append(area);
            area.select();
            document.execCommand("copy");
            area.remove();
        };
        (navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.reject(new Error("No clipboard"))).catch(fallback).then(done);
    }

    function basicAuth(username, password) {
        const bytes = new TextEncoder().encode(username + ":" + password);
        return btoa(Array.from(bytes, (byte) => String.fromCharCode(byte)).join(""));
    }

    function shellQuote(value) {
        return "'" + String(value).replace(/'/g, "'\\''") + "'";
    }

    function connectionFile(connection) {
        return [
            "# " + sprintf(__("SurveyJS Forms MCP connection for %s. Keep this file private and delete it after setup.", "surveyjs"), settings.home),
            "SURVEYJS_MCP_NAME=" + shellQuote(settings.serverName),
            "SURVEYJS_MCP_URL=" + shellQuote(settings.endpoint),
            "SURVEYJS_MCP_AUTHORIZATION=" + shellQuote("Basic " + basicAuth(connection.username, connection.password)),
            "WP_USERNAME=" + shellQuote(connection.username),
            "WP_APPLICATION_PASSWORD=" + shellQuote(connection.password),
            ""
        ].join("\n");
    }

    function configText(client, connection) {
        const name = settings.serverName;
        const url = settings.endpoint;
        const header = "Basic " + basicAuth(connection.username, connection.password);
        switch (client) {
            case "claude-code":
                return `claude mcp add --scope user --transport http ${name} "${url}" --header "Authorization: ${header}"`;
            case "codex":
                return `[mcp_servers.${name}]\nurl = "${url}"\nhttp_headers = { "Authorization" = "${header}" }`;
            case "claude-desktop":
                return JSON.stringify({
                    mcpServers: {
                        [name]: {
                            command: "npx",
                            args: ["-y", "@automattic/mcp-wordpress-remote@latest"],
                            env: {
                                WP_API_URL: url,
                                WP_API_USERNAME: connection.username,
                                WP_API_PASSWORD: connection.password,
                                OAUTH_ENABLED: "false"
                            }
                        }
                    }
                }, null, 2);
            case "cursor":
                return JSON.stringify({ mcpServers: { [name]: { url: url, headers: { Authorization: header } } } }, null, 2);
            case "vscode":
                return JSON.stringify({ servers: { [name]: { type: "http", url: url, headers: { Authorization: header } } } }, null, 2);
            default:
                return [
                    __("Server URL:", "surveyjs") + " " + url,
                    __("Transport: Streamable HTTP", "surveyjs"),
                    __("Header:", "surveyjs") + " Authorization: " + header,
                    __("Username:", "surveyjs") + " " + connection.username,
                    __("Application Password:", "surveyjs") + " " + connection.password
                ].join("\n");
        }
    }

    function initCopyButtons() {
        document.querySelectorAll("[data-copy-target]").forEach((buttonNode) => {
            buttonNode.addEventListener("click", () => copy(document.getElementById(buttonNode.dataset.copyTarget).textContent.trim(), buttonNode));
        });
    }

    function initInstall() {
        document.querySelectorAll("[data-install]").forEach((wrapper) => {
            const buttonNode = wrapper.querySelector("[data-install-button]");
            if (!buttonNode) return;
            const status = wrapper.querySelector("[data-install-status]");
            const manual = wrapper.querySelector("[data-install-manual]");
            buttonNode.addEventListener("click", () => {
                document.querySelectorAll("[data-install-button]").forEach((other) => { other.disabled = true; });
                setStatus(status, "busy", __("Installing and activating…", "surveyjs"));
                post("surveyjs_ai_install_plugin", { slug: wrapper.dataset.install })
                    .then(() => {
                        setStatus(status, "success", __("Active. Reloading…", "surveyjs"));
                        window.location.reload();
                    })
                    .catch((error) => {
                        document.querySelectorAll("[data-install-button]").forEach((other) => { other.disabled = false; });
                        setStatus(status, "error", error.message);
                        if (error.manual) manual.hidden = false;
                    });
            });
        });
    }

    const table = document.getElementById("wp-sjs-ai-connections");
    let connections = [];

    function activityCell(connection) {
        const cell = element("td", "wp-sjs-ai__activity");
        if (!connection.activity.length) {
            cell.append(element("span", "wp-sjs-ai__muted", __("No activity yet", "surveyjs")));
            return cell;
        }
        const last = connection.activity[0];
        const summary = element("span", last.error ? "wp-sjs-ai__activity-last is-error" : "wp-sjs-ai__activity-last");
        summary.title = last.date;
        summary.append(element("strong", "", last.when), " · ", element("code", "", last.action));
        if (last.error) summary.append(" ", element("span", "wp-sjs-ai__muted", __("(refused or failed)", "surveyjs")));
        cell.append(summary);
        if (connection.activity.length > 1) {
            const details = element("details", "wp-sjs-ai__history");
            details.append(element("summary", "", __("Recent activity", "surveyjs")));
            const list = element("ul");
            connection.activity.forEach((event) => {
                const item = element("li");
                item.append(element("span", "", event.date + " · "), element("code", "", event.action));
                if (event.error) item.append(" ", element("span", "wp-sjs-ai__muted", __("(refused or failed)", "surveyjs")));
                list.append(item);
            });
            details.append(list);
            cell.append(details);
        }
        return cell;
    }

    function renderConnections() {
        if (!table) return;
        const body = table.querySelector("tbody");
        body.querySelectorAll("tr[data-uuid]").forEach((row) => row.remove());
        connections.forEach((connection) => {
            const row = element("tr");
            row.dataset.uuid = connection.uuid;
            row.append(element("td", "", connection.name), element("td", "", connection.created), activityCell(connection));
            const actions = element("td", "wp-sjs-ai__actions");
            const revoke = element("button", "button delete", __("Revoke", "surveyjs"));
            revoke.type = "button";
            revoke.dataset.revoke = connection.uuid;
            actions.append(revoke);
            row.append(actions);
            body.append(row);
        });
        table.querySelector(".wp-sjs-ai__empty").hidden = connections.length > 0;
        document.getElementById("wp-sjs-ai-connections-wrap").hidden = connections.length === 0;
    }

    function initConnections() {
        if (!table) return;
        connections = JSON.parse(document.getElementById("wp-sjs-ai-connections-data").textContent);
        renderConnections();
        table.addEventListener("click", (event) => {
            const buttonNode = event.target.closest("[data-revoke]");
            if (!buttonNode) return;
            const name = buttonNode.closest("tr").firstElementChild.textContent;
            if (!window.confirm(__("Revoke this connection? The AI app that uses it will stop working right away.", "surveyjs") + "\n\n" + name)) return;
            buttonNode.disabled = true;
            post("surveyjs_ai_revoke_connection", { uuid: buttonNode.dataset.revoke })
                .then(() => {
                    connections = connections.filter((connection) => connection.uuid !== buttonNode.dataset.revoke);
                    renderConnections();
                })
                .catch((error) => {
                    buttonNode.disabled = false;
                    window.alert(error.message);
                });
        });
    }

    function watchConnection(uuid, clientLabel, notice) {
        const started = Date.now();
        const timer = setInterval(() => {
            if (Date.now() - started > POLL_LIMIT_MS || document.hidden) {
                if (Date.now() - started > POLL_LIMIT_MS) clearInterval(timer);
                return;
            }
            post("surveyjs_ai_connections", {}).then((rows) => {
                connections = rows;
                renderConnections();
                const connection = rows.find((row) => row.uuid === uuid);
                if (!connection) {
                    clearInterval(timer);
                    return;
                }
                const ok = connection.activity.find((event) => !event.error);
                if (ok) {
                    clearInterval(timer);
                    notice.querySelector("p").textContent = sprintf(__("%1$s is connected: the site received its first request (%2$s, %3$s).", "surveyjs"), clientLabel, ok.action, ok.when);
                    notice.hidden = false;
                }
            }).catch(() => {});
        }, POLL_MS);
    }

    function initCreate() {
        const consent = document.getElementById("wp-sjs-ai-consent");
        if (!consent) return;
        const paths = document.getElementById("wp-sjs-ai-paths");
        const downloadButton = document.getElementById("wp-sjs-ai-download");
        const downloadStatus = document.getElementById("wp-sjs-ai-download-status");
        const promptArea = document.getElementById("wp-sjs-ai-prompt");
        const autoConnected = document.getElementById("wp-sjs-ai-auto-connected");
        const clientSelect = document.getElementById("wp-sjs-ai-client");
        const createButton = document.getElementById("wp-sjs-ai-create");
        const createStatus = document.getElementById("wp-sjs-ai-create-status");
        const result = document.getElementById("wp-sjs-ai-result");
        const steps = document.getElementById("wp-sjs-ai-manual-steps");
        const config = document.getElementById("wp-sjs-ai-config");
        const manualConnected = document.getElementById("wp-sjs-ai-manual-connected");
        let assistant = null;
        let manual = null;

        function create(client) {
            return post("surveyjs_ai_create_connection", { client: client }).then((data) => {
                connections.push(data.connection);
                renderConnections();
                return data;
            });
        }

        function saveFile(connection) {
            const url = URL.createObjectURL(new Blob([connectionFile(connection)], { type: "text/plain" }));
            const link = element("a");
            link.href = url;
            link.download = settings.filePrefix + ".env";
            document.body.append(link);
            link.click();
            link.remove();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        }

        function showManual() {
            const client = clientSelect.value;
            steps.replaceChildren(...(STEPS[client] || STEPS.other).map((text) => element("li", "", text.replace(/%s/g, settings.serverName))));
            config.value = configText(client, manual);
            config.rows = Math.min(16, config.value.split("\n").length + 1);
        }

        consent.addEventListener("change", () => {
            paths.hidden = !consent.checked;
        });

        const wayTabs = Array.from(paths.querySelectorAll("[role='tab']"));
        function selectWay(tab) {
            wayTabs.forEach((item) => {
                const active = item === tab;
                item.classList.toggle("nav-tab-active", active);
                item.setAttribute("aria-selected", String(active));
                item.tabIndex = active ? 0 : -1;
                document.getElementById(item.getAttribute("aria-controls")).hidden = !active;
            });
        }
        wayTabs.forEach((tab, index) => {
            tab.addEventListener("click", () => selectWay(tab));
            tab.addEventListener("keydown", (event) => {
                if (event.key !== "ArrowRight" && event.key !== "ArrowLeft") return;
                const next = wayTabs[(index + (event.key === "ArrowRight" ? 1 : wayTabs.length - 1)) % wayTabs.length];
                selectWay(next);
                next.focus();
            });
        });

        downloadButton.addEventListener("click", () => {
            if (assistant) {
                saveFile(assistant);
                return;
            }
            downloadButton.disabled = true;
            setStatus(downloadStatus, "busy", __("Creating the connection…", "surveyjs"));
            create("assistant")
                .then((data) => {
                    assistant = data;
                    saveFile(data);
                    setStatus(downloadStatus, "success", sprintf(__("Connection \"%1$s\" created, file %2$s downloaded.", "surveyjs"), data.connection.name, settings.filePrefix + ".env"));
                    watchConnection(data.connection.uuid, __("Your assistant", "surveyjs"), autoConnected);
                })
                .catch((error) => setStatus(downloadStatus, "error", error.message))
                .finally(() => {
                    downloadButton.disabled = false;
                });
        });
        document.getElementById("wp-sjs-ai-copy-prompt").addEventListener("click", (event) => copy(promptArea.value, event.currentTarget));
        document.getElementById("wp-sjs-ai-copy").addEventListener("click", (event) => copy(config.value, event.currentTarget));

        clientSelect.addEventListener("change", () => {
            if (manual) showManual();
        });
        createButton.addEventListener("click", () => {
            createButton.disabled = true;
            setStatus(createStatus, "busy", __("Creating the connection…", "surveyjs"));
            create(clientSelect.value)
                .then((data) => {
                    manual = data;
                    setStatus(createStatus, "success", sprintf(__("Connection \"%s\" created.", "surveyjs"), data.connection.name));
                    manualConnected.hidden = true;
                    showManual();
                    result.hidden = false;
                    result.focus();
                    watchConnection(data.connection.uuid, clientSelect.options[clientSelect.selectedIndex].text, manualConnected);
                })
                .catch((error) => setStatus(createStatus, "error", error.message))
                .finally(() => {
                    createButton.disabled = false;
                });
        });
    }

    function initModel() {
        const select = document.getElementById("wp-sjs-ai-model");
        if (!select) return;
        const status = document.getElementById("wp-sjs-ai-model-status");
        select.addEventListener("change", () => {
            setStatus(status, "busy", __("Saving…", "surveyjs"));
            post("surveyjs_ai_chat_model", { model: select.value }, settings.chatNonce)
                .then(() => setStatus(status, "success", __("Saved.", "surveyjs")))
                .catch((error) => setStatus(status, "error", error.message));
        });
    }

    initCopyButtons();
    initInstall();
    initConnections();
    initCreate();
    initModel();
})();
