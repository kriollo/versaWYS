import chalk from 'chalk';
import { watch } from 'gulp';
import {
    glob,
    mkdir,
    readdir,
    readFile,
    rmdir,
    unlink,
    writeFile,
} from 'node:fs/promises';
import path from 'node:path';
import { minify } from 'terser';
import * as vCompiler from 'vue/compiler-sfc';
const log = console.log.bind(console);
const error = console.error.bind(console);

let PATH_SOURCE = './src';
let PATH_DIST = './public';

let pathAlias = null;

// obtener parametro de entrada
let isAll = false;
let isProd = false;
if (process.argv.length > 1) {
    const args = process.argv.slice(2);
    isAll = args.includes('--all');

    if (args.includes('--prod')) {
        isProd = true;
    }

    console.log(chalk.green(`isAll: ${isAll}`));
    console.log(chalk.green(`isProd: ${isProd}`));
}

const getPathAlias = async () => {
    const pathFile = './tsconfig.json';

    const data = await readFile(pathFile, { encoding: 'utf-8' });

    if (!data) {
        error(chalk.red('🚩 :Error al leer el archivo tsconfig.json'));
        return;
    }

    const pathAlias = JSON.parse(data).compilerOptions.paths;
    log(chalk.green(`pathAlias: ${JSON.stringify(pathAlias)}`));

    const sourceRoot = JSON.parse(data).compilerOptions.sourceRoot;
    if (sourceRoot) {
        PATH_SOURCE = sourceRoot;
        if (PATH_SOURCE.endsWith('/')) {
            PATH_SOURCE = sourceRoot.slice(0, -1);
        }
    }

    const outDir = JSON.parse(data).compilerOptions.outDir;
    if (outDir) {
        PATH_DIST = outDir;
        if (PATH_DIST.endsWith('/')) {
            PATH_DIST = outDir.slice(0, -1);
        }
    }

    console.log(chalk.green(`PATH_SOURCE: ${PATH_SOURCE}`));
    console.log(chalk.green(`PATH_DIST: ${PATH_DIST}\n`));

    return pathAlias;
};

const mapRuta = async ruta =>
    path.join(PATH_DIST, path.relative(PATH_SOURCE, ruta));

const deleteFile = async ruta => {
    const newPath = (
        await mapRuta(ruta.replace('\\', '/').replace('.vue', '.js'))
    ).toString();
    try {
        log(chalk.yellow(`🗑️ :Eliminando ${newPath}`));

        const stat = await stat(newPath);
        if (stat.isDirectory()) {
            await rmdir(newPath, { recursive: true });
        } else if (stat.isFile()) {
            if (stat.isFile()) await unlink(newPath);
        }

        const dir = path.dirname(newPath);
        const files = await readdir(dir);
        if (files.length === 0) {
            await rmdir(dir);
        }

        log(chalk.gray(`✅ :Eliminación exitosa: ${newPath} \n`));
    } catch (errora) {
        error(
            chalk.red(
                `🚩 :Error al eliminar el archivo/directorio ${newPath}: ${errora}\n`,
            ),
        );
    }
};

/**
 * Removes the "html" tag from a template string.
 *
 * @param {string} data - The template string to remove the "html" tag from.
 * @returns {string} - The modified template string without the "html" tag.
 */
const removehtmlOfTemplateString = async data => {
    const htmlRegExp = /html\s*`/g;

    data = data.replace(htmlRegExp, '`');

    //remove ", get html() { return html }"
    const htmlGetterRegExp = /,\s*get\s+html\(\)\s*{\s*return\s*html\s*}/g;
    data = data.replace(htmlGetterRegExp, '');

    return data;
};

const replaceVarByConstHTML = async data => {
    // Reemplaza todas las declaraciones de variables por constantes, excepto las que contienen 'html'
    const varRegExp = /\bvar\b\s+/g;
    return data.replaceAll(varRegExp, 'const ');
};

