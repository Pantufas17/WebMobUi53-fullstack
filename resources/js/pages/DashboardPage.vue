<script setup>
import { onActivated, watch } from "vue";
import { useFetchApi } from "../composables/useFetchApi";

const emit = defineEmits(["navigate"]);

const props = defineProps({
    loginUrl: { type: String, default: null },
});

const { fetchApiToRef, fetchApi } = useFetchApi();
const {
    data: polls,
    error,
    loading,
    fetchNow,
} = fetchApiToRef({ url: "polls/" });

function handleError(err) {
    if (!err) return;
    if (err?.status === 401) window.location.href = props.loginUrl;
}

watch(error, handleError);
onActivated(() => fetchNow());

function deletePoll(id) {
    if (!confirm("Supprimer ce sondage ?")) return;
    fetchApi({ url: `polls/${id}`, method: "DELETE" })
        .then(() => fetchNow())
        .catch((err) => console.error(err));
}
</script>

<template>
    <div>
        <div
            style="
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1rem;
            "
        >
            <h2 style="margin: 0">Mes sondages</h2>
            <button @click="emit('navigate', '#create')">
                + Nouveau sondage
            </button>
        </div>

        <p v-if="loading">Chargement des sondages...</p>
        <p v-else-if="error">Erreur lors du chargement.</p>
        <ul v-else-if="polls">
            <li
                v-for="poll in polls"
                :key="poll.id"
                style="margin-bottom: 0.5rem"
            >
                {{ poll.question }}
                <button
                    @click="deletePoll(poll.id)"
                    style="margin-left: 1rem; color: red"
                >
                    Supprimer
                </button>
            </li>
        </ul>
        <p v-else>Aucun sondage.</p>
    </div>
</template>

<style scoped>
button {
    padding: 0.5rem 1rem;
    cursor: pointer;
}
</style>
