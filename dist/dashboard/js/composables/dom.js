/**
 * @preserve
 * Selects the first element that matches the specified selector within the given context.
 *
 * @param {string} selector - The CSS selector to match the element.
 * @param {Document|Element} [context=document] - The context within which to search for the element.
 * @returns {Element|null} - The first matching element, or null if no element is found.
 */
export const $dom = (selector, context = document) => context.querySelector(selector);
/**
 * @preserve
 * Returns a list of elements that match the given selector within the specified context.
 *
 * @param {string} selector - The CSS selector to match elements against.
 * @param {Document|Element} [context=document] - The context within which to search for elements. Defaults to the document.
 * @returns { NodeList  } - A list of elements that match the given selector.
 */
export const $domAll = (selector, context = document) => context.querySelectorAll(selector);
export const blockedForm = ($form, status = 'true') => {
    $form.setAttribute('data-locked', status);
    const $submit = $form.querySelector('[type="submit"]');
    if ($submit instanceof HTMLButtonElement) {
        $submit.disabled = 'true' === status;
    }
    const $inputs = $form.querySelectorAll('input, select, textarea, button');
    $inputs.forEach($input => {
        if ($input instanceof HTMLInputElement) {
            $input.disabled = 'true' === status;
        }
        if ($input instanceof HTMLSelectElement) {
            $input.disabled = 'true' === status;
        }
        if ($input instanceof HTMLTextAreaElement) {
            $input.disabled = 'true' === status;
        }
        if ($input instanceof HTMLButtonElement) {
            $input.disabled = 'true' === status;
        }
    });
};
/**
 * @preserve
 * Serializes a form into an array of objects containing the form field names and values.
 * @param {HTMLFormElement} $form - The form element to be serialized.
 * @returns {Array} - An array of objects containing the form field names and values.
 */
export const serializeToArray = ($form) => Array.from(new FormData($form), ([name, value]) => {
    const element = $form.elements.namedItem(name);
    if (element && 'checkbox' === element.type) {
        value = element.checked ? value : '';
    }
    return { name, value };
});
/**
 * @preserve
 * Serializes a form into an object.
 *
 * @param {HTMLFormElement} $form - The form element to serialize.
 * @returns {Object} - The serialized form data as an object.
 */
export const serializeToObject = ($form) => serializeToArray($form).reduce((acc, { name, value }) => {
    acc[name] = value;
    return acc;
}, {});
/**
 * @preserve
 * Validates a form by checking if all required fields are filled.
 * @param {HTMLFormElement} $form - The form element to validate.
 * @returns {boolean} - Whether the form is valid.
 */
export const validateFormRequired = ($form) => {
    const requiredFields = $domAll('[required]', $form);
    let isValid = true;
    requiredFields.forEach(field => {
        field.parentElement?.classList.remove('border', 'border-red-500', 'border-solid', 'border-dashed');
        if (!(field instanceof HTMLInputElement)) {
            return;
        }
        if (!field.value) {
            field.parentElement?.classList.add('border', 'border-red-500', 'border-solid', 'border-dashed');
            isValid = false;
        }
    });
    return isValid;
};
//# sourceMappingURL=dom.js.map