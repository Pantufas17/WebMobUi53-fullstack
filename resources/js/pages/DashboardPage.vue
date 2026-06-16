<script setup>
/*
page principale du dasboars, genre ce que le user voir en premier quand il 
arrive sur /polls/dashboard. Cest la page qui affiche la liste de tous ses sondages
avec les boutons de modifier, supprimer.
La base etait fournie par le prof, mais le fichier n etati tout a fait complet.

Elle communique avec son parent AppPollDashboard via l evenement navigate
pour du coup changer de page (aller vers #create ou #edit)
*/

import { onActivated, watch } from "vue";
import { useFetchApi } from "../composables/useFetchApi";
import { setCurrentPoll } from "../stores/currentPoll";

/*
declaration du coup de levenemt navigate 
et que du coup le parent AppPollDashboar ecoute cet evenement pour changer
de page du coup via le useHashRoute
*/
const emit = defineEmits(["navigate"]);

const props = defineProps({
    loginUrl: { type: String, default: null },
});

/*
du coup la le fetchApi est le composable qui etati fournir par le prof pour 
faire des appels API.
Le fetchApiToRef retourne des refs reactives (data, error,etc..) qui se mettent
a jour automatiquement quand la requete se termine

fetchNow permet de relancer le fetch manuellement.
*/
const { fetchApiToRef, fetchApi } = useFetchApi();
const {
    data: polls, //la liste des sondages retournés par GET /api/vi/polls
    error,
    loading,
    fetchNow, //la fameuse fonction pour refecther les sondages
} = fetchApiToRef({ url: "polls/" });

/*
si le fetch retourne une erreur 401 (non authentifié) on le redirige 
du coup vers la page de login pour qu il puisse se log et comprendre qu il faut un compte
*/
function handleError(err) {
    if (!err) return;
    if (err?.status === 401) window.location.href = props.loginUrl;
}

watch(error, handleError);

/*
onActivated se declanche chaque fois qu on revient sur cette page
j ai fait onActivated et pas onMounted parce que cest une page nest pas detruite
ni recree a chaque navigation, du coup onMounted ne se declencherait
qu une seule fois au chargement de l app.
Et du coup avec onActivated ca declanhce a chaque fois qu on revient sur la page
ce qui permet du coup d avoir des donnes a jour et fraiches apres une creation 
ou genre une modif
*/
onActivated(() => fetchNow());

/*
la fonction pour executer une suppression d un sondage apres confirmation
j appelle DELETE /api/vi/polls/{id} pour que l affichage se mette a jour
sans devoir recharger la page apres l action de suppression
*/
function deletePoll(id) {
    if (!confirm("Supprimer ce sondage ?")) return;
    fetchApi({ url: `polls/${id}`, method: "DELETE" })
        .then(() => fetchNow())
        .catch((err) => console.error(err));
}

/*
la focntion qui preparel edition dun poll
1. Stocke le sondage selectionne dans le store de currentPoll
2. navigue vers du coup #edit via l eveneemnt navigate que j ai fait, et 
pollEditorPPage va lire le currentPoll pour pre remplir du coup le formulaire
*/
function editPoll(poll) {
    setCurrentPoll(poll);
    emit("navigate", "#edit");
}
</script>

<template>
    <div>
        <!--
        JE ME SUIS ARRETE LA-->
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
        <ul v-else-if="polls" style="padding: 0">
            <li
                v-for="poll in polls"
                :key="poll.id"
                style="
                    margin-bottom: 1rem;
                    border: 1px solid #ddd;
                    padding: 1rem;
                    border-radius: 8px;
                    list-style: none;
                    background: white;
                "
            >
                <div
                    style="
                        font-weight: bold;
                        font-size: 1.1rem;
                        margin-bottom: 0.5rem;
                    "
                >
                    {{ poll.question }}
                </div>

                <div
                    style="margin-bottom: 1rem; color: #666; font-size: 0.9rem"
                >
                    Lien de vote :
                    <a
                        :href="'/polls/' + poll.secret_token"
                        target="_blank"
                        style="color: #4f46e5; text-decoration: underline"
                    >
                        Ouvrir le sondage
                    </a>
                </div>

                <div>
                    <button @click="editPoll(poll)">Modifier</button>
                    <button
                        @click="deletePoll(poll.id)"
                        style="margin-left: 0.5rem; color: red"
                    >
                        Supprimer
                    </button>
                </div>
            </li>
        </ul>

        <p v-else>Aucun sondage.</p>
    </div>
</template>

<style scoped>
button {
    padding: 0.5rem 1rem;
    cursor: pointer;
    margin-left: 0.5rem;
}
</style>
