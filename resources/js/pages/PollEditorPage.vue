<script setup>
/*
page du formulaire du sondage du coup,
ca va servir a la fois pour creer un nouveau sondage mais aussi pour
modifier un qui existe deja.
Un seul composant pour deux usages, grace au store currentPoll.

Un seul composant pour create et edit, parce que en soit le formulaire il est
identique dans les deux cas, cest juste que un il envoie une requete POST (pour creer)
et un autre une requete PUT (pour du coup la modif).
et du coup si le currentPoll = null => bah mode creation dun nouveau sondage
et si le currentPoll != null => mode edition
*/
import { ref, computed, onMounted, onActivated } from "vue";
import { useFetchApi } from "../composables/useFetchApi";
import { currentPoll } from "../stores/currentPoll";

const emit = defineEmits(["navigate"]);
const { fetchApi } = useFetchApi();

/*
le computed qui va detecter si on est en mode edition ou creation.
retourne true si currentPoll contien un sondage (mode edition) et false si
currentPoll est null (mode creation).
Et du coup on utilise computed et pas ref car cest une valeur derivé d un 
etat reactif => elle se met a jour automatiquement.
*/
const isEdit = computed(() => currentPoll.value !== null);

/*
etats reactifs du formulaire, initialisés vides (mode creation par defaut)
*/
const title = ref("");
const question = ref("");
//minimum 2 options requises du coup pour voter ahah sinon cest jsute une option pour vote
const options = ref(["", ""]);
const isDraft = ref(true);
//par defaut le sondage est en mode brouillon
const allowMultiple = ref(false);
const resultsPublic = ref(false);
const allowVoteChange = ref(false);
//ici option de changer un vote
const duration = ref(null);
const error = ref(null);
const loading = ref(false);

/*
pre rempli du formulaire selon le mode 
=> du coup pour le mode d edition on reprends les valeurs du sondage stocke dans
le currentPoll
=> pour le mode creation on repart sur les valeurs par defaut et avec tout a zero vide

Fonction dans onMounted et onActivated car la page est dans un truc KeepAlive
et onMounted ne se declenche qu une fois, onActivated se declanche a chaque fois
qu on revient sur cette page.
*/
function prefill() {
    if (currentPoll.value) {
        //mode edition => du coup on copie les valeurs du sondage courant
        title.value = currentPoll.value.title ?? "";
        question.value = currentPoll.value.question;
        /*
        on extrait jsute les labels des options (pas les objets entiers)
        */
        options.value = currentPoll.value.options?.map((o) => o.label) ?? [
            "",
            "",
        ];
        isDraft.value = !!currentPoll.value.is_draft;
        allowMultiple.value = !!currentPoll.value.allow_multiple_choices;
        resultsPublic.value = !!currentPoll.value.results_public;
        allowVoteChange.value = !!currentPoll.value.allow_vote_change; // AJOUTÉ
        duration.value = currentPoll.value.duration ?? null;
        /*
        mode creation => on remet tout a zero
        */
    } else {
        title.value = "";
        question.value = "";
        options.value = ["", ""];
        isDraft.value = true;
        allowMultiple.value = false;
        resultsPublic.value = false;
        allowVoteChange.value = false;
        duration.value = null;
    }
}

onMounted(prefill);
onActivated(prefill);

/*
Ajoute une option vide a la lsite
*/
function addOption() {
    options.value.push("");
}

/*
supprime une option, on interdit la suppresion si on est deja a 2 options(minimum)
*/
function removeOption(index) {
    if (options.value.length > 2) options.value.splice(index, 1);
}

/*
sumet le formualire, crée ou modifie le sondage selon isEdit
on filtre les options vides avant d enovoyer
en cas de succes on revient sur le dashboard de base au debut
et si il y a une erreur on affiche un message derreur de l api
pour dire que du coup il y a eu une error
*/
function submit() {
    error.value = null;
    loading.value = true;

    /*
    URL et et methodes differentes selon le mode (PUT ou POST)
    */
    const url = isEdit.value ? `polls/${currentPoll.value.id}` : "polls/";
    const method = isEdit.value ? "PUT" : "POST";

    fetchApi({
        url,
        method,
        data: {
            title: title.value || null,
            question: question.value,
            options: options.value.filter((o) => o.trim() !== ""), //retire les
            //options vides avant denvoyer
            is_draft: isDraft.value,
            allow_multiple_choices: allowMultiple.value,
            results_public: resultsPublic.value,
            allow_vote_change: allowVoteChange.value,
            duration: duration.value ? Number(duration.value) : null,
        },
    })
        .then(() => emit("navigate", "#dashboard")) //retour au
        //dashboard apres un envoie avec succes

        //et apres du coup le message derreur
        .catch((err) => {
            error.value = err?.data?.message || "Erreur lors de la sauvegarde.";
        })
        .finally(() => {
            loading.value = false;
        });
}
</script>

<template>
    <div>
        <!--le titre change selon le mode
        si cest un nouveau sondage ou si on est entrain de modifier-->
        <h2>{{ isEdit ? "Modifier le sondage" : "Nouveau sondage" }}</h2>

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

                    <!--desactivé si on est deja a 2 options minimum requis-->
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
                ><br />
                <label
                    ><input v-model="allowVoteChange" type="checkbox" />
                    Autoriser le changement de vote</label
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
                {{
                    loading
                        ? "Sauvegarde..."
                        : isEdit
                          ? "Enregistrer"
                          : "Créer le sondage"
                }}
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
