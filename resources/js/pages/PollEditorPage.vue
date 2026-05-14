<script setup>
import { ref } from "vue";
import { useFetchApi } from "../composables/useFetchApi";

const emit = defineEmits(["navigate"]);

const { fetchApi } = useFetchApi();

const question = ref("");
const title = ref("");
const options = ref(["", ""]);
const isDraft = ref(true);
const allowMultiple = ref(false);
const resultsPublic = ref(false);
const duration = ref(null);
const error = ref(null);
const loading = ref(false);

function addOption() {
    options.value.push("");
}

function removeOption(index) {
    if (options.value.length > 2) {
        options.value.splice(index, 1);
    }
}

function submit() {
    error.value = null;
    loading.value = true;

    fetchApi({
        url: "polls/",
        data: {
            title: title.value || null,
            question: question.value,
            options: options.value.filter((o) => o.trim() !== ""),
            is_draft: isDraft.value,
            allow_multiple_choices: allowMultiple.value,
            results_public: resultsPublic.value,
            duration: duration.value ? Number(duration.value) : null,
        },
    })
        .then(() => emit("navigate", "#dashboard"))
        .catch((err) => {
            error.value = err?.data?.message || "Erreur lors de la création.";
        })
        .finally(() => {
            loading.value = false;
        });
}
</script>

<template>
    <div>
        <h2>Nouveau sondage</h2>

        <p v-if="error" style="color: red">{{ error }}</p>

        <form @submit.prevent="submit">
            <div>
                <label
                    >Titre (optionnel)<br />
                    <input
                        v-model="title"
                        type="text"
                        placeholder="Titre du sondage"
                    />
                </label>
            </div>

            <div>
                <label
                    >Question *<br />
                    <input
                        v-model="question"
                        type="text"
                        placeholder="Votre question"
                        required
                    />
                </label>
            </div>

            <div>
                <p><strong>Options (min. 2)</strong></p>
                <div v-for="(option, index) in options" :key="index">
                    <input
                        v-model="options[index]"
                        type="text"
                        :placeholder="`Option ${index + 1}`"
                        required
                    />
                    <button
                        type="button"
                        @click="removeOption(index)"
                        :disabled="options.length <= 2"
                    >
                        ✕
                    </button>
                </div>
                <button type="button" @click="addOption">
                    + Ajouter une option
                </button>
            </div>

            <div>
                <label
                    ><input v-model="isDraft" type="checkbox" />
                    Brouillon</label
                ><br />
                <label
                    ><input v-model="allowMultiple" type="checkbox" /> Choix
                    multiples</label
                ><br />
                <label
                    ><input v-model="resultsPublic" type="checkbox" /> Résultats
                    publics</label
                >
            </div>

            <div>
                <label
                    >Durée (secondes, optionnel)<br />
                    <input
                        v-model="duration"
                        type="number"
                        min="1"
                        placeholder="ex: 3600"
                    />
                </label>
            </div>

            <button type="submit" :disabled="loading">
                {{ loading ? "Création..." : "Créer le sondage" }}
            </button>
            <button type="button" @click="emit('navigate', '#dashboard')">
                Annuler
            </button>
        </form>
    </div>
</template>

<style scoped>
div {
    margin-bottom: 1rem;
}
input[type="text"],
input[type="number"] {
    padding: 0.4rem;
    margin-top: 0.25rem;
    width: 100%;
    max-width: 400px;
}
button {
    margin-top: 0.5rem;
    margin-right: 0.5rem;
    padding: 0.4rem 0.8rem;
    cursor: pointer;
}
</style>
