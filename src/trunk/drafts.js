(function () {
    const SAVE_DELAY = 1000;

    function browserStore(key) {
        return {
            load() {
                try {
                    return JSON.parse(localStorage.getItem(key));
                } catch (error) {
                    return null;
                }
            },
            save(draft) {
                try {
                    localStorage.setItem(key, JSON.stringify(draft));
                } catch (error) {
                    console.error("Error: ", error);
                }
            },
            remove() {
                try {
                    localStorage.removeItem(key);
                } catch (error) {
                    console.error("Error: ", error);
                }
            }
        };
    }

    function serverStore(options, surveyId) {
        let queue = Promise.resolve();

        function send(action, fields, keepalive) {
            const body = new URLSearchParams(Object.assign({ action: action, SurveyId: surveyId, _wpnonce: options.nonce }, fields));
            return fetch(options.url, { method: "POST", body: body, credentials: "same-origin", keepalive: keepalive })
                .catch((error) => console.error("Error: ", error));
        }

        function enqueue(action, fields) {
            queue = queue.then(() => send(action, fields, false));
        }

        return {
            load() {
                return options.saved;
            },
            save(draft, leavingPage) {
                const fields = { Data: JSON.stringify(draft.data), CurrentPageNo: draft.currentPageNo, Partial: JSON.stringify(draft.partial || null) };
                if (leavingPage) {
                    send("SurveyJS_SaveDraft", fields, true);
                } else {
                    enqueue("SurveyJS_SaveDraft", fields);
                }
            },
            remove() {
                enqueue("SurveyJS_DeleteDraft", {});
            }
        };
    }

    function attach(survey, config, container, partial) {
        const options = config.draft;
        const store = options.storageKey ? browserStore(options.storageKey) : serverStore(options, config.id);
        const saved = store.load();
        let timer = 0;
        let touched = false;
        let stored = false;
        let paused = false;
        let notice = null;

        function save(leavingPage) {
            clearTimeout(timer);
            timer = 0;
            const data = survey.data;
            if (Object.keys(data).length === 0) {
                removeDraft();
                return;
            }
            stored = true;
            store.save({ data: data, currentPageNo: survey.currentPageNo, savedAt: Math.floor(Date.now() / 1000), partial: partial ? partial.get() : null }, leavingPage);
        }

        function removeDraft() {
            if (stored) {
                stored = false;
                store.remove();
            }
        }

        function scheduleSave() {
            if (paused) {
                return;
            }
            touched = true;
            clearTimeout(timer);
            timer = setTimeout(save, SAVE_DELAY);
        }

        function hideNotice() {
            if (notice) {
                notice.remove();
                notice = null;
            }
        }

        function startOver() {
            clearTimeout(timer);
            timer = 0;
            paused = true;
            survey.clear(true, true);
            paused = false;
            touched = false;
            removeDraft();
            hideNotice();
        }

        function showNotice() {
            notice = document.createElement("div");
            notice.className = "wp-sjs-draft-notice";
            notice.setAttribute("role", "status");
            const text = document.createElement("span");
            text.textContent = options.restoredText;
            const button = document.createElement("button");
            button.type = "button";
            button.className = "wp-sjs-draft-notice__start-over";
            button.textContent = options.startOverText;
            button.addEventListener("click", startOver);
            notice.append(text, button);
            container.parentElement.insertBefore(notice, container);
        }

        const isFresh = saved && saved.data && Date.now() / 1000 - saved.savedAt < options.maxAge;
        if (isFresh) {
            survey.data = saved.data;
            if (saved.currentPageNo < survey.visiblePages.length) {
                survey.currentPageNo = saved.currentPageNo;
            }
            if (partial && saved.partial) {
                partial.set(saved.partial);
            }
            touched = true;
            stored = true;
            showNotice();
        } else if (saved) {
            store.remove();
        }

        survey.onValueChanged.add(scheduleSave);
        if (partial) {
            partial.onChange(() => {
                if (touched) {
                    scheduleSave();
                }
            });
        }
        survey.onCurrentPageChanged.add(() => {
            if (touched) {
                scheduleSave();
            }
        });
        survey.onComplete.add(() => {
            clearTimeout(timer);
            timer = 0;
            touched = false;
            removeDraft();
            hideNotice();
        });
        window.addEventListener("pagehide", () => {
            if (timer) {
                save(true);
            }
        });
    }

    window.SurveyJSDrafts = { attach: attach };
})();
