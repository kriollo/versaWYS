<script setup>
    import { $dom } from '@/dashboard/js/composables/dom';
    import { computed, defineProps, onMounted } from 'vue';

    const model = defineModel();
    const emit = defineEmits(['accion', 'update:model']);

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
        list: {
            type: Array,
            required: true,
        },
    });
    const list = computed(() => props.list);
    const from = computed(() => props.from);
    const title = computed(() => props.title);

    const setButtonValue = value => {
        model.value = value;
        emit('update:model', {
            accion: 'setButtonValue',
            from: from.value,
            item: value,
        });
        dropdownToggle(
            $dom(`#dropdownButton${from.value}`),
            $dom(`#dropdownList${from.value}`),
        );
    };

    const dropdownToggle = ($dropdown, $dropdownElement) => {
        $dropdownElement.classList.toggle('block');
        if ($dropdownElement.classList.contains('hidden')) {
            $dropdownElement.classList.remove('hidden');
            setTimeout(() => {
                $dropdownElement.classList.remove('opacity-0');
                $dropdownElement.classList.add('opacity-100');
            }, 10);
        } else {
            $dropdownElement.classList.remove('opacity-100');
            $dropdownElement.classList.add('opacity-0');
            setTimeout(() => {
                $dropdownElement.classList.add('hidden');
            }, 300); // Duración de la transición
        }

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
        $dropdownElement.style.top = `${$dropdown.offsetTop + $dropdown.offsetHeight + 10}px`;
        $dropdownElement.style.left = `${$dropdown.offsetLeft}px`;
        $dropdownElement.style.width = 'auto';
        $dropdownElement.style.whiteSpace = 'nowrap';

        if (!($dropdownElement instanceof HTMLElement)) {
            return;
        }
        const documentClickListener = e => {
            if (
                $dropdownElement.classList.contains('block') &&
                !$dropdown.contains(e.target)
            ) {
                dropdownToggle($dropdown, $dropdownElement);
                document.removeEventListener('click', documentClickListener);
            }
        };

        $dropdown.addEventListener('click', () => {
            dropdownToggle($dropdown, $dropdownElement);
            document.addEventListener('click', documentClickListener);
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
            {{ model }}
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
            class="z-10 bg-white rounded-lg shadow dark:bg-gray-700 hidden transition-opacity duration-300 ease-in-out opacity-0">
            <ul
                class="text-sm text-gray-700 dark:text-gray-200 grid justify-center cursor-pointer"
                aria-labelledby="dropdownPerPage">
                <li v-for="(item, index) in list">
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