const replaceAlias = async data => {
    // Función para escapar los caracteres especiales en una expresión regular
    const escapeRegExp = string =>
        string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    for (const key in pathAlias) {
        const values = pathAlias[key];
        // Escapa el alias para usarlo en una expresión regular
        const escapedKey = escapeRegExp(key.replace('/*', ''));

        // Genera las posibles variaciones del alias en formato de expresión regular
        const aliasPatterns = [
            new RegExp(`import\\(\\s*['"]${escapedKey}`, 'g'),
            new RegExp(`from\\s*['"]${escapedKey}`, 'g'),
            new RegExp(`['"]${escapedKey}`, 'g'),
            new RegExp(`import\\(\`\\${escapedKey}`, 'g'), // Maneja el caso de backticks
        ];

        for (const value of values) {
            const replacement = value.replace('/*', '').replace('./', '/');

            // Aplica todos los reemplazos
            for (const pattern of aliasPatterns) {
                data = data.replace(pattern, match => {
                    // Ajuste específico según el patrón encontrado
                    if (match.startsWith('import(`')) {
                        return `import(\`${replacement}`;
                    } else if (match.startsWith('import(')) {
                        return `import('${replacement}`;
                    } else if (match.startsWith('from ')) {
                        return `from '${replacement}`;
                    } else {
                        return `'${replacement}`;
                    }
                });
            }
        }

        // Reemplaza los patrones específicos './' por '/'
        data = data.replace(/import ['"]\.\//g, "import '/");
        data = data.replace(/from ['"]\.\//g, "from '/");
    }

    return data;
};

const removeCodeTagImport = async data => {
    // remove import if exist code-tag

    const codeTagRegExp = /import\s+{.*}\s+from\s+['"].*code-tag.*['"];/g;
    data = data.replace(codeTagRegExp, '');
    return data;
};

const addImportEndJs = async data => {
    const importRegExp = /import\s+[\s\S]*?\s+from\s+['"].*['"];/g;
    const importList = data.match(importRegExp);

    if (importList) {
        for (const item of importList) {
            const importRegExp2 = /from\s+['"](.*)['"];/;
            const result = item.match(importRegExp2);
            if (result) {
                const ruta = result[1];

                if (!ruta.endsWith('.js')) {
                    if (
                        ruta.endsWith('.mjs') ||
                        ruta.endsWith('.css') ||
                        !ruta.includes('/')
                    )
                        continue;
                    const newRuta = `${ruta}.js`;
                    const newImport = item.replace(ruta, newRuta);
                    data = data.replace(item, newImport);
                }
            }
        }
    }
    return data;
};

const removePreserverComent = async data => {
    const preserverRegExp =
        /\/\*[\s\S]*?@preserve[\s\S]*?\*\/|\/\/.*?@preserve.*?(?=\n|$)/g;
    data = data.replace(preserverRegExp, match =>
        match.replace(/@preserve/g, ''),
    );
    return data;
};

const estandarizaData = async data => {
    if (isProd) {
        data = await removePreserverComent(data);
    }
    data = await removehtmlOfTemplateString(data);
    data = await removeCodeTagImport(data);
    data = await replaceVarByConstHTML(data);
    data = await replaceAlias(data);
    data = await addImportEndJs(data);

    return data;
};

const compileCustomBlock = async (block, source) => {};

const preCompileVue = async (data, source) => {
    const fileName = path.basename(source).replace('.vue', '');
    const __fileName = path.parse(source).name;

    const { descriptor, errors } = vCompiler.parse(data, {
        filename: fileName,
        sourceMap: false,
        sourceRoot: path.dirname(source),
    });

    if (errors.length !== 0) {
        throw new Error(`preCompileVue: ${errors.toString()}`);
    }

    const id = Math.random().toString(36).slice(2, 12);
    const hasScoped = descriptor.styles.some(function (e) {
        return e.scoped;
    });
    const scopeId = hasScoped ? `data-v-${id}` : '';
    const templateOptions = {
        sourceMap: false,
        filename: `${descriptor.filename}.vue`,
        isProd: true,
        id: id,
        scoped: hasScoped,
        slotted: descriptor.slotted,
        source: descriptor.template.content,
        compilerOptions: {
            scopeId: hasScoped ? scopeId : null,
            mode: 'module',
            prefixIdentifiers: true,
            hoistStatic: true,
            cacheHandlers: true,
            runtimeGlobalName: 'Vue',
            runtimeModuleName: 'vue',
            optimizeBindings: true,
            runtimeContextBuiltins: true,
            runtimeDirectives: true,
            runtimeVNode: true,
            runtimeProps: true,
            runtimeSlots: true,
            runtimeComponents: true,
            runtimeCompiledRender: true,
            runtimeCompilerOptions: {
                whitespace: 'condense', // Opciones del compilador de templates
            },
        },
        preprocessLang: descriptor.template.lang,
        inlineTemplate: descriptor.template.lang === 'html',

        templateOptions: {
            compilerOptions: {
                whitespace: 'condense', // Opciones del compilador de templates
            },
        },
    };

    // Compile script
    let compiledScript;
    if (descriptor.script || descriptor.scriptSetup) {
        compiledScript = vCompiler.compileScript(descriptor, {
            id,
            templateOptions,
        });
    } else {
        compiledScript = {
            content: `export default {}`,
        };
    }

    // Compile template y obtener el contenido del template
    const compiledTemplate = vCompiler.compileTemplate({
        ...templateOptions,
    });

    let customBlocks = '';
    if (descriptor.customBlocks.length > 0) {
        // eliminar el ultimo caracter que es un punto y coma
        customBlocks = descriptor?.customBlocks[0].content.slice(0, -1) ?? '';
    }

    // Compile styles Y obtener el contenido de los estilos
    let insertStyles = '';
    if (descriptor.styles) {
        const styled = descriptor.styles.map(function (style) {
            return vCompiler.compileStyle({
                id,
                source: style.content,
                scoped: style.scoped,
                preprocessLang: style.lang,
            });
        });
        if (styled.length) {
            const cssCode = styled
                .map(function (s) {
                    return `${s.code}`;
                })
                .join('\n');
            insertStyles = `
                (function(){
                    let styleTag = document.createElement('style');
                    styleTag.setAttribute('data-v-${id}', '');
                    styleTag.innerHTML = \`${cssCode}\`;
                    document.head.appendChild(styleTag);
                })();
                `;
        }
    }

    // Combine all parts into a single module
    let output = `
    ${insertStyles}
    ${compiledScript.content}
    ${compiledTemplate.code}
  `;
    //añardir instancia de app
    const app = `import { app } from '@/dashboard/js/vue-instancia';`;
    output = `${app}${output}`;

    if (output.includes('export default {')) {
        output = output.replace(
            'export default {',
            `const ${fileName}_component = {
            __name: '${fileName}',
        `,
        );
    } else {
        output = output.replace(
            'export default /*@__PURE__*/_defineComponent({',
            `const ${fileName}_component = /*@__PURE__*/_defineComponent({
                __name: '${fileName}',
            `,
        );
    }

    // reemplazamos cuando usamos script setup
    if (descriptor.scriptSetup) {
        output = output.replaceAll(/_ctx\.(?!\$)/g, '$setup.');
        output = output.replace(
            'export function render(_ctx, _cache) {',
            'function render(_ctx, _cache, $props, $setup, $data, $options) {',
        );
    }

    const exportComponent = `
        ${fileName}_component.render = render
        ${fileName}_component.__file = '${__fileName}'
        ${hasScoped ? `${fileName}_component.__scopeId = '${scopeId}'` : ''}
        ${customBlocks}
        export const ${fileName} = app.component('${fileName}', ${fileName}_component)
    `;

    output = `${output}\n${exportComponent}`;

    // await writeFile(
    //     `./public/dashboard/js/${fileName}-temp.js`,
    //     output,
    //     'utf-8',
    // );

    // log(chalk.green(`🧪 :Pre Compilado VUE Finalizado ${fileName}`));

    return output;
};

const compileJS = async (source, destination) => {
    try {
        const startTime = Date.now(); // optener la hora actual

        const filename = path.basename(source);
        await log(chalk.blue(`🪄  :start transformation`));

        let data = await readFile(source, 'utf-8');
        if (!data) {
            await error(chalk.yellow('⚠️ :Archivo vacío\n'));
            return;
        }

        const extension = source.split('.').pop();
        if (extension === 'vue') {
            await log(chalk.green(`💚 :Pre Compile VUE`));
            data = await preCompileVue(data, source);
            if (data.error) {
                await error(
                    chalk.red(
                        `🚩 :Error durante la compilación Vue :${data.error}\n`,
                    ),
                );
                return;
            }
            destination = destination.replace('.vue', '.js');
        }

        data = await estandarizaData(data);

        await log(chalk.blue(`🤖 :minifying`));
        const result = await minify(
            { [filename]: data },
            {
                compress: {
                    passes: 2,
                },
                ecma: 2022,
                module: 'es6',
                toplevel: true,
                parse: {
                    bare_returns: true,
                    html5_comments: false,
                    shebang: false,
                },
                format: {
                    preamble: '/* WYS Soluciones Informatica - VersaWYS */',
                },
            },
        );
        await log(chalk.green(`📝 :Escribiendo ${destination}`));

        if (result.code.length === 0) {
            await error(
                chalk.yellow(
                    '⚠️ :Warning al compilar JS: El archivo está vacío\n',
                ),
            );
            // eliminar si existe el archivo de destino
            await unlink(destination);
        } else {
            if (!isProd) {
                result.code = result.code.replaceAll('*/;export', '*/\nexport');
                result.code = result.code.replaceAll('*/export', '*/\nexport');
            }
            const destinationDir = path.dirname(destination);
            await mkdir(destinationDir, { recursive: true });
            await writeFile(destination, result.code, 'utf-8');

            const endTime = Date.now();
            const elapsedTime = endTime - startTime;
            await log(
                chalk.gray(`✅ :Compilación exitosa (${elapsedTime} ms) \n`),
            );
        }
    } catch (errora) {
        error(
            chalk.red(`🚩 :Error durante la compilación JS: ${errora}\n`),
            errora,
        );
    }
};
const compile = async path => {
    console.log(chalk.green(`🔜 :Source ${path}`));

    const outputPath = path.replace(PATH_SOURCE, PATH_DIST);
    const outFileJs = outputPath.replace('.vue', '.js');
    console.log(chalk.green(`🔚 :destination ${outFileJs}`));

    const extension = path.split('.').pop();

    if (outputPath) {
        await compileJS(path, outputPath);
    } else {
        await log(chalk.yellow(`⚠️ :Tipo no reconocido: ${extension}`));
    }
};

const compileAll = async watchDir => {
    try {
        pathAlias = await getPathAlias();
        for await (const file of glob(watchDir)) {
            await compile(file.startsWith('./') ? file : `./${file}`);
        }
    } catch (errora) {
        error(chalk.red('🚩 :Error durante la compilación inicial:'), errora);
    }
};

const init = async () => {
    try {
        pathAlias = await getPathAlias();
        log(chalk.green(`👀 :Watching ${[watchJS, watchVue].join(', ')}\n`));
        watch([watchJS, watchVue])
            .on('add', path =>
                compile(path.startsWith('./') ? path : `./${path}`),
            )
            .on('change', path =>
                compile(path.startsWith('./') ? path : `./${path}`),
            )
            .on('unlink', path =>
                deleteFile(path.startsWith('./') ? path : `./${path}`),
            );
    } catch (error) {
        error(
            chalk.red('🚩 :Error al iniciar:'),
            error,
            error.fileName,
            error.lineNumber,
            error.stack,
        );
    }
};

const watchJS = `${PATH_SOURCE}/**/*.js`;
const watchVue = `${PATH_SOURCE}/**/*.vue`;
if (isAll) {
    console.log(chalk.green('🔄️ :Compilando todos los archivos...'));
    compileAll([watchJS, watchVue]);
} else init();
