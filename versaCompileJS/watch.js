const fs = require('fs').promises;
const { minify } = require('terser');
const { watch } = require('gulp');
const { glob } = require('glob');
const path = require('path');
const chalk = require('chalk');
const log = console.log.bind(console);
const error = console.error.bind(console);

const PATH_SOURCE = '../src';
const PATH_DIST = '../public';

let pathAlias = {};

const getPathAlias = async () => {
    const pathFile = '../jsconfig.json';

    const data = await fs.readFile(pathFile, 'utf-8');
    if (!data) return;

    const pathAlias = JSON.parse(data).compilerOptions.paths;
    log(chalk.green(`pathAlias: ${JSON.stringify(pathAlias)}`));

    return pathAlias;
};

const mapRuta = ruta => path.join(PATH_DIST, path.relative(PATH_SOURCE, ruta));

const init = async () => {
    try {
        const watchDir = `${PATH_SOURCE}/**/*.js`;

        watch([watchDir])
            .on('add', path => compile(path))
            .on('change', path => compile(path))
            .on('unlink', path => deleteFile(path));
        log(chalk.green(`Watching ${watchDir}`));

        pathAlias = await getPathAlias();

        // Ejecutar la compilación al inicio
        //await compileAll(watchDir);
    } catch (error) {
        error(chalk.red('Error al iniciar:'), error);
    }
};

const compile = path => {
    const pathParts = path.split('\\');
    const fileName = pathParts.pop();
    const ruta = pathParts.join('/') + '/';
    const extension = fileName.split('.').pop();

    let outputPath = mapRuta(ruta) + '\\' + fileName;

    outputPath = outputPath.replace('\\', '/');

    if (outputPath) {
        compileJS(path, outputPath);
    } else {
        log(chalk.yellow(`Tipo no reconocido: ${extension}`));
    }
};

const compileJS = async (source, destination) => {
    try {
        let data = await fs.readFile(source, 'utf-8');
        if (!data) return;

        data = removeVarHTML(data);
        data = removehtmlOfTemplateString(data);
        data = removeCodeTagImport(data);
        data = replaceAlias(data);

        // optener la hora actual
        const hora = new Date().getHours();
        const minutos = new Date().getMinutes();
        const segundos = new Date().getSeconds();

        const filename = path.basename(source);
        log(
            chalk.blue(
                `${hora}:${minutos}:${segundos} - Compilando ${filename}`
            )
        );

        const startTime = Date.now();

        const checkDataModule =
            data.includes('export default') || data.includes('import');

        const result = await minify(
            { [filename]: data },
            { compress: true, ecma: 2016, module: checkDataModule }
        );
        const endTime = Date.now();

        log(chalk.green(`Escribiendo ${destination}`));

        if (result.code.length === 0) {
            error(
                chalk.yellow('Warning al compilar JS: El archivo está vacío')
            );
            // eliminar si existe el archivo de destino
            await fs.unlink(destination);
        } else {
            const destinationDir = path.dirname(destination);
            await fs.mkdir(destinationDir, { recursive: true });
            await fs.writeFile(destination, result.code, 'utf-8');
            const elapsedTime = endTime - startTime;
            log(chalk.gray(`Compilación exitosa (${elapsedTime} ms)`));
        }
    } catch (errora) {
        error(chalk.red(`Error durante la compilación JS: ${errora}`));
    }
};

const compileAll = async watchDir => {
    try {
        const files = await glob(watchDir);
        for (const file of files) {
            compile(file);
        }
    } catch (errora) {
        error(chalk.red('Error durante la compilación inicial:'), errora);
    }
};

const deleteFile = async ruta => {
    const newPath = mapRuta(ruta.replace('\\', '/'));
    try {
        log(chalk.yellow(`Eliminando ${newPath}`));

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

        log(chalk.gray(`Eliminación exitosa: ${newPath}`));
    } catch (errora) {
        error(
            chalk.red(
                `Error al eliminar el archivo/directorio ${newPath}: ${errora}`
            )
        );
    }
};

/**
 * Removes the "html" tag from a template string.
 *
 * @param {string} data - The template string to remove the "html" tag from.
 * @returns {string} - The modified template string without the "html" tag.
 */
const removehtmlOfTemplateString = data => {
    const htmlRegExp = /html\s*`/g;
    data = data.replace(htmlRegExp, '`');
    return data;
};

const removeVarHTML = data => {
    /// var Vue;
    // var Vuex;
    // var Swal;
    // var mode_build;
    // remove all var Vue, var Vuex, var Swal, var mode_build

    const varRegExp = /var\s+Vue\s*;/g;
    data = data.replace(varRegExp, '');
    const varRegExpv = /var\s+vue\s*;/g;
    data = data.replace(varRegExpv, '');
    const varRegExp2 = /var\s+Vuex\s*;/g;
    data = data.replace(varRegExp2, '');
    const varRegExp3 = /var\s+Swal\s*;/g;
    data = data.replace(varRegExp3, '');
    const varRegExp4 = /var\s+mode_build\s*;/g;
    data = data.replace(varRegExp4, '');

    return data;
};

const replaceAlias = data => {
    for (const key in pathAlias) {
        const value = pathAlias[key][0];
        const alias = "from '" + key.replace('/*', '');
        const ruta = value.replace('/*', '');

        const aliasRegExp = new RegExp(alias, 'g');
        data = data.replace(aliasRegExp, " from '" + ruta);

        const newAliasRegExp = new RegExp('@/', 'g');
        data = data.replace(newAliasRegExp, ruta + '/');

        //reemplazar esto: from '. por eso from '/
        const aliasRegExp2 = new RegExp("from './", 'g');
        data = data.replace(aliasRegExp2, " from '/");

        const aliasRegExp3 = new RegExp("'./", 'g');
        data = data.replace(aliasRegExp3, "'/");
    }

    return data;
};

const removeCodeTagImport = data => {
    // remove import if exist code-tag

    const codeTagRegExp = /import\s+{.*}\s+from\s+['"].*code-tag.*['"];/g;
    data = data.replace(codeTagRegExp, '');
    return data;
};

init();
