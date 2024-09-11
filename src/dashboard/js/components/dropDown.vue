<script setup>
    import { $dom } from '@/dashboard/js/composables/dom';
    import { computed, defineProps, onMounted } from 'vue';

    const emit = defineEmits(['accion']);
    const props = defineProps({
        title: {
            type: String,
            default: '',
            required: false,
        },
        from: {
            type: String,
            required: true,
        },
        buttonValue: {
            type: String,
            required: true,
        },
        list: {
            type: Array,
            required: true,
        },
    });
    const buttonValue = computed(() => props.buttonValue);
    const list = computed(() => props.list);
    const from = computed(() => props.from);
    const title = computed(() => props.title);

    const setButtonValue = value => {
        emit('accion', {
            from: from.value,
            accion: 'setButtonValue',
            item: value,
        });
    };

    const dropdownToggle = ($dropdown, $dropdownElement) => {
        $dropdownElement.classList.toggle('hidden');
        $dropdownElement.classList.toggle('block');
        $dropdownElement.setAttribute(
            'aria-hidden',
            $dropdownElement.classList.contains('hidden') ? 'true' : 'false',
        );

        if ($dropdownElement.classList.contains('block')) {
            $dropdownElement.style.top = `${$dropdown.offsetTop + $dropdown.offsetHeight + 10}px`;
            $dropdownElement.style.left = `${$dropdown.offsetLeft}px`;
            $dropdownElement.style.width = 'auto';
            $dropdownElement.style.whiteSpace = 'nowrap';
        }
    };

    onMounted(() => {
        const $dropdown = $dom(`#dropdownButton${from.value}`);
        const $dropdownElement = $dom(
            `#${$dropdown.getAttribute('data-dropdown-toggle-component')}`,
        );

        $dropdownElement.style.position = 'absolute';
        $dropdownElement.style.inset = '0px auto auto 0px';
        $dropdownElement.style.margin = '0px';
        $dropdownElement.style.transform = 'translate(0px, 0px)';
        if (!($dropdownElement instanceof HTMLElement)) {
            return;
        }
        $dropdown.addEventListener('click', () => {
            dropdownToggle($dropdown, $dropdownElement);
            document.addEventListener('click', e => {
                if (
                    $dropdownElement.classList.contains('block') &&
                    !$dropdown.contains(e.target)
                ) {
                    dropdownToggle($dropdown, $dropdownElement);
                }
            });
        });
    });
</script>
<template>
    <div>
        <span class="text-gray-500 pe-1">{{ title }}</span>
        <button
            :id="'dropdownButton' + from"
            :data-dropdown-toggle-component="'dropdownList' + from"
            class="inline-flex items-center text-gray-500 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-3 py-1.5 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
            type="button">
            {{ buttonValue }}
            <svg
                class="w-2.5 h-2.5 ms-2.5"
                aria-hidden="true"
                fill="none"
                viewBox="0 0 10 6"
                xmlns="http://www.w3.org/2000/svg">
                <path
                    d="m1 1 4 4 4-4"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2" />
            </svg>
        </button>
        <!-- Dropdown menu -->
        <div
            :id="'dropdownList' + from"
            class="z-10 bg-white rounded-lg shadow dark:bg-gray-700 hidden">
            <ul
                class="text-sm text-gray-700 dark:text-gray-200 grid justify-center cursor-pointer"
                aria-labelledby="dropdownPerPage">
                <li v-for="item in list">
                    <a
                        class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white hover:rounded-lg"
                        @click="setButtonValue(item)">
                        {{ item }}
                    </a>
                </li>
            </ul>
        </div>
    </div>
</template>
