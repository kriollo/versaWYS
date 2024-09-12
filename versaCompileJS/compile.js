import chalk from 'chalk';
import { promises as fs } from 'fs';
import { glob } from 'glob-promise';
import { watch } from 'gulp';
import path from 'path';
import { minify } from 'terser';
import * as m_compiler from 'vue/compiler-sfc';
const log = console.log.bind(console);
const error = console.error.bind(console);

const PATH_SOURCE = './src';
const PATH_DIST = './public';

let pathAlias = null;

// obtener parametro de entrada
let isAll = false;
if (process.argv.length > 1) {
    const args = process.argv.slice(2);
    isAll = args.includes('--all');
}

const getPathAlias = async () => {
    const pathFile = './tsconfig.json';

    const data = await fs.readFile(pathFile, 'utf-8');
    if (!data) return;

    const pathAlias = JSON.parse(data).compilerOptions.paths;
    log(chalk.green(`pathAlias: ${JSON.stringify(pathAlias)}`));

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

        const stat = await fs.stat(newPath);
        if (stat.isDirectory()) {
            await fs.rmdir(newPath, { recursive: true });
        } else if (stat.isFile()) {
            if (stat.isFile()) await fs.unlink(newPath);
        }

        const dir = path.dirname(newPath);
        const files = await fs.readdir(dir);
        if (files.length === 0) {
            await fs.rmdir(dir);
        }

        log(chalk.gray(`✅ :Eliminación exitosa: ${newPath} \n`));
    } catch (errora) {
        error(
            chalk.red(
                `❎ :Error al eliminar el archivo/directorio ${newPath}: ${errora}\n`,
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
    return data;
};

const replaceVarByConstHTML = async data => {
    const varRegExp = /var\s+/g;
    return data.replaceAll(varRegExp, 'const ');
};

const replaceAlias = async data => {
    for (const key in pathAlias) {
        const value = pathAlias[key][0];
        const newKey = key.replaceAll('/*', '');
        const alias = `from '${newKey}`;
        const alias2 = ` '${newKey}`;

        const ruta = value.replaceAll('/*', '').replace('./', '/');

        const aliasRegExp = new RegExp(alias, 'g');
        const aliasRegExp2 = new RegExp(alias2, 'g');

        data = data.replaceAll(aliasRegExp, ` from '${ruta}`);
        data = data.replaceAll(aliasRegExp2, `'${ruta}`);

        const aliasRegExp3 = /import '.\//g;
        data = data.replaceAll(aliasRegExp3, `import '/`);

        const aliasRegExp5 = /from '\.\//g;
        data = data.replaceAll(aliasRegExp5, `from '/`);
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

const estandarizaData = async data => {
    data = await removehtmlOfTemplateString(data);
    data = await removeCodeTagImport(data);
    data = await replaceVarByConstHTML(data);
    data = await replaceAlias(data);
    data = await addImportEndJs(data);
    return data;
};

const preCompileVue = async (data, source) => {
    const fileName = path.basename(source).replace('.vue', '');
    const __fileName = path.parse(source).name;

    const { descriptor, errors } = m_compiler.parse(data);
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
        compiledScript = m_compiler.compileScript(descriptor, {
            id,
            templateOptions,
        });
    } else {
        compiledScript = {
            content: `export default {}`,
        };
    }

    // Compile template y obtener el contenido del template
    const compiledTemplate = m_compiler.compileTemplate({
        ...templateOptions,
    });

    // Compile styles Y obtener el contenido de los estilos
    let insertStyles = '';
    if (descriptor.styles) {
        const styled = descriptor.styles.map(function (style) {
            return m_compiler.compileStyle({
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
                    styleTag.setAttribute('data-v-${fileName}', '');
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

    output = output.replace(
        'export default {',
        `export const ${fileName}_component = {
        __name: '${fileName}',
    `,
    );
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
        ${fileName}_component.__scopeId = '${scopeId}'
        export const ${fileName} = app.component('${fileName}', ${fileName}_component)
    `;

    output = `${output}\n${exportComponent}`;

    log(chalk.green(`🧪 :Pre Compilado VUE Finalizado ${fileName}`));

    await fs.writeFile(
        `./public/dashboard/js/${fileName}-temp.js`,
        output,
        'utf-8',
    );

    return output;
};

const compileJS = async (source, destination) => {
    try {
        const startTime = Date.now(); // optener la hora actual
        const hora = new Date().getHours();
        const minutos = new Date().getMinutes();
        const segundos = new Date().getSeconds();

        const filename = path.basename(source);
        log(
            chalk.blue(
                `🪄 :${hora}:${minutos}:${segundos} - Compilando ${filename}`,
            ),
        );

        let data = await fs.readFile(source, 'utf-8');
        if (!data) return;

        const extension = source.split('.').pop();
        if (extension === 'vue') {
            log(chalk.green(`🧪 :Pre Compilando VUE Inicia ${filename}`));
            data = await preCompileVue(data, source);
            if (data.error) {
                error(
                    chalk.red(
                        `❎ :Error durante la compilación Vue :${data.error}\n`,
                    ),
                );
                return;
            }
            destination = destination.replace('.vue', '.js');
        }

        data = await estandarizaData(data);

        log(chalk.blue(`🔍 :Minificando ${filename}`));
        const result = await minify(
            { [filename]: data },
            {
                compress: true,
                ecma: 2022,
                module: 'es6',
                toplevel: true,
                parse: {
                    bare_returns: true,
                    html5_comments: false,
                    shebang: false,
                },
            },
        );
        log(chalk.green(`📝 :Escribiendo ${destination}`));

        if (result.code.length === 0) {
            error(
                chalk.yellow(
                    '⚠️ :Warning al compilar JS: El archivo está vacío\n',
                ),
            );
            // eliminar si existe el archivo de destino
            await fs.unlink(destination);
        } else {
            const destinationDir = path.dirname(destination);
            await fs.mkdir(destinationDir, { recursive: true });
            await fs.writeFile(destination, result.code, 'utf-8');

            const endTime = Date.now();
            const elapsedTime = endTime - startTime;
            log(chalk.gray(`✅ :Compilación exitosa (${elapsedTime} ms) \n`));
        }
    } catch (errora) {
        error(
            chalk.red(`❎ :Error durante la compilación JS: ${errora}\n`),
            errora,
        );
    }
};
const compile = async path => {
    const pathParts = path.split('\\');
    const fileName = pathParts.pop();
    const ruta = `${pathParts.join('/')}/`;
    const extension = fileName.split('.').pop();

    let outputPath = `${await mapRuta(ruta)}\\${fileName}`;

    outputPath = outputPath.replace('\\', '/');

    if (outputPath) {
        await compileJS(path, outputPath);
    } else {
        log(chalk.yellow(`Tipo no reconocido: ${extension}`));
    }
};

const compileAll = async watchDir => {
    try {
        const files = glob.sync(watchDir);
        pathAlias = getPathAlias();

        for (const file of files) {
            compile(file);
        }
    } catch (errora) {
        error(chalk.red('❎ :Error durante la compilación inicial:'), errora);
    }
};

const init = async () => {
    try {
        const watchJS = `${PATH_SOURCE}/**/*.js`;
        const watchVue = `${PATH_SOURCE}/**/*.vue`;

        await watch([watchJS, watchVue])
            .on('add', path => compile(path))
            .on('change', path => compile(path))
            .on('unlink', path => deleteFile(path));
        log(chalk.green(`👀 :Watching ${[watchJS, watchVue].join(', ')}`));

        pathAlias = await getPathAlias();
    } catch (error) {
        error(
            chalk.red('❎ :Error al iniciar:'),
            error,
            error.fileName,
            error.lineNumber,
            error.stack,
        );
    }
};

if (isAll) {
    compileAll(`${PATH_SOURCE}/**/*.{js,vue}`);
} else init();
