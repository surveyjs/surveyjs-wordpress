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
        const apiName = captcha.type === "turnstile" ? "turnstile" : "grecaptcha";
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

    function withTransparentBackground(theme) {
        const cssVariables = Object.assign({}, theme && theme.cssVariables, {
            "--sjs2-color-utility-body": "transparent",
            "--sjs2-color-utility-surface-survey": "transparent"
        });
        return Object.assign({}, theme, { cssVariables: cssVariables });
    }

    function initSurvey(configElement) {
        if (configElement.dataset.initialized) {
            return;
        }
        configElement.dataset.initialized = "true";
        const config = JSON.parse(configElement.textContent);
        const honeypot = configElement.parentElement.querySelector(".wp-sjs-hp input");
        const survey = new Survey.Model(config.json);
        survey.onTextMarkdown.add(SurveyJSMarkdown.handler(config.markdown));
        // SurveyJS keeps files as base64 in the answer by default; upload them so they can be attached to emails.
        survey.getAllQuestions().filter((question) => question.getType() === "file").forEach((question) => {
            question.storeDataAsText = false;
        });
        const container = configElement.parentElement.querySelector(".wp-sjs-survey");
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

        function saveResult(data, fields) {
            const detail = { id: config.id, survey: survey, config: config, container: container, data: data };
            return (partial ? partial.idle() : Promise.resolve())
                .then(() => extraSubmitFields(detail))
                .then((extra) => fetch(config.saveResultUrl, {
                    method: "POST",
                    body: requestBody(Object.assign(extra, { SurveyId: config.id, Json: JSON.stringify(data), Website: honeypot ? honeypot.value : "", _wpnonce: config.saveResultNonce }, partial ? partial.fields() : {}, fields))
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
                        return response.json().then(
                            (result) => Promise.reject((result && result.data && result.data.message) || messages.failed),
                            () => Promise.reject(messages.failed)
                        );
                    })
                    .catch((error) => {
                        options.allow = false;
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
                .then((response) => response.ok ? options.showSaveSuccess() : response.text().then((text) => options.showSaveError(text)))
                .catch(() => options.showSaveError());
        });

        const previewUrls = {};
        // Uploaded files are not public: the preview shows them through the signed link returned by the upload.
        survey.onDownloadFile.add((_, options) => {
            options.callback("success", previewUrls[options.content] || options.content);
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
                        throw new Error(data.error);
                    }
                    options.callback(
                        options.files.map((file) => {
                            const uploaded = data[file.name];
                            previewUrls[uploaded.url] = uploaded.preview;
                            return {
                                file: file,
                                content: uploaded.url
                            };
                        })
                    );
                })
                .catch((error) => {
                    console.error("Error: ", error);
                    options.callback([], [ 'An error occurred during file upload.' ]);
                });
        });

        function deleteFile(fileURL, options) {
            try {
                fetch(`${config.deleteFileUrl}&name=${encodeURIComponent(fileURL)}`);
            } catch (error) {
                options.callback("error");
            }
        }

        survey.onClearFiles.add((_, options) => {
            if (!options.value || options.value.length === 0) {
                options.callback("success");
                return;
            }

            if (!options.fileName && !!options.value) {
                for (const item of options.value) {
                    deleteFile(item.content, options);
                }
            } else {
                const fileToRemove = options.value.find(
                    (item) => item.name === options.fileName
                );
                if (fileToRemove) {
                    deleteFile(fileToRemove.content, options);
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
    }

    window.wpSurveyJSInit = initSurvey;
    document.querySelectorAll(".wp-sjs-survey-config").forEach(initSurvey);
})();
