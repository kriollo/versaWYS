/**
 * @preserve
 * Validates a Chilean RUT (Rol Único Tributario).
 *
 * @param {string} rut - The RUT to validate.
 * @returns {boolean} - Returns true if the RUT is valid, otherwise false.
 */
export const validateRut = rut => {
    if (!rut) return false;

    // Eliminar caracteres no válidos
    let rutValue = rut.replace(/[^0-9kK-]/g, '');
    const [num, dv] = rutValue.split('-');

    // Verificar que num y dv estén definidos
    if (!num || !dv) return false;

    let suma = 0;
    let multiplo = 2;

    // Calcular la suma de los productos
    for (let i = num.length - 1; i >= 0; i--) {
        suma += multiplo * Number(num.charAt(i));
        multiplo = multiplo < 7 ? multiplo + 1 : 2;
    }

    /**
     *  @type {string | number}
     */
    let dvEsperado = 11 - (suma % 11);
    if (dvEsperado === 10) {
        dvEsperado = 'K';
    } else if (dvEsperado === 11) {
        dvEsperado = '0';
    } else {
        dvEsperado = dvEsperado.toString();
    }

    // Comparar el dígito verificador esperado con el proporcionado
    return String(dvEsperado).toLowerCase() === dv.toLowerCase();
};
