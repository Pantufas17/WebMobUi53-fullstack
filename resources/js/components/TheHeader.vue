<script setup>
/*
cest le composant Vue reutilisable qui affiche le header du coup de l app
la barre en haut de la page
*/

/*
je fait un composant header parce qu il sera reutilisable dans les autre
composants de l app et comme ca plutot que de l ecrire dans directement dans
AppPollDashbaord
*/

/*
il recoit le titre en prop pour afficher dans le header => du coup Sondages
Je fais avec un prop plutot qu un texte en dur pour que le composant soit
du coup reutilisable, genre si on voulair le meme header avec un autre titre
ailleurs, on peut du coup juster changer la prop.
*/
defineProps({
    title: { type: String, default: "Sondages" },
});
</script>

<template>
    <header>
        <h1>{{ title }}</h1>
    </header>
</template>

<style scoped>
/*
    style scoped pour que du coup ces styles ne s applique que dans ce composant
    ils ne debordent pas sur les autres composants et on evite de conflits CSS
    */
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background-color: #1e1b4b;
    color: white;
}
h1 {
    margin: 0;
    font-size: 1.25rem;
}
</style>
