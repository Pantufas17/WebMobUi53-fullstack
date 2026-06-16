/*
un mini store qui partage un etat entre plusieurs composants Vue.
il stocke le sondage actuellement selectionne pour edition
Cest genre le lien entre DashboardPage (qui selectionne un sondage)
et PollEditorPage (qui l'affiche dans le formulaire)

je fais un store parce DasboardPage et PollEditorPage sont deux composants
qui sont rendu directement par Laravel (routes/web.php)
donc ils sont pas enfants l'un de l'autre
Du coup pas de props, pas de $parent, rien.
Le store est la solution la plus simple pour partager cet etat.

*/

import { ref, readonly } from "vue";
/*
du coup mini store au lieu d un props parce que du coup justement cest pas genre
relation parent enfant entre Dashboard et PollEditor, et du coup pas de props
parce que justmenet si jai compris les props fonctionnen avec une relation
parent enfant.
Et du coup le store permet a n importe quel composant de lire ou modifier
ce sondage sans passer par les parents.
*/

/*
la ref est privé, elle ne peut pas etre modifiie directemetn depuis le code
genre depuis l exterieur de ce fichier
*/
const _currentPoll = ref(null);

/*
exportation de currentPoll en readonly, les composants peuvent la lire mais pas l ecrire
directement, cest genre mode encapsulation comme ca on controle comment letat
peut etre modifié.
Genre Null = on est genre en mode creation; etobjet poll (en orange) = on est en mode edition
*/
export const currentPoll = readonly(_currentPoll);

/*
et du coup pour pouvoir vraiment modifier le sondage cest genre ici avec
l exportation de setCurrentPoll avec l objet poll, et non pas avec le
currentPoll qui est donc lui en readonly.
*/
export function setCurrentPoll(poll) {
    _currentPoll.value = poll;
}
