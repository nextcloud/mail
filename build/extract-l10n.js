const { GettextExtractor, JsExtractors } = require('gettext-extractor');

let extractor = new GettextExtractor();

extractor
    .createJsParser([
        JsExtractors.callExpression('t', {
            arguments: {
                text: 1,
                context: 1
            }
        }),
        JsExtractors.callExpression('n', {
            arguments: {
                text: 1,
                textPlural: 2,
                context: 3
            }
        })
    ])
    .parseFilesGlob('./src/**/*.@(ts|js|vue)');

extractor.savePotFile('./translationfiles/templates/mail-js.pot');

extractor.printStats();
