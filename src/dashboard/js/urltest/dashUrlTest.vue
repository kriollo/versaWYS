<script setup>
    import { ref } from 'vue';
    const title = ref('Hello World');
    const count = ref(0);
</script>
<template>
    <div class="grid justify-center items-center content-center p-6">
        <h1 class="text-2xl font-bold clasePersonalizada">urlTest</h1>
        <p class="clasePersonalizada">{{ title }}</p>
    </div>
    <div class="grid justify-center items-center content-center p-6">
        <button
            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
            @click="title = 'Hello VersaWYS'">
            Change Title
        </button>
        <div class="pt-6">
            <button
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                @click="count++">
                +
            </button>
            <span
                class="text-white font-bold py-2 px-4 rounded">
                {{ count }}
            </span>
            <button
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                @click="count--">
                -
            </button>
        </div>
</template>
<style scoped>
    /* Agrega tu estilo aquí */
    .clasePersonalizada {
        color: red;
    }
</style>