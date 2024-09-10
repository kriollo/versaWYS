import esbuild from 'esbuild';
import vuePlugin from 'esbuild-plugin-vue3';
import { readFileSync, unlinkSync, writeFileSync } from 'fs';
import { resolve } from 'path';

// Obtener los argumentos de la línea de comandos
const args = process.argv.slice(2);
const entryPoints = args[0]; // Primer argumento: archivo de entrada

if (!entryPoints) {
    console.error('Por favor, proporciona el archivo de entrada.');
    process.exit(1);
}

// Ruta de salida temporal
const __dirname = resolve();
const tempOutfile = resolve(__dirname, 'temp_bundle.js');

esbuild
    .build({
        entryPoints: [entryPoints],
        bundle: true,
        outfile: tempOutfile, // Ruta de salida temporal
        plugins: [vuePlugin()],
        format: 'esm',
        // minify: true,
        external: ['vue'],
    })
    .then(() => {
        // Leer la salida desde el archivo temporal
        const output = readFileSync(tempOutfile, 'utf8');

        // Aquí puedes modificar la cadena antes de escribirla en un archivo
        const modifiedOutput = `import { app } from '/public/dashboard/js/vue-instancia.js';\n${output}`;

        // Escribir la cadena modificada en un archivo
        writeFileSync('dist/bundle.js', modifiedOutput);

        // Eliminar el archivo temporal
        unlinkSync(tempOutfile);

        console.log('Compilación exitosa y archivo escrito.');
    })
    .catch(err => {
        console.error('Error durante la compilación:', err);
        process.exit(1);
    });
