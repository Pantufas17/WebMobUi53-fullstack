<script setup>
import { watch } from "vue";
import { useFetchApi } from "../composables/useFetchApi";

const props = defineProps({
    loginUrl: { type: String, default: null },
});

const { fetchApiToRef } = useFetchApi();
const { data: polls, error, loading } = fetchApiToRef({ url: "polls/" });

function handleError(err) {
    if (!err) return;
    if (err?.status === 401) window.location.href = props.loginUrl;
}

watch(error, handleError);
</script>

<template>
    <div>
        <p v-if="loading">Chargement des sondages...</p>
        <p v-else-if="error">Erreur lors du chargement.</p>
        <ul v-else-if="polls">
            <li v-for="poll in polls" :key="poll.id">
                {{ poll.question }}
            </li>
        </ul>
        <p v-else>Aucun sondage.</p>
    </div>
</template>

<style scoped></style>
