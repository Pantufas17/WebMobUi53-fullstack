<script setup>
/*
cest le composant racine de l apllication dashboard, cest lui qui 
est monté par poll-dashboard.js dans la page blade.
cest genre le maestro, il va afficher le header, gere a nav entre les pages
et passe les eveenemnts entre les composants.

il y avait la base deja avec le truc du prf mais du coup apres moi j ai 
refait et complete pour integrer du coup tout ce qui est useHashRoute,
TheHeader, KeepAlive etc..., un peu dans la meme idée qu on avait fait 
dans le TP1 en cours.


Il ne contient pas du coup la logique métier, il gère suelement la navigation
entre les pages (dashboard, pollEditorPage), via useHashRoute.
*/
import TheHeader from "./components/TheHeader.vue";
import { routes } from "./stores/routes";
import { useHashRoute } from "./composables/useHashRoute";

const props = defineProps({
    loginUrl: { type: String, default: null },
});

/*
useHAsRoute lit le has de l url et retourne le composant Vue correspondant
quil doit afficher
navigateTo permet de changer de page en modifiant le widow.location.hash
cest deja le composable qu il y avait sur le code du prof
*/

const { currentComponent, currentRoute, navigateTo } = useHashRoute(routes);
</script>

<template>
    <TheHeader title="Mes sondages" />
    <main>
        <Transition mode="out-in">
            <KeepAlive>
                <component
                    :is="currentComponent"
                    :key="currentRoute.hash"
                    :loginUrl="props.loginUrl"
                    @navigate="navigateTo"
                />
            </KeepAlive>
        </Transition>
    </main>
</template>

<style>
* {
    box-sizing: border-box;
}
body {
    margin: 0;
    font-family: system-ui, sans-serif;
    background: #f5f5f5;
}
main {
    padding: 1.5rem;
}
.v-enter-active,
.v-leave-active {
    transition: opacity 0.15s ease;
}
.v-enter-from,
.v-leave-to {
    opacity: 0;
}
</style>
