<script setup>
defineProps({
    title: { type: String, default: "Sondages" },
});
</script>

<template>
    <header>
        <h1>{{ title }}</h1>
    </header>
</template>

<style scoped>
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background-color: #1e1b4b;
    color: white;
}
h1 {
    margin: 0;
    font-size: 1.25rem;
}
</style>
