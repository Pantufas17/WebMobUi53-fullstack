/*
cest la definition des routes de l app vue dashboard. cest un simple 
tableau qui associe un hash d url (#dashboard, #create, #edit) a un 
composantvue a afficher.
cest du coup composable useHashRoute (deja fournie) qui lit ce tableau pour savoir
que composant afficher selon le hash actuel.

je l ai créer ce fichier du coup pour centraliser la navigation, au lieu
davoir genre des v-if partout dans le template pour afficher tel ou tel
composant, on declare les routes ici une suele fois
cest genre un peu ce qu on avait utilisé pour le Tp1 en cours je crois.
*/

import DashboardPage from "../pages/DashboardPage.vue";
import PollEditorPage from "../pages/PollEditorPage.vue";

/*
chaque route associe un has d url a un composant Vue
cest le composable useHashRoute qui lit ce tableau et reotur le bon composant 
selon le widow.location.hash
*/
export const routes = [
    //page principal, liste tous les sondages de l'utilisateur
    { hash: "#dashboard", label: "Mes sondages", component: DashboardPage },
    { hash: "#create", label: "Nouveau sondage", component: PollEditorPage },
    { hash: "#edit", label: "Modifier", component: PollEditorPage },
];
