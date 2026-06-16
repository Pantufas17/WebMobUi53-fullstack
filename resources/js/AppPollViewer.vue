<script setup>
/*
Du coup le composant racine de la page de vote.
il va tout gerer (genre cherger le sondage via l api, afficher le formualire de vote,
enregistrer le vote, etc...)

contrairement au dashboard par exemple qui a plusieurs pages,
ici du coup tout est dans un seul composant car la pge de vote a un seul
objectif et que ca va etre de voter et de voir les resultats, cest tout
*/
import { ref, computed, watch } from "vue";
import { useFetchApi } from "./composables/useFetchApi";
import { usePolling } from "./composables/usePolling";

/*
props passées depuis la vue blade via data-props.
on voit le fameux token secret du sondage
on voit aussi loginURL avec l url de login pour rediriger les gens qui sont
pas encore connectés
on a aussi apres le userID, avec l id du user cette fois ci qui est connecté
*/
const props = defineProps({
    token: { type: String, required: true },
    loginUrl: { type: String, default: null },
    userId: { type: Number, default: null },
});

/*
fetchApiToRef va lancer du coup automatiquement GET /api/v1/polls/{token}
au montage et retourne des refs reactives qui se mettent a jour qunad la
reponse arrive.
L API va du coup retourner un object avec les 3 champs (poll, user_vote_option_ids
 et can_vote).
*/
const { fetchApiToRef, fetchApi } = useFetchApi();
const {
    data: pollData,
    error,
    loading,
    fetchNow,
} = fetchApiToRef({
    url: `polls/${props.token}`,
});

/*
computed pour extraire les donnes de pollData.
j ai utilisé computed parce que ces valeurs dependent de pollData
et du coup elles se recalculent automatiquementà chaque fois que pollData change
(genre apre un vote ou apres un tick du polling)
*/
const poll = computed(() => pollData.value?.poll ?? null);
const userVoteIds = computed(() => pollData.value?.user_vote_option_ids ?? []);
const canVote = computed(() => pollData.value?.can_vote ?? false);

/* Polling toutes les 5 secondes pour les résultats en direct
ca appelle fetchNow() automatiquement. usePolling est le composable
qui etait la deja du prof et il va créer du coup un setInterval et le 
nettoie automatiquement quand le composant est detruit (avec clearInterval)
Et tout ca va permettre d afficher les reusltats en temps reel sans que 
le user ait a recharger la apge.
*/
usePolling(fetchNow);

//etats reactifs pour le formualire de vote
const selectedOptionIds = ref([]);
const voteError = ref(null);
const voteLoading = ref(false);
/*
la du coup pour savoir si on est entrain de modifier son vote
et du coup si ce truc est true, on reaffiche le formulaire de vote meme
si le user a deja fait son vote.
*/
const isChangingVote = ref(false);

/*
computed => l user a vote si userVoteIds contient du coup au moins 1
j ai fait avec computed et pas ref car cest une valeur derivée de userVoteId.
*/
const voted = computed(() => userVoteIds.value.length > 0);

/*
du coup ce watch va surveiller userVoteIds => quand les donnes arrivent
(apres genre le premier fetch)
ca va pre coche les options deja choisis par le user
*/ watch(
    userVoteIds,
    (newIds) => {
        if (newIds.length > 0 && selectedOptionIds.value.length === 0) {
            selectedOptionIds.value = [...newIds];
        }
    },
    /*
    le immedaitre : true du coup => le watch sexecute aussi au montage, pas
    seulement du coup quand la valeur change.
    */
    { immediate: true },
);

/*
ca va gerer le clic sur une option
-> sondage a choix unieque : on remplace la selecrion (pas dajout)
->sondaga a choix multiple mtn : on toggle (ajjoute ou retire l option).

cest du coup la garantie du cote front de garantir l unicite de vote
mais le backend va quand meme verifier les votes
*/
function toggleOption(optionId) {
    if (!poll.value.allow_multiple_choices) {
        /*
        choix unique, on rempkace toute la selection par ce seul id
        */
        selectedOptionIds.value = [optionId];
        return;
    }
    /*
    cjoix multiple, qui fait donc ce toggle 
    */
    const idx = selectedOptionIds.value.indexOf(optionId);
    if (idx === -1) selectedOptionIds.value.push(optionId);
    else selectedOptionIds.value.splice(idx, 1);
}

