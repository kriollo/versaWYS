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

const mapRuta = ruta => path.join(PATH_DIST, path.relative(PATH_SOURCE, ruta));

const init = async () => {
    try {
        const watchDir = `${PATH_SOURCE}/**/*.js`;

        watch([watchDir])
            .on('add', path => compile(path))
            .on('change', path => compile(path))
            .on('unlink', path => deleteFile(path));
        log(chalk.green(`Watching ${watchDir}`));

        // Ejecutar la compilación al inicio
        await compileAll(watchDir);
    } catch (error) {
        error(chalk.red('Error al iniciar:'), error);
    }
};

const compile = path => {
    const pathParts = path.split('\\');
    const fileName = pathParts.pop();
    const ruta = pathParts.join('/') + '/';
    const extension = fileName.split('.').pop();

    const outputPath = mapRuta(ruta) + '\\' + fileName;
    if (outputPath) {
        compileJS(path, outputPath);
    } else {
        log(chalk.yellow(`Tipo no reconocido: ${extension}`));
    }
};
const compileJS = async (source, destination) => {
    try {
        const data = await fs.readFile(source, 'utf-8');
        if (!data) return;

        const startTime = Date.now();
        const filename = path.basename(source);
        log(chalk.blue(`Compilando ${filename}`));

        const result = await minify({ [filename]: data }, { toplevel: true, compress: true, module: true });
        const endTime = Date.now();
        log(chalk.green(`Escribiendo ${destination}`));

        const destinationDir = path.dirname(destination);
        await fs.mkdir(destinationDir, { recursive: true });
        await fs.writeFile(destination, result.code, 'utf-8');

        const elapsedTime = endTime - startTime;
        log(chalk.gray(`Compilación exitosa (${elapsedTime} ms)`));
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
        } else {
            //validar si se puede eliminar el archivo
            if (stat.isFile()) await fs.unlink(newPath);
        }

        //revisar si se puede eliminar el directorio
        const dir = path.dirname(newPath);
        const files = await fs.readdir(dir);
        if (files.length === 0) {
            await fs.rmdir(dir);
        }

        log(chalk.gray(`Eliminación exitosa: ${newPath}`));
    } catch (errora) {
        error(chalk.red(`Error al eliminar el archivo/directorio ${newPath}: ${errora}`));
    }
};

init();
