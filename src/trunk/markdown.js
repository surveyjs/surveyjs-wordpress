(function () {
    let markdown = null;

    function renderer() {
        if (!markdown) {
            markdown = window.markdownit({ breaks: true });
            markdown.renderer.rules.link_open = (tokens, index, options, env, self) => {
                tokens[index].attrSet("target", "_blank");
                tokens[index].attrSet("rel", "noopener");
                return self.renderToken(tokens, index, options);
            };
        }
        return markdown;
    }

    function renderLinks(_, options) {
        const text = options.text.replace(/[&<>"']/g, (char) => "&#" + char.charCodeAt(0) + ";");
        const html = text.replace(/\[([^\]]+)\]\((https?:\/\/[^\s()]+)\)/g, "<a href=\"$2\" target=\"_blank\" rel=\"noopener\">$1</a>");
        if (html !== text) options.html = html;
    }

    function renderMarkdown(_, options) {
        if (/Html$/.test(options.name)) return;
        const md = renderer();
        const html = md.render(options.text).replace(/^<p>((?:(?!<p>)[\s\S])*)<\/p>\n$/, "$1");
        if (html !== md.utils.escapeHtml(options.text)) options.html = html;
    }

    window.SurveyJSMarkdown = {
        handler: (enabled) => (enabled && window.markdownit ? renderMarkdown : renderLinks)
    };
})();
