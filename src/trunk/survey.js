(function () {
    const CAPTCHA_LOAD_TIMEOUT_MS = 20000;

    function captchaApi(name) {
        const started = Date.now();
        return new Promise((resolve, reject) => {
            (function check() {
                if (window[name]) {
                    resolve(window[name]);
                } else if (Date.now() - started > CAPTCHA_LOAD_TIMEOUT_MS) {
                    reject();
                } else {
                    setTimeout(check, 100);
                }
            })();
        });
    }

    function createCaptcha(captcha, container) {
        if (captcha.type === "recaptcha_v3") {
            return {
                show: () => {},
                reset: () => {},
                token: () => captchaApi("grecaptcha").then((api) => new Promise((resolve, reject) => {
                    api.ready(() => api.execute(captcha.siteKey, { action: captcha.action }).then(resolve, reject));
                }))
            };
        }
        const element = document.createElement("div");
        element.className = "wp-sjs-captcha";
        element.hidden = true;
        container.after(element);
        const apiName = { turnstile: "turnstile", hcaptcha: "hcaptcha" }[captcha.type] || "grecaptcha";
        let widget = null;
        // Rendered on first show: Turnstile may fail its challenge in a hidden element, and a token solved on an earlier page could expire.
        const renderWidget = () => captchaApi(apiName).then((api) => new Promise((resolve) => {
            const render = () => resolve({ api: api, id: api.render(element, { sitekey: captcha.siteKey }) });
            if (apiName === "grecaptcha") {
                api.ready(render);
            } else {
                render();
            }
        }));
        return {
            show: (visible) => {
                element.hidden = !visible;
                if (visible && !widget) widget = renderWidget();
            },
            reset: () => widget && widget.then((rendered) => rendered.api.reset(rendered.id), () => {}),
            token: () => widget ? widget.then((rendered) => rendered.api.getResponse(rendered.id) || "") : Promise.resolve("")
        };
    }

    function createPartialEntry(config, honeypot) {
        const options = config.partial;
        const listeners = [];
        let queue = Promise.resolve();
        let ref = null;

        function set(value) {
            ref = value && value.id && value.token ? { id: value.id, token: value.token } : null;
            try {
                if (ref) {
                    sessionStorage.setItem(options.storageKey, JSON.stringify(ref));
                } else {
                    sessionStorage.removeItem(options.storageKey);
                }
            } catch (error) {
                console.error("Error: ", error);
            }
            if (ref) listeners.forEach((listener) => listener(ref));
        }

        function fields() {
            return ref ? { PartialId: ref.id, PartialToken: ref.token } : {};
        }

        try {
            const stored = JSON.parse(sessionStorage.getItem(options.storageKey));
            ref = stored && stored.id && stored.token ? stored : null;
        } catch (error) {
            ref = null;
        }

        return {
            get: () => ref,
            set: set,
            fields: fields,
            onChange: (listener) => listeners.push(listener),
            idle: () => queue,
            clear: () => set(null),
            send(data, page) {
                queue = queue
                    .then(() => fetch(options.url, {
                        method: "POST",
                        body: new URLSearchParams(Object.assign({ SurveyId: config.id, Json: JSON.stringify(data), Page: page, Website: honeypot ? honeypot.value : "", _wpnonce: config.saveResultNonce }, fields()))
                    }))
                    .then((response) => response.ok ? response.json() : null)
                    .then((result) => {
                        if (result && result.data && result.data.token) set(result.data);
                    })
                    .catch((error) => console.error("Error: ", error));
            }
        };
    }

    function dispatch(name, detail) {
        document.dispatchEvent(new CustomEvent("surveyjs:" + name, { detail: detail }));
    }

    function extraSubmitFields(detail) {
        const fields = {};
        const pending = [];
        dispatch("before-submit", Object.assign({}, detail, {
            fields: fields,
            waitUntil: (promise) => pending.push(Promise.resolve(promise).then(
                (extra) => extra && typeof extra === "object" && Object.assign(fields, extra),
                (error) => console.error("Error: ", error)
            ))
        }));
        return Promise.all(pending).then(() => fields);
    }

    function requestBody(fields) {
        const body = Object.values(fields).some((value) => value instanceof Blob) ? new FormData() : new URLSearchParams();
        Object.entries(fields).forEach(([key, value]) => body.append(key, value));
        return body;
    }

    // A view is counted once per page load, when the form is on screen in a visible tab: forms in closed popups,
    // background tabs and prerendered pages are not seen yet. No cookies or storage, so nothing identifies the visitor.
    function countView(config, element) {
        if (!config.viewUrl || navigator.webdriver || !window.IntersectionObserver) {
            return;
        }
        const observer = new IntersectionObserver((entries) => {
            if (document.visibilityState !== "visible" || !entries.some((entry) => entry.isIntersecting)) {
                return;
            }
            observer.disconnect();
            document.removeEventListener("visibilitychange", recheck);
            const body = new URLSearchParams({ id: config.id });
            if (!(navigator.sendBeacon && navigator.sendBeacon(config.viewUrl, body))) {
                fetch(config.viewUrl, { method: "POST", body: body, keepalive: true }).catch(() => {});
            }
        });
        function recheck() {
            observer.unobserve(element);
            observer.observe(element);
        }
        document.addEventListener("visibilitychange", recheck);
        observer.observe(element);
    }

    function showRestriction(container, html) {
        const message = document.createElement("div");
        message.className = "wp-sjs-restricted__message";
        message.setAttribute("role", "status");
        message.innerHTML = html;
        container.parentElement.classList.add("wp-sjs-restricted");
        container.parentElement.querySelectorAll(".wp-sjs-survey, .wp-sjs-captcha").forEach((element) => { element.hidden = true; });
        container.after(message);
    }

    function restrictionHtml(response) {
        return response.status === 403
            ? response.clone().json().then((result) => result && result.data && result.data.restricted ? result.data.html : null, () => null)
            : Promise.resolve(null);
    }

    function withTransparentBackground(theme) {
        const cssVariables = Object.assign({}, theme && theme.cssVariables, {
            "--sjs2-color-utility-body": "transparent",
            "--sjs2-color-utility-surface-survey": "transparent"
        });
        // Transparent shows the page behind the form, so a background image of the theme is not drawn either.
        const transparent = Object.assign({}, theme, { cssVariables: cssVariables });
        delete transparent.backgroundImage;
        return transparent;
    }

    function initSurvey(configElement) {
        if (configElement.dataset.initialized) {
            return;
        }
        configElement.dataset.initialized = "true";
        const config = JSON.parse(configElement.textContent);
        const honeypot = configElement.parentElement.querySelector(".wp-sjs-hp input");
        const survey = new Survey.Model(config.json);
        if (config.locale && !config.json.locale) {
            survey.locale = config.locale;
        }
        survey.onTextMarkdown.add(SurveyJSMarkdown.handler(config.markdown));
        if (config.variables) {
            survey.setVariable(config.variables.query, Object.fromEntries(new URLSearchParams(window.location.search)));
            Object.entries(config.variables.values).forEach(([name, value]) => survey.setVariable(name, value));
        }
        const container = configElement.parentElement.querySelector(".wp-sjs-survey");
        if ((config.json.widthMode || "auto") === "auto" && !config.json.width && container.closest(".wp-block-sjs-gutenberg-block:is(.alignwide, .alignfull)")) {
            survey.widthMode = "responsive";
        }
        if (config.themeMode === "adaptive" && window.SurveyWordPress) {
            container.classList.add("wp-sjs-adaptive");
            SurveyWordPress.applyWordPressStyles(container);
        } else if (config.transparentBackground) {
            survey.applyTheme(withTransparentBackground(config.theme));
        } else if (config.theme) {
            survey.applyTheme(config.theme);
        }
        window["survey" + config.id] = survey;

        const partial = config.partial ? createPartialEntry(config, honeypot) : null;
        if (partial) {
            survey.partialSendEnabled = true;
            survey.onPartialSend.add((sender) => partial.send(sender.data, sender.currentPageNo + 2));
        }

        const shownAt = Date.now();
        function minimumTimePassed() {
            const left = config.submitTime ? shownAt + config.submitTime.wait - Date.now() : 0;
            return left > 0 ? new Promise((resolve) => setTimeout(resolve, left)) : Promise.resolve();
        }

        function saveResult(data, fields) {
            const detail = { id: config.id, survey: survey, config: config, container: container, data: data };
            const submitTime = config.submitTime ? { SubmitToken: config.submitTime.token } : {};
            return (partial ? partial.idle() : Promise.resolve())
                .then(() => extraSubmitFields(detail))
                .then((extra) => minimumTimePassed().then(() => extra))
                .then((extra) => fetch(config.saveResultUrl, {
                    method: "POST",
                    body: requestBody(Object.assign(extra, { SurveyId: config.id, Json: JSON.stringify(data), Website: honeypot ? honeypot.value : "", _wpnonce: config.saveResultNonce }, submitTime, partial ? partial.fields() : {}, fields))
                }))
                .then((response) => {
                    if (response.ok) {
                        if (partial) partial.clear();
                        response.clone().json()
                            .then((result) => result && result.success ? result.data : null, () => null)
                            .then((result) => dispatch("submitted", Object.assign({ response: result }, detail)));
                    }
                    return response;
                });
        }

        const captcha = config.captcha ? createCaptcha(config.captcha, container) : null;
        if (captcha) {
            const messages = config.captcha.messages;
            let saved = false;
            const updateCaptcha = () => captcha.show(survey.isCompleteButtonVisible);
            survey.registerFunctionOnPropertyValueChanged("isCompleteButtonVisible", updateCaptcha);
            survey.onCurrentPageChanged.add(updateCaptcha);
            survey.onComplete.add(updateCaptcha);
            // Saving happens before completion so that a rejected CAPTCHA leaves the respondent on the form with the answers.
            survey.onCompleting.add((sender, options) => {
                if (saved) return;
                return captcha.token()
                    .then((token) => {
                        if (!token) return Promise.reject(messages.required);
                        sender.notify(sender.getLocalizationString("savingData"), "saving");
                        return saveResult(sender.data, { CaptchaToken: token });
                    })
                    .then((response) => {
                        if (response.ok) {
                            saved = true;
                            return;
                        }
                        return restrictionHtml(response).then((html) => html ? Promise.reject({ restriction: html }) : response.json().then(
                            (result) => Promise.reject((result && result.data && result.data.message) || messages.failed),
                            () => Promise.reject(messages.failed)
                        ));
                    })
                    .catch((error) => {
                        options.allow = false;
                        if (error && error.restriction) {
                            showRestriction(container, error.restriction);
                            return;
                        }
                        options.message = typeof error === "string" ? error : messages.failed;
                        captcha.reset();
                    });
            });
            updateCaptcha();
        }

        survey.onComplete.add((sender, options) => {
            if (captcha) {
                options.showSaveSuccess();
                return;
            }
            options.showSaveInProgress();
            saveResult(sender.data, {})
                .then((response) => response.ok ? options.showSaveSuccess() : restrictionHtml(response).then((html) => html ? showRestriction(container, html) : response.text().then((text) => options.showSaveError(text))))
                .catch(() => options.showSaveError());
        });

        // Uploaded files are not public: the preview shows them through the signed link returned by the upload,
        // and only the respondent who uploaded a file has the token that deletes it.
        const uploadedFiles = Object.assign({}, config.draft && config.draft.files);
        survey.onDownloadFile.add((_, options) => {
            const uploaded = uploadedFiles[options.content];
            options.callback("success", uploaded ? uploaded.preview : options.content);
        });

        survey.onUploadFiles.add((_, options) => {
            const formData = new FormData();
            options.files.forEach((file) => {
                formData.append(file.name, file);
            });
            formData.append("_wpnonce", config.uploadFilesNonce);
            formData.append("SurveyId", config.id);

            fetch(config.uploadFilesUrl, {
                method: "POST",
                body: formData
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.error) {
                        throw Object.assign(new Error(data.error), { userMessage: data.message });
                    }
                    options.callback(
                        options.files.map((file) => {
                            const uploaded = data[file.name];
                            uploadedFiles[uploaded.url] = uploaded;
                            return {
                                file: file,
                                content: uploaded.url
                            };
                        })
                    );
                })
                .catch((error) => {
                    console.error("Error: ", error);
                    options.callback([], [ error.userMessage || 'An error occurred during file upload.' ]);
                });
        });

        function deleteFile(content) {
            const uploaded = uploadedFiles[content];
            if (!uploaded || !uploaded.deleteToken) {
                return;
            }
            delete uploadedFiles[content];
            fetch(config.deleteFileUrl, {
                method: "POST",
                body: new URLSearchParams({ _wpnonce: config.deleteFileNonce, file: uploaded.file, expires: uploaded.expires, token: uploaded.deleteToken })
            }).catch((error) => console.error("Error: ", error));
        }

        survey.onClearFiles.add((_, options) => {
            if (!options.value || options.value.length === 0) {
                options.callback("success");
                return;
            }

            if (!options.fileName && !!options.value) {
                for (const item of options.value) {
                    deleteFile(item.content);
                }
            } else {
                const fileToRemove = options.value.find(
                    (item) => item.name === options.fileName
                );
                if (fileToRemove) {
                    deleteFile(fileToRemove.content);
                } else {
                    console.error(`File with name ${options.fileName} is not found`);
                }
            }
            options.callback("success");
        });

        if (config.draft && window.SurveyJSDrafts) {
            SurveyJSDrafts.attach(survey, config, container, partial);
        }

        SurveyUI.renderSurvey(survey, container);
        countView(config, container);

        // The page may come from a page cache made before the form closed, so the server is asked again.
        if (config.statusUrl) {
            fetch(config.statusUrl, { credentials: "same-origin", cache: "no-store" })
                .then((response) => response.ok ? response.json() : null)
                .then((result) => {
                    if (result && result.data && result.data.open === false) showRestriction(container, result.data.html);
                })
                .catch((error) => console.error("Error: ", error));
        }
    }

    window.wpSurveyJSInit = initSurvey;
    document.querySelectorAll(".wp-sjs-survey-config").forEach(initSurvey);
})();