/*
envoie le vote a l api via POST api/v1/polls/{token}/vote
et du coup en cas de succes, on quitte le mode "changement de vote" pour
du coup mettre a joru les resultats immediatement.
Et si du coup en cas d erreur, on va afficher le message d erreur
*/
function submitVote() {
    voteError.value = null;
    voteLoading.value = true;
    fetchApi({
        url: `polls/${props.token}/vote`,
        data: { option_ids: selectedOptionIds.value },
    })
        .then(() => {
            isChangingVote.value = false;
            //refetch immediat pour mettre a jour les resultats
            fetchNow();
        })
        .catch((err) => {
            voteError.value = err?.data?.message || "Erreur lors du vote.";
        })
        .finally(() => {
            voteLoading.value = false;
        });
}

/*
du coup pour mettre ce truc de activer le mode de pouvoir modifier son vote
*/
function startChangeVote() {
    isChangingVote.value = true;
}

/*
Resultats, du coup qui calcul le total des votes pour pouvoir calculer ensuite
les pourcentages
*/
const totalVotes = computed(() => {
    if (!poll.value?.options) return 0;
    return poll.value.options.reduce((sum, o) => sum + (o.votes_count ?? 0), 0);
});

/*
du coup maintenant ca va calculer les pourcentages suite au calcul 
juste avenat du calcul total des votes.
Ca arrondi les pourcentages a l entier le plus proche pour eviter d avoir
des trucs a virgule infini.
Et aussi si du coup ca va retourner 0 si personne n as encore voté
je hardcode le retour de 0 pour eviter la division par 0 => erreure
*/
function getPercent(option) {
    if (totalVotes.value === 0) return 0;
    return Math.round(((option.votes_count ?? 0) / totalVotes.value) * 100);
}

/*
computed => vrai si le sondage a une date de fin depassée deja
ca compare ends_at(qui est stocke dans la bd) avec la date actuelle
Et du coup si la date ends_at est null, le sondage n as pas de limite de temps => false
*/
const isPollEnded = computed(() => {
    if (!poll.value?.ends_at) return false;
    return new Date(poll.value.ends_at) < new Date();
});

/*
computed => est ce qu on doit du coup afficher les reusltats??
-du coup le user connecté et a voté ou sondage terminé => OUI
-user pas connecté et resultats public => OUI
- dans tous les autres cas du coup on affiche pas => NON
*/
const showResults = computed(() => {
    if (!poll.value) return false;
    if (props.userId && (voted.value || isPollEnded.value)) return true;
    if (!props.userId && poll.value.results_public) return true;
    return false;
});
</script>

<template>
    <main style="max-width: 640px; margin: 2rem auto; padding: 1rem">
        <!--lien pour retourner au dashboard si on est connecté -->
        <nav v-if="userId" style="margin-bottom: 2rem">
            <a
                href="/polls/dashboard"
                style="color: #4f46e5; text-decoration: none"
                >← Retour à mes sondages</a
            >
        </nav>
        <!--Etats de chargemtn et d erreur-->
        <p v-if="loading">Chargement du sondage...</p>
        <p v-else-if="error" style="color: red">
            Sondage introuvable ou erreur.
        </p>

        <template v-else-if="poll">
            <!--titre et question du poll-->
            <h1>{{ poll.title || poll.question }}</h1>
            <p v-if="poll.title">
                <em>{{ poll.question }}</em>
            </p>

            <!--message si le sondage est terminé-->
            <p v-if="isPollEnded" style="color: orange; font-weight: bold">
                Ce sondage est terminé, il n'est plus possible de voter.
            </p>

            <!--
            formulaire de vote affiché si :
            -user peut voter (il est conncté, le sondage est actif et pas expiré)
            -et si le user n as pas encote voté
            -et que le sondage nest pas encore terminé
            -->
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
                        <!--
                        type radio si le choix unique, checkbox si choix multiple
                        pour commprendre si cest choix unique ou multiple.
                        
                        le toggleOption va du ucoup gerer la logique de selection-->
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

                <!--annuler les modifications du vote.-->
                <button
                    v-if="isChangingVote"
                    @click="isChangingVote = false"
                    style="background: gray; margin-left: 0.5rem"
                >
                    Annuler
                </button>
            </section>

            <!--messge deja vote avec aussi le bouton de modificaiton sil vuet changer -->
            <div v-else-if="voted && !isPollEnded" style="margin-bottom: 2rem">
                <p
                    style="
                        color: green;
                        display: inline-block;
                        margin-right: 1rem;
                    "
                >
                    Vous avez déjà voté.
                </p>

                <!--messa pour les gens pas conncetl si les resultats sont pas affichés publiquement-->
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

            <!--resultas avcv les graphqiue en barre la -->
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

                    <!--la barre de profression-->
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
                                //animation sypmpa  pour que ca soit fluide lors
                                //des mises a jour => ca aidé par IA pour comprendre comment
                                //je pouvait faire un truc assez propre
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

            <!--user anonyme pas connecté => resultats non publics-->
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
