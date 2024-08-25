/**
 * Selects the first element that matches the specified selector within the given context.
 *
 * @param {string} selector - The CSS selector to match the element.
 * @param {Document|Element} [context=document] - The context within which to search for the element.
 * @returns {Element|null} - The first matching element, or null if no element is found.
 */
export const $dom = (selector, context = document) =>
    context.querySelector(selector);

/**
 * Returns a list of elements that match the given selector within the specified context.
 *
 * @param {string} selector - The CSS selector to match elements against.
 * @param {Document|Element} [context=document] - The context within which to search for elements. Defaults to the document.
 * @returns {NodeList} - A list of elements that match the selector.
 */
export const $domAll = (selector, context = document) =>
    context.querySelectorAll(selector);

/**
 * Sets the locked status of a form and disables its inputs and submit button accordingly.
 * @param {HTMLFormElement} $form - The form element to be locked.
 * @param {string} [status='true'] - The status to set for the form. Defaults to 'true'.
 */
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
 * Serializes a form into an array of objects containing the form field names and values.
 * @param {HTMLFormElement} $form - The form element to be serialized.
 * @returns {Array} - An array of objects containing the form field names and values.
 */
export const serializeToArray = $form =>
    Array.from(new FormData($form), ([name, value]) => {
        const element = $form.elements[name];
        if (element && element.type === 'checkbox') {
            value = element.checked;
        }
        return { name, value };
    });

/**
 * Serializes a form into an object.
 *
 * @param {HTMLFormElement} $form - The form element to serialize.
 * @returns {Object} - The serialized form data as an object.
 */
export const serializeToObject = $form =>
    serializeToArray($form).reduce((acc, { name, value }) => {
        acc[name] = value;
        return acc;
    }, {});
