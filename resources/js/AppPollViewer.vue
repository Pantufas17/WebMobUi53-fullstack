<script setup>
import { ref, computed, watch } from "vue";
import { useFetchApi } from "./composables/useFetchApi";
import { usePolling } from "./composables/usePolling";

const props = defineProps({
    token: { type: String, required: true },
    loginUrl: { type: String, default: null },
    userId: { type: Number, default: null },
});

const { fetchApiToRef, fetchApi } = useFetchApi();
const {
    data: pollData,
    error,
    loading,
    fetchNow,
} = fetchApiToRef({
    url: `polls/${props.token}`,
});

const poll = computed(() => pollData.value?.poll ?? null);
const userVoteIds = computed(() => pollData.value?.user_vote_option_ids ?? []);
const canVote = computed(() => pollData.value?.can_vote ?? false);

// Polling toutes les 5 secondes pour les résultats en direct
usePolling(fetchNow);

// Vote
const selectedOptionIds = ref([]);
const voteError = ref(null);
const voteLoading = ref(false);
const isChangingVote = ref(false); // AJOUTÉ : pour savoir si on est en train de modifier

const voted = computed(() => userVoteIds.value.length > 0);

// Quand les données du sondage arrivent, on pré-remplit les choix de l'utilisateur
watch(
    userVoteIds,
    (newIds) => {
        if (newIds.length > 0 && selectedOptionIds.value.length === 0) {
            selectedOptionIds.value = [...newIds];
        }
    },
    { immediate: true },
);

function toggleOption(optionId) {
    if (!poll.value.allow_multiple_choices) {
        selectedOptionIds.value = [optionId];
        return;
    }
    const idx = selectedOptionIds.value.indexOf(optionId);
    if (idx === -1) selectedOptionIds.value.push(optionId);
    else selectedOptionIds.value.splice(idx, 1);
}

function submitVote() {
    voteError.value = null;
    voteLoading.value = true;
    fetchApi({
        url: `polls/${props.token}/vote`,
        data: { option_ids: selectedOptionIds.value },
    })
        .then(() => {
            isChangingVote.value = false;
            fetchNow();
        })
        .catch((err) => {
            voteError.value = err?.data?.message || "Erreur lors du vote.";
        })
        .finally(() => {
            voteLoading.value = false;
        });
}

function startChangeVote() {
    isChangingVote.value = true;
}

// Résultats
const totalVotes = computed(() => {
    if (!poll.value?.options) return 0;
    return poll.value.options.reduce((sum, o) => sum + (o.votes_count ?? 0), 0);
});

function getPercent(option) {
    if (totalVotes.value === 0) return 0;
    return Math.round(((option.votes_count ?? 0) / totalVotes.value) * 100);
}

const isPollEnded = computed(() => {
    if (!poll.value?.ends_at) return false;
    return new Date(poll.value.ends_at) < new Date();
});

const showResults = computed(() => {
    if (!poll.value) return false;
    if (props.userId && (voted.value || isPollEnded.value)) return true;
    if (!props.userId && poll.value.results_public) return true;
    return false;
});
</script>

<template>
    <main style="max-width: 640px; margin: 2rem auto; padding: 1rem">
        <nav v-if="userId" style="margin-bottom: 2rem">
            <a
                href="/polls/dashboard"
                style="color: #4f46e5; text-decoration: none"
                >← Retour à mes sondages</a
            >
        </nav>

        <p v-if="loading">Chargement du sondage...</p>
        <p v-else-if="error" style="color: red">
            Sondage introuvable ou erreur.
        </p>

        <template v-else-if="poll">
            <h1>{{ poll.title || poll.question }}</h1>
            <p v-if="poll.title">
                <em>{{ poll.question }}</em>
            </p>

            <p v-if="isPollEnded" style="color: orange; font-weight: bold">
                Ce sondage est terminé, il n'est plus possible de voter.
            </p>

            <!-- Formulaire de vote (affiché si pas voté OU si on est en train de modifier) -->
            <section
                v-if="canVote && (!voted || isChangingVote) && !isPollEnded"
            >
                <h2>{{ isChangingVote ? "Modifier mon vote" : "Voter" }}</h2>
                <p v-if="voteError" style="color: red">{{ voteError }}</p>
                <div
                    v-for="option in poll.options"
                    :key="option.id"
                    style="margin-bottom: 0.5rem"
                >
                    <label>
                        <input
                            :type="
                                poll.allow_multiple_choices
                                    ? 'checkbox'
                                    : 'radio'
                            "
                            :value="option.id"
                            :checked="selectedOptionIds.includes(option.id)"
                            @change="toggleOption(option.id)"
                        />
                        {{ option.label }}
                    </label>
                </div>
                <button
                    @click="submitVote"
                    :disabled="voteLoading || selectedOptionIds.length === 0"
                >
                    {{
                        voteLoading
                            ? "Envoi..."
                            : isChangingVote
                              ? "Mettre à jour mon vote"
                              : "Soumettre mon vote"
                    }}
                </button>
                <button
                    v-if="isChangingVote"
                    @click="isChangingVote = false"
                    style="background: gray; margin-left: 0.5rem"
                >
                    Annuler
                </button>
            </section>

            <!-- Déjà voté -->
            <div v-else-if="voted && !isPollEnded" style="margin-bottom: 2rem">
                <p
                    style="
                        color: green;
                        display: inline-block;
                        margin-right: 1rem;
                    "
                >
                    ✅ Vous avez déjà voté.
                </p>
                <button
                    v-if="poll.allow_vote_change"
                    @click="startChangeVote"
                    style="background: #10b981"
                >
                    Modifier mon vote
                </button>
            </div>

            <p v-else-if="!userId && !poll.results_public">
                <a :href="loginUrl">Connectez-vous</a> pour voter.
            </p>

            <!-- Résultats -->
            <section v-if="showResults" style="margin-top: 2rem">
                <h2>
                    Résultats
                    <small style="font-size: 0.8em; color: gray"
                        >({{ totalVotes }} vote{{
                            totalVotes > 1 ? "s" : ""
                        }})</small
                    >
                </h2>
                <div
                    v-for="option in poll.options"
                    :key="option.id"
                    style="margin-bottom: 1rem"
                >
                    <div style="display: flex; justify-content: space-between">
                        <span>{{ option.label }}</span>
                        <span>{{ getPercent(option) }}%</span>
                    </div>
                    <div
                        style="
                            background: #e0e0e0;
                            border-radius: 4px;
                            height: 20px;
                        "
                    >
                        <div
                            :style="{
                                width: getPercent(option) + '%',
                                background: '#4f46e5',
                                height: '100%',
                                borderRadius: '4px',
                                transition: 'width 0.4s ease',
                            }"
                        ></div>
                    </div>
                    <small
                        >{{ option.votes_count ?? 0 }} vote{{
                            (option.votes_count ?? 0) > 1 ? "s" : ""
                        }}</small
                    >
                </div>
            </section>

            <p v-else-if="!userId">
                Les résultats de ce sondage ne sont pas publics.
            </p>
        </template>
    </main>
</template>

<style>
* {
    box-sizing: border-box;
}
body {
    margin: 0;
    font-family: system-ui, sans-serif;
    background: #f9fafb;
}
button {
    padding: 0.5rem 1rem;
    cursor: pointer;
    background: #4f46e5;
    color: white;
    border: none;
    border-radius: 4px;
}
button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
h1 {
    margin-bottom: 0.25rem;
}
</style>
